<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Bundles;

use Averay\TwigExtensions\Bundles\AbstractBundle;
use Averay\TwigExtensions\Bundles\ExtensionBundleInterface;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Twig\Extension\ExtensionInterface;

#[CoversClass(AbstractBundle::class)]
final class AbstractBundleTest extends TestCase
{
  public function testExtensions(): void
  {
    $extensions = [
      self::createStub(ExtensionInterface::class),
      self::createStub(ExtensionInterface::class),
      self::createStub(ExtensionInterface::class),
    ];

    $bundle = new class ($extensions) extends AbstractBundle {
      /**
       * @param list<ExtensionInterface> $extensions
       */
      public function __construct(array $extensions)
      {
        $this->extensions = $extensions;
      }
    };

    self::assertSame($extensions, $bundle->getExtensions(), 'The extensions should be stored.');
  }

  public function testAddingBundles(): void
  {
    $initialExtensions = [self::createStub(ExtensionInterface::class), self::createStub(ExtensionInterface::class)];
    $addedExtensions = [self::createStub(ExtensionInterface::class), self::createStub(ExtensionInterface::class)];

    $innerBundle = $this->createMock(ExtensionBundleInterface::class);
    $innerBundle->expects($this->once())->method('getExtensions')->willReturn($addedExtensions);

    $bundle = new class ($initialExtensions) extends AbstractBundle {
      /**
       * @param list<ExtensionInterface> $extensions
       */
      public function __construct(array $extensions)
      {
        $this->extensions = $extensions;
      }

      public function withAddedBundle(ExtensionBundleInterface $bundle): static
      {
        return $this->withBundle($bundle);
      }
    };

    $augmentedBundle = $bundle->withAddedBundle($innerBundle);

    self::assertSame(
      \array_merge($initialExtensions, $addedExtensions),
      $augmentedBundle->getExtensions(),
      'The combined extensions should be stored.',
    );

    self::assertNotSame($augmentedBundle, $bundle, 'A new bundle instance should be created.');
  }
}
