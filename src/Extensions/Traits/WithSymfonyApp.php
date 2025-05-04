<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions\Traits;

use Symfony\Bridge\Twig\AppVariable;

trait WithSymfonyApp
{
  public const CONTEXT_VALUE_SYMFONY_APP = 'app';

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
