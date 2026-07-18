<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Unit\EventSubscriber;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\State\StateInterface;
use Drupal\example_starter\EventSubscriber\RequestSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @coversDefaultClass \Drupal\example_starter\EventSubscriber\RequestSubscriber
 *
 * @group example_starter
 */
final class RequestSubscriberTest extends UnitTestCase {

  /**
   * A route name that the subscriber is expected to track.
   */
  private const TRACKED_ROUTE = 'example_starter.hello';

  /**
   * The subscriber registers onRequest against the kernel REQUEST event.
   *
   * The priority (28) matters: it must run after the router has populated the
   * route match but before the controller executes.
   *
   * @covers ::getSubscribedEvents
   */
  public function testGetSubscribedEventsMapsRequestToHandlerWithPriority(): void {
    $events = RequestSubscriber::getSubscribedEvents();

    self::assertArrayHasKey(KernelEvents::REQUEST, $events);
    self::assertSame(['onRequest', 28], $events[KernelEvents::REQUEST]);
  }

  /**
   * A tracked route increments its own counter and persists it back to State.
   *
   * @covers ::onRequest
   */
  public function testOnRequestIncrementsCounterForTrackedRoute(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn([self::TRACKED_ROUTE => 2]);
    $state->expects(self::once())
      ->method('set')
      ->with(RequestSubscriber::STATE_KEY, [self::TRACKED_ROUTE => 3]);

    $this->buildSubscriber($state, self::TRACKED_ROUTE)
      ->onRequest($this->requestEvent());
  }

  /**
   * A first hit on a tracked route seeds the counter at one.
   *
   * @covers ::onRequest
   */
  public function testOnRequestSeedsCounterOnFirstHit(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn([]);
    $state->expects(self::once())
      ->method('set')
      ->with(RequestSubscriber::STATE_KEY, [self::TRACKED_ROUTE => 1]);

    $this->buildSubscriber($state, self::TRACKED_ROUTE)
      ->onRequest($this->requestEvent());
  }

  /**
   * A corrupt (non-array) State value is discarded rather than trusted.
   *
   * @covers ::onRequest
   */
  public function testOnRequestResetsNonArrayStateValue(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn('not-an-array');
    $state->expects(self::once())
      ->method('set')
      ->with(RequestSubscriber::STATE_KEY, [self::TRACKED_ROUTE => 1]);

    $this->buildSubscriber($state, self::TRACKED_ROUTE)
      ->onRequest($this->requestEvent());
  }

  /**
   * Sub-requests are ignored so only the main request is ever counted.
   *
   * @covers ::onRequest
   */
  public function testOnRequestIgnoresSubRequests(): void {
    $state = $this->createMock(StateInterface::class);
    $state->expects(self::never())->method('get');
    $state->expects(self::never())->method('set');

    $this->buildSubscriber($state, self::TRACKED_ROUTE)
      ->onRequest($this->requestEvent(FALSE));
  }

  /**
   * Requests outside the module's route namespace are left untouched.
   *
   * @covers ::onRequest
   */
  public function testOnRequestIgnoresUntrackedRoutes(): void {
    $state = $this->createMock(StateInterface::class);
    $state->expects(self::never())->method('set');

    $this->buildSubscriber($state, 'system.admin')
      ->onRequest($this->requestEvent());
  }

  /**
   * A request with no matched route (route name NULL) is ignored.
   *
   * @covers ::onRequest
   */
  public function testOnRequestIgnoresRequestWithoutRoute(): void {
    $state = $this->createMock(StateInterface::class);
    $state->expects(self::never())->method('set');

    $this->buildSubscriber($state, NULL)
      ->onRequest($this->requestEvent());
  }

  /**
   * Builds a RequestSubscriber wired to a mocked route match and logger.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The State service the subscriber should read and write counters through.
   * @param string|null $routeName
   *   The route name the current route match should report.
   *
   * @return \Drupal\example_starter\EventSubscriber\RequestSubscriber
   *   The instantiated subscriber.
   */
  private function buildSubscriber(StateInterface $state, ?string $routeName): RequestSubscriber {
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteName')->willReturn($routeName);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->with('example_starter')->willReturn($logger);

    return new RequestSubscriber($state, $routeMatch, $loggerFactory);
  }

  /**
   * Builds a mocked kernel RequestEvent.
   *
   * @param bool $isMainRequest
   *   Whether the event should report itself as the main request.
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   *   The mocked event.
   */
  private function requestEvent(bool $isMainRequest = TRUE): RequestEvent {
    $event = $this->createMock(RequestEvent::class);
    $event->method('isMainRequest')->willReturn($isMainRequest);

    return $event;
  }

}
