<?php

declare(strict_types=1);

namespace Averay\TwigExtensions\Tests\Components\Locators;

use Averay\TwigExtensions\Components\Locators\ComponentLocator;
use Averay\TwigExtensions\Components\Locators\Exceptions\ComponentNotFoundException;
use Averay\TwigExtensions\Tests\Components\Fixtures;
use Averay\TwigExtensions\Tests\Resources\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
#[CoversClass(ComponentLocator::class)]
#[CoversClass(ComponentNotFoundException::class)]
final class ComponentLocatorTest extends TestCase
{
  #[Test]
  public function getsComponentsFromContainerByClassName(): void
  {
    $component = new Fixtures\Basic\Button();

    $container = $this->createMock(ContainerInterface::class);
    $container
      ->expects($this->once())
      ->method('get')
      ->with(Fixtures\Basic\Button::class)
      ->willReturn($component);

    $locator = new ComponentLocator($container, ['Button' => Fixtures\Basic\Button::class]);

    self::assertSame($component, $locator->get('Button'), 'The component should be retrieved from the container.');
  }

  #[Test]
  public function hasComponents(): void
  {
    $locator = new ComponentLocator(self::createStub(ContainerInterface::class), [
      'Button' => Fixtures\Basic\Button::class,
    ]);

    self::assertTrue($locator->has('Button'), 'A known component should be available.');
    self::assertFalse($locator->has('Unknown'), 'An unknown component should not be available.');
  }

  #[Test]
  public function throwsForUnknownComponents(): void
  {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->never())->method('get');

    $locator = new ComponentLocator($container, ['Button' => Fixtures\Basic\Button::class]);

    self::assertThrows(
      static function () use ($locator): void {
        $locator->get('Unknown');
      },
      test: static fn(\Throwable $exception): bool => (
        $exception instanceof ComponentNotFoundException
        && $exception->component === 'Unknown'
        && $exception->getMessage() === 'Unknown component "Unknown".'
      ),
      message: 'An unknown component should be rejected.',
    );
  }
}
