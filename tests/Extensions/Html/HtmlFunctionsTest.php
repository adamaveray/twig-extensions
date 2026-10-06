<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Html;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\HtmlExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Averay\TwigExtensions\Values\FileSizeSystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\HttpFoundation as SymfonyHttp;
use Symfony\Component\Mime\MimeTypes;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\ExtensionInterface;
use Twig\Extra\Html\HtmlExtension as HtmlExtraExtension;

/**
 * @internal
 */
#[CoversClass(HtmlExtension::class)]
final class HtmlFunctionsTest extends TestCase
{
  #[Test]
  #[DataProvider('attributesDataProvider')]
  public function attributes(string $expected, string $template): void
  {
    $environment = self::makeHtmlEnvironment($template, [new HtmlExtraExtension()]);
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, template: string }>
   */
  public static function attributesDataProvider(): iterable
  {
    yield 'None' => [
      'expected' => '<div>',
      'template' => '<div {{- attributes() -}}>',
    ];

    yield 'Single set' => [
      'expected' => '<div id="a" title="&lt;b&gt;">',
      'template' => '<div {{- attributes({ id: "a", title: "<b>" }) -}}>',
    ];

    yield 'Multiple sets' => [
      'expected' => '<div id="b" title="c">',
      'template' => '<div {{- attributes({ id: "a" }, false ? { title: "x" }, { id: "b", title: "c" }) -}}>',
    ];

    yield 'Chained' => [
      'expected' => '<div class="base extra" title="b">',
      'template' => <<<'TWIG'
        <div {{- attributes({ id: "a", class: "extra" }).defaults({ id: "x", class: "base" }).add({ title: "b" }).without("id") -}}>
        TWIG,
    ];

    yield 'Variable' => [
      'expected' => '<div id="a">',
      'template' => '{%- set attrs = attributes({ id: "a" }) -%}<div {{- attrs -}}>',
    ];

    yield 'Other escaping strategies' => [
      'expected' => '\\u0020id\\u003D\\u0022a\\u0022',
      'template' => '{%- autoescape "js" -%}{{ attributes({ id: "a" }) }}{%- endautoescape -%}',
    ];

    yield 'Explicitly escaped' => [
      'expected' => ' id=&quot;a&quot;',
      'template' => '{{- attributes({ id: "a" }) | e -}}',
    ];

    yield 'Passed to html_attr' => [
      'expected' => '<div id="a" title="b">',
      'template' => '<div {{ html_attr(attributes({ id: "a" }), { title: "b" }) }}>',
    ];

    yield 'Counted' => [
      'expected' => '2',
      'template' => '{{- attributes({ id: "a", title: "b" }) | length -}}',
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('attributesMatchHtmlAttrDataProvider')]
  public function attributesMatchHtmlAttr(string $attributes, string $htmlAttrArguments, array $context = []): void
  {
    $environment = self::makeHtmlEnvironment([
      'attributes' => '{{- ' . $attributes . ' -}}',
      'html_attr' => '{{- html_attr(' . $htmlAttrArguments . ') -}}',
    ], [new HtmlExtraExtension()]);

    $htmlAttrOutput = $environment->render('html_attr', $context);
    self::assertRenders(
      $htmlAttrOutput === '' ? '' : ' ' . $htmlAttrOutput,
      $environment,
      template: 'attributes',
      context: $context,
      message: 'The attributes should render the same as the equivalent html_attr call (aside from the leading space).',
    );
  }

  /**
   * Intentional differences from `html_attr` are excluded, as they are covered by their own tests: the leading space, `defaults` and `add` joining strings for `class`, `data-controller` and `data-action`, later `null` values removing attributes rather than throwing, and invalid attribute names throwing rather than being escaped.
   *
   * @return iterable<string, array{
   *   attributes: string,
   *   htmlAttrArguments: string,
   *   context?: array<string, mixed>,
   * }>
   */
  public static function attributesMatchHtmlAttrDataProvider(): iterable
  {
    $stringable = new class implements \Stringable {
      #[\Override]
      public function __toString(): string
      {
        return '<stringable>';
      }
    };

    $argumentCases = [
      'None' => '',
      'Empty set' => '{}',
      'Scalars' => '{ id: "a", tabindex: 0, step: 1.5 }',
      'Escaped values' => '{ title: "<a> & \\"b\\"" }',
      'Booleans' => '{ disabled: true, hidden: false, inert: null }',
      'ARIA' => '{ "aria-hidden": true, "aria-expanded": false }',
      'Data' => '{ "data-a": true, "data-b": false, "data-c": [1, 2], "data-d": { x: 1 } }',
      'Class list' => '{ class: ["a", "b"] }',
      'Style mapping' => '{ style: { color: "red", "font-size": "1em" } }',
      'Token list type' => '{ rel: ["a", "b"] | html_attr_type("cst") }',
      'Later sets win' => '{ id: "a", title: "b" }, { id: "c" }',
      'Key order' => '{ a: "1", b: "2" }, { c: "3", a: "4" }',
      'Class strings replaced' => '{ class: "a" }, { class: "b" }',
      'Lists joined' => '{ class: ["a"] }, { class: ["b"] }',
      'Keyed lists overwritten' => '{ style: { color: "red" } }, { style: { color: "blue", margin: 0 } }',
      'Style types merged' => '{ style: { color: "red" } | html_attr_type("style") }, { style: { margin: 0 } }',
      'Token list types merged' => '{ rel: "a" | html_attr_type }, { rel: ["b"] }',
      'Conditional sets' => '{ id: "a" }, false ? { id: "b" }, null, false',
      'Later false' => '{ hidden: true }, { hidden: false }',
    ];
    foreach ($argumentCases as $name => $arguments) {
      yield $name => [
        'attributes' => 'attributes(' . $arguments . ')',
        'htmlAttrArguments' => $arguments,
      ];
    }

    yield 'Enum' => [
      'attributes' => 'attributes({ value: enum })',
      'htmlAttrArguments' => '{ value: enum }',
      'context' => ['enum' => FileSizeSystem::Decimal],
    ];

    yield 'Stringable' => [
      'attributes' => 'attributes({ title: stringable })',
      'htmlAttrArguments' => '{ title: stringable }',
      'context' => ['stringable' => $stringable],
    ];

    yield 'Add' => [
      'attributes' => 'attributes({ id: "a", title: "b" }).add({ id: "c", rel: ["d"] })',
      'htmlAttrArguments' => '{ id: "a", title: "b" }, { id: "c", rel: ["d"] }',
    ];

    yield 'Add, lists' => [
      'attributes' => 'attributes({ class: ["a"] }).add({ class: ["b"] })',
      'htmlAttrArguments' => '{ class: ["a"] }, { class: ["b"] }',
    ];

    yield 'Defaults' => [
      'attributes' => 'attributes({ id: "a", title: "b" }).defaults({ id: "c", rel: ["d"] })',
      'htmlAttrArguments' => '{ id: "c", rel: ["d"] }, { id: "a", title: "b" }',
    ];

    yield 'Defaults, lists' => [
      'attributes' => 'attributes({ class: ["a"] }).defaults({ class: ["b"] })',
      'htmlAttrArguments' => '{ class: ["b"] }, { class: ["a"] }',
    ];
  }

  #[Test]
  #[DataProvider('attributesErrorsMatchHtmlAttrDataProvider')]
  public function attributesErrorsMatchHtmlAttr(string $arguments): void
  {
    $environment = self::makeHtmlEnvironment([
      'attributes' => '{{- attributes(' . $arguments . ') -}}',
      'html_attr' => '{{- html_attr(' . $arguments . ') -}}',
    ], [new HtmlExtraExtension()]);

    foreach (['html_attr', 'attributes'] as $template) {
      self::assertThrows(
        static function () use ($environment, $template): void {
          $environment->render($template);
        },
        test: static fn(\Throwable $exception): bool => $exception instanceof RuntimeError,
        message: \sprintf('The "%s" function should reject the arguments.', $template),
      );
    }
  }

  /**
   * @return iterable<string, array{ arguments: string }>
   */
  public static function attributesErrorsMatchHtmlAttrDataProvider(): iterable
  {
    yield 'Non-empty string set' => ['arguments' => '"id=a"'];
    yield 'String and list' => ['arguments' => '{ class: "a" }, { class: ["b"] }'];
    yield 'List and string' => ['arguments' => '{ class: ["a"] }, { class: "b" }'];
  }

  #[Test]
  public function attributesInvalidNames(): void
  {
    $environment = self::makeHtmlEnvironment('{{- attributes({ "a b": "c" }) -}}', [new HtmlExtraExtension()]);
    self::assertThrows(
      static function () use ($environment): void {
        $environment->render('template');
      },
      test: static fn(\Throwable $exception): bool => $exception instanceof RuntimeError,
      message: 'Invalid attribute names should be rejected rather than escaped.',
    );
  }

  #[Test]
  #[DataProvider('classesDataProvider')]
  public function classes(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- classes(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string }>
   */
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

  #[Test]
  #[DataProvider('stylesheetDataProvider')]
  public function stylesheet(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- stylesheet(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string }>
   */
  public static function stylesheetDataProvider(): iterable
  {
    yield 'Basic' => [
      'expected' => <<<'HTML'
        <link rel="stylesheet" href="stylesheet.css"/>
        HTML,
      'parameters' => '"stylesheet.css"',
    ];

    yield 'With attributes' => [
      'expected' => <<<'HTML'
        <link rel="stylesheet" href="stylesheet.css" media="example-media" integrity="example-hash" crossorigin="example-crossorigin"/>
        HTML,
      'parameters' => '"stylesheet.css", media: "example-media", integrity: "example-hash", crossorigin: "example-crossorigin"',
    ];
  }

  #[Test]
  #[DataProvider('scriptDataProvider')]
  public function script(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- script(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string }>
   */
  public static function scriptDataProvider(): iterable
  {
    yield 'Basic' => [
      'expected' => <<<'HTML'
        <script src="script.js"></script>
        HTML,
      'parameters' => '"script.js"',
    ];

    yield 'With attributes' => [
      'expected' => <<<'HTML'
        <script src="script.js" type="module" async="" integrity="example-hash" crossorigin="example-crossorigin"></script>
        HTML,
      'parameters' => '"script.js", type: "module", async: true, integrity: "example-hash", crossorigin: "example-crossorigin"',
    ];
  }

  #[Test]
  #[DataProvider('preloadLinksDataProvider')]
  public function preloadLinks(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- preload_links(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string }>
   */
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
        TWIG,
    ];

    yield 'Preconnects only' => [
      'expected' => \implode('', [
        '<link rel="preconnect" href="example.com"/>',
        '<link rel="preconnect" href="example.org"/>',
      ]),
      'parameters' => <<<'TWIG'
        {},
        preconnect_hosts: ["example.com", "example.org"]
        TWIG,
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
        TWIG,
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
        TWIG,
    ];
  }

  #[Test]
  #[DataProvider('srcsetDataProvider')]
  public function srcset(string $expected, string $parameters): void
  {
    $environment = self::makeHtmlEnvironment('{{- srcset(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string }>
   */
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
        TWIG,
    ];

    yield 'Densities' => [
      'expected' => 'image@3x.jpg 3x, image@2x.jpg 2x, image.jpg 1x',
      'parameters' => <<<'TWIG'
        {
          "image@3x.jpg": "3x",
          "image@2x.jpg": "2x",
          "image.jpg": "1x",
        }
        TWIG,
    ];

    yield 'Widths' => [
      'expected' => 'large.jpg 500px, small.jpg 200px',
      'parameters' => <<<'TWIG'
        {
          "large.jpg": "500px",
          "small.jpg": "200px"
        }
        TWIG,
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  #[Test]
  #[DataProvider('currentUrlAttrDataProvider')]
  public function currentUrlAttr(string $expected, string $parameters, array $context = []): void
  {
    $environment = self::makeHtmlEnvironment('{{- current_url_attr(' . $parameters . ') -}}');
    self::assertRenders($expected, $environment, context: $context);
  }

  /**
   * @return iterable<string, array{ expected: string, parameters: string, context?: array<string, mixed> }>
   */
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
   * @param array<string, mixed> $options
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
