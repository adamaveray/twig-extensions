<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Css;

use Averay\HtmlBuilder\Components\Media\HasMediaDensities;
use Averay\HtmlBuilder\Css\CssBuilder;
use Averay\TwigExtensions\Extensions\CssExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;

/**
 * @internal
 */
#[CoversClass(CssExtension::class)]
final class CssTest extends TestCase
{
  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('cssStringDataProvider')]
  public function cssString(string $expected, string $template, array $context = []): void
  {
    $environment = self::makeCssEnvironment($template);
    self::assertRenders($expected, $environment, context: $context);
  }

  /**
   * @return iterable<int, array{ expected: string, template: string }>
   */
  public static function cssStringDataProvider(): iterable
  {
    $sq = \htmlentities("'", \ENT_QUOTES);
    $dq = \htmlentities('"', \ENT_QUOTES);
    yield [
      'expected' => <<<HTML
        <div style="background:url({$sq}\\{$sq}hello\\{$sq} {$dq}world{$dq}.jpg{$sq})"></div>
        HTML,
      'template' => <<<'TWIG'
        <div style="background:url({{ '\'hello\' "world".jpg' | css_string }})"></div>
        TWIG,
    ];
  }

  #[Test]
  #[DataProvider('cssImageSetDataProvider')]
  public function cssImageSet(string $expected, mixed $entries, ?string $format = null): void
  {
    $environment = self::makeCssEnvironment('{{- css_image_set(entries, format) | raw -}}');
    self::assertRenders($expected, $environment, context: ['entries' => $entries, 'format' => $format]);
  }

  /**
   * @return iterable<string, array{
   *   expected: string,
   *   entries: array<string, string>|HasMediaDensities,
   *   format?: string,
   * }>
   */
  public static function cssImageSetDataProvider(): iterable
  {
    yield 'Array' => [
      'expected' => <<<'HTML'
        image-set(url('image.jpg') 1x, url('image@2x.jpg') 2x)
        HTML,
      'entries' => [
        'image.jpg' => '1x',
        'image@2x.jpg' => '2x',
      ],
    ];

    $object = new class implements HasMediaDensities {
      /**
       * @return list<string>
       */
      #[\Override]
      public function getImageFormats(): array
      {
        return ['image/webp', 'image/jpeg'];
      }

      /**
       * @return non-empty-list<array{ density: int, url: string }>
       */
      #[\Override]
      public function getDensitiesForFormat(string $format): array
      {
        return match ($format) {
          'image/webp' => [['density' => 1, 'url' => 'image.webp'], ['density' => 2, 'url' => 'image@2x.webp']],
          'image/jpeg' => [['density' => 1, 'url' => 'image.jpg'], ['density' => 2, 'url' => 'image@2x.jpg']],
          default => throw new \OutOfBoundsException('Invalid format.'),
        };
      }

      #[\Override]
      public function getUrlForFormat(string $format): string
      {
        return $this->getDensitiesForFormat($format)[0]['url'];
      }
    };
    yield 'Densities without format' => [
      'expected' => <<<'HTML'
        image-set(url('image.webp') type('image/webp') 1x, url('image@2x.webp') type('image/webp') 2x, url('image.jpg') type('image/jpeg') 1x, url('image@2x.jpg') type('image/jpeg') 2x)
        HTML,
      'entries' => $object,
    ];

    yield 'Densities with format' => [
      'expected' => <<<'HTML'
        image-set(url('image.jpg') 1x, url('image@2x.jpg') 2x)
        HTML,
      'entries' => $object,
      'format' => 'image/jpeg',
    ];
  }

  /**
   * @param array<string, string> $properties
   */
  #[Test]
  #[DataProvider('cssPropertiesDataProvider')]
  public function cssProperties(string $expected, array $properties): void
  {
    $environment = self::makeCssEnvironment('{{- css_properties(properties) | raw -}}');
    self::assertRenders($expected, $environment, context: ['properties' => $properties]);
  }

  /**
   * @return iterable<string, array{ expected: string, properties: array<string, string> }>
   */
  public static function cssPropertiesDataProvider(): iterable
  {
    yield 'None' => [
      'expected' => '',
      'properties' => [],
    ];

    yield 'Single' => [
      'expected' => 'hello:world',
      'properties' => ['hello' => 'world'],
    ];

    yield 'Multiple' => [
      'expected' => 'hello:world;foo:bar',
      'properties' => ['hello' => 'world', 'foo' => 'bar'],
    ];
  }

  /**
   * @param string|list<string> $values
   */
  #[Test]
  #[DataProvider('cssPropertyDataProvider')]
  public function cssProperty(string $expected, string $name, string|array $values): void
  {
    $environment = self::makeCssEnvironment('{{- css_property(name, values) | raw -}}');
    self::assertRenders($expected, $environment, context: ['name' => $name, 'values' => $values]);
  }

  /**
   * @return iterable<string, array{ expected: string, name: string, values: string|list<string> }>
   */
  public static function cssPropertyDataProvider(): iterable
  {
    yield 'Single' => [
      'expected' => 'hello:world',
      'name' => 'hello',
      'values' => 'world',
    ];

    yield 'Multiple' => [
      'expected' => 'hello:world;hello:again',
      'name' => 'hello',
      'values' => ['world', 'again'],
    ];
  }

  #[Test]
  #[DataProvider('cssUrlDataProvider')]
  public function cssUrl(string $expected, string $url): void
  {
    $environment = self::makeCssEnvironment('{{- css_url(url) | raw -}}');
    self::assertRenders($expected, $environment, context: ['url' => $url]);
  }

  /**
   * @return iterable<int, array{ expected: string, url: string }>
   */
  public static function cssUrlDataProvider(): iterable
  {
    yield [
      'expected' => "url('hello world.jpg')",
      'url' => 'hello world.jpg',
    ];
  }

  /**
   * @param string|array<string, string> $templates
   * @param list<ExtensionInterface> $extensions
   * @param array<string, object> $runtimeResources
   * @param array<string, mixed> $options
   */
  private static function makeCssEnvironment(
    string|array $templates,
    array $extensions = [],
    array $runtimeResources = [],
    array $options = [],
  ): Environment {
    $runtimeResources[CssBuilder::class] ??= new CssBuilder();
    return self::makeEnvironment($templates, [new CssExtension(), ...$extensions], $runtimeResources, $options);
  }
}
