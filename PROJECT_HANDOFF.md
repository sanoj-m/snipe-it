# Snipe-IT ("Killa Asset" fork) — Architecture & Context for AI Agents

> Purpose of this document: give another AI agent enough accurate, structured
> context about this codebase to propose modifications that actually fit the
> project. Everything below describes the **current state** of this repository.

## 1. What this software is

- **Snipe-IT** — open-source (AGPLv3) IT asset management system: assets,
  licenses, accessories, consumables, components, kits, users, checkin/checkout
  workflows, audits, reports, LDAP/SAML/SCIM/2FA auth, importer, notifications.
- This repo is a **fork branded "Killa Asset"** based on upstream **v8.8.0**
  (55 Killa commits on top). Killa customizations are exactly four tracks:
  1. **Floating Licenses** — local Composer package `packages/floating-licenses`
     (`snipe-it/floating-licenses`), plus tagged `[floating-licenses addon]`
     hooks in ~15 core files.
  2. **Perpetual licenses** — `licenses.perpetual` flag suppressing expiration.
  3. **LDAP deactivate-missing** — deactivate (not delete) LDAP users missing
     from sync; auto-scheduled daily ldap-sync.
  4. **Killa branding + "killa-v2" theme** — name swaps, 6 zero-build CSS
     overlays in `public/css/killa-v2*.css`, dashboard/sidebar work.
  - `skills/killa-design` — design-system skill that governs all UI/UX work.
  - `snipe-it-floating-license-plugin` is a **broken gitlink** (no `.gitmodules`)
    — legacy duplicate, pending removal.
- **NOTE:** `Order`/`OrderItem`, `SyncAdapterConfig`/`SyncAdapterInstance`, and
  the `PullInventory`/`PushInventory` commands are **upstream Snipe-IT v8.8.0
  features**, NOT Killa customizations. Treat them as core.
- Authoritative divergence docs: `docs/upstream/UPSTREAM_BASELINE.md`,
  `docs/customizations/CUSTOMIZATION_INVENTORY.md`,
  `docs/customizations/CORE_PATCH_REGISTER.md`, `docs/database/CUSTOM_SCHEMA.md`.

## 2. Stack (exact versions matter)

| Layer | Technology |
|---|---|
| Language | PHP ^8.2 |
| Framework | **Laravel ^12** (upgraded from L10 but keeps the **Laravel 10 directory structure** — middleware in `app/Http/Kernel.php`, exceptions in `app/Exceptions/Handler.php`, schedule in `app/Console/Kernel.php`; do NOT migrate to the streamlined L11/12 structure) |
| Frontend | **Laravel Mix 6 / webpack 5** (`webpack.mix.js`). **No Vite, no `npm run build`.** Use `npm run dev`, `npm run watch`, `npm run prod`. |
| UI | **AdminLTE 2 / Bootstrap 3** Blade views + jQuery 3.7, select2, bootstrap-table. **No Inertia.** |
| Livewire | **Livewire v4** — used only for discrete widgets in `app/Livewire` (~15 components: Importer, CustomFieldEditor, LdapSettings, CheckoutTargetPanel, SlackSettingsForm, …). Not the primary UI layer. Default to Blade + standard controller unless extending an existing Livewire component. |
| Charts | **Chart.js v2.9.4** (v2 API — `horizontalBar` exists, no `indexAxis`). Palette via `Helper::defaultChartColors()`. |
| API auth | Laravel Passport, personal access tokens, Socialite |
| SSO/Directory | SAML (`onelogin/php-saml`), LDAP (ext-ldap), SCIM (forked `arietimmerman/laravel-scim-server`), Google 2FA |
| Imaging/PDF | intervention/image, bacon-qr-code, tc-lib-barcode, tcpdf, dompdf, svg-sanitize |
| Backup | spatie/laravel-backup |
| Model validation | `watson/validating` — models self-validate |
| Tests | **PHPUnit 11** (NOT Pest) + paratest; larastan, Pint for style |

## 3. Architecture (the rules that matter when modifying)

### Controllers — two parallel trees
- `app/Http/Controllers/` — web/UI controllers returning **Blade views**.
- `app/Http/Controllers/Api/` — REST/JSON controllers consumed by datatables and
  select2.
- Both share subdirectory groupings: `Assets/`, `Licenses/`, `Users/`,
  `Accessories/`, `Consumables/`, `Components/`, `Kits/`, `Account/`, `Auth/`.

