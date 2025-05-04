<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Logic;

use Averay\TwigExtensions\Extensions\LogicExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Error\RuntimeError;

#[CoversClass(LogicExtension::class)]
final class LogicTest extends TestCase
{
  #[DataProvider('matchDataProvider')]
  public function testMatch(string $expected, mixed $value, string $parameters): void
  {
    $environment = self::makeEnvironment(
      '{{- value | match(' . $parameters . ') -}}',
      extensions: [new LogicExtension()],
    );
    self::assertRenders($expected, $environment, context: ['value' => $value]);
  }

  public static function matchDataProvider(): iterable
  {
    $cases = <<<'TWIG'
    {
      "first-item": "First item.",
      "second-item": "Second item.",
    }
    TWIG;

    yield 'Match first' => [
      'expected' => 'First item.',
      'value' => 'first-item',
      'parameters' => $cases,
    ];

    yield 'Match last' => [
      'expected' => 'Second item.',
      'value' => 'second-item',
      'parameters' => $cases,
    ];

    yield 'No match & not strict' => [
      'expected' => '',
      'value' => 'unknown-item',
      'parameters' => $cases . ', strict: false',
    ];
  }

  public function testMatchFailsWhenNoMatchAndStrict(): void
  {
    $environment = self::makeEnvironment(
      '{{- value | match(cases, strict: true) -}}',
      extensions: [new LogicExtension()],
    );

    $this->expectException(RuntimeError::class);
    $this->expectExceptionMessageMatches('~Value not found in choices.~');
    $environment->render('template', [
      'value' => 'test',
      'cases' => [
        'other' => true,
      ],
    ]);
  }
}
