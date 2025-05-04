<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\Nodes\Tests\SameDateAsTest;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

final class DatesExtension extends AbstractExtension
{
  #[\Override]
  public function getTests(): array
  {
    return \array_map(
      static fn(string $name): TwigTest => new TwigTest($name, null, [
        'node_class' => SameDateAsTest::class,
        'one_mandatory_argument' => true,
      ]),
      ['same_date as', 'same_year as', 'same_month as', 'same_day as', 'same_time as'],
    );
  }
}
