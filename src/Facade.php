<?php

namespace WebChemistry\Forms\Controls\Wizard;

use Nette\Object;
use WebChemistry\Forms\Controls\IWizard;
use Nette\Forms\Form;

class Facade extends Object {

	/** @var IWizard */
	private $wizard;

	/** @var int */
	private $steps = NULL;

	/**
	 * @param IWizard $wizard
	 */
	public function __construct(IWizard $wizard) {
		$this->wizard = $wizard;
	}

	/**
	 * @param bool $asArray
	 * @return array|ArrayHash
	 */
	public function getValues($asArray = FALSE) {
		return $this->wizard->getValues($asArray);
	}

	/**
	 * @return Form
	 */
	public function getCurrentComponent() {
		return $this->wizard->create();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function useLink($step) {
		return !$this->isDisabled($step) && !$this->isCurrent($step);
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isCurrent($step) {
		return $this->getCurrentStep() === $step;
	}

	/**
	 * @return boolean
	 */
	public function isSuccess() {
		return $this->wizard->isSuccess();
	}

	/**
	 * @return int
	 */
	public function getTotalSteps() {
		if ($this->steps === NULL) {
			for ($iterator = 1; $this->wizard->getComponent('step' . $iterator, FALSE); $iterator++);
			$this->steps = $iterator - 1;
		}

		return $this->steps;
	}

	/**
	 * @return array
	 */
	public function getSteps() {
		return range(1, $this->getTotalSteps());
	}

	/**
	 * @return mixed
	 */
	public function render() {
		return $this->wizard->render();
	}

	/**
	 * @return int
	 */
	public function getCurrentStep() {
		return $this->wizard->getCurrentStep();
	}

	/**
	 * @return int
	 */
	public function getLastStep() {
		return $this->wizard->getLastStep();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isActive($step) {
		return $step === $this->getCurrentStep();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isDisabled($step) {
		return $step > $this->getLastStep();
	}

}
