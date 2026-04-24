<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Unit\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\example_starter\Service\Greeter;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\example_starter\Service\Greeter
 *
 * @group example_starter
 */
final class GreeterTest extends UnitTestCase {

  /**
   * @covers ::greet
   */
  public function testGreetUsesExplicitName(): void {
    $greeter = $this->buildGreeter(prefix: 'Hello', authenticated: FALSE, displayName: 'Anon');

    self::assertSame('Hello, Alice!', $greeter->greet('Alice'));
  }

  /**
   * @covers ::greet
   */
  public function testGreetFallsBackToCurrentUserDisplayName(): void {
    $greeter = $this->buildGreeter(prefix: 'Hi', authenticated: TRUE, displayName: 'Bob');

    self::assertSame('Hi, Bob!', $greeter->greet(''));
  }

  /**
   * @covers ::greet
   */
  public function testGreetUsesGuestForAnonymous(): void {
    $greeter = $this->buildGreeter(prefix: 'Welcome', authenticated: FALSE, displayName: '');

    self::assertSame('Welcome, Guest!', $greeter->greet(''));
  }

  /**
   * @covers ::getPrefix
   */
  public function testPrefixFallsBackWhenConfigMissing(): void {
    $greeter = $this->buildGreeter(prefix: NULL, authenticated: FALSE, displayName: '');

    self::assertSame(Greeter::DEFAULT_PREFIX, $greeter->getPrefix());
  }

  /**
   * @covers ::getPrefix
   */
  public function testPrefixFallsBackWhenConfigEmpty(): void {
    $greeter = $this->buildGreeter(prefix: '', authenticated: FALSE, displayName: '');

    self::assertSame(Greeter::DEFAULT_PREFIX, $greeter->getPrefix());
  }

  /**
   * @covers ::greet
   */
  public function testGreetTrimsWhitespaceFromName(): void {
    $greeter = $this->buildGreeter(prefix: 'Hello', authenticated: FALSE, displayName: '');

    self::assertSame('Hello, Carol!', $greeter->greet('   Carol   '));
  }

  /**
   * Builds a Greeter with mocked dependencies.
   */
  private function buildGreeter(?string $prefix, bool $authenticated, string $displayName): Greeter {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn($authenticated);
    $account->method('getDisplayName')->willReturn($displayName);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->with('example_starter')->willReturn($logger);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('greeting_prefix')->willReturn($prefix);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('example_starter.settings')->willReturn($config);

    return new Greeter($account, $loggerFactory, $configFactory);
  }

}
