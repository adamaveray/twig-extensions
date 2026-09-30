<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions\Traits;

use Symfony\Bridge\Twig\AppVariable;

/**
 * @internal
 */
trait WithSymfonyApp
{
  final public const string CONTEXT_VALUE_SYMFONY_APP = 'app';

  /**
   * @param array<string, mixed> $context
   */
  final protected static function getAppVariableFromContext(array $context): ?AppVariable
  {
    /** @psalm-suppress MixedAssignment */
    $value = $context[self::CONTEXT_VALUE_SYMFONY_APP] ?? null;
    if ($value instanceof AppVariable) {
      return $value;
    }
    return null;
  }
}
