<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\RuntimeLoaders;

use Averay\TwigExtensions\RuntimeLoaders\ExcludingRuntimeLoader;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * @internal
 */
#[CoversClass(ExcludingRuntimeLoader::class)]
final class ExcludingRuntimeLoaderTest extends TestCase
{
  #[Test]
  public function loadsOtherRuntimes(): void
  {
    $runtime = new \stdClass();

    $innerLoader = $this->createMock(RuntimeLoaderInterface::class);
    $innerLoader
      ->expects($this->once())
      ->method('load')
      ->with(\stdClass::class)
      ->willReturn($runtime);

    $loader = new ExcludingRuntimeLoader($innerLoader, [\ArrayObject::class]);

    self::assertSame($runtime, $loader->load(\stdClass::class), 'A runtime that is not excluded should be loaded.');
  }

  #[Test]
  public function skipsExcludedRuntimes(): void
  {
    $innerLoader = $this->createMock(RuntimeLoaderInterface::class);
    $innerLoader->expects($this->never())->method('load');

    $loader = new ExcludingRuntimeLoader($innerLoader, [\ArrayObject::class]);

    self::assertNull(
      $loader->load(\ArrayObject::class),
      'An excluded runtime should not be loaded, without consulting the wrapped loader.',
    );
  }
}
