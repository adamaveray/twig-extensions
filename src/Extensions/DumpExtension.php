<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Helpers\TemplateDumper;
use Averay\TwigExtensions\Helpers\TemplateDumperInterface;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TemplateWrapper;
use Twig\TwigFunction;

final class DumpExtension extends AbstractExtension
{
  private readonly TemplateDumperInterface $dumper;

  public function __construct(?TemplateDumperInterface $dumper = null)
  {
    $this->dumper = $dumper ?? new TemplateDumper();
  }

  /**
   * @return list<TwigFunction>
   */
  #[\Override]
  public function getFunctions(): array
  {
    return [
      new TwigFunction('dump', $this->dump(...), [
        'is_safe' => ['html'],
        'needs_context' => true,
        'is_variadic' => true,
      ]),
    ];
  }

  /**
   * @param array<string, mixed> $context
   */
  private function dump(array $context, mixed ...$vars): string
  {
    if (empty($vars)) {
      $vars = ['context' => \array_filter($context, self::isNotTemplate(...))];
    }

    $showLabels = \count($vars) !== 1 || !\array_is_list($vars);

    ob_start();
    /** @psalm-suppress MixedAssignment */
    foreach ($vars as $label => $var) {
      $this->dumper->dumpValue($var, label: $showLabels ? (string) $label : null);
    }
    $result = ob_get_clean();
    \assert(\is_string($result));
    return $result;
  }

  private static function isNotTemplate(mixed $value): bool
  {
    return !($value instanceof Template || $value instanceof TemplateWrapper);
  }
}
