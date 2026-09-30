<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Basic;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent(template: 'custom/explicit.html.twig')]
final class ExplicitTemplate {}
