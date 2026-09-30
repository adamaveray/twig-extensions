<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Duplicates;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Same')]
final class First {}
