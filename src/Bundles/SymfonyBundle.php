<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SymfonyBundle extends AbstractBundle
{
  public function __construct(
    private readonly ?AppVariable $appVariable,
    ?TranslatorInterface $translator,
    ?Packages $assetPackages,
  ) {
    $this->extensions = [
      new Extension\CsrfExtension(),
      new Extension\FormExtension($translator),
      new Extension\YamlExtension(),
    ];
    if ($assetPackages !== null) {
      $this->extensions[] = new Extension\AssetExtension($assetPackages);
    }
  }

  /**
   * @return array<string, mixed>
   */
  #[\Override]
  public function getGlobals(): array
  {
    /** @var array<string, mixed> $globals */
    $globals = [];
    if ($this->appVariable !== null) {
      $globals['app'] = $this->appVariable;
    }
    return $globals + parent::getGlobals();
  }
}
