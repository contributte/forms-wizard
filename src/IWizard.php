<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

interface IWizard
{

	public const PREV_SUBMIT_NAME = 'prev';

	public const NEXT_SUBMIT_NAME = 'next';

	public const FINISH_SUBMIT_NAME = 'finish';

	public function setFactory(IFormFactory $factory): IWizard;

	public function getCurrentStep(): int;

	public function getLastStep(): int;

	public function setStep(int $step): IWizard;

	public function render(): void;

	public function reset(): void;

	public function create(?string $step = null, bool $defaultValues = true): Form;

	public function isSuccess(): bool;

	/**
	 * @return bool[]
	 */
	public function getSteps(): array;

	/**
	 * @return mixed[]
	 */
	public function getStepData(int $step): array;

	public function getTotalSteps(): int;

	/**
	 * @return array<mixed>|ArrayHash<mixed>
	 */
	public function getValues(bool $asArray = false);

}
