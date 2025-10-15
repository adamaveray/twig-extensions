<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Extensions\Traits\WithLocale;
use Averay\TwigExtensions\Values\FileSizeSystem;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\TwigFilter;

final class UnitsExtension extends AbstractExtension
{
  use WithLocale;

  private const array SI_PREFIXES = ['', 'k', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y', 'R', 'Q'];

  public function __construct(string|TranslatorInterface|null $translatorOrLocale = null)
  {
    $this->setLocaleProvider($translatorOrLocale);
  }

  #[\Override]
  public function getFilters(): array
  {
    return [
      new TwigFilter('format_si_amount', $this->filterSiAmount(...), [
        'needs_environment' => true,
        'needs_context' => true,
      ]),
      new TwigFilter('format_bytes', $this->filterBytes(...), ['needs_environment' => true, 'needs_context' => true]),
    ];
  }

  /**
   * @param array<string, mixed> $context
   * @param array<string, mixed> $attrs
   */
  private function formatNumber(
    Environment $environment,
    array $context,
    int|float $number,
    array $attrs,
    ?string $locale,
  ): string {
    $intlExtension = $environment->getExtension(IntlExtension::class);
    $locale ??= $this->inferLocale($context);
    return $intlExtension->formatNumber($number, $attrs, locale: $locale);
  }

  /**
   * Formats an amount with SI prefixes in a human-readable format.
   *
   * @param array<string, mixed> $context
   * @param int|non-empty-array<string, non-negative-int> $precision
   * @param float $prefix_step_percentage
   * @param array<string, mixed> $number_format_attrs
   * @see calculateSiAmount Documentation on the $precision and $prefix_step_percentage parameters.
   */
  private function filterSiAmount(
    Environment $environment,
    array $context,
    int|float $amount,
    string $unit,
    int|array $precision = ['' => 0, 'M' => 2],
    float $prefix_step_percentage = 0.95,
    ?\RoundingMode $rounding_mode = null,
    array $number_format_attrs = [],
    ?string $locale = null,
  ): string {
    ['amount' => $amount, 'prefix' => $prefix] = self::calculateSiAmount(
      amount: $amount,
      precision: $precision,
      prefixStepPercentage: $prefix_step_percentage,
      roundingMode: $rounding_mode ?? \RoundingMode::HalfAwayFromZero,
    );
    $formattedAmount = $this->formatNumber($environment, $context, $amount, $number_format_attrs, $locale);
    return $formattedAmount . $prefix . $unit;
  }

  /**
   * Formats a file size in a human-readable format.
   *
   * @param array<string, mixed> $context
   * @param int|non-empty-array<string, non-negative-int> $precision
   * @param float $prefix_step_percentage
   * @param array<string, mixed> $number_format_attrs
   * @see calculateSiAmount Documentation on the $precision and $prefix_step_percentage parameters.
   */
  private function filterBytes(
    Environment $environment,
    array $context,
    int|float $bytes,
    FileSizeSystem|string $system = 'decimal',
    int|array $precision = ['' => 0, 'M' => 2],
    float $prefix_step_percentage = 0.95,
    ?\RoundingMode $rounding_mode = null,
    array $number_format_attrs = [],
    ?string $locale = null,
  ): string {
    if (\is_string($system)) {
      $system = $system === 'binary' ? FileSizeSystem::BinaryJedec : FileSizeSystem::from($system);
    }
    ['amount' => $amount, 'prefix' => $prefix] = self::calculateSiAmount(
      amount: $bytes,
      step: $system->getMultiple(),
      precision: $precision,
      prefixStepPercentage: $prefix_step_percentage,
      roundingMode: $rounding_mode ?? \RoundingMode::HalfAwayFromZero,
    );
    $formattedAmount = $this->formatNumber($environment, $context, $amount, $number_format_attrs, $locale);
    return $formattedAmount . $prefix . $system->getInfix($prefix) . 'B';
  }

  /**
   * Validates parameters for SI amount calculation.
   *
   * @psalm-assert non-empty-array<string, non-negative-int> $precision
   * @psalm-assert non-negative-int $prefixStepPercentage
   * @throws \InvalidArgumentException
   */
  private static function validateCalculateAmountParameters(
    float $step,
    array $precision,
    float $prefixStepPercentage,
  ): void {
    if ($step <= 0) {
      throw new \InvalidArgumentException('Step must be a positive number.');
    }

    if (empty($precision)) {
      throw new \InvalidArgumentException('Precision array cannot be empty.');
    }
    foreach ($precision as $prefix => $decimals) {
      if (!\is_int($decimals) || $decimals < 0) {
        throw new \InvalidArgumentException(
          \sprintf(
            'Precision must be a non-negative integer, got %s for prefix "%s".',
            \get_debug_type($decimals),
            $prefix,
          ),
        );
      }
    }

    if ($prefixStepPercentage <= 0 || $prefixStepPercentage > 1) {
      throw new \InvalidArgumentException('Prefix step percentage must be greater than 0 and at most 1.');
    }
  }

  /**
   * @param int|non-empty-array<string, non-negative-int> $precision The number of decimal places to display. An array mapping SI prefixes to numbers of decimals can be used, from which the first matching amount will be used (e.g. `['' => 0, 'M' => 2, 'P' => 4]` would use 0 for '' and 'k', 2 for 'M', 'G' and 'T', and 4 for everything from 'P' onwards). A fixed number is equivalent to `['' => 0]`.
   * @param positive-int|float $step The value for each SI prefix level (e.g. 1000 for standard SI, 1024 for binary).
   * @param float $prefixStepPercentage The percentage of an SI prefix level at which to move to the next level (e.g. 0.95 will display a 950B amount in kB). Must be greater than 0 and at most 1.
   * @return array{ amount: float, prefix: string }
   * @note Amounts exceeding the largest supported SI prefix (see SI_PREFIXES constant) will remain in that prefix.
   */
  private static function calculateSiAmount(
    int|float $amount,
    int|float $step = 1000,
    int|array $precision = ['' => 0, 'M' => 2],
    float $prefixStepPercentage = 0.95,
    \RoundingMode $roundingMode = \RoundingMode::HalfAwayFromZero,
  ): array {
    $amount = (float) $amount;
    $step = (float) $step;
    if (\is_int($precision)) {
      $precision = ['' => $precision];
    }

    self::validateCalculateAmountParameters($step, $precision, $prefixStepPercentage);

    $inverted = false;
    if ($amount < 0) {
      $amount = \abs($amount);
      $inverted = true;
    }

    $prefixIndex = 0;
    $matchedPrecision = \current($precision);
    $matchedPrefix = self::getSiPrefix($prefixIndex);
    $maximumPrefixIndex = \count(self::SI_PREFIXES) - 1;
    while ($amount / $step >= $prefixStepPercentage && $prefixIndex < $maximumPrefixIndex) {
      $amount /= $step;
      $prefixIndex += 1;
      $matchedPrefix = self::getSiPrefix($prefixIndex);
      $matchedPrecision = $precision[$matchedPrefix] ?? $matchedPrecision;
    }

    $finalAmount = \round($amount, $matchedPrecision, $roundingMode);
    if ($inverted) {
      $finalAmount = -$finalAmount;
    }
    return ['amount' => $finalAmount, 'prefix' => $matchedPrefix];
  }

  private static function getSiPrefix(int $index): string
  {
    return self::SI_PREFIXES[$index] ?? throw new \OutOfBoundsException('Invalid SI prefix index.');
  }
}
