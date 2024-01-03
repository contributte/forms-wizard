<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Contributte\FormWizard\Wizard;
use Nette\Application\UI\Form;
use Nette\Http\Session;

class DummyWizard extends Wizard
{

	public static int $called = 0;

	/** @var array<mixed> */
	public static array $values = [];

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
		$this->skipStepIf(2, fn (array $values): bool => isset($values[1]) && $values[1]['skip']);
		$this->setDefaultValues(1, function (Form $form, array $values): void {
			$data = [
				'name' => 'This is default name',
			];
			$form->setDefaults($data);
		});
	}

	protected function createStep1(): Form
	{
		$form = $this->createForm();
		$form->allowCrossOrigin();

		$form->addText('name', 'Name')
			->setRequired();

		$form->addCheckbox('skip', 'Skip');

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep2(): Form
	{
		$form = $this->createForm();
		$form->allowCrossOrigin();

		$form->addText('optional', 'Optional');

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Prev');
		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep3(): Form
	{
		$form = $this->createForm();
		$form->allowCrossOrigin();

		$form->addText('email', 'Email')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Prev');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Register');

		return $form;
	}

}
