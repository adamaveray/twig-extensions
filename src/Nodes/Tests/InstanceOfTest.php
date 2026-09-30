<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Nodes\Tests;

use Twig\Compiler;
use Twig\Node\Expression\TestExpression;

/**
 * @internal
 *
 * @coversNothing
 */
final class InstanceOfTest extends TestExpression
{
  #[\Override]
  public function compile(Compiler $compiler): void
  {
    $arguments = $this->getNode('arguments');

    $compiler
      ->raw('\\is_a(')
      ->subcompile($this->getNode('node'))
      ->raw(', ')
      ->subcompile($arguments->getNode('0'))
      ->raw(')');
  }
}
