<?php

declare(strict_types=1);

namespace Drupal\example_starter\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tracks how often module-owned routes are hit, using the State API.
 */
final class RequestSubscriber implements EventSubscriberInterface {

  /**
   * State key under which per-route counters live.
   */
  public const STATE_KEY = 'example_starter.request_count';

  /**
   * Route prefix this subscriber reacts to.
   */
  private const TRACKED_ROUTE_PREFIX = 'example_starter.';

  /**
   * The example_starter logger channel.
   */
  private LoggerChannelInterface $logger;

  /**
   * Constructs a new RequestSubscriber.
   */
  public function __construct(
    private readonly StateInterface $state,
    private readonly RouteMatchInterface $routeMatch,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $loggerFactory->get('example_starter');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      // Run late so the route matcher has populated the route match.
      KernelEvents::REQUEST => ['onRequest', 28],
    ];
  }

  /**
   * Increments a per-route counter for module routes.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $routeName = $this->routeMatch->getRouteName();
    if ($routeName === NULL || !str_starts_with($routeName, self::TRACKED_ROUTE_PREFIX)) {
      return;
    }

    $counters = $this->state->get(self::STATE_KEY, []);
    if (!is_array($counters)) {
      $counters = [];
    }
    $counters[$routeName] = ((int) ($counters[$routeName] ?? 0)) + 1;
    $this->state->set(self::STATE_KEY, $counters);

    $this->logger->debug('Request hit %route (total: @count).', [
      '%route' => $routeName,
      '@count' => $counters[$routeName],
    ]);
  }

}
