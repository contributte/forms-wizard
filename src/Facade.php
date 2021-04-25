<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Nette\Forms\Form;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use Nette\Utils\ObjectHelpers;

/**
 * @property-read mixed[]|ArrayHash $values
 * @property-read Form $currentComponent
 * @property-read bool $success
 * @property-read int $totalSteps
 * @property-read array $steps
 * @property-read int $currentStep
 * @property-read int $lastStep
 */
class Facade
{

	use SmartObject;

	/** @var IWizard */
	private $wizard;

	/** @var int|null */
	private $steps = null;

	public function __construct(IWizard $wizard)
	{
		$this->wizard = $wizard;
	}

	/**
	 * @return mixed[]|ArrayHash
	 */
	public function getValues(bool $asArray = false)
	{
		return $this->wizard->getValues($asArray);
	}

	public function getCurrentComponent(): Form
	{
		return $this->wizard->create();
	}

	public function useLink(int $step): bool
	{
		return !$this->isDisabled($step) && !$this->isCurrent($step);
	}

	public function isCurrent(int $step): bool
	{
		return $this->getCurrentStep() === $step;
	}

	public function isSuccess(): bool
	{
		return $this->wizard->isSuccess();
	}

	public function getTotalSteps(): int
	{
		if ($this->steps === null) {
			$iterator = 1;
			while ($this->wizard->getComponent('step' . $iterator, false)) {
				$iterator++;
			}

			$this->steps = $iterator - 1;
		}

		return $this->steps;
	}

	/**
	 * @return int[]
	 */
	public function getSteps(): array
	{
		return range(1, $this->getTotalSteps());
	}

	public function render(): void
	{
		$this->wizard->render();
	}

	public function getCurrentStep(): int
	{
		return $this->wizard->getCurrentStep();
	}

	public function getLastStep(): int
	{
		return $this->wizard->getLastStep();
	}

	public function isActive(int $step): bool
	{
		return $step === $this->getCurrentStep();
	}

	public function isDisabled(int $step): bool
	{
		return $step > $this->getLastStep();
	}

	/**
	 * @return mixed
	 */
	public function __get(string $name)
	{
		$getters = ['get' . ucfirst($name), 'is' . ucfirst($name)];
		foreach ($getters as $getter) {
			if (method_exists($this, $getter)) {
				return $this->$getter();
			}
		}

		ObjectHelpers::strictGet(static::class, $name);
	}

}
