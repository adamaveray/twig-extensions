<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Bundles\ExtensionBundleInterface;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TwigEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @internal
 */
#[CoversClass(TwigEnvironment::class)]
final class TwigEnvironmentTest extends TestCase
{
  #[Test]
  public function addContainerLoaderMethod(): void
  {
    $name = 'test-object';
    $testObject = new \stdClass();

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())->method('has')->with($name)->willReturn(true);
    $container->expects($this->once())->method('get')->with($name)->willReturn($testObject);

    $environment = self::makeCustomEnvironment();
    $environment->addContainerLoader($container);

    // @mago-expect analysis:possibly-invalid-argument
    self::assertSame(
      $testObject,
      $environment->getRuntime($name),
      'The object should be retrieved from the container.',
    );
  }

  #[Test]
  public function addRuntimeLoaders(): void
  {
    $loaders = [self::createStub(RuntimeLoaderInterface::class), self::createStub(RuntimeLoaderInterface::class)];

    $environment = self::makeCustomEnvironment();
    $environment->addRuntimeLoaders($loaders);

    $reflectionProperty = new \ReflectionProperty(Environment::class, 'runtimeLoaders');
    self::assertSame($loaders, $reflectionProperty->getValue($environment), 'The runtime loaders should be stored.');
  }

  #[Test]
  public function addBundles(): void
  {
    $createMockBundle =
      /**
       * @param list<AbstractExtension> $extensions
       */
      function (array $extensions = []): ExtensionBundleInterface&MockObject {
        $bundle = $this->createMock(ExtensionBundleInterface::class);
        $bundle->expects($this->once())->method('getExtensions')->willReturn($extensions);
        return $bundle;
      };

    // Test adding single bundle
    $initialExtensions = [new class extends AbstractExtension {}, new class extends AbstractExtension {}];
    $initialBundle = $createMockBundle($initialExtensions);

    $environment = self::makeCustomEnvironment();
    $environment->addBundle($initialBundle);
    self::assertContainsAll(
      $initialExtensions,
      $environment->getExtensions(),
      'The bundled extensions should be loaded.',
    );

    // Test adding multiple bundles
    $additionalExtensionSets = [
      'one' => [new class extends AbstractExtension {}, new class extends AbstractExtension {}],
      'two' => [new class extends AbstractExtension {}],
    ];
    $additionalBundles = [
      $createMockBundle($additionalExtensionSets['one']),
      $createMockBundle($additionalExtensionSets['two']),
    ];
    $environment->addBundles($additionalBundles);

    self::assertContainsAll(
      [...$additionalExtensionSets['one'], ...$additionalExtensionSets['two']],
      $environment->getExtensions(),
      'The bundled extensions should be loaded.',
    );
  }

  #[Test]
  public function addExtensions(): void
  {
    $environment = self::makeCustomEnvironment();

    $extensions = [new class extends AbstractExtension {}, new class extends AbstractExtension {}];

    $environment->addExtensions($extensions);

    self::assertContainsAll($extensions, $environment->getExtensions(), 'The extensions should be stored.');
  }

  #[Test]
  public function addTokenParsers(): void
  {
    $createTokenParser = function (string $tag): TokenParserInterface {
      $tokenParser = $this->createStub(TokenParserInterface::class);
      $tokenParser->method('getTag')->willReturn($tag);
      return $tokenParser;
    };
    $tokenParsers = [$createTokenParser('one'), $createTokenParser('two')];

    $environment = self::makeCustomEnvironment();
    $environment->addTokenParsers($tokenParsers);

    self::assertContainsAll($tokenParsers, $environment->getTokenParsers(), 'The token parsers should be stored.');
  }

  #[Test]
  public function addNodeVisitors(): void
  {
    $visitors = [self::createStub(NodeVisitorInterface::class), self::createStub(NodeVisitorInterface::class)];

    $environment = self::makeCustomEnvironment();
    $environment->addNodeVisitors($visitors);

    self::assertContainsAll($visitors, $environment->getNodeVisitors(), 'The node visitors should be stored.');
  }

  #[Test]
  public function addFilters(): void
  {
    $filters = [new TwigFilter('abc'), new TwigFilter('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addFilters($filters);

    self::assertContainsAll($filters, $environment->getFilters(), 'The filters should be stored.');
  }

  #[Test]
  public function addTests(): void
  {
    $tests = [new TwigTest('abc'), new TwigTest('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addTests($tests);

    self::assertContainsAll($tests, $environment->getTests(), 'The tests should be stored.');
  }

  #[Test]
  public function addFunctions(): void
  {
    $functions = [new TwigFunction('abc'), new TwigFunction('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addFunctions($functions);

    self::assertContainsAll($functions, $environment->getFunctions(), 'The functions should be stored.');
  }

  #[Test]
  public function addGlobals(): void
  {
    $globals = [
      'hello' => 'world',
      'test' => new \stdClass(),
    ];

    $environment = self::makeCustomEnvironment();
    $environment->addGlobals($globals);

    self::assertSame($globals, $environment->getGlobals(), 'The globals should be stored.');
  }

  #[Test]
  public function containerConstructorOption(): void
  {
    $testService = new \stdClass();
    $testServiceName = 'test-service';

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())->method('has')->with($testServiceName)->willReturn(true);
    $container->expects($this->once())->method('get')->with($testServiceName)->willReturn($testService);

    $environment = new TwigEnvironment(new ArrayLoader([]), ['container' => $container]);
    // @mago-expect analysis:possibly-invalid-argument
    $loadedService = $environment->getRuntime($testServiceName);
    self::assertSame($testService, $loadedService, 'The container service should be loaded.');
  }

  #[Test]
  public function addContainerLoaderMethodExcludesRuntimes(): void
  {
    $environment = self::makeCustomEnvironment();
    $environment->addContainerLoader(self::makeRuntimeContainerHavingEverything(), [\ArrayObject::class]);

    self::assertRuntimesExcluded($environment);
  }

  #[Test]
  public function containerExcludedRuntimesConstructorOption(): void
  {
    $environment = new TwigEnvironment(new ArrayLoader([]), [
      'container' => self::makeRuntimeContainerHavingEverything(),
      'container_excluded_runtimes' => [\ArrayObject::class],
    ]);

    self::assertRuntimesExcluded($environment);
  }

  #[Test]
  public function containerExcludedRuntimesConstructorOptionRequiresContainer(): void
  {
    self::assertThrows(
      static function (): void {
        new TwigEnvironment(new ArrayLoader([]), ['container_excluded_runtimes' => [\ArrayObject::class]]);
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof \InvalidArgumentException
        && $exception->getMessage() === 'The "container_excluded_runtimes" option requires the "container" option.'
      ),
      message: 'Excluding runtimes without a container should be rejected.',
    );
  }

  /**
   * Creates a container able to provide every runtime.
   */
  private static function makeRuntimeContainerHavingEverything(): ContainerInterface
  {
    $container = self::createStub(ContainerInterface::class);
    $container->method('has')->willReturn(true);
    $container
      ->method('get')
      ->willReturnCallback(static fn(string $id): object => match ($id) {
        \stdClass::class => new \stdClass(),
        default => throw new \LogicException(\sprintf('The container should not be asked for "%s".', $id)),
      });
    return $container;
  }

  private static function assertRuntimesExcluded(TwigEnvironment $environment): void
  {
    self::assertInstanceOf(
      \stdClass::class,
      $environment->getRuntime(\stdClass::class),
      'A runtime that is not excluded should be loaded from the container.',
    );
    self::assertThrows(
      static function () use ($environment): void {
        $environment->getRuntime(\ArrayObject::class);
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof RuntimeError
        && $exception->getMessage() === \sprintf('Unable to load the "%s" runtime.', \ArrayObject::class)
      ),
      message: 'An excluded runtime should not be loaded from the container.',
    );
  }

  private static function makeCustomEnvironment(): TwigEnvironment
  {
    return new TwigEnvironment(new ArrayLoader([]));
  }

  /**
   * @param list<object> $needles
   * @param array<array-key, object> $haystack
   */
  private static function assertContainsAll(array $needles, array $haystack, string $message = ''): void
  {
    foreach ($needles as $needle) {
      self::assertContains($needle, $haystack, $message);
    }
  }
}
