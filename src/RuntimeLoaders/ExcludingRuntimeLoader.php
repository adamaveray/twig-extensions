<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\RuntimeLoaders;

use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Prevents a runtime loader from loading the excluded runtimes, allowing other runtime loaders to load them instead.
 *
 * @api
 */
final readonly class ExcludingRuntimeLoader implements RuntimeLoaderInterface
{
  /** @var array<string, true> Excluded runtime class names (as keys for fast lookup). */
  private array $excludedClasses;

  /**
   * @param RuntimeLoaderInterface $loader The runtime loader to load non-excluded runtimes from.
   * @param list<class-string> $excludedClasses Runtime class names to never load.
   */
  public function __construct(
    private RuntimeLoaderInterface $loader,
    array $excludedClasses,
  ) {
    $this->excludedClasses = \array_fill_keys($excludedClasses, true);
  }

  #[\Override]
  public function load(string $class): ?object
  {
    if (isset($this->excludedClasses[$class])) {
      // Excluded
      return null;
    }

    return $this->loader->load($class);
  }
}
