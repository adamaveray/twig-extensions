<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Symfony\Bridge\Twig\AppVariable;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class SymfonyCompatibilityExtension extends AbstractExtension implements GlobalsInterface
{
  public function __construct(private readonly ?AppVariable $appVariable) {}

  /**
   * @return array<string, mixed>
   */
  #[\Override]
  public function getGlobals(): array
  {
    /** @var array<string, mixed> $globals */
    $globals = [];
    if ($this->appVariable !== null) {
      $globals['app'] = $this->appVariable;
    }
    return $globals;
  }
}
