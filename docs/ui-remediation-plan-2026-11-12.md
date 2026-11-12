# Killa Asset — UI Remediation Plan (executes the 2026-11-12 audit)

**Date:** 2026-11-12 · **Companion doc:** [ui-audit-2026-11-12.md](ui-audit-2026-11-12.md)

## Change classification (per AGENTS.md)

```
Feature:        killa-v2 theme consistency remediation (audit follow-up)
Files likely affected:
  public/css/killa-v2.css          (tokens + dead sidebar block removal)
  public/css/killa-v2-forms.css    (32px control scale, dead checkbox skin)
  public/css/killa-v2-tables.css   (token moves, --k-accent-soft fix)
  public/css/killa-v2-widgets.css  (remove scale-down block, move shared token)
  public/css/killa-v2-dark.css     (zebra dedupe, dark token moves)
  public/css/killa-ux.css          (retoken .kx onto the v2 palette)
Upstream core touched: NO          (Killa-owned CSS overlays only)
Database change: NO
UI/API/Permission change: UI only (visual; no markup, route, or permission change)
Upgrade risk: LOW                  (all files are KILLA-UI zero-build overlays)
Tests required: none (layout-only; existing suites untouched)
Documentation required: this file + updated audit doc status
```

## Scope decisions

| Audit item | In scope? | Approach |
|---|---|---|
| P0-1 control height split (32 vs 36px) | YES | Settle on 32px everywhere; re-pin select2 + input-group buttons; delete losing declarations |
| P0-2 `--k-accent-contrast` wrong file | YES | Move to `killa-v2.css` :root + dark block (co-located with other shared tokens) |
| P0-3 `--k-accent-soft` undefined | YES | Replace usage with existing `--k-accent-tint` |
| P0-4 dark zebra two sources | YES | Keep `--k-table-stripe` token; delete dark.css override |
| P0-5 double checkbox implementation | YES | Keep the killa-v2.css custom skin; delete dead forms.css/dark.css rules |
| P0-6 placeholder opacity conflict | YES | Single rule (opacity 1, muted color) |
| P1-1 dead sidebar CSS (~500 lines) | YES | Delete CHARCOAL NAV RAIL + 250px width block + dead flyout clamp + old motion block from `killa-v2.css`; verified no view renders `.main-sidebar`/`.sidebar-menu`/`.treeview-menu` anymore |
| P1-2 lying single-source comments | YES | Rewrite comments to match reality |
| P1-3 cross-file token deps | YES | Move `--k-progress-track` (used by notifications.css) into `killa-v2.css` |
| P1-7 two design languages | YES | Retoken `.kx` light+dark values onto the v2 palette (structure kept; warm cream → cool neutral, duplicate semantics → v2 values) |
| P1-9 three page-header idioms | PARTIAL | CSS-only: raise classic `content-header` h1 toward the Insights scale (20px/650). Full Blade-level header unification deferred (touches every view). |
| P1-4 `!important` reduction | NO | Upstream `overrides.less` forces tenant colors with `!important`; wholesale removal is a specificity gamble. Deferred. |
| P2 token-file split / design manual rewrite | NO | Documented as follow-up; not risk-free busywork for this session. |
| Sidebar status-dot AdminLTE colors | NO | Intentional per design comment (statuslabel identity colors). |

## Execution order

1. `killa-v2.css` — add `--k-accent-contrast`, `--k-progress-track` (+ dark values); remove placeholder `opacity: 0.75`; delete dead sidebar blocks; align content-header h1.
2. `killa-v2-forms.css` — 32px control scale (inputs, select2 single/multiple, arrow, input-group buttons, label rows); delete dead checkbox/radio block; trim dark block to the live `select` rule.
3. `killa-v2-tables.css` — drop local `--k-accent-contrast` defs; fix `--k-accent-soft` → `--k-accent-tint`.
4. `killa-v2-widgets.css` — delete the "compact scale-down" re-pin block (now the single source in forms.css); drop `--k-progress-track` from local tokens.
5. `killa-v2-dark.css` — delete the `.table-striped` dark override (token wins); delete dead checkbox `color-scheme` rules.
6. `killa-ux.css` — retoken `.kx` (light + dark) onto v2 palette/shadow values.
7. Gates: no PHP changed (Pint/PHPUnit not implicated); layout-only.
8. Deploy: commit → `git archive` → pscp → `snipeit-apply-update.sh` on `root@40.30.20.6` (script stamps `app_version`, which busts the CF-cached `?v=` asset URLs).
9. Verify on live: HTTP 200 + new content markers on every touched CSS via `https://asset.killadesign.co/css/...?v=<new version>`, login page HTML link check, `cf-cache-status` on versioned URLs.

## Rollback

All changes are in 5 committed CSS overlays + 1 commit. Roll back with `git revert <sha>` + re-run the deploy script, or restore the pre-deploy backup the server script takes automatically.
