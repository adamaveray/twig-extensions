<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Assertions;

use Averay\TwigExtensions\Extensions\AssertionsExtension;
use Averay\TwigExtensions\Nodes\AssertNode;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TokenParsers\AssertTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Error\RuntimeError;

#[CoversClass(AssertionsExtension::class)]
#[CoversClass(AssertTokenParser::class)]
#[CoversClass(AssertNode::class)]
final class AssertTest extends TestCase
{
  #[DataProvider('assertTrueDataProvider')]
  public function testAssertTrue(string $value): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {% assert $value %}
      world
      TWIG
      ,
      [new AssertionsExtension()],
    );

    $result = $environment->render('template');
    self::assertEquals('Hello' . "\n" . 'world', $result);
  }

  public static function assertTrueDataProvider(): iterable
  {
    yield 'True' => ['true'];
    yield 'True-y' => ['1'];
    yield 'Result' => ['("hello" | length) == 5'];
  }

  #[DataProvider('assertFalseDataProvider')]
  public function testAssertFalse(string $value): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {% assert $value %}
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
      testPrevious: static fn(\Throwable|null $previous): bool => $previous instanceof \AssertionError,
    );
  }

  public static function assertFalseDataProvider(): iterable
  {
    yield 'False' => ['false'];
    yield 'False-y' => ['0'];
    yield 'Result' => ['("hello" | length) == 0'];
  }

  public function testAssertFalseWithMessage(): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {% assert false "The test value must be true." %}
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
      testPrevious: static fn(\Throwable|null $previous): bool => $previous instanceof \AssertionError &&
        $previous->getMessage() === 'The test value must be true.',
    );
  }
}
