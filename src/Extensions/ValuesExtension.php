<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Nodes\Tests\InstanceOfTest;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

final class ValuesExtension extends AbstractExtension
{
  #[\Override]
  public function getTests(): array
  {
    return [
      new TwigTest('instance of', null, ['node_class' => InstanceOfTest::class, 'one_mandatory_argument' => true]),
    ];
  }
}
