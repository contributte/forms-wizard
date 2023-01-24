<?php declare(strict_types = 1);

namespace Contributte\FormWizard\Latte;

use Contributte\FormWizard\Facade;
use Contributte\FormWizard\IWizard;
use Generator;
use Latte\Compiler\NodeHelpers;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Nette\ComponentModel\IComponent;

final class WizardNode extends StatementNode
{

	/** @var ExpressionNode */
	public $name;

	/** @var AreaNode */
	public $content;

	/**
	 * @return Generator<int, ?mixed[], array{AreaNode, ?Tag}, self>
	 */
	public static function create(Tag $tag): Generator
	{
		$tag->outputMode = $tag::OutputRemoveIndentation;
		$tag->expectArguments();

		$node = new static;
		$node->name = $tag->parser->parseUnquotedStringOrExpression();

		[$node->content] = yield;

		return $node;
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

	public function print(PrintContext $context): string
	{
		$nameValue = static::toValue($this->name);
		$componentGetter = '$this->global->uiControl->getComponent("' . $nameValue . '")';
		// variable : getter
		$wizardGetter = $nameValue[0] === '$' ? sprintf('is_object(%s) ? %s : %s', $nameValue, $nameValue, $componentGetter) : $componentGetter;

		return $context->format(
			'$wizard = %raw::createFacade(%raw);'
			. "\n"
			. ' %line %node ' // content
			. "\n\n",
			static::class,
			$wizardGetter,
			$this->position,
			$this->content
		);
	}
	public function &getIterator(): \Generator
	{
		yield $this->name;
		yield $this->content;
	}

	public static function toValue($args): mixed
	{
		try {
			return NodeHelpers::toValue($args, constants: true);
		} catch (\InvalidArgumentException) {
			return null;
		}
	}

}
