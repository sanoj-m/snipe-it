# Session: Fork future-proofing (master engineering prompt, all phases) — 2026-10-07

## Goal

Execute the full "Future-Proof, Upgrade-Safe Refactoring" master prompt: Phase 1
discovery, Phase 2 guardrails/docs, Phase 3 extension isolation, Phase 4 UI
normalization, Phase 5 upgrade simulation, Phase 6 validation/report — then the
follow-up debt list (branding conflict elimination, font self-hosting, policy +
transformer compliance, script hardening).

## What was done

**Phase 1 — discovery (docs only)**
- Established upstream topology: merge base = upstream tag **v8.8.0** (the latest
  release); 55 Killa commits; 133 files changed, 0 upstream files deleted.
- Corrected a major misclassification: `Order`/`OrderItem`/`SyncAdapter*`/
  `PullInventory`/`PushInventory` are **upstream v8.8.0 features, not Killa
  custom** (PROJECT_HANDOFF.md fixed, ADR-0004).
- Found `snipe-it-floating-license-plugin` was a broken gitlink (no
  `.gitmodules`, objects absent) — legacy duplicate.
- Produced docs/upstream/UPSTREAM_BASELINE.md,
  docs/customizations/CUSTOMIZATION_INVENTORY.md, CORE_PATCH_REGISTER.md
  (35 patches), docs/database/CUSTOM_SCHEMA.md.

**Phase 2 — guardrails**
- `AGENTS.md` (golden rule, change classification, pre-flight, post-change
  report), `docs/upgrades/UPSTREAM_UPGRADE_RUNBOOK.md` (full workflow +
  rollback), `docs/upgrades/UPGRADE_HISTORY.md`, `docs/architecture/ARCHITECTURE.md`,
  `docs/testing/TEST_STRATEGY.md`, `docs/ui/UI_AUDIT.md` + `UI_GUIDELINES.md`,
  4 ADRs, `CHANGELOG-KILLA.md`, `scripts/check-upstream-conflicts.sh`,
  `scripts/test-upgrade-regression.sh`, extended package README.

**Phase 3 — isolation & security** (commits d543a7f9, c786fb1c)
- Perpetual expiration-clearing consolidated from 5 call sites into one fenced
  `License::saving` hook; perpetual hunks marked `[perpetual-licenses addon]`.
- Floating form-sync moved out of core controller into a package `License::saved`
  observer gated on `licenses.store`/`licenses.update` routes.
- `licenses/view.blade.php` 511→225 lines via 5 package partials; edit-form
  section extracted likewise.
- Security fixes: open redirect in floating `release()` (same-host/relative
  referer only); FMCS company scoping across floating licenses (+10 tests);
  `floating-licenses:expire` scheduled every 5 min (fenced KCP-031);
  unguarded settings migration guarded; dead `revoke()`/`STATUS_REVOKED`
  removed; breadcrumbs added; exportUsers N+1 fixed.
- Branding centralized: global `MessageSending` listener sets
  `X-System-Sender: Killa Asset`; **16 notification files reverted
  byte-identical to upstream**; footer_credit restored to compliant upstream
  attribution + Killa credit appended; broken gitlink removed.

**Phase 4 — UI normalization** (commit a1788a63)
- ~65 lines dead flyout corridor CSS removed (verified fully overridden);
  comment drift fixed (800ms/0.5s); `--k-*` token duplicates unified
  (light in killa-v2.css :root, dark overrides only in killa-v2-dark.css);
  cache-bust `md5_file()`→`config('version.app_version')`; SKILL.md synced.

**Phase 5 — upgrade simulation** (vs upstream/master, 112 unreleased commits)
- Before refactor: 9 collision candidates, 1 conflict. After: 8 candidates,
  1 conflict → then `sync_adapters.php` lang reverted to upstream (pure
  branding) → **0 conflicts**, clean merge-tree.

**Debt-list round** (commits 660ebe6c…ef7626fa)
- `update-snipeit.sh`: SERVER now required env var, `SKIP_MERGE=1` implemented.
- `.vscode/` gitignored; `killa_` migration infix rule added to
  .ai/rules/migrations.md.
- Fonts self-hosted (2 variable woff2, ~91KB, `public/fonts/vendor/inter/`),
  Google CDN dropped from both layouts; 768px flyout clamp added.
- Package compliance: `FloatingLicenseConfigPolicy` + whitelisted API
  transformer; 6 new tests; envelope/codes unchanged.

## Decisions & root causes

- FMCS scoping reuses upstream mechanisms (`Company::scopeCompanyables` via
  license relation, `canCheckoutTo`) — no `company_id` column added.
- Policy deliberately does NOT extend `SnipePermissionsPolicy` (its `before()`
  would grant admin-flag users and run company checks on a company-less table).
- String gates kept for non-model abilities (permissions UI + ownership-split
  release) — intentional, documented.
- Branding string swaps are the highest-frequency conflict source; centralized
  or reverted rather than kept (sync_adapters lang revert = 0-conflict merges).
- Token dedupe kept the cascade-winning values (dark.css loaded last).

## Verification

- `git merge-tree --write-tree master upstream/master`: clean tree, **0 conflicts**.
- `git diff --check` clean on all commits.
- **NOT verified (no PHP/composer/Herd on this machine):** pint, phpunit
  (Unit/Feature/FloatingLicenses), phpstan, artisan, browser checks.
  Maintainer must run the gate list (see Follow-ups).

## Server / environment notes

- Maintainer Windows machine has **no PHP/composer/Herd on PATH** — all PHP
  gates must run elsewhere (server or Herd machine).
- `update-snipeit.sh` now requires `SERVER=user@host` env; remote apply script
  `/usr/local/sbin/snipeit-apply-update.sh` lives on the server, not in repo.

## Follow-ups / known issues

1. Run full quality gates + browser smoke (fonts, floating pages, flyouts),
   then `git push origin master` (branch ahead of origin).
2. Parallel-session caution: a second session was pruning upstream files
   (ansible/, Vagrantfile, upgrade.php, etc.) and editing AGENTS.md
   (.ci/ config paths) concurrently — those worktree changes are intentionally
   NOT part of this session's commits; commit-amend collision occurred once.
3. Open UI items: full sub-768px audit beyond the flyout clamp; footer link
   deletion decision; licenses/view inline styles (partially mitigated).
4. Next upstream tag (v8.9.0+): run `scripts/check-upstream-conflicts.sh` +
   runbook — expected 0 conflicts based on simulation.
