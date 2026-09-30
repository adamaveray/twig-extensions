<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use Averay\TwigExtensions\Tests\Resources\LocaleTraitHost;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
#[CoversTrait(WithLocale::class)]
#[CoversTrait(WithSymfonyApp::class)]
final class LocaleTraitTest extends TestCase
{
  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('localeInferenceDataProvider')]
  public function localeInference(
    string $expectedLocale,
    string|TranslatorInterface|null $translatorOrLocale,
    array $context,
  ): void {
    $instance = new LocaleTraitHost($translatorOrLocale);
    self::assertSame($expectedLocale, $instance->getLocale($context), 'The locale should be inferred correctly.');
  }

  /**
   * @return iterable<string, array{
   *   expectedLocale: string,
   *   translatorOrLocale: string|TranslatorInterface|null,
   *   context: array<string, mixed>,
   * }>
   */
  public static function localeInferenceDataProvider(): iterable
  {
    $app = new SymfonyAppVariable();
    $app->setLocaleSwitcher(new LocaleSwitcher('zz_ZZ', []));
    yield 'Context Symfony app' => [
      'expectedLocale' => 'zz_ZZ',
      'translatorOrLocale' => null,
      'context' => ['app' => $app],
    ];

    yield 'Context Symfony app fallback to locale' => [
      'expectedLocale' => 'zz_ZZ',
      'translatorOrLocale' => null,
      'context' => [
        'app' => new SymfonyAppVariable(),
        'locale' => 'zz_ZZ',
      ],
    ];

    yield 'Context locale' => [
      'expectedLocale' => 'zz_ZZ',
      'translatorOrLocale' => null,
      'context' => ['locale' => 'zz_ZZ'],
    ];

    yield 'Preset locale value' => [
      'expectedLocale' => 'zz_ZZ',
      'translatorOrLocale' => 'zz_ZZ',
      'context' => [],
    ];

    $translator = new Translator('zz_ZZ');
    yield 'Preset Symfony translator' => [
      'expectedLocale' => 'zz_ZZ',
      'translatorOrLocale' => $translator,
      'context' => [],
    ];
  }

  #[Test]
  public function localeInferenceFailsWhenNoLocale(): void
  {
    $instance = new LocaleTraitHost(null);
    $this->expectException(\RuntimeException::class);
    $instance->getLocale([]);
  }
}
