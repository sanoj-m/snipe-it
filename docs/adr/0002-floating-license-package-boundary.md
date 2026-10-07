# ADR-0002: Floating-license package boundary

**Status:** Accepted

## Context

Floating (concurrent) licensing needs pool state, allocation tracking, cost
modes, leases, a UI, an API, and scheduled expiry — far too much to scatter
through upstream files. But it also must intercept core flows (seat checkout,
license save, transformers, presenters) where upstream offers no events or
extension points.

## Decision

Split into two zones:

1. **Isolated package** (`packages/floating-licenses`, `snipe-it/floating-licenses`,
   Composer path repo, auto-discovered provider). Owns: models
   (`FloatingLicenseConfig`, `FloatingLicenseAllocation`),
   `FloatingLicenseService` (all state changes; concurrency-safe allocation via
   `lockForUpdate()` in a transaction), routes (web CRUD + API), views, lang,
   5 migrations (no FKs, per project rule), config, console commands, tests.
   Permissions are injected into `config('permissions')` at runtime — core
   `config/permissions.php` is untouched.
2. **Fenced core hunks** (~15 files) only where interception is impossible from
   outside: checkout/checkin controllers, license store/update, three
   transformers, two presenters, license views, settings. All registered as
   KCP-001…023, all no-op when the `floating_licenses_enabled` master switch
   is off.

## Alternatives considered

- **Observer/events only** — upstream fires no pre-checkout event; covers
  store/update (planned) but not checkout interception or transformers.
- **Fork the license subsystem in core** — maximal merge pain; rejected.
- **Separate microservice** — absurd for a single-deployment admin tool.

## Consequences

- Package alone is ~5,700 LOC with its own test suite (17 files, ~114 tests)
  and README (authoritative).
- Core hunks are the ranked conflict hotspots (KCP-007, KCP-018 = HIGH).
- Master switch gives a global kill-switch: with it off, web/API 403 and every
  license behaves as a core fixed-seat license.
- Package must remain installed — fenced hunks reference its classes.

## Upgrade implications

On upstream merge: take upstream side of the ~15 patched files, re-apply the
fenced hunks from CORE_PATCH_REGISTER, run `--testsuite FloatingLicenses` as
the regression canary. Observer extraction (KCP-003/004) would shrink the
hunk set if upstream's model events stabilize.
