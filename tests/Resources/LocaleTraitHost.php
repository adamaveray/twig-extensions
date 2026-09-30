<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Resources;

use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class LocaleTraitHost
{
  use WithLocale;

  public function __construct(string|TranslatorInterface|null $translatorOrLocale)
  {
    $this->setLocaleProvider($translatorOrLocale);
  }

  /**
   * @param array<string, mixed> $context
   */
  public function getLocale(array $context): string
  {
    return $this->inferLocale($context);
  }
}
