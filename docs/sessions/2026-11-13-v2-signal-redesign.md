# 2026-11-13 — v2 "Signal" redesign (skills-guided) — addendum to the v2.1 log

## What happened

Owner asked for a *bolder* redesign than the v2.1 polish pass (which read as
"same old" on live) and supplied two skills to guide it:
`nextlevelbuilder/ui-ux-pro-max-skill` and `leonxlnx/taste-skill`. Both were
converted+registered as personal-market plugins (links in the session reply).

Skills application — honest note: taste-skill's browser gate tooling
(gate.mjs etc.) targets standalone marketing pages (Node 22 + Chrome), not a
Laravel CSS overlay, so its **checklist** was applied by hand (one system, no
violet, motion discipline, a11y) and ui-ux-pro-max contributed its **three-layer
token architecture** (primitive → semantic → component) and state discipline.

## Changes (commit `70fee55364`, deployed `v8.8.0+d20261113_125044`)

All in `public/css/killa-v2.css` (token-driven; dark block updated in-file):

- **Charcoal brand rail in BOTH schemes** — pure token swap
  (`--k-sidebar-*` in `:root` now carry the former dark values; dark block
  deepens bg to `#101216`). All rail styling still owned by
  `killa-v2-sidebar.css` (KCP-049) — zero structural edits.
- **Tinted signal tiles** — `.k-tile` surfaces are a 12% (light) / 18% (dark)
  color-mix of each tile's signal color over `--k-surface`, tinted borders
  (`--k-tint-border`), signal-colored labels; replaces the v2.1 2px top-edge.
- **No-violet palette** (taste rule) — `--k-tile-purple` `#7c5cbf` → navy
  `#34558b` (dark `#9a80d4` → `#6f8fc0`).
- **Brand links** — new semantic layer `--k-link` `#c52d21` (AA on white) /
  `#f4716a` (dark); `body a` default color replaces upstream Bootstrap blue.
  Component contexts (sidebar/navbar/breadcrumb/buttons) keep their own rules.
- **Titles** 20px → 24px display.
- **A11y** — global `:focus-visible` accent ring on links; global
  `prefers-reduced-motion` guard (120ms cap).
- Stale comments fixed (removed reference to the deleted CHARCOAL NAV RAIL
  block; sidebar comment updated).

## Verified live

- `killa-v2.css?v=…125044`: 200, `--k-link` ×6, `#17191d` rail, `#34558b` navy,
  tinted-tile color-mix rule all present; `/login` HTML references the new
  version stamp (Cloudflare busted).
- Brace balance 187/187 light, 112/112 dark.
- Cross-file token consumers checked: datepicker `--k-sidebar-text-active`
  (overlays) improved (white-on-red), widgets direct-chat rules consistent.

## Rollback

`git revert 70fee55364` + redeploy.

## Addendum — owner review (2026-11-13 ~13:25 UTC)

Owner feedback: keep the rail LIGHT in light mode (no dark sidebar), and add
a topbar toggle to compare old vs new. Shipped as commit `9f537253ad`,
deployed `v8.8.0+d20261113_125044` → `v8.8.0+d20261113_132514`:

- Rail reverted to light in light mode; charcoal remains in dark mode only.
- **KCP-054 New/Classic toggle**: segmented pill injected into `.left-navblock`
  (top-left topbar) by new Killa-owned `public/js/killa-design-toggle.js`;
  Classic = v2.1 base (white tiles + color edges, stock links, 20px titles),
  New = Signal. `html[data-design='classic']` CSS gating → instant switch, no
  reload; persists in localStorage; pre-paint stamp script prevents flash;
  hidden on phones.
- Token hygiene fix: datepicker on-accent text now uses `--k-accent-contrast`
  instead of misusing `--k-sidebar-text-active` (overlays.css).
- Registered KCP-054; inventory UI-02 updated.
- Verified live: toggle JS 200, 18 gated CSS rules, light rail token, pages
  referencing the new version stamp.

Rollback: `git revert 9f537253ad 70fee55364` + redeploy.
