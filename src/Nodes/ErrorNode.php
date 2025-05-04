<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Nodes;

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

#[YieldReady]
final class ErrorNode extends Node
{
  public function __construct(AbstractExpression $message, ?AbstractExpression $severity, int $line)
  {
    $nodes = ['message' => $message];
    if ($severity !== null) {
      $nodes['severity'] = $severity;
    }
    parent::__construct($nodes, [], $line);
  }

  #[\Override]
  public function compile(Compiler $compiler): void
  {
    $compiler
      ->addDebugInfo($this)
      ->raw('throw new \\ErrorException(')
      ->subcompile($this->getNode('message'))
      ->raw(', ');
    if ($this->hasNode('severity')) {
      $compiler->raw('0, ')->subcompile($this->getNode('severity'));
    }
    $compiler->raw(');' . "\n");
    //    $compiler->addDebugInfo($this)->raw('\\trigger_error(')->subcompile($this->getNode('message'))->raw(', ');
    //    if ($this->hasNode('severity')) {
    //      $compiler->subcompile($this->getNode('severity'));
    //    } else {
    //      $compiler->raw('\\E_USER_ERROR');
    //    }
    //    $compiler->raw(');' . "\n");
  }
}
