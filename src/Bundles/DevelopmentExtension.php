<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Averay\TwigExtensions\Extensions;
use Averay\TwigExtensions\Helpers\TemplateDumperInterface;
use Symfony\Bridge\Twig\Extension as SymfonyExtensions;
use Symfony\Component\Stopwatch\Stopwatch;
use Twig\Profiler\Profile;

final class DevelopmentExtension extends AbstractBundle
{
  public function __construct(
    ?Stopwatch $stopwatch,
    ?Profile $profile = null,
    ?TemplateDumperInterface $templateDumper = null,
    ?Stopwatch $profileStopwatch = null,
  ) {
    $this->extensions = [
      new Extensions\DumpExtension($templateDumper),
      new Extensions\PlaceholdersExtension(),
      new SymfonyExtensions\StopwatchExtension($stopwatch, $profile !== null),
    ];

    if ($profile !== null) {
      $this->extensions[] = new SymfonyExtensions\ProfilerExtension($profile, $profileStopwatch);
    }
  }
}
