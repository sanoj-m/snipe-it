# Session: LAN access, split-DNS prep & session handoff — 2026-11-17

## Goal

Let office users reach the app without Cloudflare traffic: collapsed-sidebar
flyout fix, LAN hostname support, a "switch to local" banner, and seamless
session carry-over to the LAN URL.

## What was done

**Sidebar flyout fix (commit de7fae1693, hot-patched live)**
- Collapsed-rail hover flyout was invisible: `.ks-sub` was `position:absolute`
  inside `.ks-content` (`overflow-x:hidden/overflow-y:auto`) → clipped.
- `public/css/killa-v2-sidebar.css`: flyout now `position:fixed` with
  `--ks-flyout-top` anchor + `max-height: calc(100vh - top - 12px)`.
- `public/js/killa-sidebar.js`: `positionFlyout()` on mouseenter/focusin,
  viewport-bottom clamping.

**LAN hostname + nginx/TLS (server config, no code)**
- `.env` live: `APP_ALT_HOSTS=40.30.20.6,asset.ad.killadesign.com` (KCP-050
  mechanism already existed).
- `conf.d/snipeit.conf` (the LIVE vhost — `conf.d` loads before
  `sites-enabled`): server_name + all 3 names; `sites-enabled` copy synced.
- `conf.d/snipeit-alt-host.conf` scheme map: `asset.ad.killadesign.com` → HTTP
  (same as raw IP); everything else HTTPS.
- Let's Encrypt cert for `asset.killadesign.co` via certbot HTTP-01 webroot
  (expires 2027-02-14, systemd timer auto-renew); new `listen 443 ssl` block
  tagged `[killa split-dns]` so LAN split-horizon DNS can serve HTTPS direct.
- Cleanup: `sites-enabled/snipeit.conf.bak` moved to `/root/nginx-backups/` —
  nginx `include sites-enabled/*` loads `.bak` files as live server blocks.

**LAN-switch banner (KCP-060, commit c023349899)**
- `config/app.php`: `KILLA_LAN_WAN_IPS` + `KILLA_LAN_URL` (fenced).
- `resources/views/partials/killa-lan-banner.blade.php` (Killa-owned): shows
  when `CF-Connecting-IP` ∈ whitelist and host isn't an alt host; dismissible
  (localStorage). Fenced `@include` in `layouts/default.blade.php` after the
  impersonation banner. Strings in `resources/lang/en-US/killa.php`.
- Live `.env`: `KILLA_LAN_URL=http://asset.ad.killadesign.com`,
  `KILLA_LAN_WAN_IPS=86.98.6.123` (office WAN IP, detected via ifconfig.me).

**Session handoff (KCP-063, commit e9d0ead116 — originally tagged KCP-062,
renumbered after a parallel session registered session-keepalive as KCP-062)**
- `app/Http/Controllers/Killa/LanSwitchController.php`: authed
  `GET killa/lan-switch` mints a 60s **relative-signed** URL (host-independent,
  same APP_KEY both sides) onto KILLA_LAN_URL; `killa/lan-handoff` validates
  signature + activated user, `Auth::loginUsingId`, session regenerate,
  path-only redirect (`safePath`). Throttled 10/min.
- Banner button routes through the handoff → no re-login on the LAN URL.

## Decisions & root causes

- **Autoswitch belongs in DNS, not the app.** Public-name resolution happens
  before the app is reached; split-horizon DNS on KLD-DC1 (pinpoint zone
  `asset.killadesign.co` → 40.30.20.6) is the clean switch. Server is ready for
  it (443 + LE cert); the AD DNS record is the user's remaining step.
- **Browser-side LAN probing rejected**: mixed content + Private Network Access
  preflights block https→http-LAN fetches. Server-side `CF-Connecting-IP`
  whitelist is reliable instead.
- **Broken-layout screenshot root cause**: transient mixed asset versions during
  a parallel session's hot-patch (09:15) — verified healthy via Playwright
  (collapsed 60/60px, expanded 240/240px, no JS errors).
- **2FA caveat**: handoff logs in directly (token only issued to an already-
  authenticated session, 60s expiry).

## Verification

- curl matrix on live: LAN host serves http + host-rooted links; Cloudflare
  host unchanged https; tampered handoff signature → 403; handoff → authed 200
  on `/hardware` on LAN host.
- Playwright end-to-end from this machine (office IP 86.98.6.123, real CF
  path): banner renders with correct copy/link; layout healthy.
- Temp admins (tmp-verify, -2, -3) created for tests and deleted after.

## Server / environment notes

- nginx has DUPLICATE vhosts: `conf.d/snipeit.conf` is authoritative (include
  order); `sites-enabled/snipeit.conf` is ignored ("conflicting server name"
  warnings). Edit both or consolidate.
- Parallel sessions share this working tree and the live server — full deploys
  from HEAD can revert another session's hot patches. Commit before deploying.
- Pint/PHPUnit unavailable on this Windows machine (no PHP); gates noted as
  skipped for Blade/config-only changes.

## Follow-ups / known issues

- User: create AD DNS record (`asset.ad.killadesign.com → 40.30.20.6`, and/or
  pinpoint zone `asset.killadesign.co` → 40.30.20.6 for true autoswitch).
- Optional: Cloudflare SSL mode Flexible → Full (strict) now that origin has a
  real cert.
- If office WAN IP is dynamic, `KILLA_LAN_WAN_IPS` needs updates (or prefer
  split-DNS).
- Uncommitted parallel-session work in tree at save time: DashboardController,
  killa dashboard blade/tests/lang, `docs/sessions/2026-11-16-site-tagline-header.md`,
  `imran maintainance/` — left untouched on purpose.
