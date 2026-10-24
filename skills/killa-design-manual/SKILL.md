---
name: killa-design-manual
skill_id: killa-design-manual
version: 1.0.0
applies_to: All Killa ERM UI/UX design, redesign, page builds, component redesigns, visual polish, and layout reorganizations
priority: HIGH - Use whenever the user mentions "design", "redesign", "make it more colorful", "simplify UX", "new UI", or asks to reorganize a page
companion_skills:
  - ui-ux-pro-max
  - intent
  - evaluate
  - journey
  - specify
  - articulate
  - include
  - fortify
  - hallmark
  - ckm-ui-styling
  - ckm-design-system
  - impeccable
---

# Killa ERM Manual

**The canonical design guideline for Killa ERM.**

This manual defines how every agent must approach UI/UX work inside Killa ERM. It is not optional. When the user says *"design"*, *"make it more colorful"*, *"simplify UX"*, *"new UI"*, or asks to *reorganize* any interface, this skill activates automatically and must be followed before any code is emitted.

The core principle: **rebuild the structure and visual language without changing what the tool does.**

---

## When This Skill Activates

Activate on any of these user signals (including casual phrasing):

| Signal | Examples |
|--------|----------|
| Explicit design ask | "design this page", "redesign the settings", "build a new UI" |
| Color/vibe ask | "make it more colorful", "more vibrant", "less boring", "bolder" |
| UX simplification | "simplify the UX", "make it simpler", "cleaner", "easier to use" |
| Reorganization | "reorganize this", "rearrange", "new layout", "better structure" |
| Visual polish | "polish this", "improve the UI", "modernize", "upgrade the look" |
| Page/component redesign | "redesign the dialog", "new card design", "settings page redesign" |

If any signal fires, **stop** and run the full protocol below before writing code.

---

## Phase 0 — Pre-Flight: Read the Living Reference

Before designing anything, the agent must study the **established administration/settings design language** already proven in the codebase. These pages are the single source of truth for Killa ERM UI patterns.

### Required Reference Files (READ ALL)

Read these files in this order:

1. **`app/src/modules/settings/pages/SecurityPage.tsx`** — Core card architecture
2. **`app/src/modules/settings/pages/GeneralPage.tsx`** — Bento-style dashboard patterns
3. **`app/src/modules/settings/SettingsLayout.tsx`** — **Primary reference for sidebar layout, navigation sections, color token system, and section cards**
4. **`app/src/modules/settings/pages/ProjectsPage.tsx`** — **Primary reference for page headers, stat tiles, filter chips, tab bars, and table design**
5. **`app/src/modules/hrms/pages/DashboardPage.tsx`** — **Primary reference for HR/HRMS page cards (`ConfigPanel` + `SectionTitle` icon-chip headers), KPI tiles, list rows, mini stat tiles, and the full-width HR page shell**
6. **`app/src/modules/hrms/components/HrmsPageHeader.tsx`** — **Primary reference for HR banner headers (accent-parameterized) and clickable stat chips**
7. **`app/src/components/avatar-sync/AvatarSyncSummary.tsx`** — Compact inline card pattern
8. **`app/src/components/avatar-sync/AvatarSyncSettings.tsx`** — Tabbed dialog pattern
9. **`AGENTS.md`** § `⚠️ DESIGN CONSISTENCY RULE` — Hard rules

### Extract These Design Tokens

From the reference files, write down:

| Token Category | What to Capture |
|----------------|-----------------|
| **Card shell** | `ConfigPanel` patterns: `rounded-2xl border bg-card shadow-sm` |
| **Card header — Settings DNA** | `px-5 py-3.5 border-b bg-muted/30` with small icon + semibold title (settings pages only) |
| **Card header — HR page DNA** | `SectionTitle` inside `p-5`: solid `w-10 h-10 rounded-xl` accent tile (via `moduleId`) + white icon + title + subtitle — never shadcn `CardHeader`/`CardTitle` (HR/HRMS pages) |
| **Section title** | Icon circle (`w-8 h-8 rounded-lg bg-{color}-100`) + title + optional subtitle |
| **Spacing density** | `p-5`, `space-y-4`, `gap-4`, `gap-3` — compact but breathable |
| **Color application** | Pastel icon containers (`/10`–`/20` tints), colored left borders, gradient top bars |
| **Badge style** | Small inline chips with dot indicators, percentage-based color coding |
| **Button hierarchy** | `variant="outline"` for secondary actions, `variant="default"` for primary, soft tones |
| **Dialog sizing** | `sm:max-w-lg` for simple, `sm:max-w-[900px]` for wide tables, `sm:max-w-[1280px]` for complex dashboards |
| **Tab patterns** | `SmoothTab` for page-level navigation; `TabsList` only for inline cards, dialogs, and sub-section toggles |
| **Empty/loading states** | Centered spinners, icon-in-rounded-container empty states |
| **Table rows** | Status-colored avatars, prominent badges, subtle hover tints |
| **Stats cards** | `StatusCard`: colored-border tiles with soft icon chip, decorative blurred glow blob, clear metric labels |
| **Filter chips** | Toggle chips with colored dot indicators and counts |
| **Page header** | `flex items-start justify-between` with `text-2xl font-bold tracking-tight` title + muted subtitle + action button group |
| **Stat tiles** | `StatusCard` (`SettingsBlocks.tsx`): `relative overflow-hidden rounded-2xl border border-{color}-200 dark:border-{color}-800/60 bg-card p-4 shadow-sm`, `text-xs` muted label + `text-xl font-bold tracking-tight` value, `w-9 h-9 rounded-lg bg-{color}-500/10` icon chip, decorative blurred glow blob bottom-right — never hand-roll a new tile style |
| **Tab bar** | Spring-animated sliding background, icon + fully-visible title + optional badge, full-width bottom toolbar (`SmoothTab`) |
| **Dialog sections** | `rounded-xl border border-border/60 bg-background overflow-hidden shadow-sm` with colored header bar (`bg-{color}-50 border-b border-{color}-100`) |
| **Sidebar layout** | `w-[320px] border-r bg-sidebar flex flex-col shadow-sm shrink-0` with gradient-tinted section cards |
| **Nav color tokens** | Cohesive families per item: `text-{color}-600`, `bg-{color}-500/20`, `bg-{color}-500/[0.18]`, `border-{color}-500`, `bg-{color}-500` indicator |
| **Section card** | `rounded-2xl border bg-gradient-to-b p-1` with `from-{color}-500/[0.10] to-transparent border-{color}-500/25` tint |
| **List nav button** | `group relative flex items-center gap-3 rounded-xl px-3 py-2.5` with left border accent, icon container, text stack, hover chevron |
| **Grid tile** | `group relative flex flex-col items-center gap-2 rounded-xl p-3` with top border accent, icon, label, scale hover |

