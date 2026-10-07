# Test Strategy — Killa Asset

## Stack

- **PHPUnit 11** — NOT Pest. Do not introduce Pest.
- Parallel runner: **paratest** (`vendor/bin/paratest`).
- Test env uses **array drivers** (cache/session/etc.) — see `.ai/rules/tests.md`.
- CI matrix (`.github/workflows/`): **MySQL, PostgreSQL, SQLite**; plus larastan,
  CodeQL, docker builds.

## Testsuites (`phpunit.xml`)

| Suite | Path | Covers |
|---|---|---|
| `Unit` | `tests/Unit` | Pure unit tests |
| `Feature` | `tests/Feature` (~67 domain dirs) | HTTP/DB feature tests: Assets, Checkouts, Checkins, Api, Importer, Ldap, Fmcs, Livewire, Blade, Console… |
| `FloatingLicenses` | `packages/floating-licenses/tests` | Package feature tests (17 files, ~114 tests); runs against host `Tests\TestCase`, master switch enabled in `setUp()` |

## Commands

```bash
vendor/bin/phpunit -c .ci/phpunit.xml --testsuite Unit
vendor/bin/phpunit -c .ci/phpunit.xml --testsuite Feature
vendor/bin/phpunit -c .ci/phpunit.xml --testsuite FloatingLicenses
vendor/bin/phpunit -c .ci/phpunit.xml --filter SomeTest          # single test
vendor/bin/paratest                           # parallel
vendor/bin/pint --dirty --config .ci/pint.json --format agent        # style gate, after any PHP change
vendor/bin/phpstan analyse -c .ci/phpstan.neon.dist                    # static analysis
```

New tests via artisan (PHPUnit, not Pest):

```bash
php artisan make:test SomeTest --phpunit --no-interaction
```

**Local-run blocker:** the maintainer's Windows machine currently has no PHP
runtime on `PATH`, so these commands cannot be executed locally. State this
when reporting; the commands above are the canonical gate and run in CI.

## When to add tests

| Change | Tests required? |
|---|---|
| Behavior / logic change | **Yes** — test the changed behavior and its important failure modes |
| Pure copy, styling, layout-only | No |
| New core patch (KCP) | Yes, plus register the patch |
| Floating-licenses change | Yes — in the package suite, not `tests/Feature` |

Do not add tests beyond the changed behavior. Do not delete or weaken failing
tests — report PRE-EXISTING vs REGRESSION.

## Upgrade regression philosophy

- After every upstream merge: run all three suites; the FloatingLicenses suite
  is the canary for the fenced core hunks (checkout interception, transformers,
  presenters).
- Compare against the pre-merge baseline: a test red before and after is
  PRE-EXISTING; a test green→red is a REGRESSION and blocks the upgrade.
- Record results in `docs/upgrades/UPGRADE_HISTORY.md` (Regression results
  column).
