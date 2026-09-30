<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Twig\Extension\ExtensionInterface;

/**
 * @api
 */
interface ExtensionBundleInterface
{
  /**
   * @return list<ExtensionInterface>
   */
  public function getExtensions(): array;
}