### API responses
- Every API controller returns data through a **Transformer** in
  `app/Http/Transformers/` — never raw model attributes.
- `DatatablesTransformer` wraps paginated results.
- API responses use the standard envelope (see `.ai/rules/api.md`).

### Authorization
- All authorization goes through **Policies** in `app/Policies/`.
- `CheckoutablePermissionsPolicy` is the base for assets, licenses, accessories,
  consumables. Its `checkout()`/`checkin()` accept `$item = null`, so
  `@can('checkout', \App\Models\Asset::class)` works without an instance.
- Permission map lives in `config/permissions.php`.

### Routes
- UI routes: `routes/web.php` **and** per-entity files under `routes/web/`
  (`hardware.php`, `users.php`, `licenses.php`, `accessories.php`,
  `components.php`, `consumables.php`, `kits.php`, `models.php`, `fields.php`,
  `locations.php`). Check both.
- API routes: `routes/api.php`. SCIM routes: `routes/scim.php`.
- **Every UI route should have an inline breadcrumb** via
  `->breadcrumbs(fn (Trail $trail) => ...)` (tabuna/breadcrumbs).
- Some route names contain slashes, not dots: `route('reports/unaccepted_assets')`.

### Full Multiple Company Support (FMCS)
- Gated by `Setting::getSettings()->full_multiple_companies_support == '1'`.
- select2 endpoints (`selectlist()` methods) accept a `companyId` query param:
  ```php
  if ((Setting::getSettings()->full_multiple_companies_support == '1') && ($request->filled('companyId'))) {
      $query->where('table.company_id', $request->input('companyId'));
  }
  ```
- Wired from Blade via `data-company-id="{{ $user->company_id }}"`.

### Select2 AJAX dropdowns
- Use `class="js-data-ajax"` with `data-endpoint="hardware|licenses|consumables|…"`.
- `snipeit.js` auto-initializes them, forwarding `data-company-id` as
  `companyId` and `data-asset-status-type` as `statusType`.

### Checkout redirect flow
- `Helper::getRedirectOption()` reads `$request->redirect_option`. To redirect
  back to the assigned user the form sets `redirect_option=target`,
  `checkout_to_type=user`, `assigned_user={{ $user->id }}`.

### Views
- `$snipeSettings` is shared with every view by `SettingsServiceProvider` — use
  it directly in Blade, never pass `Setting::getSettings()` from controllers.

### Key helpers (`app/Helpers/Helper.php`)
- `Helper::deployableStatusLabelList()`, `Helper::defaultChartColors($i)`,
  `Helper::getRedirectOption($request, $id, $table, $item_id = null)`.
- Note: `defaultChartColors()` returns the Killa 10-color palette (upstream's
  266-entry array was replaced — data-only change).

## 4. Project rules (`.ai/rules/` — load-bearing, enforced)

- **actions.md** — Action classes expose a single static `run()` method.
- **controllers.md** — No DTOs, no repository layer.
- **requests.md** — Form Requests are the validation entry point.
- **models.md** — Models self-validate with watson/validating.
- **migrations.md** — **No foreign-key constraints** in migrations.
- **presenters.md** — Presenters own display/datatable configuration.
- **livewire.md** — Class-based component + separate view file.
- **api.md** — Wrap API responses in the standard envelope.
- **app.md / views.md** — Translation conventions (always use `trans()`).
- **tests.md** — Test env uses array drivers.
- `.ai/rules/index.md` maps file globs → rule files; read it before editing.

## 5. Domain model inventory (`app/Models`, ~50)

Asset, AssetModel, Accessory(+Checkout), Component(+Assignment),
Consumable(+Assignment), License, LicenseSeat, PredefinedKit, User, Group,
Company, Department, Location, Manufacturer, Supplier, Category, Statuslabel,
Depreciation, Maintenance(+Type), CustomField(+Fieldset), Actionlog,
CheckoutAcceptance, CheckoutRequest, Import, ReportTemplate, Setting,
Order/OrderItem, SyncAdapter* (both **upstream v8.8.0**), SCIM/SAML models, Ldap.
Base class: `SnipeModel`.

## 6. Application layer directories

