<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use Faker\Factory;
use Faker\Generator;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

final class PlaceholdersExtension extends AbstractExtension
{
  use WithLocale;
  use WithSymfonyApp;

  private const PATH_IMAGE_TEMPLATE = __DIR__ . '/../../views/placeholders/placeholder-image.svg.twig';

  /** @var array<string, Generator> $generators Keys are locales. */
  private array $generators = [];

  private ?TemplateWrapper $placeholderImageTemplate = null;

  public function __construct(string|TranslatorInterface|null $translatorOrLocale = null)
  {
    $this->setLocaleProvider($translatorOrLocale);
  }

  #[\Override]
  public function getFunctions(): array
  {
    return [
      new TwigFunction('placeholder_generator', $this->getGenerator(...), ['needs_context' => true]),

      new TwigFunction('placeholder_words', $this->getWords(...), ['needs_context' => true]),
      new TwigFunction('placeholder_sentences', $this->getSentences(...), ['needs_context' => true]),
      new TwigFunction('placeholder_paragraphs', $this->getParagraphs(...), ['needs_context' => true]),
      new TwigFunction('placeholder_text', $this->getText(...), ['needs_context' => true]),

      new TwigFunction('placeholder_image_url', $this->getImageUrl(...), ['needs_environment' => true]),
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  private function getGenerator(array $context, ?string $locale = null): Generator
  {
    $locale ??= $this->inferLocale($context);
    $this->generators[$locale] ??= Factory::create($locale);
    return $this->generators[$locale];
  }

  /**
   * @param array<string, mixed> $context
   */
  private function getWords(array $context, int $count, ?string $locale = null): string
  {
    $result = $this->getGenerator($context, $locale)->words($count, asText: true);
    \assert(\is_string($result), 'A string must be returned since asText is true.');
    return $result;
  }

  /**
   * @param array<string, mixed> $context
   */
  private function getSentences(array $context, int $count, ?string $locale = null): string
  {
    $result = $this->getGenerator($context, $locale)->sentences($count, asText: true);
    \assert(\is_string($result), 'A string must be returned since asText is true.');
    return $result;
  }

  /**
   * @param array<string, mixed> $context
   */
  private function getParagraphs(array $context, int $count, ?string $locale = null): string
  {
    $result = $this->getGenerator($context, $locale)->paragraphs($count, asText: true);
    \assert(\is_string($result), 'A string must be returned since asText is true.');
    return $result;
  }

  /**
   * @param array<string, mixed> $context
   */
  private function getText(array $context, int $count, ?string $locale = null): string
  {
    return $this->getGenerator($context, $locale)->text($count);
  }

  private function getImageUrl(
    Environment $environment,
    int|float $width,
    int|float $height,
    ?string $label = null,
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);

    if ($this->placeholderImageTemplate === null) {
      $templateContent = \file_get_contents(self::PATH_IMAGE_TEMPLATE);
      if ($templateContent === false) {
        // @codeCoverageIgnoreStart
        throw new \RuntimeException(
          \sprintf('Failed reading placeholder image template file "%s".', self::PATH_IMAGE_TEMPLATE),
        );
        // @codeCoverageIgnoreEnd
      }
      $this->placeholderImageTemplate = $environment->createTemplate($templateContent);
    }

    $svg = $this->placeholderImageTemplate->render([
      'width' => (int) $width,
      'height' => (int) $height,
      'label' => $label,
      'colors' => self::generateRandomColorField(4),
    ]);
    return $htmlBuilder->generateDataUri($svg, 'image/svg+xml');
  }

  /**
   * @psalm-type Color = array{ r: int, g: int, b: int }
   * @param positive-int $count How many colours to generate.
   * @param positive-int $distance How far from the internal seed colour each colour can be.
   * @return list<Color>
   * @throws \Random\RandomException
   */
  private static function generateRandomColorField(int $count, int $distance = 250): array
  {
    $getRandomColorChannel = static function (int $seed) use ($distance): int {
      $value = $seed + \random_int(-$distance, $distance);
      return \max(0, \min(255, $value));
    };

    $hash = \md5('color-' . \random_int(0, 99999999));
    $seed = [
      'r' => (int) \hexdec(\substr($hash, 0, 2)),
      'g' => (int) \hexdec(\substr($hash, 2, 2)),
      'b' => (int) \hexdec(\substr($hash, 4, 2)),
    ];

    /** @var list<Color> $colors */
    $colors = [];
    for ($i = 0; $i < $count; $i++) {
      $colors[] = [
        'r' => $getRandomColorChannel($seed['r']),
        'g' => $getRandomColorChannel($seed['g']),
        'b' => $getRandomColorChannel($seed['b']),
      ];
    }
    return $colors;
  }
}
