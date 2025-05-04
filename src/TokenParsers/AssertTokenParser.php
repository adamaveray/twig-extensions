<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\TokenParsers;

use Averay\TwigExtensions\Nodes\AssertNode;
use Twig\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

final class AssertTokenParser extends AbstractTokenParser
{
  #[\Override]
  public function getTag(): string
  {
    return 'assert';
  }

  #[\Override]
  public function parse(Token $token): Node\Node
  {
    $parser = $this->parser;
    $stream = $parser->getStream();

    $assertion = $parser->parseExpression();

    /** @var Node\Expression\AbstractExpression|null $message */
    $message = null;
    if (!$stream->getCurrent()->test(Token::BLOCK_END_TYPE)) {
      $message = $parser->parseExpression();
    }
    $stream->expect(Token::BLOCK_END_TYPE);

    return new AssertNode($assertion, $message, $token->getLine());
  }
}
