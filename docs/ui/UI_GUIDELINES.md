# UI Guidelines — Killa Asset (quick reference)

Condensed from the design-system authority. **Full authority:
[skills/killa-design/SKILL.md](../../skills/killa-design/SKILL.md)** — read it
before any non-trivial UI work. Findings tracker: [UI_AUDIT](UI_AUDIT.md).

## Hard constraints

- **Zero-build, additive CSS only.** Theme lives in committed stylesheets
  `public/css/killa-v2*.css`, loaded after `all.css`. No new build steps;
  Laravel Mix only for upstream assets (`npm run dev`).
- **AdminLTE 2 / Bootstrap 3 / Blade only.** No Tailwind, no CSS-in-JS,
  no Inertia.
- **Additive and override-based.** This repo merges upstream releases; never
  delete or restructure upstream markup, routes, table ids, form field names,
  or JS hooks. Restyle, don't re-architect.

## Token discipline

- All visual values are `--k-*` tokens at the top of `public/css/killa-v2.css`
  — **single source of truth**. Never inline hex/font values in Blade or CSS.
- Need a value that's missing? Add a named token, then use it.
- One brand color: `--k-accent` (Killa red `#E63B2E`). Semantic colors muted.

## Marking

- Mark custom Blade blocks with `{{-- [killa-v2] --}}` comments.
- Register any core-view edit in `docs/customizations/CORE_PATCH_REGISTER.md`.

## Change hierarchy (for touching UI)

CSS override → shared partial → `@include` → **view edit (last resort)**.
Never copy an entire upstream view to make a small change; extract or override
the smallest unit that expresses the change.

## Conventions

- **Breadcrumbs:** every UI route gets an inline breadcrumb
  (`->breadcrumbs(fn (Trail $trail) => ...)`).
- **`trans()` always** — no hardcoded UI strings.
- **`!important` discipline:** each use must be justified (third-party
  specificity wars only). Audit count is already high (~251) — do not add
  casually.

## Responsive & accessibility floor

- No horizontal scroll at **768px**; tables keep existing responsive behavior;
  tooltips/dropdowns must not clip.
- Contrast ≥ 4.5:1 for text (≥ 3:1 for large/secondary).
- Hover + focus-visible states on every interactive element; `cursor:pointer`
  on clickables; Font Awesome icons only (no emoji); no italic headings.
- Style empty and error states, not just the happy path; don't break print.

## Process

1. Audit first — file:line evidence before changing anything.
2. Fix at token/cascade level first; Blade only when CSS can't express it.
3. Bounded verification — one batched screenshot pass (desktop + 768px).
4. Commit theme/page changes separately from upstream merges.