If your new component does not match at least **10 of these 20** tokens, stop and revise.

---

## Default Tab Component — `SmoothTab`

For **page-level** tab navigation, the canonical component is `app/src/components/ui/smooth-tab.tsx`.

### When to use it
- The tab bar sits at the top of a page or major section.
- There are 2–6 tabs.
- Tabs need icons, short descriptions, and optional count badges.
- The URL should reflect the active tab (`?tab=...` or path-based).

### When NOT to use it
- Inline card sub-tabs (use `TabsList`/`TabsTrigger`).
- Dialog or sheet section toggles.
- View-mode toggles with only 2 options (use a `ToggleGroup` or segmented control).

### Implementation pattern
```tsx
import SmoothTab, { type TabItem } from '@/components/ui/smooth-tab';
import { Tabs, TabsContent } from '@/components/ui/tabs';
import { useUrlTabState } from '@/hooks/useUrlTabState';

const tabItems: TabItem[] = [
  { id: 'overview', title: 'Overview', icon: LayoutDashboard, color: 'bg-violet-500', description: 'Summary of the section.' },
  { id: 'details', title: 'Details', icon: FileText, color: 'bg-blue-500', description: 'Detailed records and history.' },
];

export default function SomePage() {
  const [activeTab, setActiveTab] = useUrlTabState('overview');
  return (
    <Tabs value={activeTab} className="space-y-4">
      <SmoothTab items={tabItems} value={activeTab} onChange={setActiveTab} />
      <TabsContent value="overview">...</TabsContent>
      <TabsContent value="details">...</TabsContent>
    </Tabs>
  );
}
```

### Rules
1. Tab titles must be fully visible — no truncation. `SmoothTab` uses `minmax(min-content, 1fr)` columns and `whitespace-nowrap`.
2. Each tab must have a distinct `color` (solid Tailwind bg class) used for the sliding active background.
3. Each tab should have a one-sentence `description` for the header card area.
4. Preserve count badges via the `badge` prop, not by squeezing counts into the title.
5. Keep content inside `TabsContent` so accessibility and keyboard handling remain intact.

---

## Phase 1 — Intent UX Research (MANDATORY)

Before touching code, invoke the **Intent** skill system to understand the *human problem* behind the redesign.

### Required Skills to Apply

| Skill | Purpose | Minimum Output |
|-------|---------|----------------|
| `intent` | Frame the design intent and user context | One-paragraph intent statement |
| `evaluate` | Score the current design against heuristics | Ranked issue list with severity |
| `journey` | Map the user's task flow through the page | Step-by-step flow with pain points |
| `articulate` | Audit and improve labels, empty states, CTAs, tooltips | Copy recommendations |
| `include` | Check accessibility (contrast, focus, screen readers, keyboard) | A11y findings + fixes |
| `fortify` | Identify edge cases, loading states, empty states, errors | State inventory |

### How to Apply

Run these in parallel where possible:

1. **Evaluate the current page**: Use `evaluate` to score the existing design on Intent's UX dimensions (clarity, hierarchy, feedback, error prevention, efficiency, discoverability). Export a ranked punch list.
2. **Map the journey**: Use `journey` to document the user's path. Where do they enter? What decisions do they make? Where do they fail?
3. **Harden the design**: Use `fortify` to inventory all states (empty, loading, error, success, partial, first-run, no-permission, offline).
4. **Audit the words**: Use `articulate` to review every label, tooltip, empty state, error message, and CTA. Simplify to 8th-grade readability.
5. **Check inclusion**: Use `include` to verify WCAG 2.2 AA minimums for your proposed colors and interactions.

**Do not write code until you have a written summary of findings from at least 3 of these skills.**

