<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Locators;

use Averay\TwigExtensions\Components\Locators\Exceptions\ComponentNotFoundException;
use Averay\TwigExtensions\Components\Locators\NullRendererLocator;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * @internal
 */
#[CoversClass(NullRendererLocator::class)]
final class NullRendererLocatorTest extends TestCase
{
  #[Test]
  public function hasNoRenderers(): void
  {
    self::assertFalse(new NullRendererLocator()->has('Button'), 'No renderers should be available.');
  }

  #[Test]
  public function throwsForAllRenderers(): void
  {
    self::assertThrows(
      static function (): void {
        new NullRendererLocator()->get('Button');
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof ComponentNotFoundException
        && $exception->component === 'Button'
        && $exception->getMessage() === 'No custom renderer is available for component "Button".'
      ),
      message: 'Retrieving a renderer should be rejected.',
    );
  }
}
