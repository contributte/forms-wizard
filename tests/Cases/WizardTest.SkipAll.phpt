<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Nette\Application\Request as AppRequest;
use Nette\Application\Responses\TextResponse;
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
});
