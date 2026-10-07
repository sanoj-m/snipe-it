# Session: killa-v2 UI/UX redesign & hardening — 2026-10-05

## Goal

Ground-up redesign of the Killa Asset UI ("v2") without losing any
functionality and without forking upstream layouts: new design skill, full
theme system, dashboard + sidebar redesigns, dark-mode completeness, and an
iterated defect-fix loop driven by real screenshots of the live site.

## What was done

### Design foundation
- `skills/killa-design/SKILL.md` — combined design authority distilled from
  three external sources: ui-ux-pro-max (reasoning rules/checklists),
  impeccable (Operate-mode consistency discipline), hallmark (anti-AI-slop
  gates), adapted to AdminLTE 2/BS3 + zero-build + upstream-merge survival.
- `public/css/killa-v2.css` — "Atelier" token system (`--k-*`: surfaces,
  accent `#E63B2E`, radii, shadows, motion) + full `html[data-theme='dark']`
  token block. Inter/Inter Tight fonts via Google Fonts links.
- Domain stylesheets loaded after it: `killa-v2-forms/tables/widgets/overlays/dark.css`
  (built by 5 parallel agents; each token-driven with dark coverage).

### Deploy & infrastructure
- All theme links carry a content-hash cache-buster
  (`?v={{ substr(md5_file(...),0,8) }}`) after Cloudflare was caught serving
  stale CSS (`cf-cache-status: HIT`, max-age 4h).
- Tenant branding realigned in DB (`settings` table): header_color `#ffffff`,
  nav_link_color `#374151`, skin `black-light`, link colors blue — Snipe-IT's
  own theming engine (tenant CSS vars with `!important`) was the root cause of
  most "mystery" dark/white control colors.
- `scripts/update-snipeit.sh` + `/usr/local/sbin/snipeit-apply-update.sh`
  (server): merge upstream tag → archive → backup (rotated, sessions/uploads
  excluded) → rsync → composer.lock plugin patch → migrate → caches.
- Server disk filled to 100% by 1.5 GB backups once; rotation added (4 DB
  dumps, 2 file archives).

### Redesigns
- Dashboard stat tiles rebuilt as white cards (icon chip, big number, hover lift).
- Sidebar v3 (reference-driven): neutral gray-pill active state, pure white
  rail light / `#15181c` dark, 250px wide, simple indented submenu, soft badges.
- Compact pass: base font 12.5px, 36px table rows, 32px inputs, 28px toolbar buttons.

### Defect fixes (each root-caused against the live DOM, then verified)
- Blank user menu/dropdowns — per-user `nav_link_color #FFFFFF` overrode settings.
- White-on-white `.btn-theme` (LDAP Sync) — quieted to default button treatment.
- Selected table rows washed out — bootstrap-table's own `!important` near-white.
- Text bleeding through pinned columns — transparent-cells rule + translucent
  stripe token knocked out the opaque backer; fixed with solid composited
  backgrounds + killed upstream's gradient overlay (uniform row color).
- Collapsed-sidebar flyout unusable — multi-layer fix: opaque card, then
  visibility-grace mechanism, then finally a JS hover-intent (`k-flyout-hold`,
  800ms) in `layouts/default.blade.php`; flyout pinned flush to the icon row
  with border + shadow. Verified with scripted slow-motion crossings.
- Logo overflow/truncation — fork renders logo as navbar-brand INSIDE navbar;
  block now sizes to content.
- select2/dropdown dark states, yellow search highlight (upstream
  `.search-highlight #e9d15b`), required-field 5px orange bar → 3px warning.

## Decisions & root causes (the "why")
- **Theme = additive CSS only.** Zero build steps, two `[killa-v2]` layout
  lines, everything token-driven → upstream merges stay trivial.
- **Specificity wars are the real enemy.** Snipe-IT's overrides.less uses
  `!important` + tenant vars pervasively; every theme rule that matters needs
  body-prefixing and, where upstream is `!important`, `!important`.
- **Verify against the real render.** Playwright loop against production
  (temp superuser, deleted after each round) replaced guessing; most defects
  were not what they first appeared (CDN cache, tenant vars, stacking rules).

## Verification
- Playwright screenshots + computed-style probes of dashboard/assets/users/
  settings/license-edit in light+dark; interaction shots (dropdowns open,
  rows selected, flyout crossings at 600–800ms).
- `php artisan view:cache` clean after every Blade edit; login 200; all six
  stylesheets 200.

## Server / environment notes
- Server: root@40.30.20.6, app at `/opt/snipe-it`, PHP 8.3, MariaDB `snipeit_db`.
- Cloudflare in front of asset.killadesign.co — caches static CSS 4h.
- composer GitHub API rate limit (60/hr unauthenticated) blocks
  `composer update` for the `laravel-scim-server` VCS repo; lock-patch path
  documented in the deploy script.
- Backups in `/root/snipeit-backups/` (rotated).

## Follow-ups / known issues
- Dense tables: very long "Checked out to" names clamp tight against the
  Checkin button (upstream column widths).
- Chart.js canvas gridline colors are JS options, not CSS — dashboard pie is
  handled; other charts not audited.
- Phase-3 polish candidates: sidebar user card + Light/Dark toggle widget,
  remaining inline styles (803 across 204 blade files) tokenized over time.
