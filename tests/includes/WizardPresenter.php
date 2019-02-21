<?php

use Nette\Application\UI\Presenter;
use Nette\Http\Session;
use Contributte\FormWizard\Latte\WizardMacros;

class WizardPresenter extends Presenter
{

	private $session;

	public function getCustomSession()
	{
		if (!$this->session) {
			$this->session = new Session($this->getHttpRequest(), $this->getHttpResponse());
		}

		return $this->session;
	}

	protected function createTemplate(): \Nette\Application\UI\ITemplate
	{
		$template = parent::createTemplate();

		WizardMacros::install($template->getLatte());

		return $template;
	}

	public function renderDefault()
	{
		$this->template->setFile(__DIR__ . '/template.latte');
	}

	public function link(string $destination, $args = []): string
	{
		return 'link';
	}

	protected function createComponentWizard()
	{
		return new Wizard($this->getCustomSession());
	}
}