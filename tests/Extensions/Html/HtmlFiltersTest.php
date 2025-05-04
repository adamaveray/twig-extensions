<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Html;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\HtmlExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\MimeTypeGuesserInterface;
use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;

#[CoversClass(HtmlExtension::class)]
final class HtmlFiltersTest extends TestCase
{
  #[DataProvider('wrapWordsDataProvider')]
  public function testWrapWords(string $expected, string $string, string $wrappingTag): void
  {
    $environment = self::makeHtmlEnvironment('{{- string | wrap_words(wrapping_tag) -}}');
    self::assertRenders($expected, $environment, context: ['string' => $string, 'wrapping_tag' => $wrappingTag]);
  }

  public static function wrapWordsDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'string' => '',
      'wrappingTag' => '<span>',
    ];

    yield 'Single word' => [
      'expected' => '<span>Hello</span>',
      'string' => 'Hello',
      'wrappingTag' => '<span>',
    ];

    yield 'Multiple words' => [
      'expected' => '<span>Hello</span> <span>world.</span>',
      'string' => 'Hello world.',
      'wrappingTag' => '<span>',
    ];
  }

  #[DataProvider('wrapParagraphsDataProvider')]
  public function testWrapParagraphs(string $expected, string $string, ?string $wrappingTag = null): void
  {
    $environment = self::makeHtmlEnvironment(
      '{{- string | wrap_paragraphs' . ($wrappingTag === null ? '' : '(wrapping_tag)') . ' -}}',
    );
    self::assertRenders($expected, $environment, context: ['string' => $string, 'wrapping_tag' => $wrappingTag]);
  }

  public static function wrapParagraphsDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'string' => '',
    ];

    yield 'Single paragraph' => [
      'expected' => '<p>Hello world.</p>',
      'string' => 'Hello world.',
    ];

    yield 'Multiple paragraphs' => [
      'expected' => '<p>Hello world.</p><p>Second paragraph.</p>',
      'string' => <<<'TXT'
      Hello world.

      Second paragraph.
      TXT
    ,
    ];

    yield 'Custom tag' => [
      'expected' => '<div class="wrapper">Hello world.</div><div class="wrapper">Second paragraph.</div>',
      'string' => <<<'TXT'
      Hello world.

      Second paragraph.
      TXT
      ,
      'wrappingTag' => '<div class="wrapper">',
    ];
  }

  #[DataProvider('addClassDataProvider')]
  public function testAddClass(string $expected, string $classList, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- class_list | add_class(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment, context: ['class_list' => $classList]);
  }

  public static function addClassDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'classList' => '',
      'parameters' => '',
    ];

    yield 'No extras' => [
      'expected' => 'hello-world',
      'classList' => 'hello-world',
      'parameters' => '',
    ];

    yield 'One extra' => [
      'expected' => 'hello-world foo-bar',
      'classList' => 'hello-world',
      'parameters' => '"foo-bar"',
    ];

    yield 'Multiple extras' => [
      'expected' => 'hello-world foo-bar abc-123',
      'classList' => 'hello-world',
      'parameters' => '"foo-bar", "abc-123"',
    ];
  }

  #[DataProvider('mapHtmlIdsDataProvider')]
  public function testMapHtmlIds(string $expected, string $html, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- html | map_html_ids(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment, context: ['html' => $html]);
  }

  public static function mapHtmlIdsDataProvider(): iterable
  {
    $transformer = '(id) => "new-" ~ id ~ "-value"';
    yield 'Empty' => [
      'expected' => '',
      'html' => '',
      'parameters' => $transformer,
    ];

    yield 'No IDs' => [
      'expected' => <<<'HTML'
      <div>
        <h1>Test heading.</h1>
        <p>Test paragraph.</p>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div>
        <h1>Test heading.</h1>
        <p>Test paragraph.</p>
      </div>
      HTML
      ,
      'parameters' => $transformer,
    ];

    yield 'With IDs' => [
      'expected' => <<<'HTML'
      <div id="new-intro-value" class="section" aria-labelledby="new-section-title-value">
        <h1 id="new-section-title-value">Test heading.</h1>
        <p id="new-section-paragraph-value" class="lede">Test paragraph.</p>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div id="intro" class="section" aria-labelledby="section-title">
        <h1 id="section-title">Test heading.</h1>
        <p id="section-paragraph" class="lede">Test paragraph.</p>
      </div>
      HTML
      ,
      'parameters' => $transformer,
    ];

    yield 'With IDs & custom attributes' => [
      'expected' => <<<'HTML'
      <div id="new-intro-value" class="section" aria-labelledby="new-section-title-value">
        <h1 id="new-section-title-value">Test heading.</h1>
        <p id="new-section-paragraph-value" class="lede">Test paragraph.</p>
        <button data-target="new-intro-value">Test button.</button>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div id="intro" class="section" aria-labelledby="section-title">
        <h1 id="section-title">Test heading.</h1>
        <p id="section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'parameters' => $transformer . ', additional_attributes: ["data-target"]',
    ];
  }

  #[DataProvider('prefixHtmlIdsDataProvider')]
  public function testPrefixHtmlIds(string $expected, string $html, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- html | prefix_html_ids(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment, context: ['html' => $html]);
  }

  public static function prefixHtmlIdsDataProvider(): iterable
  {
    $prefix = '"prefixed"';
    yield 'Empty' => [
      'expected' => '',
      'html' => '',
      'parameters' => $prefix,
    ];

    yield 'No IDs' => [
      'expected' => <<<'HTML'
      <div>
        <h1>Test heading.</h1>
        <p>Test paragraph.</p>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div>
        <h1>Test heading.</h1>
        <p>Test paragraph.</p>
      </div>
      HTML
      ,
      'parameters' => $prefix,
    ];

    yield 'With IDs' => [
      'expected' => <<<'HTML'
      <div id="prefixed-intro" class="section" aria-labelledby="prefixed-section-title">
        <h1 id="prefixed-section-title">Test heading.</h1>
        <p id="prefixed-section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div id="intro" class="section" aria-labelledby="section-title">
        <h1 id="section-title">Test heading.</h1>
        <p id="section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'parameters' => $prefix,
    ];

    yield 'With IDs & custom separator' => [
      'expected' => <<<'HTML'
      <div id="prefixed__intro" class="section" aria-labelledby="prefixed__section-title">
        <h1 id="prefixed__section-title">Test heading.</h1>
        <p id="prefixed__section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div id="intro" class="section" aria-labelledby="section-title">
        <h1 id="section-title">Test heading.</h1>
        <p id="section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'parameters' => $prefix . ', separator: "__"',
    ];

    yield 'With IDs & custom attributes' => [
      'expected' => <<<'HTML'
      <div id="prefixed-intro" class="section" aria-labelledby="prefixed-section-title">
        <h1 id="prefixed-section-title">Test heading.</h1>
        <p id="prefixed-section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="prefixed-intro">Test button.</button>
      </div>
      HTML
      ,
      'html' => <<<'HTML'
      <div id="intro" class="section" aria-labelledby="section-title">
        <h1 id="section-title">Test heading.</h1>
        <p id="section-paragraph" class="lede">Test paragraph.</p>
        <button data-target="intro">Test button.</button>
      </div>
      HTML
      ,
      'parameters' => $prefix . ', additional_attributes: ["data-target"]',
    ];
  }

  #[DataProvider('dataUriDataProvider')]
  public function testDataUri(
    string $expected,
    string $data,
    string $parameters,
    ?string $inferredMimeType = null,
  ): void {
    $mimeTypesGuesser = $this->createMock(MimeTypeGuesserInterface::class);
    if ($inferredMimeType === null) {
      $mimeTypesGuesser->expects($this->never())->method('guessMimeType');
    } else {
      $mimeTypesGuesser
        ->expects($this->once())
        ->method('guessMimeType')
        ->willReturnCallback(static function (string $path) use ($data, $inferredMimeType): string {
          self::assertEquals(
            $data,
            \file_get_contents($path),
            'The data contents should be written to a file for inference.',
          );
          return $inferredMimeType;
        });
    }
    $runtimeResources = [HtmlBuilder::class => new HtmlBuilder($mimeTypesGuesser)];
    $environment = self::makeHtmlEnvironment(
      '{{- data | data_uri(' . $parameters . ') -}}',
      runtimeResources: $runtimeResources,
    );
    self::assertRenders($expected, $environment, context: ['data' => $data]);
  }

  public static function dataUriDataProvider(): iterable
  {
    yield 'Preset text' => [
      'expected' => 'data:text/plain,Hello%20world.',
      'data' => 'Hello world.',
      'parameters' => 'mime: "text/plain"',
    ];

    yield 'Preset binary' => [
      'expected' => 'data:unknown/test;base64,' . base64_encode('Hello world.'),
      'data' => 'Hello world.',
      'parameters' => 'mime: "unknown/test"',
    ];

    yield 'Inferred type' => [
      'expected' => 'data:foo/bar;base64,' . base64_encode('Hello world.'),
      'data' => 'Hello world.',
      'parameters' => '',
      'inferredMimeType' => 'foo/bar',
    ];

    yield 'Custom parameters' => [
      'expected' => 'data:unknown/test;foo=bar;base64,' . base64_encode('Hello world.'),
      'data' => 'Hello world.',
      'parameters' => 'mime: "unknown/test", parameters: { foo: "bar" }',
    ];
  }

  /**
   * @param string|array<string, string> $templates
   * @param list<ExtensionInterface> $extensions
   * @param array<string, object> $runtimeResources
   */
  private static function makeHtmlEnvironment(
    string|array $templates,
    array $extensions = [],
    array $runtimeResources = [],
    array $options = [],
  ): Environment {
    $runtimeResources[HtmlBuilder::class] ??= new HtmlBuilder(new MimeTypes());
    return self::makeEnvironment($templates, [new HtmlExtension(), ...$extensions], $runtimeResources, $options);
  }
}
