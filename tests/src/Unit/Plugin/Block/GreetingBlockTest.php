<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Unit\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\example_starter\Plugin\Block\GreetingBlock;
use Drupal\example_starter\Service\GreeterInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\example_starter\Plugin\Block\GreetingBlock
 *
 * @group example_starter
 */
final class GreetingBlockTest extends UnitTestCase {

  /**
   * The block plugin ID under test.
   */
  private const PLUGIN_ID = 'example_starter_greeting';

  /**
   * A minimal plugin definition; BlockBase reads the 'provider' key.
   */
  private const PLUGIN_DEFINITION = [
    'id' => 'example_starter_greeting',
    'provider' => 'example_starter',
  ];

  /**
   * Verifies create() pulls the greeter service off the container and wires it.
   *
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreateInjectsGreeterFromContainer(): void {
    $greeter = $this->createMock(GreeterInterface::class);
    $greeter->method('greet')->willReturn('Ahoy!');

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(FALSE);

    // The greeting rendered by build() must be the value produced by the very
    // greeter the container returned, proving create() fetched
    // 'example_starter.greeter' and passed it to the constructor.
    self::assertSame('Ahoy!', $this->buildBlock($greeter, $account)->build()['#greeting']);
  }

  /**
   * Verifies build() returns the greeting render array for an anonymous user.
   *
   * @covers ::build
   */
  public function testBuildReturnsGreetingRenderArray(): void {
    $greeter = $this->createMock(GreeterInterface::class);
    $greeter->method('greet')->with('')->willReturn('Hello, Guest!');

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(FALSE);

    $build = $this->buildBlock($greeter, $account)->build();

    self::assertSame('example_starter_greeting', $build['#theme']);
    self::assertSame('Hello, Guest!', $build['#greeting']);
    self::assertSame('', $build['#user_name']);
  }

  /**
   * An authenticated user's display name is passed through to the template.
   *
   * @covers ::build
   */
  public function testBuildIncludesAuthenticatedUserName(): void {
    $greeter = $this->createMock(GreeterInterface::class);
    $greeter->method('greet')->willReturn('Hello, Alice!');

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);
    $account->method('getDisplayName')->willReturn('Alice');

    $build = $this->buildBlock($greeter, $account)->build();

    self::assertSame('Alice', $build['#user_name']);
    self::assertSame('Hello, Alice!', $build['#greeting']);
  }

  /**
   * The configured custom name is forwarded to the greeter.
   *
   * @covers ::build
   */
  public function testBuildPassesConfiguredCustomNameToGreeter(): void {
    $greeter = $this->createMock(GreeterInterface::class);
    $greeter->expects(self::once())
      ->method('greet')
      ->with('Robin')
      ->willReturn('Hello, Robin!');

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(FALSE);

    $build = $this->buildBlock($greeter, $account, ['custom_name' => 'Robin'])->build();

    self::assertSame('Hello, Robin!', $build['#greeting']);
  }

  /**
   * The block declares the 'user' cache context rather than inheriting none.
   *
   * @covers ::getCacheContexts
   */
  public function testGetCacheContextsIncludesUser(): void {
    self::assertContains('user', $this->buildBlock()->getCacheContexts());
  }

  /**
   * The block declares the settings config cache tag for invalidation.
   *
   * @covers ::getCacheTags
   */
  public function testGetCacheTagsIncludeSettingsConfig(): void {
    self::assertContains('config:example_starter.settings', $this->buildBlock()->getCacheTags());
  }

  /**
   * The block declares an explicit, permanent max-age.
   *
   * A block that forgets its cache metadata defaults to an uncacheable
   * max-age of 0; this block opts into permanent caching and leans on its
   * cache tags and contexts for correct invalidation.
   *
   * @covers ::getCacheMaxAge
   */
  public function testGetCacheMaxAgeIsPermanent(): void {
    self::assertSame(Cache::PERMANENT, $this->buildBlock()->getCacheMaxAge());
  }

  /**
   * Builds a GreetingBlock through create() with mocked dependencies.
   *
   * @param \Drupal\example_starter\Service\GreeterInterface|null $greeter
   *   The greeter the container should return; a bare mock when omitted.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The current user the container should return; a bare mock when omitted.
   * @param array<string, mixed> $configuration
   *   Block configuration merged over the plugin defaults.
   *
   * @return \Drupal\example_starter\Plugin\Block\GreetingBlock
   *   The instantiated block.
   */
  private function buildBlock(
    ?GreeterInterface $greeter = NULL,
    ?AccountInterface $account = NULL,
    array $configuration = [],
  ): GreetingBlock {
    $greeter ??= $this->createMock(GreeterInterface::class);
    $account ??= $this->createMock(AccountInterface::class);

    $container = $this->createMock(ContainerInterface::class);
    // ContainerInterface::get() has a defaulted second argument, which PHPUnit
    // includes when matching a return-value map; keying a callback on the
    // service ID sidesteps that and reads more clearly.
    $container->method('get')->willReturnCallback(
      fn (string $id) => match ($id) {
        'example_starter.greeter' => $greeter,
        'current_user' => $account,
        default => throw new \InvalidArgumentException(sprintf('Unexpected service "%s".', $id)),
      },
    );

    return GreetingBlock::create(
      $container,
      $configuration,
      self::PLUGIN_ID,
      self::PLUGIN_DEFINITION,
    );
  }

}
