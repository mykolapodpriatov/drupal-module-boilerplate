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
    $resolved = $this->resolveName($name);
    $prefix = $this->getPrefix();
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
   * Resolves the name to greet, falling back to the current account.
   */
  private function resolveName(string $name): string {
    $name = trim($name);
    if ($name !== '') {
      return $name;
    }

    if ($this->currentUser->isAuthenticated()) {
      return $this->currentUser->getDisplayName();
    }

    return 'Guest';
  }

}
