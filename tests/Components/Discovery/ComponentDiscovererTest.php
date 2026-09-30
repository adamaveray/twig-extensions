<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Discovery;

use Averay\TwigExtensions\Components\Definitions\ComponentDefinition;
use Averay\TwigExtensions\Components\Definitions\ComponentRegistry;
use Averay\TwigExtensions\Components\Discovery\ComponentDiscoverer;
use Averay\TwigExtensions\Components\Discovery\Exceptions\TemplateNotFoundException;
use Averay\TwigExtensions\Components\Discovery\NamespaceFinder;
use Averay\TwigExtensions\Tests\Components\Fixtures;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
#[CoversClass(ComponentDiscoverer::class)]
#[CoversClass(TemplateNotFoundException::class)]
final class ComponentDiscovererTest extends TestCase
{
  private const string FIXTURES_NAMESPACE = 'Averay\\TwigExtensions\\Tests\\Components\\Fixtures\\';

  #[Test]
  public function discoversComponents(): void
  {
    $registry = self::discover([self::FIXTURES_NAMESPACE . 'Basic\\' => '@views/components'], [
      '@views/components/Button.html.twig',
      '@views/components/Media/Gallery.html.twig',
      '@views/components/Custom/Name.html.twig',
    ]);

    self::assertEquals(
      [
        'Button' => new ComponentDefinition(
          name: 'Button',
          className: Fixtures\Basic\Button::class,
          template: '@views/components/Button.html.twig',
          templateMethod: null,
          exposePublicProps: true,
          attributesVar: 'attributes',
          preMountMethods: [],
          mountMethods: [],
          postMountMethods: [],
        ),
        'Custom:Name' => new ComponentDefinition(
          name: 'Custom:Name',
          className: Fixtures\Basic\Named::class,
          template: '@views/components/Custom/Name.html.twig',
          templateMethod: null,
          exposePublicProps: true,
          attributesVar: 'attributes',
          preMountMethods: [],
          mountMethods: [],
          postMountMethods: [],
        ),
        'ExplicitTemplate' => new ComponentDefinition(
          name: 'ExplicitTemplate',
          className: Fixtures\Basic\ExplicitTemplate::class,
          template: 'custom/explicit.html.twig',
          templateMethod: null,
          exposePublicProps: true,
          attributesVar: 'attributes',
          preMountMethods: [],
          mountMethods: [],
          postMountMethods: [],
        ),
        'Media:Gallery' => new ComponentDefinition(
          name: 'Media:Gallery',
          className: Fixtures\Basic\Media\Gallery::class,
          template: '@views/components/Media/Gallery.html.twig',
          templateMethod: null,
          exposePublicProps: false,
          attributesVar: 'attrs',
          preMountMethods: ['preMountHigh', 'preMountLow'],
          mountMethods: ['mount'],
          postMountMethods: ['postMount'],
        ),
        'MethodTemplate' => new ComponentDefinition(
          name: 'MethodTemplate',
          className: Fixtures\Basic\MethodTemplate::class,
          template: null,
          templateMethod: 'getTemplate',
          exposePublicProps: true,
          attributesVar: 'attributes',
          preMountMethods: [],
          mountMethods: [],
          postMountMethods: [],
        ),
      ],
      self::sortByKey($registry->getComponents()),
      'Instantiable classes with the component attribute should be discovered.',
    );
  }

  /**
   * @param list<string> $existingTemplates
   */
  #[Test]
  #[DataProvider('templateDirectoryPrecedenceDataProvider')]
  public function templateDirectoryPrecedence(string $expected, array $existingTemplates): void
  {
    $registry = self::discover([
      self::FIXTURES_NAMESPACE . 'Override\\' => ['@views/components', '@framework/components'],
    ], $existingTemplates);

    self::assertSame(
      $expected,
      $registry->getComponent('Button')?->template,
      'The template in the earliest directory should be used.',
    );
  }

  /**
   * @return iterable<string, array{ string, list<string> }>
   */
  public static function templateDirectoryPrecedenceDataProvider(): iterable
  {
    yield 'Both directories' => [
      '@views/components/Button.html.twig',
      ['@views/components/Button.html.twig', '@framework/components/Button.html.twig'],
    ];
    yield 'First directory only' => ['@views/components/Button.html.twig', ['@views/components/Button.html.twig']];
    yield 'Second directory only' => [
      '@framework/components/Button.html.twig',
      ['@framework/components/Button.html.twig'],
    ];
  }

