<?php
declare(strict_types=1);

namespace Averay\TwigExtensions;

use Averay\TwigExtensions\Bundles\AbstractBundle;
use Psr\Container\ContainerInterface;
use Twig\Cache\CacheInterface;
use Twig\Extension\ExtensionInterface;
use Twig\Extra as TwigExtra;
use Twig\Loader\LoaderInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\RuntimeLoader\ContainerRuntimeLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * @psalm-type IntlPrototypes = array{
 *   dateFormatter?: \IntlDateFormatter,
 *   numberFormatter?: \NumberFormatter,
 * }
 */
class TwigEnvironment extends \Twig\Environment
{
  /**
   * @param array{
   *   autoescape?: 'html'|'name'|false|(callable(string $templateName):('html'|'name'|false)),
   *   auto_reload?: bool|null,
   *   cache?: string|CacheInterface|false,
   *   charset?: string,
   *   container?: ContainerInterface,
   *   debug?: bool,
   *   optimizations?: -1|int-mask-of<OptimizerNodeVisitor::OPTIMIZE_*>,
   *   strict_variables?: bool,
   *   use_yield?: bool,
   * } $options
   */
  public function __construct(LoaderInterface $loader, array $options = [])
  {
    $options = \array_merge(
      [
        'container' => null,
        'strict_variables' => true,
        'use_yield' => true,
      ],
      $options,
    );

    $container = $options['container'];
    unset($options['container']);

    parent::__construct($loader, $options);

    if ($container !== null) {
      $this->addContainerLoader($container);
    }
  }

  public function addContainerLoader(ContainerInterface $container): void
  {
    $this->addRuntimeLoader(new ContainerRuntimeLoader($container));
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

  #[\Override]
  public function addExtension(ExtensionInterface $extension): void
  {
    if ($extension instanceof AbstractBundle) {
      // Unwrap extension set
      foreach ($extension->getExtensions() as $innerExtension) {
        $this->addExtension($innerExtension);
      }
      return;
    }

    parent::addExtension($extension);
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
}
