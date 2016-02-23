<?php

use Nette\Application\UI\Presenter;
use Nette\Http\Session;

class WizardPresenter extends Presenter {

	/** @var Session */
	private $session;

	public function __construct(Session $session) {
		$this->session = $session;
	}

	protected function createTemplate() {
		$template = parent::createTemplate();

		\WebChemistry\Forms\Controls\Wizard\Macros::install($template->getLatte());

		return $template;
	}

	public function renderDefault() {
		$this->template->setFile(__DIR__ . '/template.latte');
	}

	/**
	 * Generates URL to presenter, action or signal.
	 *
	 * @param  string   destination in format "[//] [[[module:]presenter:]action | signal! | this] [#fragment]"
	 * @param  array|mixed
	 * @return string
	 * @throws InvalidLinkException
	 */
	public function link($destination, $args = array()) {
		return 'link';
	}

	protected function createComponentWizard() {
		return new \Wizard($this->session);
	}
}