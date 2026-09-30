<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Resources;

use Twig\RuntimeLoader\RuntimeLoaderInterface;

final readonly class ArrayRuntimeLoader implements RuntimeLoaderInterface
{
  /**
   * @param array<string, object> $resources
   */
  public function __construct(
    private array $resources,
  ) {}

  #[\Override]
  public function load(string $class): ?object
  {
    return $this->resources[$class] ?? null;
  }
}
