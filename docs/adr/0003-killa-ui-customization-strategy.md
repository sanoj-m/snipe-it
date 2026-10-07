# ADR-0003: Killa UI customization strategy

**Status:** Accepted

## Context

Killa needs a branded, modernized UI ("killa-v2 / Atelier") on a codebase that
is AdminLTE 2 / Bootstrap 3 / Blade, built with Laravel Mix, and continuously
merged with upstream. Any approach that rewrites upstream views or adds a build
pipeline would conflict on nearly every upgrade.

## Decision

1. **Zero-build additive CSS overlay**: committed plain stylesheets
   `public/css/killa-v2*.css` (6 files) loaded once after `all.css` in both
   layouts. No new build steps; deliberately outside webpack so upstream merges
   never wipe the theme.
2. **Token discipline**: all visual values are `--k-*` CSS custom properties
   with a single source of truth; no inline hex/fonts.
3. **Change hierarchy**: CSS override → shared partial → `@include` → view
   edit (last resort). Never copy an upstream view for a small change.
4. **Marking**: custom Blade blocks carry `{{-- [killa-v2] --}}`; view edits
   are registered (KCP-025…029).
5. **Governance**: all UI work follows `skills/killa-design/SKILL.md`
   (Operate-mode consistency, anti-slop gates, 768px responsive floor, a11y
   checklist).

## Alternatives considered

- **Rebuild views per page** — unmaintainable across upstream merges; rejected.
- **Tailwind / component framework** — violates the AdminLTE2/BS3 constraint
  and adds a build dependency; rejected.
- **Theming via upstream's own skin settings** — far too limited (logo +
  accent color only).

## Consequences

- Most upstream pages inherit the theme with zero per-page work (verified on
  6 spot-checked pages; see UI_AUDIT).
- Known drift exists and is tracked: duplicate tokens, dead flyout CSS,
  SKILL.md vs reality, ~251 `!important`s (UI_AUDIT UI-A-01…05).
- Layout edits (loader, footer deletions, inline flyout JS) remain registered
  core patches.

## Upgrade implications

CSS overlay files are untouched by merges. Conflicts concentrate in
`layouts/default.blade.php` / `basic.blade.php` and `dashboard/top-boxes`
(KCP-025…027) — resolve per ADR-0001, keeping the loader block and re-auditing
footer deletions. When upstream changes markup classes, audit tokens/cascade
rather than patching views.
