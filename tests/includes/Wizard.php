<?php

use Nette\Http\Session;

class Wizard extends \Contributte\FormWizard\Wizard
{

	public static $called = 0;

	public static $values = [];

	public function __construct(Session $session)
	{
		parent::__construct($session);
		self::$called = 0;
		self::$values = [];
	}

	protected function finish(): void
	{
		self::$values = $this->getValues(true);
		self::$called++;
	}

	protected function startup(): void
	{
		$this->skipStepIf(2, function (array $values): bool {
			return isset($values[1]) && $values[1]['skip'];
		});
	}

	protected function createStep1()
	{
		$form = $this->createForm();

		$form->addText('name', 'Name')
			->setRequired();

		$form->addCheckbox('skip', 'Skip');

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep2()
	{
		$form = $this->createForm();

		$form->addText('optional', 'Optional');

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Prev');
		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep3()
	{
		$form = $this->createForm();

		$form->addText('email', 'Email')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Prev');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Register');

		return $form;
	}

}
