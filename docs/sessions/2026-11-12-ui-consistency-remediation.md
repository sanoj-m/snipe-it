# Session: UI consistency audit & killa-v2 remediation (2026-11-12)

## What happened

Full UI inspection of the killa-v2 theme layer (all 9 stylesheets, layouts,
sidebar partial, dashboard views), followed by an executed remediation pass.
Findings: [docs/ui-audit-2026-11-12.md](../ui-audit-2026-11-12.md) · plan:
[docs/ui-remediation-plan-2026-11-12.md](../ui-remediation-plan-2026-11-12.md).

## Root causes found

- **Four visual languages at once**: killa-v2 (cool gray), sidebar pass 2,
  killa-ux `.kx` (warm cream, Licenses toggle), k-insights headers.
- **Conflicting declarations**: `.form-control` 32px vs 36px vs 32px across
  three files; select2 stayed 36px next to 32px inputs; placeholder opacity
  0.75 vs 1; dark zebra stripes had two sources of truth.
- **Broken token hygiene**: `--k-accent-contrast` defined in tables.css but
  consumed by killa-v2.css/dark.css; `--k-accent-soft` referenced but never
  defined; `--k-progress-track` shared cross-file from widgets.css.
- **~400 lines of dead CSS**: the old CHARCOAL NAV RAIL block in
  killa-v2.css styled `.main-sidebar`/`.sidebar-menu` markup removed by
  KCP-049 — including a stale design contract contradicting the live rail.

## Changes (commit 039fe064b2)

- Control scale unified at 32px (inputs, select2 single/multiple, arrow,
  input-group buttons, label rows) — forms.css is the single source.
- `--k-accent-contrast` + `--k-progress-track` moved to killa-v2.css
  (:root + dark block); `--k-accent-soft` usage → `--k-accent-tint`.
- Dark zebra: token only; dark.css override deleted. Duplicate stripe rule
  in tables.css removed.
- Dead native-checkbox rules deleted from forms.css (custom
  `appearance:none` skin in killa-v2.css is the one implementation).
- Placeholder rule consolidated (no opacity conflict).
- Dead sidebar CSS + orphaned tokens deleted from killa-v2.css
  (1549→1135 lines); logo width transition moved to killa-v2-sidebar.css.
- `.kx` retokened 1:1 onto the v2 palette (light + dark) — Licenses pages
  now match the app canvas/semantics; component structures unchanged.
- Classic `content-header` h1: 17px/700 → 20px/650 (closer to Insights).

## Gates

CSS-only change: no PHP (Pint/PHPUnit not implicated), no config/route
changes, no migrations. Brace balance verified on all 6 touched files.

## Deployment — BLOCKED on SSH auth

Tarball built (`git archive HEAD`, 25.9 MB) but neither plink nor OpenSSH on
this machine has working auth to `root@40.30.20.6` (no pageant, no saved
session, no key in `~/.ssh`; server offers publickey,password). Port 22 is
reachable. Once credentials are available:

```bash
"/c/Program Files/PuTTY/pscp.exe" /tmp/killa-ui-2026-11-12.tar.gz root@40.30.20.6:/tmp/snipeit-update.tar.gz
"/c/Program Files/PuTTY/plink.exe" root@40.30.20.6 "bash /usr/local/sbin/snipeit-apply-update.sh /tmp/snipeit-update.tar.gz"
```

(the apply script stamps a fresh `app_version`, which busts the CF-cached
`?v=` asset URLs). Verify afterwards: fetch each touched CSS via
`https://asset.killadesign.co/css/<file>?v=<new version>` and confirm the
new markers (e.g. no `sidebar-menu` rules in killa-v2.css, `#f4f5f7` kx bg).

## Follow-ups (deferred, documented in the plan)

- `!important` reduction (upstream `overrides.less` forces many).
- Full Blade-level page-header unification (kx hero / Insights / classic).
- Token-file split (`killa-v2-tokens.css`) + real design manual for THIS app
  (`skills/killa-design-manual` currently documents a different product).
