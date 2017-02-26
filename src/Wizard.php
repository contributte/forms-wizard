<?php

namespace WebChemistry\Forms\Controls;

use Nette\ComponentModel\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Session;
use Nette\Application\UI\Form;
use Nette\Http\SessionSection;
use Nette\UnexpectedValueException;
use Nette\Utils\ArrayHash;
use WebChemistry\Forms\Factory\IFactory;
use Nette\Forms;

class Wizard extends Container implements IWizard {

	/** @var Session */
	private $session;

	/** @var \DateTime|string|int */
	protected $expiration = '+ 20 minutes';

	/** @var array */
	public $onSuccess = [];

	/** @var IFactory */
	private $factory;

	/** @var bool */
	private $isSuccess = FALSE;

	/**
	 * @param Session $session
	 */
	public function __construct(Session $session) {
		$this->session = $session;
	}

	/**
	 * @param IFactory $factory
	 * @return self
	 */
	public function setFactory(IFactory $factory) {
		$this->factory = $factory;

		return $this;
	}

	protected function finish() {}

	/**
	 * @return bool
	 */
	public function isSuccess() {
		return $this->isSuccess;
	}

	/**
	 * @return SessionSection
	 */
	protected function getSection() {
		return $this->session->getSection('wizard' . $this->getName())->setExpiration($this->expiration);
	}

	private function resetSection() {
		$this->getSection()->remove();
	}

	/**
	 * @return int
	 */
	public function getCurrentStep() {
		return $this->getSection()->currentStep ? : 1;
	}

	/**
	 * @param bool $asArray
	 * @return array|ArrayHash
	 */
	public function getValues($asArray = FALSE) {
		if ($asArray) {
			return (array) $this->getSection()->values;
		} else {
			return ArrayHash::from((array) $this->getSection()->values);
		}
	}

	/**
	 * @return int
	 */
	public function getLastStep() {
		return $this->getSection()->lastStep ? : 1;
	}

	/**
	 * @param $step
	 * @return Wizard
	 */
	public function setStep($step) {
		if ($this->getLastStep() >= $step && $step > 0 && $this->getComponent($step, FALSE)) {
			$this->getSection()->currentStep = $step;
		}

		return $this;
	}

	/**
	 * @return Form
	 */
	protected function createForm() {
		if ($this->factory) {
			$form = $this->factory->create();
		} else {
			$form = new Form();
		}

		return $form;
	}

	/**
	 * @return Form
	 * @deprecated
	 */
	protected function getForm() {
		return $this->createForm();
	}

	/**
	 * @param SubmitButton $button
	 */
	public function submitStep(SubmitButton $button) {
		$form = $button->getForm();
		$submitName = $button->getName();

		if ($submitName === self::PREV_SUBMIT_NAME) {
			$currentStep = $this->getCurrentStep();
			$this->getSection()->currentStep = $currentStep - 1;

		} else if ($submitName === self::NEXT_SUBMIT_NAME && $form->isValid()) {
			$this->merge($form->getValues(TRUE));
			$this->getSection()->lastStep = $this->getSection()->currentStep = $this->getCurrentStep() + 1;

		} else if ($submitName === self::FINISH_SUBMIT_NAME && $form->isValid() && $this->getSection()->values !== NULL) {
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
	private function merge(array $array) {
		$this->getSection()->values = array_merge((array) $this->getSection()->values, $array);
	}

	public function render() {
		$this->create()->render();
	}

	/**
	 * @param string $step
	 * @return Form
	 */
	public function create($step = NULL) {
		/** @var Form $form */
		$form = $this->getComponent('step' . ($step !== NULL ? $step : $this->getCurrentStep()));
		$form->setValues($this->getValues(TRUE));

		return $form;
	}

	/**
	 * Control factory. Delegates the creation of components to a createComponent<Name> method.
	 *
	 * @param  string $name component name
	 * @return Form the created component (optionally)
	 */
	protected function createComponent($name) {
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
	private function applyCallbacksToButtons(Forms\Form $form) {
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
