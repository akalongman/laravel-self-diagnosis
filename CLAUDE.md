# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`beyondcode/laravel-self-diagnosis` is a Laravel package that runs a configurable list of health checks against a Laravel application via the `php artisan self-diagnosis` command. This fork tracks Laravel 11 and 12 on PHP ^8.2 and adds extra checks (localized routes, Elasticsearch, disk space, PHP INI options, filesystems, Horizon).

The package itself is **not** a Laravel app. It is consumed by host apps that auto-discover `BeyondCode\SelfDiagnosis\SelfDiagnosisServiceProvider`. Tests run against a real bootstrapped Laravel via `orchestra/testbench`.

## Commands

```bash
composer test                                                 # full PHPUnit suite
vendor/bin/phpunit tests/AppKeyIsSetTest.php                  # single test class
vendor/bin/phpunit --filter it_checks_app_key_existance       # single test method
composer test-coverage                                        # HTML coverage report into ./coverage
composer phpcs                                                # lint (longman/php-code-style)
composer phpcbf                                               # auto-fix lint violations
```

Coverage and CI artifacts are written to `build/` (gitignored). PHPUnit is configured via `phpunit.xml.dist`; the test suite is named `BeyondCode Test Suite` and scans `tests/`.

## Architecture

The whole package is a runner over a contract. Three files are load-bearing:

- `src/SelfDiagnosisServiceProvider.php` registers the command (`command.selfdiagnosis`), merges `config/config.php` under the `self-diagnosis` key, and publishes translations and config.
- `src/SelfDiagnosisCommand.php` (`php artisan self-diagnosis {environment?}`) reads `self-diagnosis.checks`, then resolves the environment (CLI argument > `app()->environment()`, with fallback through `environment_aliases`), and runs `self-diagnosis.environment_checks.{env}`. Any failed check pushes its `message()` onto `$messages`; a non-empty list makes the command exit `1`.
- `src/Checks/Check.php` is the contract every check implements: `name(array $config)`, `check(array $config): bool`, `message(array $config)`. The same `$config` array is passed to all three methods, which lets a check stash failure state on the instance during `check()` and read it back in `message()` (see `RedisCanBeAccessed::$message`, `ServersArePingable::$notReachableServers`, `MigrationsAreUpToDate::$databaseError`).

### Config shape (`config/config.php`)

Checks are listed either as bare class strings or as `Class::class => [...config]` entries. `SelfDiagnosisCommand::runChecks()` detects which form via `is_numeric($check)`. Two top-level lists:

- `checks` runs in every environment.
- `environment_checks.{env}` runs only when the resolved env matches. `environment_aliases` maps custom names to canonical ones (`prod` and `live` map to `production`, `local` maps to `development`).

Adding a new check is three coordinated edits:

1. Create `src/Checks/MyCheck.php` implementing `Check`. Constructor injection works because checks are resolved with `app($check)` (see `HorizonIsRunning` consuming `Laravel\Horizon\Contracts\MasterSupervisorRepository`).
2. Register it in `config/config.php` under `checks` or `environment_checks.{env}`, with a config array if it needs parameters.
3. Add a snake_case key to `translations/en/checks.php` with `name` and `message`. Reference them as `self-diagnosis::checks.my_check.name` etc. Messages can be a string or a sub-array of variants (see `migrations_are_up_to_date`, `redis_can_be_accessed`, `supervisor_programs_are_running` for the multi-message pattern). Use `:placeholder` tokens and pass them via the second `trans()` argument.

### Shared utilities

- `src/Composer.php` extends `Illuminate\Support\Composer` with `installDryRun()` used by the two composer up-to-date checks.
- `src/SystemFunctions.php` is a thin proxy for `shell_exec`, `is_callable` plus disabled functions, and the Windows OS check. Inject it (not raw globals) when a check needs to shell out, so it can be mocked in tests (see `tests/SupervisorProgramsAreRunningTest.php`, `tests/LocalesAreInstalledTest.php`).
- `src/Server.php` is the DTO consumed by `ServersArePingable`.
- `src/Checks/Traits/ParsesNumValues.php` provides `toBytes`/`fromBytes` for byte size config (`AvailableDiskSpace`, `PhpIniOptions`).
- `src/Exceptions/InvalidConfigurationException.php` is the canonical throw for invalid per-check config (`ServersArePingable` is the reference example).

### Tests

Tests extend `Orchestra\Testbench\TestCase`, which boots a minimal Laravel for each case. PHPUnit annotation style is `/** @test */` (not attributes), keeping snake_case method names; the phpcs rule `PSR1.Methods.CamelCapsMethodName.NotCamelCaps` is excluded for `tests/` for that reason. Mock external boundaries via `Mockery` (constructor injection of `SystemFunctions`) or Laravel facades (`Artisan::shouldReceive` in `MigrationsAreUpToDateTest`). Static fixtures live in `tests/fixtures/`.

## Coding conventions

These reinforce the global PHP rules in `~/.claude/rules/php.md` and are non-obvious from a single file:

- Every PHP file declares `declare(strict_types=1);` directly under the opening tag.
- Internal PHP functions and constants are imported at the top of the file (`use function ...`, `use const ...`), not called with leading backslashes. Match the existing style of nearby files when adding code.
- All user-facing strings go through `trans('self-diagnosis::checks.<key>.<field>', [...])`. Never hardcode messages in `name()` or `message()`.
- Failure state for a check belongs on the instance, populated in `check()` and consumed in `message()`. Do not recompute or rerun probes inside `message()`.
- Use typed properties; avoid docblock-only type hints. Short nullable syntax (`?string`) only.
- `phpcs` is the source of truth for style; if a change fails it, run `composer phpcbf` before hand-editing.

## Documentation style

When editing this file, `README.md`, or any markdown, do not use dashes (`—` or ` - `) as sentence punctuation. Rephrase with periods, commas, or parentheses. List bullets are unaffected.