---

## Phase 2 — Design Intelligence with UI-UX-Pro-Max

After Intent research, apply **`ui-ux-pro-max`** for the visual intelligence layer:

1. **Study options**: Generate at least 3 valid layout options for the target page/component.
2. **Score each option** against:
   - Intent UX findings (Phase 1)
   - Killa reference tokens (Phase 0)
   - Color vibrancy requirements
   - Information hierarchy clarity
   - Cognitive load reduction
3. **Pick the best option** and document why in one sentence.

### Color Rules

- Every redesign must be **more colorful** than the current version.
- Use **semantic color coding**: blue for security/settings, emerald for success/active, amber for warnings/financials, violet for appearance/creative, rose for destructive/danger, sky for info/communication.
- Each major section should have a **consistent theme color** (icon container, top border, subtle background tint).
- Never use more than **one gradient per card** and never gradient the entire card background — only subtle header bars or decorative accents.

### Accent Variety Rule (STRICT — project owner directive)

**Never wash an entire panel, card, or page in a single module color.** A monochrome violet/blue/etc. wash is an instant fail.

- The module color is reserved for **primary identity elements only**: active tab, primary CTA, module icon, key brand surfaces (e.g. profile hero gradient).
- **Secondary section headers, labels, chips, and stat accents** must use subtle **semantic per-section accents** — "a bit of colors here and there, not too much":
  - sky/blue → work, info, professional data
  - emerald → personal, success, active states
  - amber → compliance, warnings, financials
  - rose → destructive, expiry, danger
  - violet → creative/appearance (sparingly, never as the only accent on the page)
- Accents must be **soft tints, never solid fills**: `bg-<color>-100 text-<color>-700` paired with `dark:bg-<color>-950/50 dark:text-<color>-300`.
- **Every accent requires a `dark:` variant** — verify both themes before shipping.
- This rule is the sanctioned application of the AGENTS.md Icon Color Consistency **exception** for profile/personal dashboards: per-section semantic accents are allowed when they follow this rule; icons still keep one family within each individual section.

### Layout Rules

- **Group related controls** into mental buckets using cards or section dividers.
- **Status first**: The most important state indicators go at the top of the page/card.
- **Progressive disclosure**: Hide advanced options behind collapsible sections or secondary tabs.
- **No orphan inputs**: Every input belongs inside a card with a clear section title.
- **Consistent rhythm**: If sibling cards use `p-5`, yours uses `p-5`. If they use `rounded-2xl`, you use `rounded-2xl`.

---

## Phase 3 — Specification Before Code

Use `specify` to produce a written spec that engineering can implement. The spec must include:

1. **Macrostructure**: List every section in order.
2. **Component inventory**: Which existing components to reuse (`ConfigPanel`, `SectionCard`, `StatusPill`, etc.) and which new ones to create.
3. **Color map**: Theme color for each section.
4. **Interaction map**: Every button, toggle, tab, and dialog transition.
5. **State map**: Loading, empty, error, success, partial, permission-denied states.
6. **Responsive plan**: 320 / 375 / 768 / 1024 / 1280+ behavior.
7. **Accessibility checklist**: Focus order, ARIA labels, color contrast, motion preferences.

**Get user confirmation on the spec if the redesign changes more than 3 files or removes any existing functionality.**

---

## Phase 4 — Implementation Rules

### Hard Rules

1. **Never change what the tool does.** Rebuild structure and design; preserve business logic, API contracts, data models, and validation rules.
2. **Reuse existing sub-components.** If `ConfigPanel`, `SectionTitle`, `StatusPill`, `ToggleRow`, `FormRow`, or similar tokens exist in the target page, use them or match them exactly.
3. **Match sibling card density.** If the page uses compact `p-5` cards, do not emit spacious `p-8` cards.
4. **Use standard shadcn primitives.** `Button`, `Badge`, `Tabs`, `Dialog`, `Select`, `Popover`, `Command`, `Card` — no custom rebuilt versions unless the existing page already has them.
5. **No foreign patterns.** Forbidden unless the entire page is being redesigned:
   - Sidebar navigation inside dialogs
   - Circular progress indicators (use text/badges)
   - Full-card gradients (use flat cards with subtle header tints)
   - Oversized custom buttons next to standard outline buttons
   - Foreign icon libraries when Lucide is already used

### File Safety

- State the exact files you expect to modify before editing.
- Deletions require explicit user confirmation.
- Never delete route trees, tests, or API endpoints.
- Preserve TypeScript types; only restyle, don't retype.

### Build Verification

After implementation:

```bash
cd app && npm run build
```

This must pass. If it fails, fix before declaring done.

---

## Enterprise Theme Engine — Token & Color Rules

Killa ERM uses an **Enterprise Theme Engine** (`ThemeEngineContext`) as the single source of truth for all theming. All design work must follow this token hierarchy:

```
ThemeEngineContext → CSS variables (--primary, --background, etc.) → Tailwind tokens (bg-primary, text-foreground) → Components
```

### Approved Semantic Tokens (MANDATORY)

