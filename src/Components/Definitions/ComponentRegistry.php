<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Definitions;

/**
 * The class-based Twig components available for rendering.
 *
 * @internal
 *
 * @psalm-import-type ComponentInternalConfig from ComponentDefinition
 */
final readonly class ComponentRegistry
{
  /**
   * @param array<string, ComponentDefinition> $components Component definitions keyed by component name.
   */
  public function __construct(
    private array $components,
  ) {}

  /**
   * @return array<string, ComponentInternalConfig> Symfony UX component configurations keyed by component name.
   */
  public function getConfig(): array
  {
    return $this->map(
      /** @return ComponentInternalConfig */
      static fn(ComponentDefinition $component): array => $component->toSymfonyUxConfig(),
    );
  }

  /**
   * @return array<string, class-string> Component class names keyed by component name.
   */
  public function getClassNames(): array
  {
    return $this->map(static fn(ComponentDefinition $component): string => $component->className);
  }

  /**
   * @return array<class-string, string> Component names keyed by class name.
   */
  public function getClassMap(): array
  {
    /** @var array<class-string, string> $classMap */
    $classMap = [];
    foreach ($this->components as $component) {
      $classMap[$component->className] = $component->name;
    }
    return $classMap;
  }

  /**
   * @return array<string, ComponentDefinition>
   */
  public function getComponents(): array
  {
    return $this->components;
  }

  public function getComponent(string $component): ?ComponentDefinition
  {
    return $this->components[$component] ?? null;
  }

  /**
   * @template T
   *
   * @param callable(ComponentDefinition):T $fn
   *
   * @return array<string, T>
   */
  private function map(callable $fn): array
  {
    return \array_map($fn, $this->components);
  }
}
