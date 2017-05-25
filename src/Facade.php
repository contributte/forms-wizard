<?php

declare(strict_types=1);

namespace WebChemistry\Forms\Controls\Wizard;

use Nette\Utils\ObjectMixin;
use WebChemistry\Forms\Controls\IWizard;
use Nette\Forms\Form;

class Facade {

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
	public function getValues(bool $asArray = FALSE) {
		return $this->wizard->getValues($asArray);
	}

	/**
	 * @return Form
	 */
	public function getCurrentComponent(): Form {
		return $this->wizard->create();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function useLink(int $step): bool {
		return !$this->isDisabled($step) && !$this->isCurrent($step);
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isCurrent(int $step): bool {
		return $this->getCurrentStep() === $step;
	}

	/**
	 * @return bool
	 */
	public function isSuccess(): bool {
		return $this->wizard->isSuccess();
	}

	/**
	 * @return int
	 */
	public function getTotalSteps(): int {
		if ($this->steps === NULL) {
			for ($iterator = 1; $this->wizard->getComponent('step' . $iterator, FALSE); $iterator++);
			$this->steps = $iterator - 1;
		}

		return $this->steps;
	}

	/**
	 * @return array
	 */
	public function getSteps(): array {
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
	public function getCurrentStep(): int {
		return $this->wizard->getCurrentStep();
	}

	/**
	 * @return int
	 */
	public function getLastStep(): int {
		return $this->wizard->getLastStep();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isActive(int $step): bool {
		return $step === $this->getCurrentStep();
	}

	/**
	 * @param int $step
	 * @return bool
	 */
	public function isDisabled(int $step): bool {
		return $step > $this->getLastStep();
	}

	public function &__get(string $name) {
		return ObjectMixin::get($this, $name);
	}

	public function __set(string $name, $value) {
		ObjectMixin::strictSet(self::class, $value);
	}

	public function __call(string $name, $args) {
		ObjectMixin::strictCall(self::class, $args);
	}

	public static function __callStatic(string $name, $args) {
		ObjectMixin::strictStaticCall($name, $args);
	}

}
