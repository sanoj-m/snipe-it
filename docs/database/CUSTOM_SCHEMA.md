# Custom Database Schema — Killa Asset vs upstream v8.8.0

Project rule: **no foreign-key constraints** — all 7 custom migrations comply.
Multi-DB (MySQL/PostgreSQL/SQLite) compatibility verified by CI.

## Core migrations (database/migrations/)

| Migration | Table.Column | Definition | Guarded? | down() | Notes |
|---|---|---|---|---|---|
| 2026_08_25_000000_add_ldap_deactivate_missing_to_settings_table | `settings.ldap_deactivate_missing` | tinyInteger NOT NULL default 0 | Yes (hasColumn up+down) | Drops column | Feeds LdapSync deactivate-missing (KCP-014) |
| 2026_09_02_000000_add_perpetual_to_licenses_table | `licenses.perpetual` | tinyInteger NOT NULL default 0, after expiration_date | Yes | Drops column | `->after()` is MySQL-only hint, harmless elsewhere |
| 2026_10_21_100000_killa_add_currency_to_licenses_table | `licenses.currency` | char(3) nullable, after purchase_cost | Yes | Drops column | Per-license currency (KCP-040–043); rates in settings |
| 2026_10_21_100001_killa_add_exchange_rates_to_settings_table | `settings.currency_exchange_rates` | text nullable (JSON `{USD: 3.67, …}`), after default_currency | Yes | Drops column | `App\Services\Killa\CurrencyConverter`; Renewal Tracker AED equivalents |
| 2026_10_21_100002_killa_add_location_to_licenses_table | `licenses.location_id` | unsignedBigInteger nullable, after company_id | Yes | Drops column | License location (KLD-CPT / KLD-Headoffice); Renewal Tracker Location column |
| 2026_11_19_100000_killa_create_license_purchases_table | `license_purchases` | id, indexed `license_id` (no FK), `purchase_date`, nullable `period_start`/`period_end`, `term_months` smallint, `amount`/`amount_aed`/`ai_amount_aed` decimal(14,2), `currency` char(3), `invoice_file`, `notes`, `created_by`, timestamps, softDeletes | Yes (hasTable) | Drops table | License Subscription & Spend Tracking (KCP-067–073); invoices on `local` disk under `private_uploads/licenses/{id}/purchases` |
| 2026_11_19_100001_killa_add_subscription_flags_to_licenses_table | `licenses.is_subscription`, `licenses.is_ai` | boolean NOT NULL default 0 (after `perpetual`) | Yes (hasColumn up+down) | Drops columns | Subscription + AI flags feeding spend/forecast/AI reports |

## Package migrations (packages/floating-licenses/database/migrations/)

| Migration | Change | Notes |
|---|---|---|
| 2025_01_01_000001_create_floating_license_configs_table | `floating_license_configs`: id, `license_id` unique, `pool_size`, `total_cost` decimal(12,2) null, `cost_mode` default 'pool_slot', `allow_over_allocation` bool, `lease_duration_minutes` default 120, `idle_timeout_minutes` default 60, timestamps, softDeletes | 1:1 with licenses (by convention, no FK) |
| 2025_01_01_000002_create_floating_license_allocations_table | `floating_license_allocations`: id, nullable indexed license_id/user_id/asset_id, `status` default 'active' + index, `allocated_cost` decimal(12,2) null, allocated_at/last_seen_at/expires_at/released_at, notes, timestamps, softDeletes | Index names under MySQL 64-char limit |
| 2026_01_01_000001_make_durations_nullable_on_floating_license_configs | duration columns → nullable (native `->change()`, L12 OK) | down() lossy: NULLs become 120/60 |
| 2026_01_01_000002_add_floating_licenses_enabled_to_settings | `settings.floating_licenses_enabled` boolean default false | **UNGUARDED — fatals if column exists; add hasColumn guard** |
| 2026_09_02_000001_recalculate_total_cost_from_unit_price | Data migration: recompute total_cost + allocated_cost per cost_mode | Irreversible down() (documented); portable query-builder |

## Settings columns and consumers

- `ldap_deactivate_missing` → `LdapSettings` Livewire wizard, `LdapSync.php`, lang keys, LdapWizardTest.
- `floating_licenses_enabled` → `FloatingLicenseSync::isEnabled()` master gate, `SettingsController`, settings/general view, conditional license UI.
- `perpetual` (on licenses) → License model cast/rule/mutator + `isExpired()`, web+API controllers, importer, transformer, edit view.
- `currency` (on licenses) → License model rule/fillable, license edit form select, `LicensesController` store/update, Renewal Tracker display.
- `currency_exchange_rates` → `App\Services\Killa\CurrencyConverter`, `Killa\CurrencySettingsController`, `killa/settings/currency` view, Renewal Tracker AED equivalents.
- `license_purchases` → `App\Models\LicensePurchase` + `License::purchases()` (KCP-067), `Killa\LicensePurchasesController` (CRUD + invoice download), `Killa\LicenseSpendReportController` (yearly/monthly/forecast/AI/attention reports + CSV), `App\Services\Killa\LicenseSpendCalculator`, dashboard widget `killa/dashboard/_license-spend`.
- `is_subscription` / `is_ai` (on licenses) → License model casts/rules/fillable/mutators (KCP-067), both license edit forms (KCP-069 + killa/licenses/edit), forecast/AI/attention filtering in LicenseSpendCalculator.

## Collision-risk notes

- Upstream v8.8.0 itself ships migrations dated up to 2026_09_22; Killa's dates interleave. Filename collision with a future upstream same-timestamp migration is the main hazard → **name future Killa migrations with a `killa_` infix**. The existing Killa migrations listed above predate this convention and are grandfathered — do not rename them (renaming a merged migration breaks `migrations` table bookkeeping).
- If upstream ever adds its own `ldap_deactivate_missing`/`perpetual`/`floating_licenses_enabled` columns with different definitions, guarded migrations silently keep the existing column (fresh installs get upstream's definition only if it runs first — audit on upgrade).
