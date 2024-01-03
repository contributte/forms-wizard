<?php declare(strict_types = 1);

namespace Contributte\FormWizard\DI;

use Contributte\FormWizard\Latte\WizardExtension as WizardExtensionLatte;
use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Statement;

final class WizardExtension extends CompilerExtension
{

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		$latteDef = $builder->getDefinition('nette.latteFactory');
		assert($latteDef instanceof FactoryDefinition);

		$latteDef->getResultDefinition()
			->addSetup('addExtension', [new Statement(WizardExtensionLatte::class)]);
	}

}
