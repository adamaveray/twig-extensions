<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\HtmlBuilder\Html;
use Averay\TwigExtensions\Nodes\Tests\InstanceOfTest;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

final class ValuesExtension extends AbstractExtension
{
  #[\Override]
  public function getFilters(): array
  {
    return [new TwigFilter('js_value', Html\escapeJsValue(...), ['is_safe' => ['html']])];
  }

  #[\Override]
  public function getTests(): array
  {
    return [
      new TwigTest('instance of', null, ['node_class' => InstanceOfTest::class, 'one_mandatory_argument' => true]),

      // Primitive types
      new TwigTest('array', \is_array(...)),
      new TwigTest('bool', \is_bool(...)),
      new TwigTest('callable', \is_callable(...)),
      new TwigTest('countable', \is_countable(...)),
      new TwigTest('float', \is_float(...)),
      new TwigTest('int', \is_int(...)),
      new TwigTest('numeric', \is_numeric(...)),
      new TwigTest('object', \is_object(...)),
      new TwigTest('resource', \is_resource(...)),
      new TwigTest('scalar', \is_scalar(...)),
      new TwigTest('string', \is_string(...)),
    ];
  }
}
