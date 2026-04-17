<?php

declare(strict_types=1);

namespace Drupal\example_starter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the Example Starter module's greeting behaviour.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Configuration object name managed by this form.
   */
  private const SETTINGS = 'example_starter.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'example_starter_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [self::SETTINGS];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::SETTINGS);

    $form['greeting_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Greeting prefix'),
      '#description' => $this->t('Text shown before the user name, e.g. "Hello" or "Welcome".'),
      '#default_value' => $config->get('greeting_prefix') ?? 'Hello',
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
    ];

    $form['show_username'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show user name in greeting'),
      '#description' => $this->t('When enabled, authenticated visitors see their display name in the greeting block.'),
      '#default_value' => (bool) $config->get('show_username'),
    ];

    $form['max_name_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum accepted name length'),
      '#description' => $this->t('Names longer than this value will be truncated before display.'),
      '#default_value' => (int) ($config->get('max_name_length') ?? 64),
      '#min' => 1,
      '#max' => 255,
      '#step' => 1,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $prefix = (string) $form_state->getValue('greeting_prefix');
    $prefix = trim($prefix);
    if ($prefix === '') {
      $form_state->setErrorByName('greeting_prefix', $this->t('The greeting prefix cannot be empty or whitespace only.'));
    }
    if (mb_strlen($prefix) > 64) {
      $form_state->setErrorByName('greeting_prefix', $this->t('The greeting prefix must be 64 characters or fewer.'));
    }

    $max = (int) $form_state->getValue('max_name_length');
    if ($max < 1 || $max > 255) {
      $form_state->setErrorByName('max_name_length', $this->t('Maximum name length must be between 1 and 255.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config(self::SETTINGS)
      ->set('greeting_prefix', trim((string) $form_state->getValue('greeting_prefix')))
      ->set('show_username', (bool) $form_state->getValue('show_username'))
      ->set('max_name_length', (int) $form_state->getValue('max_name_length'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
