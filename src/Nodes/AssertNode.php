<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Nodes;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

#[YieldReady]
final class AssertNode extends Node
{
  public function __construct(AbstractExpression $assertion, ?AbstractExpression $message, int $line)
  {
    $nodes = ['assertion' => $assertion];
    if ($message !== null) {
      $nodes['message'] = $message;
    }
    parent::__construct($nodes, [], $line);
  }

  #[\Override]
  public function compile(Compiler $compiler): void
  {
    $compiler->addDebugInfo($this)->write('\\assert(')->subcompile($this->getNode('assertion'));
    if ($this->hasNode('message')) {
      $compiler->raw(', ')->subcompile($this->getNode('message'));
    }
    $compiler->raw(');' . "\n");
  }
}
