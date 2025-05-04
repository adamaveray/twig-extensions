<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Loaders;

use Twig\Loader\FilesystemLoader;

class NamespacedFilesystemLoader extends FilesystemLoader
{
  /**
   * @param array<string, list<string>|string> $namespacedPaths Keys represent namespaces (which can be loaded via `@{namespace}/...` paths), and values are one or more file directories to load templates within.
   */
  public function __construct(array $namespacedPaths)
  {
    parent::__construct([], '/dev/null');
    foreach ($namespacedPaths as $namespace => $paths) {
      $this->setPaths($paths, $namespace);
    }
  }

  /**
   * @return list<string>
   */
  #[\Override]
  public function getPaths(string $namespace = self::MAIN_NAMESPACE): array
  {
    if ($namespace === self::MAIN_NAMESPACE) {
      return [];
    }
    return parent::getPaths($namespace);
  }

  /**
   * @return list<string>
   */
  #[\Override]
  public function getNamespaces(): array
  {
    $paths = $this->paths;
    unset($paths[self::MAIN_NAMESPACE]);
    return \array_keys($paths);
  }

  /**
   * @param string|list<string> $paths
   * @param string $namespace
   * @psalm-suppress MoreSpecificImplementedParamType Library uses ambiguous array syntax but expects a list.
   */
  #[\Override]
  public function setPaths(mixed $paths, string $namespace = self::MAIN_NAMESPACE): void
  {
    if ($namespace === self::MAIN_NAMESPACE) {
      throw new \InvalidArgumentException('Cannot set paths in the main namespace.');
    }
    parent::setPaths($paths, $namespace);
  }

  #[\Override]
  public function addPath(string $path, string $namespace = self::MAIN_NAMESPACE): void
  {
    if ($namespace === self::MAIN_NAMESPACE) {
      throw new \InvalidArgumentException('Cannot add paths in the main namespace.');
    }
    parent::addPath($path, $namespace);
  }

  #[\Override]
  public function prependPath(string $path, string $namespace = self::MAIN_NAMESPACE): void
  {
    if ($namespace === self::MAIN_NAMESPACE) {
      throw new \InvalidArgumentException('Cannot prepend paths in the main namespace.');
    }
    parent::prependPath($path, $namespace);
  }
}
