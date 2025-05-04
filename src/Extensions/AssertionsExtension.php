<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Extensions;

use Averay\TwigExtensions\TokenParsers\AssertTokenParser;
use Averay\TwigExtensions\TokenParsers\ErrorTokenParser;
use Twig\Extension\AbstractExtension;

final class AssertionsExtension extends AbstractExtension
{
  #[\Override]
  public function getTokenParsers(): array
  {
    return [new AssertTokenParser(), new ErrorTokenParser()];
  }
}
