<?php

namespace WebChemistry\Forms\Controls;

use Nette;
use Nette\ComponentModel\Container;
use Nette\Http\Session;
use WebChemistry\Forms\Form;
use WebChemistry\Forms\IProvider;

class Wizard extends Container implements IWizard {

	/** @var Session */
	private $session;

	/** @var \DateTime|string|int */
	protected $expiration = '+ 20 minutes';

	/** @var array */
	public $onSuccess = array();

	/** @var IProvider */
	private $provider;

	/** @var bool */
	private $isSuccess = FALSE;

	public function __construct(Session $session) {
		$this->session = $session;

		$this->monitor('Nette\Application\IPresenter');
	}

	public function setProvider(IProvider $provider) {
		$this->provider = $provider;

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
	 * @return Nette\Http\SessionSection
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
	 * @return array|Nette\Utils\ArrayHash
	 */
	public function getValues($asArray = FALSE) {
		if ($asArray) {
			return (array) $this->getSection()->values;
		} else {
			return Nette\Utils\ArrayHash::from((array) $this->getSection()->values);
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
		$lastStep = $this->getSection()->lastStep ? : 1;

		if ($lastStep >= $step && $step > 0 && $this->getComponent($step, FALSE)) {
			$this->getSection()->currentStep = $step;
		}

		return $this;
	}

	/**
	 * @return Form
	 */
	protected function getForm() {
		if ($this->provider) {
			$form = $this->provider->create();
		} else {
			$form = new Form;
		}

		$form->onSubmit[] = array($this, 'submitStep');

		return $form;
	}

	/**
	 * @param Form $form
	 */
	public function submitStep(Form $form) {
		$submitName = $form->getSubmittedName();

		if ($submitName === self::PREV_SUBMIT_NAME) {
			$currentStep = $this->getCurrentStep();

			$this->getSection()->currentStep = $currentStep - 1;
		} else if ($submitName === self::NEXT_SUBMIT_NAME) {
			if ($form->isValid()) {
				$this->getSection()->values = array_merge((array) $this->getSection()->values, $form->getValues(TRUE));

				$currentStep = $this->getCurrentStep();

				$this->getSection()->lastStep = $this->getSection()->currentStep = $currentStep + 1;
			}
		} else if ($submitName === self::FINISH_SUBMIT_NAME) {
			if (!$form->isValid()) {
				return;
			}

			if ($this->getSection()->values === NULL) {
				return;
			}

			$this->getSection()->values = array_merge((array) $this->getSection()->values, $form->getValues(TRUE));

			$this->isSuccess = TRUE;

			$this->finish();

			$this->onSuccess($this);

			$this->resetSection();
		}
	}

	/**
	 * @return string
	 */
	public function render() {
		return $this->create()->render();
	}

	/**
	 * @param string $name
	 * @return Form
	 */
	public function create($step = NULL) {
		/** @var Form $form */
		$form = $this->getComponent('step' . ($step !== NULL ? $step : $this->getCurrentStep()));

		$form->setValues($this->getValues(TRUE));

		foreach ($form->getComponents(FALSE, 'Nette\Forms\Controls\SubmitButton') as $control) {
			if ($control->name === self::PREV_SUBMIT_NAME) {
				$control->getControlPrototype()->data('novalidate', '');
			}
		}

		return $form;
	}

	/**
	 * Component factory. Delegates the creation of components to a createComponent<Name> method.
	 * @param  string      component name
	 * @return Form  the created component (optionally)
	 */
	protected function createComponent($name) {
		$ucname = ucfirst($name);
		$method = 'create' . $ucname;
		if ($ucname !== $name && method_exists($this, $method) && $this->getReflection()->getMethod($method)->getName() === $method) {
			$component = $this->$method($name);
			if (!$component instanceof Nette\ComponentModel\IComponent && !isset($this->components[$name])) {
				$class = get_class($this);
				throw new Nette\UnexpectedValueException("Method $class::$method() did not return or create the desired component.");
			}
			return $component;
		}
	}
}