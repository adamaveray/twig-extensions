<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Components\Definitions;

/**
 * @internal
 *
 * @psalm-type ComponentInternalConfig = array<string, mixed>
 */
final readonly class ComponentDefinition
{
  /**
   * @param string $name The name used to render the component (e.g. `Media:Gallery`).
   * @param class-string $className
   * @param string|null $template The template to render, which may only be null if $templateMethod is set.
   * @param string|null $templateMethod A component method returning the template to render.
   * @param bool $exposePublicProps Whether to expose all public properties as template variables.
   * @param string $attributesVar The template variable name for the component's HTML attributes.
   * @param list<string> $preMountMethods Method names to call before mounting, in order.
   * @param list<string> $mountMethods Method names to call to mount the component, in order.
   * @param list<string> $postMountMethods Method names to call after mounting, in order.
   */
  public function __construct(
    public string $name,
    public string $className,
    public ?string $template,
    public ?string $templateMethod,
    public bool $exposePublicProps,
    public string $attributesVar,
    public array $preMountMethods,
    public array $mountMethods,
    public array $postMountMethods,
  ) {}

  /**
   * Converts the definition to the configuration format used by Symfony UX's component factory.
   *
   * @return ComponentInternalConfig
   */
  public function toSymfonyUxConfig(): array
  {
    $config = [
      'key' => $this->name,
      'class' => $this->className,
      'service_id' => $this->className,
      'template' => $this->template ?? '', // Replaced at render time when a template method is set, but must still be a string
      'expose_public_props' => $this->exposePublicProps,
      'attributes_var' => $this->attributesVar,
      'pre_mount' => $this->preMountMethods,
      'mount' => $this->mountMethods,
      'post_mount' => $this->postMountMethods,
    ];
    if ($this->templateMethod !== null) {
      $config['template_from_method'] = $this->templateMethod;
    }
    return $config;
  }
}
