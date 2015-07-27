<?php

class WizardTest extends \PHPUnit_Framework_TestCase {

	/** @var bool */
	private static $isCalled = FALSE;

	protected function setUp() {

	}

	protected function tearDown() {

	}

	public function testSteps() {
		$wizard = new Wizard(E::getByType('Nette\Http\Session'));

		$form = $wizard->create();

		$this->assertInstanceOf('Nette\Forms\Form', $form);
		$this->assertSame('step1', $form->getName());

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());

		$wizard->setStep(2);
		$this->assertSame(1, $wizard->getCurrentStep());

		$wizard->setStep(-1);
		$this->assertSame(1, $wizard->getCurrentStep());
	}

	public function testMacros() {
		$wizard = new Wizard(E::getByType('Nette\Http\Session'));

		$template = E::getByType('Nette\Application\UI\ITemplateFactory')->createTemplate();

		\WebChemistry\Forms\Controls\Wizard\Macros::install($template->getLatte());

		$template->wzd = $wizard;

		$template->setFile(E::directory('%data%/wizard/template.latte'));

		$this->assertStringEqualsFile(E::dumpedFile('wizardTemplate'), (string) $template);
	}

	public function testInvalidSubmit() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step1-submit'
		), array(
			''
		)));

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(1), $wizard->create());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testSubmit() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step1-submit'
		), array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => ''
		)));

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(array(
			'name' => 'Name'
		), $wizard->getValues(TRUE));
	}

	public function testSubmitBack() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step1-submit'
		), array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => ''
		)));

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());

		// Back

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertSame(2, $wizard->getCurrentStep());

		$presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step2-submit'
		), array(
			\Wizard::PREV_SUBMIT_NAME => ''
		)));

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(array(
			'name' => 'Name'
		), $wizard->getValues(TRUE));
	}

	public function testEndAndTemplates() {
		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$response = $presenter->run(new \Nette\Application\Request('Wizard'));

		$this->assertStringEqualsFile(E::dumpedFile('step1'), (string) $response->getSource());

		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$response = $presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step1-submit'
		), array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => ''
		)));

		$this->assertStringEqualsFile(E::dumpedFile('step2'), (string) $response->getSource());

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());

		// Second

		/** @var \Nette\Application\IPresenterFactory $presenterFactory */
		$presenterFactory = E::getByType('Nette\Application\IPresenterFactory');

		$presenter = $presenterFactory->createPresenter('Wizard');
		$presenter->autoCanonicalize = FALSE;

		$presenter['wizard']->onSuccess[] = array($this, 'wizardSuccess');

		$response = $presenter->run(new \Nette\Application\Request('Wizard', 'POST', array(
			'do' => 'wizard-step2-submit'
		), array(
			'name' => 'Name',
			'email' => 'Email',
			\Wizard::FINISH_SUBMIT_NAME => ''
		)));

		$this->assertStringEqualsFile(E::dumpedFile('successStep'), (string) $response->getSource());

		/** @var \Wizard $wizard */
		$wizard = $presenter['wizard'];

		$this->assertTrue($wizard->isSuccess());
		$this->assertTrue(self::$isCalled);
		$this->assertSame(array(), $wizard->getValues(TRUE));
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function wizardSuccess(\Wizard $wizard) {
		$this->assertSame(array(
			'name' => 'Name',
			'email' => 'Email'
		), $wizard->getValues(TRUE));

		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame(2, $wizard->getLastStep());

		self::$isCalled = TRUE;
	}
}
