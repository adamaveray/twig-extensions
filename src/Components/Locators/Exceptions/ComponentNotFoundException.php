<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Locators\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

/**
 * @api
 */
final class ComponentNotFoundException extends \OutOfBoundsException implements NotFoundExceptionInterface
{
  public function __construct(
    public readonly string $component,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null,
  ) {
    if ($message === '') {
      $message = \sprintf('Unknown component "%s".', $component);
    }

    parent::__construct($message, $code, $previous);
  }
}
