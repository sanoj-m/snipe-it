# Core Patch Register — Killa Asset

Every modification to an upstream-controlled file must be registered here. If a
diff vs upstream exists for a file not listed here, treat it as a defect.

Convention: floating-license hunks are fenced with `// [floating-licenses addon]
BEGIN/END` (or `{{-- [floating-licenses addon] --}}` in Blade). Theme blocks use
`[killa-v2]`. Perpetual-license and branding hunks are currently **unmarked** —
marking them is Phase 3 work.

## Register

| Patch ID | Upstream file | Feature | Lines | Marked? | Conflict risk | Removal strategy |
|---|---|---|---|---|---|---|
| KCP-001 | `app/Http/Controllers/Licenses/LicenseCheckoutController.php` | Floating checkout interception | +32 | Yes | MEDIUM | Only if upstream fires a pre-checkout event |
| KCP-002 | `app/Http/Controllers/Licenses/LicenseCheckinController.php` | Floating bulk checkin (`floating:<id>`) | +36 | Yes | MED-LOW | None viable; keep fenced |
| KCP-003 | `app/Http/Controllers/Licenses/LicensesController.php` | Floating sync comment pointers + perpetual assignment in store/update | +8 | Yes | LOW | Floating sync moved to package `License::saved` listener (route-gated); perpetual clear moved to model `saving` hook |
| KCP-004 | `app/Http/Controllers/Api/LicensesController.php` | Perpetual comment pointers only (clearing moved to `License::saving`) | +4 | Yes | LOW | Already consolidated on the model |
| KCP-005 | `app/Http/Controllers/Api/UsersController.php` | Floating allocations in user licenses | +13 | Yes | MEDIUM | Possible package endpoint + JS |
| KCP-006 | `app/Http/Controllers/SettingsController.php` | `floating_licenses_enabled` persist | +2 | No | LOW | Package-owned settings table |
| KCP-007 | `app/Http/Transformers/LicensesTransformer.php` | Floating seat math + cost columns | +67 | Yes | **HIGH** | Upstream transformer extension point (none exists) |
| KCP-008 | `app/Http/Transformers/LicenseSeatsTransformer.php` | Suppress per-seat checkout | +25 | Yes | LOW-MED | None viable |
| KCP-009 | `app/Http/Transformers/UsersTransformer.php` | Floating allocation rows | +50 | Yes | MEDIUM | None viable |
| KCP-010 | `app/Presenters/LicensePresenter.php` | New columns; **replaces purchase_cost column** | +33 | No | MED-HIGH | Revert to additive columns |
| KCP-011 | `app/Presenters/UserPresenter.php` | license_type column | +12 | Yes | MEDIUM | None viable |
| KCP-012 | `app/Models/License.php` | perpetual cast/rule/fillable/mutator + isExpired guard + `saving` hook clearing expiration_date (consolidates KCP-003/004 clearing) | +22 | Yes | MEDIUM | Trait if upstream supports; else keep |
| KCP-013 | `app/Models/Ldap.php` | deactivateUsersMissingFromLdap() | +36 | No | NONE (tail append) | Extract to Action class |
| KCP-014 | `app/Console/Commands/LdapSync.php` | deactivate-missing elseif | +8 | No | LOW-MED | Keep |
| KCP-015 | `app/Console/Kernel.php` | daily ldap-sync auto-schedule | +3 | No | LOW | Config-gate or upstream PR |
| KCP-031 | `app/Console/Kernel.php` | schedule `floating-licenses:expire` every 5 min when addon enabled | +5 | Yes (`[floating-licenses addon]`) | LOW | Keep fenced |
| KCP-016 | `app/Livewire/LdapSettings.php` + `resources/views/livewire/ldap-settings.blade.php` | deactivate-missing setting UI | +15 | No | LOW-MED | Keep |
| KCP-017 | `app/Importer/Importer.php`, `LicenseImporter.php`, `app/Livewire/Importer.php` | perpetual CSV mapping (clearing moved to `License::saving`) | +7 | Yes | LOW | Keep |
| KCP-018 | `resources/views/licenses/view.blade.php` | Inline fenced `@php` data-prep + 4 fenced `@include('floating-licenses::partials/license-view-*')` + 4 tight `@if(!$floatingConfig)` wrappers around upstream blocks | ~20 | Yes | MED-LOW | Data-prep must stay inline — `@include` scope does not leak back (regression fix 2026-10-21); wrappers must stay |
| KCP-019 | `resources/views/licenses/edit.blade.php` | Perpetual row/JS (marked) + fenced `@include('floating-licenses::partials/license-form-floating')` | ~8 | Yes | LOW | Done (floating section extracted to package partial) |
| KCP-020 | `resources/views/licenses/checkout.blade.php` | Pool availability header | +21 | Yes | MED-LOW | Keep |
| KCP-021 | `resources/views/users/view.blade.php` | Floating tab/rows | +18 | Yes | MEDIUM | Keep |
| KCP-022 | `resources/views/partials/bootstrap-table.blade.php` | Floating release formatter | +15 | Yes | LOW | Keep |
| KCP-023 | `resources/views/settings/general.blade.php` | Master-switch checkbox | +8 | No | LOW | Keep |
| KCP-024 | `resources/views/users/ldap.blade.php` | Summary-row key guards | +2 | No | LOW | Keep (defensive) |
| KCP-025 | `resources/views/layouts/default.blade.php` | Theme CSS loaders (shared + post-custom notifications), footer-link deletions, flyout JS | +21/−23 | Partial | MEDIUM | Move loader to partial; reconsider deletions |
| KCP-026 | `resources/views/layouts/basic.blade.php` | Theme CSS loader, title | +12/−5 | Partial | LOW | Move loader to partial |
| KCP-027 | `resources/views/blade/dashboard/top-boxes.blade.php` | k-tile restructure | ~98 | Yes ([killa-v2]) | MEDIUM | Keep (additive pattern preserved) |
| KCP-028 | `app/Helpers/Helper.php` | Chart palette swap (data only) | +12/−268 | Yes | LOW | Config-driven palette |
| KCP-029 | `resources/views/dashboard.blade.php` | Chart font colors | +1 | No | LOW | Keep |
| KCP-030 | `app/Providers/AppServiceProvider.php` | Global `MessageSending` listener stamps `X-System-Sender: Killa Asset` on all outgoing mail (notifications + mailables); **16 `app/Notifications/*.php` reverted to upstream** | +10 | Yes (`[killa branding]`) | LOW | Keep listener |
| KCP-030a | `snipe-it-floating-license-plugin` (broken gitlink) | Removed legacy gitlink (no .gitmodules, unreachable target); canonical code lives in `packages/floating-licenses` | −1 gitlink | N/A | NONE | Done — deleted |
| KCP-031 | `resources/views/vendor/mail/{html,markdown}/message.blade.php`, `vendor/notifications/email.blade.php` | Mail branding | ±5 | No | LOW | Keep |
| KCP-032 | `resources/views/setup/done.blade.php` | Branding + **deleted community-links block** | +5/−13 | No | LOW-MED | Reconsider deletion |
| KCP-033 | 5 × `resources/lang/en-US/*.php` | Branding + 3 functional keys; `footer_credit` now **preserves upstream Grokability attribution** with " — Killa Asset by Sanoj Maliyekkal" appended. **`admin/settings/sync_adapters.php` reverted to upstream** (was branding-only swaps, the sole recurring merge conflict per ADR-0001) | ~55 lines | No | HIGH-freq | Brand-name lang key |
| KCP-034 | 5 × console commands (DisableLDAP, DisableSAML, FixDoubleEscape, GeneratePersonalAccessToken, LdapTroubleshooter) | Branding strings | 1–3 lines each | No | HIGH-freq | Drop or centralize |
| KCP-035 | `composer.json`, `phpunit.xml`, `package.json` | Package/test/tooling wiring | +16 | N/A | LOW | Keep |
| KCP-036 | `routes/web.php` | Fenced `dashboard.insights` auth route and breadcrumb | +11 | Yes (`[killa-v2 insights dashboard]`) | LOW-MED | Remove route with the isolated Insights dashboard |
| KCP-037 | `resources/views/dashboard.blade.php` | Fenced Classic/Insights view-switch include | +6 | Yes (`[killa-v2 insights dashboard]`) | LOW-MED | Remove include; classic dashboard otherwise unchanged |
| KCP-038 | `routes/web/licenses.php` | Fenced `licenses.renewals` redirect → `licenses.index` (tracker page merged into the list 2026-10-23, KCP-046; controller/view/tests deleted; `licenses.sort_order` column left inert) | +4 | Yes (`[killa-v2 renewal tracker]`) | LOW | Keep redirect for bookmarks |
| KCP-039 | `resources/views/layouts/default.blade.php` | **REVERTED** — Licenses sidebar back to flat upstream link (tracker merged, KCP-046) | 0 | N/A | NONE | Nothing to preserve on upgrade |
| KCP-046 | `app/Http/Transformers/LicensesTransformer.php`, `app/Presenters/LicensePresenter.php` | Renewal columns merged into the licenses list: `days_to_expire`, `term`, `cost_monthly`/`cost_yearly` (per-seat × seats, own currency + AED equivalent), Notes made visible | +45/+30 | Yes (`[killa renewal-columns]`) | MED-LOW | Keep (supersedes the standalone tracker, KCP-038/039) |
| KCP-040 | `routes/web.php` | Fenced `settings.currency.*` routes (index/save/fetch) in admin group | +12 | Yes (`[killa currency]`) | LOW | Keep |
| KCP-041 | `resources/views/settings/index.blade.php` | Currency settings tile | +17 | Yes (`[killa currency]`) | LOW | Keep |
| KCP-042 | `resources/views/licenses/edit.blade.php`, `app/Http/Controllers/Licenses/LicensesController.php` | Currency select after purchase-cost + `$license->currency` assignment in store/update | +17/+2 | Yes (`[killa currency]`) | LOW | Keep |
| KCP-043 | `app/Models/License.php`, `app/Models/Setting.php` | `currency` rule/fillable; `currency_exchange_rates` fillable | +3 | Yes (`[killa currency]`) | LOW | Keep |
| KCP-044 | `resources/views/licenses/edit.blade.php`, `app/Http/Controllers/Licenses/LicensesController.php`, `app/Models/License.php` | Location select + `location_id` assignment in store/update + fillable/rule/`location()` relation | +7/+2/+3 | Yes (`[killa license-location]`) | LOW | Keep |
| KCP-045 | `app/Http/Transformers/LicensesTransformer.php`, `app/Http/Controllers/Api/LicensesController.php`, `resources/views/blade/table/index.blade.php`, `blade/table/licenses.blade.php`, `licenses/index.blade.php` | Scalar `category_name` field; category-dominant ordering so group headers stay aligned under any user sort; opt-in `default_sort`/`group_by`/`page_size` props on x-table; licenses list groups by category via vendored bootstrap-table group-by-v2 extension (`public/js/extensions/`) | +1/+8/+15/+8/+12 | Yes (`[killa license-grouping]`) | MED-LOW | Keep |

**Totals: 38 registered patches across 59 modified files (was 72 — 16 notification
files reverted to upstream in Phase 3; gitlink removed). HIGH risk: 1 (KCP-007).
MED-HIGH: 2 (KCP-010 and frequency-class branding).**

## Unmarked patches (defect — fix in Phase 3)

KCP-006, KCP-010, KCP-013–016, KCP-023–024, KCP-029, KCP-031–034.
