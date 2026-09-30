<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Locators;

use Averay\TwigExtensions\Components\Locators\Exceptions\ComponentNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * A locator that never locates anything.
 *
 * @internal
 */
final readonly class NullRendererLocator implements ContainerInterface
{
  #[\Override]
  public function get(string $id): never
  {
    throw new ComponentNotFoundException(component: $id, message: \sprintf(
      'No custom renderer is available for component "%s".',
      $id,
    ));
  }

  #[\Override]
  public function has(string $id): bool
  {
    return false;
  }
}
