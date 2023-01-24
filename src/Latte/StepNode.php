<?php declare(strict_types = 1);

namespace Contributte\FormWizard\Latte;

use Generator;
use Latte\CompileException;
use Latte\Compiler\Nodes\AreaNode;
use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\Php\Scalar\StringNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Position;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

final class StepNode extends StatementNode
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

	public function print(PrintContext $context): string
	{
		$word = $this->name instanceof StringNode ? $this->name->value : intval($this->name->value ?? $this->name);

		if (!is_numeric($word) && !in_array($word, ['success', '"success"', "'success'"], true)) {
			throw new CompileException('First parameter in {step} must be a numeric.');
		}

		if (is_string($word) && str_contains($word, 'success')) {
			return $context->format(
				'if ($wizard->isSuccess()) {'
				. "\n"
				. ' %line %node ' // content
				. "\n"
				. "}"
				. "\n\n",
				$this->position,
				$this->content
			);
		}

		return $context->format(
			'if ($wizard->getCurrentStep() === %raw && !$wizard->isSuccess()) { $wizardForm = $form = $wizard->getCurrentComponent();'
			. "\n"
			. ' %line %node ' // content
			. "\n"
			. "}"
			. "\n\n",
			$word,
			$this->position,
			$this->content
		);
	}
	public function &getIterator(): \Generator
	{
		yield $this->name;
		yield $this->content;
	}

}
