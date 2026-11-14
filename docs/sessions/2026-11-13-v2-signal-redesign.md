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


## Addendum 2 — v2.2 polish round (2026-11-13, evening)

Owner review pass over the v2.1/Signal UI. Seven items, all shipped in one
commit; theme work in the zero-build overlay layer, two fenced core patches.

1. **Universal thin scrollbars** — the old rules were `body ::-webkit-scrollbar`
   (Chrome-only path) + `scrollbar-width` on `body`/`.fixed-table-body` only
   (Firefox doesn't inherit → fat bars elsewhere). Replaced with a universal
   `*, html { scrollbar-width: thin }` + bare `::-webkit-scrollbar` (8px)
   block in killa-v2.css; per-surface rules (sidebar, tables, notifications)
   kept.
2. **Table headers tightened (taste-skill)** — thead cells dropped
   `text-transform: uppercase` + `letter-spacing: .06em`, size 10.5→11.5px.
   Sentence case, no wide tracking.
3. **List loader fixed + redesigned** — `.fixed-table-loading` was z-index 10,
   slipping under sticky/pinned cells (read as faint text bleeding through
   rows). Now z-index 60, 92% surface veil; the `<h2>`+fa-spin message is
   normalized off the content-heading scale (12.5px/600, muted, accent
   spinner); bootstrap-table's bouncing dots hidden; reduced-motion exemption
   so the spinner never freezes.
4. **Topbar redesign (ui-ux skill)** — 56px token-driven bar
   (`--k-header-h`): flex row on desktop, ghost-box 34px icon shortcuts with
   accent-tint active state, pill lookup field (input+go share a surface),
   "Create" as the one accent-tinted primary pill, avatar+name ghost box,
   brand block full-height 15px display type. Dark mode via tokens.
5. **Header/sidebar gap fix** — root cause: body has no `fixed` class, so the
   header scrolled away while the rail stayed `position:fixed; top:50px`.
   Header is now fixed full-width (z 830 over rail 810), rail docks at
   `--k-header-h`, content padding-top 56px (112px mobile wrap, mirroring
   AdminLTE `.fixed`), print falls back to absolute. Upstream navbar-offset
   (230/50px) and collapsed-logo-shrink rules neutralized; rail no longer
   tracks the header and vice versa.
6. **List loading everywhere** — same global loader component, fixed once in
   killa-v2-tables.css.
7. **Multi-host links (KCP-050 amendment)** — IP browsing kept login on the
   IP but every baked `config('app.url')` link (logo, table cell formatters,
   favicon/uploads via the public disk URL compiled at config-cache time)
   jumped to the public domain. The `[killa multi-host]` fence now also
   re-roots `app.url` + `URL::forceRootUrl` + the public disk URL path onto
   the incoming host. Both directions hold; console never matches (host
   `localhost`), so mails/QR/CLI keep the canonical APP_URL.

New patch: **KCP-055** — `partials/bootstrap-table.blade.php` sticky chrome
offsets: `stickyHeaderOffsetY = 18 + header height` (activation threshold AND
clone position both derive from it — verified in the bundle source), and
`pinTopScrollbarIfScrolled` pins/engages at the header bottom edge.

Files: `public/css/killa-v2.css` (token + thead + navbar shadow + v2.2 block),
`public/css/killa-v2-sidebar.css` (rail top token, header margin rules
dropped, collapsed-logo shrink removed), `public/css/killa-v2-tables.css`
(loader), `public/css/killa-v2-notifications.css` (menu top offset),
`app/Providers/AppServiceProvider.php` (KCP-050 amend),
`resources/views/partials/bootstrap-table.blade.php` (KCP-055),
register + this log.

Not run locally: PHP/composer/pint not on this machine — PHP change is a
fenced 15-line additive block (syntax eyeballed, no new imports; `URL`
facade already imported). CSS brace balance verified 234/234, 71/71,
101/101, 13/13.

Known watch items: mobile (≤767px) assumes the bar wraps to two rows like
upstream `.fixed` (padding-top 112px) — verify on a phone; table fullscreen
mode stacking vs the fixed bar unverified.


## Addendum 3 — Classic toggle reworked to genuine stock theme (2026-11-13, late)

Owner feedback: the KCP-054 "Classic" toggle only toned Signal down to the
v2.1 base — it never showed the ORIGINAL Snipe-IT look. Root cause: Classic
was expressed as CSS gating on top of the still-loaded v2 overlay layer.

Reworked to a server-side switch:

- Choice persists in a **`killa_design` cookie** (JS-set; excepted in
  `EncryptCookies` — Laravel would decrypt-fail it to null otherwise).
  localStorage `killa.design` kept as a legacy mirror; the pre-paint script
  migrates an old localStorage-only choice into the cookie with one reload.
- `default.blade.php` gates on the cookie: Classic skips ALL killa-v2
  overlay stylesheets (fonts/theme/sidebar/forms/tables/widgets/overlays/
  dark/notifications) and `killa-sidebar.js`; `killa-ux.css` stays (scoped
  to .kx wrappers).
- New Killa-owned partial `layouts/partials/killa-classic-sidebar.blade.php`
  = the stock upstream sidebar block preserved verbatim from b58de20859^
  (pre-KCP-049), rendered in Classic instead of the `.ks-*` rail — stock
  `.main-sidebar`/`.sidebar-menu` treeview driven by upstream's own JS.
  Verified menu parity (no Killa-only items missing).
- Toggle JS (`killa-design-toggle.js`) rewritten: writes cookie + mirror,
  reloads the page (live swap impossible — different asset set + markup);
  injects neutral fallback pill styles in Classic (v2 CSS absent there).
- Also removed the orphaned `</aside>` leftover at the sidebar include site
  (mismatched closer from the KCP-049 replacement).

Register: KCP-054 amended (new mechanism + files), KCP-049 noted.

Rollback: revert this round's commit + redeploy.


## Addendum 4 — KCP-056 profile-dropdown topbar user area (2026-11-13, late)

Owner supplied the kokonutui `profile-dropdown` React component and asked for
it as the topbar user avatar area. Stack is fixed (AGENTS.md: Blade + jQuery +
zero-build CSS, no React/Tailwind/new deps), so the component's **design** was
ported, not the code:

- **Trigger** (v2 only): pill card with right-aligned name-over-email stack,
  gradient-ring avatar (accent → orange → teal sweep; ring works for both
  gravatar images and the no-gravatar icon fallback), and the component's
  bending-line caret (inline SVG, muted → accent + scale when the Bootstrap
  dropdown opens — driven by the stock `.open` class, no new JS).
- **Dropdown** (v2 only): 264px card with an identity header (large ring
  avatar + name + email), rounded roomy item rows with icon color transitions,
  the gradient hairline divider, and a red-tinted sign-out row
  (`k-user-signout` on the existing logout `<li>` — same POST logout form,
  unchanged).
- **Menu items unchanged** — all permission gates (`self.profile`,
  `viewRequestable`, `self.api`, LDAP password suppression) and routes kept
  verbatim; the React sample's Model/Subscription/Terms rows don't map to
  Snipe-IT concepts and were dropped.
- **Classic untouched**: the whole block is gated on the `killa_design`
  cookie — Classic renders the stock trigger + dropdown verbatim and none of
  the new CSS loads there (KCP-054 gate).
- Mobile: meta stack + caret are `hidden-xs`; trigger collapses to a
  round avatar button ≤767px.

Files: `resources/views/layouts/default.blade.php` (fenced
`[killa-v2 profile-dropdown]` blocks), `public/css/killa-v2.css` (KCP-056
section appended; token-driven, dark mode via the in-file dark `:root`).
Register: KCP-056 added.

Not run locally: no PHP/composer on this machine — Blade change is additive
markup gated on the same cookie already used by KCP-054; verified server-side
via `view:cache` compile + authenticated curl of both designs.


## Addendum 5 — dropdown language unified across the topbar (2026-11-14)

Owner accepted the KCP-056 follow-up: the profile-dropdown component language
now covers all three topbar dropdowns, not just the user menu. Pure CSS in the
Killa-owned layers (`killa-v2.css`, `killa-v2-notifications.css`) — zero
upstream markup touched, zero new core patches; KCP-056 register entry amended.

- **Create menu** — panel becomes a padded rounded card (220px+, `var(--k-radius)`,
  8px offset from the bar); item rows get the rounded roomy treatment (8px 12px,
  `--k-radius-chip`, flex gap, 12.5px/500); icons muted → accent on hover;
  the current-target row (`/hardware/create` etc.) gets a quiet accent tint
  matching the topbar's active-shortcut state.
- **Alerts / notifications panel** — panel padding + rounded card; the solid
  header band becomes a small-caps muted section label; rows swap full-bleed
  hairline separators for rounded margin-spaced items; deprecation warning
  banners keep their warning identity but join the card rhythm (rounded,
  inset). Scroll containment, SlimScroll suppression, and the fixed-position
  offset from the v2.2 header are unchanged.
- Classic design unaffected (layers don't load there, KCP-054 gate).
