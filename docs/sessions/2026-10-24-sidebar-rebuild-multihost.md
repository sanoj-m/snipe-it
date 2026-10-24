# Session: Sidebar rebuild (KCP-049) + multi-host IP access (KCP-050) — 2026-10-24

## Goal

Redesign the entire sidebar from scratch to match the beui "animated-sidebar"
reference (React/Tailwind) while keeping every existing menu item — natively,
since the stack is Laravel/Blade/AdminLTE 2/Bootstrap 3 (no React/Tailwind
allowed). Later: make the app reachable via the server's raw IP in addition to
the Cloudflare-tunnel domain.

## What was done

### Sidebar rebuild (7 iterative passes, all deployed to live)

- `resources/views/layouts/partials/killa-sidebar.blade.php` (new) — all menu
  items, gates (`@can`/`Gate::allows`), routes, `trans()` keys, badges and the
  dynamic statuslabels loop copied verbatim from the old inline block;
  regrouped into Overview / Inventory / People & Org / System. Verified token
  multisets vs original: 39 `route(`, 31 `@can`, identical `request()->is()`.
- `public/css/killa-v2-sidebar.css` (new, zero-build) — width morph 256↔68px,
  sliding active pill, staggered clip-path submenus, collapsed hover flyout,
  mobile drawer + scrim, focus-visible, prefers-reduced-motion, tokens only.
- `public/js/killa-sidebar.js` (new, vanilla, deferred) — collapse toggle +
  localStorage, Ctrl/⌘+B, accordion, pill positioning, focus trap, scroll
  lock, AdminLTE PushMenu sync via MutationObserver.
- `resources/views/layouts/default.blade.php` — old `<aside>` (~470 lines)
  replaced by fenced `@include`; loaders added; two legacy inline sidebar
  scripts removed.
- Pass highlights: plain icons instead of chip wells, dot markers + guide line
  for children, neutral active pill + 3px accent bar, slim chevron-right,
  compact density (34/28px rows), collapsed 40×40px tiles on 44px pitch with
  hairline group separators, navbar logo de-overlapped in collapsed mode.
- Registered **KCP-049** (patch register now 45 patches after KCP-050).

### Multi-host access (KCP-050)

- `app/Providers/AppServiceProvider.php` + `config/app.php`: new
  `APP_ALT_HOSTS` whitelist — requests to a whitelisted host skip
  `forceScheme('https')` + `URL::forceRootUrl(APP_URL)`.
- Server: `/etc/nginx/conf.d/snipeit-alt-host.conf` — `map $http_host
  $killa_https_flag` (domain→`on`, `40.30.20.6`→empty); `snipeit.conf` uses
  `fastcgi_param HTTPS $killa_https_flag if_not_empty;` (backup:
  `snipeit.conf.bak-killa`). `.env` got `APP_ALT_HOSTS=40.30.20.6`.

## Decisions & root causes

- **React component rejected**: user supplied a shadcn/Tailwind/motion
  component; stack is locked, so the design was recreated natively.
- **rem trap**: Bootstrap 3 sets `html{font-size:10px}`, so `--k-sidebar-w:
  16rem` rendered as 160px — width tokens must be px in this codebase.
- **Invisible submenus**: children had base `opacity:0` revealed only by a
  staggered transition; server-rendered open parents + unbounded `--i` delays
  (up to ~2.8s) left most children hidden. Fix: children always visible; open/
  closed state lives on the `<ul>`; stagger opt-in via `.ks-stagger`, capped.
- **Collapsed logo artifact**: AdminLTE squeezes `.main-header .logo` to 50px;
  the fork's navbar-brand img+text spilled over the rail. Pinned to 68px,
  transparent, mini logo only.
- **IP redirect mystery**: `http://40.30.20.6/` 302'd to the https domain
  because (a) Snipe-IT forces root URL/scheme from `APP_URL`, and (b)
  `/etc/nginx/conf.d/snipeit.conf` hard-forced `fastcgi_param HTTPS on` for
  ALL requests (Cloudflare terminates TLS, talks plain HTTP to origin).
- HEAD commit `b58de20859` had accidentally swept in a half-applied sidebar
  refactor (layout referencing a missing partial — would 500); this session
  completed it.

## Verification

- Live curl checks after each deploy: login 200, served CSS/JS contain the new
  rules, `php artisan view:cache` compiled clean on server.
- Multi-host: `http://40.30.20.6/` → `Location: http://40.30.20.6/login`;
  assets served from the IP; domain Host still forces `https://asset.killadesign.co`.
- Visual verification done by the user via screenshots each pass.

## Server / environment notes

- Auth for deploys: `plink -pw` (password in chat history — rotate it or set
  up a saved PuTTY session/Pageant; no saved sessions exist on this machine).
- Ship tarballs to `/root/snipeit-update.tar.gz`, NOT `/tmp` (a tmp cleaner
  ate an upload mid-deploy once).
- nginx runs two duplicate vhosts (`conf.d/snipeit.conf` + `sites-enabled/`)
  with a "conflicting server name" warning — conf.d wins; left as-is.
- Deploy script re-caches config; `.env` edits need a manual `config:cache`.

## Follow-ups / known issues

- IP path is plain HTTP — trusted networks only.
- Emails/QR labels still reference the domain (APP_URL) — intended.
- Uncommitted working-tree items NOT from this session left untouched:
  `skills/killa-design/SKILL.md` deleted + new `skills/killa-design-manual/`
  (appears user-made — not committed here), two 2026-10-23 session logs,
  license-controller changes were committed by a parallel session.
- pint/phpunit never run this session (no PHP on PATH).
