---
name: ui-ux-pro-max
description: >-
  UI/UX design intelligence with searchable local database (styles, palettes,
  typography, UX guidelines, charts, stacks). Use when designing, building,
  reviewing, or improving UI/UX: pages, components, color, typography, layout,
  accessibility, animation, mobile/portal UX, or visual polish. Prefer alongside
  the project Tailwind design system for Bufete Melián.
---

# UI/UX Pro Max — Design Intelligence

Searchable design rules for professional UI/UX decisions. Use before implementing or refactoring visual UI.

## Project override (Bufete Melián)

This repo already has a brand system. **Do not invent a new palette.**

1. Read `.cursor/skills/tailwind-design-system/SKILL.md` first for tokens (`primary`, `muted`, `.panel`, Badge variants).
2. Use this skill for **UX decisions**: hierarchy, density, mobile touch, forms, progressive disclosure, a11y, anti-patterns.
3. Stack for this project: **`react`** + **`shadcn`** + **`html-tailwind`** (not a greenfield landing default).
4. Prefer Lucide icons already in the frontend (no emoji as icons).

## Prerequisites

```bash
python3 --version || python --version
```

## Search tool path (this repo)

Always run from the project root:

```bash
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "<query>" [options]
```

## When to apply

Use for UI structure, visual design, interaction patterns, or UX quality control.

Skip for pure backend, API/DB, DevOps, or non-visual scripts — unless the change affects look, feel, motion, or interaction.

## Priority checklist

| Priority | Focus | Must have | Avoid |
|----------|--------|-----------|--------|
| 1 | Accessibility | Contrast 4.5:1, labels, keyboard, aria | Removing focus rings, icon-only without label |
| 2 | Touch | 44×44px targets, 8px+ gaps, loading feedback | Hover-only actions |
| 3 | Performance | Lazy media, reserve space | Layout shift / CLS |
| 4 | Style | Consistent system, SVG icons | Mixing unrelated styles, emoji icons |
| 5 | Layout | Mobile-first, no horizontal scroll | Fixed px-only widths |
| 6 | Type & color | 16px base, semantic tokens | Body &lt; 12px, raw hex in components |
| 7 | Motion | 150–300ms, meaningful | Decorative-only, no reduced-motion |
| 8 | Forms | Visible labels, errors near fields | Placeholder-as-label |
| 9 | Navigation | Predictable back, clear hierarchy | Overloaded nav |
| 10 | Charts | Legends + accessible colors | Color-only meaning |

## Workflow

### 1. Analyze

Extract product type, audience, style keywords, industry, stack.

### 2. Design system search (new pages / big redesigns)

```bash
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "legal services professional portal mobile" --design-system -p "Bufete Melián"
```

Then **filter** recommendations through the existing navy/primary tokens — do not replace brand colors.

Persist only if the user asks:

```bash
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "legal professional portal" --design-system --persist -p "Bufete Melian" --output-dir .
```

### 3. Domain deep-dives

```bash
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "<keyword>" --domain <domain> [-n 5]
```

| Need | Domain |
|------|--------|
| UX / a11y / mobile | `ux` |
| Styles | `style` |
| Colors | `color` |
| Typography | `typography` |
| Charts | `chart` |
| Landing structure | `landing` |
| React performance | `react` |
| Icons | `icons` (if available) |

### 4. Stack guidelines

```bash
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "forms layout responsive" --stack react
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "components forms" --stack shadcn
python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "spacing responsive" --stack html-tailwind
```

## Portal cliente / mobile (project focus)

When improving client portal screens:

1. Search UX: `python3 .cursor/skills/ui-ux-pro-max/scripts/search.py "mobile forms progressive disclosure touch" --domain ux`
2. Reduce noise: one primary action per viewport, fewer competing steppers/labels.
3. Touch targets ≥ 44px; cards themselves are the CTA (no redundant “Continuar”).
4. Keep brand navy via `primary` / design-system skill.

## Professional UI rules (always)

- SVG/Lucide icons only — no emoji icons
- `cursor-pointer` + hover feedback on clickable cards
- Transitions 150–300ms; no layout-shifting scales
- Semantic tokens (`bg-primary`, `text-muted-foreground`) — no hardcoded hex
- Focus rings visible
- Responsive check: 375 / 768 / 1024

## Pre-delivery checklist

- [ ] No emoji icons; Lucide consistent
- [ ] Clickable elements have clear affordance
- [ ] Contrast ≥ 4.5:1 for body text
- [ ] No horizontal scroll on mobile
- [ ] Labels on inputs; errors near fields
- [ ] `prefers-reduced-motion` respected if animating
- [ ] Tokens match `tailwind-design-system` skill

## If search returns 0 results

Retry with broader keywords. If still empty, use the priority table above and tell the user the recommendation is from defaults, not a DB match.
