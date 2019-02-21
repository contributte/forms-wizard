<?php declare(strict_types = 1);

namespace WebChemistry\Forms\Controls\Wizard;

use Latte\CompileException;
use Latte\Engine;
use Latte\Macros\MacroSet;
use Latte\MacroNode;
use Latte\PhpWriter;
use Nette\Utils\Strings;

class Macros extends MacroSet {

	/**
	 * @param Engine $latte
	 */
	public static function install(Engine $latte): void {
		$me = new self($latte->getCompiler());

		$me->addMacro('wizard', [$me, 'wizardStart'], [$me, 'wizardEnd']);
		$me->addMacro('step', [$me, 'stepStart'], '}');
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 * @throws CompileException
	 */
	public function wizardStart(MacroNode $node, PhpWriter $writer): string {
		$words = $node->tokenizer->fetchWords();
		if (!$words) {
			throw new CompileException('Missing control name in {wizard}');
		}
		$name = $writer->formatWord($words[0]);

		return ($name[0] === '$' ? "if (is_object($name)) \$_tmp = $name; else " : '')
		. '$_tmp = $_control->getComponent(' . $name . '); '
		. 'if (!$_tmp instanceof WebChemistry\Forms\Controls\IWizard) throw new \Exception(\'Wizard must be instance of WebChemistry\Forms\Controls\IWizard\');'
		. '$wizard = new WebChemistry\Forms\Controls\Wizard\Facade($_tmp);';
	}

	/**
	 * @param MacroNode $node
	 * @param PhpWriter $writer
	 * @return string
	 * @throws CompileException
	 */
	public function stepStart(MacroNode $node, PhpWriter $writer): string {
		$word = $node->tokenizer->fetchWord();
		if (!is_numeric($word) && !in_array($word, ['success', '"success"', "'success'"])) {
			throw new CompileException('First parameter in {step} must be a numeric.');
		}

		if ($word === 'success') {
			return 'if ($wizard->isSuccess()) {';
		}

		return 'if ($wizard->getCurrentStep() === ' . $word . ' && !$wizard->isSuccess()) { $wizardForm = $form = $wizard->getCurrentComponent(); ';
	}

	public function wizardEnd() {}

}
