<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Assertions;

use Averay\TwigExtensions\Extensions\AssertionsExtension;
use Averay\TwigExtensions\Nodes\ErrorNode;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TokenParsers\ErrorTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Error\RuntimeError;

#[CoversClass(AssertionsExtension::class)]
#[CoversClass(ErrorTokenParser::class)]
#[CoversClass(ErrorNode::class)]
final class ErrorTest extends TestCase
{
  public function testError(): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {% error "Test error." %}
      world
      TWIG
      ,
      [new AssertionsExtension()],
    );

    self::assertThrows(
      static function () use ($environment): void {
        $environment->render('template');
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof RuntimeError,
      testPrevious: static fn(\Throwable|null $previous): bool => $previous instanceof \ErrorException &&
        $previous->getMessage() === 'Test error.',
    );
  }

  public function testErrorWithLevel(): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {% error "Test error." constant("\\E_USER_ERROR") %}
      world
      TWIG
      ,
      [new AssertionsExtension()],
    );

    self::assertThrows(
      static function () use ($environment): void {
        $environment->render('template');
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof RuntimeError,
      testPrevious: static fn(\Throwable|null $previous): bool => $previous instanceof \ErrorException &&
        $previous->getMessage() === 'Test error.' &&
        $previous->getSeverity() === \E_USER_ERROR,
    );
  }
}
