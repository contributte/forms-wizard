<?php

namespace WebChemistry\Forms\Controls;

use Nette\ComponentModel\Container;
use Nette\ComponentModel\IComponent;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\Application\UI\Form;
use Nette\Http\SessionSection;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use WebChemistry\Forms\Controls\Wizard\IFormFactory;
use Nette\Forms;

class Wizard extends Container implements IWizard {

	private const CURRENT_STEP = 'currentStep';
	private const VALUES = 'values';
	private const LAST_STEP = 'lastStep';

	/** @var Session */
	private $session;

	/** @var \DateTime|string|int */
	protected $expiration = '+ 20 minutes';

	/** @var array */
	public $onSuccess = [];

	/** @var IFormFactory */
	private $factory;

	/** @var bool */
	private $isSuccess = FALSE;

	/**
	 * @param Session $session
	 */
	public function __construct(Session $session) {
		parent::__construct();

		$this->session = $session;
	}

	/**
	 * @param IFormFactory $factory
	 * @return IWizard
	 */
	public function setFactory(IFormFactory $factory): IWizard {
		$this->factory = $factory;

		return $this;
	}

	protected function finish(): void {}

	/**
	 * @return bool
	 */
	public function isSuccess(): bool {
		return $this->isSuccess;
	}

	/**
	 * @return SessionSection
	 */
	protected function getSection(): SessionSection {
		return $this->session->getSection('wizard' . $this->getName())->setExpiration($this->expiration);
	}

	private function resetSection(): void {
		$this->getSection()->remove();
	}

	/**
	 * @return int
	 */
	public function getCurrentStep(): int {
		return $this->getSection()[self::CURRENT_STEP]? : 1;
	}

	/**
	 * @param bool $asArray
	 * @return array|ArrayHash
	 */
	public function getValues(bool $asArray = FALSE) {
		if ($asArray) {
			return (array) $this->getSection()[self::VALUES];
		} else {
			return ArrayHash::from((array) $this->getSection()[self::VALUES]);
		}
	}

	/**
	 * @return int
	 */
	public function getLastStep(): int {
		return $this->getSection()[self::LAST_STEP] ? : 1;
	}

	/**
	 * @param int $step
	 * @return IWizard
	 */
	public function setStep(int $step): IWizard {
		if ($this->getLastStep() >= $step && $step > 0 && $this->getComponent("step" . $step, FALSE)) {
			$this->getSection()[self::CURRENT_STEP] = $step;
		}

		return $this;
	}

	/**
	 * @return Form
	 */
	protected function createForm(): Form {
		return $this->factory ? $this->factory->create() : new Form();
	}

	/**
	 * @param SubmitButton $button
	 */
	public function submitStep(SubmitButton $button): void {
		$form = $button->getForm();
		$submitName = $button->getName();

		if ($submitName === self::PREV_SUBMIT_NAME) {
			$currentStep = $this->getCurrentStep();
			$this->getSection()[self::CURRENT_STEP] = $currentStep - 1;

		} else if ($submitName === self::NEXT_SUBMIT_NAME && $form->isValid()) {
			$this->merge($form->getValues(TRUE));
			$this->getSection()[self::LAST_STEP] = $this->getSection()[self::CURRENT_STEP] = $this->getCurrentStep() + 1;

		} else if ($submitName === self::FINISH_SUBMIT_NAME && $form->isValid() && $this->getSection()[self::VALUES] !== NULL) {
			$this->merge($form->getValues(TRUE));

			$this->isSuccess = TRUE;
			$this->finish();
			foreach ($this->onSuccess as $callback) {
				$callback($this);
			}
			$this->resetSection();
		}
	}

	/**
	 * @param array $array
	 */
	private function merge(array $array): void {
		$this->getSection()[self::VALUES] = array_merge((array) $this->getSection()[self::VALUES], $array);
	}

	public function render() {
		$this->create()->render();
	}

	/**
	 * @param string $step
	 * @return Form
	 */
	public function create(string $step = NULL): Form {
		/** @var Form $form */
		$form = $this->getComponent('step' . ($step !== NULL ? $step : $this->getCurrentStep()));
		$form->setValues($this->getValues(TRUE));

		return $form;
	}

	/**
	 * Control factory. Delegates the creation of components to a createComponent<Name> method.
	 *
	 * @param string $name component name
	 * @return IComponent|NULL the created component (optionally)
	 */
	protected function createComponent($name): ?IComponent {
		$ucname = ucfirst($name);
		$method = 'create' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && (new \ReflectionMethod($this, $method))->getName() === $method) {
			$component = $this->$method($name);
			if (!$component instanceof Forms\Form && !isset($this->components[$name])) {
				$class = get_class($this);
				throw new UnexpectedValueException("Method $class::$method() did not return or create Nette\\Forms\\Form.");
			}
			$this->applyCallbacksToButtons($component);

			return $component;
		}

		return NULL;
	}

	/**
	 * @param Forms\Form $form
	 */
	private function applyCallbacksToButtons(Forms\Form $form): void {
		/** @var SubmitButton $control */
		foreach ($form->getComponents(FALSE, SubmitButton::class) as $control) {
			if (!in_array($control->getName(), [self::FINISH_SUBMIT_NAME, self::NEXT_SUBMIT_NAME, self::PREV_SUBMIT_NAME])) {
				continue;
			}

			$control->onClick[] = [$this, 'submitStep'];
			$control->onInvalidClick[] = [$this, 'submitStep'];
			if ($control->getName() === self::PREV_SUBMIT_NAME) {
				$control->setValidationScope(FALSE);
			}
		}
	}

}
