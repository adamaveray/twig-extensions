<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Definitions;

use Averay\TwigExtensions\Components\Definitions\ComponentDefinition;
use Averay\TwigExtensions\Components\Definitions\ComponentRegistry;
use Averay\TwigExtensions\Tests\Components\Fixtures;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversClass(ComponentRegistry::class)]
#[CoversClass(ComponentDefinition::class)]
final class ComponentRegistryTest extends TestCase
{
  #[Test]
  public function getConfig(): void
  {
    self::assertSame(
      [
        'Button' => [
          'key' => 'Button',
          'class' => Fixtures\Basic\Button::class,
          'service_id' => Fixtures\Basic\Button::class,
          'template' => 'components/Button.html.twig',
          'expose_public_props' => true,
          'attributes_var' => 'attributes',
          'pre_mount' => ['preA', 'preB'],
          'mount' => ['mount'],
          'post_mount' => ['post'],
        ],
        'MethodTemplate' => [
          'key' => 'MethodTemplate',
          'class' => Fixtures\Basic\MethodTemplate::class,
          'service_id' => Fixtures\Basic\MethodTemplate::class,
          'template' => '',
          'expose_public_props' => false,
          'attributes_var' => 'attrs',
          'pre_mount' => [],
          'mount' => [],
          'post_mount' => [],
          'template_from_method' => 'getTemplate',
        ],
      ],
      self::makeRegistry()->getConfig(),
      'The definitions should be converted to Symfony UX configurations.',
    );
  }

  #[Test]
  public function getClassNames(): void
  {
    self::assertSame(
      ['Button' => Fixtures\Basic\Button::class, 'MethodTemplate' => Fixtures\Basic\MethodTemplate::class],
      self::makeRegistry()->getClassNames(),
      'The class names should be keyed by component name.',
    );
  }

  #[Test]
  public function getClassMap(): void
  {
    self::assertSame(
      [Fixtures\Basic\Button::class => 'Button', Fixtures\Basic\MethodTemplate::class => 'MethodTemplate'],
      self::makeRegistry()->getClassMap(),
      'The component names should be keyed by class name.',
    );
  }

  private static function makeRegistry(): ComponentRegistry
  {
    return new ComponentRegistry([
      'Button' => new ComponentDefinition(
        name: 'Button',
        className: Fixtures\Basic\Button::class,
        template: 'components/Button.html.twig',
        templateMethod: null,
        exposePublicProps: true,
        attributesVar: 'attributes',
        preMountMethods: ['preA', 'preB'],
        mountMethods: ['mount'],
        postMountMethods: ['post'],
      ),
      'MethodTemplate' => new ComponentDefinition(
        name: 'MethodTemplate',
        className: Fixtures\Basic\MethodTemplate::class,
        template: null,
        templateMethod: 'getTemplate',
        exposePublicProps: false,
        attributesVar: 'attrs',
        preMountMethods: [],
        mountMethods: [],
        postMountMethods: [],
      ),
    ]);
  }
}
