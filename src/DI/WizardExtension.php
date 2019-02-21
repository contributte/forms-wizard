<?php declare(strict_types = 1);

namespace Contributte\FormWizard\DI;

use Contributte\FormWizard\Latte\WizardMacros;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;

final class WizardExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var FactoryDefinition $latte */
		$latte = $builder->getDefinition('nette.latteFactory');

		$latte->getResultDefinition()
			->addSetup(WizardMacros::class . '::install(?)', ['@self']);
	}

}
