<?php declare(strict_types = 1);

namespace Contributte\FormWizard;

use Nette\Application\UI\Form;
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

	public function create(?string $step = null): Form;

	public function isSuccess(): bool;

	/**
	 * @return bool[]
	 */
	public function getSteps(): array;

	public function getTotalSteps(): int;

	/**
	 * @return mixed[]|ArrayHash<string|int,mixed>
	 */
	public function getValues(bool $asArray = false);

}
