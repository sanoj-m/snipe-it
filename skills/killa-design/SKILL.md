---
name: killa-design
description: "Killa Asset design system authority. Use for ANY UI/UX work in this repo — new pages, redesigns, restyling, component polish, UI audits. Combines ui-ux-pro-max design intelligence, impeccable design-consistency discipline, and hallmark anti-AI-slop gates, adapted to this codebase (AdminLTE 2 / Bootstrap 3 / Blade / Laravel Mix, upstream-fork constraints)."
---

# Killa Design Skill (v2 UI)

Decision authority for all visual work on Killa Asset. Merged from three
sources, in this precedence order when they conflict:

1. **impeccable** (pbakaus/impeccable) — consistency discipline. This is an
   **Operate-mode** product (asset management: scanability, consistency,
   native expectations outrank expression). Brand lives in precise details,
   not decoration.
2. **hallmark** (nutlope/hallmark) — anti-AI-slop gates. Output must look
   made, not generated.
3. **ui-ux-pro-max** (nextlevelbuilder) — reasoning rules, palette/type
   intelligence, pre-delivery checklists.

## Hard constraints of this codebase (never violate)

- AdminLTE 2 / Bootstrap 3 Blade views. No Tailwind, no CSS-in-JS, no Inertia.
- Frontend builds with Laravel Mix (`npm run dev`). Prefer **zero-build**
  changes: the v2 theme is seven plain committed stylesheets in `public/css/`,
  loaded in order in `layouts/default.blade.php` and `layouts/basic.blade.php`
  after `all.css`:
  0. `killa-v2-fonts.css` — self-hosted Inter/Inter Tight @font-face
     (files in `public/fonts/vendor/inter/`, latin variable woff2)
  1. `killa-v2.css` — tokens (`:root` + dark block) + app shell (sidebar,
     navbar, flyout menus, login)
  2. `killa-v2-forms.css` — form controls, inputs, select2 chrome
  3. `killa-v2-tables.css` — bootstrap-table chrome, .table variants, print
  4. `killa-v2-widgets.css` — dashboard widgets, timeline, panels, progress
  5. `killa-v2-overlays.css` — modals, dropdowns, popovers, tooltips, pickers
  6. `killa-v2-dark.css` — dark-mode completeness layer (ONLY
     `html[data-theme='dark']` rules + dark token overrides)
  They are cache-busted with `?v={{ config('version.app_version') }}` — busts
  on each release, zero per-request disk reads. Blade edits are allowed; new
  build steps are not.
- This repo is a fork that merges upstream Snipe-IT releases. All theme work
  must be **additive and override-based** (CSS cascade + tokens), so upstream
  merges never wipe it. Mark custom Blade blocks with
  `{{-- [killa-v2] --}}` comments.
- Keep every layout, route, table id, form field name, and JS hook as-is.
  We restyle and refine structure; we do not re-architect pages.

## Design tokens (single source of truth)

All visual decisions reference tokens defined at the top of
`public/css/killa-v2.css`. Never inline hex/font values in Blade or CSS
outside the token block (hallmark "locked tokens" gate). If a needed value
is missing, add a named token, then use it.

Token single-source-of-truth rule: each `--k-*` token is defined exactly
once — the light value in `killa-v2.css` `:root` (or in the owning domain
file's `:root` for domain-private tokens), and the dark value ONLY in the
`html[data-theme='dark']` block of the same file, or in `killa-v2-dark.css`
for shared tokens (`--k-input-disabled-bg`, `--k-scrollbar-thumb`). Never
define the same token in two files.

Current token set (v2 "Atelier" theme):
- Color: `--k-bg` (cool neutral page), `--k-surface` (cards),
  `--k-border`, `--k-text`, `--k-text-muted`, `--k-accent` (Killa red
  `#E63B2E`, from the Killa Design logo — the ONE brand color),
  semantic `--k-success/warning/danger/info` muted to match.
- Type: `--k-font` system-first stack (self-hosted Inter via
  `killa-v2-fonts.css` / `public/fonts/vendor/inter/`, fallback
  -apple-system/Segoe UI). 13px base stays (dense admin), headings roman
  only, weight carries hierarchy — never italic headers (hallmark gate).
- Shape: `--k-radius` 12px cards / `--k-radius-sm` 6px inputs,
  `--k-shadow-1` hairline + faint lift, no heavy AdminLTE drop shadows.
- Space: 4px grid; card padding 16/20px; section rhythm 24px.

When `!important` is justified: only to beat AdminLTE 2 / Bootstrap 3 rules
that themselves carry `!important` (e.g. collapsed-sidebar flyout
positioning) — that is the blanket case. Any other new `!important` needs a
comment naming the rule it defeats.

## The six operating rules (impeccable + hallmark, condensed)

1. **Scanability first (Operate mode).** Every screen is a task surface.
   Align numbers right, labels sentence-case, one primary action per panel.
2. **Pre-emit self-critique.** Before delivering any UI change, score 1–5:
   Philosophy, Hierarchy, Execution, Specificity, Restraint, Variety.
   Any <3 → revise before shipping.
3. **No invented content.** No fake metrics, fake testimonials, or placeholder
   stats. Real data or nothing.
4. **Consistency sweep.** A change to a component (button, table, card,
   modal, form row) must be applied via the shared CSS/component so every
   page inherits it. Never style one page's copy of a shared element.
5. **Restraint.** No gradients-as-decoration, no emoji icons, no purple AI
   aesthetic, no glassmorphism, no animated confetti. Depth comes from
   hairline borders + one shadow level + whitespace.
6. **Responsive floor.** No horizontal scroll at 768px; tables keep their
   existing responsive behavior; tooltips/dropdowns must not clip.

## Anti-slop checklist (run before every UI deliverable)

- [ ] No emojis as icons; use the existing Font Awesome set
- [ ] cursor:pointer on all clickable elements
- [ ] All interactive elements have hover + focus-visible states
- [ ] Contrast ≥ 4.5:1 for text (muted text ≥ 3:1 for large/secondary)
- [ ] No italic headings; hierarchy via weight/size/spacing
- [ ] No hardcoded colors/fonts outside the token block
- [ ] Empty states and error states styled, not just the happy path
- [ ] Print styles not broken (Snipe-IT prints labels/reports)

## Process for redesign work

1. **Audit first** — list inconsistencies (spacing, color, typography,
   component duplication) with file:line evidence before changing anything.
2. **Tokens → global layer → page.** Fix at the token/cascade level first;
   only touch a Blade file when CSS cannot express the fix.
3. **Bounded verification.** One batched screenshot/defect pass (desktop +
   768px), fix in one batch, at most one confirm round. No open-ended QA.
4. Commit theme + page changes separately from upstream merges.
