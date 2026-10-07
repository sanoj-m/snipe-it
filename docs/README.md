# Killa Asset Documentation Index

| Folder | Contents |
|---|---|
| `adr/` | Architecture decision records (upstream-compat, floating-license boundary, killa UI, orders/sync) |
| `architecture/` | `ARCHITECTURE.md` — overall system architecture |
| `customizations/` | `CORE_PATCH_REGISTER.md`, `CUSTOMIZATION_INVENTORY.md`, `ORDERS.md`, `SYNC_ADAPTERS.md` — Killa divergence from upstream |
| `database/` | `CUSTOM_SCHEMA.md` — Killa-owned columns/migrations |
| `sessions/` | Dated working-session logs |
| `testing/` | `TEST_STRATEGY.md` — suites, commands, gates |
| `ui/` | `UI_AUDIT.md`, `UI_GUIDELINES.md`, `MULTI_FIELDSET_DESIGN.md` |
| `upgrades/` | `UPSTREAM_UPGRADE_RUNBOOK.md`, `UPGRADE_HISTORY.md` (incl. list of intentionally pruned upstream files) |
| `upstream/` | `UPSTREAM_BASELINE.md` — pinned upstream base |

Root-level canonical docs: `AGENTS.md`, `PROJECT_HANDOFF.md` (plus `docs/CHANGELOG-KILLA.md`). Community docs (`CODE_OF_CONDUCT.md`, `CONTRIBUTING.md`, `SECURITY.md`) live in `docs/` — GitHub still auto-detects them there. QA tool configs (`phpunit.xml`, `pint.json`, `phpstan*`, `phpmd*`) live in `.ci/` — pass `-c .ci/<file>` when invoking the tools directly.
