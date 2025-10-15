<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Units;

use Averay\TwigExtensions\Extensions\UnitsExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\Values\FileSizeSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Extra\Intl\IntlExtension;

#[CoversClass(UnitsExtension::class)]
#[CoversClass(FileSizeSystem::class)]
final class UnitsTest extends TestCase
{
  private const string DEFAULT_LOCALE = 'en_GB';

  #[DataProvider('siAmountDataProvider')]
  public function testSiAmount(string $expected, int|float $amount, string $unit, string $parameters = ''): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- amount | format_si_amount(unit$parameters) -}}
      TWIG
      ,
      [new IntlExtension(), new UnitsExtension(self::DEFAULT_LOCALE)],
    );
    self::assertRenders(
      $expected,
      $environment,
      context: ['amount' => $amount, 'unit' => $unit],
      message: 'The SI amount should be formatted correctly.',
    );
  }

  public static function siAmountDataProvider(): iterable
  {
    yield 'Small amount (no prefix)' => [
      'expected' => '5Hz',
      'amount' => 5,
      'unit' => 'Hz',
    ];

    yield 'Kilohertz' => [
      'expected' => '1kHz',
      'amount' => 1_000,
      'unit' => 'Hz',
    ];

    yield 'Megahertz' => [
      'expected' => '2.5MHz',
      'amount' => 2_500_000,
      'unit' => 'Hz',
    ];

    yield 'Gigahertz' => [
      'expected' => '3.2GHz',
      'amount' => 3_200_000_000,
      'unit' => 'Hz',
    ];

    yield 'Terahertz' => [
      'expected' => '1.5THz',
      'amount' => 1_500_000_000_000,
      'unit' => 'Hz',
    ];

    yield 'Negative amount' => [
      'expected' => '-500kHz',
      'amount' => -500_000,
      'unit' => 'Hz',
    ];

    yield 'Watts' => [
      'expected' => '750kW',
      'amount' => 750_000,
      'unit' => 'W',
    ];

    yield 'Fixed precision 0' => [
      'expected' => '1kHz',
      'amount' => 1_234,
      'unit' => 'Hz',
      'parameters' => ', precision: 0',
    ];

    yield 'Fixed precision 2' => [
      'expected' => '1.23kHz',
      'amount' => 1_234,
      'unit' => 'Hz',
      'parameters' => ', precision: 2',
    ];

    yield 'Array precision - base unit' => [
      'expected' => '500Hz',
      'amount' => 500,
      'unit' => 'Hz',
      'parameters' => ', precision: {"": 0, "M": 2}',
    ];

    yield 'Array precision - kilo (inherits base)' => [
      'expected' => '500kHz',
      'amount' => 500_000,
      'unit' => 'Hz',
      'parameters' => ', precision: {"": 0, "M": 2}',
    ];

    yield 'Array precision - mega (uses 2)' => [
      'expected' => '1.23MHz',
      'amount' => 1_234_567,
      'unit' => 'Hz',
      'parameters' => ', precision: {"": 0, "M": 2}',
    ];

    yield 'Array precision - giga (inherits mega)' => [
      'expected' => '1.23GHz',
      'amount' => 1_234_567_890,
      'unit' => 'Hz',
      'parameters' => ', precision: {"": 0, "M": 2}',
    ];

    yield 'Custom prefix step percentage (0.95 default)' => [
      'expected' => '1kHz',
      'amount' => 950,
      'unit' => 'Hz',
    ];

    yield 'Custom prefix step percentage (0.99)' => [
      'expected' => '950Hz',
      'amount' => 950,
      'unit' => 'Hz',
      'parameters' => ', prefix_step_percentage: 0.99',
    ];

    yield 'Very large amount' => [
      'expected' => '1.5YHz',
      'amount' => 1.5e24,
      'unit' => 'Hz',
    ];

    yield 'Zero' => [
      'expected' => '0Hz',
      'amount' => 0,
      'unit' => 'Hz',
    ];

    yield 'Small fractional amount' => [
      'expected' => '0.5Hz',
      'amount' => 0.5,
      'unit' => 'Hz',
      'parameters' => ', precision: 1',
    ];
  }

  #[DataProvider('bytesDataProvider')]
  public function testBytes(string $expected, int|float $bytes, string $parameters = ''): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- bytes | format_bytes($parameters) -}}
      TWIG
      ,
      [new IntlExtension(), new UnitsExtension(self::DEFAULT_LOCALE)],
    );
    self::assertRenders(
      $expected,
      $environment,
      context: ['bytes' => $bytes],
      message: 'The bytes should be formatted correctly.',
    );
  }

  public static function bytesDataProvider(): iterable
  {
    // Decimal system (default, 1000-based)
    yield 'Bytes (decimal)' => [
      'expected' => '500B',
      'bytes' => 500,
    ];

    yield 'Kilobytes (decimal)' => [
      'expected' => '1kB',
      'bytes' => 1_000,
    ];

    yield 'Megabytes (decimal)' => [
      'expected' => '2.5MB',
      'bytes' => 2_500_000,
    ];

    yield 'Gigabytes (decimal)' => [
      'expected' => '1.5GB',
      'bytes' => 1_500_000_000,
    ];

    yield 'Terabytes (decimal)' => [
      'expected' => '3.2TB',
      'bytes' => 3_200_000_000_000,
    ];

    yield 'Petabytes (decimal)' => [
      'expected' => '1PB',
      'bytes' => 1_000_000_000_000_000,
    ];

    yield 'Zero bytes' => [
      'expected' => '0B',
      'bytes' => 0,
    ];

    yield 'Negative bytes' => [
      'expected' => '-1.5MB',
      'bytes' => -1_500_000,
    ];

    // Binary IEC system (1024-based with 'i' infix)
    yield 'Bytes (binary-iec)' => [
      'expected' => '500iB',
      'bytes' => 500,
      'parameters' => 'system: "binary-iec"',
    ];

    yield 'Kibibytes (binary-iec)' => [
      'expected' => '1kiB',
      'bytes' => 1_024,
      'parameters' => 'system: "binary-iec"',
    ];

    yield 'Mebibytes (binary-iec)' => [
      'expected' => '2.44MiB',
      'bytes' => 2_560_000,
      'parameters' => 'system: "binary-iec"',
    ];

    yield 'Gibibytes (binary-iec)' => [
      'expected' => '1.4GiB',
      'bytes' => 1_500_000_000,
      'parameters' => 'system: "binary-iec"',
    ];

    yield 'Tebibytes (binary-iec)' => [
      'expected' => '2.91TiB',
      'bytes' => 3_200_000_000_000,
      'parameters' => 'system: "binary-iec"',
    ];

    // Binary JEDEC system (1024-based, 'i' only for T and above)
    yield 'Bytes (binary-jedec)' => [
      'expected' => '500B',
      'bytes' => 500,
      'parameters' => 'system: "binary-jedec"',
    ];

    yield 'Kilobytes (binary-jedec)' => [
      'expected' => '1kB',
      'bytes' => 1_024,
      'parameters' => 'system: "binary-jedec"',
    ];

    yield 'Megabytes (binary-jedec)' => [
      'expected' => '2.44MB',
      'bytes' => 2_560_000,
      'parameters' => 'system: "binary-jedec"',
    ];

    yield 'Gigabytes (binary-jedec)' => [
      'expected' => '1.4GB',
      'bytes' => 1_500_000_000,
      'parameters' => 'system: "binary-jedec"',
    ];

    yield 'Tebibytes (binary-jedec)' => [
      'expected' => '2.91TiB',
      'bytes' => 3_200_000_000_000,
      'parameters' => 'system: "binary-jedec"',
    ];

    yield 'Pebibytes (binary-jedec)' => [
      'expected' => '1PiB',
      'bytes' => 1_125_899_906_842_624, // Exactly 1 PiB
      'parameters' => 'system: "binary-jedec"',
    ];

    // Using 'binary' as shorthand for binary-jedec
    yield 'Binary shorthand' => [
      'expected' => '1kB',
      'bytes' => 1_024,
      'parameters' => 'system: "binary"',
    ];

    // Precision variations
    yield 'Fixed precision 0' => [
      'expected' => '1kB',
      'bytes' => 1_234,
      'parameters' => 'precision: 0',
    ];

    yield 'Fixed precision 3' => [
      'expected' => '1.234kB',
      'bytes' => 1_234,
      'parameters' => 'precision: 3',
    ];

    yield 'Array precision - bytes' => [
      'expected' => '500B',
      'bytes' => 500,
      'parameters' => 'precision: {"": 0, "M": 3}',
    ];

    yield 'Array precision - kilobytes' => [
      'expected' => '500kB',
      'bytes' => 500_000,
      'parameters' => 'precision: {"": 0, "M": 3}',
    ];

    yield 'Array precision - megabytes' => [
      'expected' => '1.235MB',
      'bytes' => 1_234_567,
      'parameters' => 'precision: {"": 0, "M": 3}',
    ];

    // Prefix step percentage
    yield 'Prefix step percentage (950 B -> kB at 0.95)' => [
      'expected' => '1kB',
      'bytes' => 950,
    ];

    yield 'Prefix step percentage (950 B stays B at 0.99)' => [
      'expected' => '950B',
      'bytes' => 950,
      'parameters' => 'prefix_step_percentage: 0.99',
    ];

    // Using FileSizeSystem enum
    yield 'Using FileSizeSystem enum' => [
      'expected' => '1kiB',
      'bytes' => 1_024,
      'parameters' => 'system: constant("Averay\\\\TwigExtensions\\\\Values\\\\FileSizeSystem::BinaryIec")',
    ];
  }

  public function testLocaleFormatting(): void
  {
    // Test that number formatting respects locale (French uses comma as decimal separator).
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- bytes | format_bytes(locale: "fr_FR") -}}
      TWIG
      ,
      [new IntlExtension(), new UnitsExtension(self::DEFAULT_LOCALE)],
    );
    self::assertRenders(
      '1,5MB',
      $environment,
      context: ['bytes' => 1_500_000],
      message: 'The bytes should use French locale formatting.',
    );
  }

  public function testSiAmountLocaleFormatting(): void
  {
    // Test that number formatting respects locale for si_amount.
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- amount | format_si_amount("Hz", locale: "de_DE") -}}
      TWIG
      ,
      [new IntlExtension(), new UnitsExtension(self::DEFAULT_LOCALE)],
    );
    self::assertRenders(
      '2,5MHz',
      $environment,
      context: ['amount' => 2_500_000],
      message: 'The SI amount should use German locale formatting.',
    );
  }
}
