<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Discovery;

use Averay\TwigExtensions\Components\Discovery\NamespaceFinder;
use Averay\TwigExtensions\Tests\Components\Fixtures;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversClass(NamespaceFinder::class)]
final class NamespaceFinderTest extends TestCase
{
  private const string TESTS_PREFIX = 'Averay\\TwigExtensions\\Tests\\';
  private const string BASIC_NAMESPACE = 'Averay\\TwigExtensions\\Tests\\Components\\Fixtures\\Basic\\';
  private const string TESTS_DIRECTORY = __DIR__ . '/../..';

  /** @var list<class-string> */
  private const array BASIC_CLASSES = [
    Fixtures\Basic\AbstractComponent::class,
    Fixtures\Basic\Button::class,
    Fixtures\Basic\ExplicitTemplate::class,
    Fixtures\Basic\Media\Gallery::class,
    Fixtures\Basic\MethodTemplate::class,
    Fixtures\Basic\Named::class,
    Fixtures\Basic\NotAComponent::class,
  ];

  #[Test]
  public function findsLoadableClassesWithinNamespace(): void
  {
    $finder = new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders()));

    self::assertSame(
      self::BASIC_CLASSES,
      self::findSorted($finder, self::BASIC_NAMESPACE),
      'All loadable classes within the namespace and its sub-namespaces should be found.',
    );
  }

  #[Test]
  public function usesMostSpecificPrefix(): void
  {
    $classLoader = new ClassLoader();
    $classLoader->addPsr4(self::TESTS_PREFIX, self::TESTS_DIRECTORY);
    $classLoader->addPsr4(self::BASIC_NAMESPACE, self::TESTS_DIRECTORY . '/non-existent');
    $finder = new NamespaceFinder([$classLoader]);

    self::assertSame(
      [],
      self::findSorted($finder, self::BASIC_NAMESPACE),
      'Only the directories of the most specific matching prefix should be searched.',
    );
  }

  #[Test]
  public function combinesDirectoriesForSamePrefix(): void
  {
    $emptyClassLoader = new ClassLoader();
    $emptyClassLoader->addPsr4(self::TESTS_PREFIX, self::TESTS_DIRECTORY . '/non-existent');
    $classLoader = new ClassLoader();
    $classLoader->addPsr4(self::TESTS_PREFIX, self::TESTS_DIRECTORY);
    $finder = new NamespaceFinder([$emptyClassLoader, $classLoader]);

    self::assertSame(
      self::BASIC_CLASSES,
      self::findSorted($finder, self::BASIC_NAMESPACE),
      'Directories for the same prefix across class loaders should all be searched.',
    );
  }

  #[Test]
  public function returnsNothingForMissingDirectory(): void
  {
    $finder = new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders()));

    self::assertSame(
      [],
      self::findSorted($finder, self::TESTS_PREFIX . 'NonExistent\\'),
      'A namespace without a directory should contain no classes.',
    );
  }

  #[Test]
  public function throwsForNonAutoloadedNamespace(): void
  {
    $finder = new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders()));

    self::assertThrows(
      static function () use ($finder): void {
        self::findSorted($finder, 'NotAutoloaded\\');
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof \LogicException
        && $exception->getMessage() === 'Component namespace "NotAutoloaded\\" is not autoloaded using PSR-4.'
      ),
      message: 'A namespace not autoloaded using PSR-4 should be rejected.',
    );
  }

  /**
   * @return list<class-string>
   */
  private static function findSorted(NamespaceFinder $finder, string $namespace): array
  {
    $classNames = \iterator_to_array($finder->findForNamespace($namespace), preserve_keys: false);
    \sort($classNames);
    return $classNames;
  }
}
