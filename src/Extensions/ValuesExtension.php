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
      new TwigTest('instance of', null, [
        'node_class' => InstanceOfTest::class,
        'one_mandatory_argument' => true,
        'always_allowed_in_sandbox' => true,
      ]),

      // Primitive types
      new TwigTest('array', \is_array(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('bool', \is_bool(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('callable', \is_callable(...)),
      new TwigTest('countable', \is_countable(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('float', \is_float(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('int', \is_int(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('numeric', \is_numeric(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('object', \is_object(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('resource', \is_resource(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('scalar', \is_scalar(...), ['always_allowed_in_sandbox' => true]),
      new TwigTest('string', \is_string(...), ['always_allowed_in_sandbox' => true]),
    ];
  }
}
