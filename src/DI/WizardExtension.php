<?php

namespace WebChemistry\Forms\Controls\DI;

use Nette\DI\CompilerExtension;

class WizardExtension extends CompilerExtension {

	/**
	 * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function beforeCompile() {
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('nette.latteFactory')
				->addSetup('WebChemistry\Forms\Controls\Wizard\Macros::install(?)', array('@self'));
	}

}