| Token | CSS Variable | Usage |
|-------|-------------|-------|
| `bg-background` | `--background` | Page background |
| `bg-card` | `--card` | Card/container backgrounds |
| `bg-muted` | `--muted` | Subtle backgrounds, alternating rows, disabled states |
| `bg-primary` | `--primary` | Primary action backgrounds |
| `bg-secondary` | `--secondary` | Secondary action backgrounds |
| `bg-destructive` | `--destructive` | Danger/error action backgrounds |
| `text-foreground` | `--foreground` | Primary text |
| `text-muted-foreground` | `--muted-foreground` | Secondary text, placeholders, descriptions |
| `text-primary-foreground` | `--primary-foreground` | Text on primary-colored backgrounds |
| `text-destructive-foreground` | `--destructive-foreground` | Text on destructive-colored backgrounds |
| `border-border` | `--border` | All borders, dividers |
| `ring-ring` | `--ring` | Focus rings |
| `shadow-sm/md/lg/xl` | `--shadow-sm/md/lg/xl` | Elevation (density-aware via ThemeEngine) |

### Rejected Patterns (DO NOT USE)

| Pattern | Why | Correct Alternative |
|---------|-----|---------------------|
| `bg-white` | Breaks dark mode | `bg-background` or `bg-card` |
| `bg-slate-*`, `bg-gray-*` | Not theme-aware | `bg-muted`, `bg-background`, `bg-card` |
| `text-slate-*`, `text-gray-*` | Not theme-aware | `text-foreground`, `text-muted-foreground` |
| `border-gray-*`, `border-slate-*` | Not theme-aware | `border-border` |
| `placeholder:text-gray-*` | Not theme-aware | `placeholder:text-muted-foreground` |
| `ring-gray-*`, `ring-slate-*` | Not theme-aware | `ring-ring` |
| `divide-gray-*` | Not theme-aware | `divide-border` |
| Inline HSL shadows (e.g. `shadow-[0_4px_20px_hsl(0_0%_0%/0.1)]`) | Bypasses theme shadow settings | `shadow-sm`, `shadow-md`, `shadow-lg`, `shadow-xl` |
| Hardcoded `text-white` on neutral backgrounds | Breaks on custom primary colors | `text-foreground`, `text-primary-foreground` |

### When Hardcoded Colors ARE Correct

These are the **only** exceptions:

1. **`text-white` on colored backgrounds** — Required for contrast on colored surfaces:
   - Icon containers: `bg-emerald-500 text-white`
   - Gradient headers: `bg-gradient-to-r from-blue-600 to-blue-500 text-white`
   - Status badges with solid color backgrounds

2. **`bg-black/60` overlays** — Semantically correct in both light and dark mode:
   - Modal backdrops: `bg-black/60 backdrop-blur-sm`
   - Image overlays: `bg-black/40`

3. **Semantic status colors** — These convey meaning and should not be tokenized:
   - Success: `text-emerald-500`, `bg-emerald-500`
   - Warning: `text-amber-500`, `bg-amber-500`
   - Error: `text-red-500`, `bg-red-500`
   - Info: `text-blue-500`, `bg-blue-500`

### Chart Colors

Charts must use CSS variables or read from `--primary` dynamically:

```ts
// ✅ Correct — reads CSS variable
const primaryHsl = getComputedStyle(document.documentElement).getPropertyValue('--primary');
const primaryColor = primaryHsl ? `hsl(${primaryHsl})` : fallback;

// ✅ Correct — uses hsl(var()) in SVG/Recharts
<Area stroke="hsl(var(--primary))" fill="hsl(var(--primary))" />

// ❌ Wrong — hardcoded hex/HSL
<Area stroke="#3b82f6" fill="#e2e8f0" />
```

### Working with the Theme Engine

**Read theme values:**
```tsx
import { useThemeEngine } from '@/contexts/ThemeEngineContext';

const { config, primaryColorHex, primaryColorHsl } = useThemeEngine();
// config.primaryHue, config.primarySaturation, config.radius, etc.
```

**Do NOT read from `useAccentColor()` for theme values.** It is a deprecated read-only wrapper kept for backward compatibility. Use `useThemeEngine()` directly.

**Never write CSS variables from components.** `ThemeEngineContext` is the sole DOM writer. Components read via Tailwind tokens only.

---

## Phase 5 — Handoff Checklist

Before finishing, verify:

- [ ] All findings from `evaluate` are addressed or explicitly deprioritized with reason
- [ ] At least 3 Intent skills were applied (Phase 1)
- [ ] `ui-ux-pro-max` generated and compared multiple layout options
- [ ] Design matches ≥ 10 of 20 reference tokens from Phase 0
- [ ] Page is more colorful than before without breaking accessibility
- [ ] Loading, empty, error, and success states are explicitly handled
- [ ] `cd app && npm run build` passes
- [ ] No business logic was changed — only structure and visual language
- [ ] New/changed components use existing tokens and shadcn primitives
- [ ] **No hardcoded neutral colors** (`bg-white`, `bg-gray-*`, `text-slate-*`, etc.) — only semantic tokens
- [ ] **Chart colors use CSS variables** (`hsl(var(--primary))`) not hardcoded hex/HSL
- [ ] **HR/HRMS page cards use the HR card standard** (`ConfigPanel` + `p-5` + `SectionTitle` icon-chip header; no shadcn `CardHeader`, no muted settings header bar) and the page shell is full width (no `max-w` cap)
- [ ] `AGENTS.md` session log is updated with the design work

