<?php declare(strict_types=1);

namespace WebChemistry\Forms\Controls\DI;

use Nette\DI\CompilerExtension;
use WebChemistry\Forms\Controls\Wizard\Macros;

class WizardExtension extends CompilerExtension {

	/**
	 * Adjusts DI container before is compiled to PHP class. Intended to be overridden by descendant.
	 *
	 * @return void
	 */
	public function beforeCompile(): void {
		$builder = $this->getContainerBuilder();

		$builder->getDefinition('nette.latteFactory')
			->addSetup(Macros::class . '::install(?)', array('@self'));
	}

}
