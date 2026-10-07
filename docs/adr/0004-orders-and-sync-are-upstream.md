# ADR-0004: Orders and Sync Adapters are upstream, not Killa

**Status:** Accepted

## Context

Earlier handoff documentation classified `Order`/`OrderItem`,
`SyncAdapterConfig`/`SyncAdapterInstance`, and the `PullInventory`/
`PushInventory` console commands as Killa customizations needing isolation
work. Discovery during Phase 1 baseline analysis proved this wrong:

```bash
git cat-file -e v8.8.0:app/Models/Order.php   # exists in the upstream tag
```

These features ship in **upstream Snipe-IT v8.8.0** and have **zero Killa
modifications** in the v8.8.0 → master diff.

## Decision

Treat Orders and Sync Adapters as **core upstream code**:

- No isolation treatment, no KCP registration, no `[killa]` fencing.
- No "removal strategy" planning; they merge with upstream like any other core
  file.
- Earlier misclassification in handoff docs is corrected here and in
  `docs/upstream/UPSTREAM_BASELINE.md`, `docs/customizations/ORDERS.md`, and
  `docs/customizations/SYNC_ADAPTERS.md`.

## Alternatives considered

- **Isolate anyway "just in case"** — would create pointless divergence and
  merge conflicts on genuinely upstream code; rejected.
- **Keep the old docs with a caveat** — stale authority is worse than
  correction; the docs were rewritten.

## Consequences

- The Killa customization surface is exactly four tracks (floating licenses,
  perpetual licenses, LDAP deactivate-missing, branding/theme) — smaller than
  previously believed.
- Sync-adapter bugs/improvements should go upstream as PRs where generic.
- UI audit treats Orders/Sync pages as upstream surfaces styled by the theme
  layer (UI_AUDIT matrix).

## Upgrade implications

Orders/Sync files now carry **no special handling** in upgrades — standard
three-way merge, no hunk re-application. When auditing diffs vs upstream, any
Killa-side change appearing in `app/Models/Order*.php`,
`app/Models/SyncAdapter*.php`, `app/SyncAdapters/`, or the Pull/Push commands
is a *new* deviation and must be registered in CORE_PATCH_REGISTER at that
time.
