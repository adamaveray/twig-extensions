<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\TokenParsers;

use Twig\Error\SyntaxError;
use Twig\ExpressionParser\Infix\FilterExpressionParser;
use Twig\Node;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Replaces the core `set` tag with support for applying filters to captured block values.
 *
 * @example Existing inline single set: `{% set foo = 'foo' %}`
 * @example Existing inline multiple sets: `{% set foo, bar = 'foo', 'bar' %}`
 * @example Existing block set: `{% set foo %}Some content{% endset %}`
 * @example New block set with filters: `{% set foo | trim | upper %}Some content{% endset %}`
 */
final class SetWithFiltersTokenParser extends AbstractTokenParser
{
  #[\Override]
  public function getTag(): string
  {
    return 'set';
  }

  #[\Override]
  // @mago-expect lint:sensitive-parameter -- False positive.
  public function parse(Token $token): Node\Node
  {
    $lineNumber = $token->getLine();
    $names = $this->parseAssignmentExpression();

    return $this->parser->getStream()->nextIf(Token::OPERATOR_TYPE, '=')
      ? $this->parseInlineSet($names, $lineNumber)
      : $this->parseBlockSet($names, $lineNumber);
  }

  private function parseInlineSet(Node\Nodes $names, int $lineNumber): Node\Node
  {
    $stream = $this->parser->getStream();

    $values = self::parseExpressionList($this->parser);
    $stream->expect(Token::BLOCK_END_TYPE);

    if (\count($names) !== \count($values)) {
      throw new SyntaxError(
        'When using set, you must have the same number of variables and assignments.', // Same message as Twig
        $stream->getCurrent()->getLine(),
        $stream->getSourceContext(),
      );
    }

    return new Node\SetNode(false, $names, $values, $lineNumber);
  }

  private function parseBlockSet(Node\Nodes $names, int $lineNumber): Node\Node
  {
    $stream = $this->parser->getStream();

    if (\count($names) > 1) {
      throw new SyntaxError(
        'When using set with a block, you cannot have a multi-target.', // Same message as Twig
        $stream->getCurrent()->getLine(),
        $stream->getSourceContext(),
      );
    }

    $parseBody = function () use ($stream): Node\Node {
      $stream->expect(Token::BLOCK_END_TYPE);

      $closeTag = 'end' . $this->getTag();
      // @mago-expect lint:sensitive-parameter -- False positive.
      $isCloseTag = static fn(Token $token): bool => $token->test($closeTag);

      $body = $this->parser->subparse(test: $isCloseTag, dropNeedle: true);
      $stream->expect(Token::BLOCK_END_TYPE);

      return $body;
    };

    if (!$stream->test(Token::OPERATOR_TYPE, '|')) {
      // No filters - use normal set
      return new Node\SetNode(true, $names, $parseBody(), $lineNumber);
    }

    // Apply filters
    $ref = new Node\Expression\Variable\LocalVariable(null, $lineNumber); // An anonymous variable to store the block for applying filters to before assigning to the target
    $filters = self::parseFilterChain($this->parser, $ref);

    return new Node\Nodes([
      new Node\SetNode(true, $ref, $parseBody(), $lineNumber),
      new Node\SetNode(false, $names, $filters, $lineNumber),
    ], $lineNumber);
  }

  private static function parseExpressionList(Parser $parser): Node\Nodes
  {
    $stream = $parser->getStream();

    /** @var list<Node\Expression\AbstractExpression> $expressions */
    $expressions = [];
    do {
      $expressions[] = $parser->parseExpression();
    } while ($stream->nextIf(Token::PUNCTUATION_TYPE, ','));

    return new Node\Nodes($expressions);
  }

  private static function parseFilterChain(
    Parser $parser,
    Node\Expression\AbstractExpression $target,
  ): Node\Expression\AbstractExpression {
    $filterParser = $parser
      ->getEnvironment()
      ->getExpressionParsers()
      ->getByClass(FilterExpressionParser::class) ?? throw new \LogicException(
        'Filter expression parser is not registered.',
      );

    $stream = $parser->getStream();

    $expression = $target;
    while ($stream->nextIf(Token::OPERATOR_TYPE, '|')) {
      $expression = $filterParser->parse($parser, $expression, $stream->getCurrent());
    }
    return $expression;
  }
}
