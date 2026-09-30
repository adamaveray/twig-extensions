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
   * @param list<string> $searchedPaths Template paths that were searched.
   */
  public function __construct(
    public readonly string $componentName,
    public readonly string $className,
    public readonly array $searchedPaths,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null,
  ) {
    if ($message === '') {
      $message = \sprintf('No template found for component "%s" (%s).', $componentName, $className);
      if ($searchedPaths !== []) {
        $message .= \sprintf(' Searched in %s.', self::formatPaths($searchedPaths));
      }
    }

    parent::__construct($message, $code, $previous);
  }

  /**
   * @param list<string> $paths
   */
  private static function formatPaths(array $paths): string
  {
    return \implode(', ', \array_map(static fn(string $path): string => '"' . $path . '"', $paths));
  }
}
