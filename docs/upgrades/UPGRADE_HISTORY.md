# Upgrade History — Killa Asset

One row per upstream upgrade. Runbook: `UPSTREAM_UPGRADE_RUNBOOK.md`.
Baseline reference: [UPSTREAM_BASELINE](../upstream/UPSTREAM_BASELINE.md).

| Date | Previous upstream | New upstream | Killa release | Conflicts | Core patches reapplied/removed | Migrations | Dependency changes | Security changes | Regression results | Notes |
|---|---|---|---|---|---|---|---|---|---|---|
| 2026 (fork cut) | — | v8.8.0 (`2c466fa`) | — (initial fork, 55 commits) | N/A (baseline) | All 35 KCP registered at baseline | 2 core + 5 package migrations added | `snipe-it/floating-licenses` path repo; puppeteer/ssh2 in package.json | — | Not run (no PHP runtime on maintainer machine) | Initial Killa fork; Orders/Sync confirmed upstream v8.8.0, not Killa |

<!--
Entry template:

| YYYY-MM-DD | vA.B.C | vX.Y.Z | killa-vN | N files (list HIGH first) | KCP-xxx reapplied; KCP-yyy removed (upstream adopted) | new upstream migrations audited vs Killa columns | composer/npm deltas | upstream security fixes merged (list) | Unit/Feature/FloatingLicenses pass-fail counts | free text |
-->
