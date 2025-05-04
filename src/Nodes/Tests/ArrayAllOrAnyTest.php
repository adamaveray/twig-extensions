<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Nodes\Tests;

use Twig\Compiler;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Node;
use Twig\TwigTest;

final class ArrayAllOrAnyTest extends TestExpression
{
  private readonly string $functionName;

  public function __construct(Node $node, TwigTest|string $test, ?Node $arguments, int $lineno)
  {
    parent::__construct($node, $test, $arguments, $lineno);

    $name = $this->getTestName();
    $this->functionName = match ($name) {
      'any_empty' => '\\array_any',
      'all_empty' => '\\array_all',
      // @codeCoverageIgnoreStart
      default => throw new \OutOfBoundsException(\sprintf('Invalid test name "%s".', $name)), // @codeCoverageIgnoreEnd
    };
  }

  private function getTestName(): string
  {
    \assert(\is_array($this->attributes) && isset($this->attributes['name']) && \is_string($this->attributes['name']));
    return $this->attributes['name'];
  }

  #[\Override]
  public function compile(Compiler $compiler): void
  {
    $compiler
      ->raw($this->functionName)
      ->raw('(')
      ->subcompile($this->getNode('node'))
      ->raw(', ')
      ->raw('static fn (mixed $value): bool => empty($value)')
      ->raw(')');
  }
}
