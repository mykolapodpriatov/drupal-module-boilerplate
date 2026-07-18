<?php

declare(strict_types=1);

namespace Drupal\example_starter\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\example_starter\EventSubscriber\RequestSubscriber;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Forgets the per-route request counters recorded by RequestSubscriber.
 *
 * Queue an item whose payload is the machine name of a tracked route (e.g.
 * "example_starter.hello") and this worker drops that route's entry from the
 * State-backed counter map. It demonstrates the QueueWorker plugin pattern:
 * PHP 8 attribute discovery, cron-driven processing, and dependency injection
 * through ContainerFactoryPluginInterface::create().
 */
#[QueueWorker(
  id: 'example_starter_cleanup',
  title: new TranslatableMarkup('Example Starter request-counter cleanup'),
  cron: ['time' => 30],
)]
final class ExampleStarterCleanupWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The example_starter logger channel.
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructs a new ExampleStarterCleanupWorker.
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array<string, mixed> $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service holding the per-route request counters.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger channel factory.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    private readonly StateInterface $state,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $loggerFactory->get('example_starter');
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): self {
    return new self(
      $configuration,
      (string) $plugin_id,
      $plugin_definition,
      $container->get('state'),
      $container->get('logger.factory'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @param mixed $data
   *   The queued item. This worker expects the machine name of a tracked route
   *   whose request counter should be forgotten; anything else is skipped.
   */
  public function processItem($data): void {
    if (!is_string($data) || $data === '') {
      return;
    }

    $counters = $this->state->get(RequestSubscriber::STATE_KEY, []);
    if (!is_array($counters) || !array_key_exists($data, $counters)) {
      return;
    }

    unset($counters[$data]);
    $this->state->set(RequestSubscriber::STATE_KEY, $counters);

    $this->logger->info('Cleared request counter for route %route.', ['%route' => $data]);
  }

}
