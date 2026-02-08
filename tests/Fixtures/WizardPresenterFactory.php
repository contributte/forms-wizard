<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Mockery;
use Nette\Application\IPresenterFactory;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Http\Request as HttpRequest;
use Nette\Http\Response as HttpResponse;
use Nette\Http\UrlScript;
use Nette\Routing\Router;

class WizardPresenterFactory
{

	public static function create(
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
			$httpRequest,
			$httpResponse,
			Mockery::mock(IPresenterFactory::class),
			Mockery::mock(Router::class),
			null,
			null,
			$templateFactory
		);

		return $presenter;
	}

}