`app/`: Actions, Auth, Console (Commands + Kernel), Enums, Events, Exceptions,
Helpers, Http (Controllers, Middleware, Requests, Transformers), Importer, Jobs,
Listeners, Livewire, Mail, Models, Notifications, Observers, Policies,
Presenters, Providers, Rules, Services, SyncAdapters, Traits, View.

Notable console commands (~60): `LdapSync`, `LdapTroubleshooter`,
`SendExpectedCheckinAlerts`, `SendExpirationAlerts`, `SendInventoryAlerts`,
`SendUpcomingAuditReport`, `ObjectImportCommand`, `SystemBackup`,
`CreateAdmin`, `PaveIt`, `Purge`, `RotateAppKey`, `SyncAssetCounters`,
`DedupeAssets`, plus upstream `PullInventory` / `PushInventory` (sync adapters).

## 7. Testing & CI

- PHPUnit 11, three testsuites: `tests/Unit`, `tests/Feature` (~67 dirs, organized
  per domain: Assets, Checkouts, Checkins, Api, Importer, Ldap, Fmcs, Livewire,
  Blade, Console…), and `packages/floating-licenses/tests`.
- CI workflows (`.github/workflows/`): tests on **MySQL, PostgreSQL, SQLite**,
  larastan static analysis, CodeQL, docker builds (alpine/ubuntu).
- Behavior/logic changes should come with tests; layout-only changes don't need
  them. Create tests with `php artisan make:test --phpunit`.

## 8. Style & workflow gates (mandatory after PHP edits)

1. Run `vendor/bin/pint --dirty --config .ci/pint.json --format agent` before finalizing PHP changes.
2. Clear caches after config/route changes: `php artisan optimize:clear`.
3. If a frontend change doesn't show up: `npm run dev` or `npm run watch` (Mix,
   not Vite).
4. Create new files via `php artisan make:*` with `--no-interaction`.
5. PHP style: constructor property promotion, explicit return types and param
   type hints, curly braces always, TitleCase enum keys, PHPDoc over inline
   comments, array-shape types in PHPDoc.

## 9. Deployment context

- Snipe-IT is AGPLv3 open source; can be self-hosted or run on
  **Grokability-managed hosting** (stock-source only — code changes are not
  possible there, only supported `.env` config). This local repo, however, is a
  full fork ("Killa Asset") and is free to modify.
- Docker support in `docker/` (apache/fpm, alpine/ubuntu). Upstream-only
  leftovers (Ansible playbooks, Vagrant/Heroku/installer scripts, Crowdin,
  psalm/phpmd configs, pa11y) were pruned in the 2026 repo cleanup — see
  `docs/upgrades/UPGRADE_HISTORY.md`.
- Served locally by Laravel Herd at `https://snipe-it.test`.

## 10. Constraints & traps for anyone proposing changes

- **Don't add dependencies** without asking; check composer.json/package.json first.
- **Don't migrate** to the new Laravel structure, Vite, Pest, Inertia, or a newer
  Chart.js — the project intentionally stays on Mix, AdminLTE 2, Chart.js v2,
  PHPUnit, and the L10 layout.
- **No FK constraints** in migrations; **no DTO/repository layers**;
  **no raw model returns** from API controllers (use Transformers);
  **policies only** for authorization.
- Reuse before writing: check `Helper`, existing Livewire components,
  transformers, and presenters first (YAGNI / minimal-diff philosophy).
- UI changes must respect the **killa-design** design-system skill.
- The fork's custom features (floating-licenses package, perpetual licenses,
  LDAP deactivate-missing, killa-v2 theme) are first-class parts of the
  codebase — modifications must not break them.

## 11. Quick-start for a proposing agent

To suggest a modification here you must know:
1. Which layer it touches: Blade+web controller (UI), Api controller+Transformer
   (JSON/datatables), Livewire (only for widgets), console command, or the
   floating-licenses package.
2. Which rules apply (see §4) — especially policies, transformers, no-FK
   migrations, Form Requests.
3. The build/test gate: `npm run dev` for frontend, `vendor/bin/pint`,
   PHPUnit feature/unit tests, `php artisan optimize:clear`.

## 12. Session history

Work sessions are logged in `docs/sessions/` (index: `docs/sessions/README.md`),
maintained by the `skills/saver` skill (session log → docs update → commit at
≥50 changes). Latest: 2026-10-05 killa-v2 UI/UX redesign & hardening.
