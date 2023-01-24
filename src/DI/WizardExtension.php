<?php declare(strict_types = 1);

namespace Contributte\FormWizard\DI;

use Contributte\FormWizard\Latte\WizardMacros;
use Latte\Engine;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Statement;

final class WizardExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		/** @var FactoryDefinition $latte */
		$latte = $builder->getDefinition('nette.latteFactory');

		$resultDefinition = $latte->getResultDefinition();

		if (version_compare(Engine::VERSION, '3', '<')) { // @phpstan-ignore-line
			$resultDefinition->addSetup(WizardMacros::class . '::install(?->getCompiler())', ['@self']);
		} else {
			$resultDefinition->addSetup('addExtension', [new Statement(\Contributte\FormWizard\Latte\WizardExtension::class)]);
		}
	}

}
