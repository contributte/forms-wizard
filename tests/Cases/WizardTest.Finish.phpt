<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Nette\Application\Request as AppRequest;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Form;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tester\DomQuery;
use Tests\Fixtures\DummyWizard;
use Tests\Fixtures\DummyWizardPresenter;
use Tests\Fixtures\WizardPresenterFactory;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$presenter = WizardPresenterFactory::create(
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
});
