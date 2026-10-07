# Upgrade History — Killa Asset

One row per upstream upgrade. Runbook: `UPSTREAM_UPGRADE_RUNBOOK.md`.
Baseline reference: [UPSTREAM_BASELINE](../upstream/UPSTREAM_BASELINE.md).

| Date | Previous upstream | New upstream | Killa release | Conflicts | Core patches reapplied/removed | Migrations | Dependency changes | Security changes | Regression results | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| 2026 (fork cut) | — | v8.8.0 (`2c466fa`) | — (initial fork, 55 commits) | N/A (baseline) | All 35 KCP registered at baseline | 2 core + 5 package migrations added | `snipe-it/floating-licenses` path repo; puppeteer/ssh2 in package.json | — | Not run (no PHP runtime on maintainer machine) | Initial Killa fork; Orders/Sync confirmed upstream v8.8.0, not Killa |
| 2026-10-07 (hardening, not an upstream merge) | v8.8.0 | v8.8.0 (unchanged) | unreleased | **Dry-run simulation vs upstream/master (112 commits, unreleased): 1 conflict** — `lang/en-US/admin/settings/sync_adapters.php` (branding strings); **subsequently reverted to upstream (branding-only diff, ADR-0001 trade) → re-run `git merge-tree --write-tree HEAD upstream/master` now reports 0 conflicts** (clean exit, tree `b1b1097`); collision candidates 9 → 8 after refactor; 7 auto-merge | KCP-003/004 consolidated into `License::saving` hook; FL sync moved to package observer; KCP-018/019 extracted to package partials (view 511→225 lines); KCP-030 reduced 16 files → 1 global `MessageSending` listener; gitlink removed | none new; `floating_licenses_enabled` migration guarded | puppeteer/ssh2 → devDependencies | **Fixed:** open redirect in floating release(); FMCS scoping added to floating licenses; `floating-licenses:expire` now scheduled (5 min) | Not runnable locally (no PHP) — maintainer must run full gates; see TEST_STRATEGY.md | 4 commits: docs → floating-licenses refactor → branding → UI normalization |

<!--
Entry template:

| YYYY-MM-DD | vA.B.C | vX.Y.Z | killa-vN | N files (list HIGH first) | KCP-xxx reapplied; KCP-yyy removed (upstream adopted) | new upstream migrations audited vs Killa columns | composer/npm deltas | upstream security fixes merged (list) | Unit/Feature/FloatingLicenses pass-fail counts | free text |
-->

## Killa-pruned upstream files (intentional deletions)

These upstream v8.8.0 files were deliberately deleted in the 2026 repo cleanup.
On future upstream merges, treat their absence as intentional — do not
"restore" them when resolving conflicts; re-delete upstream additions instead.

- `ansible/` (freebsd, ubuntu playbooks) — upstream leftover, no CI, not Killa-maintained
- `Vagrantfile`, `Procfile`, `app.json` — Heroku/Vagrant hosting legacy
- `install.sh`, `snipeit.sh`, `upgrade.php` — upstream installer scripts (Killa deploys via `update-snipeit.sh` / `deploy-server.sh`)
- `_config.yml` — 0-byte GitHub Pages stub
- `crowdin.yml`, `.github/workflows/crowdin-upload.yml` — upstream translation pipeline
- `psalm.xml` — unused analyzer config (no composer script/CI reference; gates are Pint + Larastan/PHPStan + PHPMD + CodeQL)
- `.all-contributorsrc`, `CONTRIBUTORS.md` — upstream community metadata
- `pa11y.js`, `.pa11yci.json`, `tests/pa11y/` — orphaned a11y tooling (package never installed, dir empty)
- `app/Jobs/Job.php` — Laravel-4-era abstract base, nothing extends it
- `public/css/overrides.css` + `.map` (root; live copy is `css/build/overrides.css` via Mix), `public/css/signature-pad.css` (only `.min.css` is referenced)
- `routes/web.php` `/test-email` debug route + its `CheckoutComponentMail` import

Kept despite being upstream-legacy: `server.php` (used by `php artisan serve`),
`sample_csvs/` (referenced by `tests/Feature/Seeders/ImportSeederTest.php`),
all of `docker/` + `Dockerfile*` (exercised by CI workflows).
