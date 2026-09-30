<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Helpers;

/**
 * @api
 */
interface TemplateDumperInterface
{
  /**
   * @psalm-suppress PossiblyUnusedReturnValue Required for compatibility with Symfony library method signature.
   */
  public function dumpValue(mixed $value, ?string $label): ?string;
}
