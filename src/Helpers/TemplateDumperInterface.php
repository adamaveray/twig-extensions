<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Helpers;

use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextualizedDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

interface TemplateDumperInterface
{
  /**
   * @psalm-suppress PossiblyUnusedReturnValue Required for compatibility with Symfony library method signature.
   */
  public function dumpValue(mixed $value, ?string $label): ?string;
}
