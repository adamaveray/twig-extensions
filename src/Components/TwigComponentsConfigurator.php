<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components;

use Averay\TwigExtensions\Components\Discovery\ComponentDiscoverer;
use Averay\TwigExtensions\Components\Discovery\NamespaceFinder;
use Averay\TwigExtensions\Components\Locators\ComponentLocator;
use Averay\TwigExtensions\Components\Locators\NullRendererLocator;
use Averay\TwigExtensions\Components\Templates\ChainedTemplateFinder;
use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface;
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
  /** @var NamespacesMap */
  private array $namespaces;
  /** @var TemplateDirectoriesList */
  private array $anonymousTemplateDirectories;

  private EventDispatcherInterface $eventDispatcher;
  private PropertyAccessorInterface $propertyAccessor;

  /**
   * @param array<string, string|TemplateDirectoriesList> $namespaces PHP namespaces to discover component classes within, mapped to the template directories to find their components’ templates within, in order of precedence (e.g. `['App\\Components\\' => '@views/components']`).
   * @param ContainerInterface $componentContainer Provides component instances by class name (must return a new instance for every retrieval).
   * @param string|TemplateDirectoriesList $anonymousTemplateDirectory Template directories to find anonymous (template-only, no corresponding PHP class) components within, in order of precedence.
   * @param EventDispatcherInterface|null $eventDispatcher Receives the Symfony UX component lifecycle events.
   */
  public function __construct(
    array $namespaces,
    private ContainerInterface $componentContainer,
    string|array $anonymousTemplateDirectory = 'components',
    ?EventDispatcherInterface $eventDispatcher = null,
    ?PropertyAccessorInterface $propertyAccessor = null,
  ) {
    $this->namespaces = \array_map(self::normaliseTemplateDirectoryList(...), $namespaces);
    $this->anonymousTemplateDirectories = self::normaliseTemplateDirectoryList($anonymousTemplateDirectory);
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

  private function createRuntime(Environment $twig): ComponentRuntime
  {
    $loader = $twig->getLoader();

    $stack = new ComponentStack();

    $registry = new ComponentDiscoverer(
      $loader,
      new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders())),
    )->discover($this->namespaces);

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
      new ComponentProperties($this->propertyAccessor),
      $stack,
    );

    return new ComponentRuntime($renderer, new NullRendererLocator(), $stack);
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
}