---

## Anti-Patterns (Instant Fail)

An implementation fails this manual if it contains any of:

| Anti-Pattern | Example |
|--------------|---------|
| **Island design** | A card that looks completely different from its siblings on the same page |
| **Gradients everywhere** | Multiple full-card gradients when siblings are flat |
| **Dialog sidebar** | Vertical tab sidebar inside a dialog when the app uses top tabs |
| **Button zoo** | Mixing 3+ button styles in one action bar |
| **Icon inconsistency** | Phosphor or custom icons mixed with Lucide without system reason |
| **Spacing chaos** | `p-3` next to `p-8` next to `p-6` on equivalent cards |
| **Missing states** | No loading or empty state defined |
| **Logic drift** | Changed validation, API shape, or data flow while "only redesigning" |

If any anti-pattern is present, stop and revise before handing off.

---

## Quick Reference: Settings Page Design DNA

Use this as a cheat sheet after reading Phase 0 files.

```
Layout:      ConfigPanel cards in a 1-column or 2-column grid
Card:        rounded-2xl border bg-card shadow-sm
Header:      px-5 py-3.5 border-b bg-muted/30, small icon + semibold title
Body:        p-5 space-y-4
Section:     icon circle (w-8 h-8 bg-{color}-100) + title + optional subtitle
Active item: colored left border or top accent bar
Badges:      small dot + label inline chips
Buttons:     outline for secondary, default for primary
Tabs:        top horizontal, bg-muted/50, small rounded triggers
Dialogs:     sm:max-w-lg | sm:max-w-[900px] | sm:max-w-[1280px]
Tables:      status avatar + name + code badge + hover tint
Stats:       StatusCard — colored border + icon chip + corner glow blob (see Stat Tiles DNA)
Filters:     toggle chips with colored dots and counts
```

### SettingsLayout Sidebar Design DNA (Reference Standard)

Use these tokens when building any sidebar, navigation panel, or settings menu:

**Sidebar Container**
```
Layout:      flex h-[calc(100vh-4rem)] -mx-6 -mt-6 bg-muted/20
Sidebar:     w-[320px] border-r bg-sidebar flex flex-col shadow-sm shrink-0
Content:     flex-1 overflow-auto + mx-auto p-8 xl:p-12 max-w-[1600px]
```

**Sidebar Header**
```
Container:   p-5 border-b
Icon:        p-2.5 rounded-xl bg-gradient-to-br from-primary to-primary/80
             shadow-md shadow-primary/20
Badge:       absolute -top-1 -right-1 w-4 h-4 rounded-full bg-amber-400
             border-2 border-background
Title:       text-lg font-bold tracking-tight
Subtitle:    text-[11px] text-muted-foreground font-medium
```

**Section Card (Collapsible)**
```
Card:        rounded-2xl border bg-gradient-to-b p-1
Tint:        from-{color}-500/[0.10] to-transparent border-{color}-500/25
Header:      flex items-center gap-2 w-full px-3 py-2.5 text-left rounded-xl
             hover:bg-foreground/[0.02] transition-colors
Title:       text-xs font-semibold text-muted-foreground uppercase tracking-wider
Chevron:     w-3.5 h-3.5 ml-auto text-muted-foreground transition-transform
             rotate-90 when expanded
Content:     max-h-0 opacity-0 → max-h-[800px] opacity-100 transition-all duration-300
```

**Color Token System (Per Nav Item)**
```
text:        text-{color}-600        (icon/text color)
bgColor:     bg-{color}-500/20       (icon container background)
activeBg:    bg-{color}-500/[0.18]   (active item background)
borderColor: border-{color}-500      (border accent)
indicator:   bg-{color}-500          (left/top accent bar)
```

**Core Nav Button (List Row)**
```
Container:   group relative flex items-center gap-3 rounded-xl px-3 py-2.5
             transition-all duration-200
Active:      bg-card shadow-md ring-1 ring-foreground/10
Inactive:    hover:bg-card/80 hover:shadow-sm
Left accent: absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 rounded-r-full
Icon box:    flex items-center justify-center w-9 h-9 rounded-lg
             transition-all duration-200 shrink-0
             active: full opacity + bgColor + color
             inactive: opacity-80 group-hover:opacity-100
Label:       text-sm font-medium leading-tight
Description: text-[11px] text-muted-foreground leading-tight mt-0.5
Chevron:     w-4 h-4 shrink-0 transition-all duration-200
             active: opacity-100 translate-x-0
             inactive: opacity-0 -translate-x-1 group-hover:opacity-40 group-hover:translate-x-0
```

**Module Grid Tile**
```
Container:   group relative flex flex-col items-center justify-center gap-2
             rounded-xl p-3 transition-all duration-200 text-center
Active:      bg-card shadow-md ring-1 ring-foreground/10 scale-[1.02]
Inactive:    hover:bg-card/80 hover:shadow-sm hover:scale-[1.02]
Top accent:  absolute top-0 left-1/2 -translate-x-1/2 h-[3px] w-12 rounded-b-full
Icon:        flex items-center justify-center w-10 h-10 rounded-xl
             active: shadow-sm scale-110
             inactive: opacity-80 group-hover:opacity-100 group-hover:scale-105
Label:       text-xs font-medium leading-tight
```

