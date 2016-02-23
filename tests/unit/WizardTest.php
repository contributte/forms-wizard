<?php

class WizardTest extends \Codeception\TestCase\Test {

	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \WebChemistry\Forms\Controls\Wizard */
	protected $wizard;

	/** @var \Nette\Http\Session */
	private $session;

	protected function _before() {
		$this->session = new \Nette\Http\Session(
			new \Nette\Http\Request(new \Nette\Http\UrlScript()), new \Nette\Http\Response()
		);
		$this->session = new \Kdyby\FakeSession\Session($this->session);
		$this->session->start();

		$this->wizard = new Wizard($this->session);
	}

	protected function _after() {
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
		$response = NULL;
		$wizard = $this->sendRequestToPresenter('wizard', 'step1', array(
			\Wizard::NEXT_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertStringEqualsFile(__DIR__ . '/expected/start.expected', $response);
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame($wizard->create(1), $wizard->create());
		$this->assertSame(1, $wizard->getLastStep());
	}

	public function testSubmit() {
		$response = NULL;
		$wizard = $this->sendRequestToPresenter('wizard', 'step1', array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertStringEqualsFile(__DIR__ . '/expected/step2.expected', $response);
		$this->assertFalse($wizard->isSuccess());
		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame(array(
			'name' => 'Name'
		), $wizard->getValues(TRUE));
	}

	public function testSubmitBack() {
		$response = NULL;
		$wizard = $this->sendRequestToPresenter('wizard', 'step1', array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertStringEqualsFile(__DIR__ . '/expected/step2.expected', $response);
		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());

		// Back
		$wizard = $this->sendRequestToPresenter('wizard', 'step2', array(
			\Wizard::PREV_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertFalse($wizard->isSuccess());
		$this->assertSame(1, $wizard->getCurrentStep());
		$this->assertSame(2, $wizard->getLastStep());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame(array(
			'name' => 'Name'
		), $wizard->getValues(TRUE));
	}

	public function testFinish() {
		$response = NULL;
		$wizard = $this->sendRequestToPresenter('wizard', 'step1', array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertStringEqualsFile(__DIR__ . '/expected/step2.expected', $response);
		$this->assertFalse($wizard->isSuccess());
		$this->assertSame(0, Wizard::$called);
		$this->assertSame(2, $wizard->getCurrentStep());
		$this->assertSame($wizard->create(2), $wizard->create());
		$this->assertSame(2, $wizard->getLastStep());

		// Second
		$wizard = $this->sendRequestToPresenter('wizard', 'step2', array(
			'void' => 'void',
			'email' => 'email',
			\Wizard::FINISH_SUBMIT_NAME => 'submit'
		), $response);

		$this->assertStringEqualsFile(__DIR__ . '/expected/finish.expected', $response);
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

	public function testFacade() {
		$facade = new \WebChemistry\Forms\Controls\Wizard\Facade($this->wizard);
		$this->assertTrue($facade->isDisabled(2));

		$wizard = $this->sendRequestToPresenter('wizard', 'step1', array(
			'name' => 'Name',
			\Wizard::NEXT_SUBMIT_NAME => 'submit'
		));

		$facade = new \WebChemistry\Forms\Controls\Wizard\Facade($wizard);

		$this->assertSame(2, $facade->getCurrentStep());
		$this->assertInstanceOf('Nette\Forms\Form', $facade->getCurrentComponent());
		$this->assertSame('step2', $facade->getCurrentComponent()->getName());
		$this->assertNotNull($facade->getCurrentComponent()->lookup('Nette\Application\IPresenter', FALSE));
		$this->assertTrue($facade->useLink(1));
		$this->assertFalse($facade->useLink(2));
		$this->assertTrue($facade->isCurrent(2));
		$this->assertFalse($facade->isCurrent(1));
		$this->assertFalse($facade->isSuccess());
		$this->assertSame(2, $facade->getTotalSteps());
		$this->assertSame([
			1, 2
		], $facade->getSteps());
		$this->assertSame(2, $facade->getLastStep());
		$this->assertFalse($facade->isActive(1));
		$this->assertTrue($facade->isActive(2));
		$this->assertFalse($facade->isDisabled(2));
		$this->assertFalse($facade->isDisabled(1));
	}

	/************************* Helpers **************************/

	/**
	 * @param string $name
	 * @return \Nette\Application\UI\Presenter
	 */
	protected function createPresenter($name) {
		$presenterFactory = new \Nette\Application\PresenterFactory(function ($class) {
			/** @var \Nette\Application\UI\Presenter $presenter */
			$presenter = new $class($this->session);
			$presenter->injectPrimary(NULL, NULL, NULL,
				new \Nette\Http\Request(new \Nette\Http\UrlScript()), new \Nette\Http\Response(), NULL, NULL,
				new \Nette\Bridges\ApplicationLatte\TemplateFactory(new MockLatteFactory()));
			$presenter->autoCanonicalize = FALSE;

			return $presenter;
		});

		return $presenterFactory->createPresenter($name);
	}

	protected function sendRequestToPresenter($controlName = 'wizard', $step = 'step1', $post = [], &$response = NULL) {
		$presenter = $this->createPresenter('Wizard');

		$response = (string) $presenter->run(new \Nette\Application\Request('Wizard', 'POST', [
			'do' => $controlName . '-' . $step . '-submit'
		], $post))->getSource();

		/** @var WebChemistry\Forms\Controls\Wizard $form */
		$form = $presenter[$controlName];

		return $form;
	}

}