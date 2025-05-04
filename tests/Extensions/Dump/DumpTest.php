<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Dump;

use Averay\TwigExtensions\Extensions\DumpExtension;
use Averay\TwigExtensions\Helpers\TemplateDumper;
use Averay\TwigExtensions\Helpers\TemplateDumperInterface;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(DumpExtension::class)]
#[CoversClass(TemplateDumper::class)]
final class DumpTest extends TestCase
{
  public function testDumpReceivesArgumentsList(): void
  {
    $dumper = self::createStubDumper([0 => 'hello', 1 => 'world'], '%%dump-output%%');

    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {{ dump("hello", "world") }}
      world
      TWIG
      ,
      [new DumpExtension($dumper)],
    );

    self::assertEquals(
      'Hello' . "\n" . '%%dump-output%%%%dump-output%%' . "\n" . 'world',
      self::genericiseDumpId($environment->render('template')),
    );
  }

  public function testDumpReceivesArgumentsArray(): void
  {
    $dumper = self::createStubDumper(['first' => 'hello', 'second' => 'world'], '%%dump-output%%');

    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {{ dump(first: "hello", second: "world") }}
      world
      TWIG
      ,
      [new DumpExtension($dumper)],
    );

    self::assertEquals(
      'Hello' . "\n" . '%%dump-output%%%%dump-output%%' . "\n" . 'world',
      self::genericiseDumpId($environment->render('template')),
    );
  }

  public function testDumpReceivesContext(): void
  {
    $context = ['hello' => 'world'];

    $dumper = self::createStubDumper(['context' => $context], '%%dump-output%%');

    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {{ dump() }}
      world
      TWIG
      ,
      [new DumpExtension($dumper)],
    );

    self::assertEquals(
      'Hello' . "\n" . '%%dump-output%%' . "\n" . 'world',
      self::genericiseDumpId($environment->render('template', $context)),
    );
  }

  #[DataProvider('dumpDataProvider')]
  public function testTemplateDumper(array $arguments, string $syntax, bool $labels): void
  {
    $dumper = new TemplateDumper();
    ob_start();
    foreach ($arguments as $key => $argument) {
      $dumper->dumpValue($argument, label: $labels ? (string) $key : null);
    }
    $output = ob_get_clean();

    $environment = self::makeEnvironment(
      <<<TWIG
      Hello
      {{ dump($syntax) }}
      world
      TWIG
      ,
      [new DumpExtension()],
    );

    self::assertEquals(
      'Hello' . "\n" . self::genericiseDumpId($output) . "\n" . 'world',
      self::genericiseDumpId($environment->render('template')),
    );
  }

  public static function dumpDataProvider(): iterable
  {
    yield 'Single arguments' => [['hello world'], '"hello world"', false];
    yield 'Multiple argument' => [['hello', 'world'], '"hello", "world"', true];
  }

  private static function genericiseDumpId(string $html): string
  {
    return \preg_replace(pattern: '~' . \preg_quote('sf-dump-', '~') . '\d+~u', replacement: '$1%ID%', subject: $html);
  }

  private static function createStubDumper(
    array $expectedConsecutiveArguments,
    string $dumpOutput,
    string $message = '',
  ): TemplateDumperInterface {
    $dumper = self::createStub(TemplateDumperInterface::class);
    $dumper
      ->method('dumpValue')
      ->willReturnCallback(static function (mixed $value, mixed $label) use (
        $expectedConsecutiveArguments,
        $dumpOutput,
        $message,
      ): void {
        static $i = 0;

        try {
          self::assertEquals(\array_values($expectedConsecutiveArguments)[$i], $value, $message);
          self::assertEquals(\array_keys($expectedConsecutiveArguments)[$i], $label, $message);
          echo $dumpOutput;
        } finally {
          $i++;
        }
      });

    return $dumper;
  }
}
