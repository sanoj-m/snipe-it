# Killa Asset — Customization Inventory

Every intentional divergence from upstream Snipe-IT v8.8.0. Companion docs:
[UPSTREAM_BASELINE](../upstream/UPSTREAM_BASELINE.md) · [CORE_PATCH_REGISTER](CORE_PATCH_REGISTER.md) · [CUSTOM_SCHEMA](../database/CUSTOM_SCHEMA.md)

Types: KILLA-CORE-PATCH (modifies upstream file) · KILLA-EXTENSION (new isolated code) · KILLA-UI · KILLA-CONFIG · KILLA-DATABASE · KILLA-BRANDING.

## 1. Floating Licenses (package) — KILLA-FLOATING-LICENSE

| ID | Area | File/Module | Type | Upstream modified? | Reason | Isolation possible? | Risk | Tests |
|---|---|---|---|---|---|---|---|---|
| FL-EXT | Package | `packages/floating-licenses/**` (~5,700 LOC) | KILLA-EXTENSION | No | Core feature | Already isolated | LOW | 17 feature files, ~114 tests |
| FL-01 | Wiring | `composer.json` (path repo, require, autoload-dev) | KILLA-CONFIG | Yes | Path-package registration | No (inherent) | LOW | — |
| FL-02 | Wiring | `phpunit.xml` (3rd testsuite) | KILLA-CONFIG | Yes | Package tests | No (inherent) | LOW | — |
| FL-03 | Web controller | `Licenses/LicensesController.php` store/update | KILLA-CORE-PATCH | Yes (comment pointers only) | **Done** — `License::saved` listener in package service provider, gated to `licenses.store`/`licenses.update` routes | LOW | LicenseFormSyncTest |
| FL-04 | Checkout | `Licenses/LicenseCheckoutController.php` | KILLA-CORE-PATCH | Yes (+32, marked) | Intercept seat checkout → pool allocation | No (no pre-checkout event upstream) | MEDIUM | FloatingCheckoutInterceptionTest |
| FL-05 | Checkin | `Licenses/LicenseCheckinController.php` | KILLA-CORE-PATCH | Yes (+36, marked) | Parse `floating:<id>` bulk checkin, release | No | MED-LOW | ReleaseTest |
| FL-06 | Settings | `SettingsController.php` | KILLA-CORE-PATCH | Yes (+2) | Persist master switch | **YES — package-owned config** | LOW | Yes |
| FL-07 | API | `Api/UsersController.php` | KILLA-CORE-PATCH | Yes (+13, marked) | Append floating allocations to user licenses | Partial (package endpoint) | MEDIUM | LicenseUsers…Test |
| FL-08 | Transformer | `LicensesTransformer.php` | KILLA-CORE-PATCH | Yes (+67) | Floating seat math + cost columns | No | **HIGH** | ApiTest, LicensesApiAvailabilityTest |
| FL-09 | Transformer | `LicenseSeatsTransformer.php` | KILLA-CORE-PATCH | Yes (+25, marked) | Suppress per-seat checkout for pools | No | LOW-MED | Yes |
| FL-10 | Transformer | `UsersTransformer.php` | KILLA-CORE-PATCH | Yes (+50, marked) | Floating rows + release URL | No | MEDIUM | Yes |
| FL-11 | Presenters | `LicensePresenter.php`, `UserPresenter.php` | KILLA-CORE-PATCH | Yes (+45) | New columns; **purchase_cost column replaced** | No | MED-HIGH | Yes |
| FL-12 | Views | `licenses/view.blade.php` (~20, includes + wrappers), `edit` (~8), `checkout` (+21), `users/view` (+18), `settings/general` (+8), `partials/bootstrap-table` (+15) | KILLA-CORE-PATCH / KILLA-UI | Yes | Floating UI | Done for view/edit (`@include` package partials) | MEDIUM | LicenseViewIntegrationTest |
| FL-13 | Console | `app/Console/Kernel.php` — `floating-licenses:expire` scheduled every 5 min (fenced, KCP-031) when master switch on | KILLA-CONFIG | Yes (+5) | Lease expiry | N/A | LOW | ExpirationTest |
| FL-14 | DB | 5 package migrations (2 tables + 3 settings/data) | KILLA-DATABASE | No | Schema | Isolated | LOW | Yes |
| FL-15 | Debt | `snipe-it-floating-license-plugin` broken gitlink (no `.gitmodules`, objects absent) | KILLA-EXTENSION | Yes (gitlink) | Legacy duplicate | **Remove** | LOW | — |

