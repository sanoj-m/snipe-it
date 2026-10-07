# Architecture — Killa Asset (Snipe-IT fork)

Layered view of how Killa sits on top of upstream Snipe-IT. Companion docs:
[UPSTREAM_BASELINE](../upstream/UPSTREAM_BASELINE.md) ·
[CUSTOMIZATION_INVENTORY](../customizations/CUSTOMIZATION_INVENTORY.md) ·
[CORE_PATCH_REGISTER](../customizations/CORE_PATCH_REGISTER.md) ·
[ADR-0001](../adr/0001-upstream-compatibility-strategy.md)

## Governing policy

> **Minimum core patching, maximum extension isolation.** Prefer a slightly
> less elegant extension over an elegant change that forks upstream. Every
> modification to an upstream-controlled file is a registered, fenced patch
> (CORE_PATCH_REGISTER); everything else lives in Killa-owned locations.

## Layers (bottom → top)

| Layer | What | Location | Owned by |
|---|---|---|---|
| Upstream core | Snipe-IT v8.8.0 — controllers, models, views, transformers, policies, routes | `app/`, `resources/`, `routes/`, `config/` | upstream (Killa patches registered) |
| Killa extension points | Registered, fenced hunks inside upstream files | ~15 core files, `[floating-licenses addon]` / `[killa-v2]` markers | Killa, registered |
| Floating-licenses package | Self-contained Composer path package (~5,700 LOC) | `packages/floating-licenses/` | Killa, isolated |
| killa-v2 theme layer | Zero-build additive CSS overlays + design-system skill | `public/css/killa-v2*.css`, `skills/killa-design/` | Killa, isolated |

Higher layers may hook lower layers only through the extension mechanisms
below. Lower layers never reference upper layers except via registered patches.

## The four customization tracks

| Track | Shape | Isolation level |
|---|---|---|
| Floating Licenses | Path package + fenced core hunks | Mostly isolated; core hunks registered (KCP-001…023) |
| Perpetual licenses | Small core patches (model, controllers, importer, view) | Core patches, currently unmarked |
| LDAP deactivate-missing | One model method + sync hook + settings UI | Core patches, tail-appended |
| Branding + killa-v2 theme | CSS overlay files + string swaps + layout edits | CSS isolated; branding hunks unmarked |

## Extension mechanisms actually used

| Mechanism | Where | Notes |
|---|---|---|
| Composer path package + auto-discovered service provider | `packages/floating-licenses`, root `composer.json` | Provider loads migrations/routes/views/lang, registers `floating-licenses:expire` |
| Gate injection into `config('permissions')` at runtime | `FloatingLicensesServiceProvider` | Core `config/permissions.php` untouched; group/user permission pages pick up `floating_licenses.*` automatically |
| Fenced core hunks | `// [floating-licenses addon] BEGIN/END` (PHP), `{{-- [floating-licenses addon] --}}` / `{{-- [killa-v2] --}}` (Blade) | Convention for surviving upstream merges: take upstream side, re-apply hunks |
| CSS overlay (zero-build) | `public/css/killa-v2*.css` loaded after `all.css` in both layouts | Cascade + `--k-*` tokens; deliberately outside webpack so upstream merges never wipe it |
| Data-only core swap | `Helper::defaultChartColors()` palette | Replaces array contents, no code deleted |

## Dependency direction

```
packages/floating-licenses ──depends on──▶ core models (License, User, Setting)
killa-v2 CSS            ──overrides──▶    AdminLTE 2 / Bootstrap 3 markup
core files              ──fenced calls──▶ \SnipeIt\FloatingLicenses\* (guarded; no-op when master switch off)
```

Core never *requires* the package to function: every fenced call is gated on
`floating_licenses_enabled` and no-ops when off (but the package must stay
installed — the classes are referenced; see package README uninstall guide).

## Where new Killa code goes

1. Floating-licenses feature → inside `packages/floating-licenses/`.
2. New isolated feature → a clearly Killa-owned location (new package or
   `app/` namespace that upstream does not control).
3. Core-file edit → last resort; must be fenced, minimal, and registered in
   CORE_PATCH_REGISTER.md.
