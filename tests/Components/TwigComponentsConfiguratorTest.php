<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components;

use Averay\TwigExtensions\Components\Definitions\ComponentDefinition;
use Averay\TwigExtensions\Components\Definitions\ComponentRegistry;
use Averay\TwigExtensions\Components\Templates\ChainedTemplateFinder;
use Averay\TwigExtensions\Components\TwigComponentsConfigurator;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[CoversClass(TwigComponentsConfigurator::class)]
#[CoversClass(ChainedTemplateFinder::class)]
final class TwigComponentsConfiguratorTest extends TestCase
{
  private const string RENDERING_NAMESPACE = 'Averay\\TwigExtensions\\Tests\\Components\\Fixtures\\Rendering\\';

  private const array COMPONENT_TEMPLATES = [
    'components/Greeting.html.twig' => 'Hello {{ name }}.',
    'components/Panel.html.twig' => '<div{{ attributes }}>{{ variant }}: {{ label }}</div>',
    'components/Card.html.twig' => '<section>{% block content %}{% endblock %}</section>',
  ];

  #[Test]
  public function rendersComponentsWithFunction(): void
  {
    $environment = self::makeComponentsEnvironment(self::makeConfigurator(), [
      'template' => "{{ component('Greeting', { name: 'User' }) }}",
    ]);

    self::assertRenders('Hello User.', $environment, message: 'The component should be rendered.');
  }

  #[Test]
  public function rendersComponentsWithHtmlSyntax(): void
  {
    $environment = self::makeComponentsEnvironment(self::makeConfigurator(), [
      'template' => '<twig:Greeting name="User" />',
    ]);

    self::assertRenders('Hello User.', $environment, message: 'The component should be rendered.');
  }

  #[Test]
  public function mountsComponentsAndRendersAttributes(): void
  {
    $environment = self::makeComponentsEnvironment(self::makeConfigurator(), [
      'template' => '<twig:Panel variant="example" label="Label" id="test-id" />',
    ]);

    self::assertRenders(
      '<div id="test-id">EXAMPLE: Label</div>',
      $environment,
      message: 'The component should be mounted, with its attributes rendered without escaping.',
    );
  }

  #[Test]
  public function rendersEmbeddedAnonymousComponents(): void
  {
    $environment = self::makeComponentsEnvironment(self::makeConfigurator(), [
      'template' => '<twig:Card>Content</twig:Card>',
    ]);

    self::assertRenders(
      '<section>Content</section>',
      $environment,
      message: 'The anonymous component should be rendered.',
    );
  }

  /**
   * @param array<string, string> $templates
   */
  #[Test]
  #[DataProvider('anonymousTemplateDirectoryPrecedenceDataProvider')]
  public function anonymousTemplateDirectoryPrecedence(string $expected, array $templates): void
  {
    $configurator = self::makeConfigurator(anonymousTemplateDirectory: [
      '@views/components',
      '@framework/components',
    ]);
    $environment = self::makeComponentsEnvironment($configurator, ['template' => '<twig:Badge />', ...$templates]);

    self::assertRenders($expected, $environment, message: 'The template in the earliest directory should be used.');
  }

  /**
   * @return iterable<string, array{ string, array<string, string> }>
   */
  public static function anonymousTemplateDirectoryPrecedenceDataProvider(): iterable
  {
    yield 'Both directories' => [
      'First Directory',
      [
        '@views/components/Badge.html.twig' => 'First Directory',
        '@framework/components/Badge.html.twig' => 'Second Directory',
      ],
    ];
    yield 'Second directory only' => [
      'Second Directory',
      ['@framework/components/Badge.html.twig' => 'Second Directory'],
    ];
  }

  #[Test]
  public function retrievesComponentsFromContainer(): void
  {
    $container = $this->createMock(ContainerInterface::class);
    $container
      ->expects($this->exactly(2))
      ->method('get')
      ->with(Fixtures\Rendering\Greeting::class)
      ->willReturnCallback(static fn(): Fixtures\Rendering\Greeting => new Fixtures\Rendering\Greeting());

    $environment = self::makeComponentsEnvironment(self::makeConfigurator(componentContainer: $container), [
      'template' => '<twig:Greeting name="User" /> <twig:Greeting />',
    ]);

    self::assertRenders(
      'Hello User. Hello Default User.',
      $environment,
      message: 'A component instance should be retrieved from the container for every render.',
    );
  }

  #[Test]
  public function dispatchesEvents(): void
  {
    $eventDispatcher = new EventDispatcher();
    $eventDispatcher->addListener(PreRenderEvent::class, static function (PreRenderEvent $event): void {
      $event->setVariables(['name' => 'Event User'] + $event->getVariables());
    });

    $environment = self::makeComponentsEnvironment(self::makeConfigurator(eventDispatcher: $eventDispatcher), [
      'template' => '<twig:Greeting name="User" />',
    ]);

    self::assertRenders(
      'Hello Event User.',
      $environment,
      message: 'The event dispatcher should receive component events.',
    );
  }

