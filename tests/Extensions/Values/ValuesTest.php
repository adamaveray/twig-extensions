<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Values;

use Averay\TwigExtensions\Extensions\ValuesExtension;
use Averay\TwigExtensions\Nodes\Tests\InstanceOfTest;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversClass(ValuesExtension::class)]
#[CoversClass(InstanceOfTest::class)]
final class ValuesTest extends TestCase
{
  #[Test]
  #[DataProvider('jsValueDataProvider')]
  public function jsValue(string $expected, string $value, string $parameters = ''): void
  {
    $environment = self::makeEnvironment('{{- ' . $value . ' | js_value(' . $parameters . ') -}}', [
      new ValuesExtension(),
    ]);
    self::assertRenders(
      $expected,
      $environment,
      context: ['value' => $value],
      message: 'The value should be encoded as an inline JS value.',
    );
  }

  /**
   * @return iterable<string, array{ expected: string, value: string, parameters?: string }>
   */
  public static function jsValueDataProvider(): iterable
  {
    yield 'String' => [
      'expected' => '"hello world"',
      'value' => "'hello world'",
    ];

    yield 'Array' => [
      'expected' => '["a","b","c"]',
      'value' => "['a', 'b', 'c']",
    ];

    yield 'Object' => [
      'expected' => '{"a":1,"b":2,"c":3}',
      'value' => '{ a: 1, b: 2, c: 3 }',
    ];

    yield 'Pretty printed' => [
      'expected' => <<<'TXT'
        {
            "a": 1,
            "b": 2,
            "c": 3
        }
        TXT,
      'value' => '{ a: 1, b: 2, c: 3 }',
      'parameters' => 'flags: constant("\\JSON_PRETTY_PRINT")',
    ];
  }

  #[Test]
  #[DataProvider('instanceOfDataProvider')]
  public function instanceOfTest(bool $expected, mixed $value, string $className): void
  {
    $environment = self::makeEnvironment('{{- value is instance of class_name ? "yes" : "no" -}}', [
      new ValuesExtension(),
    ]);
    self::assertRenders(
      $expected ? 'yes' : 'no',
      $environment,
      context: [
        'value' => $value,
        'class_name' => $className,
      ],
    );
  }

  /**
   * @return iterable<string, array{ expected: bool, value: object, className: class-string }>
   */
  public static function instanceOfDataProvider(): iterable
  {
    yield 'Match' => [
      'expected' => true,
      'value' => new \DateTimeImmutable(),
      'className' => \DateTimeInterface::class,
    ];

    yield 'No match' => [
      'expected' => false,
      'value' => new \stdClass(),
      'className' => \DateTimeInterface::class,
    ];
  }

  #[Test]
  #[DataProvider('typeTestDataProvider')]
  public function typeTest(bool $expected, string $test, mixed $value): void
  {
    $environment = self::makeEnvironment('{{- value is ' . $test . ' ? "yes" : "no" -}}', [new ValuesExtension()]);
    self::assertRenders($expected ? 'yes' : 'no', $environment, context: ['value' => $value]);
  }

  /**
   * @return iterable<string, array{ expected: bool, test: string, value: mixed }>
   */
  public static function typeTestDataProvider(): iterable
  {
    yield 'Array match' => ['expected' => true, 'test' => 'array', 'value' => ['a', 'b']];
    yield 'Array no match' => ['expected' => false, 'test' => 'array', 'value' => new \ArrayObject()];

    yield 'Bool match' => ['expected' => true, 'test' => 'bool', 'value' => false];
    yield 'Bool no match' => ['expected' => false, 'test' => 'bool', 'value' => 0];

    yield 'Callable match' => ['expected' => true, 'test' => 'callable', 'value' => static fn(): null => null];
    yield 'Callable no match' => ['expected' => false, 'test' => 'callable', 'value' => 'not a function'];

    yield 'Countable match' => ['expected' => true, 'test' => 'countable', 'value' => new \ArrayObject()];
    yield 'Countable no match' => ['expected' => false, 'test' => 'countable', 'value' => new \stdClass()];

    yield 'Float match' => ['expected' => true, 'test' => 'float', 'value' => 1.5];
    yield 'Float no match' => ['expected' => false, 'test' => 'float', 'value' => 1];

    yield 'Int match' => ['expected' => true, 'test' => 'int', 'value' => 1];
    yield 'Int no match' => ['expected' => false, 'test' => 'int', 'value' => '1'];

    yield 'Numeric match' => ['expected' => true, 'test' => 'numeric', 'value' => '1.5'];
    yield 'Numeric no match' => ['expected' => false, 'test' => 'numeric', 'value' => 'abc'];

    yield 'Object match' => ['expected' => true, 'test' => 'object', 'value' => new \stdClass()];
    yield 'Object no match' => ['expected' => false, 'test' => 'object', 'value' => ['a' => 1]];

    yield 'Resource match' => ['expected' => true, 'test' => 'resource', 'value' => \fopen('php://memory', 'rb')];
    yield 'Resource no match' => ['expected' => false, 'test' => 'resource', 'value' => 'php://memory'];

    yield 'Scalar match' => ['expected' => true, 'test' => 'scalar', 'value' => 'abc'];
    yield 'Scalar no match' => ['expected' => false, 'test' => 'scalar', 'value' => null];

    yield 'String match' => ['expected' => true, 'test' => 'string', 'value' => 'abc'];
    yield 'String no match' => ['expected' => false, 'test' => 'string', 'value' => 1];
  }
}
