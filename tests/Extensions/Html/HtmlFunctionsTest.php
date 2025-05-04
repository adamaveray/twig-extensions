<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Html;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\HtmlExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\HttpFoundation as SymfonyHttp;
use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;

#[CoversClass(HtmlExtension::class)]
final class HtmlFunctionsTest extends TestCase
{
  #[DataProvider('attrsDataProvider')]
  public function testAttrs(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- attrs(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function attrsDataProvider(): iterable
  {
    yield 'None' => [
      'expected' => '',
      'parameters' => '',
    ];

    yield 'Single set, single item' => [
      'expected' => 'hello="world"',
      'parameters' => '{ hello: "world" }',
    ];

    yield 'Single set, multiple items' => [
      'expected' => 'hello="world" foo="bar"',
      'parameters' => '{ hello: "world", foo: "bar" }',
    ];

    yield 'Multiple sets, single item' => [
      'expected' => 'hello="world" foo="bar"',
      'parameters' => '{ hello: "world" }, { foo: "bar" }',
    ];

    yield 'Multiple sets, multiple items' => [
      'expected' => 'hello="world" foo="baz" aria-pressed="true" disabled',
      'parameters' => '{ hello: "world", foo: "bar", "aria-pressed": true }, { foo: "baz", disabled: true }',
    ];
  }

  #[DataProvider('classesDataProvider')]
  public function testClasses(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- classes(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function classesDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'parameters' => '{}',
    ];

    yield 'Values' => [
      'expected' => 'hello-world foo-bar',
      'parameters' => '{ "hello-world": true, "disabled-class": false, "foo-bar": true }',
    ];
  }

  #[DataProvider('stylesheetDataProvider')]
  public function testStylesheet(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- stylesheet(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function stylesheetDataProvider(): iterable
  {
    yield 'Basic' => [
      'expected' => <<<'HTML'
      <link rel="stylesheet" href="stylesheet.css"/>
      HTML
      ,
      'parameters' => '"stylesheet.css"',
    ];

    yield 'With attributes' => [
      'expected' => <<<'HTML'
      <link rel="stylesheet" href="stylesheet.css" media="example-media" integrity="example-hash" crossorigin="example-crossorigin"/>
      HTML
      ,
      'parameters' =>
        '"stylesheet.css", media: "example-media", integrity: "example-hash", crossorigin: "example-crossorigin"',
    ];
  }

  #[DataProvider('scriptDataProvider')]
  public function testScript(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- script(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function scriptDataProvider(): iterable
  {
    yield 'Basic' => [
      'expected' => <<<'HTML'
      <script src="script.js"></script>
      HTML
      ,
      'parameters' => '"script.js"',
    ];

    yield 'With attributes' => [
      'expected' => <<<'HTML'
      <script src="script.js" type="module" async integrity="example-hash" crossorigin="example-crossorigin"></script>
      HTML
      ,
      'parameters' =>
        '"script.js", type: "module", async: true, integrity: "example-hash", crossorigin: "example-crossorigin"',
    ];
  }

  #[DataProvider('preloadLinksDataProvider')]
  public function testPreloadLinks(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- preload_links(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function preloadLinksDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'parameters' => '[]',
    ];

    yield 'Preloads only' => [
      'expected' => \implode('', [
        '<link rel="preload" href="script.js" as="script"/>',
        '<link rel="preload" href="style.css" as="style"/>',
      ]),
      'parameters' => <<<'TWIG'
      preloads: { script: "script.js", style: ["style.css"] }
      TWIG
    ,
    ];

    yield 'Preconnects only' => [
      'expected' => \implode('', [
        '<link rel="preconnect" href="example.com"/>',
        '<link rel="preconnect" href="example.org"/>',
      ]),
      'parameters' => <<<'TWIG'
      {},
      preconnect_hosts: ["example.com", "example.org"]
      TWIG
    ,
    ];

    yield 'Both' => [
      'expected' => \implode('', [
        '<link rel="preload" href="script.js" as="script"/>',
        '<link rel="preload" href="style.css" as="style"/>',
        '<link rel="preconnect" href="example.com"/>',
        '<link rel="preconnect" href="example.org"/>',
      ]),
      'parameters' => <<<'TWIG'
      preloads: { script: "script.js", style: ["style.css"] },
      preconnect_hosts: ["example.com", "example.org"]
      TWIG
    ,
    ];

    yield 'With attributes' => [
      'expected' => \implode('', [
        '<link rel="preload" href="script.js" as="script" integrity="example-hash-1" crossorigin="example-crossorigin-1"/>',
        '<link rel="preload" href="style.css" as="style" integrity="example-hash-2" crossorigin="example-crossorigin-2"/>',
        '<link rel="preconnect" href="example.com"/>',
        '<link rel="preconnect" href="example.org"/>',
      ]),
      'parameters' => <<<'TWIG'
      preloads: {
        script: [
          { url: "script.js", integrity: "example-hash-1", crossorigin: "example-crossorigin-1" },
        ],
        style: [
          { url: "style.css", integrity: "example-hash-2", crossorigin: "example-crossorigin-2" },
        ],
      },
      preconnect_hosts: ["example.com", "example.org"]
      TWIG
    ,
    ];
  }

  #[DataProvider('srcsetDataProvider')]
  public function testSrcset(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- srcset(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  public static function srcsetDataProvider(): iterable
  {
    yield 'Empty' => [
      'expected' => '',
      'parameters' => '{}',
    ];

    yield 'Single' => [
      'expected' => 'image.jpg 1x',
      'parameters' => <<<'TWIG'
      { "image.jpg": "1x" }
      TWIG
    ,
    ];

    yield 'Densities' => [
      'expected' => 'image@3x.jpg 3x, image@2x.jpg 2x, image.jpg 1x',
      'parameters' => <<<'TWIG'
      {
        "image@3x.jpg": "3x",
        "image@2x.jpg": "2x",
        "image.jpg": "1x",
      }
      TWIG
    ,
    ];

    yield 'Widths' => [
      'expected' => 'large.jpg 500px, small.jpg 200px',
      'parameters' => <<<'TWIG'
      {
        "large.jpg": "500px",
        "small.jpg": "200px"
      }
      TWIG
    ,
    ];
  }

  #[DataProvider('currentUrlAttrDataProvider')]
  public function testCurrentUrlAttr(string $expected, string $parameters, array $context = []): void
  {
    $environment = self::makeHtmlEnvironment('{{- current_url_attr(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment, context: $context);
  }

  public static function currentUrlAttrDataProvider(): iterable
  {
    yield 'Matching explicit' => [
      'expected' => 'aria-current="page"',
      'parameters' => '"/", current_url: "/"',
    ];

    yield 'Not matching explicit' => [
      'expected' => '',
      'parameters' => '"/", current_url: "/other/"',
    ];

    yield 'Alternate value' => [
      'expected' => 'aria-current="step"',
      'parameters' => '"/", current_url: "/", value: "step"',
    ];

    $symfonyRequest = new SymfonyHttp\Request(server: ['REQUEST_URI' => '/example/page/']);
    $symfonyApp = new SymfonyAppVariable();
    $symfonyApp->setRequestStack(new SymfonyHttp\RequestStack([$symfonyRequest]));
    yield 'Matching from Symfony app' => [
      'expected' => 'aria-current="page"',
      'parameters' => '"/example/page/"',
      'context' => [HtmlExtension::CONTEXT_VALUE_SYMFONY_APP => $symfonyApp],
    ];

    yield 'Not matching from Symfony app' => [
      'expected' => '',
      'parameters' => '"/other/path/"',
      'context' => [HtmlExtension::CONTEXT_VALUE_SYMFONY_APP => $symfonyApp],
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
