<?php declare(strict_types = 1);

namespace Contributte\FormWizard\Session;

use Nette\Http\SessionSection;

class WizardSessionSection
{

	private const CURRENT_STEP = 'currentStep';
	private const VALUES = 'values';
	private const LAST_STEP = 'lastStep';

	/** @var SessionSection<string, mixed> */
	private SessionSection $section;

	/** @var mixed[]|null */
	private ?array $cache = null;

	/**
	 * @param SessionSection<string, mixed> $section
	 */
	public function __construct(SessionSection $section)
	{
		$this->section = $section;
	}

	public function getLastStep(): ?int
	{
		return $this->getSectionValue(self::LAST_STEP);
	}

	public function setLastStep(int $step): void
	{
		$this->setSectionValue(self::LAST_STEP, $step);
	}

	public function getCurrentStep(): ?int
	{
		return $this->getSectionValue(self::CURRENT_STEP);
	}

	public function setCurrentStep(int $step): void
	{
		$this->setSectionValue(self::CURRENT_STEP, $step);
	}

	/**
	 * @return mixed[]|null
	 */
	public function getValues(): ?array
	{
		return $this->getSectionValue(self::VALUES);
	}

	/**
	 * @return mixed[]|null
	 */
	public function getStepValues(int $step): ?array
	{
		return $this->getSectionValue(self::VALUES)[$step] ?? null;
	}

	/**
	 * @param mixed[] $values
	 */
	public function setStepValues(int $step, array $values): void
	{
		$sectionValues = $this->getSectionValue(self::VALUES);
		$sectionValues[$step] = $values;

		$this->setSectionValue(self::VALUES, $sectionValues);
	}

	public function reset(): void
	{
		$this->cache = $this->section[self::VALUES];

		$this->section->remove();
	}

	protected function getSectionValue(string $name): mixed
	{
		if ($name === self::VALUES && $this->cache !== null) {
			return $this->cache;
		}

		return $this->section[$name];
	}

	protected function setSectionValue(string $name, mixed $value): void
	{
		$this->section[$name] = $value;
	}

}
