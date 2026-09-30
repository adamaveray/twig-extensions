<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\TwigOverrides;

use Averay\TwigExtensions\Extensions\TwigOverridesExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TokenParsers\SetWithFiltersTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Twig\Error\SyntaxError;

/**
 * @internal
 */
#[CoversClass(TwigOverridesExtension::class)]
#[CoversClass(SetWithFiltersTokenParser::class)]
final class SetWithFiltersTest extends TestCase
{
  #[Test]
  public function replacesCoreSetTag(): void
  {
    $environment = self::makeEnvironment(extensions: [new TwigOverridesExtension()]);

    self::assertInstanceOf(SetWithFiltersTokenParser::class, $environment->getTokenParser('set'));
  }

  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('existingSyntaxDataProvider')]
  public function existingSyntaxRenders(string $expected, string $template, array $context = []): void
  {
    $environment = self::makeEnvironment(['template' => $template], [new TwigOverridesExtension()]);

    self::assertRenders($expected, $environment, context: $context);
  }

  /**
   * @return iterable<string, array{ expected: string, template: string, context?: array<string, mixed> }>
   */
  public static function existingSyntaxDataProvider(): iterable
  {
    yield 'Inline' => [
      'expected' => '&lt;b&gt;',
      'template' => "{%- set x = '<b>' -%}{{- x -}}",
    ];

    yield 'Inline expression' => [
      'expected' => 'AB',
      'template' => "{%- set x = 'a' ~ 'b' -%}{{- x | upper -}}",
    ];

    yield 'Multi-target' => [
      'expected' => 'AB',
      'template' => "{%- set a, b = 'A', 'B' -%}{{- a ~ b -}}",
    ];

    yield 'Block' => [
      'expected' => '<p>Hello</p>',
      'template' => '{%- set x %}<p>Hello</p>{% endset -%}{{- x -}}',
    ];

    yield 'Block with expression' => [
      'expected' => '<p>&lt;b&gt;</p>',
      'template' => '{%- set x %}<p>{{ name }}</p>{% endset -%}{{- x -}}',
      'context' => ['name' => '<b>'],
    ];

    yield 'Empty block' => [
      'expected' => '[]',
      'template' => '{%- set x %}{% endset -%}[{{- x -}}]',
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('filtersDataProvider')]
  public function filtersMatchManualAssignment(
    string $expected,
    string $filters,
    string $body,
    array $context = [],
  ): void {
    $environment = self::makeEnvironment([
      'filtered' => '{%- set x | ' . $filters . ' %}' . $body . '{% endset -%}{{- x -}}',
      'manual' => '{%- set x %}' . $body . '{% endset -%}{%- set x = x | ' . $filters . ' -%}{{- x -}}',
    ], [new TwigOverridesExtension()]);

    self::assertRenders($expected, $environment, 'filtered', $context);
    self::assertRenders($expected, $environment, 'manual', $context);
  }

  /**
   * @return iterable<string, array{ expected: string, filters: string, body: string, context?: array<string, mixed> }>
   */
  public static function filtersDataProvider(): iterable
  {
    yield 'Safety-preserving filter' => [
      'expected' => '<p>Hello</p>',
      'filters' => 'trim',
      'body' => '  <p>Hello</p>  ',
    ];

    yield 'Unsafe filter' => [
      'expected' => '&lt;P&gt;HELLO&lt;/P&gt;',
      'filters' => 'upper',
      'body' => '<p>Hello</p>',
    ];

    yield 'Multiple filters' => [
      'expected' => '&lt;P&gt;HELLO&lt;/P&gt;',
      'filters' => 'trim | upper',
      'body' => '  <p>Hello</p>  ',
    ];

    yield 'Filter arguments' => [
      'expected' => '&lt;p&gt;Hello &lt;b&gt;World&lt;/b&gt;&lt;/p&gt;',
      'filters' => "replace({ '%name%': name })",
      'body' => '<p>Hello %name%</p>',
      'context' => ['name' => '<b>World</b>'],
    ];

    yield 'Body with tags' => [
      'expected' => 'a,b',
      'filters' => "trim(',', 'right')",
      'body' => '{% for item in items %}{{ item }},{% endfor %}',
      'context' => ['items' => ['a', 'b']],
    ];

    yield 'Body with escaped expression' => [
      'expected' => 'HELLO &amp;LT;B&amp;GT;',
      'filters' => 'upper',
      'body' => 'Hello {{ name }}',
      'context' => ['name' => '<b>'],
    ];

    yield 'Empty body' => [
      'expected' => '',
      'filters' => 'trim',
      'body' => '',
    ];
  }

  #[Test]
  #[DataProvider('invalidSyntaxDataProvider')]
  public function invalidSyntaxThrows(string $template): void
  {
    $environment = self::makeEnvironment(['template' => $template], [new TwigOverridesExtension()]);

    self::assertThrows(
      static function () use ($environment): void {
        $environment->render('template');
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof SyntaxError,
    );
  }

  /**
   * @return iterable<string, array{string}>
   */
  public static function invalidSyntaxDataProvider(): iterable
  {
    yield 'Multi-target with filters' => ['{% set a, b | trim %}x{% endset %}'];
    yield 'Multi-target block' => ['{% set a, b %}x{% endset %}'];
    yield 'Mismatched multi-target' => ["{% set a, b = 'x' %}"];
    yield 'Filters with inline assignment' => ["{% set a | trim = 'x' %}"];
    yield 'Missing filter name' => ['{% set a | %}x{% endset %}'];
    yield 'Unknown filter' => ['{% set a | not_a_filter %}x{% endset %}'];
    yield 'Missing end tag' => ['{% set a | trim %}x'];
  }
}
