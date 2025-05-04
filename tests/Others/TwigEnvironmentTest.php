<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Bundles\AbstractBundle;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\TwigEnvironment;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Loader\ArrayLoader;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

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

  public function testExtensions(): void
  {
    $environment = self::makeCustomEnvironment();

    $builder = $this->getMockBuilder(ExtensionInterface::class);
    $extensions = [
      $builder->setMockClassName('MockExtensionOne')->getMock(),
      $builder->setMockClassName('MockExtensionTwo')->getMock(),
    ];

    $environment->addExtensions($extensions);

    self::assertContainsAll($extensions, $environment->getExtensions(), 'The extensions should be stored.');
  }

  public function testTokenParsers(): void
  {
    $builder = $this->getMockBuilder(TokenParserInterface::class);
    $tokenParsers = [
      $builder->setMockClassName('MockTokenParserOne')->getMock(),
      $builder->setMockClassName('MockTokenParserTwo')->getMock(),
    ];
    $tokenParsers[0]->method('getTag')->willReturn('one');
    $tokenParsers[1]->method('getTag')->willReturn('two');

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

  public function testBundledExtensions(): void
  {
    $makeMockTokenParser = function (string $tag): TokenParserInterface {
      $mock = $this->createMock(TokenParserInterface::class);
      $mock->expects($this->once())->method('getTag')->willReturn($tag);
      return $mock;
    };
    $valueSets = [
      'tokenParsers' => [$makeMockTokenParser('tag-one'), $makeMockTokenParser('tag-two')],
      'nodeVisitors' => [
        $this->createStub(NodeVisitorInterface::class),
        $this->createStub(NodeVisitorInterface::class),
      ],
      'filters' => [new TwigFilter('filter-one'), new TwigFilter('filter-two')],
      'tests' => [new TwigTest('test-one'), new TwigTest('test-two')],
      'functions' => [new TwigFunction('function-one'), new TwigFunction('function-two')],
      'operators' => [
        [
          'test-unary-one' => [
            'precedence' => 1,
            'class' => 'test-one',
          ],
          'test-unary-two' => [
            'precedence' => 1,
            'class' => 'test-two',
          ],
        ],
        [
          'test-binary-one' => [
            'precedence' => 2,
            'class' => 'test-three',
            'associativity' => 1,
          ],
          'test-binary-two' => [
            'precedence' => 3,
            'class' => 'test-four',
            'associativity' => 1,
          ],
        ],
      ],
      'globals' => [
        'global-one' => 'value-one',
        'global-two' => 'value-two',
      ],
    ];

    $extension = $this->createMockForIntersectionOfInterfaces([ExtensionInterface::class, GlobalsInterface::class]);

    foreach ($valueSets as $valueType => $values) {
      $method = 'get' . \ucfirst($valueType);
      $extension->expects($this->once())->method($method)->willReturn($values);
    }

    $environment = self::makeCustomEnvironment();
    $environment->addExtension($extension);

    foreach ($valueSets as $valueType => $values) {
      if ($valueType === 'operators') {
        continue;
      }

      $method = 'get' . \ucfirst($valueType);
      self::assertContainsAll(
        $values,
        $environment->{$method}(),
        \sprintf('The bundled extension %s values should be added.', $valueType),
      );
    }

    $expectedUnaryOperatorNames = \array_keys($valueSets['operators'][0]);
    $expectedBinaryOperatorNames = \array_keys($valueSets['operators'][1]);
    $expressionParsers = \iterator_to_array($environment->getExpressionParsers()->getIterator());
    self::assertContainsAll(
      \array_merge($expectedUnaryOperatorNames, $expectedBinaryOperatorNames),
      \array_keys($expressionParsers),
      'The bundled extension operators should be processed.',
    );
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