**Admin Nav Button (Compact Row)**
```
Container:   group relative flex items-center gap-2.5 rounded-lg px-2.5 py-2
             transition-all duration-200
Active:      bg-card shadow-md ring-1 ring-foreground/10
Inactive:    hover:bg-card/80
Left accent: absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-6 rounded-r-full
Icon box:    flex items-center justify-center w-7 h-7 rounded-md
Label:       text-sm (active: font-medium)
```

**User Profile Card (Bottom)**
```
Container:   p-3 border-t bg-gradient-to-t from-muted/40 to-transparent
Card:        flex items-center gap-3 px-3 py-3 rounded-xl bg-card border
             shadow-sm hover:shadow-md transition-shadow duration-200
Status dot:  w-1.5 h-1.5 rounded-full (admin=primary, user=blue-500)
Name:        font-semibold text-sm truncate leading-tight
Role:        text-[11px] text-muted-foreground truncate font-medium
```

### ProjectsPage Design DNA (Reference Standard)

Use these tokens when building any settings/admin page with stats, tables, or tabs:

**Page Header**
```
Container:   flex items-start justify-between
Title:       text-2xl font-bold tracking-tight
Subtitle:    text-muted-foreground text-sm mt-1
Actions:     flex gap-2 (outline secondary, default primary, h-10, gap-2)
```

**Stat Tiles (N-up grid)** — canonical component: `StatusCard` from `app/src/modules/settings/components/SettingsBlocks.tsx`. Reference implementation: the Audit Trail stats row (`/settings/audit`). Do NOT hand-roll page-local tile components — pass `color` (named palette) or `moduleId` (theme-engine tokens) to `StatusCard`.
```
Grid:        grid grid-cols-2 md:grid-cols-4 gap-3 (md:grid-cols-5 for 5 tiles)
Card:        relative overflow-hidden rounded-2xl border bg-card p-4 shadow-sm
             border-{color}-200 dark:border-{color}-800/60
             (moduleId path: border-[hsl(var(--mod-{id})/0.3)] via tokens.softBorder)
Label:       text-xs text-muted-foreground font-medium
Value:       text-xl font-bold text-foreground tracking-tight (mt-0.5, optional suffix)
Icon chip:   w-9 h-9 rounded-lg bg-{color}-500/10 dark:bg-{color}-400/15 (top-right)
Icon:        w-4 h-4 text-{color}-600 dark:text-{color}-400
Glow blob:   absolute -bottom-5 -right-5 w-20 h-20 rounded-full opacity-40 blur-2xl
             bg-{color}-300 dark:bg-{color}-500/40
             (moduleId path: bg-[hsl(var(--mod-{id})/0.18)] via tokens.glow)
Tokens:      colorStyles map in SettingsBlocks.tsx (named colors) +
             softBorder/glow on ModuleColorTokens (moduleConfig.ts, moduleColors.ts)
Palette:     blue (total/overview), emerald (created/active), amber (warning/financial),
             violet (completed), rose (deleted/danger), red (critical), sky (info)
Rules:       one accent family per page (Icon Color Consistency rule); semantic
             per-stat colors only when each tile is a distinct category (Audit DNA);
             interactive tiles add cursor-pointer hover:shadow-md transition-shadow
Hover glow:  every .status-card glows on hover via index.css —
             0 0 26px -8px hsl(var(--primary)/0.45) (dark: /0.55), plus a
             !important override under .rp-dash-flat (which strips card shadows).
             Tune via those rules; do not re-add per-card shadow classes.
Gotcha:      the moduleId path (hsl(var(--mod-*)/0.3) border + /0.18 glow) renders
             nearly flat — use color="<named family matching the module hue>"
             (staff→violet, security→rose, hrms-leave→emerald, …) when the visible
             border + glow effect is required. Hand-rolled tiles (e.g. Documents
             DashboardStatCard, Leave SummaryTile, Launchpad HeroStat) replicate
             the same border + bottom-right glow blob + relative z-10 content.
```

**Tab Bar**
```
Container:   flex gap-1 p-1 bg-muted/50 rounded-xl w-fit
Active:      bg-primary text-primary-foreground shadow-sm
Inactive:    text-muted-foreground hover:text-foreground hover:bg-muted
Tab:         flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold transition-all
Count badge: text-xs px-2 py-0.5 rounded-full font-bold
             active: bg-primary-foreground/20 text-primary-foreground
             inactive: bg-muted-foreground/10 text-muted-foreground
```

**Filter Chips**
```
Container:   flex items-center gap-2 flex-wrap
Chip:        flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium border transition-all
Active:      {colored-bg} {colored-border} {colored-text} shadow-sm
Inactive:    bg-background border-border text-muted-foreground hover:bg-muted hover:text-foreground
Dot:         w-2 h-2 rounded-full (colored by status)
Count:       text-xs px-1.5 py-0 rounded-full font-bold
```

