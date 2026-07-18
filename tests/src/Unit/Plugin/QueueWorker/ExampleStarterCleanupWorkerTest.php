<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Unit\Plugin\QueueWorker;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\example_starter\EventSubscriber\RequestSubscriber;
use Drupal\example_starter\Plugin\QueueWorker\ExampleStarterCleanupWorker;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\example_starter\Plugin\QueueWorker\ExampleStarterCleanupWorker
 *
 * @group example_starter
 */
final class ExampleStarterCleanupWorkerTest extends UnitTestCase {

  /**
   * The QueueWorker plugin ID under test.
   */
  private const PLUGIN_ID = 'example_starter_cleanup';

  /**
   * A minimal plugin definition; PluginBase reads the 'provider' key.
   */
  private const PLUGIN_DEFINITION = [
    'id' => 'example_starter_cleanup',
    'provider' => 'example_starter',
  ];

  /**
   * A tracked route whose counter the worker should be able to forget.
   */
  private const TRACKED_ROUTE = 'example_starter.hello';

  /**
   * The create() factory pulls the State service off the container and wires it.
   *
   * Proven indirectly: processing an item mutates the very State mock the
   * container returned, so create() must have fetched 'state' and passed it to
   * the constructor.
   *
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreateInjectsStateFromContainer(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn([self::TRACKED_ROUTE => 5]);
    $state->expects(self::once())
      ->method('set')
      ->with(RequestSubscriber::STATE_KEY, []);

    $this->buildWorker($state)->processItem(self::TRACKED_ROUTE);
  }

  /**
   * Processing a tracked route drops only that route's counter entry.
   *
   * @covers ::processItem
   */
  public function testProcessItemRemovesOnlyTheTargetRoute(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn([
      self::TRACKED_ROUTE => 3,
      'example_starter.settings' => 1,
    ]);
    $state->expects(self::once())
      ->method('set')
      ->with(RequestSubscriber::STATE_KEY, ['example_starter.settings' => 1]);

    $this->buildWorker($state)->processItem(self::TRACKED_ROUTE);
  }

  /**
   * An item for a route with no counter is a no-op (State is left alone).
   *
   * @covers ::processItem
   */
  public function testProcessItemIgnoresUnknownRoute(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(['example_starter.settings' => 1]);
    $state->expects(self::never())->method('set');

    $this->buildWorker($state)->processItem(self::TRACKED_ROUTE);
  }

  /**
   * A corrupt (non-array) State value is treated as empty, not written back.
   *
   * @covers ::processItem
   */
  public function testProcessItemIgnoresNonArrayState(): void {
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn('not-an-array');
    $state->expects(self::never())->method('set');

    $this->buildWorker($state)->processItem(self::TRACKED_ROUTE);
  }

  /**
   * Non-string / empty payloads are skipped before State is ever touched.
   *
   * @covers ::processItem
   *
   * @dataProvider invalidPayloadProvider
   */
  public function testProcessItemIgnoresInvalidPayload(mixed $payload): void {
    $state = $this->createMock(StateInterface::class);
    $state->expects(self::never())->method('get');
    $state->expects(self::never())->method('set');

    $this->buildWorker($state)->processItem($payload);
  }

  /**
   * Payloads that are not a non-empty route machine name.
   *
   * @return array<string, array{mixed}>
   *   Test cases keyed by description.
   */
  public static function invalidPayloadProvider(): array {
    return [
      'empty string' => [''],
      'integer' => [42],
      'array' => [['route' => self::TRACKED_ROUTE]],
      'null' => [NULL],
    ];
  }

  /**
   * Builds an ExampleStarterCleanupWorker through create() with mocked deps.
   *
   * @param \Drupal\Core\State\StateInterface|null $state
   *   The State service the container should return; a bare mock when omitted.
   *
   * @return \Drupal\example_starter\Plugin\QueueWorker\ExampleStarterCleanupWorker
   *   The instantiated worker.
   */
  private function buildWorker(?StateInterface $state = NULL): ExampleStarterCleanupWorker {
    $state ??= $this->createMock(StateInterface::class);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->with('example_starter')->willReturn($logger);

    $container = $this->createMock(ContainerInterface::class);
    // ContainerInterface::get() has a defaulted second argument, which PHPUnit
    // includes when matching a return-value map; keying a callback on the
    // service ID sidesteps that and reads more clearly.
    $container->method('get')->willReturnCallback(
      fn (string $id) => match ($id) {
        'state' => $state,
        'logger.factory' => $loggerFactory,
        default => throw new \InvalidArgumentException(sprintf('Unexpected service "%s".', $id)),
      },
    );

    return ExampleStarterCleanupWorker::create(
      $container,
      [],
      self::PLUGIN_ID,
      self::PLUGIN_DEFINITION,
    );
  }

}
