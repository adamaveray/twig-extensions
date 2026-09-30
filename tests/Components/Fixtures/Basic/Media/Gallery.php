<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Fixtures\Basic\Media;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PostMount;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent(exposePublicProps: false, attributesVar: 'attrs')]
final class Gallery
{
  #[PreMount(priority: 1)]
  public function preMountLow(): void {}

  #[PreMount(priority: 10)]
  public function preMountHigh(): void {}

  public function mount(): void {}

  #[PostMount]
  public function postMount(): void {}

  public function unrelated(): void {}
}
