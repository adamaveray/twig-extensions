<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\TokenParsers;

use Twig\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;
use Averay\TwigExtensions\Nodes\ErrorNode;

final class ErrorTokenParser extends AbstractTokenParser
{
  #[\Override]
  public function getTag(): string
  {
    return 'error';
  }

  #[\Override]
  public function parse(Token $token): Node\Node
  {
    $parser = $this->parser;
    $stream = $parser->getStream();

    $message = $parser->parseExpression();

    /** @var Node\Expression\AbstractExpression|null $level */
    $level = null;
    if (!$stream->getCurrent()->test(Token::BLOCK_END_TYPE)) {
      $level = $parser->parseExpression();
    }

    $stream->expect(Token::BLOCK_END_TYPE);

    return new ErrorNode($message, $level, $token->getLine());
  }
}
