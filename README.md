# Example Starter — Drupal 10/11 Module Boilerplate

[![CI](https://github.com/mykolapodpriatov/drupal-module-boilerplate/actions/workflows/ci.yml/badge.svg)](https://github.com/mykolapodpriatov/drupal-module-boilerplate/actions/workflows/ci.yml)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg)](phpstan.neon)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

A production-ready scaffold for custom Drupal 10/11 modules. Use it as a
starting point for any module that needs more than a single `.module` file —
it ships with the patterns you'd otherwise rebuild from scratch on every new
project.

## What's inside

| Concern              | Demonstrated with                                                |
| -------------------- | ---------------------------------------------------------------- |
| Dependency injection | `src/Service/Greeter.php` + `example_starter.services.yml`       |
| Routing & access     | `example_starter.routing.yml` + `src/Controller/HelloController` |
| Config + schema      | `config/install/*.yml`, `config/schema/*.yml`, `SettingsForm`    |
| Plugins (PHP attr.)  | `src/Plugin/Block/GreetingBlock.php`                             |
| Event subscribers    | `src/EventSubscriber/RequestSubscriber.php`                      |
| Update hooks         | `example_starter.install` (`hook_update_9001`)                   |
| Twig + libraries     | `templates/` + `example_starter.libraries.yml`                   |
| Tests                | Unit / Kernel / Functional under `tests/src/`                    |
| Static analysis      | PHPStan **level 8** via `phpstan.neon`                           |
| Coding standards     | `Drupal` + `DrupalPractice` via `phpcs.xml.dist`                 |
| CI                   | GitHub Actions matrix (PHP 8.2/8.3 × Drupal 10.3/11): PHPCS, PHPStan, unit + kernel tests, ShellCheck |

## Requirements

- PHP 8.2 or 8.3
- Drupal core `^10.3 || ^11`
- Composer 2
- DDEV (optional but recommended for local development)

## Use as a template

### Option A — GitHub "Use this template"

On [the repository page](https://github.com/mykolapodpriatov/drupal-module-boilerplate),
click **Use this template → Create a new repository**. GitHub copies the files
into a fresh repo of your own with no commit history to clean up. Then clone
your new repo and run the rename steps from Option B (skipping the
`git clone` / `rm -rf .git` lines).

### Option B — Clone and rename

Use the bundled script to rename the module (file contents **and** the
`example_starter.*` file names) in one step. It validates the machine name,
refuses to run until you change it from `example_starter`, and detects the
local `sed -i` flavour (macOS/BSD vs GNU/Linux) for you.

```bash
git clone https://github.com/mykolapodpriatov/drupal-module-boilerplate.git my_new_module
cd my_new_module
# Preview every change first (writes nothing):
bash scripts/rename-module.sh \
  --machine-name=my_new_module \
  --human-name="My New Module" \
  --dry-run
# Apply the rename:
bash scripts/rename-module.sh \
  --machine-name=my_new_module \
  --human-name="My New Module"
# Start fresh history for your module:
rm -rf .git && git init
```

The machine name must match `^[a-z][a-z0-9_]*$` (lower-case, starts with a
letter, words joined by underscores).

<details>
<summary>Prefer to run the raw commands by hand?</summary>

```bash
# Replace machine name everywhere:
grep -rl 'example_starter' . --exclude-dir=.git | xargs sed -i '' 's/example_starter/my_new_module/g'
# Replace human-readable name in info.yml, README, etc:
grep -rl 'Example Starter' . --exclude-dir=.git | xargs sed -i '' 's/Example Starter/My New Module/g'
# Rename file prefixes:
for f in example_starter.*; do mv "$f" "${f/example_starter/my_new_module}"; done
```

> The `sed -i ''` syntax above is for macOS/BSD. On GNU/Linux use `sed -i`
> (no empty-string argument).

</details>

## DDEV quickstart

```bash
ddev start
ddev composer install
ddev drush site:install -y
ddev drush en example_starter -y
ddev launch /example-starter/hello
```

## Running the test suite

```bash
# Unit tests only (fast, no DB required)
composer test:unit

# Kernel tests (requires bootstrapped Drupal)
composer test:kernel

# Functional tests (full BrowserTestBase) — run locally; not executed in CI
composer test:functional

# Static analysis
composer analyse

# Coding standards
composer lint
composer lint:fix     # auto-fix where possible
```

## Project layout

```
.
├── .github/workflows/ci.yml         GitHub Actions matrix (PHP × Drupal)
├── .ddev/config.yaml                Local dev environment
├── config/
│   ├── install/                     Default config shipped with module
│   └── schema/                      Config type metadata
├── css/                             Library CSS
├── js/                              Library JS
├── src/
│   ├── Controller/                  Route controllers
│   ├── EventSubscriber/             Kernel event listeners
│   ├── Form/                        Config and entity forms
│   ├── Plugin/Block/                Block plugins (PHP 8 attributes)
│   └── Service/                     Plain services + interfaces
├── templates/                       Twig templates
├── tests/src/
│   ├── Unit/                        PHPUnit unit tests
│   ├── Kernel/                      KernelTestBase tests
│   └── Functional/                  BrowserTestBase tests
├── example_starter.info.yml
├── example_starter.module
├── example_starter.install
├── example_starter.services.yml
├── example_starter.routing.yml
├── example_starter.permissions.yml
├── example_starter.links.menu.yml
├── example_starter.libraries.yml
├── composer.json
├── phpstan.neon
├── phpcs.xml.dist
├── phpunit.xml.dist
└── CHANGELOG.md
```

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

[MIT](LICENSE) — use freely, ship commercially, no attribution required.
