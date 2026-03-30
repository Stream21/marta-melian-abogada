---
name: tailwind-design-system
description: >-
  Design system and Tailwind CSS conventions for Bufete Melián frontend.
  Defines semantic tokens, component classes, Badge variants, and forbidden
  patterns. Use when creating or editing React components, pages, or styles
  in the frontend directory. Ensures consistent use of CSS variables, CVA
  variants, and @layer component classes instead of hardcoded colors.
---

# Tailwind Design System — Bufete Melián

## Golden Rule

**Never use hardcoded hex colors** (e.g. `#1e3a8a`, `#162d6e`).
Always use semantic Tailwind tokens mapped to CSS variables.

---

## 1. Semantic Color Tokens

All colors resolve through CSS variables defined in `frontend/src/index.css`.
The brand navy (`#1e3a8a`) is mapped to `--primary`.

### Core palette (use these, not raw colors)

| Token | Usage |
|-------|-------|
| `primary` / `primary-foreground` | Brand navy — buttons, sidebar bg, links, focus rings |
| `foreground` | Default text (replaces `gray-900`, `slate-900`) |
| `muted-foreground` | Secondary text (replaces `gray-500`, `slate-500`, `gray-400`) |
| `muted` / `muted/50` | Subtle backgrounds (replaces `gray-50`, `slate-50`, `bg-gray-100`) |
| `card` / `card-foreground` | Card/panel surfaces (replaces `bg-white`) |
| `border` | Borders (replaces `border-gray-100`, `border-gray-200`, `border-slate-*`) |
| `destructive` | Errors and danger states (replaces `text-red-*` for errors) |
| `ring` | Focus ring color (same as primary) |

### Opacity modifiers for `primary`

| Class | Purpose |
|-------|---------|
| `bg-primary` | Solid brand background (sidebar, avatar, buttons) |
| `bg-primary/10` | Light tinted background (icon containers, active rows) |
| `bg-primary/5` | Hover tint on table rows |
| `text-primary` | Brand-colored text (headings, links) |
| `text-primary-foreground` | White text on primary bg |
| `text-primary-foreground/70` | Sidebar nav links (inactive) |
| `border-primary/20` | Subtle brand-tinted borders |

### Status colors (Tailwind palette, not CSS vars)

For categorical data where the color carries meaning (badges, urgency, charts):

- **Green/success**: `emerald-50`, `emerald-100`, `emerald-700`
- **Orange/warning**: `amber-50`, `amber-100`, `amber-700`; or `orange-*`
- **Red/danger**: Use `destructive` token for errors; `red-*` for urgency badges
- **Blue/info**: `blue-50`, `blue-100`, `blue-700`
- **Purple/violet/teal/indigo**: Acceptable for categorical badges (tipo de caso, etc.)

---

## 2. `@layer components` — Reusable CSS Classes

Defined in `frontend/src/index.css`. Use these instead of repeating utility chains.

| Class | What it replaces | Typical element |
|-------|-----------------|-----------------|
| `.panel` | `bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden` | Card/section wrapper |
| `.panel-header` | `flex items-center gap-3 p-6 border-b border-gray-100` | Header row with icon + title |
| `.panel-header-icon` | `p-2 bg-blue-50 rounded-lg text-[#1e3a8a]` | Icon container inside header |
| `.panel-title` | `text-gray-900 text-lg font-bold leading-tight` | Panel heading text |
| `.panel-footer` | `p-4 border-t border-gray-100 bg-gray-50/50 text-center` | Footer with link/action |
| `.link-brand` | `text-sm text-[#1e3a8a] font-bold hover:text-blue-900` | Brand-colored text link |
| `.page-title` | `text-2xl font-semibold text-gray-800` | Page heading `<h1>` |
| `.page-subtitle` | `mt-1 text-sm text-gray-500` | Description below page title |
| `.section-label` | `text-[11px] font-bold uppercase tracking-widest text-gray-500` | Table headers, form labels, breadcrumbs |
| `.input-field` | Long chain of focus/ring/border utilities | `<input>`, `<select>` outside shadcn |
| `.table-toolbar` | `flex flex-wrap items-center gap-3 p-5 border-b` | Filter bar above tables |

