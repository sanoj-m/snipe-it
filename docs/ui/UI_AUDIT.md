# UI Audit — Killa Asset

Formal record of the completed UI audit. Rules authority:
[skills/killa-design/SKILL.md](../../skills/killa-design/SKILL.md) · quick
reference: [UI_GUIDELINES](UI_GUIDELINES.md).

Classes: BUG · INCONSISTENCY · ACCESSIBILITY · RESPONSIVE · DESIGN-SYSTEM ·
UPSTREAM-DIVERGENCE. Severities: HIGH / MEDIUM / LOW.

## Findings

| # | Finding | Evidence | Class | Severity | Status |
|---|---|---|---|---|---|
| UI-A-01 | ~90 lines dead/contradictory flyout "corridor" CSS still live | `public/css/killa-v2.css:1400-1525` | BUG (dead code) | MEDIUM | OPEN |
| UI-A-02 | Comment/behavior drift: comment says 400ms, code holds 800ms | `killa-v2.css:1520` vs `layouts/default.blade.php:1095` | INCONSISTENCY | LOW | OPEN |
| UI-A-03 | Duplicate diverging tokens: `--k-input-disabled-bg`, `--k-scrollbar-thumb` | `forms.css:13` vs `dark.css:15`; tables vs dark | DESIGN-SYSTEM | MEDIUM | OPEN |
| UI-A-04 | 6× `md5_file()` per request (cache-busting) in both layouts | `layouts/default.blade.php`, `layouts/basic.blade.php` | BUG (perf) | MEDIUM | OPEN |
| UI-A-05 | SKILL.md drift: says 1 CSS file / 6px radius; reality 6 files / 12px | `skills/killa-design/SKILL.md` vs `public/css/killa-v2*.css` | INCONSISTENCY | LOW | OPEN |
| UI-A-06 | `licenses/view.blade.php`: inline styles + ~320-line divergence from upstream | KCP-018 | UPSTREAM-DIVERGENCE | HIGH | OPEN |
| UI-A-07 | No responsive rules below 768px anywhere in the theme | `public/css/killa-v2*.css` | RESPONSIVE | MEDIUM | OPEN |
| UI-A-08 | Google Fonts CDN loaded on login pages (GDPR/offline concern) | `layouts/basic.blade.php` | ACCESSIBILITY/privacy | MEDIUM | OPEN |
| UI-A-09 | Flyout hover JS inlined in layout instead of a JS file | `layouts/default.blade.php` | DESIGN-SYSTEM | LOW | OPEN |
| UI-A-10 | Footer links deleted from upstream layout (attribution/community links) | `layouts/default.blade.php` (KCP-025), BR-04 | UPSTREAM-DIVERGENCE | MEDIUM (compliance) | OPEN |

## Page-area checklist

Columns: Header / Breadcrumb / Buttons / Tables / Forms / Modals / Responsive /
A11y. Values: PASS · FAIL(#) · N/A · — (not yet audited).

| Area | Header | Breadcrumb | Buttons | Tables | Forms | Modals | Responsive | A11y | Notes |
|---|---|---|---|---|---|---|---|---|---|
| Dashboard | PASS | PASS | PASS | PASS | PASS | PASS | — | — | Audited; UI-A-07 applies |
| Assets | — | — | — | — | — | — | — | — | Not yet audited |
| Licenses — list | PASS | PASS | PASS | PASS | PASS | PASS | — | — | Audited |
| Licenses — view/edit/checkout | PASS | PASS | FAIL(UI-A-06) | PASS | FAIL(UI-A-06) | PASS | — | — | Audited; inline styles + divergence |
| Accessories | — | — | — | — | — | — | — | — | Not yet audited |
| Consumables | — | — | — | — | — | — | — | — | Not yet audited |
| Components | — | — | — | — | — | — | — | — | Not yet audited |
| People (Users) | — | — | — | — | — | — | — | — | Not yet audited |
| Companies | — | — | — | — | — | — | — | — | Not yet audited |
| Locations | — | — | — | — | — | — | — | — | Not yet audited |
| Departments | — | — | — | — | — | — | — | — | Not yet audited |
| Models | — | — | — | — | — | — | — | — | Not yet audited |
| Categories | — | — | — | — | — | — | — | — | Not yet audited |
| Manufacturers | — | — | — | — | — | — | — | — | Not yet audited |
| Suppliers | — | — | — | — | — | — | — | — | Not yet audited |
| Status Labels | — | — | — | — | — | — | — | — | Not yet audited |
| Custom Fields | — | — | — | — | — | — | — | — | Not yet audited |
| Reports | — | — | — | — | — | — | — | — | Not yet audited |
| Settings | — | — | — | — | — | — | — | — | Not yet audited |
| Profile | — | — | — | — | — | — | — | — | Not yet audited |
| Checkout flows | — | — | — | — | — | — | — | — | Not yet audited |
| Checkin flows | — | — | — | — | — | — | — | — | Not yet audited |
| Audit | — | — | — | — | — | — | — | — | Not yet audited |
| Import | — | — | — | — | — | — | — | — | Not yet audited |
| Orders (upstream) | — | — | — | — | — | — | — | — | Not yet audited |
| Sync adapters (upstream) | — | — | — | — | — | — | — | — | Not yet audited |
| Floating Licenses | — | — | — | — | — | — | — | — | Not yet audited |
| Login/Auth | PASS | N/A | PASS | N/A | PASS | N/A | — | FAIL(UI-A-08) | Audited; Fonts CDN issue |
| Error pages | — | — | — | — | — | — | — | — | Not yet audited |
| Layouts (default/basic) | PASS | N/A | PASS | N/A | N/A | N/A | FAIL(UI-A-07) | FAIL(UI-A-08) | Audited; UI-A-01/02/04/09/10 |

### Spot-checked upstream pages (theme-layer inheritance check)

These six upstream pages were spot-checked to confirm the killa-v2 theme layer
styles them without per-page edits — all **PASS via theme layer**:

`hardware/index` · `users/index` · `licenses/index` · `settings/index` ·
`hardware/checkout` · `reports/index`

PASS here means: header/breadcrumb/buttons/tables/forms inherit tokens and
cascade correctly with no page-specific override needed. They are still listed
as "not yet audited" in the full matrix above (deep per-column audit pending).
