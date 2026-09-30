<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Values;

enum FileSizeSystem: string
{
  case Decimal = 'decimal';
  case BinaryIec = 'binary-iec';
  case BinaryJedec = 'binary-jedec';

  /**
   * @return int The number of each unit (e.g. bytes) to use when determining SI prefixes (i.e. the number of values in a "kilo").
   *
   * @internal
   */
  public function getMultiple(): int
  {
    return match ($this) {
      self::Decimal => 1000,
      self::BinaryIec, self::BinaryJedec => 1024,
    };
  }

  /**
   * Provides a string to be inserted between the SI prefix and the unit (e.g. the 'i' in '1MiB', or '' for '1MB').
   *
   * @param string|null $siPrefix The prefix being used.
   *
   * @internal
   */
  public function getInfix(?string $siPrefix = null): string
  {
    return match ($this) {
      self::Decimal => '',
      self::BinaryIec => 'i',
      self::BinaryJedec => \in_array($siPrefix, ['', 'k', 'M', 'G'], true) ? '' : 'i',
    };
  }
}