### Usage example

```tsx
// BEFORE (verbose)
<div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div className="flex items-center gap-3 p-6 border-b border-gray-100">
    <div className="p-2 bg-blue-50 rounded-lg text-[#1e3a8a]">

// AFTER (clean)
<div className="panel">
  <div className="panel-header">
    <div className="panel-header-icon">
```

---

## 3. Badge Variants (CVA)

`src/components/ui/badge.tsx` uses `class-variance-authority`.
Available `variant` values:

| Variant | Visual | Use for |
|---------|--------|---------|
| `default` | Solid primary bg | Active status, primary tag |
| `secondary` | Muted bg | Draft, neutral, void |
| `destructive` | Red bg | Errors, overdue |
| `outline` | Border only | Neutral outline tag |
| `success` | Emerald light bg | Paid, completed, positive |
| `warning` | Amber light bg | Pending, outstanding |
| `info` | Blue light bg | Informational, in-progress |

```tsx
<Badge variant="success">Pagada</Badge>
<Badge variant="warning">Pendiente</Badge>
```

**Do not** override Badge colors with `className` — use the variant prop.

---

## 4. Forbidden Patterns

| Instead of | Use |
|-----------|-----|
| `bg-[#1e3a8a]`, `text-[#1e3a8a]` | `bg-primary`, `text-primary` |
| `focus:ring-[#1e3a8a]` | `focus:ring-ring` or `.input-field` class |
| `bg-white` (for panels) | `bg-card` or `.panel` |
| `border-gray-100`, `border-gray-200` | `border-border` or just `border` (base rule adds it) |
| `text-gray-900` | `text-foreground` |
| `text-gray-500`, `text-gray-400` | `text-muted-foreground` |
| `bg-gray-50`, `bg-gray-100` | `bg-muted` or `bg-muted/50` |
| `text-slate-*`, `bg-slate-*` | Use `foreground`/`muted-foreground`/`muted` tokens |
| `bg-blue-50 text-[#1e3a8a]` (icon containers) | `.panel-header-icon` or `bg-primary/10 text-primary` |
| Long input utility chains | `.input-field` class |
| `className="bg-emerald-100 text-emerald-700"` on Badge | `variant="success"` |

---

## 5. Sidebar & Dark Surfaces

The sidebar uses `bg-primary` as background. Text on it uses `primary-foreground` with opacity:

| Element | Class |
|---------|-------|
| Nav link (inactive) | `text-primary-foreground/70` |
| Nav link (hover) | `hover:text-primary-foreground hover:bg-primary-foreground/5` |
| Nav link (active) | `[&.active]:bg-primary-foreground/10 [&.active]:text-primary-foreground` |
| Divider | `bg-primary-foreground/10` |
| Subtle bg (footer) | `bg-black/10` |
| Icon button | `text-primary-foreground/40 hover:text-primary-foreground` |

---

## 6. Component Hierarchy (shadcn/ui)

- `src/components/ui/` — shadcn primitives. **Do not modify core styles**, but extending variants (like Badge) is fine.
- Use `Button` component with its built-in variants instead of raw `<button>` with utility classes.
- `default` Button variant already renders as `bg-primary text-primary-foreground`.
- For colored buttons outside the brand (Holded = orange, Stripe = violet, WhatsApp = green), override via `className`.

---

## Quick Reference: File Locations

| File | Purpose |
|------|---------|
| `frontend/src/index.css` | CSS variables (`:root`) + `@layer components` |
| `frontend/tailwind.config.js` | Color mapping to `hsl(var(--*))` |
| `frontend/src/lib/utils.ts` | `cn()` helper (clsx + tailwind-merge) |
| `frontend/src/components/ui/badge.tsx` | Badge with CVA variants |
| `frontend/src/components/ui/button.tsx` | Button with CVA variants |
