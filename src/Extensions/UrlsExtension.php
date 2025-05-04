<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Symfony\Component\String\AbstractString;
use Symfony\Component\String\AbstractUnicodeString;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function Symfony\Component\String\u;

final class UrlsExtension extends AbstractExtension
{
  private const URL_WWW_PREFIX = 'www.';
  private const URL_PARTS_MAP = [
    'scheme' => \PHP_URL_SCHEME,
    'host' => \PHP_URL_HOST,
    'port' => \PHP_URL_PORT,
    'username' => \PHP_URL_USER,
    'password' => \PHP_URL_PASS,
    'path' => \PHP_URL_PATH,
    'query' => \PHP_URL_QUERY,
    'fragment' => \PHP_URL_FRAGMENT,
  ];

  /**
   * @return list<TwigFilter>
   */
  #[\Override]
  public function getFilters(): array
  {
    return [
      new TwigFilter('url_part', self::filterUrlPart(...)),
      new TwigFilter('append_query_params', self::filterAppendQueryParams(...)),
    ];
  }

  private static function filterUrlPart(string $url, string $part, bool $strip_www = false): string|int|null
  {
    $component =
      self::URL_PARTS_MAP[$part] ?? throw new \OutOfBoundsException(\sprintf('Unknown URL part "%s".', $part));

    $result = \parse_url($url, $component);
    if ($component === \PHP_URL_HOST && $strip_www) {
      $result = u((string) $result)->trimPrefix(self::URL_WWW_PREFIX)->toString();
    }
    return $result;
  }

  /**
   * Appends query string components to an existing URL.
   *
   * @param string $url A URL to append to.
   * @param array<string, string|int> $values
   * @return string|\Stringable The URL with the new parameters added.
   */
  private static function filterAppendQueryParams(string $url, array $values, bool $raw = false): string|\Stringable
  {
    $url = u($url);
    $encoder = $raw ? \rawurlencode(...) : \urlencode(...);

    $separator = $url->containsAny('?') ? '&' : '?';
    /** @var array{ 0: AbstractString, 1?: AbstractString } $parts */
    $parts = $url->split('#', 2);

    $newUrl = $parts[0];
    foreach ($values as $newName => $newValue) {
      $newUrl = $newUrl->append($separator, $encoder($newName), '=', $encoder((string) $newValue));
      $separator = '&';
    }

    if (isset($parts[1])) {
      // Restore hash
      $newUrl = $newUrl->append('#', $parts[1]->toString());
    }

    return $newUrl;
  }
}
