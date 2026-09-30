<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Rendering;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Greeting
{
  public string $name = 'Default User';
}
