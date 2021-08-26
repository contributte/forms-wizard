<?php

require __DIR__ . '/vendor/autoload.php';

use Contributte\FormWizard\Facade;
use Nette\Forms\Form;
use Nette\Http\RequestFactory;
use Nette\Http\Response;
use Nette\Http\Session;

class Wizard extends Contributte\FormWizard\Wizard {

	private $stepNames = [
		1 => "Skip username",
		2 => "Username",
		3 => "Email",
	];

	protected function finish(): void
	{
		$values = $this->getValues();

		var_dump($values);
	}

	protected function startup(): void
	{
		$this->skipStepIf(2, function (array $values): bool {
			return isset($values[1]) && $values[1]['skip'] === true;
		});
		$this->setDefaultValues(2, function (Form $form, array $values) {
			$data = [
				'username' => 'john_doe'
			];
			$form->setValues($data);
		});
	}

	public function getStepData(int $step): array
	{
		return [
			'name' => $this->stepNames[$step]
		];
	}

	protected function createStep1(): Form
	{
		$form = new Form();

		$form->addCheckbox('skip', 'Skip username');

		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep2(): Form
	{
		$form = new Form();

		$form->addText('username', 'Username')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Back');
		$form->addSubmit(self::NEXT_SUBMIT_NAME, 'Next');

		return $form;
	}

	protected function createStep3(): Form
	{
		$form = new Form();

		$form->addText('email', 'Email')
			->setRequired();

		$form->addSubmit(self::PREV_SUBMIT_NAME, 'Back');
		$form->addSubmit(self::FINISH_SUBMIT_NAME, 'Register');

		return $form;
	}
}

$requestFactory = new RequestFactory();
$httpRequest = $requestFactory->fromGlobals();
$httpResponse = new Response();

$session = new Session($httpRequest, $httpResponse);
$session->start();

$facade = new Facade(new Wizard($session));
$facade->attached();

if ($facade->isSuccess()) {
	var_dump($facade->getValues());

	echo "Wizard success\n";
} elseif ($facade->getCurrentStep() === 1) {
	// first step
	echo $facade->renderCurrentComponent();
} else {
	// other steps
	echo $facade->renderCurrentComponent();
}
