<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Twig\Extension\ExtensionInterface;

interface BundledExtensionsInterface
{
  /**
   * @return list<ExtensionInterface>
   */
  public function getExtensions(): array;
}
