<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions\Traits;

use Symfony\Contracts\Translation\TranslatorInterface;

trait WithLocale
{
  public const CONTEXT_VALUE_LOCALE = 'locale';

  /** @var callable():(string|null)|null */
  protected mixed $localeProvider;

  final protected function setLocaleProvider(string|TranslatorInterface|null $translatorOrLocale): void
  {
    $this->localeProvider = match (true) {
      \is_string($translatorOrLocale) => static fn(): string => $translatorOrLocale,
      $translatorOrLocale instanceof TranslatorInterface => $translatorOrLocale->getLocale(...),
      $translatorOrLocale === null => null,
    };
  }

  /**
   * @param array<string, mixed> $context
   * @return string
   */
  final protected function inferLocale(array $context): string
  {
    $app = self::getAppVariableFromContext($context);
    if ($app !== null) {
      try {
        return $app->getLocale();
      } catch (\RuntimeException) {
        // App locale unavailable - try other sources
      }
    }

    /** @psalm-suppress MixedAssignment */
    $locale = $context[self::CONTEXT_VALUE_LOCALE] ?? null;
    if (\is_string($locale)) {
      return $locale;
    }

    if ($this->localeProvider !== null) {
      $locale = ($this->localeProvider)();
      if ($locale !== null) {
        return $locale;
      }
    }

    throw new \RuntimeException('The locale must be provided.');
  }
}
