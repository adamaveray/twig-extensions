<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Arrays;

use Averay\TwigExtensions\Extensions\ArraysExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Error\RuntimeError;

#[CoversClass(ArraysExtension::class)]
final class ArraySortTest extends TestCase
{
  #[DataProvider('sortDataProvider')]
  public function testSort(array $expected, iterable $array, string $parameters): void
  {
    $environment = self::makeEnvironment('{{- array | sort(' . $parameters . ') | json_encode | raw -}}', [
      new ArraysExtension(),
    ]);

    $result = $environment->render('template', ['array' => $array]);
    self::assertEquals(
      $expected,
      \json_decode($result, true, flags: \JSON_THROW_ON_ERROR),
      'The array should be sorted correctly.',
    );
  }

  public static function sortDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => [],
      'array' => [],
      'parameters' => '',
    ];

    // Default
    yield 'List, default' => [
      'expected' => [
        1 => 'a',
        2 => 'b',
        0 => 'c',
      ],
      'array' => ['c', 'a', 'b'],
      'parameters' => '',
    ];

    yield 'List, default, removing keys' => [
      'expected' => [
        0 => 'a',
        1 => 'b',
        2 => 'c',
      ],
      'array' => ['c', 'a', 'b'],
      'parameters' => 'preserve_keys: false',
    ];

    yield 'Array, default' => [
      'expected' => [
        'second' => 'a',
        'third' => 'b',
        'first' => 'c',
      ],
      'array' => [
        'first' => 'c',
        'second' => 'a',
        'third' => 'b',
      ],
      'parameters' => '',
    ];

    yield 'Array, default, removing keys' => [
      'expected' => [
        0 => 'a',
        1 => 'b',
        2 => 'c',
      ],
      'array' => [
        'first' => 'c',
        'second' => 'a',
        'third' => 'b',
      ],
      'parameters' => 'preserve_keys: false',
    ];

    yield 'List, default, custom' => [
      'expected' => [
        2 => 'b1',
        0 => 'c12',
        1 => 'a123',
      ],
      'array' => ['c12', 'a123', 'b1'],
      'parameters' => '(a, b) => (a | length) - (b | length)',
    ];

    yield 'List, default, removing keys, custom' => [
      'expected' => [
        0 => 'b1',
        1 => 'c12',
        2 => 'a123',
      ],
      'array' => ['c12', 'a123', 'b1'],
      'parameters' => '(a, b) => (a | length) - (b | length), preserve_keys: false',
    ];

    yield 'Array, default, custom' => [
      'expected' => [
        'third' => 'b1',
        'first' => 'c12',
        'second' => 'a123',
      ],
      'array' => [
        'first' => 'c12',
        'second' => 'a123',
        'third' => 'b1',
      ],
      'parameters' => '(a, b) => (a | length) - (b | length)',
    ];

    yield 'Array, default, removing keys, custom' => [
      'expected' => [
        0 => 'b1',
        1 => 'c12',
        2 => 'a123',
      ],
      'array' => [
        'first' => 'c12',
        'second' => 'a123',
        'third' => 'b1',
      ],
      'parameters' => '(a, b) => (a | length) - (b | length), preserve_keys: false',
    ];

    yield 'Iterator, default' => [
      'expected' => [
        1 => 'a',
        2 => 'b',
        0 => 'c',
      ],
      'array' => new \ArrayIterator(['c', 'a', 'b']),
      'parameters' => '',
    ];

    // By keys
    yield 'List, by keys' => [
      'expected' => [
        0 => 'c',
        1 => 'a',
        2 => 'b',
      ],
      'array' => ['c', 'a', 'b'],
      'parameters' => 'by: "key"',
    ];

    yield 'Array, by keys' => [
      'expected' => [
        'a' => 'second',
        'b' => 'third',
        'c' => 'first',
      ],
      'array' => [
        'c' => 'first',
        'a' => 'second',
        'b' => 'third',
      ],
      'parameters' => 'by: "key"',
    ];

    yield 'Array, by keys, custom' => [
      'expected' => [
        'b1' => 'third',
        'c12' => 'first',
        'a123' => 'second',
      ],
      'array' => [
        'c12' => 'first',
        'a123' => 'second',
        'b1' => 'third',
      ],
      'parameters' => '(a, b) => (a | length) - (b | length), by: "key"',
    ];
  }

  public function testSortFailsWithInvalidTarget(): void
  {
    $this->expectException(RuntimeError::class);
    $this->testSort([], [], 'by: "other"');
  }

  public function testSortFailsWithNonPreservedKeys(): void
  {
    $this->expectException(RuntimeError::class);
    $this->testSort([], [], 'by: "key", preserve_keys: false');
  }
}
