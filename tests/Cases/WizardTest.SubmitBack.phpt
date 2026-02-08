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
});
