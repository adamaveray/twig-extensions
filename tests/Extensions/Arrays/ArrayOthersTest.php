<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Arrays;

use Averay\TwigExtensions\Extensions\ArraysExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Twig\Error\RuntimeError;

/**
 * @internal
 */
#[CoversClass(ArraysExtension::class)]
final class ArrayOthersTest extends TestCase
{
  /**
   * @param list<string> $expected
   * @param list<string> $array
   */
  #[Test]
  #[DataProvider('appendDataProvider')]
  public function append(array $expected, array $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | append(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The values should be appended correctly.',
    );
  }

  /**
   * @return iterable<string, array{ expected: list<string>, array: list<string>, parameters: string }>
   */
  public static function appendDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '',
    ];

    yield 'Single value' => [
      'expected' => ['a', 'b', 'c'],
      'array' => ['a', 'b'],
      'parameters' => '"c"',
    ];

    yield 'Multiple values' => [
      'expected' => ['a', 'b', 'c', 'd'],
      'array' => ['a', 'b'],
      'parameters' => '"c", "d"',
    ];
  }

  /**
   * @param array<string, int|string> $expected
   * @param array<string, int|null> $array
   */
  #[Test]
  #[DataProvider('mergeExistingDataProvider')]
  public function mergeExisting(array $expected, array $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | merge_existing(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The values should be mergeExistinged correctly.',
    );
  }

  /**
   * @return iterable<string, array{
   *   expected: array<string, int|string>,
   *   array: array<string, int|null>,
   *   parameters: string,
   * }>
   */
  public static function mergeExistingDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '',
    ];

    yield 'Single, no matches' => [
      'expected' => [
        'first' => 1,
      ],
      'array' => [
        'first' => 1,
      ],
      'parameters' => '{ second: 2, third: 3 }',
    ];

    yield 'Multiple, no matches' => [
      'expected' => [
        'first' => 1,
      ],
      'array' => [
        'first' => 1,
      ],
      'parameters' => '{ second: 2 }, { third: 3 }',
    ];

    yield 'Single, some matches' => [
      'expected' => [
        'first' => 1,
        'second' => 3,
      ],
      'array' => [
        'first' => 1,
        'second' => 2,
      ],
      'parameters' => '{ second: 3, third: 4, fourth: 5 }',
    ];

    yield 'Multiple, some matches' => [
      'expected' => [
        'first' => 1,
        'second' => 3,
        'third' => 4,
      ],
      'array' => [
        'first' => 1,
        'second' => 2,
        'third' => 3,
      ],
      'parameters' => '{ second: 3 }, { third: 4 }, { fourth: 5 }',
    ];

    yield 'Replaces nulls' => [
      'expected' => [
        'first' => 'replaced',
      ],
      'array' => [
        'first' => null,
      ],
      'parameters' => '{ first: "replaced" }',
    ];
  }

  /**
   * @param array<array-key, string> $expected
   * @param array<array-key, string> $array
   */
  #[Test]
  #[DataProvider('omitDataProvider')]
  public function omit(array $expected, array $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | omit(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The values should be omited correctly.',
    );
  }

  /**
   * @return iterable<string, array{
   *   expected: array<array-key, string>,
   *   array: array<array-key, string>,
   *   parameters: string,
   * }>
   */
  public static function omitDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '[]',
    ];

    yield 'Lists' => [
      'expected' => [0 => 'a', 2 => 'c'],
      'array' => ['a', 'b', 'c', 'd'],
      'parameters' => '[1, 3]',
    ];

    yield 'Arrays' => [
      'expected' => [
        'first' => 'a',
        'third' => 'c',
      ],
      'array' => [
        'first' => 'a',
        'second' => 'b',
        'third' => 'c',
        'fourth' => 'd',
      ],
      'parameters' => '["second", "fourth"]',
    ];

    yield 'Ignores undefined' => [
      'expected' => [
        'first' => 'a',
        'third' => 'c',
      ],
      'array' => [
        'first' => 'a',
        'second' => 'b',
        'third' => 'c',
        'fourth' => 'd',
      ],
      'parameters' => '["second", "fourth", "tenth"]',
    ];
  }

  /**
   * @param array<array-key, int|string> $expected
   * @param array<array-key, int|string> $array
   */
  #[Test]
  #[DataProvider('pickDataProvider')]
  public function pick(array $expected, array $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | pick(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The values should be picked correctly.',
    );
  }

  /**
   * @return iterable<string, array{
   *   expected: array<array-key, int|string>,
   *   array: array<array-key, int|string>,
   *   parameters: string,
   * }>
   */
  public static function pickDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '[]',
    ];

    yield 'List' => [
      'expected' => [0 => 'a', 2 => 'c'],
      'array' => ['a', 'b', 'c', 'd'],
      'parameters' => '[0, 2]',
    ];

    yield 'Array' => [
      'expected' => [
        'first' => 1,
        'third' => 3,
      ],
      'array' => [
        'first' => 1,
        'second' => 2,
        'third' => 3,
        'fourth' => 4,
      ],
      'parameters' => '["first", "third"]',
    ];

    yield 'Undefined keys but not strict' => [
      'expected' => [
        'first' => 1,
        'third' => 3,
      ],
      'array' => [
        'first' => 1,
        'second' => 2,
        'third' => 3,
        'fourth' => 4,
      ],
      'parameters' => '["first", "third", "fifth"], strict: false',
    ];
  }

  #[Test]
  public function pickFailsWithUndefinedKeys(): void
  {
    $environment = self::makeEnvironment('{{- { hello: "world" } | pick(["unknown-key"]) -}}', [new ArraysExtension()]);

    $this->expectException(RuntimeError::class);
    $environment->render('template');
  }

  /**
   * @param array<string, string> $expected
   * @param array<array-key, int|string> $array
   */
  #[Test]
  #[DataProvider('mapEntriesDataProvider')]
  public function mapEntries(array $expected, array $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | map_entries(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The values should be mapped correctly.',
    );
  }

  /**
   * @return iterable<string, array{
   *   expected: array<string, string>,
   *   array: array<array-key, int|string>,
   *   parameters: string,
   * }>
   */
  public static function mapEntriesDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '(key, value) => []',
    ];

    yield 'List' => [
      'expected' => [
        'Key: 0' => 'Value: a',
        'Key: 1' => 'Value: b',
        'Key: 2' => 'Value: c',
      ],
      'array' => ['a', 'b', 'c'],
      'parameters' => '(key, value) => ["Key: " ~ key, "Value: " ~ value]',
    ];

    yield 'Array' => [
      'expected' => [
        'Key: first' => 'Value: 1',
        'Key: second' => 'Value: 2',
        'Key: third' => 'Value: 3',
      ],
      'array' => [
        'first' => 1,
        'second' => 2,
        'third' => 3,
      ],
      'parameters' => '(key, value) => ["Key: " ~ key, "Value: " ~ value]',
    ];
  }
}
