# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-28

### Added
- Initial public release as a reusable Drupal 10/11 module template.
- `Greeter` service with full dependency injection (current user + logger).
- `HelloController` exposing `/example-starter/hello` with permission gating.
- `SettingsForm` for `/admin/config/example-starter/settings` backed by config schema.
- `GreetingBlock` plugin using PHP 8 attribute discovery.
- `RequestSubscriber` tracking per-route request counts via the State API.
- `hook_update_9001()` example renaming a legacy config key.
- Unit, Kernel, and Functional test coverage.
- GitHub Actions CI matrix (PHP 8.2/8.3 × Drupal 10.3/11) running PHPCS, PHPStan
  level 8, and PHPUnit.
- DDEV configuration for local development.

[Unreleased]: https://github.com/example/drupal-module-boilerplate/compare/1.0.0...HEAD
[1.0.0]: https://github.com/example/drupal-module-boilerplate/releases/tag/1.0.0
