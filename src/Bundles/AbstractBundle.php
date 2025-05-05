<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Twig\Extension\ExtensionInterface;

abstract class AbstractBundle implements ExtensionBundleInterface
{
  /** @var list<ExtensionInterface> */
  protected array $extensions = [];

  /**
   * @return list<ExtensionInterface>
   */
  #[\Override]
  public function getExtensions(): array
  {
    return $this->extensions;
  }

  protected function withBundle(ExtensionBundleInterface $bundle): static
  {
    $clone = clone $this;
    foreach ($bundle->getExtensions() as $extension) {
      $clone->extensions[] = $extension;
    }
    return $clone;
  }
}
