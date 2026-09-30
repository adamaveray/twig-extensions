<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components;

use Averay\TwigExtensions\Components\Definitions\ComponentRegistry;
use Averay\TwigExtensions\Components\Discovery\ComponentDiscoverer;
use Averay\TwigExtensions\Components\Discovery\NamespaceFinder;
use Averay\TwigExtensions\Components\Locators\ComponentLocator;
use Averay\TwigExtensions\Components\Locators\NullRendererLocator;
use Averay\TwigExtensions\Components\Templates\ChainedTemplateFinder;
use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentProperties;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\ComponentTemplateFinder;
use Symfony\UX\TwigComponent\Twig\ComponentExtension;
use Symfony\UX\TwigComponent\Twig\ComponentLexer;
use Symfony\UX\TwigComponent\Twig\ComponentRuntime;
use Twig\Environment;
use Twig\Loader\LoaderInterface;
use Twig\Runtime\EscaperRuntime;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Configures Twig environments to render Symfony UX Twig Components without the Symfony framework.
 *
 * @api
 *
 * @psalm-type TemplateDirectoriesList = list<string>
 * @psalm-type NamespacesMap = array<string, TemplateDirectoriesList>
 */
final readonly class TwigComponentsConfigurator
{
  private const string REGISTRY_CACHE_KEY_PREFIX = 'twig_components.registry.';

  /** Must be incremented whenever the structure of the cached component registry changes. */
  private const int REGISTRY_CACHE_VERSION = 1;

  /** @var NamespacesMap */
  private array $namespaces;
  /** @var TemplateDirectoriesList */
  private array $anonymousTemplateDirectories;

  private string $registryCacheKey;

  private AdapterInterface $cache;
  private EventDispatcherInterface $eventDispatcher;
  private PropertyAccessorInterface $propertyAccessor;

  /**
   * @param array<string, string|TemplateDirectoriesList> $namespaces PHP namespaces to discover component classes within, mapped to the template directories to find their components’ templates within, in order of precedence (e.g. `['App\\Components\\' => '@views/components']`).
   * @param ContainerInterface $componentContainer Provides component instances by class name (must return a new instance for every retrieval).
   * @param string|TemplateDirectoriesList $anonymousTemplateDirectory Template directories to find anonymous (template-only, no corresponding PHP class) components within, in order of precedence.
   * @param AdapterInterface|null $cache Stores the discovered components and their property metadata, which must be cleared or re-warmed when components change.
   * @param EventDispatcherInterface|null $eventDispatcher Receives the Symfony UX component lifecycle events.
   */
  public function __construct(
    array $namespaces,
    private ContainerInterface $componentContainer,
    string|array $anonymousTemplateDirectory = 'components',
    ?AdapterInterface $cache = null,
    ?EventDispatcherInterface $eventDispatcher = null,
    ?PropertyAccessorInterface $propertyAccessor = null,
  ) {
    $this->namespaces = \array_map(self::normaliseTemplateDirectoryList(...), $namespaces);
    $this->anonymousTemplateDirectories = self::normaliseTemplateDirectoryList($anonymousTemplateDirectory);
    $this->registryCacheKey = self::REGISTRY_CACHE_KEY_PREFIX . self::generateCacheKey($this->namespaces);
    $this->cache = $cache ?? new ArrayAdapter();
    $this->eventDispatcher = $eventDispatcher ?? new EventDispatcher();
    $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
  }

  /**
   * Enables rendering components within the environment, including the `<twig:Component />` HTML syntax.
   */
  public function configure(Environment $twig): void
  {
    $twig->setLexer(new ComponentLexer($twig));
    $twig->getRuntime(EscaperRuntime::class)->addSafeClass(ComponentAttributes::class, ['html']);
    $twig->addExtension(new ComponentExtension());
    $twig->addRuntimeLoader(new FactoryRuntimeLoader([
      ComponentRuntime::class => fn(): ComponentRuntime => $this->createRuntime($twig),
    ]));
  }

  /**
   * Discovers the components and their property metadata, and stores them in the cache.
   */
  public function warmCache(Environment $twig): void
  {
    $registry = $this->discoverRegistry($twig->getLoader());
    $this->cache->save($this->cache->getItem($this->registryCacheKey)->set($registry));

    // Unset metadata entries are loaded and stored during warming
    $classNames = \array_values($registry->getClassNames());
    new ComponentProperties($this->propertyAccessor, \array_fill_keys($classNames, null), $this->cache)->warmup();
  }

  private function createRuntime(Environment $twig): ComponentRuntime
  {
    $loader = $twig->getLoader();

    $stack = new ComponentStack();

    $registry = $this->loadRegistry($loader);

    $templateFinder = new ChainedTemplateFinder(\array_map(
      static fn(string $directory): ComponentTemplateFinder => new ComponentTemplateFinder($loader, $directory),
      $this->anonymousTemplateDirectories,
    ));

    $factory = new ComponentFactory(
      $templateFinder,
      new ComponentLocator($this->componentContainer, $registry->getClassNames()),
      $this->propertyAccessor,
      $this->eventDispatcher,
      $registry->getConfig(),
      $registry->getClassMap(),
      $twig,
    );

    $renderer = new ComponentRenderer(
      $twig,
      $this->eventDispatcher,
      $factory,
      new ComponentProperties($this->propertyAccessor, cache: $this->cache),
      $stack,
    );

    return new ComponentRuntime($renderer, new NullRendererLocator(), $stack);
  }

  private function loadRegistry(LoaderInterface $loader): ComponentRegistry
  {
    $cacheItem = $this->cache->getItem($this->registryCacheKey);
    $cachedRegistry = $cacheItem->isHit() ? $cacheItem->get() : null;
    if ($cachedRegistry instanceof ComponentRegistry) {
      return $cachedRegistry;
    }

    $registry = $this->discoverRegistry($loader);
    $this->cache->save($cacheItem->set($registry));
    return $registry;
  }

  private function discoverRegistry(LoaderInterface $loader): ComponentRegistry
  {
    return new ComponentDiscoverer(
      $loader,
      new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders())),
    )->discover($this->namespaces);
  }

  /**
   * @param string|TemplateDirectoriesList $value
   *
   * @return TemplateDirectoriesList
   */
  private static function normaliseTemplateDirectoryList(string|array $value): array
  {
    return \is_string($value) ? [$value] : $value;
  }

  /**
   * @param array<string, mixed> $namespaces
   */
  private static function generateCacheKey(array $namespaces): string
  {
    return 'v' . self::REGISTRY_CACHE_VERSION . '.' . \hash('xxh128', \serialize($namespaces));
  }
}
