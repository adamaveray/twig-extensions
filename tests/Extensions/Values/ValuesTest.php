<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Values;

use Averay\TwigExtensions\Extensions\ValuesExtension;
use Averay\TwigExtensions\Nodes\Tests\InstanceOfTest;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ValuesExtension::class)]
#[CoversClass(InstanceOfTest::class)]
final class ValuesTest extends TestCase
{
  #[DataProvider('instanceOfDataProvider')]
  public function testInstanceOf(bool $expected, mixed $value, string $className): void
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
}
