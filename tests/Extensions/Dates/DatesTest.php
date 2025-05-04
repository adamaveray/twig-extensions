<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Dates;

use Averay\TwigExtensions\Extensions\DatesExtension;
use Averay\TwigExtensions\Nodes\Tests\SameDateAsTest;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Error\SyntaxError;

#[CoversClass(DatesExtension::class)]
#[CoversClass(SameDateAsTest::class)]
final class DatesTest extends TestCase
{
  private static function getTestDate(): \DateTimeInterface
  {
    return new \DateTimeImmutable('2000-01-01T00:00:00Z', new \DateTimeZone('UTC'));
  }

  private static function getTestDateAlternateTimezone(): \DateTimeInterface
  {
    return self::getTestDate()->setTimezone(new \DateTimeZone('Australia/Brisbane'));
  }

  #[DataProvider('isSameDateDataProvider')]
  public function testIsSameDate(bool $expected, string|array $statements, array $context): void
  {
    $statements = \is_string($statements) ? [$statements] : $statements;
    $extensions = [new DatesExtension()];
    foreach ($statements as $statement) {
      self::assertMatchesTest(
        $expected,
        $statement,
        $extensions,
        $context,
        'The date equality should be determined correctly.',
      );
    }
  }

  public static function isSameDateDataProvider(): iterable
  {
    yield 'Same' => [
      true,
      [
        'base is same_date as(comparison)',
        'base is same_year as(comparison)',
        'base is same_month as(comparison)',
        'base is same_day as(comparison)',
        'base is same_time as(comparison)',
      ],
      [
        'base' => self::getTestDate(),
        'comparison' => self::getTestDate(),
      ],
    ];

    yield 'Different' => [
      false,
      [
        'base is same_date as(comparison)',
        'base is same_year as(comparison)',
        'base is same_month as(comparison)',
        'base is same_day as(comparison)',
        'base is same_time as(comparison)',
      ],
      [
        'base' => self::getTestDate(),
        'comparison' => new \DateTimeImmutable('2010-02-03T01:02:03Z'),
      ],
    ];

    yield 'Same with different timezones automatically converted' => [
      true,
      ['base is same_date as(comparison)', 'base is same_time as(comparison)'],
      [
        'base' => self::getTestDate(),
        'comparison' => self::getTestDateAlternateTimezone(),
      ],
    ];

    yield 'Same with different timezones manually converted' => [
      true,
      [
        'base is same_date as(comparison, timezone: timezone)',
        'base is same_year as(comparison, timezone: timezone)',
        'base is same_month as(comparison, timezone: timezone)',
        'base is same_day as(comparison, timezone: timezone)',
        'base is same_time as(comparison, timezone: timezone)',
      ],
      [
        'base' => self::getTestDate(),
        'comparison' => self::getTestDateAlternateTimezone(),
        'timezone' => new \DateTimeZone('Asia/Tokyo'),
      ],
    ];

    yield 'Same with different timezones manually preserved' => [
      false,
      ['base is same_date as(comparison, timezone: false)', 'base is same_time as(comparison, timezone: false)'],
      [
        'base' => self::getTestDate(),
        'comparison' => self::getTestDateAlternateTimezone(),
      ],
    ];

    yield 'Same year' => [
      true,
      'base is same_year as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-12-31T23:59:59Z'),
      ],
    ];

    yield 'Different year' => [
      false,
      'base is same_year as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2001-01-01T00:00:00Z'),
      ],
    ];

    yield 'Same month' => [
      true,
      'base is same_month as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-01-31T23:59:59Z'),
      ],
    ];

    yield 'Different month' => [
      false,
      'base is same_month as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-02-01T00:00:00Z'),
      ],
    ];

    yield 'Same day' => [
      true,
      'base is same_day as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-01-01T23:59:59Z'),
      ],
    ];

    yield 'Different day' => [
      false,
      'base is same_day as(comparison)',
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-01-02T00:00:00Z'),
      ],
    ];

    yield 'Same with custom format' => [
      true,
      ['base is same_date as(comparison, format: "Y")'],
      [
        'base' => new \DateTimeImmutable('2000-01-01T00:00:00Z'),
        'comparison' => new \DateTimeImmutable('2000-12-31T23:59:59Z'),
      ],
    ];
  }

  #[DataProvider('rejectsCustomFormatForSpecificComparatorsDataProvider')]
  public function testRejectsCustomFormatForSpecificComparators(string $test): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- datetime is $test as(datetime, format: "hello world") -}}
      TWIG
      ,
      [new DatesExtension()],
    );

    $this->expectException(SyntaxError::class);

    $environment->render('template', ['datetime' => new \DateTimeImmutable('2000-01-01T00:00:00Z')]);
  }

  public static function rejectsCustomFormatForSpecificComparatorsDataProvider(): iterable
  {
    yield 'same_year' => ['same_year'];
    yield 'same_month' => ['same_month'];
    yield 'same_day' => ['same_day'];
    yield 'same_time' => ['same_time'];
  }
}
