<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Nodes\Tests;

use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Extension\CoreExtension;
use Twig\Node\Expression\TestExpression;
use Twig\Node\Node;
use Twig\TwigTest;

final class SameDateAsTest extends TestExpression
{
  private const DEFAULT_DATETIME_FORMAT = \DateTimeInterface::ATOM;
  private ?string $defaultFormat;

  public function __construct(Node $node, TwigTest|string $test, ?Node $arguments, int $lineno)
  {
    parent::__construct($node, $test, $arguments, $lineno);

    $name = $this->getTestName();
    $this->defaultFormat = match ($name) {
      'same_date as' => null,
      'same_year as' => 'Y',
      'same_month as' => 'Y-m',
      'same_day as' => 'Y-m-d',
      'same_time as' => 'H:i:s',
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
    $defaultTimezone = $compiler->getEnvironment()->getExtension(CoreExtension::class)->getTimezone();

    $arguments = $this->getNode('arguments');
    $formatNode = $arguments->hasNode('format') ? $arguments->getNode('format') : null;
    $timezoneNode = $arguments->hasNode('timezone') ? $arguments->getNode('timezone') : null;

    $compiler->raw('(');
    self::compileDate(
      $compiler,
      $this->getNode('node'),
      $formatNode,
      $this->defaultFormat,
      $timezoneNode,
      $defaultTimezone,
    );
    $compiler->raw(' === ');
    self::compileDate(
      $compiler,
      $arguments->getNode('0'),
      $formatNode,
      $this->defaultFormat,
      $timezoneNode,
      $defaultTimezone,
    );
    $compiler->raw(')');
  }

  private static function compileDate(
    Compiler $compiler,
    Node $node,
    ?Node $format,
    ?string $defaultFormat,
    ?Node $timezoneNode,
    \DateTimeZone $defaultTimezone,
  ): void {
    // Convert to immutable
    self::compileToVariable(
      $compiler,
      $datetimeVarName,
      static fn() => $compiler->raw('\\DateTimeImmutable::createFromInterface(')->subcompile($node)->raw(')'),
    );

    // Convert to timezone
    $compiler->raw('->setTimezone(');
    if ($timezoneNode === null) {
      $compiler->raw('new \\DateTimeZone(')->string($defaultTimezone->getName())->raw(')');
    } else {
      $timezoneVarName = $compiler->getVarName();
      self::compileTernary(
        $compiler,
        // If is DateTimeZone instance...
        if: static fn() => self::compileToVariable(
          $compiler,
          $timezoneVarName,
          static fn() => $compiler->subcompile($timezoneNode),
        )->raw(' instanceof \\DateTimeZone'),
        // ...then use value
        then: static fn() => $compiler->raw('$' . $timezoneVarName),
        // ...else
        else: static fn() => self::compileTernary(
          $compiler,
          // If is string
          if: static fn() => $compiler->raw('\\is_string($' . $timezoneVarName . ')'),
          // ...then use as DateTimeZone name
          then: static fn() => $compiler->raw('new \\DateTimeZone($' . $timezoneVarName . ')'),
          // ...else
          else: static fn() => self::compileTernary(
            $compiler,
            // If is false
            if: static fn() => $compiler->raw('$' . $timezoneVarName . ' === false'),
            // ...then preserve current time zone
            then: static fn() => $compiler->raw('$' . $datetimeVarName . '->getTimezone()'),
            // ...else use default time zone
            else: static fn() => $compiler->raw('new \\DateTimeZone(')->string($defaultTimezone->getName())->raw(')'),
          ),
        ),
      );
    }
    $compiler->raw(')');

    // Format for comparison
    $compiler->raw('->format(');
    if ($format === null) {
      $compiler->string($defaultFormat ?? self::DEFAULT_DATETIME_FORMAT);
    } else {
      if ($defaultFormat !== null) {
        throw new SyntaxError('A custom format cannot be set for this test.');
      }
      $compiler->subcompile($format);
    }
    $compiler->raw(')');
  }

  /**
   * @param string|null $variableName
   * @param-out string $variableName
   * @param callable(Compiler):mixed $fn
   */
  private static function compileToVariable(Compiler $compiler, ?string &$variableName, callable $fn): Compiler
  {
    $variableName ??= $compiler->getVarName();
    $compiler->raw('($' . $variableName . ' = ');
    $fn($compiler);
    $compiler->raw(')');
    return $compiler;
  }

  /**
   * @psalm-type Fn = callable(Compiler $compiler):(mixed|void)
   * @param Fn $if
   * @param Fn $then
   * @param Fn $else
   */
  private static function compileTernary(Compiler $compiler, callable $if, callable $then, callable $else): Compiler
  {
    $compiler->raw('(');
    $if($compiler);
    $compiler->raw(') ? (');
    $then($compiler);
    $compiler->raw(') : (');
    $else($compiler);
    $compiler->raw(')');
    return $compiler;
  }
}
