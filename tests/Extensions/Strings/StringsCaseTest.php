<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Strings;

use Averay\TwigExtensions\Extensions\StringsExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use function Symfony\Component\String\u;

#[CoversClass(StringsExtension::class)]
final class StringsCaseTest extends TestCase
{
  private const DEFAULT_LOCALE = 'en';

  #[DataProvider('upperDataProvider')]
  public function testUpperCaseConversion(
    string $expected,
    string $input,
    string $parameters = '',
    string $locale = self::DEFAULT_LOCALE,
  ): void {
    self::assertFilterRenders(
      $expected,
      $input,
      'upper(' . $parameters . ')',
      extensions: [new StringsExtension($locale)],
      message: 'The string should be uppercased correctly.',
    );
  }

  public static function upperDataProvider(): iterable
  {
    yield 'All' => [
      'expected' => 'HELLO WORLD',
      'input' => 'hello world',
    ];

    yield 'Words' => [
      'expected' => 'Hello World',
      'input' => 'hello world',
      'parameters' => '"words"',
    ];

    yield 'First' => [
      'expected' => 'Hello world',
      'input' => 'hello world',
      'parameters' => '"first"',
    ];

    $chars = 'iIıİ';
    yield 'Default locale aware' => [
      'expected' => u($chars)->localeUpper(self::DEFAULT_LOCALE)->toString(), // Defer to Symfony
      'input' => $chars,
    ];

    yield 'Custom locale aware' => [
      'expected' => u($chars)->localeUpper('tr_TR')->toString(), // Defer to Symfony
      'input' => $chars,
      'locale' => 'tr_TR',
    ];
  }

  #[DataProvider('lowerDataProvider')]
  public function testLowerCaseConversion(
    string $expected,
    string $input,
    string $parameters = '',
    string $locale = 'en',
  ): void {
    self::assertFilterRenders(
      $expected,
      $input,
      'lower(' . $parameters . ')',
      extensions: [new StringsExtension($locale)],
      message: 'The string should be lowercased correctly.',
    );
  }

  public static function lowerDataProvider(): iterable
  {
    yield 'All' => [
      'expected' => 'hello world',
      'input' => 'HELLO WORLD',
    ];

    yield 'Words' => [
      'expected' => 'hELLO wORLD',
      'input' => 'HELLO WORLD',
      'parameters' => '"words"',
    ];

    yield 'First' => [
      'expected' => 'hELLO WORLD',
      'input' => 'HELLO WORLD',
      'parameters' => '"first"',
    ];

    $chars = 'iIıİ';
    yield 'Default locale aware' => [
      'expected' => u($chars)->localeLower(self::DEFAULT_LOCALE)->toString(), // Defer to Symfony
      'input' => $chars,
    ];

    yield 'Custom locale aware' => [
      'expected' => u($chars)->localeLower('tr_TR')->toString(), // Defer to Symfony
      'input' => $chars,
      'locale' => 'tr_TR',
    ];
  }
}
