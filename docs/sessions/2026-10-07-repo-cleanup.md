# Session: Repo cleanup & root consolidation — 2026-10-07

## Goal
Deep-clean the fork: audit folder structure, remove unnecessary/dead files and
code, consolidate docs, and slim the repo root.

## What was done

### Audit (read-only, two explore passes)
- Full audit of top-level/auxiliary dirs, app/ subdirs, routes, configs,
  public/css, and the floating-licenses package.
- Findings: no empty app dirs, no orphaned route files, no stray killa CSS,
  no live debug statements, no stale/duplicate docs under `docs/`.

### Deletions — upstream leftovers (user-approved aggressive tier)
- `ansible/`, `Vagrantfile`, `Procfile`, `app.json`, `install.sh`,
  `snipeit.sh`, `upgrade.php`, `_config.yml` (0-byte), `crowdin.yml` +
  `.github/workflows/crowdin-upload.yml`, `psalm.xml`, `.all-contributorsrc`,
  `CONTRIBUTORS.md`
- Orphaned pa11y tooling: `pa11y.js`, `.pa11yci.json`, `tests/pa11y/`
- Dead code: `/test-email` debug route + unused `CheckoutComponentMail` import
  (`routes/web.php`), `app/Jobs/Job.php` (nothing extends it), commented `dd()`
  in `DestroyManufacturerAction.php`, stale root `public/css/overrides.css(.map)`
  and `public/css/signature-pad.css` (only Mix-built/min copies referenced)
- All deletions recorded in `docs/upgrades/UPGRADE_HISTORY.md` under
  "Killa-pruned upstream files" so future upstream merges treat them as
  intentional.

### Kept after verification (audit flags that turned out load-bearing)
- `sample_csvs/` — used by `tests/Feature/Seeders/ImportSeederTest.php`
- `phpmd-ruleset.xml` + `phpmd.baseline.xml` — PHPMD is wired into
  `composer analyse` + `static-analysis.yml` CI
- `server.php` — used by `php artisan serve`
- `docker/`, `Dockerfile*`, compose files — exercised by CI

### Docs move + index
- `MULTI_FIELDSET_DESIGN.md` → `docs/ui/`; `CODE_OF_CONDUCT.md`,
  `CONTRIBUTING.md`, `SECURITY.md`, `CHANGELOG-KILLA.md` → `docs/`;
  `TESTING.md` → `docs/testing/`; `deploy-server.sh`, `update-snipeit.sh` →
  `scripts/` (usage comments updated to `./scripts/update-snipeit.sh`)
- Created `docs/README.md` index; updated all cross-references (README,
  runbook, history, inventory, baseline, handoff, strategy).

### Root slimming — QA configs to `.ci/`
- Moved `phpunit.xml`, `pint.json`, `phpstan.neon.dist/.example`,
  `phpstan-baseline.neon`, `phpmd-ruleset.xml`, `phpmd.baseline.xml` → `.ci/`
- Fixed internal relative paths (`../vendor/...`, `../app`, `../tests/...`,
  bootstrap, cacheDirectory — cache still lands at root, already gitignored)
- Updated `composer.json` (`analyse:*`, `coverage:*` scripts), the three
  `tests-*.yml` workflows (`php artisan test --parallel -c .ci/phpunit.xml`),
  and every documented command (AGENTS.md, TEST_STRATEGY, TESTING, runbook,
  handoff, CLAUDE.md, package README, `.ai/rules/boost/tests.md`)
- Root: 29 files → 17, all tooling-locked (manifests, artisan, Docker CI,
  README/LICENSE/agent files)

## Decisions & root causes
- Kept upstream files that CI or tests actually consume — "dead looking" ≠
  dead; two audit candidates (sample_csvs, phpmd) were rescued by grep
  evidence before deletion.
- pa11y removed rather than fixed: package never installed, dir empty — the
  tooling was aspirational, not real.
- `.ci/` chosen over `config/` for tool configs to avoid confusion with
  Laravel runtime config.

## Verification
- Pre-deletion greps for every candidate (references in tests, CI, composer,
  Blade views).
- `composer.json` re-validated as JSON after edits.
- **Gates NOT run — PHP/composer not on PATH on this machine (Herd), and
  `vendor/`/`node_modules/` not installed.** Maintainer must run:
  `vendor/bin/pint --dirty --config .ci/pint.json --format agent`,
  `php artisan optimize:clear`,
  `vendor/bin/phpunit -c .ci/phpunit.xml --testsuite Unit`,
  `vendor/bin/phpunit -c .ci/phpunit.xml --testsuite FloatingLicenses`,
  `composer analyse`, `npm install && npm run dev`.
- Unverified risk: PHPStan/PHPUnit `../` path resolution from `.ci/` — first
  real run confirms.

## Server / environment notes
- Maintainer machine: Windows + Git Bash, PHP via Herd (not on PATH for CLI),
  node/npm available.
- `docker/docker-secrets.env` is committed (upstream sample) — maintainer
  should confirm it holds no real secrets.

## Follow-ups / known issues
- Run the full gate suite above before tagging anything.
- Upstream merges will surface the pruned files as conflicts — resolution is
  "keep deleted"; see the list in `docs/upgrades/UPGRADE_HISTORY.md`.
- 71 upstream-inherited TODOs in `app/` intentionally left untouched.
