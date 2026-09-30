<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Discovery;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Finder\Finder;

/**
 * Finds the loadable classes within PSR-4 autoloaded namespaces.
 *
 * @internal
 */
final readonly class NamespaceFinder
{
  /**
   * @param list<ClassLoader> $classLoaders Composer class loaders to resolve namespace directories from.
   */
  public function __construct(
    private array $classLoaders,
  ) {}

  /**
   * @param string $namespace A PHP namespace ending in a backslash.
   *
   * @return iterable<class-string>
   */
  public function findForNamespace(string $namespace): iterable
  {
    $directories = $this->getDirectoriesForNamespace($namespace);
    if ($directories === []) {
      return;
    }

    $files = Finder::create()->files()->in($directories)->name('*.php')->sortByName();
    foreach ($files as $file) {
      // Infer class name
      $relativePathSuffix = \substr($file->getRelativePathname(), 0, -\strlen('.php'));
      $className = $namespace . self::getNamespaceForPath($relativePathSuffix);
      if (!\class_exists($className)) {
        // Class not loadable
        continue;
      }

      yield $className;
    }
  }

  /**
   * @return list<string>
   */
  private function getDirectoriesForNamespace(string $namespace): array
  {
    // Determine candidate directories
    /** @var string|null $matchedPrefix */
    $matchedPrefix = null;
    /** @var list<list<string>> $prefixDirectorySets */
    $prefixDirectorySets = [];
    foreach ($this->iterateMatchingPrefixes($namespace) as $prefix => $directories) {
      if (self::isMoreSpecificPrefix($prefix, $matchedPrefix)) {
        // New more-specific match - replace
        $matchedPrefix = $prefix;
        $prefixDirectorySets = [$directories];
      } elseif ($prefix === $matchedPrefix) {
        // Same match - append
        $prefixDirectorySets[] = $directories;
      }
    }
    if ($matchedPrefix === null) {
      throw new \LogicException(\sprintf('Component namespace "%s" is not autoloaded using PSR-4.', $namespace));
    }

    return self::resolveDirectorySuffixes(
      prefixes: \array_values(\array_merge([], ...$prefixDirectorySets)),
      suffix: self::getPathForNamespace(\substr($namespace, \strlen($matchedPrefix))),
    );
  }

  /**
   * @return iterable<string, list<string>> PSR-4 prefixes containing the namespace, mapped to their directories.
   */
  private function iterateMatchingPrefixes(string $namespace): iterable
  {
    foreach ($this->classLoaders as $classLoader) {
      foreach ($classLoader->getPrefixesPsr4() as $prefix => $directories) {
        if (\str_starts_with($namespace, $prefix)) {
          yield $prefix => $directories;
        }
      }
    }
  }

  /**
   * @param list<string> $prefixes
   *
   * @return list<string>
   */
  private static function resolveDirectorySuffixes(array $prefixes, string $suffix): array
  {
    /** @var list<string> $directories */
    $directories = [];
    foreach ($prefixes as $prefix) {
      $path = \realpath(\rtrim($prefix, '/') . '/' . $suffix);
      if ($path !== false && \is_dir($path)) {
        $directories[] = $path;
      }
    }
    return \array_values(\array_unique($directories));
  }

  private static function getPathForNamespace(string $namespace): string
  {
    return \str_replace('\\', '/', $namespace);
  }

  private static function getNamespaceForPath(string $path): string
  {
    return \str_replace('/', '\\', $path);
  }

  /**
   * @return bool Whether $test is a more specific prefix than $comparison
   *
   * @note This method exists to work around a Mago bug where it narrows $comparison from `string|null` to `nonnull` instead of to `string`.
   *
   * @psalm-assert-if-true string $matchedPrefix
   */
  private static function isMoreSpecificPrefix(string $test, ?string $comparison): bool
  {
    if ($comparison === null) {
      return true;
    }

    return \strlen($test) > \strlen($comparison);
  }
}
