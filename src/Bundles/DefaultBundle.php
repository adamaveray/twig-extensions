<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Bundles;

use Averay\TwigExtensions\Extensions;
use Averay\TwigExtensions\Helpers\TemplateDumperInterface;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extra\Cache\CacheExtension;
use Twig\Profiler\Profile;

final class DefaultBundle extends AbstractBundle
{
  public function __construct(private readonly ?TranslatorInterface $translator)
  {
    $this->extensions = [
      // Twig-provided
      new CacheExtension(),

      // Custom
      new Extensions\ArraysExtension(),
      new Extensions\AssertionsExtension(),
      new Extensions\CssExtension(),
      new Extensions\DatesExtension(),
      new Extensions\HtmlExtension(),
      new Extensions\LogicExtension(),
      new Extensions\StringsExtension($this->translator),
      new Extensions\UrlsExtension(),
      new Extensions\ValuesExtension(),
    ];
  }

  /**
   * @see DevelopmentExtension
   */
  public function withDevelopment(
    ?Stopwatch $stopwatch,
    ?Profile $profile = null,
    ?TemplateDumperInterface $templateDumper = null,
    ?Stopwatch $profileStopwatch = null,
  ): static {
    return $this->withBundle(new DevelopmentExtension($stopwatch, $profile, $templateDumper, $profileStopwatch));
  }

  /**
   * @see IntlBundle
   */
  public function withIntl(?\IntlDateFormatter $dateFormatter = null, ?\NumberFormatter $numberFormatter = null): static
  {
    return $this->withBundle(
      new IntlBundle(
        $this->translator ?? throw new \LogicException('Translator must be set.'),
        $dateFormatter,
        $numberFormatter,
      ),
    );
  }

  /**
   * @see SymfonyBundle
   */
  public function withSymfony(?AppVariable $appVariable, ?Packages $assetPackages): static
  {
    return $this->withBundle(new SymfonyBundle($appVariable, $this->translator, $assetPackages));
  }
}
