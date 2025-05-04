<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class LogicExtension extends AbstractExtension
{
  #[\Override]
  public function getFilters(): array
  {
    return [new TwigFilter('match', self::filterMatch(...))];
  }

  /**
   * @template TChoices of array
   * @param key-of<TChoices> $value
   * @param TChoices $cases
   * @param bool $strict
   * @return ($strict is true ? value-of<TChoices> : value-of<TChoices>|null)
   */
  private static function filterMatch(mixed $value, array $cases, bool $strict = true): mixed
  {
    if (!\array_key_exists($value, $cases)) {
      if (!$strict) {
        return null;
      }
      throw new \OutOfBoundsException('Value not found in choices.');
    }
    /** @psalm-suppress MixedReturnStatement */
    return $cases[$value];
  }
}
