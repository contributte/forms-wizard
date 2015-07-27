<?php

class Wizard extends \WebChemistry\Forms\Controls\Wizard {

	protected function finish() {
		$values = $this->getValues();
	}

	protected function createStep1() {
		$form = $this->getForm();

		$form->addText('name', 'Uživatelské jméno')
			 ->setRequired();

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Další');

		return $form;
	}

	protected function createStep2() {
		$form = $this->getForm();

		$form->addText('email', 'Email')
			 ->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Zpět');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Registrovat');

		return $form;
	}
}