<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\SymfonyCompatibility;

use Averay\TwigExtensions\Extensions\SymfonyCompatibilityExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bridge\Twig\AppVariable;

/**
 * @internal
 */
#[CoversClass(SymfonyCompatibilityExtension::class)]
final class SymfonyCompatibilityExtensionTest extends TestCase
{
  #[Test]
  #[DataProvider('globalsDataProvider')]
  public function globals(array $expected, ?AppVariable $appVariable): void
  {
    $extension = new SymfonyCompatibilityExtension($appVariable);
    self::assertSame($expected, $extension->getGlobals(), 'The globals should be generated correctly.');
  }

  public static function globalsDataProvider(): iterable
  {
    yield 'No variable' => [
      'expected' => [],
      'appVariable' => null,
    ];

    $appVariable = new AppVariable();
    yield 'With variable' => [
      'expected' => ['app' => $appVariable],
      'appVariable' => $appVariable,
    ];
  }
}
