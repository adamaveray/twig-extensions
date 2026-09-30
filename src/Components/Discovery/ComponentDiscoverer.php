<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Discovery;

use Averay\TwigExtensions\Components\Definitions\ComponentDefinition;
use Averay\TwigExtensions\Components\Definitions\ComponentRegistry;
use Averay\TwigExtensions\Components\Discovery\Exceptions\TemplateNotFoundException;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Twig\Loader\LoaderInterface;

/**
 * Discovers classes marked with `#[AsTwigComponent]` within PSR-4 autoloaded namespaces.
 *
 * @internal
 */
final readonly class ComponentDiscoverer
{
  private const string TEMPLATE_EXTENSION = 'html.twig'; // Matching non-configurable Symfony UX value

  public function __construct(
    private LoaderInterface $loader,
    private NamespaceFinder $namespaceFinder,
  ) {}

  /**
   * Locates components defined in the given namespaces. Components found in later namespaces replace same-named components found in earlier namespaces.
   *
   * @param array<string, string|list<string>> $namespaces PHP namespaces to search, mapped to the template directories to find their components' templates within, in order of precedence.
   */
  public function discover(array $namespaces): ComponentRegistry
  {
    /** @var array<string, ComponentDefinition> $components */
    $components = [];
    /** @var array<class-string, string> $classNamespaces */
    $classNamespaces = [];

    foreach ($namespaces as $namespace => $templateDirectories) {
      $namespace = \trim($namespace, '\\') . '\\';
      $templateDirectories = \is_string($templateDirectories) ? [$templateDirectories] : $templateDirectories;
      /** @var array<string, class-string> $namespaceComponents */
      $namespaceComponents = [];

      foreach ($this->findComponentClassNames($namespace) as $className => $attribute) {
        // Prevent duplicate namespaces for component
        if (isset($classNamespaces[$className])) {
          throw new \LogicException(\sprintf(
            'Component class "%s" is within both the "%s" and "%s" component namespaces.',
            $className,
            $classNamespaces[$className],
            $namespace,
          ));
        }
        $classNamespaces[$className] = $namespace;

        $component = $this->buildDefinition($className, $namespace, $templateDirectories, $attribute);
        $componentName = $component->name;

        // Prevent duplicate components in namespace
        if (isset($namespaceComponents[$componentName])) {
          throw new \LogicException(\sprintf(
            'Component classes "%s" and "%s" are both named "%s".',
            $namespaceComponents[$componentName],
            $className,
            $componentName,
          ));
        }
        $namespaceComponents[$componentName] = $className;

        $components[$componentName] = $component;
      }
    }

    return new ComponentRegistry($components);
  }

  /**
   * @return iterable<class-string, AsTwigComponent>
   */
  private function findComponentClassNames(string $namespace): iterable
  {
    foreach ($this->namespaceFinder->findForNamespace($namespace) as $className) {
      $reflectionClass = new \ReflectionClass($className);
      if (!$reflectionClass->isInstantiable()) {
        // Non-instantiable class (abstract, etc)
        continue;
      }

      // Check for attribute
      $reflectionAttribute = \array_first($reflectionClass->getAttributes(AsTwigComponent::class));
      if ($reflectionAttribute === null) {
        // Class missing attribute
        continue;
      }

      yield $className => $reflectionAttribute->newInstance();
    }
  }

  /**
   * @param class-string $className
   * @param list<string> $templateDirectories
   */
  private function buildDefinition(
    string $className,
    string $namespace,
    array $templateDirectories,
    AsTwigComponent $attribute,
  ): ComponentDefinition {
    /** @var array{
     *   key: string|null,
     *   template?: string|null,
     *   template_from_method?: string,
     *   expose_public_props: bool,
     *   attributes_var: string,
     * } $attributeConfig
     */
    $attributeConfig = $attribute->serviceConfig();

    $name = $attributeConfig['key'] ?? \str_replace('\\', ':', \substr($className, \strlen($namespace)));
    $templateMethod = $attributeConfig['template_from_method'] ?? null;
    $template = $attributeConfig['template'] ?? null;
    if ($template === null && $templateMethod === null) {
      $templatePaths = self::getTemplatePaths($name, $templateDirectories);
      $template = $this->findExistingTemplate($templatePaths) ?? throw new TemplateNotFoundException(
          componentName: $name,
          className: $className,
          searchedPaths: $templatePaths,
        );
    }

    [
      'preMount' => $preMountMethods,
      'mount' => $mountMethods,
      'postMount' => $postMountMethods,
    ] = self::findMountMethods($className);
    return new ComponentDefinition(
      name: $name,
      className: $className,
      template: $template,
      templateMethod: $templateMethod,
      exposePublicProps: $attributeConfig['expose_public_props'],
      attributesVar: $attributeConfig['attributes_var'],
      preMountMethods: $preMountMethods,
      mountMethods: $mountMethods,
      postMountMethods: $postMountMethods,
    );
  }

  /**
   * @param list<string> $templatePaths
   */
  private function findExistingTemplate(array $templatePaths): ?string
  {
    foreach ($templatePaths as $templatePath) {
      if ($this->loader->exists($templatePath)) {
        return $templatePath;
      }
    }
    return null;
  }

  /**
   * @param list<string> $templateDirectories
   *
   * @return list<string> Candidate template paths for the component, in order of precedence.
   */
  private static function getTemplatePaths(string $componentName, array $templateDirectories): array
  {
    $componentPath = \str_replace(':', '/', $componentName) . '.' . self::TEMPLATE_EXTENSION;
    return \array_map(
      static fn(string $directory): string => \rtrim($directory, '/') . '/' . $componentPath,
      $templateDirectories,
    );
  }

  /**
   * @param class-string $className
   *
   * @return array{ preMount: list<string>, mount: list<string>, postMount: list<string> } Method name sets.
   */
  private static function findMountMethods(string $className): array
  {
    /** @var array<string, int> $preMount */
    $preMount = [];
    /** @var array<string, int> $mount */
    $mount = [];
    /** @var array<string, int> $postMount */
    $postMount = [];

    foreach (new \ReflectionClass($className)->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
      foreach ($method->getAttributes(PreMount::class) as $attribute) {
        $preMount[$method->getName()] = $attribute->newInstance()->priority;
      }
      foreach ($method->getAttributes(PostMount::class) as $attribute) {
        $postMount[$method->getName()] = $attribute->newInstance()->priority;
      }
      if ($method->getName() === 'mount') {
        $mount['mount'] = 0;
      }
    }

    \arsort($preMount, \SORT_NUMERIC);
    \arsort($postMount, \SORT_NUMERIC);

    return [
      'preMount' => \array_keys($preMount),
      'mount' => \array_keys($mount),
      'postMount' => \array_keys($postMount),
    ];
  }
}