**Table with Filters**
```
Wrapper:     Card className="overflow-hidden border shadow-sm" + CardContent className="p-0"
Filter bar:  flex flex-col gap-4 p-5 border-b bg-gradient-to-r from-muted/30 to-background
Search:      relative flex-1 min-w-[240px] max-w-sm + Input with pl-9 + Search icon
Header row:  bg-gradient-to-r from-muted/80 to-muted/40 hover:bg-muted/50
Data row:    group hover:bg-blue-50/40 dark:hover:bg-blue-950/10 transition-colors
Status av:   w-10 h-10 rounded-xl flex items-center justify-center text-sm font-bold shrink-0
             (status-colored bg + text)
Code badge:  text-[10px] px-1.5 py-0.5 rounded bg-muted font-mono font-medium text-foreground/80
Action btn:  opacity-0 group-hover:opacity-100 transition-opacity
Empty state: w-16 h-16 rounded-2xl bg-muted flex items-center justify-center mx-auto mb-4
```

**Dialog Section Cards (Edit/Create dialogs)**
```
Section:     rounded-xl border border-border/60 bg-background overflow-hidden shadow-sm
Header:      flex items-center gap-2 px-4 py-2.5 bg-{color}-50 border-b border-{color}-100
Header icon: w-6 h-6 rounded-md bg-{color}-100 flex items-center justify-center
Header text: text-xs font-bold uppercase tracking-wider text-{color}-700
Body:        p-5 space-y-4 (or p-5 grid grid-cols-2 gap-4 for forms)
Team sec:    border-violet-200 bg-violet-50/30 (colored border variant for team sections)
```

### HR Pages Design DNA (Reference Standard — `/staff/hr/*`)

Use these tokens when building any HRMS module page (HR dashboard, Time Off, Checklist, Departments, People, Documents). Canonical implementation: `app/src/modules/hrms/pages/DashboardPage.tsx`.

**Page Shell — full width, never capped**
```
Shell:       h-full flex flex-col bg-background
Header:      <HrmsPageHeader accent="{area-accent}" icon title subtitle action stats />
Body:        flex-1 overflow-auto  →  <div className="w-full p-5 space-y-5">
Width rule:  HR pages are FULL WIDTH (PageContainer = p-6, no max-w cap).
             Do NOT wrap content in max-w-[1600px] mx-auto — that cap belongs
             to checklist pages only. Content uses the full canvas so dense
             data fits one page.
```

**Content Card (MANDATORY — the HR card standard)**
```
Shell:       <ConfigPanel className="h-full">     (rounded-2xl border bg-card shadow-sm overflow-hidden)
Inner:       <div className="p-5 h-full flex flex-col">
Header:      <SectionTitle moduleId="{hrms-area-accent-id}" icon={LucideIcon}
               title="..." subtitle="..." />
             → solid w-10 h-10 rounded-xl accent tile + white w-5 h-5 icon,
               text-base font-semibold title, text-xs muted subtitle, mb-5 gap.
               moduleId (e.g. "hrms-leave", "hrms-checklist") derives the solid
               accent tile automatically; use color={CARD_ACCENTS.x.tile} for
               semantic per-card accents on the HR dashboard only.
Trailing:    when the header carries a search input or badge, wrap in
             flex items-center justify-between and pass className="mb-0"
Body:        charts/lists below the header (min-h-[220px] for chart areas)
Forbidden:   shadcn Card / CardHeader / CardTitle on HR pages — plain text
             titles without the icon chip are island design (instant fail).
             The muted header bar (px-5 py-3.5 border-b bg-muted/30) is
             SETTINGS DNA — never use it on HR pages.
```

**Banner Stat Chips (`HrmsPageHeader stats`)**
```
Chip:        flex items-center gap-2.5 px-3 py-2 rounded-2xl border backdrop-blur-sm
Tile:        w-8 h-8 rounded-xl soft-tint icon container (STAT_COLOR_MAP palette)
Text:        text-[10px] uppercase muted label + text-sm font-bold tabular-nums value
Interactive: onClick chips render as <button aria-pressed>, active = colored
             ring + tint; chips double as one-click filters/navigation
Colors:      semantic meaning per chip (pending=amber, approved=emerald,
             rejected=rose, neutral=staff); one accent family per page
```

**Floating Filter Bar**
```
Bar:         rounded-2xl border border-{accent}-500/15 bg-card/90 backdrop-blur-sm
             shadow-sm px-4 py-3 bg-gradient-to-r from-{accent}-500/5 via-transparent
Search:      rounded-full Input with pl-9 icon (accent-colored), max-w-xs
Controls:    Select / date-range popover / sort / view toggle, all h-9
```

**Status Dot-Chips (filter row)**
```
Chip:        rounded-full border px-3 py-1.5 text-xs font-medium
Content:     w-2 h-2 rounded-full status dot + label + count
Active:      soft status tint (bg-{color}-500/10 border-{color}-500/30 text-{color}-700)
Inactive:    bg-background border-border text-muted-foreground hover:bg-muted
```

**List Rows (in-panel activity/entity rows)**
```
Row:         flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-muted/40
Icon slot:   w-9 h-9 rounded-xl bg-muted flex items-center justify-center shrink-0
Primary:     text-sm font-medium text-foreground truncate
Secondary:   text-xs text-muted-foreground truncate
Meta:        right-aligned text-xs text-muted-foreground shrink-0
```

