# ADR-0001: Upstream compatibility strategy

**Status:** Accepted

## Context

Killa Asset is a fork of Snipe-IT v8.8.0 that must keep merging upstream
releases (security fixes land upstream constantly). The fork carries ~55
commits across 72 modified upstream files; uncontrolled divergence would make
every upgrade a rewrite.

## Decision

Adopt **minimum core patching, maximum extension isolation**:

1. Features live in isolated, Killa-owned locations (Composer path package,
   overlay CSS) wherever possible.
2. Any unavoidable edit to an upstream file is a minimal, fenced hunk
   (`// [floating-licenses addon] BEGIN/END`, `{{-- [killa-v2] --}}`) and is
   registered in `docs/customizations/CORE_PATCH_REGISTER.md`. A diff vs
   upstream in an unregistered file is treated as a defect.
3. Merge conflicts are resolved three-way (BASE / UPSTREAM / KILLA intent) —
   never plain `ours`/`theirs`. Goal: new upstream behavior + reapplied Killa
   intent.
4. Upstream security fixes always win; Killa code is redesigned around them.

## Alternatives considered

- **Hard fork (stop merging upstream)** — loses security fixes; rejected.
- **Full plugin architecture with zero core edits** — upstream has no extension
  points for checkout interception, transformers, or presenters; not feasible
  without forking even harder.
- **Upstream-first (PR everything)** — Killa-specific features (floating
  licenses, branding) are not upstream-mergeable; kept as an option for
  genuinely generic fixes only.

## Consequences

- Every core touch has a known owner, marker, and removal strategy (KCP
  register).
- Conflict surface is enumerable and ranked (UPSTREAM_BASELINE hotspots).
- Some extensions are deliberately less elegant (e.g. transformer hunks)
  because no upstream hook exists.

## Upgrade implications

Every upgrade follows `docs/upgrades/UPSTREAM_UPGRADE_RUNBOOK.md`: pre-merge
conflict scan, three-way resolution preserving fenced hunks, full test gates,
and a `docs/upgrades/UPGRADE_HISTORY.md` entry. KCP entries are re-audited
each upgrade — when upstream adopts an equivalent feature, the Killa patch is
removed.
