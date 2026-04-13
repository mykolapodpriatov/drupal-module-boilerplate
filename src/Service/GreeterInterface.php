<?php

declare(strict_types=1);

namespace Drupal\example_starter\Service;

/**
 * Builds localized greetings using module configuration and the current user.
 */
interface GreeterInterface {

  /**
   * Generates a greeting string for the given name.
   *
   * @param string $name
   *   The name to greet. An empty string falls back to the current user's
   *   account name, or the anonymous label if the user is not authenticated.
   *
   * @return string
   *   The fully formatted greeting, e.g. "Hello, Alice!".
   */
  public function greet(string $name = ''): string;

  /**
   * Returns the greeting prefix currently configured for the module.
   *
   * @return string
   *   The configured prefix, with the module default applied as fallback.
   */
  public function getPrefix(): string;

}
