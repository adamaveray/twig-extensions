<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Placeholders;

use Averay\TwigExtensions\Extensions\PlaceholdersExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use Faker\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\TwigFunction;

#[CoversClass(PlaceholdersExtension::class)]
final class PlaceholderStringsTest extends TestCase
{
  private const DEFAULT_LOCALE = 'en_GB';

  #[DataProvider('generatorDataProvider')]
  public function testGenerator(string $parameters): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- get_class(placeholder_generator($parameters)) -}}
      TWIG
      ,
      [new PlaceholdersExtension(self::DEFAULT_LOCALE)],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertRenders(Generator::class, $environment, message: 'The correct generator instance should be loaded.');
  }

  public static function generatorDataProvider(): iterable
  {
    yield 'Default locale' => [''];
    yield 'Custom locale' => ['locale: "ja_JP"'];
  }

  #[DataProvider('wordsDataProvider')]
  public function testWords(string $expectedPattern, string $parameters): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- placeholder_words($parameters) -}}
      TWIG
      ,
      [new PlaceholdersExtension(self::DEFAULT_LOCALE)],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertMatchesRegularExpression(
      $expectedPattern,
      $environment->render('template'),
      message: 'The correct placeholder data should be generated.',
    );
  }

  public static function wordsDataProvider(): iterable
  {
    yield 'Single' => [
      'expectedPattern' => '~^\w+$~iu',
      'parameters' => '1',
    ];

    yield 'Multiple' => [
      'expectedPattern' => '~^\w+( \w+){3}$~iu',
      'parameters' => '4',
    ];
  }

  #[DataProvider('sentencesDataProvider')]
  public function testSentences(string $expectedPattern, string $parameters): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- placeholder_sentences($parameters) -}}
      TWIG
      ,
      [new PlaceholdersExtension(self::DEFAULT_LOCALE)],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertMatchesRegularExpression(
      $expectedPattern,
      $environment->render('template'),
      message: 'The correct placeholder data should be generated.',
    );
  }

  public static function sentencesDataProvider(): iterable
  {
    yield 'Single' => [
      'expectedPattern' => '~^\w[\w ]+\.$~iu',
      'parameters' => '1',
    ];

    yield 'Multiple' => [
      'expectedPattern' => '~^\w[\w ]+\.( \w[\w ]+\.){3}$~iu',
      'parameters' => '4',
    ];
  }

  #[DataProvider('paragraphsDataProvider')]
  public function testParagraphs(string $expectedPattern, string $parameters): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- placeholder_paragraphs($parameters) -}}
      TWIG
      ,
      [new PlaceholdersExtension(self::DEFAULT_LOCALE)],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertMatchesRegularExpression(
      $expectedPattern,
      $environment->render('template'),
      message: 'The correct placeholder data should be generated.',
    );
  }

  public static function paragraphsDataProvider(): iterable
  {
    yield 'Single' => [
      'expectedPattern' => '~^\w[\w ]+\.( \w[\w ]+\.)*$~iu',
      'parameters' => '1',
    ];

    yield 'Multiple' => [
      'expectedPattern' => '~^\w[\w ]+\.( \w[\w ]+\.)*(\\n\\n\w[\w ]+\.( \w[\w ]+\.)*){3}$~iu',
      'parameters' => '4',
    ];
  }

  #[DataProvider('textDataProvider')]
  public function testText(string $expectedPattern, string $parameters): void
  {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- placeholder_text($parameters) -}}
      TWIG
      ,
      [new PlaceholdersExtension(self::DEFAULT_LOCALE)],
    );
    $environment->addFunction(new TwigFunction('get_class', \get_class(...)));

    self::assertMatchesRegularExpression(
      $expectedPattern,
      $environment->render('template'),
      message: 'The correct placeholder data should be generated.',
    );
  }

  public static function textDataProvider(): iterable
  {
    // Must have a minimum of 5 characters
    yield 'Multiple' => [
      'expectedPattern' => '~^[a-z]{1,4}\.$~iu',
      'parameters' => '5',
    ];
  }
}
