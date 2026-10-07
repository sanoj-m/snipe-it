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

- **Known open issue:** open redirect in
  `FloatingLicenseController::release()` (redirects to attacker-controlled
  `Referer` header) — HIGH, tracked in CUSTOMIZATION_INVENTORY defects.
- **Known open issue:** no FMCS company scoping in floating licenses —
  cross-company allocate/list possible when FMCS is enabled.
- `general.php footer_credit` replaces Grokability/AGPL attribution —
  compliance concern under review (BR-04).

### Upgrade Notes

- Run `php artisan migrate` — adds `settings.ldap_deactivate_missing`,
  `licenses.perpetual`, `settings.floating_licenses_enabled`, and the two
  floating tables (all migrations FK-free; note:
  `floating_licenses_enabled` migration is unguarded — known defect).
- `floating-licenses:expire` is registered but **not yet scheduled** in
  `app/Console/Kernel.php` — add
  `$schedule->command('floating-licenses:expire')->everyFiveMinutes();`
  or run it from cron if pools use durations.
- After upgrade: `composer dump-autoload`, `php artisan optimize:clear`,
  `npm run dev` (Mix, not Vite).
- Broken gitlink `snipe-it-floating-license-plugin` (legacy duplicate) is
  pending removal.
