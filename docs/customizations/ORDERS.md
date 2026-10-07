# Orders — UPSTREAM feature, not a Killa customization

`Order` / `OrderItem` are **upstream Snipe-IT v8.8.0 code**. Verified:

```bash
git cat-file -e v8.8.0:app/Models/Order.php   # present in the upstream tag
```

There are zero Killa modifications to these files in the v8.8.0 → master diff.

## Pointers

| What | Where |
|---|---|
| Models | `app/Models/Order.php`, `app/Models/OrderItem.php` |

## Correction notice

Earlier handoff documentation misclassified Orders as a Killa customization
requiring isolation. That was wrong — see
[ADR-0004](../adr/0004-orders-and-sync-are-upstream.md). No KCP registration,
no fencing, no removal strategy applies. If a Killa-side change ever appears
in these files, register it in [CORE_PATCH_REGISTER](CORE_PATCH_REGISTER.md)
at that time.
