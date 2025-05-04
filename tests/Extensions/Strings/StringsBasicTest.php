<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Strings;

use Averay\TwigExtensions\Extensions\StringsExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Output\RenderedContentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\String\ByteString;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TwigFunction;
use function Symfony\Component\String\s;

#[CoversClass(StringsExtension::class)]
final class StringsBasicTest extends TestCase
{
  #[DataProvider('stringWrapperFilterDataProvider')]
  public function testStringWrapperFilter(string $expectedClass, string $string, string $filter): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- get_class(string | $filter) -}}
      TWIG
      ,
      [new StringsExtension()],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertRenders($expectedClass, $environment, context: ['string' => $string]);
  }

  public static function stringWrapperFilterDataProvider(): iterable
  {
    yield 'Unicode' => [UnicodeString::class, 'こんにちは', 'u'];
    yield 'Binary' => [ByteString::class, 'hello world', 'b'];
    yield 'Autodetect Unicode' => [\get_class(s(UnicodeString::class)), 'こんにちは', 's'];
    yield 'Autodetect Binary' => [\get_class(s(ByteString::class)), 'hello world', 's'];
  }

  #[DataProvider('usesAppLocaleDataProvider')]
  public function testUsesAppLocale(string $locale, ?string $defaultLocale, array $context): void
  {
    $extension = new StringsExtension($defaultLocale);
    $method = new \ReflectionMethod(StringsExtension::class, 'inferLocale');
    $result = $method->invoke($extension, $context);
    self::assertEquals($locale, $result, 'The locale should be loaded correctly.');
  }

  public static function usesAppLocaleDataProvider(): iterable
  {
    $locale = 'en_AU';

    yield 'Default locale' => [
      'locale' => $locale,
      'defaultLocale' => $locale,
      'context' => [],
    ];

    yield 'Global locale value' => [
      'locale' => $locale,
      'defaultLocale' => null,
      'context' => [
        StringsExtension::CONTEXT_VALUE_LOCALE => $locale,
      ],
    ];

    $localeSwitcher = new LocaleSwitcher('??', []);
    $localeSwitcher->setLocale($locale);
    $app = new SymfonyAppVariable();
    $app->setLocaleSwitcher($localeSwitcher);
    yield 'Global Twig bridge app instance' => [
      'locale' => $locale,
      'defaultLocale' => $locale,
      'context' => [StringsExtension::CONTEXT_VALUE_SYMFONY_APP => $app],
    ];
  }

  public function testMarkdown(): void
  {
    $filters = ['markdown', 'markdown_to_html'];
    $input = 'Markdown.';
    $output = '<p>HTML</p>';

    $loader = $this->createMockMarkdownLoader($input, $output, $filters);

    foreach ($filters as $filter) {
      $environment = self::makeEnvironment('{{- markdown | ' . $filter . ' -}}', [new StringsExtension()]);
      $environment->addRuntimeLoader($loader);

      self::assertEquals(
        $output,
        $environment->render('template', ['markdown' => $input]),
        'The Markdown should be processed correctly.',
      );
    }
  }

  public function testMarkdownIgnoringIndentation(): void
  {
    $filters = ['markdown', 'markdown_to_html'];
    $inputIndented = <<<'TXT'
        Hello world.

            This is a code block.

        This is a paragraph.
    TXT;
    $inputOutdented = <<<'TXT'
    Hello world.

        This is a code block.

    This is a paragraph.
    TXT;
    $output = '<p>HTML</p>';

    $loader = $this->createMockMarkdownLoader($inputOutdented, $output, $filters);

    foreach ($filters as $filter) {
      $environment = self::makeEnvironment('{{- markdown | ' . $filter . '(ignore_indentation: true) -}}', [
        new StringsExtension(),
      ]);
      $environment->addRuntimeLoader($loader);

      self::assertEquals(
        $output,
        $environment->render('template', ['markdown' => $inputIndented]),
        'The Markdown should be processed correctly.',
      );
    }
  }

  #[DataProvider('slugDataProvider')]
  public function testSlug(
    string $expectedSeparator,
    bool $expectedLower,
    bool $expectedUpper,
    string $expectedLocale,
    string $twigParameters,
    string $locale,
  ): void {
    $input = 'Input.';
    $output = 'Output.';

    $outputString = $this->createMock(UnicodeString::class);
    $outputString->expects($this->once())->method('__toString')->willReturn($output);
    if ($expectedUpper) {
      $outputString->expects($this->once())->method('localeUpper')->with($expectedLocale)->willReturnSelf();
    } else {
      $outputString->expects($this->never())->method('localeUpper');
    }
    if ($expectedLower) {
      $outputString->expects($this->once())->method('localeLower')->with($expectedLocale)->willReturnSelf();
    } else {
      $outputString->expects($this->never())->method('localeLower');
    }

    $slugger = $this->createMock(SluggerInterface::class);
    $slugger
      ->expects($this->once())
      ->method('slug')
      ->with($input, $expectedSeparator, $expectedLocale)
      ->willReturn($outputString);

    $loader = $this->createMock(RuntimeLoaderInterface::class);
    $loader->expects($this->once())->method('load')->with(SluggerInterface::class)->willReturn($slugger);

    $environment = self::makeEnvironment(
      <<<TWIG
      {{- string | slug($twigParameters) -}}
      TWIG
      ,
      [new StringsExtension($locale)],
    );
    $environment->addRuntimeLoader($loader);

    self::assertRenders($output, $environment, context: ['string' => $input]);
  }

  public static function slugDataProvider(): iterable
  {
    yield 'Defaults' => [
      'expectedSeparator' => '-', // Default
      'expectedLower' => true, // Default
      'expectedUpper' => false, // Default
      'expectedLocale' => 'zz',
      'twigParameters' => '',
      'locale' => 'zz',
    ];

    yield 'Custom separator' => [
      'expectedSeparator' => '_',
      'expectedLower' => true, // Default
      'expectedUpper' => false, // Default
      'expectedLocale' => 'zz',
      'twigParameters' => 'separator: "_"',
      'locale' => 'zz',
    ];

    yield 'No case adjustment' => [
      'expectedSeparator' => '-', // Default
      'expectedLower' => false,
      'expectedUpper' => false,
      'expectedLocale' => 'zz',
      'twigParameters' => 'case: null',
      'locale' => 'zz',
    ];

    yield 'Uppercased' => [
      'expectedSeparator' => '-', // Default
      'expectedLower' => false,
      'expectedUpper' => true,
      'expectedLocale' => 'zz',
      'twigParameters' => 'case: "upper"',
      'locale' => 'zz',
    ];

    yield 'Custom locale' => [
      'expectedSeparator' => '-', // Default
      'expectedLower' => true, // Default
      'expectedUpper' => false, // Default
      'expectedLocale' => 'en',
      'twigParameters' => 'locale: "en"',
      'locale' => 'zz',
    ];
  }

  #[DataProvider('outdentDataProvider')]
  public function testOutdent(string $expected, string $string): void
  {
    $environment = self::makeEnvironment('{{- string | outdent -}}', [new StringsExtension()]);

    self::assertEquals(
      $expected,
      $environment->render('template', ['string' => $string]),
      'The indentation should be removed correctly.',
    );
  }

  public static function outdentDataProvider(): iterable
  {
    yield 'No indentation' => [
      'expected' => <<<'TXT'
      Hello world.

      Lorem ipsum.
      TXT
      ,
      'string' => <<<'TXT'
      Hello world.

      Lorem ipsum.
      TXT
    ,
    ];

    yield 'Internal indentation only' => [
      'expected' => <<<'TXT'
      Hello world.

        An indented line.

      Lorem ipsum.
      TXT
      ,
      'string' => <<<'TXT'
      Hello world.

        An indented line.

      Lorem ipsum.
      TXT
    ,
    ];

    yield 'Full indentation' => [
      'expected' => <<<'TXT'
      Hello world.

      Lorem ipsum.
      TXT
      ,
      'string' => <<<'TXT'
          Hello world.

          Lorem ipsum.
      TXT
    ,
    ];

    yield 'Full indentation with internal indentation' => [
      'expected' => <<<'TXT'
      Hello world.

        An indented line.

      Lorem ipsum.
      TXT
      ,
      'string' => <<<'TXT'
          Hello world.

            An indented line.

          Lorem ipsum.
      TXT
    ,
    ];
  }

  /**
   * @param list<string> $filters
   */
  private function createMockMarkdownLoader(string $input, string $output, array $filters): RuntimeLoaderInterface
  {
    $renderedContent = $this->createMock(RenderedContentInterface::class);
    $renderedContent
      ->expects($this->exactly(\count($filters)))
      ->method('__toString')
      ->willReturn($output);

    $converter = $this->createMock(ConverterInterface::class);
    $converter
      ->expects($this->exactly(\count($filters)))
      ->method('convert')
      ->with($input)
      ->willReturn($renderedContent);

    $loader = $this->createMock(RuntimeLoaderInterface::class);
    $loader
      ->expects($this->exactly(\count($filters)))
      ->method('load')
      ->with(ConverterInterface::class)
      ->willReturn($converter);

    return $loader;
  }
}
