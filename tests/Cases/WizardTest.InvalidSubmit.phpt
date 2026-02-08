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
use Tests\Fixtures\WizardPresenterFactory;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
	$presenter = WizardPresenterFactory::create(
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
});