  #[Test]
  public function reusesCachedComponents(): void
  {
    $cache = new ArrayAdapter();
    $configurator = self::makeConfigurator(cache: $cache);
    self::assertRenders(
      'Hello User.',
      self::makeComponentsEnvironment($configurator, ['template' => '<twig:Greeting name="User" />']),
      message: 'The component should be rendered.',
    );

    // Replaces the cached Greeting component's template to detect the cached components being used
    $modifiedGreetingDefinition = new ComponentDefinition(
      name: 'Greeting',
      className: Fixtures\Rendering\Greeting::class,
      template: 'cached/Greeting.html.twig',
      templateMethod: null,
      exposePublicProps: true,
      attributesVar: 'attributes',
      preMountMethods: [],
      mountMethods: [],
      postMountMethods: [],
    );
    $modifiedCacheItem = $cache->getItem(self::getCachedRegistryKey($cache))->set(new ComponentRegistry([
      'Greeting' => $modifiedGreetingDefinition,
    ]));
    $cache->save($modifiedCacheItem);

    $environment = self::makeComponentsEnvironment($configurator, [
      'template' => '<twig:Greeting name="User" />',
      'cached/Greeting.html.twig' => 'Cached: Hello {{ name }}.',
    ]);
    self::assertRenders(
      'Cached: Hello User.',
      $environment,
      message: 'The cached components should be used instead of discovering them again.',
    );
  }

  #[Test]
  public function rendersComponentsWhenOtherTemplatesAreMissing(): void
  {
    $environment = self::makeComponentsEnvironment(
      self::makeConfigurator(),
      [
        'template' => '<twig:Greeting name="User" />',
        'panel-template' => '<twig:Panel variant="example" />',
      ],
      componentTemplates: self::excludingKeys(self::COMPONENT_TEMPLATES, ['components/Panel.html.twig']),
    );

    self::assertRenders(
      'Hello User.',
      $environment,
      message: 'A component should be rendered when another component’s template is missing.',
    );
    self::assertThrows(
      static function () use ($environment): void {
        $environment->render('panel-template');
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof LoaderError
      && \str_contains($exception->getMessage(), '"components/Panel.html.twig"'),
      message: 'Rendering a component with a missing template should fail.',
    );
  }

  #[Test]
  public function warmsCache(): void
  {
    $cache = new ArrayAdapter();
    $configurator = self::makeConfigurator(cache: $cache);
    $configurator->warmCache(self::makeComponentsEnvironment($configurator, []));

    $registry = $cache->getItem(self::getCachedRegistryKey($cache))->get();
    self::assertInstanceOf(ComponentRegistry::class, $registry, 'The components should be cached.');
    self::assertSame(
      [Fixtures\Rendering\Greeting::class, Fixtures\Rendering\Panel::class],
      \array_values($registry->getClassNames()),
      'All discovered components should be cached.',
    );
    self::assertTrue(
      $cache->getItem('ux.twig_component.component_properties')->isHit(), // Symfony UX's property metadata cache key
      'The component property metadata should be cached.',
    );
  }

  #[Test]
  public function providesRuntimeClasses(): void
  {
    $environment = self::makeComponentsEnvironment(self::makeConfigurator(), []);

    foreach (TwigComponentsConfigurator::RUNTIME_CLASSES as $runtimeClass) {
      self::assertInstanceOf(
        $runtimeClass,
        $environment->getRuntime($runtimeClass),
        'Each listed runtime class should be provided by the configured environment.',
      );
    }
  }

  /**
   * @param string|list<string> $anonymousTemplateDirectory
   */
  private static function makeConfigurator(
    ?ContainerInterface $componentContainer = null,
    string|array $anonymousTemplateDirectory = 'components',
    ?AdapterInterface $cache = null,
    ?EventDispatcher $eventDispatcher = null,
  ): TwigComponentsConfigurator {
    return new TwigComponentsConfigurator(
      namespaces: [self::RENDERING_NAMESPACE => 'components'],
      componentContainer: $componentContainer ?? self::makeComponentContainer(),
      anonymousTemplateDirectory: $anonymousTemplateDirectory,
      cache: $cache,
      eventDispatcher: $eventDispatcher,
    );
  }

  private static function makeComponentContainer(): ContainerInterface
  {
    $container = self::createStub(ContainerInterface::class);
    $container
      ->method('get')
      ->willReturnCallback(static fn(string $id): object => match ($id) {
        Fixtures\Rendering\Panel::class => new Fixtures\Rendering\Panel(),
        Fixtures\Rendering\Greeting::class => new Fixtures\Rendering\Greeting(),
        default => throw new \OutOfBoundsException(\sprintf('Unknown component class "%s".', $id)),
      });
    return $container;
  }

  /**
   * @param array<string, string> $templates
   * @param array<string, string> $componentTemplates
   */
  private static function makeComponentsEnvironment(
    TwigComponentsConfigurator $configurator,
    array $templates,
    array $componentTemplates = self::COMPONENT_TEMPLATES,
  ): Environment {
    $environment = new Environment(new ArrayLoader([...$componentTemplates, ...$templates]), [
      'strict_variables' => true,
    ]);
    $configurator->configure($environment);
    return $environment;
  }

  private static function getCachedRegistryKey(ArrayAdapter $cache): string
  {
    foreach (\array_keys($cache->getValues()) as $key) {
      $key = (string) $key;
      if ($cache->getItem($key)->get() instanceof ComponentRegistry) {
        return $key;
      }
    }
    self::fail('No component registry was cached.');
  }

  /**
   * @template TKeyIncluded of array-key
   * @template TKeyExcluded of array-key
   * @template TValue
   *
   * @param array<TKeyIncluded|TKeyExcluded, TValue> $values
   * @param list<TKeyExcluded> $keys
   *
   * @return array<TKeyIncluded, TValue>
   */
  private static function excludingKeys(array $values, array $keys): array
  {
    return \array_diff_key($values, \array_flip($keys));
  }
}
