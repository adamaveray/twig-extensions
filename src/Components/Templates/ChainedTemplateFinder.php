<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Templates;

use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;

/**
 * Finds anonymous component templates using the first template finder to find a match.
 *
 * @internal
 */
final readonly class ChainedTemplateFinder implements ComponentTemplateFinderInterface
{
  /**
   * @param list<ComponentTemplateFinderInterface> $finders Template finders in order of precedence.
   */
  public function __construct(
    private array $finders,
  ) {}

  #[\Override]
  public function findAnonymousComponentTemplate(string $name): ?string
  {
    foreach ($this->finders as $finder) {
      $template = $finder->findAnonymousComponentTemplate($name);
      if ($template !== null) {
        return $template;
      }
    }
    return null;
  }
}
