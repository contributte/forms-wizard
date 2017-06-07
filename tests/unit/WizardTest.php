<?php

use WebChemistry\Testing\TUnitTest;

class WizardTest extends \Codeception\TestCase\Test {

	use TUnitTest;

	/** @var \WebChemistry\Forms\Controls\Wizard */
	protected $wizard;

	protected function _before() {
		$session = new \Nette\Http\Session(
			new \Nette\Http\Request(new \Nette\Http\UrlScript()), new \Nette\Http\Response()
		);
		$session->start();
		$this->wizard = new Wizard($session);

		$this->services->presenter->setMapping([
			'*' => '*Presenter',
		]);
	}

	public function testSteps() {
		$wizard = $this->wizard;
		$form = $this->wizard->create();

		$this->assertInstanceOf('Nette\Forms\Form', $form);
		$this->assertSame('step1', $form->getName());

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());

		$wizard->setStep(2);
		$this->assertSame(1, $wizard->getCurrentStep());

		$wizard->setStep(-1);
		$this->assertSame(1, $wizard->getCurrentStep());
	}

	public function testInvalidSubmit() {
		$hierarchy = $this->services->hierarchy->createHierarchy('Wizard');

		$response = $hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])
			->send();


		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();
		$this->assertStringEqualsFile(__DIR__ . '/expected/start.expected', $response->toString());

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame($wizard->create(1), $wizard->create());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testSubmit() {
		$hierarchy = $this->services->hierarchy->createHierarchy('Wizard');

		$response = $hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'foo',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();

		$this->assertStringEqualsFile(__DIR__ . '/expected/step2.expected', $response->toString());
		$this->assertFalse($wizard->isSuccess());
		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame(array(
			'name' => 'foo'
		), $wizard->getValues(TRUE));
	}

	public function testSubmitBack() {
		$hierarchy = $this->services->hierarchy->createHierarchy('Wizard');

		// Submit step1
		$hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'foo',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		// Checks if current form is step2
		$this->assertStringEqualsFile(__DIR__ . '/expected/step2.expected', $hierarchy->send()->toString());

		$hierarchy->cleanup();

		// Submit step2 to step1
		$response = $hierarchy->getControl('wizard')
			->getForm('step2')
			->setValues([
				Wizard::PREV_SUBMIT_NAME => 'submit',
			])->send();

		$this->assertTrue($response->toDomQuery()->has('#frm-wizard-step1-name'));
	}

	public function testFinish() {
		$hierarchy = $this->services->hierarchy->createHierarchy('Wizard');

		// Submit step1
		$hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'Name',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		$response = $hierarchy->getControl('wizard')
			->getForm('step2')
			->setValues([
				'void' => 'void',
				'email' => 'email',
				Wizard::FINISH_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();
		$this->assertStringEqualsFile(__DIR__ . '/expected/finish.expected', $response->toString());

		$this->assertTrue($wizard->isSuccess());
		$this->assertSame(1, Wizard::$called);
		$this->assertSame(array(), $wizard->getValues(TRUE));
		$this->assertSame(array(
			'name' => 'Name',
			'email' => 'email'
		), Wizard::$values);
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());
	}

}