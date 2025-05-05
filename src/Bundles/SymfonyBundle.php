<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Averay\TwigExtensions\Extensions\SymfonyCompatibilityExtension;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SymfonyBundle extends AbstractBundle
{
  public function __construct(?AppVariable $appVariable, ?TranslatorInterface $translator, ?Packages $assetPackages)
  {
    $this->extensions = [
      // TwigBridge-provided
      new Extension\CsrfExtension(),
      new Extension\FormExtension($translator),
      new Extension\YamlExtension(),

      // Custom
      new SymfonyCompatibilityExtension($appVariable),
    ];
    if ($assetPackages !== null) {
      $this->extensions[] = new Extension\AssetExtension($assetPackages);
    }
  }
}
