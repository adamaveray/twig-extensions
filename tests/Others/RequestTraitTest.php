<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Others;

use Averay\TwigExtensions\Extensions\Traits\WithRequest;
use Averay\TwigExtensions\Extensions\Traits\WithSymfonyApp;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use League\Uri\Contracts\UriInterface as LeagueUriInterface;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Psr\Http\Message\UriInterface as PsrUriInterface;
use Symfony\Bridge\Twig\AppVariable as SymfonyAppVariable;
use Symfony\Component\HttpFoundation as SymfonyHttp;

#[CoversTrait(WithRequest::class)]
#[CoversTrait(WithSymfonyApp::class)]
final class RequestTraitTest extends TestCase
{
  /**
   * @param array<string, mixed> $context
   */
  #[DataProvider('requestInferenceDataProvider')]
  public function testRequestInference(mixed $expected, array $context): void
  {
    $instance = self::makeTraitInstance();
    self::assertSame($expected, $instance->getRequest($context), 'The request should be inferred correctly.');
  }

  public static function requestInferenceDataProvider(): iterable
  {
    $psrRequest = self::createStub(PsrServerRequestInterface::class);

    $symfonyRequest = new SymfonyHttp\Request();

    $symfonyApp = new SymfonyAppVariable();
    $symfonyApp->setRequestStack(new SymfonyHttp\RequestStack([$symfonyRequest]));

    yield 'None' => [
      'expected' => null,
      'context' => [],
    ];

    yield 'Context Symfony app' => [
      'expected' => $symfonyRequest,
      'context' => ['app' => $symfonyApp],
    ];

    yield 'Context Symfony request' => [
      'expected' => $symfonyRequest,
      'context' => ['request' => $symfonyRequest],
    ];

    yield 'Context PSR request' => [
      'expected' => $psrRequest,
      'context' => ['request' => $psrRequest],
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  #[DataProvider('requestUriInferenceDataProvider')]
  public function testRequestUriInference(string $expected, array $context): void
  {
    $instance = self::makeTraitInstance();
    self::assertSame($expected, $instance->getRequestUri($context), 'The request URI should be inferred correctly.');
  }

  public static function requestUriInferenceDataProvider(): iterable
  {
    $uri = '/example/page/';

    $psrUri = self::createStub(PsrUriInterface::class);
    $psrUri->method('__toString')->willReturn($uri);

    $leagueUri = self::createStub(LeagueUriInterface::class);
    $leagueUri->method('__toString')->willReturn($uri);

    $psrRequest = self::createStub(PsrServerRequestInterface::class);
    $psrRequest->method('getUri')->willReturn($psrUri);

    $symfonyRequest = new SymfonyHttp\Request(server: ['REQUEST_URI' => $uri]);

    $symfonyApp = new SymfonyAppVariable();
    $symfonyApp->setRequestStack(new SymfonyHttp\RequestStack([$symfonyRequest]));

    yield 'Context Symfony app' => [
      'expected' => $uri,
      'context' => ['app' => $symfonyApp],
    ];

    yield 'Context Symfony request' => [
      'expected' => $uri,
      'context' => ['request' => $symfonyRequest],
    ];

    yield 'Context PSR request' => [
      'expected' => $uri,
      'context' => ['request' => $psrRequest],
    ];

    yield 'Context PSR URI' => [
      'expected' => $uri,
      'context' => ['request_uri' => $psrUri],
    ];

    yield 'Context League URI' => [
      'expected' => $uri,
      'context' => ['request_uri' => $leagueUri],
    ];

    yield 'Context string URI' => [
      'expected' => $uri,
      'context' => ['request_uri' => $uri],
    ];
  }

  public function testRequestUriInferenceFailsWhenNoApp(): void
  {
    $instance = self::makeTraitInstance();
    $this->expectException(\RuntimeException::class);
    $instance->getRequestUri([]);
  }

  public function testRequestUriInferenceFailsWhenIncompatibleApp(): void
  {
    $instance = self::makeTraitInstance();
    $this->expectException(\RuntimeException::class);
    $instance->getRequestUri(['app' => new \stdClass()]);
  }

  private static function makeTraitInstance(): object
  {
    return new class {
      use WithRequest;
      use WithSymfonyApp;

      public function getRequest(array $context): mixed
      {
        return self::inferRequest($context);
      }

      public function getRequestUri(array $context): string
      {
        return self::inferRequestUri($context);
      }
    };
  }
}
