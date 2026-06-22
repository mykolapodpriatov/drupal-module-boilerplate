<?php

declare(strict_types=1);

namespace Drupal\example_starter\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\example_starter\Service\GreeterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a greeting produced by the Greeter service.
 */
#[Block(
  id: 'example_starter_greeting',
  admin_label: new TranslatableMarkup('Example Starter greeting'),
  category: new TranslatableMarkup('Example Starter'),
)]
final class GreetingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new GreetingBlock.
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
   * @param string $plugin_id
   *   Plugin ID.
   * @param array<string, mixed> $plugin_definition
   *   Plugin definition.
   * @param \Drupal\example_starter\Service\GreeterInterface $greeter
   *   The greeter service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user account.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    array $plugin_definition,
    private readonly GreeterInterface $greeter,
    private readonly AccountInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $configuration
   *   Plugin configuration.
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
      $container->get('example_starter.greeter'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The default block configuration.
   */
  public function defaultConfiguration(): array {
    return [
      'custom_name' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The block configuration form structure.
   *
   * @return array<string, mixed>
   *   The block configuration form structure.
   */
  public function blockForm($form, $form_state): array {
    $form = parent::blockForm($form, $form_state);
    $form['custom_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom name'),
      '#description' => $this->t('Leave blank to greet the current user.'),
      '#default_value' => (string) $this->configuration['custom_name'],
      '#maxlength' => 64,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The block configuration form structure.
   */
  public function blockSubmit($form, $form_state): void {
    $this->configuration['custom_name'] = trim((string) $form_state->getValue('custom_name'));
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, mixed>
   *   The block render array.
   */
  public function build(): array {
    $name = (string) $this->configuration['custom_name'];
    $userName = $this->currentUser->isAuthenticated()
      ? $this->currentUser->getDisplayName()
      : '';

    return [
      '#theme' => 'example_starter_greeting',
      '#greeting' => $this->greeter->greet($name),
      '#user_name' => $userName,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'view example starter page');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return array_merge(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return array_merge(parent::getCacheTags(), ['config:example_starter.settings']);
  }

}
