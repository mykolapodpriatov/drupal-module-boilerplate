# Contributing

Thanks for your interest in improving the boilerplate. The goal is to keep
this repository **lean**, **idiomatic**, and **production-ready** — any new
example should pull its weight by demonstrating a pattern most Drupal modules
actually need.

## Local setup

```bash
ddev start
ddev composer install
ddev drush site:install -y
ddev drush en example_starter -y
```

If you don't use DDEV, run the equivalents directly: `composer install`, then
point your local Drupal site to this module via a path repository in your
site's `composer.json`.

## Running the checks

Before submitting a PR, please make sure all of these pass locally:

```bash
composer lint
composer analyse
composer test:unit
composer test:kernel
# Functional (BrowserTestBase) tests require a working Drupal install plus a
# WebDriver/Chrome for any JS coverage; run them locally before opening a PR:
composer test:functional
```

CI runs PHPCS, PHPStan (level 8), and the unit + kernel suites across
PHP 8.2/8.3 and Drupal 10.3/11. Functional tests are **not** run in CI (they
need a provisioned browser, which adds flakiness), so please run
`composer test:functional` locally and confirm it is green. PRs that turn CI
red will not be merged.

## Code style

- PHP files start with `<?php\n\ndeclare(strict_types=1);`.
- All parameters and return types are explicitly typed.
- One-line class-level docblocks; no novel-length comments.
- Follow `Drupal` + `DrupalPractice` coding standards (`composer lint:fix`
  handles the obvious bits).

## Commit messages

Short, imperative, no trailing period:

```
Add SettingsForm with config schema
Fix permission key in routing.yml
```

## Reporting issues

Please include:
- PHP version, Drupal core version
- Steps to reproduce
- Expected vs actual behavior
- Relevant log entries (`watchdog`, browser console, etc.)
