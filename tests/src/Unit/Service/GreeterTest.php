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
   * Explicit names are honoured even when show_username is disabled.
   *
   * @covers ::greet
   */
  public function testExplicitNameIgnoresShowUsername(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Hello',
      authenticated: TRUE,
      displayName: 'Bob',
      showUsername: FALSE,
    );

    self::assertSame('Hello, Alice!', $greeter->greet('Alice'));
  }

  /**
   * When show_username is off the authenticated name is omitted.
   *
   * @covers ::greet
   */
  public function testShowUsernameDisabledOmitsAuthenticatedName(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Hello',
      authenticated: TRUE,
      displayName: 'Bob',
      showUsername: FALSE,
    );

    self::assertSame('Hello!', $greeter->greet(''));
  }

  /**
   * When show_username is off the anonymous fallback is also omitted.
   *
   * @covers ::greet
   */
  public function testShowUsernameDisabledOmitsGuest(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Welcome',
      authenticated: FALSE,
      displayName: '',
      showUsername: FALSE,
    );

    self::assertSame('Welcome!', $greeter->greet(''));
  }

  /**
   * Names longer than max_name_length are truncated (multibyte safe).
   *
   * @covers ::greet
   */
  public function testGreetTruncatesLongName(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Hello',
      authenticated: FALSE,
      displayName: '',
      showUsername: TRUE,
      maxNameLength: 5,
    );

    // "Alexander" (9 chars) truncated to the first 5 characters.
    self::assertSame('Hello, Alexa!', $greeter->greet('Alexander'));
  }

  /**
   * Truncation counts characters, not bytes.
   *
   * @covers ::greet
   */
  public function testGreetTruncatesMultibyteNameByCharacters(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Hello',
      authenticated: FALSE,
      displayName: '',
      showUsername: TRUE,
      maxNameLength: 4,
    );

    // "caféé" is five characters; truncating to four yields "café".
    // A byte-based truncate to four would split the first é and corrupt it.
    self::assertSame('Hello, café!', $greeter->greet('caféé'));
  }

  /**
   * Names at or below the limit are left untouched.
   *
   * @covers ::greet
   */
  public function testGreetKeepsNameWithinLimit(): void {
    $greeter = $this->buildGreeter(
      prefix: 'Hello',
      authenticated: FALSE,
      displayName: '',
      showUsername: TRUE,
      maxNameLength: 10,
    );

    self::assertSame('Hello, Alice!', $greeter->greet('Alice'));
  }

  /**
   * Builds a Greeter with mocked dependencies.
   *
   * @param string|null $prefix
   *   The configured greeting_prefix value (NULL simulates a missing key).
   * @param bool $authenticated
   *   Whether the mocked current user is authenticated.
   * @param string $displayName
   *   The mocked current user's display name.
   * @param bool $showUsername
   *   The configured show_username value.
   * @param int $maxNameLength
   *   The configured max_name_length value.
   */
  private function buildGreeter(
    ?string $prefix,
    bool $authenticated,
    string $displayName,
    bool $showUsername = TRUE,
    int $maxNameLength = 64,
  ): Greeter {
    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn($authenticated);
    $account->method('getDisplayName')->willReturn($displayName);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->with('example_starter')->willReturn($logger);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->willReturnMap([
      ['greeting_prefix', $prefix],
      ['show_username', $showUsername],
      ['max_name_length', $maxNameLength],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('get')->with('example_starter.settings')->willReturn($config);

    return new Greeter($account, $loggerFactory, $configFactory);
  }

}
