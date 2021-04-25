<?php declare(strict_types = 1);

namespace Contributte\FormWizard\Latte;

use Contributte\FormWizard\Facade;
use Contributte\FormWizard\IWizard;
use Exception;
use Latte\CompileException;
use Latte\Engine;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\PhpWriter;
use Nette\ComponentModel\IComponent;

class WizardMacros extends MacroSet
{

	public static function install(Engine $latte): void
	{
		$me = new self($latte->getCompiler());

		$me->addMacro('wizard', [$me, 'wizardStart'], [$me, 'wizardEnd']);
		$me->addMacro('step', [$me, 'stepStart'], '}');
	}

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

	public function wizardStart(MacroNode $node, PhpWriter $writer): string
	{
		$words = $node->tokenizer->fetchWords();
		if (!$words) {
			throw new CompileException('Missing control name in {wizard}');
		}

		$name = $writer->formatWord($words[0]);

		$componentGetter = '$this->global->uiControl->getComponent(' . $name . ')';
		// variable
		if ($name[0] === '$') {
			$wizard = sprintf('is_object(%s) ? %s : %s', $name, $name, $componentGetter);
		} else {
			$wizard = $componentGetter;
		}

		return sprintf('$wizard = %s::createFacade(%s);', static::class, $wizard);
	}

	public function stepStart(MacroNode $node, PhpWriter $writer): string
	{
		$word = $node->tokenizer->fetchWord();
		if (!is_numeric($word) && !in_array($word, ['success', '"success"', "'success'"])) {
			throw new CompileException('First parameter in {step} must be a numeric.');
		}

		if ($word === 'success') {
			return 'if ($wizard->isSuccess()) {';
		}

		return 'if ($wizard->getCurrentStep() === ' . $word . ' && !$wizard->isSuccess()) { $wizardForm = $form = $wizard->getCurrentComponent(); ';
	}

	public function wizardEnd(): void
	{
	}

}
