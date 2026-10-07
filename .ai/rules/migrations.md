---
paths:
  - 'database/migrations/**'
---

# Migrations

## No foreign-key constraints
Relationship columns are plain `integer('other_id')` columns (nullable and indexed as needed). Do not add `foreignId()`, `foreignIdFor()`, `constrained()`, or `->foreign()->references()` — this schema has no FK constraints and referential integrity is enforced in application code.

## Killa-added migration naming
Killa-added migrations must use a `killa_` infix after the timestamp (e.g. `2026_10_15_000000_killa_add_...`) to eliminate filename collisions with upstream migrations. Existing Killa migrations predate this rule and are grandfathered (see docs/database/CUSTOM_SCHEMA.md).
