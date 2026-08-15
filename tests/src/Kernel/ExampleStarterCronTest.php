<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Kernel;

use Drupal\Core\Queue\QueueInterface;
use Drupal\example_starter\EventSubscriber\RequestSubscriber;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that hook_cron() feeds ExampleStarterCleanupWorker from State.
 *
 * @group example_starter
 */
final class ExampleStarterCronTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'example_starter',
  ];

  /**
   * A tracked route whose counter should be turned into a queue item.
   */
  private const TRACKED_ROUTE = 'example_starter.hello';

  /**
   * A second tracked route, used to prove every map key is enqueued.
   */
  private const OTHER_ROUTE = 'example_starter.settings';

  /**
   * Returns the example_starter_cleanup queue, creating the backend if needed.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue the cron producer writes to.
   */
  private function cleanupQueue(): QueueInterface {
    $queue = $this->container->get('queue')->get('example_starter_cleanup');
    $queue->createQueue();
    return $queue;
  }

  /**
   * Collects every currently queued payload.
   *
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue to drain.
   *
   * @return list<mixed>
   *   Claimed item data, in claim order.
   */
  private function claimAll(QueueInterface $queue): array {
    $claimed = [];
    while (($item = $queue->claimItem()) !== FALSE) {
      self::assertIsObject($item);
      self::assertTrue(property_exists($item, 'data'));
      $claimed[] = $item->data;
      $queue->deleteItem($item);
    }
    return $claimed;
  }

  /**
   * Cron enqueues each State key as a route-name payload.
   */
  public function testCronEnqueuesTrackedRouteNamesFromState(): void {
    $this->container->get('state')->set(RequestSubscriber::STATE_KEY, [
      self::TRACKED_ROUTE => 3,
      self::OTHER_ROUTE => 1,
    ]);

    $queue = $this->cleanupQueue();
    self::assertSame(0, $queue->numberOfItems());

    $this->container->get('module_handler')->invoke('example_starter', 'cron');

    self::assertEqualsCanonicalizing(
      [self::TRACKED_ROUTE, self::OTHER_ROUTE],
      $this->claimAll($queue),
    );
  }

  /**
   * Cron is a no-op when no counters have been recorded.
   */
  public function testCronLeavesQueueEmptyWhenStateHasNoCounters(): void {
    $this->container->get('state')->set(RequestSubscriber::STATE_KEY, []);

    $queue = $this->cleanupQueue();
    $this->container->get('module_handler')->invoke('example_starter', 'cron');

    self::assertSame(0, $queue->numberOfItems());
    self::assertSame([], $this->claimAll($queue));
  }

  /**
   * A corrupt (non-array) State value is ignored instead of being enqueued.
   */
  public function testCronLeavesQueueEmptyWhenStateIsCorrupt(): void {
    $this->container->get('state')->set(RequestSubscriber::STATE_KEY, 'not-an-array');

    $queue = $this->cleanupQueue();
    $this->container->get('module_handler')->invoke('example_starter', 'cron');

    self::assertSame(0, $queue->numberOfItems());
    self::assertSame([], $this->claimAll($queue));
  }

  /**
   * Non-string State keys are skipped; only route machine names are queued.
   */
  public function testCronSkipsNonStringStateKeys(): void {
    $this->container->get('state')->set(RequestSubscriber::STATE_KEY, [
      self::TRACKED_ROUTE => 2,
      0 => 9,
      '' => 1,
    ]);

    $this->container->get('module_handler')->invoke('example_starter', 'cron');

    self::assertSame([self::TRACKED_ROUTE], $this->claimAll($this->cleanupQueue()));
  }

}
