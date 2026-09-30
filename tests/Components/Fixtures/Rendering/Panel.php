<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Rendering;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Panel
{
  public string $label = '';
  public string $variant = '';

  public function mount(string $variant): void
  {
    $this->variant = \strtoupper($variant);
  }
}
