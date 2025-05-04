<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Resources;

use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
  /**
   * @param callable():void $fn
   * @param (callable(\Throwable $exception):bool)|null $test
   * @param (callable(\Throwable|null $previous):bool)|null $testPrevious
   */
  final protected static function assertThrows(
    callable $fn,
    ?callable $test = null,
    ?callable $testPrevious = null,
    string $message = '',
  ): void {
    try {
      $fn();
    } catch (\Throwable $exception) {
      if ($test === null) {
        self::assertInstanceOf(\Throwable::class, $exception, $message);
      } else {
        self::assertTrue($test($exception), $message);
      }
      if ($testPrevious !== null) {
        self::assertTrue($testPrevious($exception->getPrevious()), $message);
      }
      return;
    }
    self::fail($message);
  }

  final protected static function assertRenders(
    string $expected,
    Environment $environment,
    string $template = 'template',
    array $context = [],
    string $message = '',
  ): void {
    $output = $environment->render($template, $context);
    self::assertSame($expected, $output, $message);
  }

  final protected static function assertFilterRenders(
    string $expected,
    mixed $value,
    string $filters,
    array $extensions = [],
    array $context = [],
    string $message = '',
  ): void {
    $environment = self::makeEnvironment(
      <<<TWIG
      {{- test_value | $filters -}}
      TWIG
      ,
      $extensions,
    );

    self::assertRenders($expected, $environment, context: ['test_value' => $value] + $context, message: $message);
  }

  final protected static function assertMatchesTest(
    bool $expected,
    string $statement,
    array $extensions,
    array $context,
    string $message = '',
  ): void {
    $environment = self::makeEnvironment(
      <<<TWIG
      {%- if $statement -%}
        yes
      {%- else -%}
        no
      {%- endif -%}
      TWIG
      ,
      $extensions,
    );

    $result = $environment->render('template', $context);
    self::assertEquals($expected ? 'yes' : 'no', $result, $message);
  }

  /**
   * @param array<string, string> $templates
   * @param list<ExtensionInterface> $extensions
   * @param array<string, object> $runtimeResources
   */
  final protected static function makeEnvironment(
    array|string $templates = [],
    array $extensions = [],
    array $runtimeResources = [],
    array $options = [],
  ): Environment {
    $loader = new \Twig\Loader\ArrayLoader(\is_array($templates) ? $templates : ['template' => $templates]);
    $environment = new Environment(
      $loader,
      $options + [
        'strict_variables' => true,
        'debug' => true,
        'use_yield' => true,
      ],
    );
    foreach ($extensions as $extension) {
      $environment->addExtension($extension);
    }
    $environment->addRuntimeLoader(new ArrayRuntimeLoader($runtimeResources));
    return $environment;
  }
}
