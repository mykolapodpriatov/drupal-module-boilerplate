<?php

declare(strict_types=1);

namespace Drupal\Tests\example_starter\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\example_starter\Form\SettingsForm;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\example_starter\Form\SettingsForm
 *
 * @group example_starter
 */
final class SettingsFormTest extends KernelTestBase {

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
   * @covers ::buildForm
   */
  public function testFormBuildsWithConfigDefaults(): void {
    $form = $this->container->get('form_builder')->getForm(SettingsForm::class);

    self::assertSame('Hello', $form['greeting_prefix']['#default_value']);
    self::assertSame(64, $form['max_name_length']['#default_value']);
    self::assertTrue((bool) $form['show_username']['#default_value']);
  }

  /**
   * @covers ::submitForm
   */
  public function testValidSubmitPersistsValues(): void {
    $form_state = (new FormState())->setValues([
      'greeting_prefix' => '  Howdy  ',
      'show_username' => FALSE,
      'max_name_length' => 128,
      'op' => 'Save configuration',
    ]);

    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);

    self::assertEmpty($form_state->getErrors(), 'Valid form submission produces no errors.');

    $config = $this->config('example_starter.settings');
    self::assertSame('Howdy', $config->get('greeting_prefix'));
    self::assertSame(128, $config->get('max_name_length'));
    self::assertFalse($config->get('show_username'));
  }

  /**
   * @covers ::validateForm
   */
  public function testEmptyPrefixIsRejected(): void {
    $form_state = (new FormState())->setValues([
      'greeting_prefix' => '   ',
      'show_username' => TRUE,
      'max_name_length' => 64,
      'op' => 'Save configuration',
    ]);

    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);

    $errors = $form_state->getErrors();
    self::assertArrayHasKey('greeting_prefix', $errors);
  }

  /**
   * @covers ::validateForm
   */
  public function testOutOfRangeMaxLengthIsRejected(): void {
    $form_state = (new FormState())->setValues([
      'greeting_prefix' => 'Hello',
      'show_username' => TRUE,
      'max_name_length' => 999,
      'op' => 'Save configuration',
    ]);

    $this->container->get('form_builder')->submitForm(SettingsForm::class, $form_state);

    $errors = $form_state->getErrors();
    self::assertArrayHasKey('max_name_length', $errors);
  }

}
