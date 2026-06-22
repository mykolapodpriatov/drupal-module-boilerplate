<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Functional\Controller;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * @coversDefaultClass \Drupal\example_starter\Controller\HelloController
 *
 * @group example_starter
 */
final class HelloControllerTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['example_starter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @covers ::page
   */
  public function testAnonymousIsDenied(): void {
    $this->drupalGet('/example-starter/hello');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * @covers ::page
   */
  public function testAuthorizedUserSeesGreeting(): void {
    $user = $this->drupalCreateUser(['view example starter page']);
    $this->assertInstanceOf(UserInterface::class, $user);
    $this->drupalLogin($user);

    $this->drupalGet('/example-starter/hello');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Hello,');
    $this->assertSession()->pageTextContains((string) $user->getDisplayName());
  }

  /**
   * @covers ::page
   */
  public function testGreetingReflectsConfigChange(): void {
    $this->config('example_starter.settings')
      ->set('greeting_prefix', 'Welcome')
      ->save();

    $user = $this->drupalCreateUser(['view example starter page']);
    $this->assertInstanceOf(UserInterface::class, $user);
    $this->drupalLogin($user);

    $this->drupalGet('/example-starter/hello');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Welcome,');
  }

}
