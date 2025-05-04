<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Helpers;

use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\ContextProvider\SourceContextProvider;
use Symfony\Component\VarDumper\Dumper\ContextualizedDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;

final class TemplateDumper extends ContextualizedDumper implements TemplateDumperInterface
{
  private readonly VarCloner $cloner;

  public function __construct(?VarCloner $cloner = null)
  {
    parent::__construct(new HtmlDumper(), [new SourceContextProvider()]);

    if ($cloner === null) {
      $cloner = new VarCloner();
      /** @psalm-suppress InvalidArgument Psalm cannot infer generated callable syntax. */
      $cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);
    }
    $this->cloner = $cloner;
  }

  #[\Override]
  public function dumpValue(mixed $value, ?string $label): ?string
  {
    $data = $this->cloner->cloneVar($value);
    if ($label !== null) {
      $data = $data->withContext(['label' => $label]);
    }
    return $this->dump($data);
  }
}
