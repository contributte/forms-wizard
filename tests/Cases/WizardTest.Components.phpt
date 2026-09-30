<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Nette\ComponentModel\Container;
use Nette\Forms\Controls\SubmitButton;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Tester\Assert;
use Tests\Fixtures\DummyWizard;

require_once __DIR__ . '/../bootstrap.php';

// Wizard buttons get callbacks attached
Toolkit::test(function (): void {
	$session = new Session(
		new HttpRequest(new UrlScript()),
		new HttpResponse()
	);
	$session->start();

	$wizard = new DummyWizard($session);
	$form = $wizard->create();

	$next = $form[DummyWizard::NEXT_SUBMIT_NAME];
	Assert::type(SubmitButton::class, $next);
	Assert::count(1, $next->onClick);
	Assert::count(1, $next->onInvalidClick);

	$step2 = $wizard->getComponent('step2');

	$prev = $step2[DummyWizard::PREV_SUBMIT_NAME];
	Assert::type(SubmitButton::class, $prev);
	Assert::count(1, $prev->onClick);
	Assert::count(1, $prev->onInvalidClick);
	Assert::same([], $prev->getValidationScope());
});

// Non-step components are not treated as steps
Toolkit::test(function (): void {
	$session = new Session(
		new HttpRequest(new UrlScript()),
		new HttpResponse()
	);
	$session->start();

	$wizard = new DummyWizard($session);
	$component = new Container();

	Assert::noError(function () use ($wizard, $component): void {
		$wizard->addComponent($component, 'foo');
	});

	Assert::same($component, $wizard->getComponent('foo'));
});
