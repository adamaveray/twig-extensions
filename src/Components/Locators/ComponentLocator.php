<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Locators;

use Averay\TwigExtensions\Components\Locators\Exceptions\ComponentNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Retrieves component instances by component name from a container using their class names.
 *
 * @internal
 */
final readonly class ComponentLocator implements ContainerInterface
{
  /**
   * @param ContainerInterface $container A container returning a new instance of the requested component class for every retrieval.
   * @param array<string, class-string> $classNames Component class names keyed by component name.
   */
  public function __construct(
    private ContainerInterface $container,
    private array $classNames,
  ) {}

  #[\Override]
  public function get(string $id): object
  {
    $className = $this->classNames[$id] ?? throw new ComponentNotFoundException(component: $id);
    /** @var object */
    return $this->container->get($className);
  }

  #[\Override]
  public function has(string $id): bool
  {
    return isset($this->classNames[$id]);
  }
}