## 2. Perpetual licenses — KILLA-CORE-PATCH

| ID | File | Diff | Reason | Isolation possible? | Risk | Tests |
|---|---|---|---|---|---|---|
| PL-01 | `database/migrations/2026_09_02_000000_add_perpetual_to_licenses_table.php` | new | Column | N/A (idempotent) | LOW | — |
| PL-02 | `app/Models/License.php` | +22 (6 hunks, **marked**) | cast/rule/fillable/mutator, `isExpired()` guard, `saving` hook clearing expiration_date | No (must be on model) | MEDIUM | UpdateLicenseTest |
| PL-03 | `LicensesController` + `Api/LicensesController` | comment pointers only | `expiration_date=null` when perpetual — **collapsed into `License::saving`** (KCP-012) | Done | LOW | Yes |
| PL-04 | `Importer.php`, `LicenseImporter.php`, `Livewire/Importer.php` | +10 | CSV mapping | No | LOW | ImportLicenseTest |
| PL-05 | `licenses/edit.blade.php`, lang `admin/licenses/form.php`, `sample_csvs/licenses-sample.csv` | +75 | UI/labels/sample | No | LOW-MED | Yes |

## 3. LDAP deactivate-missing — KILLA-CORE-PATCH

| ID | File | Diff | Reason | Isolation possible? | Risk | Tests |
|---|---|---|---|---|---|---|
| LD-01 | Migration `2026_08_25_...settings.ldap_deactivate_missing` | new, guarded | Column | N/A | LOW | — |
| LD-02 | `app/Models/Ldap.php` | +36 (tail append) | `deactivateUsersMissingFromLdap()` | ~90% — could be Action class | **NONE-LOW** | LdapDeactivateMissingTest |
| LD-03 | `LdapSync.php` | +8 | `elseif` branch gated on setting | Small hook only | LOW-MED | Yes |
| LD-04 | `LdapSettings.php` + `ldap-settings.blade.php` + lang | +19 | Settings UI | No (wizard is core) | LOW-MED | LdapWizardTest |
| LD-05 | `app/Console/Kernel.php` | +3 | **Auto-schedules daily ldap-sync when LDAP enabled** (behavior change vs upstream) | Could be config-gated | LOW | — |
| LD-06 | `users/ldap.blade.php` | +2 | Guards for summary rows missing keys | No | LOW | — |

## 4. Branding — KILLA-BRANDING

| ID | Files | Diff | Reason | Isolation possible? | Risk | Tests |
|---|---|---|---|---|---|---|
| BR-01 | ~~21 `app/Notifications/*.php`~~ → `app/Providers/AppServiceProvider.php` (KCP-030) | +10 in one file | `X-System-Sender: Killa Asset` now applied via a global `MessageSending` listener; **all 16 notification files reverted to upstream — FIXED** | Done | LOW | — |
| BR-02 | 3 mail vendor views + `setup/done.blade.php` | ~±20 | Footer/header branding; **deletes upstream community-links block** | Partial | LOW-MED | — |
| BR-03 | 5 `lang/en-US/*` files | ~55 lines | String swaps (+3 functional keys: perpetual, ldap_deactivate_missing×2). **`admin/settings/sync_adapters.php` reverted to upstream** — branding-only swaps; it was the sole dry-run merge conflict vs upstream/master (ADR-0001 trade) | Partial (brand-name key) | HIGH frequency | — |
| BR-04 | `general.php footer_credit` | 1 | **CHANGED — FIXED**: upstream Grokability/AGPL attribution restored verbatim, " — Killa Asset by Sanoj Maliyekkal" appended | Done | LOW | — |
| BR-05 | 5 console commands (DisableLDAP/SAML, FixDoubleEscape, GenPAT, LdapTroubleshooter) | 1–3 lines each | Name swap | No | HIGH frequency | — |

