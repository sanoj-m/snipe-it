# Upstream Baseline — Killa Asset fork

Generated: Phase 1 discovery (2026). Regenerate after every upstream upgrade.

## Git topology

| Item | Value |
|---|---|
| `origin` | https://github.com/sanoj-m/snipe-it.git (Killa Asset fork) |
| `upstream` | https://github.com/grokability/snipe-it.git |
| Fork branch | `master` |
| Merge base | `2c466fa8a5a68525439bf1bbbc24470d6670c516` = **tag v8.8.0** |
| Latest upstream tag at analysis | **v8.8.0** (Killa is based on the newest release) |
| Commits unique to Killa | **55** |
| Commits upstream is ahead | **112** (post-8.8.0 development on `upstream/master`, unreleased) |

## Divergence statistics (v8.8.0 → master)

- **133 files changed**: +7,636 / −539 lines
- **72 modified** upstream files, **61 added** files, **0 deleted** files
- One **broken gitlink** at `snipe-it-floating-license-plugin` (mode 160000, no `.gitmodules`, target objects absent locally) — legacy duplicate of the floating-license code; see CUSTOMIZATION_INVENTORY.

## What Killa actually is (corrected)

Killa's 55 commits consist of exactly four feature/theme tracks:

1. **Floating Licenses** — `packages/floating-licenses` Composer path package (~5,700 LOC) + tagged `[floating-licenses addon]` hooks in ~15 core files.
2. **Perpetual licenses** — `licenses.perpetual` boolean; small edits across License model/controllers/importer + migration.
3. **LDAP deactivate-missing** — `settings.ldap_deactivate_missing`; one new `Ldap::deactivateUsersMissingFromLdap()` method + LdapSync hook + Livewire settings UI; plus auto-scheduled daily `snipeit:ldap-sync` in `app/Console/Kernel.php`.
4. **Killa branding + "killa-v2 / Atelier" theme** — mechanical `Snipe-IT → Killa Asset` string swaps (21 notification classes, mail templates, lang files), 6 committed zero-build CSS overlays in `public/css/killa-v2*.css`, layout/dashboard view edits, sidebar flyout hover JS, chart palette swap.

**Important correction to earlier handoff docs:** `Order`/`OrderItem`, `SyncAdapterConfig`/`SyncAdapterInstance`, and `PullInventory`/`PushInventory` are **upstream Snipe-IT v8.8.0 features, not Killa customizations**. They have zero Killa modifications and need no isolation work.

## Conflict hotspots (ranked)

| Rank | File | Diff size | Risk | Why |
|---|---|---|---|---|
| 1 | `resources/views/licenses/view.blade.php` | +320 | HIGH | Floating-license table/UI interleaved; `@if(!$floatingConfig)` wrappers *around* upstream blocks; heavily edited upstream |
| 2 | `app/Http/Transformers/LicensesTransformer.php` | +67 | HIGH | 50-line insertion inside the main transform method; upstream refactors transformers regularly |
| 3 | `app/Presenters/LicensePresenter.php` | +33 | MED-HIGH | **Replaces** (not adds) the `purchase_cost` column |
| 4 | `app/Http/Controllers/Licenses/LicensesController.php` | +14 | MED-HIGH | Hooks inside `store()`/`update()`, the most upstream-edited methods |
| 5 | `app/Http/Controllers/Licenses/LicenseCheckoutController.php` | +32 | MEDIUM | Checkout interception; upstream security fixes land here |
| 6 | `resources/views/licenses/edit.blade.php` | +73 | MEDIUM | Perpetual + floating form sections |
| 7 | `resources/views/layouts/default.blade.php` | +20/−23 | MEDIUM | Theme loader, footer-link deletions, inline flyout JS |
| 8 | `app/Http/Transformers/UsersTransformer.php` | +50 | MEDIUM | Floating allocations appended to user licenses |
| 9 | Branding one-liners (21 notifications, 5 console commands, mail views, lang files) | ~1 line each | HIGH-FREQUENCY/LOW-SEVERITY | Upstream edits these strings periodically; every such edit conflicts |
| 10 | `app/Helpers/Helper.php` | +12/−268 | LOW | Palette data swap only (266-entry array → 10-entry); no functions deleted |

## Database divergence

7 custom migrations total: 2 core (`settings.ldap_deactivate_missing`, `licenses.perpetual`, both idempotent/guarded, future-dated 2026_08_25/2026_09_02) + 5 package (`floating_license_configs`, `floating_license_allocations`, durations-nullable change, `settings.floating_licenses_enabled` **unguarded**, cost recalculation data-migration). No FK constraints anywhere (project rule held). See docs/database/CUSTOM_SCHEMA.md.

## Package / dependency divergence

- `composer.json`: path repo + `snipe-it/floating-licenses: *` + test autoload. No other dependency changes.
- `package.json`: `puppeteer`, `ssh2` added (tooling; misplaced in `dependencies` instead of `devDependencies`).
- `phpunit.xml`: third testsuite `FloatingLicenses`.
- `config/`: **no divergence at all**.

## Upgrade tooling already present

`update-snipeit.sh` — fetch-tag → `git merge` → archive/scp deploy to hardcoded server IP. Documents the "keep both sides, re-apply `[floating-licenses addon]`" convention, but references an unimplemented `SKIP_MERGE=1` flag and an out-of-repo server-side script.
