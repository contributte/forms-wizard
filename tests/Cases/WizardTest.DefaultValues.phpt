<?php declare(strict_types = 1);

namespace Tests\Cases;

use Contributte\Tester\Toolkit;
use Nette\Application\Request as AppRequest;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Tester\Assert;
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

		$defaultValues = $wizard->create()->getValues('array');
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
});
