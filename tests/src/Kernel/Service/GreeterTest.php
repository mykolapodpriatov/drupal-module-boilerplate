<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Kernel\Service;

use Drupal\example_starter\Service\GreeterInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\example_starter\Service\Greeter
 *
 * @group example_starter
 */
final class GreeterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'example_starter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['example_starter']);
  }

  /**
   * Returns the greeter service under test.
   */
  private function greeter(): GreeterInterface {
    return $this->container->get('example_starter.greeter');
  }

  /**
   * @covers ::greet
   */
  public function testGreetUsesShippedDefaults(): void {
    // Default config: prefix "Hello", show_username TRUE, max_name_length 64.
    self::assertSame('Hello, Alice!', $this->greeter()->greet('Alice'));
  }

  /**
   * @covers ::greet
   */
  public function testGreetHonoursPrefixConfig(): void {
    $this->config('example_starter.settings')
      ->set('greeting_prefix', 'Welcome')
      ->save();

    self::assertSame('Welcome, Alice!', $this->greeter()->greet('Alice'));
  }

  /**
   * @covers ::greet
   */
  public function testGreetTruncatesNameToConfiguredLength(): void {
    $this->config('example_starter.settings')
      ->set('max_name_length', 5)
      ->save();

    self::assertSame('Hello, Alexa!', $this->greeter()->greet('Alexander'));
  }

  /**
   * @covers ::greet
   */
  public function testShowUsernameDisabledOmitsName(): void {
    $this->config('example_starter.settings')
      ->set('show_username', FALSE)
      ->save();

    // With no explicit name and show_username off, the name is omitted.
    self::assertSame('Hello!', $this->greeter()->greet());
    // An explicit name is still honoured regardless of show_username.
    self::assertSame('Hello, Alice!', $this->greeter()->greet('Alice'));
  }

}
