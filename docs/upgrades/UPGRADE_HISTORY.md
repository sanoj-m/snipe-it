# Upgrade History — Killa Asset

One row per upstream upgrade. Runbook: `UPSTREAM_UPGRADE_RUNBOOK.md`.
Baseline reference: [UPSTREAM_BASELINE](../upstream/UPSTREAM_BASELINE.md).

| Date | Previous upstream | New upstream | Killa release | Conflicts | Core patches reapplied/removed | Migrations | Dependency changes | Security changes | Regression results | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| 2026 (fork cut) | — | v8.8.0 (`2c466fa`) | — (initial fork, 55 commits) | N/A (baseline) | All 35 KCP registered at baseline | 2 core + 5 package migrations added | `snipe-it/floating-licenses` path repo; puppeteer/ssh2 in package.json | — | Not run (no PHP runtime on maintainer machine) | Initial Killa fork; Orders/Sync confirmed upstream v8.8.0, not Killa |
| 2026-10-07 (hardening, not an upstream merge) | v8.8.0 | v8.8.0 (unchanged) | unreleased | **Dry-run simulation vs upstream/master (112 commits, unreleased): 1 conflict** — `lang/en-US/admin/settings/sync_adapters.php` (branding strings); collision candidates 9 → 8 after refactor; 7 auto-merge | KCP-003/004 consolidated into `License::saving` hook; FL sync moved to package observer; KCP-018/019 extracted to package partials (view 511→225 lines); KCP-030 reduced 16 files → 1 global `MessageSending` listener; gitlink removed | none new; `floating_licenses_enabled` migration guarded | puppeteer/ssh2 → devDependencies | **Fixed:** open redirect in floating release(); FMCS scoping added to floating licenses; `floating-licenses:expire` now scheduled (5 min) | Not runnable locally (no PHP) — maintainer must run full gates; see TEST_STRATEGY.md | 4 commits: docs → floating-licenses refactor → branding → UI normalization |

<!--
Entry template:

| YYYY-MM-DD | vA.B.C | vX.Y.Z | killa-vN | N files (list HIGH first) | KCP-xxx reapplied; KCP-yyy removed (upstream adopted) | new upstream migrations audited vs Killa columns | composer/npm deltas | upstream security fixes merged (list) | Unit/Feature/FloatingLicenses pass-fail counts | free text |
-->