## 5. Killa-v2 theme / UI — KILLA-UI

| ID | Files | Diff | Reason | Isolation possible? | Risk | Tests |
|---|---|---|---|---|---|---|
| UI-01 | `public/css/killa-v2*.css` (6 files, ~162KB, zero-build) | new | Theme layer — deliberately outside webpack | Already isolated | LOW | — |
| UI-02 | `layouts/default.blade.php` + `basic.blade.php` | +32/−28 | CSS/font loader, footer-link deletions, inline flyout JS (800ms hold) | Partial (partial/@stack) | MEDIUM | — |
| UI-03 | `blade/dashboard/top-boxes.blade.php` | ~98 | k-tile restructure (keeps upstream hooks) | Partial | MEDIUM | — |
| UI-04 | `Helper::defaultChartColors()` | +12/−268 | 266-color palette → 10-color Killa palette (data only, **no code deleted**) | Could be config-driven | LOW | — |
| UI-05 | `dashboard.blade.php` | 1 | Chart.js font colors | No | LOW | — |
| UI-06 | `skills/killa-design/SKILL.md` | new | Design-system authority (note: drifted from reality — says 1 file/6px radius, actual 6 files/12px) | N/A | — | — |

## 6. Tooling — KILLA-CONFIG

| ID | File | Reason | Risk |
|---|---|---|---|
| TL-01 | `update-snipeit.sh` | Upstream-merge + scp deploy workflow; hardcoded prod IP; `SKIP_MERGE` documented but unimplemented | LOW |
| TL-02 | `package.json` puppeteer/ssh2 in `dependencies` (should be devDependencies) | Screenshotter/deploy tooling | LOW |
| TL-03 | `deploy-server.sh` (untracked), `.vscode/` (untracked) | Local ops | — |

## Known functional defects found during inventory

1. **Open redirect** — FIXED: `FloatingLicenseController::release()` now honors only same-host/relative Referer targets (`safeRedirectTarget()`), else falls back to the pool page; tests cover external and lookalike hosts.
2. **No FMCS company scoping** — FIXED: `FloatingLicenseConfig::companyScoped()` (scopes via the license relation) applied to the pool index; config-bound pages 404 out-of-scope pools; allocate/bulk-add/API allocate reject cross-company users via `CompanyableTrait::canCheckoutTo()`; API availability is scoped by the License global scope on route binding. FMCS tests in `tests/Feature/FmcsScopingTest.php`.
3. **`floating-licenses:expire` never scheduled** — FIXED: fenced `[floating-licenses addon]` block in `app/Console/Kernel.php` (KCP-031) schedules it every five minutes when the master switch is on.
4. **Dead code**: FIXED: removed `FloatingLicenseService::revoke()`, `STATUS_REVOKED`, the `log.revoke` lang key, and the README audit row (no callers/routes existed).
5. **Package rule deviations**: string Gates instead of policies; no breadcrumbs on package UI routes (FIXED: breadcrumbs added on all package GET page routes, parented on `licenses.index`/`floating-licenses.index`); API returns raw model payload (no Transformer).
6. **CSS drift**: ~90 lines of contradictory flyout "corridor" CSS still live; comment/behavior drift (400ms vs 800ms); duplicate diverging tokens (`--k-input-disabled-bg`); ~251 `!important`s.
7. **Perf**: 6× `md5_file()` per page render in both layouts.
8. **Google Fonts CDN** on all pages incl. login (GDPR/offline concern).
9. **`floating_licenses_enabled` migration unguarded** — FIXED: `Schema::hasColumn` guards added in `up()`/`down()` matching the ldap_deactivate_missing pattern.
10. **Migration dates interleave upstream's** (2026_08_25 < upstream's 2026_09_22) — recommend `killa_` infix for future migrations.