**Mini Stat Tiles (inside a summary card)**
```
Grid:        grid grid-cols-2 sm:grid-cols-4 gap-3
Tile:        flex items-center gap-3 rounded-2xl border bg-muted/40 px-4 py-3.5
Icon:        w-9 h-9 rounded-xl bg-{accent}-500/10 dark:bg-{accent}-400/15
             + w-4 h-4 text-{accent}-600 dark:text-{accent}-400 icon
             (one accent family for ALL tile icons on the page — icon rule)
Value:       text-base font-bold tabular-nums leading-tight
Label:       text-[11px] text-muted-foreground truncate
Tone:        value text default foreground; amber/rose/emerald/sky only for
             semantic status meaning (pending, approved, utilization)
Density:     never ship bare centered number boxes (big empty tile, tiny
             centered digit) — they read as void at small data volumes.
             The icon chip + left-aligned stack is the HR standard.
```

**Charts in cards (platform floor — GOTCHA)**
```
`app/src/styles/full-page-layout.css` forces EVERY `.recharts-wrapper` to
min-width/min-height: 200px (+ width: 100%, svg height: auto). Any chart
sized under 200px is stretched to 200px and overflows its box (donut
overlapping siblings). Rules of thumb:
  - Chart areas are always ≥ 200px tall (h-56 / h-64 / h-72 or
    Math.max(200, rows * rowH) for data-driven heights).
  - Donuts are FIXED-px charts, not ResponsiveContainer: wrap in a
    relative div style={{width:200,height:200}} + <PieChart width={200}
    height={200}> (innerRadius 70 / outerRadius 96), with an
    absolute inset-0 pointer-events-none center label (total + unit).
  - Pair the donut with rich per-type rows (dot + name · req/days · % bar)
    so the card reads full even with a single data row.
  - Horizontal bar charts: always cap barSize (26) and add LabelList
    value labels, or one data row becomes a full-width slab.
  - Category YAxis tick labels wrap when the tick width is constrained —
    truncate with tickFormatter (~11 chars at 10px font / ~104px axis
    width) and let the tooltip carry the full name; widening the axis
    does not stop the wrap. RP dashboard panels use the rp-panel Card
    DNA (border-b header, module-tint icon chip, corner glow blob) —
    never PastelCard / hardcoded hex palettes there.
  - Sparse trend charts get header context chips (totals · Peak month)
    and an inline legend row (bars vs line) under the SectionTitle.
```

**Viewport-locked workbench pages (platform floor — GOTCHAS)**
```
Pattern (Leave People page): page chrome (banner header, filters, stat
strip) stays fixed; the content grid fills the remaining viewport height
and lists scroll INSIDE their cards. On xl+: xl:grow xl:basis-0 xl:min-h-0
chains; below xl the page scrolls naturally with fixed-height lists
(h-[320px] / h-[240px]) that scroll internally.

`app/src/styles/full-page-layout.css` contains three substring-attribute
traps that silently break this pattern at ALL breakpoints:
  1. `.flex-col > .flex-1, .flex-col > [class*="flex-1"] { flex:1 1 auto;
     min-height:0 }` — matches ANY class attribute containing "flex-1",
     INCLUDING responsive variants like `xl:flex-1` (substring match!).
     Specificity 0-2-0 beats every single-class min-h utility. Consequences:
       - Never write `xl:flex-1` / `lg:flex-1` — it applies everywhere.
         Use `xl:grow xl:basis-0` (+ `xl:min-h-0`) instead.
       - Never floor a flex-1 child with `min-h-[NNNpx]` — it loses to the
         forced min-height:0. Use `h-[NNNpx]` (height is not overridden).
  2. `[class*="divide-y"] { min-height: min-content }` — a divide-y scroll
     list can NEVER be shorter than its content (min-height beats
     max-height per CSS spec), so `max-h-[600px] divide-y` renders the
     FULL list (this caused the original over-long People page). For
     scrollable lists, drop divide-y and give rows their own borders:
     `border-b border-border last:border-b-0`.
  3. `.flex-col > * { flex-shrink: 0 }` — children of flex columns do not
     shrink by default; plan overflow via the nearest overflow-y-auto.

Route-area contract (App.tsx): the authenticated shell renders
<ChecklistLoginBanner /> + <div className="flex-1 min-h-0 min-w-0 flex
flex-col">{routes}</div> — h-full pages stay viewport-locked even when the
banner is visible; do not revert this to a plain h-full block.
```

**CTAs under the ThemeEngine (GOTCHA)**
```
The ThemeEngine forces background: hsl(var(--primary)) on every
[data-button-shape] [data-slot="button"][data-variant="default"].
Accent-colored primary CTAs MUST use Tailwind important modifiers:
  className="!bg-emerald-600 hover:!bg-emerald-700 text-white"
Plain bg-{color}-600 loses the cascade non-deterministically.
```

---

## Skill Invocation Summary

When the user asks for design work, your internal workflow is:

```
1. Read reference pages (Phase 0)
2. Run Intent skills: evaluate + journey + articulate + include + fortify
3. Run ui-ux-pro-max to generate and score visual options
4. Write a specify handoff document
5. Implement using existing tokens and shadcn primitives
6. Verify build and update AGENTS.md
```

**This is not optional.** Skip any phase and the design will be inconsistent with Killa ERM.
