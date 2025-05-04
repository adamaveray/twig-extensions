<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Twig\Extension\ExtensionInterface;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\LastModifiedExtensionInterface;
use Twig\NodeVisitor\NodeVisitorInterface;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

abstract class AbstractBundle implements
  ExtensionInterface,
  GlobalsInterface,
  LastModifiedExtensionInterface,
  BundledExtensionsInterface
{
  /** @var list<ExtensionInterface> */
  protected array $extensions = [];
  private ?int $lastModified = null;

  /**
   * @return list<ExtensionInterface>
   */
  #[\Override]
  public function getExtensions(): array
  {
    return $this->extensions;
  }

  /** @return list<TokenParserInterface> */
  #[\Override]
  public function getTokenParsers(): array
  {
    /** @var list<TokenParserInterface> */
    return $this->loadAll('getTokenParsers');
  }

  /** @return list<NodeVisitorInterface> */
  #[\Override]
  public function getNodeVisitors(): array
  {
    /** @var list<NodeVisitorInterface> */
    return $this->loadAll('getNodeVisitors');
  }

  /** @return list<TwigFilter> */
  #[\Override]
  public function getFilters(): array
  {
    /** @var list<TwigFilter> */
    return $this->loadAll('getFilters');
  }

  /** @return list<TwigTest> */
  #[\Override]
  public function getTests(): array
  {
    /** @var list<TwigTest> */
    return $this->loadAll('getTests');
  }

  /** @return list<TwigFunction> */
  #[\Override]
  public function getFunctions(): array
  {
    /** @var list<TwigFunction> */
    return $this->loadAll('getFunctions');
  }

  /**
   * @psalm-suppress UnusedPsalmSuppress It is actually used...
   * @psalm-suppress LessSpecificReturnStatement,MoreSpecificReturnType Library type definitions are too complex and have incorrect class references.
   */
  #[\Override]
  public function getOperators(): array
  {
    return $this->loadAll('getOperators');
  }

  /**
   * @return array<string, mixed>
   */
  #[\Override]
  public function getGlobals(): array
  {
    /** @var list<array<string, mixed>> $globals */
    $sets = [];
    foreach ($this->getExtensions() as $extension) {
      if ($extension instanceof GlobalsInterface) {
        /** @var array<string, mixed> */
        $sets[] = $extension->getGlobals();
      }
    }
    return \array_merge(...$sets);
  }

  #[\Override]
  public function getLastModified(): int
  {
    $this->lastModified ??= $this->loadLastModified();
    return $this->lastModified;
  }

  private function loadAll(string $method): array
  {
    $sets = [];
    foreach ($this->getExtensions() as $extension) {
      /** @var array $set */
      $set = $extension->{$method}();
      $sets[] = $set;
    }
    return \array_merge(...$sets);
  }

  private function loadLastModified(): int
  {
    $lastModified = 0;
    foreach ($this->getExtensions() as $extension) {
      if ($extension instanceof LastModifiedExtensionInterface) {
        $lastModified = \max($extension->getLastModified(), $lastModified);
      } else {
        $r = new \ReflectionObject($extension);
        if (!\is_file($r->getFileName())) {
          continue;
        }
        if (($mtime = \filemtime($r->getFileName())) === false) {
          continue;
        }
        $lastModified = \max($mtime, $lastModified);
      }
    }
    return $lastModified;
  }
}
