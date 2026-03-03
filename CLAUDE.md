# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel package (`encodia/laravel-health-env-vars`) that extends [Spatie's Laravel Health](https://github.com/spatie/laravel-health) with an `EnvVars` check. It validates that required `.env` variables are set and optionally match specific values, with support for environment-specific rules.

## Commands

```bash
# Run all tests
composer test

# Run a single test file
XDEBUG_MODE=off ./vendor/bin/pest tests/EnvVarsTest.php

# Run a specific test by name
XDEBUG_MODE=off ./vendor/bin/pest --filter "test name here"

# Run static analysis (PHPStan level 9)
composer analyse

# Run tests with coverage
composer test-coverage

# Fix code style (Laravel Pint)
./vendor/bin/pint
```

## Architecture

The package is minimal — two source classes plus a service provider:

- **`src/Health/Checks/EnvVars.php`** — The main check class extending `Spatie\Health\Checks\Check`. Implements `run()` which validates env vars by querying `env()`. Key behavior: skips all checks when `App::configurationIsCached()` returns true (because env vars aren't available after caching). Uses internal collections (`$requiredVarNames`, `$requiredVarValues`, `$requiredVarNamesForEnvironments`, `$requiredVarValuesForEnvironments`) populated via the fluent API.

- **`src/Health/Checks/CheckResultDto.php`** — Simple DTO with `ok()` / `error()` factory methods used internally by `EnvVars::run()`.

- **`src/EnvVarsServiceProvider.php`** — Registers translations (in `resources/lang/`). Auto-discovered by Laravel.

## Testing Patterns

Tests use Pest with Orchestra Testbench. Helper functions are defined in `tests/Pest.php`:

- `initEnvVars()` — creates a fresh `EnvVars` instance via `EnvVars::new()`
- `unsetEnvVars(array $names)` — removes env vars from `$_ENV` and `$_SERVER` for testing
- `mockCurrentEnvironment(string $env)` — mocks `App::environment()` return value
- `currentEnvironment()` — returns `App::environment()`

Test files are organized by feature:
- `tests/EnvVarsTest.php` — core functionality
- `tests/EnvironmentTest.php` — environment detection
- `tests/MultipleEnvironmentTest.php` — multi-environment scenarios

## Laravel Version Compatibility

The CI matrix tests PHP 8.0–8.4 against Laravel 8–13 with corresponding Testbench versions (6–11). Version constraints (e.g. Laravel 13 requires PHP ≥ 8.3) are enforced in `.github/workflows/run-tests.yml` via matrix exclusions.

## Code Style

Laravel Pint enforces code style and runs automatically via GitHub Actions on push (auto-committing fixes). PHPStan runs at level 9. The baseline is in `phpstan-baseline.php`.
