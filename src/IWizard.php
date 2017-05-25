<?php

namespace WebChemistry\Forms\Controls;

use Nette\ComponentModel\IComponent;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;
use WebChemistry\Forms\Controls\Wizard\IFormFactory;

interface IWizard {

	const PREV_SUBMIT_NAME = 'prev';
	const NEXT_SUBMIT_NAME = 'next';
	const FINISH_SUBMIT_NAME = 'finish';

	/**
	 * @return IWizard
	 */
	public function setFactory(IFormFactory $factory): IWizard;

	/**
	 * @return int
	 */
	public function getCurrentStep(): int;

	/**
	 * @return int
	 */
	public function getLastStep(): int;

	/**
	 * @param int $step
	 * @return IWizard
	 */
	public function setStep(int $step): IWizard;

	/**
	 * Returns component specified by name or path.
	 * @param  string
	 * @param  bool   throw exception if component doesn't exist?
	 * @return IComponent|NULL
	 */
	public function getComponent($name, $need = TRUE);

	/**
	 * @return mixed
	 */
	public function render();


	/**
	 * @param string $step
	 * @return Form
	 */
	public function create(string $step = NULL): Form;

	/**
	 * @return bool
	 */
	public function isSuccess(): bool;

	/**
	 * @param bool $asArray
	 * @return array|ArrayHash
	 */
	public function getValues(bool $asArray = FALSE);

}
