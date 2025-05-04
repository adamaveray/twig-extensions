<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions\Traits;

use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

trait WithRequest
{
  public const CONTEXT_VALUE_REQUEST = 'request';
  public const CONTEXT_VALUE_REQUEST_URI = 'request_uri';

  final protected static function inferRequest(array $context): PsrServerRequestInterface|SymfonyRequest|null
  {
    // Symfony app
    $symfonyRequest = self::getAppVariableFromContext($context)?->getRequest();
    if ($symfonyRequest !== null) {
      return $symfonyRequest;
    }

    // Request instance
    /** @psalm-suppress MixedAssignment */
    $request = $context[self::CONTEXT_VALUE_REQUEST] ?? null;
    if ($request instanceof PsrServerRequestInterface || $request instanceof SymfonyRequest) {
      return $request;
    }

    return null;
  }

  /**
   * @param array<string, mixed> $context
   */
  final protected static function inferRequestUri(array $context): string
  {
    // Request instance
    $request = self::inferRequest($context);
    if ($request instanceof PsrServerRequestInterface) {
      return (string) $request->getUri();
    }
    if ($request instanceof SymfonyRequest) {
      return $request->getRequestUri();
    }

    // Direct URI value
    /** @psalm-suppress MixedAssignment */
    $uri = $context[self::CONTEXT_VALUE_REQUEST_URI] ?? null;
    if (\is_string($uri) || $uri instanceof \Stringable) {
      return (string) $uri;
    }

    throw new \RuntimeException('The request URI must be provided.');
  }
}
