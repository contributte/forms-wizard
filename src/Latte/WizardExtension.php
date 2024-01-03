<?php declare(strict_types = 1);

namespace Contributte\FormWizard\Latte;

use Contributte\FormWizard\Facade;
use Contributte\FormWizard\IWizard;
use Exception;
use Latte\Extension;
use Nette\ComponentModel\IComponent;

class WizardExtension extends Extension
{

	/**
	 * @throws Exception
	 */
	public static function createFacade(IComponent $component): Facade
	{
		if (!$component instanceof IWizard) {
			throw new Exception('Wizard must be instance of ' . IWizard::class);
		}

		return new Facade($component);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTags(): array
	{
		return [
			'wizard' => [WizardNode::class, 'create'],
			'step' => [StepNode::class, 'create'],
		];
	}

}
