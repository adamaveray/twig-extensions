<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Intl\IntlExtension;

final class IntlBundle extends AbstractBundle
{
  public function __construct(
    TranslatorInterface $translator,
    ?\IntlDateFormatter $dateFormatter = null,
    ?\NumberFormatter $numberFormatter = null,
  ) {
    $this->extensions = [new TranslationExtension($translator), new IntlExtension($dateFormatter, $numberFormatter)];
  }
}