  #[Test]
  public function laterNamespacesReplaceComponents(): void
  {
    $registry = self::discover([
      self::FIXTURES_NAMESPACE . 'Basic\\' => '@framework/components',
      self::FIXTURES_NAMESPACE . 'Override\\' => '@views/components',
    ], [
      '@framework/components/Button.html.twig',
      '@framework/components/Media/Gallery.html.twig',
      '@framework/components/Custom/Name.html.twig',
      '@views/components/Button.html.twig',
    ]);

    self::assertSame(
      Fixtures\Override\Button::class,
      $registry->getComponent('Button')?->className,
      'The component in the later namespace should replace the earlier one.',
    );
  }

  #[Test]
  #[DataProvider('normalisesNamespacesDataProvider')]
  public function normalisesNamespaces(string $namespace): void
  {
    $registry = self::discover([$namespace => '@views/components'], ['@views/components/Button.html.twig']);

    self::assertSame(['Button'], \array_keys($registry->getComponents()), 'The namespace should be normalised.');
  }

  /**
   * @return iterable<string, array{ string }>
   */
  public static function normalisesNamespacesDataProvider(): iterable
  {
    yield 'Trailing separator' => [self::FIXTURES_NAMESPACE . 'Override\\'];
    yield 'No trailing separator' => [self::FIXTURES_NAMESPACE . 'Override'];
    yield 'Leading separator' => ['\\' . self::FIXTURES_NAMESPACE . 'Override\\'];
  }

  #[Test]
  public function missingTemplatesUseFirstDirectory(): void
  {
    $registry = self::discover([
      self::FIXTURES_NAMESPACE . 'MissingTemplate\\' => ['@views/components/', '@framework/components'],
    ], []);

    self::assertSame(
      '@views/components/Orphan.html.twig',
      $registry->getComponent('Orphan')?->template,
      'A component without an existing template should use the template path in the first directory.',
    );
  }

  #[Test]
  public function throwsWithoutTemplateDirectories(): void
  {
    self::assertThrows(
      static function (): void {
        self::discover([self::FIXTURES_NAMESPACE . 'MissingTemplate\\' => []], []);
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof TemplateNotFoundException
        && $exception->componentName === 'Orphan'
        && $exception->className === Fixtures\MissingTemplate\Orphan::class
        && $exception->getMessage() === \sprintf(
          'No template found for component "Orphan" (%s).',
          Fixtures\MissingTemplate\Orphan::class,
        )
      ),
      message: 'A component without any template directories should be rejected.',
    );
  }

  #[Test]
  public function throwsForDuplicateNames(): void
  {
    self::assertThrows(
      static function (): void {
        self::discover([self::FIXTURES_NAMESPACE . 'Duplicates\\' => '@views/components'], [
          '@views/components/Same.html.twig',
        ]);
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof \LogicException
        && $exception->getMessage() === \sprintf(
          'Component classes "%s" and "%s" are both named "Same".',
          Fixtures\Duplicates\First::class,
          Fixtures\Duplicates\Second::class,
        )
      ),
      message: 'Components with the same name in a namespace should be rejected.',
    );
  }

  #[Test]
  public function throwsForOverlappingNamespaces(): void
  {
    self::assertThrows(
      static function (): void {
        self::discover([
          self::FIXTURES_NAMESPACE . 'Basic\\Media\\' => '@views/components',
          self::FIXTURES_NAMESPACE . 'Basic\\' => '@views/components',
        ], [
          '@views/components/Gallery.html.twig',
          '@views/components/Button.html.twig',
          '@views/components/Media/Gallery.html.twig',
          '@views/components/Custom/Name.html.twig',
        ]);
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof \LogicException
        && $exception->getMessage() === \sprintf(
          'Component class "%s" is within both the "%sBasic\\Media\\" and "%sBasic\\" component namespaces.',
          Fixtures\Basic\Media\Gallery::class,
          self::FIXTURES_NAMESPACE,
          self::FIXTURES_NAMESPACE,
        )
      ),
      message: 'A component within multiple configured namespaces should be rejected.',
    );
  }

  /**
   * @param array<string, string|list<string>> $namespaces
   * @param list<string> $existingTemplates
   */
  private static function discover(array $namespaces, array $existingTemplates): ComponentRegistry
  {
    $loader = new ArrayLoader(\array_fill_keys($existingTemplates, ''));
    $namespaceFinder = new NamespaceFinder(\array_values(ClassLoader::getRegisteredLoaders()));
    return new ComponentDiscoverer($loader, $namespaceFinder)->discover($namespaces);
  }

  /**
   * @template T
   *
   * @param array<string, T> $values
   *
   * @return array<string, T>
   */
  private static function sortByKey(array $values): array
  {
    \ksort($values);
    return $values;
  }
}
