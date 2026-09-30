<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Arrays;

use Averay\TwigExtensions\Extensions\ArraysExtension;
use Averay\TwigExtensions\Nodes\Tests\ArrayAllOrAnyTest;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversClass(ArraysExtension::class)]
#[CoversClass(ArrayAllOrAnyTest::class)]
final class ArrayTestsTest extends TestCase
{
  #[Test]
  #[DataProvider('arraysDataProvider')]
  public function allEmptyTest(array $array): void
  {
    $environment = self::makeEnvironment('{{- array is all_empty ? "yes" : "no" -}}', [new ArraysExtension()]);

    // @mago-expect lint:no-empty
    self::assertRenders(
      \array_all($array, static fn(mixed $value): bool => empty($value)) ? 'yes' : 'no',
      $environment,
      context: ['array' => $array],
      message: 'The `all_empty` test should match the PHP function `array_all`.',
    );
  }

  #[Test]
  #[DataProvider('arraysDataProvider')]
  public function anyEmptyTest(array $array): void
  {
    $environment = self::makeEnvironment('{{- array is any_empty ? "yes" : "no" -}}', [new ArraysExtension()]);

    // @mago-expect lint:no-empty
    self::assertRenders(
      \array_any($array, static fn(mixed $value): bool => empty($value)) ? 'yes' : 'no',
      $environment,
      context: ['array' => $array],
      message: 'The `any_empty` test should match the PHP function `array_any`.',
    );
  }

  public static function arraysDataProvider(): iterable
  {
    yield 'Empty' => [[]];
    yield 'Single item list, all set' => [['hello world']];
    yield 'Single item array, all set' => [['hello' => 'world']];
    yield 'Multiple item list, all set' => [['hello world', 'foo bar']];
    yield 'Multiple item array, all set' => [['hello' => 'world', 'foo' => 'bar']];
    yield 'Single item list, all empty' => [['']];
    yield 'Single item array, all empty' => [['hello' => '']];
    yield 'Multiple item list, one empty' => [['hello world', '']];
    yield 'Multiple item array, one empty' => [['hello' => 'world', 'foo' => '']];
    yield 'Multiple item list, all empty' => [[null, '']];
    yield 'Multiple item array, all empty' => [['hello' => null, 'foo' => '']];
  }
}
