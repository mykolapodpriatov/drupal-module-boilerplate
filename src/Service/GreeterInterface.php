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
   * Module configuration shapes the result: the greeting_prefix is prepended,
   * names longer than max_name_length are truncated, and when show_username is
   * disabled the current user's name is omitted entirely (an explicitly passed
   * name is always honoured).
   *
   * @param string $name
   *   The name to greet. An empty string falls back to the current user's
   *   account name (when show_username is enabled), the anonymous label when the
   *   user is not authenticated, or no name at all when show_username is off.
   *
   * @return string
   *   The fully formatted greeting, e.g. "Hello, Alice!" or "Hello!" when the
   *   name is omitted.
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
