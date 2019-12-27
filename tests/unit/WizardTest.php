<?php

use Nette\Forms\Form as NetteForms;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use WebChemistry\Testing\TUnitTest;

class WizardTest extends \Codeception\TestCase\Test
{

	use TUnitTest;

	/** @var \Contributte\FormWizard\Wizard */
	protected $wizard;

	protected function _before()
	{
		$session = new Session(
			new Request(new UrlScript()), new Response()
		);
		$session->start();

		$this->wizard = new Wizard($session);
	}

	public function testSteps()
	{
		$wizard = $this->wizard;
		$form = $this->wizard->create();

		$this->assertInstanceOf(NetteForms::class, $form);
		$this->assertSame('step1', $form->getName());

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());

		$wizard->setStep(2);
		$this->assertSame(1, $wizard->getCurrentStep());

		$wizard->setStep(-1);
		$this->assertSame(1, $wizard->getCurrentStep());
	}

	public function testInvalidSubmit()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		$response = $hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])
			->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();

		$dom = $response->toDomQuery();

		$this->assertDomHas($dom, '#frm-wizard-step1');

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame($wizard->create(1), $wizard->create());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testSubmit()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		$response = $hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'foo',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();

		$this->assertDomHas($response->toDomQuery(), '#frm-wizard-step2');

		$this->assertFalse($wizard->isSuccess());
		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame([
			'name' => 'foo',
			'skip' => false,
		], $wizard->getValues(true));
	}

	public function testSubmitBack()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		// Submit step1
		$hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'foo',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		// Checks if current form is step2
		$this->assertDomHas($hierarchy->send()->toDomQuery(), '#frm-wizard-step2');

		$hierarchy->cleanup();

		// Submit step2 to step1
		$response = $hierarchy->getControl('wizard')
			->getForm('step2')
			->setValues([
				Wizard::PREV_SUBMIT_NAME => 'submit',
			])->send();

		$this->assertTrue($response->toDomQuery()->has('#frm-wizard-step1-name'));
	}

	public function testFinish()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		// Submit step1
		$hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'Name',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		// Submit step2
		$hierarchy->getControl('wizard')
			->getForm('step2')
			->setValues([
				'optional' => 'Optional',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		$response = $hierarchy->getControl('wizard')
			->getForm('step3')
			->setValues([
				'void' => 'void',
				'email' => 'email',
				Wizard::FINISH_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();
		$this->assertDomHas($response->toDomQuery(), '#success');

		$this->assertTrue($wizard->isSuccess());
		$this->assertSame(1, Wizard::$called);
		$this->assertSame([
			'name' => 'Name',
			'skip' => false,
			'optional' => 'Optional',
			'email' => 'email',
		], $wizard->getValues(true));
		$this->assertSame([
			'name' => 'Name',
			'skip' => false,
			'optional' => 'Optional',
			'email' => 'email',
		], Wizard::$values);
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testOptionalStep()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		// Submit step1
		$hierarchy->getControl('wizard')
			->getForm('step1')
			->setValues([
				'name' => 'Name',
				'skip' => '1',
				Wizard::NEXT_SUBMIT_NAME => 'submit',
			])->send();

		$hierarchy->cleanup();

		$response = $hierarchy->getControl('wizard')
			->getForm('step3')
			->setValues([
				'void' => 'void',
				'email' => 'email',
				Wizard::FINISH_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();
		$this->assertDomHas($response->toDomQuery(), '#success');

		$this->assertTrue($wizard->isSuccess());
		$this->assertSame(1, Wizard::$called);
		$this->assertSame([
			'name' => 'Name',
			'skip' => true,
			'email' => 'email',
		], $wizard->getValues(true));
		$this->assertSame([
			'name' => 'Name',
			'skip' => true,
			'email' => 'email',
		], Wizard::$values);
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testSkipAll()
	{
		$hierarchy = $this->services->hierarchy->createHierarchy(WizardPresenter::class);

		$response = $hierarchy->getControl('wizard')
			->getForm('step3')
			->setValues([
				'void' => 'void',
				'email' => 'email',
				Wizard::FINISH_SUBMIT_NAME => 'submit',
			])->send();

		/** @var Wizard $wizard */
		$wizard = $response->getForm()->getParent();
		$this->assertDomNotHas($response->toDomQuery(), '#success');

		$this->assertFalse($wizard->isSuccess());
	}

}