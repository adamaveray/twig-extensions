<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversTrait(WithLocale::class)]
#[CoversTrait(WithSymfonyApp::class)]
final class LocaleTraitTest extends TestCase
{
  #[DataProvider('localeInferenceDataProvider')]
  public function testLocaleInference(
    string $expectedLocale,
    string|TranslatorInterface|null $translatorOrLocale,
    array $context,
  ): void {
    $instance = self::makeTraitInstance($translatorOrLocale);
    self::assertSame($expectedLocale, $instance->getLocale($context), 'The locale should be inferred correctly.');
  }

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

  public function testLocaleInferenceFailsWhenNoLocale(): void
  {
    $instance = self::makeTraitInstance(null);
    $this->expectException(\RuntimeException::class);
    $instance->getLocale([]);
  }

  private static function makeTraitInstance(string|TranslatorInterface|null $translatorOrLocale): object
  {
    return new class ($translatorOrLocale) {
      use WithLocale;
      use WithSymfonyApp;

      public function __construct(string|TranslatorInterface|null $translatorOrLocale)
      {
        $this->setLocaleProvider($translatorOrLocale);
      }

      public function getLocale(array $context): string
      {
        return $this->inferLocale($context);
      }
    };
  }
}
