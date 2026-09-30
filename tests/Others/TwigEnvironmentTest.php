<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Bundles\ExtensionBundleInterface;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TwigEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Twig\Environment;
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
  public function testAddContainerLoader(): void
  {
    $name = 'test-object';
    $testObject = new \stdClass();

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())->method('has')->with($name)->willReturn(true);
    $container->expects($this->once())->method('get')->with($name)->willReturn($testObject);

    $environment = self::makeCustomEnvironment();
    $environment->addContainerLoader($container);

    self::assertSame(
      $testObject,
      $environment->getRuntime($name),
      'The object should be retrieved from the container.',
    );
  }

  public function testRuntimeLoaders(): void
  {
    $loaders = [self::createStub(RuntimeLoaderInterface::class), self::createStub(RuntimeLoaderInterface::class)];

    $environment = self::makeCustomEnvironment();
    $environment->addRuntimeLoaders($loaders);

    $reflectionProperty = new \ReflectionProperty(Environment::class, 'runtimeLoaders');
    self::assertSame($loaders, $reflectionProperty->getValue($environment), 'The runtime loaders should be stored.');
  }

  public function testBundles(): void
  {
    $createMockBundle = function (array $extensions = []): ExtensionBundleInterface&MockObject {
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

  public function testExtensions(): void
  {
    $environment = self::makeCustomEnvironment();

    $extensions = [new class extends AbstractExtension {}, new class extends AbstractExtension {}];

    $environment->addExtensions($extensions);

    self::assertContainsAll($extensions, $environment->getExtensions(), 'The extensions should be stored.');
  }

  public function testTokenParsers(): void
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

  public function testNodeVisitors(): void
  {
    $visitors = [self::createStub(NodeVisitorInterface::class), self::createStub(NodeVisitorInterface::class)];

    $environment = self::makeCustomEnvironment();
    $environment->addNodeVisitors($visitors);

    self::assertContainsAll($visitors, $environment->getNodeVisitors(), 'The node visitors should be stored.');
  }

  public function testFilters(): void
  {
    $filters = [new TwigFilter('abc'), new TwigFilter('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addFilters($filters);

    self::assertContainsAll($filters, $environment->getFilters(), 'The filters should be stored.');
  }

  public function testTests(): void
  {
    $tests = [new TwigTest('abc'), new TwigTest('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addTests($tests);

    self::assertContainsAll($tests, $environment->getTests(), 'The tests should be stored.');
  }

  public function testFunctions(): void
  {
    $functions = [new TwigFunction('abc'), new TwigFunction('def')];

    $environment = self::makeCustomEnvironment();
    $environment->addFunctions($functions);

    self::assertContainsAll($functions, $environment->getFunctions(), 'The functions should be stored.');
  }

  public function testGlobals(): void
  {
    $globals = [
      'hello' => 'world',
      'test' => new \stdClass(),
    ];

    $environment = self::makeCustomEnvironment();
    $environment->addGlobals($globals);

    self::assertSame($globals, $environment->getGlobals(), 'The globals should be stored.');
  }

  public function testAddsContainerLoader(): void
  {
    $testService = new \stdClass();
    $testServiceName = 'test-service';

    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())->method('has')->with($testServiceName)->willReturn(true);
    $container->expects($this->once())->method('get')->with($testServiceName)->willReturn($testService);

    $environment = new TwigEnvironment(new ArrayLoader([]), ['container' => $container]);
    $loadedService = $environment->getRuntime($testServiceName);
    self::assertSame($testService, $loadedService, 'The container service should be loaded.');
  }

  private static function makeCustomEnvironment(): TwigEnvironment
  {
    return new TwigEnvironment(new ArrayLoader([]));
  }

  private static function assertContainsAll(array $needles, array $haystack, string $message = ''): void
  {
    foreach ($needles as $needle) {
      self::assertContains($needle, $haystack, $message);
    }
  }
}
