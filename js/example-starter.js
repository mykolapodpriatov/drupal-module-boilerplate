/**
 * @file
 * Behaviors for the Example Starter module.
 */

(function (Drupal, once) {
  'use strict';

  /**
   * Marks greeting blocks as interactive after attaching.
   */
  Drupal.behaviors.exampleStarterGreeting = {
    attach: function (context) {
      once('example-starter-greeting', '.example-starter-greeting', context).forEach(function (element) {
        element.setAttribute('data-ready', 'true');
      });
    }
  };

})(Drupal, once);
