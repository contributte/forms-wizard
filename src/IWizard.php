<?php

namespace WebChemistry\Forms\Controls;

use Nette\ComponentModel\IComponent;
use WebChemistry\Forms\Form;
use WebChemistry\Forms\IProvider;

interface IWizard {

	const PREV_SUBMIT_NAME = 'prev';
	const NEXT_SUBMIT_NAME = 'next';
	const FINISH_SUBMIT_NAME = 'finish';

	/**
	 * @return IWizard
	 */
	public function setProvider(IProvider $provider);

	/**
	 * @return int
	 */
	public function getCurrentStep();

	/**
	 * @return int
	 */
	public function getLastStep();

	/**
	 * @param int $step
	 * @return IWizard
	 */
	public function setStep($step);

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
	 * @param string $name
	 * @return Form
	 */
	public function create($step = NULL);

	/**
	 * @return boolean
	 */
	public function isSuccess();
}