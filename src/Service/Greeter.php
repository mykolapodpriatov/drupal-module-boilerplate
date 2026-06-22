<?php

declare(strict_types=1);

namespace Drupal\example_starter\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Default Greeter implementation backed by module configuration.
 */
final class Greeter implements GreeterInterface {

  /**
   * Default greeting prefix when configuration is missing.
   */
  public const DEFAULT_PREFIX = 'Hello';

  /**
   * Default maximum name length when configuration is missing.
   */
  public const DEFAULT_MAX_NAME_LENGTH = 64;

  /**
   * The example_starter logger channel.
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructs a new Greeter.
   */
  public function __construct(
    private readonly AccountInterface $currentUser,
    LoggerChannelFactoryInterface $loggerFactory,
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->logger = $loggerFactory->get('example_starter');
  }

  /**
   * {@inheritdoc}
   */
  public function greet(string $name = ''): string {
    $prefix = $this->getPrefix();
    $resolved = $this->resolveName($name);

    if ($resolved === '') {
      $greeting = sprintf('%s!', $prefix);
      $this->logger->debug('Generated name-less greeting.');

      return $greeting;
    }

    $resolved = $this->truncateName($resolved);
    $greeting = sprintf('%s, %s!', $prefix, $resolved);

    $this->logger->debug('Generated greeting for %name.', ['%name' => $resolved]);

    return $greeting;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrefix(): string {
    $configured = $this->configFactory
      ->get('example_starter.settings')
      ->get('greeting_prefix');

    if (!is_string($configured) || $configured === '') {
      return self::DEFAULT_PREFIX;
    }

    return $configured;
  }

  /**
   * Resolves the name to greet, honouring the show_username setting.
   *
   * An explicit, non-empty name is always honoured. When no explicit name is
   * given the current account's display name is used, unless show_username is
   * disabled in which case an empty string is returned so callers can render a
   * name-less greeting.
   *
   * @param string $name
   *   The explicitly requested name, if any.
   *
   * @return string
   *   The resolved name, or an empty string when the name must be omitted.
   */
  private function resolveName(string $name): string {
    $name = trim($name);
    if ($name !== '') {
      return $name;
    }

    if (!$this->showUsername()) {
      return '';
    }

    if ($this->currentUser->isAuthenticated()) {
      return (string) $this->currentUser->getDisplayName();
    }

    return 'Guest';
  }

  /**
   * Truncates a name to the configured maximum length (multibyte safe).
   *
   * @param string $name
   *   The name to truncate.
   *
   * @return string
   *   The name, truncated to max_name_length characters when longer.
   */
  private function truncateName(string $name): string {
    $max = $this->getMaxNameLength();
    if (mb_strlen($name) > $max) {
      return mb_substr($name, 0, $max);
    }

    return $name;
  }

  /**
   * Whether the current user's name should appear in the greeting.
   */
  private function showUsername(): bool {
    return (bool) $this->configFactory
      ->get('example_starter.settings')
      ->get('show_username');
  }

  /**
   * Returns the configured maximum name length, with a sane fallback.
   */
  private function getMaxNameLength(): int {
    $configured = $this->configFactory
      ->get('example_starter.settings')
      ->get('max_name_length');

    if (!is_int($configured) || $configured < 1) {
      return self::DEFAULT_MAX_NAME_LENGTH;
    }

    return $configured;
  }

}
