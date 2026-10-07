# Sync Adapters — UPSTREAM feature, not a Killa customization

The sync-adapter subsystem (`SyncAdapterConfig`, `SyncAdapterInstance`, the
`app/SyncAdapters/` tree, and the `snipeit:pull-inventory` /
`snipeit:push-inventory` commands) ships in **upstream Snipe-IT v8.8.0** and
carries zero Killa modifications. Earlier handoff documentation misclassified
it as a Killa customization; corrected by
[ADR-0004](../adr/0004-orders-and-sync-are-upstream.md). No isolation, fencing,
or KCP registration applies.

## Architecture summary (from the upstream code)

- **Base class** `app/SyncAdapters/SyncAdapter.php` — every adapter extends it.
  Owns all shared behavior: URL storage with SSRF-guarded validation
  (`ExternalUrl` rule blocks loopback/RFC-1918/metadata targets), encrypted
  secret storage (`Crypt`), blank-preserves-existing save semantics,
  active/heartbeat toggles, per-field target mapping and pull/push direction.
  Subclasses essentially declare only `typeSlug()`, `typeLabel()`,
  `settingsSchema()` (auth fields: text/password/textarea/checkbox/select/
  multiselect/category/field_map), and `pull(): iterable`.
- **Discovery** — `SyncAdapter::allTypes()` glob-scans
  `app/SyncAdapters/*/*Adapter.php`; no central registry. `factory()` hydrates
  an adapter from a `SyncAdapterInstance` row.
- **Instances & config** — `SyncAdapterInstance` rows hold slug/label/company/
  active/last_sync state; `SyncAdapterConfig` is a per-instance key-value store
  (`get`/`put`/`forget`/`has`) for URL, credentials, mappings
  (`mapping.{field}`), directions (`direction.{field}` = pull/push/skip/both),
  group mappings (`group_mapping.{vendorGroupId}` → company id), cached vendor
  groups/custom fields, and push settings (`push_dry_run`,
  `push_notes_template`, `push_notes_target`).
- **Records** — adapters yield `HostInventoryRecord` objects; the
  `SyncsHostFromRecord` trait owns the asset-side upsert path
  (`syncFromRecord()`): create/adopt assets (`adopt_by_serial` migration aid),
  user matching (`user_match_strategy`: none/email/username/username_then_email),
  silent assignment (`suppress_notifications` default on), optional
  `checkin_on_null_user`.
- **Push** — adapters implementing `PushableAdapter` can push
  Snipe-IT-authoritative fields back to the vendor; base provides
  `pushViaSinglePayload()` template, composed-notes templating
  (`{placeholder}` syntax incl. `{custom.Field Name}`), and a dry-run mode.
- **Field targets** — `MappingTargets` lists standard source fields
  (hostname, serial, asset_tag, model, notes, …) with shipped defaults;
  adapter-specific extras via `extraFields()` (typed text/boolean,
  `admin_defined` for tenant-labeled vendor fields).
- **Console commands** — `snipeit:pull-inventory {adapter?}`
  (`app/Console/Commands/PullInventory.php`) and
  `snipeit:push-inventory {adapter?}` (`PushInventory.php`); both accept an
  instance slug or run every enabled instance.
- **Shipped adapters** (~20): Addigy, AppleBusinessManager, CustomHttp, Fleet,
  GoogleWorkspace, Intune, Jamf (+JamfPlatform, JamfSchool), JumpCloud, Kandji,
  KaseyaVsa10, Landscape, MerakiSystemsManager, Mosyle, NinjaOne, Osctrl,
  Unifi, WorkspaceOne, Zentral.
- Asset↔vendor linkage lives in `app/Models/AssetExternalSource.php`
  (`asset_external_sources` table, matched by source + external_id).

## Correction notice

Treat all of the above as core. If a Killa-side change ever appears in these
paths, register it in [CORE_PATCH_REGISTER](CORE_PATCH_REGISTER.md) then.
