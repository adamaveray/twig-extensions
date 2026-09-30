# Twig Components

[Symfony UX Twig Components](https://symfony.com/bundles/ux-twig-component/current/index.html) can be used without the Symfony framework via `TwigComponentsConfigurator`.

## Installation

Install the additional packages:

```sh
composer require symfony/ux-twig-component symfony/cache symfony/finder
```

## Setup

Use the configurator to configure Twig:

```php
use Averay\TwigExtensions\Components\TwigComponentsConfigurator;

$configurator = new TwigComponentsConfigurator(
  namespaces: ['App\\Components\\' => '@views/components'],
  componentContainer: $componentContainer, // Must return a new instance on every `get()` call.
);
$configurator->configure($twig);
```

## Usage

Components can be rendered using Symfony UX syntax:

```twig
<twig:Greeting name="User" />

{{ component('Greeting', { name: 'User' }) }}

{% component Greeting with { name: 'User' } %}{% endcomponent %}
```

Component classes in the specified PSR-4 namespaces will be auto-discovered, and anonymous component templates will be loaded via the configured Twig loaders.

## Caching

Auto-discovery scans the filesystem and inspects component classes, so should be cached in production:

```php
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$configurator = new TwigComponentsConfigurator(
  // ...
  cache: new FilesystemAdapter(directory: $cacheDirectory), // Any AdapterInterface instance
);
```

Cache clearing must be performed manually. Caches should be pre-warmed for increased performance:

```php
$configurator->warmCache($twig);
```

## Limitations

- [Live Components](https://symfony.com/bundles/ux-live-component/current/index.html) are not supported.
- Symfony UX's per-namespace component name prefixes are not supported.
