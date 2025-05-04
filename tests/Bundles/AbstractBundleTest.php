<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Bundles;

use Averay\TwigExtensions\Bundles\AbstractBundle;
use Averay\TwigExtensions\Bundles\BundledExtensionsInterface;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\LastModifiedExtensionInterface;

#[CoversClass(AbstractBundle::class)]
final class AbstractBundleTest extends TestCase
{
  #[DataProvider('combiningFunctionsDataProvider')]
  public function testCombiningFunctions(string $method): void
  {
    /** @var list<mixed> $expected */
    $expected = [];

    /** @var list<ExtensionInterface> $extensions */
    $extensions = [];
    for ($i = 0; $i < 3; $i++) {
      $thisExpected = [new \stdClass(), new \stdClass(), new \stdClass()];
      \array_push($expected, ...$thisExpected);

      $extension = $this->createMock(ExtensionInterface::class);
      $extension->expects($this->once())->method($method)->willReturn($thisExpected);
      $extensions[] = $extension;
    }

    $bundle = self::createBundle($extensions);
    self::assertSame($expected, $bundle->{$method}(), 'The values should be combined.');
  }

  public static function combiningFunctionsDataProvider(): iterable
  {
    yield 'Token Parsers' => ['method' => 'getTokenParsers'];
    yield 'Node Visitors' => ['method' => 'getNodeVisitors'];
    yield 'Filters' => ['method' => 'getFilters'];
    yield 'Tests' => ['method' => 'getTests'];
    yield 'Functions' => ['method' => 'getFunctions'];
  }

  public function testExtensions(): void
  {
    $nestedExtensions = [
      self::createStub(ExtensionInterface::class),
      self::createStub(ExtensionInterface::class),
      self::createStub(ExtensionInterface::class),
    ];
    $bundle = self::createBundle($nestedExtensions);
    self::assertSame($nestedExtensions, $bundle->getExtensions(), 'The extensions should be combined.');
  }

  public function testOperators(): void
  {
    $createStub = static function (array $operators): ExtensionInterface {
      $extension = self::createStub(ExtensionInterface::class);
      $extension->method('getOperators')->willReturn($operators);
      return $extension;
    };

    $bundle = self::createBundle([
      $createStub([
        [
          'test-unary-one' => [123],
          'test-unary-two' => [456],
        ],
        [
          'test-binary-one' => [789],
          'test-binary-two' => [987],
        ],
      ]),
      $createStub([
        [
          'test-unary-three' => [654],
        ],
        [
          'test-binary-three' => [321],
        ],
      ]),
    ]);

    self::assertEquals(
      [
        [
          'test-unary-one' => [123],
          'test-unary-two' => [456],
          'test-unary-three' => [654],
        ],
        [
          'test-binary-one' => [789],
          'test-binary-two' => [987],
          'test-binary-three' => [321],
        ],
      ],
      $bundle->getOperators(),
      'The operators should be combined.',
    );
  }

  public function testGlobals(): void
  {
    $createStub = static function (array $globals): ExtensionInterface {
      $extension = self::createStubForIntersectionOfInterfaces([ExtensionInterface::class, GlobalsInterface::class]);
      $extension->method('getGlobals')->willReturn($globals);
      return $extension;
    };

    $bundle = self::createBundle([
      $createStub(globals: ['hello' => 'world', 'foo' => 'bar']),
      $createStub(globals: ['baz' => 'qux']),
      self::createStub(ExtensionInterface::class),
    ]);

    self::assertEquals(
      [
        'hello' => 'world',
        'foo' => 'bar',
        'baz' => 'qux',
      ],
      $bundle->getGlobals(),
      'The globals should be combined.',
    );
  }

  public function testLastModified(): void
  {
    $createStub = static function (int $lastModified): ExtensionInterface {
      $extension = self::createStub(LastModifiedExtensionInterface::class);
      $extension->method('getLastModified')->willReturn($lastModified);
      return $extension;
    };

    $bundle = self::createBundle([
      $createStub(lastModified: 3),
      $createStub(lastModified: 2),
      $createStub(lastModified: 1),
    ]);

    self::assertEquals(3, $bundle->getLastModified(), 'The latest last modified time should be returned.');
  }

  /**
   * @param list<ExtensionInterface> $extensions
   */
  private static function createBundle(array $extensions): AbstractBundle
  {
    return new class ($extensions) extends AbstractBundle {
      /**
       * @param list<ExtensionInterface> $extensions
       */
      public function __construct(array $extensions)
      {
        $this->extensions = $extensions;
      }
    };
  }
}
