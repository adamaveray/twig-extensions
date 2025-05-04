<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Symfony\Bridge\Twig\Extension;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SymfonyBundle extends AbstractBundle
{
  public function __construct(?TranslatorInterface $translator, ?Packages $assetPackages)
  {
    $this->extensions = [
      new Extension\CsrfExtension(),
      new Extension\FormExtension($translator),
      new Extension\YamlExtension(),
    ];
    if ($assetPackages !== null) {
      $this->extensions[] = new Extension\AssetExtension($assetPackages);
    }
  }
}
