<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Discovery\Exceptions;

/**
 * @api
 */
final class TemplateNotFoundException extends \LogicException
{
  /**
   * @param class-string $className
   */
  public function __construct(
    public readonly string $componentName,
    public readonly string $className,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null,
  ) {
    if ($message === '') {
      $message = \sprintf('No template found for component "%s" (%s).', $componentName, $className);
    }

    parent::__construct($message, $code, $previous);
  }
}
