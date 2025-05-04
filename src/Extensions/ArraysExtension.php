<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Nodes\Tests\ArrayAllOrAnyTest;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

final class ArraysExtension extends AbstractExtension
{
  #[\Override]
  public function getFilters(): array
  {
    return [
      new TwigFilter('append', self::filterAppend(...), ['is_variadic' => true]),
      new TwigFilter('merge_existing', self::filterMergeExisting(...), ['is_variadic' => true]),
      new TwigFilter('omit', self::filterOmit(...)),
      new TwigFilter('pick', self::filterPick(...)),
      new TwigFilter('sort', self::filterSort(...)),
      new TwigFilter('map_entries', self::filterMapEntries(...)),
    ];
  }

  #[\Override]
  public function getTests(): array
  {
    return [
      new TwigTest('all_empty', null, ['node_class' => ArrayAllOrAnyTest::class]),
      new TwigTest('any_empty', null, ['node_class' => ArrayAllOrAnyTest::class]),
    ];
  }

  /**
   * An enhanced version of the inbuilt Twig sort filter, allowing ignoring associations or sorting by either values or keys while remaining compatible with the original.
   *
   * @param callable(mixed, mixed):int $arrow
   */
  private static function filterSort(
    array|\Traversable $array,
    callable|null $arrow = null,
    string $by = 'value',
    bool $preserve_keys = true,
  ): array {
    if ($array instanceof \Traversable) {
      $array = \iterator_to_array($array);
    }

    switch ($by) {
      case 'value':
      case 'values':
        if ($arrow === null) {
          if ($preserve_keys) {
            \asort($array);
          } else {
            \sort($array);
          }
        } else {
          if ($preserve_keys) {
            \uasort($array, $arrow);
          } else {
            \usort($array, $arrow);
          }
        }
        break;

      case 'key':
      case 'keys':
        if (!$preserve_keys) {
          throw new RuntimeError('Key sorting must be associative.');
        }

        if ($arrow === null) {
          \ksort($array);
        } else {
          \uksort($array, $arrow);
        }
        break;

      default:
        throw new RuntimeError(\sprintf('Invalid sort target "%s".', $by));
    }

    return $array;
  }

  private static function filterAppend(array $array, mixed ...$values): array
  {
    return \array_merge($array, $values);
  }

  /**
   * @template T of array
   * @template TOthers of array
   * @param T $target
   * @param TOthers ...$arrays
   * @return T&TOthers
   */
  private static function filterMergeExisting(array $target, array ...$arrays): array
  {
    foreach ($arrays as $array) {
      /** @psalm-suppress MixedAssignment */
      foreach ($array as $key => $value) {
        if (\array_key_exists($key, $target)) {
          $target[$key] = $value;
        }
      }
    }
    /** @var T&TOthers */
    return $target;
  }

  /**
   * @template T of array
   * @param T $target
   * @param list<key-of<T>> $keys
   * @return T
   */
  private static function filterOmit(array $target, array $keys): array
  {
    foreach ($keys as $key) {
      unset($target[$key]);
    }
    return $target;
  }

  /**
   * @template T of array
   * @param T $target
   * @param list<key-of<T>> $keys
   * @return T
   */
  private static function filterPick(array $target, array $keys, bool $strict = true): array
  {
    /** @var T $picked */
    $picked = [];
    foreach ($keys as $key) {
      if (!\array_key_exists($key, $target)) {
        if (!$strict) {
          continue;
        }
        /** @psalm-suppress InvalidCast Psalm knows `$key` is of type `key-of<array<array-key, mixed>>` but apparently can't make the leap to `array-key` which is safe to cast. */
        throw new \OutOfBoundsException(\sprintf('Key "%s" not found.', (string) $key));
      }
      /** @var value-of<T> */
      $picked[$key] = $target[$key];
    }
    /** @var T */
    return $picked;
  }

  /**
   * @template TIn of array
   * @template TOut of array
   * @param TIn $array
   * @param callable(key-of<TIn> $key, value-of<TIn> $value):list{ key-of<TOut>, value-of<TOut> } $callback
   * @return TOut
   * @psalm-suppress MixedArgument,MixedAssignment,InvalidArgument This method is too complicated to keep Psalm happy.
   */
  private static function filterMapEntries(array $array, callable $callback): array
  {
    /** @var TOut $mapped */
    $mapped = [];
    foreach ($array as $key => $value) {
      [$newKey, $newValue] = $callback($key, $value);
      $mapped[$newKey] = $newValue;
    }
    /** @var TOut */
    return $mapped;
  }
}
