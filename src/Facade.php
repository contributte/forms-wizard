<?php

namespace WebChemistry\Forms\Controls\Wizard;

use Nette;
use WebChemistry\Forms\Controls\IWizard;
use WebChemistry\Forms\Form;

class Facade extends Nette\Object {

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
		if ($this->steps !== NULL) {
			return $this->steps;
		}

		$isEnd = FALSE;
		$iterator = 1;

		while (!$isEnd) {
			$component = $this->wizard->getComponent('step' . $iterator, FALSE);

			if ($component) {
				$iterator++;
			} else {
				$isEnd = TRUE;
			}
		}

		return $this->steps = $iterator - 1;
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