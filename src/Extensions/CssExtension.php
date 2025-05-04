<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\HtmlBuilder\Components\Media;
use Averay\HtmlBuilder\Css;
use Averay\HtmlBuilder\Css\CssBuilder;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class CssExtension extends AbstractExtension
{
  #[\Override]
  public function getFilters(): array
  {
    return [new TwigFilter('css_string', Css\escapeString(...))];
  }

  #[\Override]
  public function getFunctions(): array
  {
    return [
      new TwigFunction('css_image_set', $this->buildImageSet(...), ['needs_environment' => true]),
      new TwigFunction('css_properties', $this->buildProperties(...), ['needs_environment' => true]),
      new TwigFunction('css_property', $this->buildProperty(...), ['needs_environment' => true]),
      new TwigFunction('css_url', $this->buildUrl(...), ['needs_environment' => true]),
    ];
  }

  /**
   * @param iterable<string, string>|Media\HasMediaFormats|Media\HasMediaDensities $entries
   */
  private function buildImageSet(
    Environment $environment,
    iterable|Media\HasMediaFormats|Media\HasMediaDensities $entries,
    ?string $format = null,
  ): string {
    return $environment->getRuntime(CssBuilder::class)->buildImageSet($entries, $format);
  }

  /**
   * @param array<string, string | list<string>> $properties
   */
  private function buildProperties(Environment $environment, array $properties): string
  {
    return $environment->getRuntime(CssBuilder::class)->buildProperties($properties);
  }

  /**
   * @param string|list<string> $values
   */
  private function buildProperty(Environment $environment, string $name, string|array $values): string
  {
    return $environment->getRuntime(CssBuilder::class)->buildProperty($name, $values);
  }

  private function buildUrl(Environment $environment, string $url): string
  {
    return $environment->getRuntime(CssBuilder::class)->buildUrl($url);
  }
}
