<?php declare(strict_types = 1);

namespace Tests\Fixtures;

use Contributte\FormWizard\Latte\WizardExtension;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\Template;
use Nette\Http\Session;

class DummyWizardPresenter extends Presenter
{

	private Session|null $session = null;

	public function getCustomSession(): Session
	{
		if (!$this->session) {
			$this->session = new Session($this->getHttpRequest(), $this->getHttpResponse());
		}

		return $this->session;
	}

	public function renderDefault(): void
	{
		$this->template->setFile(__DIR__ . '/template.latte');
	}

	public function link(string $destination, mixed $args = []): string
	{
		return 'link';
	}

	protected function createTemplate(?string $class = null): Template
	{
		$template = parent::createTemplate($class);
		$template->getLatte()->addExtension(new WizardExtension());

		return $template;
	}

	protected function createComponentWizard(): DummyWizard
	{
		return new DummyWizard($this->getCustomSession());
	}

}
