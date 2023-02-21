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

	protected function createTemplate(): \Nette\Application\UI\Template
	{
		$template = parent::createTemplate();

		if (version_compare(\Latte\Engine::VERSION, '3', '<')) { // @phpstan-ignore-line
			WizardMacros::install($template->getLatte());
		} else {
			$template->getLatte()->addExtension(new \Contributte\FormWizard\Latte\WizardExtension());
		}

		return $template;
	}

	public function renderDefault()
	{
		$this->template->setFile(__DIR__ . '/template.latte');
	}

	public function link(string $destination, ...$args): string
	{
		return 'link';
	}

	protected function createComponentWizard()
	{
		return new Wizard($this->getCustomSession());
	}
}
