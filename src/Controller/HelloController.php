<?php

declare(strict_types=1);

namespace Drupal\example_starter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\example_starter\Service\GreeterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns the public greeting page for the Example Starter module.
 */
final class HelloController extends ControllerBase {

  /**
   * Constructs a new HelloController.
   */
  public function __construct(
    private readonly GreeterInterface $greeter,
    private readonly AccountInterface $account,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('example_starter.greeter'),
      $container->get('current_user'),
    );
  }

  /**
   * Builds the greeting page render array.
   *
   * @return array<string, mixed>
   *   The renderable greeting page.
   */
  public function page(): array {
    $userName = $this->account->isAuthenticated()
      ? $this->account->getDisplayName()
      : '';

    return [
      '#theme' => 'example_starter_greeting',
      '#greeting' => $this->greeter->greet(),
      '#user_name' => $userName,
      '#cache' => [
        'contexts' => ['user', 'url'],
        'tags' => ['config:example_starter.settings'],
        'max-age' => 300,
      ],
    ];
  }

}
