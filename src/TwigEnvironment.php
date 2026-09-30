<?php

declare(strict_types=1);

namespace Averay\TwigExtensions;

use Averay\TwigExtensions\Bundles\ExtensionBundleInterface;
use Averay\TwigExtensions\RuntimeLoaders\ExcludingRuntimeLoader;
use Psr\Container\ContainerInterface;
use Twig\Cache\CacheInterface;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\LoaderInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @api
 *
 * @psalm-type Options = array{
 *   autoescape?: 'html'|'name'|false|(callable(string $templateName):('html'|'name'|false)),
 *   auto_reload?: bool|null,
 *   cache?: string|CacheInterface|false,
 *   charset?: string,
 *   container?: ContainerInterface,
 *   container_excluded_runtimes?: list<class-string>,
 *   debug?: bool,
 *   optimizations?: -1|int-mask-of<OptimizerNodeVisitor::OPTIMIZE_*>,
 *   strict_variables?: bool,
 *   use_yield?: bool,
 * }
 * @psalm-type BaseOptions = array{
 *   autoescape?: 'html'|'name'|false|(callable(string $templateName):('html'|'name'|false)),
 *   auto_reload?: bool|null,
 *   cache?: string|CacheInterface|false,
 *   charset?: string,
 *   debug?: bool,
 *   optimizations?: -1|int-mask-of<OptimizerNodeVisitor::OPTIMIZE_*>,
 *   strict_variables?: bool,
 *   use_yield?: bool,
 * }
 * @psalm-type IntlPrototypes = array{
 *   dateFormatter?: \IntlDateFormatter,
 *   numberFormatter?: \NumberFormatter,
 * }
 */
class TwigEnvironment extends \Twig\Environment
{
  /**
   * @param Options $options
   */
  public function __construct(LoaderInterface $loader, array $options = [])
  {
    ['base' => $baseOptions, 'custom' => $customOptions] = self::splitOptions($options);

    parent::__construct($loader, ['strict_variables' => true, 'use_yield' => true, ...$baseOptions]);

    // Handle custom options
    ['container' => $container] = $customOptions;
    if ($container !== null) {
      $this->addContainerLoader($container, $customOptions['container_excluded_runtimes'] ?? []);
    }
  }

  /**
   * @param list<class-string> $excludedRuntimes Runtime class names the container should not provide even if capable.
   */
  public function addContainerLoader(ContainerInterface $container, array $excludedRuntimes = []): void
  {
    $loader = new ContainerRuntimeLoader($container);
    if ($excludedRuntimes !== []) {
      $loader = new ExcludingRuntimeLoader($loader, $excludedRuntimes);
    }
    $this->addRuntimeLoader($loader);
  }

  /**
   * @param iterable<RuntimeLoaderInterface> $loaders
   */
  public function addRuntimeLoaders(iterable $loaders): void
  {
    foreach ($loaders as $loader) {
      $this->addRuntimeLoader($loader);
    }
  }

  /**
   * @param iterable<ExtensionInterface> $extensions
   */
  public function addExtensions(iterable $extensions): void
  {
    foreach ($extensions as $extension) {
      $this->addExtension($extension);
    }
  }

  public function addBundle(ExtensionBundleInterface $bundle): void
  {
    $this->addExtensions($bundle->getExtensions());
  }

  /**
   * @param iterable<ExtensionBundleInterface> $bundles
   */
  public function addBundles(iterable $bundles): void
  {
    foreach ($bundles as $bundle) {
      $this->addBundle($bundle);
    }
  }

  /**
   * @param iterable<TokenParserInterface> $tokenParsers
   */
  public function addTokenParsers(iterable $tokenParsers): void
  {
    foreach ($tokenParsers as $tokenParser) {
      $this->addTokenParser($tokenParser);
    }
  }

  /**
   * @param iterable<NodeVisitorInterface> $visitors
   */
  public function addNodeVisitors(iterable $visitors): void
  {
    foreach ($visitors as $visitor) {
      $this->addNodeVisitor($visitor);
    }
  }

  /**
   * @param iterable<TwigFilter> $filters
   */
  public function addFilters(iterable $filters): void
  {
    foreach ($filters as $filter) {
      $this->addFilter($filter);
    }
  }

  /**
   * @param iterable<TwigTest> $tests
   */
  public function addTests(iterable $tests): void
  {
    foreach ($tests as $test) {
      $this->addTest($test);
    }
  }

  /**
   * @param iterable<TwigFunction> $functions
   */
  public function addFunctions(iterable $functions): void
  {
    foreach ($functions as $function) {
      $this->addFunction($function);
    }
  }

  /**
   * @param iterable<string, mixed> $globals
   */
  public function addGlobals(iterable $globals): void
  {
    /** @psalm-suppress MixedAssignment */
    foreach ($globals as $name => $value) {
      $this->addGlobal($name, $value);
    }
  }

  /**
   * @param Options $options
   *
   * @return array{
   *   base: BaseOptions,
   *   custom: array{
   *     container: ContainerInterface|null,
   *     container_excluded_runtimes: list<class-string>|null,
   *   },
   * }
   */
  private static function splitOptions(array $options): array
  {
    /** @var BaseOptions $baseOptions */
    $baseOptions = \array_diff_key($options, \array_flip(['container', 'container_excluded_runtimes']));
    $customOptions = [
      'container' => $options['container'] ?? null,
      'container_excluded_runtimes' => $options['container_excluded_runtimes'] ?? null,
    ];

    if ($customOptions['container_excluded_runtimes'] !== null && $customOptions['container'] === null) {
      throw new \InvalidArgumentException('The "container_excluded_runtimes" option requires the "container" option.');
    }

    return [
      'base' => $baseOptions,
      'custom' => $customOptions,
    ];
  }
}
