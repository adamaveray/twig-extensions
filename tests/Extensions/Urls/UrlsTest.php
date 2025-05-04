<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Urls;

use Averay\TwigExtensions\Extensions\UrlsExtension;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(UrlsExtension::class)]
final class UrlsTest extends TestCase
{
  #[DataProvider('urlPartDataProvider')]
  public function testUrlPart(string $expected, string $url, string $parameters): void
  {
    self::assertFilterRenders(
      \htmlspecialchars($expected, \ENT_QUOTES | \ENT_HTML5),
      $url,
      'url_part(' . $parameters . ')',
      extensions: [new UrlsExtension()],
      message: 'The URL part should be retrieved correctly.',
    );
  }

  public static function urlPartDataProvider(): iterable
  {
    $exampleUrl = 'https://testUsername:testPassword@www.example.com:123/test/path/?hello=world#test-fragment';
    yield 'Scheme' => [
      'expected' => 'https',
      'url' => $exampleUrl,
      'parameters' => '"scheme"',
    ];

    yield 'Host' => [
      'expected' => 'www.example.com',
      'url' => $exampleUrl,
      'parameters' => '"host"',
    ];

    yield 'Host Without Prefix' => [
      'expected' => 'example.com',
      'url' => $exampleUrl,
      'parameters' => '"host", strip_www: true',
    ];

    yield 'Host With Different Prefix' => [
      'expected' => 'other.example.com',
      'url' => 'https://other.example.com/',
      'parameters' => '"host", strip_www: true',
    ];

    yield 'Port' => [
      'expected' => '123',
      'url' => $exampleUrl,
      'parameters' => '"port"',
    ];

    yield 'Username' => [
      'expected' => 'testUsername',
      'url' => $exampleUrl,
      'parameters' => '"username"',
    ];

    yield 'Password' => [
      'expected' => 'testPassword',
      'url' => $exampleUrl,
      'parameters' => '"password"',
    ];

    yield 'Path' => [
      'expected' => '/test/path/',
      'url' => $exampleUrl,
      'parameters' => '"path"',
    ];

    yield 'Query' => [
      'expected' => 'hello=world',
      'url' => $exampleUrl,
      'parameters' => '"query"',
    ];

    yield 'Fragment' => [
      'expected' => 'test-fragment',
      'url' => $exampleUrl,
      'parameters' => '"fragment"',
    ];
  }

  #[DataProvider('appendQueryParamsDataProvider')]
  public function testAppendQueryParams(string $expected, string $url, string $parameters): void
  {
    self::assertFilterRenders(
      \htmlspecialchars($expected, \ENT_QUOTES | \ENT_HTML5),
      $url,
      'append_query_params(' . $parameters . ')',
      extensions: [new UrlsExtension()],
      message: 'The URL part should be retrieved correctly.',
    );
  }

  public static function appendQueryParamsDataProvider(): iterable
  {
    yield 'No existing query' => [
      'expected' => 'https://www.example.com/?hello=world',
      'url' => 'https://www.example.com/',
      'parameters' => '{ hello: "world" }',
    ];

    yield 'With existing query' => [
      'expected' => 'https://www.example.com/?existing&hello=world',
      'url' => 'https://www.example.com/?existing',
      'parameters' => '{ hello: "world" }',
    ];

    yield 'No existing query with hash' => [
      'expected' => 'https://www.example.com/?hello=world#location',
      'url' => 'https://www.example.com/#location',
      'parameters' => '{ hello: "world" }',
    ];

    yield 'With existing query and hash' => [
      'expected' => 'https://www.example.com/?existing&hello=world#location',
      'url' => 'https://www.example.com/?existing#location',
      'parameters' => '{ hello: "world" }',
    ];

    yield 'Multiple values' => [
      'expected' => 'https://www.example.com/?hello=world&foo=bar&abc=123',
      'url' => 'https://www.example.com/',
      'parameters' => '{ hello: "world", foo: "bar", abc: 123 }',
    ];

    yield 'No values' => [
      'expected' => 'https://www.example.com/',
      'url' => 'https://www.example.com/',
      'parameters' => '{}',
    ];

    yield 'URL encoding' => [
      'expected' => 'https://www.example.com/?hello+world=foo+bar',
      'url' => 'https://www.example.com/',
      'parameters' => '{ "hello world": "foo bar" }',
    ];

    yield 'Raw URL encoding' => [
      'expected' => 'https://www.example.com/?hello%20world=foo%20bar',
      'url' => 'https://www.example.com/',
      'parameters' => '{ "hello world": "foo bar" }, raw: true',
    ];
  }
}
