<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use League\CommonMark\ConverterInterface as MarkdownConverterInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function Symfony\Component\String\b;
use function Symfony\Component\String\s;
use function Symfony\Component\String\u;

final class StringsExtension extends AbstractExtension
{
  use WithLocale;
  use WithSymfonyApp;

  public function __construct(string|TranslatorInterface|null $translatorOrLocale = null)
  {
    $this->setLocaleProvider($translatorOrLocale);
  }

  /**
   * @return list<TwigFilter>
   */
  #[\Override]
  public function getFilters(): array
  {
    return [
      new TwigFilter('b', b(...)),
      new TwigFilter('s', s(...)),
      new TwigFilter('u', u(...)),

      new TwigFilter('lower', $this->filterCaseLower(...), ['needs_context' => true]),
      new TwigFilter('upper', $this->filterCaseUpper(...), ['needs_context' => true]),

      new TwigFilter('slug', $this->filterSlug(...), ['needs_environment' => true, 'needs_context' => true]),

      new TwigFilter('markdown', $this->filterMarkdown(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]),
      new TwigFilter('markdown_to_html', $this->filterMarkdown(...), [
        'needs_environment' => true,
        'is_safe' => ['html'],
      ]), // For compatibility with Twig documentation

      new TwigFilter('outdent', self::filterOutdent(...)),
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  private function filterCaseLower(
    array $context,
    string $string,
    string $target = 'all',
    ?string $locale = null,
  ): string|\Stringable {
    $locale ??= $this->inferLocale($context);

    $string = u($string);
    return match ($target) {
      'all' => $string->localeLower($locale),
      'words' => $string->replaceMatches('~\b\w~', static function (array $matches) use ($locale): string {
        /** @var array{ string } $matches */
        return u($matches[0])->localeLower($locale)->toString();
      }),
      'first' => $string->slice(0, 1)->localeLower($locale)->toString() . $string->slice(1)->toString(),
      default => throw new \OutOfBoundsException(\sprintf('Unknown case transformation target "%s".', $target)),
    };
  }

  /**
   * @param array<string, mixed> $context
   */
  private function filterCaseUpper(
    array $context,
    string $string,
    string $target = 'all',
    ?string $locale = null,
  ): string|\Stringable {
    $locale ??= $this->inferLocale($context);

    $string = u($string);
    return match ($target) {
      'all' => $string->localeUpper($locale),
      'words' => $string->replaceMatches('~\b\w~', static function (array $matches) use ($locale): string {
        /** @var array{ string } $matches */ return u($matches[0])->localeUpper($locale)->toString();
      }),
      'first' => $string->slice(0, 1)->localeUpper($locale)->toString() . $string->slice(1)->toString(),
      default => throw new \OutOfBoundsException(\sprintf('Unknown case transformation target "%s".', $target)),
    };
  }

  /**
   * @param array<string, mixed> $context
   */
  private function filterSlug(
    Environment $environment,
    array $context,
    string $string,
    string $separator = '-',
    ?string $case = 'lower',
    ?string $locale = null,
  ): string|\Stringable {
    $locale ??= $this->inferLocale($context);

    $slugger = $environment->getRuntime(SluggerInterface::class);
    $slug = $slugger->slug($string, $separator, $locale);
    return match ($case) {
      'lower' => $slug->localeLower($locale),
      'upper' => $slug->localeUpper($locale),
      null => $slug,
      default => throw new \OutOfBoundsException(\sprintf('Unknown case transformation target "%s".', $case)),
    };
  }

  public function filterMarkdown(
    Environment $environment,
    string $string,
    bool $ignore_indentation = false,
  ): string|\Stringable {
    $converter = $environment->getRuntime(MarkdownConverterInterface::class);
    if ($ignore_indentation) {
      $string = self::stripIndentation($string);
    }
    return $converter->convert($string);
  }

  private static function filterOutdent(string $string): string
  {
    return self::stripIndentation($string);
  }

  private static function stripIndentation(string $string): string
  {
    $indentation = \substr($string, 0, \strspn($string, " \t\r\n\0\x0B"));
    if (!empty($indentation)) {
      // Remove indentation
      $string = \preg_replace('~^' . \preg_quote($indentation, '~') . '~m', '', $string);
      if (!\is_string($string)) {
        // @codeCoverageIgnoreStart
        throw new \UnexpectedValueException('Failed stripping indentation.');
        // @codeCoverageIgnoreEnd
      }
    }
    return $string;
  }
}
