<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Basic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\FromMethod;

#[AsTwigComponent(template: new FromMethod('getTemplate'))]
final class MethodTemplate
{
  public function getTemplate(): string
  {
    return 'custom/method.html.twig';
  }
}
