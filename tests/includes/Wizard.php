<?php

class Wizard extends \WebChemistry\Forms\Controls\Wizard {

	public static $called = 0;

	public static $values = [];

	public function __construct(\Nette\Http\Session $session) {
		parent::__construct($session);
		self::$called = 0;
		self::$values = [];
	}

	protected function finish(): void {
		self::$values = $this->getValues(TRUE);
		self::$called++;
	}

	protected function createStep1() {
		$form = $this->createForm();

		$form->addText('name', 'Uživatelské jméno')
			 ->setRequired();

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Další');

		return $form;
	}

	protected function createStep2() {
		$form = $this->createForm();

		$form->addText('email', 'Email')
			 ->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Zpět');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Registrovat');

		return $form;
	}

}
