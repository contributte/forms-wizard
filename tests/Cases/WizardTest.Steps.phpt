<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Nette\Forms\Form as NetteForms;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tests\Fixtures\DummyWizard;

require_once __DIR__ . '/../bootstrap.php';

Toolkit::test(function (): void {
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
});
