<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Resources;

use Averay\TwigExtensions\Extensions\Traits\WithRequest;
use Psr\Http\Message\ServerRequestInterface as PsrServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * @internal
 */
final class RequestTraitHost
{
  use WithRequest;

  /**
   * @param array<string, mixed> $context
   */
  public function getRequest(array $context): PsrServerRequestInterface|SymfonyRequest|null
  {
    return self::inferRequest($context);
  }

  /**
   * @param array<string, mixed> $context
   */
  public function getRequestUri(array $context): string
  {
    return self::inferRequestUri($context);
  }
}
