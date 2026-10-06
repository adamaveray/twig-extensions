<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Extensions\ArraysExtension;
use Averay\TwigExtensions\Extensions\DatesExtension;
use Averay\TwigExtensions\Extensions\ValuesExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Sandbox\SecurityNotAllowedTestError;
use Twig\Sandbox\SecurityPolicy;

/**
 * @internal
 */
#[CoversClass(ArraysExtension::class)]
#[CoversClass(DatesExtension::class)]
#[CoversClass(ValuesExtension::class)]
final class SandboxTest extends TestCase
{
  #[Test]
  #[DataProvider('alwaysAllowedTestsDataProvider')]
  public function alwaysAllowedTests(string $statement, mixed $value = null): void
  {
    $environment = self::makeSandboxedEnvironment('{{- (' . $statement . ') ? "yes" : "no" -}}');
    self::assertRenders(
      'yes',
      $environment,
      context: ['value' => $value],
      message: 'The test should be usable in a sandbox without being explicitly allowed.',
    );
  }

  /**
   * @return iterable<string, array{ statement: string, value?: mixed }>
   */
  public static function alwaysAllowedTestsDataProvider(): iterable
  {
    // Arrays
    yield 'all_empty' => ['statement' => 'value is all_empty', 'value' => ['', null]];
    yield 'any_empty' => ['statement' => 'value is any_empty', 'value' => ['a', null]];

    // Dates
    $date = new \DateTimeImmutable('2000-01-02 03:04:05');
    foreach (['same_date as', 'same_year as', 'same_month as', 'same_day as', 'same_time as'] as $test) {
      yield $test => ['statement' => 'value is ' . $test . ' value', 'value' => $date];
    }

    // Values
    yield 'instance of' => ['statement' => 'value is instance of "stdClass"', 'value' => new \stdClass()];
    yield 'array' => ['statement' => 'value is array', 'value' => []];
    yield 'bool' => ['statement' => 'value is bool', 'value' => true];
    yield 'countable' => ['statement' => 'value is countable', 'value' => []];
    yield 'float' => ['statement' => 'value is float', 'value' => 1.5];
    yield 'int' => ['statement' => 'value is int', 'value' => 1];
    yield 'numeric' => ['statement' => 'value is numeric', 'value' => '1'];
    yield 'object' => ['statement' => 'value is object', 'value' => new \stdClass()];
    yield 'resource' => ['statement' => 'value is resource', 'value' => \fopen('php://memory', 'rb')];
    yield 'scalar' => ['statement' => 'value is scalar', 'value' => 'a'];
    yield 'string' => ['statement' => 'value is string', 'value' => 'a'];
  }

  #[Test]
  #[DataProvider('notAllowedTestsDataProvider')]
  public function notAllowedTests(string $statement, mixed $value = null): void
  {
    $environment = self::makeSandboxedEnvironment('{{- (' . $statement . ') ? "yes" : "no" -}}');
    self::assertThrows(
      static function () use ($environment, $value): void {
        $environment->render('template', ['value' => $value]);
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof SecurityNotAllowedTestError,
      message: 'The test should require explicitly allowing in a sandbox.',
    );
  }

  /**
   * @return iterable<string, array{ statement: string, value?: mixed }>
   */
  public static function notAllowedTestsDataProvider(): iterable
  {
    // Values
    yield 'callable' => ['statement' => 'value is callable', 'value' => 'strlen'];
  }

  private static function makeSandboxedEnvironment(string $template): Environment
  {
    $policy = new SecurityPolicy();
    $policy->setStrict(true);

    return self::makeEnvironment($template, [
      new ArraysExtension(),
      new DatesExtension(),
      new ValuesExtension(),
      new SandboxExtension($policy, sandboxed: true),
    ]);
  }
}
