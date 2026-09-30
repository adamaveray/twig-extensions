<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Bundles;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Bundles\DefaultBundle;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;

/**
 * @internal
 */
#[CoversClass(DefaultBundle::class)]
final class DefaultBundleTest extends TestCase
{
  #[Test]
  public function providesHtmlExtraFunctions(): void
  {
    $environment = self::makeBundleEnvironment(<<<'TWIG'
      {{- html_classes('a', { b: true, c: false }) -}}
      TWIG);

    self::assertRenders('a b', $environment, message: 'The html-extra functions should be available.');
  }

  /**
   * Both `twig/html-extra` and HtmlExtension provide `data_uri` filters, but the Twig one is less full-featured so the improved one must be verified as the one in use.
   */
  #[Test]
  public function customDataUriFilterTakesPriority(): void
  {
    $environment = self::makeBundleEnvironment(<<<'TWIG'
      {{- '<svg/>' | data_uri(mime: 'image/svg+xml') -}}
      TWIG);

    self::assertRenders(
      'data:image/svg+xml,%3Csvg%2F%3E', // `twig/html-extra`'s version base64-encodes XML MIME types.
      $environment,
      message: 'The custom data_uri filter should override the html-extra implementation.',
    );
  }

  private static function makeBundleEnvironment(string $template): Environment
  {
    return self::makeEnvironment($template, new DefaultBundle(null)->getExtensions(), [
      HtmlBuilder::class => new HtmlBuilder(new MimeTypes()),
    ]);
  }
}
