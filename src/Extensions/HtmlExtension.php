<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\Traits\WithRequest;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use Psr\Http\Message\UriInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class HtmlExtension extends AbstractExtension
{
  use WithRequest;
  use WithSymfonyApp;

  #[\Override]
  public function getFilters(): array
  {
    return [
      new TwigFilter('wrap_words', self::wrapWords(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFilter('wrap_paragraphs', self::wrapParagraphs(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFilter('add_class', self::addClass(...), [
        'needs_environment' => true,
      ]),
      new TwigFilter('map_html_ids', self::mapHtmlIds(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFilter('prefix_html_ids', self::prefixHtmlIds(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFilter('data_uri', self::generateDataUri(...), [
        'needs_environment' => true,
      ]),
    ];
  }

  #[\Override]
  public function getFunctions(): array
  {
    return [
      new TwigFunction('attrs', self::buildAttrs(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFunction('classes', self::buildClasses(...), [
        'needs_environment' => true,
      ]),
      new TwigFunction('stylesheet', self::buildStylesheet(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFunction('script', self::buildScript(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFunction('preload_links', self::buildPreloadLinks(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFunction('srcset', self::buildSrcSet(...), [
        'needs_environment' => true,
      ]),
      new TwigFunction('current_url_attr', self::buildCurrentUrlAttr(...), [
        'needs_context' => true,
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
    ];
  }

  /**
   * @param array<array-key, \Stringable|scalar|null> ...$attrs
   * @see HtmlBuilder::buildAttrs
   */
  private static function buildAttrs(Environment $environment, array ...$attrs): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildAttrs(...$attrs);
  }

  /**
   * @param array<string, bool> $classes
   * @see HtmlBuilder::buildClasses
   */
  private static function buildClasses(Environment $environment, array $classes): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildClasses($classes);
  }

  /**
   * @see HtmlBuilder::addClasses
   */
  private static function addClass(Environment $environment, string $class_list, string ...$additional_classes): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->addClasses($class_list, ...$additional_classes);
  }

  /**
   * @param "anonymous"|"use-credentials"|null $crossorigin
   * @see HtmlBuilder::buildStylesheet
   */
  private static function buildStylesheet(
    Environment $environment,
    string $url,
    ?string $media = null,
    ?string $integrity = null,
    ?string $crossorigin = null,
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildStylesheet($url, $media, $integrity, $crossorigin);
  }

  /**
   * @param "module"|null $type A script type ("media").
   * @param "anonymous"|"use-credentials"|null $crossorigin
   * @see HtmlBuilder::buildScript
   */
  private static function buildScript(
    Environment $environment,
    string $url,
    ?string $type = null,
    bool $async = false,
    ?string $integrity = null,
    ?string $crossorigin = null,
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildScript($url, $type, $async, $integrity, $crossorigin);
  }

  /**
   * @psalm-type FullResource = array{
   *   url: string,
   *   integrity?: string,
   *   crossorigin?: string,
   * }
   * @param array<value-of<HtmlBuilder::PRELOAD_TYPES>, string|list<string|FullResource>> $preloads
   * @param list<string> $preconnect_hosts
   * @see HtmlBuilder::buildPreloadLinks
   */
  private static function buildPreloadLinks(
    Environment $environment,
    array $preloads,
    array $preconnect_hosts = [],
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildPreloadLinks($preloads, $preconnect_hosts);
  }

  /**
   * @param array<string, string> $entries
   * @see HtmlBuilder::buildSrcSet
   */
  private static function buildSrcSet(Environment $environment, array $entries): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->buildSrcSet($entries);
  }

  /**
   * @param array<string, mixed> $context
   * @param 'page'|'step'|'location'|'date'|'time'|true $value The ARIA value for the referenced item.
   * @see HtmlBuilder::buildCurrentUrlAttr
   */
  private static function buildCurrentUrlAttr(
    Environment $environment,
    array $context,
    string|UriInterface $link_url,
    string|UriInterface|null $current_url = null,
    string|true $value = 'page',
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    $current_url ??= self::inferRequestUri($context);
    return $htmlBuilder->buildCurrentUrlAttr($link_url, $current_url, $value);
  }

  /**
   * @see HtmlBuilder::wrapParagraphs
   */
  private static function wrapParagraphs(Environment $environment, string $string, string $wrapping_tag = '<p>'): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->wrapParagraphs($string, $wrapping_tag);
  }

  /**
   * @see HtmlBuilder::wrapWords
   */
  private static function wrapWords(Environment $environment, string $string, string $wrapping_tag): string
  {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->wrapWords($string, $wrapping_tag);
  }

  /**
   * @param callable(string $attribute_value, string $attribute_name):string $transformer
   * @param list<string> $additional_attributes
   * @see HtmlBuilder::mapHtmlIds
   */
  private static function mapHtmlIds(
    Environment $environment,
    string $html,
    callable $transformer,
    array $additional_attributes = [],
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->mapHtmlIds($html, $transformer, $additional_attributes);
  }

  /**
   * @param list<string> $additional_attributes
   * @see HtmlBuilder::mapHtmlIds
   */
  private static function prefixHtmlIds(
    Environment $environment,
    string $html,
    string $prefix,
    string $separator = '-',
    array $additional_attributes = [],
  ): string {
    return self::mapHtmlIds(
      $environment,
      $html,
      static fn(string $id): string => $prefix . ($id === '' ? '' : $separator) . $id,
      $additional_attributes,
    );
  }

  /**
   * @param array<string, string> $parameters
   * @see HtmlBuilder::generateDataUri
   */
  private static function generateDataUri(
    Environment $environment,
    string $data,
    ?string $mime = null,
    array $parameters = [],
  ): string {
    $htmlBuilder = $environment->getRuntime(HtmlBuilder::class);
    return $htmlBuilder->generateDataUri($data, $mime, $parameters);
  }
}
