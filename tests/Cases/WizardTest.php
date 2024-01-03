<?php declare(strict_types = 1);

namespace Tests\Cases;

use Mockery;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as AppRequest;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\DI\Container;
use Nette\Forms\Form as NetteForms;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Tester\Assert;
use Tester\DomQuery;
use Tester\TestCase;
use Tests\Fixtures\DummyLatteFactory;
use Tests\Fixtures\DummyWizard;
use Tests\Fixtures\DummyWizardPresenter;

require_once __DIR__ . '/../bootstrap.php';

class WizardTest extends TestCase
{

	public function testSteps(): void
	{
		$session = new Session(
			new HttpRequest(new UrlScript()),
			new HttpResponse()
		);
		$session->start();

		$wizard = new DummyWizard($session);
		$form = $wizard->create();

		Assert::type(NetteForms::class, $form);
		Assert::same('step1', $form->getName());

		Assert::same(1, $wizard->getCurrentStep());
		Assert::same(1, $wizard->getLastStep());

		$wizard->setStep(2);
		Assert::same(1, $wizard->getCurrentStep());

		$wizard->setStep(-1);
		Assert::same(1, $wizard->getCurrentStep());
	}

	public function testInvalidSubmit(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step1-submit',
					DummyWizard::NEXT_SUBMIT_NAME => 'true',
				]
			)
		);

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));

		/** @var DummyWizard $wizard */
		$wizard = $presenter->getComponent('wizard');

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true($dom->has('#frm-wizard-step1'));

		Assert::same(1, $wizard->getCurrentStep());
		Assert::same(0, DummyWizard::$called);
		Assert::same($wizard->create('1'), $wizard->create());
		Assert::same(1, $wizard->getLastStep());
	}

	public function testSubmit(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step1-submit',
					DummyWizard::NEXT_SUBMIT_NAME => 'true',
					'name' => 'foo',
				]
			)
		);

		/** @var DummyWizard $wizard */
		$wizard = $presenter->getComponent('wizard');

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true($dom->has('#frm-wizard-step2'));

		Assert::false($wizard->isSuccess());
		Assert::same(2, $wizard->getCurrentStep());
		Assert::same($wizard->create('2'), $wizard->create());
		Assert::same(2, $wizard->getLastStep());
		Assert::same(0, DummyWizard::$called);
		Assert::same([
			'name' => 'foo',
			'skip' => false,
		], $wizard->getValues(true));
	}

	public function testSubmitBack(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
			)
		);

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: [
				'_do' => 'wizard-step2-submit',
				DummyWizard::PREV_SUBMIT_NAME => 'true',
			]
		));

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true($dom->has('#frm-wizard-step1'));
	}

	public function testReset(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step1-submit',
					'name' => 'Name',
					'skip' => '0',
					DummyWizard::NEXT_SUBMIT_NAME => 'submit',
				]
			)
		);

		$presenter->onShutdown[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			Assert::false($wizard->isSuccess());
			Assert::same(0, DummyWizard::$called);
			Assert::same([
				'name' => 'Name',
				'skip' => false,
			], $wizard->getValues(true));
			Assert::same(2, $wizard->getCurrentStep());
			Assert::same(2, $wizard->getLastStep());

			// Reset wizard
			$wizard->reset();

			Assert::false($wizard->isSuccess());
			Assert::same(0, DummyWizard::$called);
			Assert::same([], $wizard->getValues(true));
			Assert::same([], DummyWizard::$values);
			Assert::same(1, $wizard->getCurrentStep());
			Assert::same(1, $wizard->getLastStep());
		};

		$presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));
	}

	public function testFinish(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step3-submit',
					'skip' => 'false',
					'email' => 'email',
					DummyWizard::FINISH_SUBMIT_NAME => 'submit',
				]
			)
		);

		$presenter->onStartup[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			/** @var Form $step1 */
			$step1 = $wizard->getComponent('step1');
			$step1->setValues(['name' => 'Name']);
			$step1->setSubmittedBy($step1['next']);
			$step1->fireEvents();

			/** @var Form $step2 */
			$step2 = $wizard->getComponent('step2');
			$step2->setValues(['optional' => 'Optional']);
			$step2->setSubmittedBy($step2['next']);
			$step2->fireEvents();
		};

		$presenter->onShutdown[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			Assert::true($wizard->isSuccess());
			Assert::same(1, DummyWizard::$called);
			Assert::same([
				'name' => 'Name',
				'skip' => false,
				'optional' => 'Optional',
				'email' => 'email',
			], $wizard->getValues(true));
			Assert::same([
				'name' => 'Name',
				'skip' => false,
				'optional' => 'Optional',
				'email' => 'email',
			], DummyWizard::$values);
			Assert::same(1, $wizard->getCurrentStep());
			Assert::same(1, $wizard->getLastStep());
		};

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true($dom->has('#success'));
	}

	public function testOptionalStep(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step3-submit',
					'skip' => 'false',
					'email' => 'email',
					DummyWizard::FINISH_SUBMIT_NAME => 'submit',
				]
			)
		);

		$presenter->onStartup[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			/** @var Form $step1 */
			$step1 = $wizard->getComponent('step1');
			$step1->setValues([
				'name' => 'Name',
				'skip' => '1',
			]);
			$step1->setSubmittedBy($step1['next']);
			$step1->fireEvents();
		};

		$presenter->onShutdown[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			Assert::true($wizard->isSuccess());
			Assert::same(1, DummyWizard::$called);
			Assert::same([
				'name' => 'Name',
				'skip' => true,
				'email' => 'email',
			], $wizard->getValues(true));
			Assert::same([
				'name' => 'Name',
				'skip' => true,
				'email' => 'email',
			], DummyWizard::$values);
			Assert::same(1, $wizard->getCurrentStep());
			Assert::same(1, $wizard->getLastStep());
		};

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true($dom->has('#success'));
	}

	public function testSkipAll(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step3-submit',
					'void' => 'void',
					'email' => 'email',
					DummyWizard::FINISH_SUBMIT_NAME => 'submit',
				]
			)
		);

		$presenter->onShutdown[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			Assert::false($wizard->isSuccess());
		};

		/** @var TextResponse $response */
		$response = $presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));

		$dom = DomQuery::fromHtml((string) $response->getSource());
		Assert::true(!$dom->has('#success'));
	}

	public function testDefaultValues(): void
	{
		$presenter = $this->createPresenter(
			httpRequest: new HttpRequest(
				url: new UrlScript('http://localhost'),
				method: 'POST',
				post: [
					'_do' => 'wizard-step1-submit',
					'name' => 'This is default name',
					'skip' => '0',
					DummyWizard::NEXT_SUBMIT_NAME => 'submit',
				]
			)
		);

		$presenter->onStartup[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			$defaultValues = $wizard->create()->getValues(true);
			Assert::same([
				'name' => 'This is default name',
				'skip' => false,
			], $defaultValues);
		};

		$presenter->onShutdown[] = function (DummyWizardPresenter $presenter): void {
			/** @var DummyWizard $wizard */
			$wizard = $presenter->getComponent('wizard');

			Assert::false($wizard->isSuccess());

			Assert::same(0, DummyWizard::$called);
			Assert::same([
				'name' => 'This is default name',
				'skip' => false,
			], $wizard->getValues(true));
			Assert::same(2, $wizard->getCurrentStep());
			Assert::same(2, $wizard->getLastStep());
		};

		$presenter->run(new AppRequest(
			name: 'test',
			method: $presenter->getHttpRequest()->getMethod(),
			post: $presenter->getHttpRequest()->getPost(),
		));
	}

	private function createPresenter(
		?HttpRequest $httpRequest = null,
	): DummyWizardPresenter
	{
		$httpRequest ??= new HttpRequest(
			url: new UrlScript('http://localhost'),
			method: 'POST'
		);
		$httpResponse = new HttpResponse();

		$templateFactory = new TemplateFactory(
			new DummyLatteFactory()
		);

		$presenter = new DummyWizardPresenter();
		$presenter->injectPrimary(
			Mockery::mock(Container::class),
			Mockery::mock(IPresenterFactory::class),
			Mockery::mock(Router::class),
			$httpRequest,
			$httpResponse,
			null,
			null,
			$templateFactory
		);

		return $presenter;
	}

}

(new WizardTest())->run();
