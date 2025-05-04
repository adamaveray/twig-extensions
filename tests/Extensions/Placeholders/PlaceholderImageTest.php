<?php
declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Extensions\Placeholders;

use Averay\HtmlBuilder\Html\HtmlBuilder;
use Averay\TwigExtensions\Extensions\AssertionsExtension;
use Averay\TwigExtensions\Extensions\PlaceholdersExtension;
use Averay\TwigExtensions\Nodes\AssertNode;
use Averay\TwigExtensions\Tests\Extensions\Assertions\AssertTest;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DependsOnClass;
use Symfony\Component\Mime\MimeTypes;
use function Symfony\Component\String\b;

#[CoversClass(PlaceholdersExtension::class)]
#[CoversClass(AssertionsExtension::class)]
#[CoversClass(AssertNode::class)]
final class PlaceholderImageTest extends TestCase
{
  #[DependsOnClass(AssertTest::class)]
  public function testImageUrl(): void
  {
    $width = 500;
    $height = 350;

    $environment = self::makeEnvironment(
      <<<TWIG
      {{- placeholder_image_url(width: width, height: height) -}}
      TWIG
      ,
      extensions: [new AssertionsExtension(), new PlaceholdersExtension(null)],
      runtimeResources: [HtmlBuilder::class => new HtmlBuilder(new MimeTypes())],
    );

    $dataUriPrefix = 'data:image/svg+xml;base64,';
    $result = $environment->render('template', ['width' => $width, 'height' => $height]);
    self::assertStringStartsWith($dataUriPrefix, $result, 'The image should be a valid data URI.');

    $xml = \base64_decode(b($result)->trimPrefix($dataUriPrefix)->toString());
    new \SimpleXMLElement($xml);
  }
}
