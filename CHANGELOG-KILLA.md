# Changelog — Killa Asset

Killa Asset releases — **always note the upstream base**. Format loosely
follows Keep a Changelog. For divergence detail see
[docs/customizations/CUSTOMIZATION_INVENTORY.md](docs/customizations/CUSTOMIZATION_INVENTORY.md);
for upgrade history see [docs/upgrades/UPGRADE_HISTORY.md](docs/upgrades/UPGRADE_HISTORY.md).

## Unreleased — based on Snipe-IT v8.8.0

Initial Killa fork: 55 commits on top of upstream tag `v8.8.0`
(`2c466fa8a5a68525439bf1bbbc24470d6670c516`). 133 files changed: +7,636/−539.

### Added

- **Floating Licenses** — `packages/floating-licenses` Composer path package
  (~5,700 LOC): concurrent-use license pools on top of fixed-seat licenses;
  pool configs 1:1 with licenses, concurrency-safe allocation
  (`lockForUpdate`), `pool_slot`/`active_user` cost modes, optional leases +
  idle timeouts, bulk add/remove users, per-license CSV import/export, master
  export, `/api/v1/floating-licenses/*` endpoints, `floating_licenses.*`
  permission set (injected into `config('permissions')` at runtime), master
  switch in Settings > General. ~15 fenced `[floating-licenses addon]` core
  hunks (KCP-001…023). Package test suite: 17 feature files, ~114 tests.
- **Perpetual licenses** — `licenses.perpetual` flag suppressing expiration
  (model, web + API controllers, importer, edit view, lang keys).
- **LDAP deactivate-missing** — `settings.ldap_deactivate_missing`; users
  missing from LDAP sync are deactivated instead of deleted; Livewire settings
  UI; auto-scheduled daily `snipeit:ldap-sync` when LDAP is enabled.
- **killa-v2 "Atelier" theme** — 6 zero-build CSS overlay files in
  `public/css/killa-v2*.css` with `--k-*` design tokens; dashboard k-tile
  restructure; sidebar flyout hover JS; layout loader in
  `layouts/default|basic.blade.php`.
- **Killa branding** — `Snipe-IT → Killa Asset` across 21 notification
  classes, mail templates, lang files, 5 console commands.
- Design-system skill `skills/killa-design/SKILL.md` governing UI work.

### Changed

- `Helper::defaultChartColors()` — 266-color upstream palette replaced with
  10-color Killa palette (data-only).
- `LicensePresenter` — `purchase_cost` column replaced by floating cost
  columns (KCP-010).
- `LicensesTransformer` / `UsersTransformer` / `LicenseSeatsTransformer` —
  floating seat math and allocation rows (KCP-007…009).
- `phpunit.xml` — third testsuite `FloatingLicenses`.
- `composer.json` — path repository + `snipe-it/floating-licenses: *` +
  package test autoload.

### Fixed

- `users/ldap.blade.php` — guards for summary rows with missing keys (KCP-024).

### Security

- **FIXED 2026-10-07:** open redirect in `FloatingLicenseController::release()`
  — Referer now honored only for relative or same-host URLs (`safeRedirectTarget()`).
- **FIXED 2026-10-07:** FMCS company scoping added across floating licenses
  (`scopeCompanyScoped()`, cross-company allocate/bulk/API rejected, 404s via
  License global scope). 10 new tests in `FmcsScopingTest`.
- **FIXED 2026-10-07:** `footer_credit` now preserves the upstream Grokability
  attribution verbatim with the Killa credit appended.

### Hardening release — 2026-10-07 (upstream base unchanged: v8.8.0)

- Perpetual expiration-clearing consolidated from 5 duplicated call sites into a
  single fenced `License::saving` hook in `app/Models/License.php`.
- `FloatingLicenseSync::syncFromRequest` moved out of core
  `LicensesController` into a package `License::saved` observer gated on the
  `licenses.store`/`licenses.update` routes (2 core hunks removed).
- `licenses/view.blade.php` 511 → 225 lines: floating blocks extracted into 5
  package partials (`floating-licenses::partials/*`); edit form section
  extracted likewise (KCP-018/019 conflict surface greatly reduced).
- Notification branding centralized: global `MessageSending` listener in
  `AppServiceProvider` sets `X-System-Sender: Killa Asset`; **16 notification
  files reverted byte-identical to upstream** (KCP-030 now 1 file).
- `floating-licenses:expire` scheduled (every 5 min, gated on master switch) —
  KCP-031 fenced block in `app/Console/Kernel.php`.
- `floating_licenses_enabled` migration now `hasColumn`-guarded.
- Dead code removed: `FloatingLicenseService::revoke()`, `STATUS_REVOKED`.
- Breadcrumbs added to all package UI routes; `exportUsers()` N+1 fixed.
- Floating Licenses rule deviations fixed: `FloatingLicenseConfigPolicy` now
  backs the model-level controller checks (string gates kept intentionally for
  the permissions config UI and non-model abilities), and API
  allocate/release/heartbeat payloads go through a whitelisting
  `FloatingLicenseAllocationsTransformer` instead of raw model serialization.
- Broken gitlink `snipe-it-floating-license-plugin` removed.
- puppeteer/ssh2 moved to `devDependencies` (run `npm install` to refresh lock).
- UI: dead flyout corridor CSS removed (~65 lines), `--k-*` token duplicates
  unified (single source of truth), stylesheet cache-bust switched from
  per-request `md5_file()` to `config('version.app_version')`, SKILL.md synced
  to reality, stale timing comments fixed.
- Upgrade simulation vs `upstream/master` (112 unreleased commits): **1 merge
  conflict** (`sync_adapters.php` lang branding); collision candidates 9 → 8.
- `resources/lang/en-US/admin/settings/sync_adapters.php` **reverted to
  upstream** — its diff was purely "Snipe-IT → Killa Asset" branding swaps, so
  it was traded for zero recurring merge conflicts (ADR-0001). Re-run of
  `git merge-tree --write-tree HEAD upstream/master` after the revert: **0
  conflicts** (clean exit, tree `b1b1097`). KCP-033/BR-03 now cover 5 lang
  files.
- `update-snipeit.sh`: `SERVER` env var is now required (hardcoded production
  IP default removed); `SKIP_MERGE=1` implemented — skips fetch/merge after
  verifying a clean tree with no merge in progress.

### Fixed

- `users/ldap.blade.php` — guards for summary rows with missing keys (KCP-024).

### Upgrade Notes

- Run `php artisan migrate` — adds `settings.ldap_deactivate_missing`,
  `licenses.perpetual`, `settings.floating_licenses_enabled`, and the two
  floating tables (all migrations FK-free and idempotent-guarded).
- `floating-licenses:expire` is scheduled automatically (no action needed).
- After upgrade: `composer install`, `php artisan optimize:clear`,
  `npm install && npm run dev` (Mix, not Vite).
- **Pending maintainer verification (no PHP runtime on the commit machine):**
  `vendor/bin/pint --dirty --format agent`, full PHPUnit suites
  (`Unit`, `Feature`, `FloatingLicenses`), `php artisan schedule:list`,
  browser check of floating license pages and collapsed-sidebar flyouts.
