<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\TokenParsers\SetWithFiltersTokenParser;
use Twig\Extension\AbstractExtension;

final class TwigOverridesExtension extends AbstractExtension
{
  #[\Override]
  public function getTokenParsers(): array
  {
    return [new SetWithFiltersTokenParser()];
  }
}
