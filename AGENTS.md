# AGENTS.md — Killa Asset (Snipe-IT fork)

**Read this first.** Then read `PROJECT_HANDOFF.md` before any non-trivial work.

## What this is

Killa Asset is a fork of **Snipe-IT v8.8.0** (upstream: `grokability/snipe-it`,
remote `upstream`). It adds exactly four customization tracks:

1. **Floating Licenses** — `packages/floating-licenses` (Composer path package)
   + `[floating-licenses addon]` fenced hooks in core files.
2. **Perpetual licenses** — `licenses.perpetual` flag.
3. **LDAP deactivate-missing** — deactivate instead of delete missing LDAP users.
4. **Killa branding + killa-v2 theme** — string swaps + zero-build CSS overlays
   in `public/css/killa-v2*.css`.

`Order`/`OrderItem`/`SyncAdapter*`/`PullInventory`/`PushInventory` are **upstream
v8.8.0 features — NOT custom**. Don't isolate or "fix" them.

## Golden rule

> **Protect upstream compatibility.** Minimum core patching, maximum extension
> isolation. Prefer a slightly less elegant extension over an elegant change
> that forks upstream. Never resolve upgrade conflicts with plain `ours`/`theirs`:
> the goal is *new upstream behavior + reapplied Killa intent*. Upstream security
> fixes always win; redesign Killa code around them.

## Stack (do not migrate these)

PHP 8.2 · Laravel 12 with **Laravel 10 directory structure** · Laravel Mix 6
(**no Vite, no `npm run build`** — use `npm run dev`/`watch`/`prod`) ·
AdminLTE 2 / Bootstrap 3 / jQuery / select2 / bootstrap-table · Livewire v4
(widgets only) · Chart.js v2 API · PHPUnit 11 (**not Pest**) · Pint · Larastan.

## Directory map

| What | Where |
|---|---|
| Web controllers (Blade) | `app/Http/Controllers/` |
| API controllers (JSON) | `app/Http/Controllers/Api/` (always via Transformers) |
| Policies (all authorization) | `app/Policies/` |
| Routes | `routes/web.php` + `routes/web/*.php` + `routes/api.php` (every UI route needs a breadcrumb) |
| Floating Licenses | `packages/floating-licenses/` (README there is authoritative) |
| Theme layer | `public/css/killa-v2*.css` (zero-build, loaded after `all.css`) |
| Design rules | `skills/killa-design/SKILL.md` (read before ANY UI work) |
| Project rules | `.ai/rules/index.md` → per-area rules (globs map; **read matching rules before editing**) |
| Killa divergence docs | `docs/upstream/`, `docs/customizations/`, `docs/database/`, `docs/upgrades/` |

## Pre-flight check (required before editing)

1. `PROJECT_HANDOFF.md`, this file, `.ai/rules/index.md` + matching rules.
2. `docs/customizations/CUSTOMIZATION_INVENTORY.md` and
   `docs/customizations/CORE_PATCH_REGISTER.md` — is the file you're touching a
   registered core patch? Preserve the fenced `[floating-licenses addon]` /
   `[killa-v2]` blocks.
3. Related tests; for UI also `skills/killa-design/SKILL.md`; for floating
   licenses also `packages/floating-licenses/README.md`.
4. Classify the change (below) and state the classification in your plan.

## Change classification (state before significant work)

```
Feature:
Files likely affected:
Upstream core touched: YES/NO     ← prefer NO
Database change: YES/NO           ← no FK constraints; use killa_ infix in migration names
UI/API/Permission change: YES/NO
Upgrade risk: LOW/MEDIUM/HIGH/CRITICAL
Tests required:
Documentation required:
```

## Hard rules

- No DTOs/repository layers; Form Requests for validation; models self-validate
  (watson/validating); Transformers for all API output; policies/gates for all
  authorization; `trans()` for all UI strings; breadcrumbs on UI routes.
- No new dependencies without asking. No FK constraints in migrations.
- Mark any new core-file insertion with a fenced comment block naming the
  feature, and **register it in `docs/customizations/CORE_PATCH_REGISTER.md`**.
- New Killa code goes in the floating-licenses package or a clearly Killa-owned
  location — not scattered through upstream files.
- Don't delete or weaken failing tests; report PRE-EXISTING vs REGRESSION.

## Commands

```bash
vendor/bin/pint --dirty --format agent        # after ANY PHP change (required)
php artisan optimize:clear                    # after config/route changes
vendor/bin/phpunit --testsuite Unit           # tests
vendor/bin/phpunit --testsuite Feature
vendor/bin/phpunit --testsuite FloatingLicenses
vendor/bin/phpstan analyse                    # static analysis (if configured)
npm run dev                                   # frontend build (Mix, not Vite)
scripts/check-upstream-conflicts.sh vX.Y.Z    # before any upstream merge
scripts/test-upgrade-regression.sh            # Killa smoke suite
```

NOTE: if PHP/composer are not on PATH (Windows maintainer machine), state that
the gates could not be executed and list the exact commands for the user.

## Upgrading upstream

Never run a bare `git merge upstream/master`. Follow
`docs/upgrades/UPSTREAM_UPGRADE_RUNBOOK.md`: fetch tag →
`scripts/check-upstream-conflicts.sh` → upgrade branch → merge tag → resolve
(three-way: BASE / UPSTREAM / KILLA intent) → full gates → record in
`docs/upgrades/UPGRADE_HISTORY.md` and update `docs/upstream/UPSTREAM_BASELINE.md`.

## Post-change report (required for substantial changes)

```
Purpose / Files changed / Upstream core files modified: YES/NO (Patch IDs if yes)
Database changes / Security impact / Upgrade-compatibility risk
Tests added / Tests executed + results / Documentation updated
Known limitations / Rollback
```
