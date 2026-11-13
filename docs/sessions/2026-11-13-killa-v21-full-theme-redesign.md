# 2026-11-13 — killa-v2.1 full theme redesign (all areas)

## What happened

Continuation of the 2026-11-12 UI audit/remediation. The owner approved the
direction and asked for a **full v2 redesign across all pages/areas**, executed
as a theme-layer overhaul (zero-build CSS overlays, no Blade rewrites except the
fenced setup-layout loader).

## Changes (commit `4578b1ff2b`, branch `chore/code-cleanup`)

### `public/css/killa-v2.css`
- `.box` elevated with `var(--k-shadow-1)`; box-header padding 16px; `.box-title` 15px;
  `.box-header.with-border` hairline restored.
- box-solid headers: 8% semantic `color-mix` tints + colored titles in light mode.
- New "v2.1 polish & coverage pass" section:
  - dashboard tiles: 2px signal-colored top edges (`--k-tile-accent`, inset shadow) + hover lift;
  - light-mode `.nav-pills` (accent-tint active), plain `.nav-tabs`, plain `.pagination` on tokens;
  - global slim 8px scrollbars (light);
  - normalized stray content/modal `h2/h3/h4` (30/24px Bootstrap defaults → 19/16/14px);
  - error pages on the basic layout: repaired the inline 200px top pad, display-type error headings.

### `public/css/killa-v2-setup.css` (NEW, 4.7 KB)
Setup wizard theme: token base, 26px display page-header, bs-wizard stepper in
success/accent tokens, preflight-success/error/warning/info → semantic tokens,
upstream inline sizes reined in, code chips.

### `resources/views/layouts/setup.blade.php` — KCP-053 (registered)
Fenced `[killa-v2]` block after the upstream inline `<style>` linking
`killa-v2-fonts.css`, `killa-v2.css`, `killa-v2-forms.css`, `killa-v2-overlays.css`,
`killa-v2-setup.css` (all `?v={{ config('version.app_version') }}`). Setup pages
previously loaded only `all.css` and were completely unthemed.

### `public/css/killa-v2-dark.css` — dark parity
- nav-pills active aligned to the tint style (was solid accent);
- scrollbars slimmed 10px → 8px, transparent track, border removed (matches new light);
- plain nav-tabs/pagination verified token-driven → dark coverage comes free via tokens;
- box-solid 22% dark tints already present (verified).

### Docs
- `CORE_PATCH_REGISTER.md`: KCP-053 registered (LOW risk).
- `CUSTOMIZATION_INVENTORY.md`: UI-01 now 10 CSS files; UI-02 covers setup layout.

## Verification
- Brace balance: killa-v2.css 181/181, killa-v2-dark.css 112/112, killa-v2-setup.css 31/31.
- No stray `--k-accent-soft` / stale 36px references (remaining 36px is a table-row height).
- Approval/preview widget updated (v2.0 vs v2.1 mock):
  `widget_3e3919c4-3d6e-4b70-8a2d-cf50bebdb864`.
- PHP gates not run (no PHP/composer on this machine) — CSS/Blade-loader-only change, no PHP logic touched.

## Deploy status

**Blocked on SSH auth from this machine** (same as 2026-11-12): no pageant key,
no saved PuTTY session, no `~/.ssh` key; server offers `publickey,password`.
Commits `039fe064b2` (2026-11-12 remediation) and `4578b1ff2b` (this pass) are
both pending deploy. Once a key is available:

```bash
git archive --format=tar.gz -o /tmp/snipeit-v21.tar.gz HEAD
"C:\Program Files\PuTTY\pscp.exe" /tmp/snipeit-v21.tar.gz root@40.30.20.6:/tmp/snipeit-update.tar.gz
"C:\Program Files\PuTTY\plink.exe" root@40.30.20.6 "bash /usr/local/sbin/snipeit-apply-update.sh /tmp/snipeit-update.tar.gz"
curl -sI "https://asset.killadesign.co/css/killa-v2.css?v=<new app_version>"   # cf-cache-status + 200
```

## Rollback

`git revert 4578b1ff2b` + redeploy. Setup layout fence removal alone restores
stock setup pages; the setup stylesheet is inert when not linked.
