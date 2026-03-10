# CRM Design System v1.0

**Version:** 1.0  
**Status:** Production Ready  
**Last Updated:** March 10, 2026

A complete, modern SaaS design system for OpenCRM. Inspired by industry leaders like Stripe, Linear, and Vercel.

---

## Table of Contents

1. [Design Principles](#design-principles)
2. [Design Tokens](#design-tokens)
3. [Color System](#color-system)
4. [Typography](#typography)
5. [Spacing](#spacing)
6. [Component Library](#component-library)
7. [Layout System](#layout-system)
8. [Interactive States](#interactive-states)
9. [Responsive Design](#responsive-design)
10. [Accessibility](#accessibility)

---

## Design Principles

### 1. **Simplicity**

Clean interfaces with minimal clutter. Every element has purpose.

### 2. **Consistency**

Unified component library enables predictable interactions.

### 3. **Clarity**

Clear visual hierarchy guides users to important information.

### 4. **Data-Centric**

Design prioritizes data visibility and insights.

### 5. **Accessible**

WCAG AA compliant. Works for users of all abilities.

### 6. **Responsive**

Mobile-first approach ensures great experience on any device.

---

## Design Tokens

All design elements are defined as reusable tokens.

### CSS Custom Properties

```css
:root {
  /* Colors - Primary */
  --color-primary: #4facfe;
  --color-primary-50: #f0f7ff;
  --color-primary-100: #e0f2fe;
  --color-primary-200: #bae6fd;
  --color-primary-300: #7dd3fc;
  --color-primary-400: #38bdf8;
  --color-primary-500: #0ea5e9;
  --color-primary-600: #0284c7;
  --color-primary-700: #0369a1;
  --color-primary-800: #075985;
  --color-primary-900: #0c3d66;

  /* Colors - Semantic */
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --color-info: #3b82f6;

  /* Colors - Neutral */
  --color-black: #000000;
  --color-white: #ffffff;
  --color-gray-50: #f9fafb;
  --color-gray-100: #f3f4f6;
  --color-gray-200: #e5e7eb;
  --color-gray-300: #d1d5db;
  --color-gray-400: #9ca3af;
  --color-gray-500: #6b7280;
  --color-gray-600: #4b5563;
  --color-gray-700: #374151;
  --color-gray-800: #1f2937;
  --color-gray-900: #111827;

  /* Spacing Scale */
  --space-0: 0;
  --space-1: 4px;
  --space-2: 8px;
  --space-3: 12px;
  --space-4: 16px;
  --space-5: 20px;
  --space-6: 24px;
  --space-7: 28px;
  --space-8: 32px;
  --space-10: 40px;
  --space-12: 48px;
  --space-16: 64px;

  /* Typography */
  --font-family-sans:
    -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu,
    Cantarell, sans-serif;
  --font-family-mono:
    "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, "Courier New",
    monospace;

  --font-size-12: 12px;
  --font-size-13: 13px;
  --font-size-14: 14px;
  --font-size-15: 15px;
  --font-size-16: 16px;
  --font-size-18: 18px;
  --font-size-20: 20px;
  --font-size-24: 24px;
  --font-size-28: 28px;
  --font-size-32: 32px;
  --font-size-36: 36px;
  --font-size-40: 40px;

  --font-weight-regular: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;

  --line-height-tight: 1.3;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.7;

  /* Radius */
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-full: 9999px;

  /* Shadows */
  --shadow-xs: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
  --shadow-xl: 0 12px 32px rgba(0, 0, 0, 0.15);

  /* Transitions */
  --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
  --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
}
```

---

## Color System

### Primary Color

**Blue** - Trust, professional, used for primary actions

- Primary: `#4facfe` (cyan-blue transition)
- Hover: `#0284c7` (darker blue)
- Active: `#075985` (even darker)

### Semantic Colors

- **Success:** `#10b981` (Green) - Positive actions, completed states
- **Warning:** `#f59e0b` (Amber) - Caution, needs attention
- **Danger:** `#ef4444` (Red) - Destructive, errors, warnings
- **Info:** `#3b82f6` (Blue) - Informational

### Neutral Colors

Used for text, backgrounds, borders

- **Backgrounds:** `#ffffff`, `#f9fafb`, `#f3f4f6`
- **Text:** `#111827` (dark), `#6b7280` (medium), `#9ca3af` (light)
- **Borders:** `#e5e7eb`, `#d1d5db`

### Usage in Components

| Component        | Color    | Usage                       |
| ---------------- | -------- | --------------------------- |
| Primary Button   | Primary  | Main actions                |
| Secondary Button | Gray-200 | Alternative actions         |
| Success State    | Success  | Completed, success messages |
| Error State      | Danger   | Errors, invalid             |
| Warning State    | Warning  | Alerts, notifications       |
| Links            | Primary  | Link text                   |

---

## Typography

### Font Stack

```
Primary: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, ...
Monospace: "SF Mono", Monaco, "Cascadia Code", ...
```

### Type Scale

| Element     | Size | Weight         | Line Height |
| ----------- | ---- | -------------- | ----------- |
| **H1**      | 40px | 700 (Bold)     | 1.2         |
| **H2**      | 32px | 700 (Bold)     | 1.3         |
| **H3**      | 28px | 600 (Semibold) | 1.4         |
| **H4**      | 24px | 600 (Semibold) | 1.4         |
| **H5**      | 20px | 600 (Semibold) | 1.5         |
| **H6**      | 18px | 600 (Semibold) | 1.5         |
| **Body**    | 16px | 400 (Regular)  | 1.5         |
| **Small**   | 14px | 400 (Regular)  | 1.5         |
| **Tiny**    | 12px | 400 (Regular)  | 1.4         |
| **Caption** | 13px | 500 (Medium)   | 1.5         |

### Usage

```css
h1 {
  font: 700 40px/1.2 var(--font-family-sans);
}
h2 {
  font: 700 32px/1.3 var(--font-family-sans);
}
body {
  font: 400 16px/1.5 var(--font-family-sans);
}
.label {
  font: 500 13px/1.5 var(--font-family-sans);
}
```

---

## Spacing

**Base Unit:** 4px (scale from 4px to 64px)

### Spacing Scale

```
--space-1: 4px    (minimal)
--space-2: 8px    (xs)
--space-3: 12px   (sm)
--space-4: 16px   (md) ← default
--space-6: 24px   (lg)
--space-8: 32px   (xl)
--space-12: 48px  (xxl)
--space-16: 64px  (xxxl)
```

### Applications

**Component Padding:**

- Button: 12px 16px
- Card: 24px
- Input: 12px 16px
- Badge: 4px 8px

**Component Gaps:**

- Flex: 16px (medium gap)
- Section: 32px (vertical gap)
- Grid: 24px (column gap)

**Margin:**

- Section: 32px bottom
- Item: 16px bottom
- Heading: 24px bottom

---

## Component Library

### Ready-Made Components

#### 1. Buttons

**Variants:**

- Primary (blue, most common)
- Secondary (gray outline)
- Danger (red)
- Ghost (transparent)
- Link (text only)

**States:**

- Normal
- Hover
- Active
- Disabled
- Loading

**Sizes:**

- Small (12px 12px)
- Medium (12px 16px) - default
- Large (16px 20px)

---

#### 2. Cards

**Variants:**

- Default (white card with shadow)
- Interactive (hover lift effect)
- Stat (metric card with large number)
- Elevated (higher shadow)

**Elements:**

- Header (padding-bottom)
- Body (content area)
- Footer (action buttons)

---

#### 3. Forms

**Components:**

- Text Input (fixed 44px height for mobile)
- Textarea (flexible height)
- Select Dropdown
- Checkbox (custom styled)
- Radio Button (custom styled)
- Toggle Switch
- File Upload

**Features:**

- Inline labels
- Helper text below
- Error messages (red, clear)
- Focus state (outline, not removed)
- Disabled state (gray, not clickable)

---

#### 4. Data Tables

**Features:**

- Sticky headers
- Sortable columns
- Row hover state
- Pagination (25 items/page default)
- Row actions menu (3-dot)
- Status indicators
- Avatar columns

---

#### 5. Badges

**Colors:** Primary, Success, Warning, Danger, Info

**Sizes:** Small, Medium

**Usage:**

- Status tags: "Active", "Inactive"
- Type tags: "Lead", "Customer"
- Category tags: "Hot", "Warm", "Cold"

---

#### 6. Avatars

**Types:**

- User avatar (image or initials)
- Group avatar (stacked multiple)
- Icon avatar (for system users)

**Sizes:** 32px, 40px, 48px, 56px, 80px

---

### Component Spacing Standards

```
Button padding:       12px 16px
Input padding:        12px 16px
Card padding:         24px
Badge padding:        4px 8px
Avatar size:          40px (default)
Form gap:             16px
List item padding:    16px
Table cell padding:   12px 16px
```

---

## Layout System

### Main Layout: Sidebar + Main Content

```
┌─────────────────────────────────────┐
│ Sidebar (280px) | Main Content      │
│ - Logo (48x48)  | - Top Nav         │
│ - Nav Items     | - Page Title      │
│ - Settings      | - Content Area    │
│ - Logout        | - Footer          │
└─────────────────────────────────────┘
```

### Sidebar (Desktop: 280px)

- Logo area (60px height)
- Navigation items (48px height each, 12px gap)
- Settings section (bottom)
- Collapse to icons on medium screens

### Top Navigation Bar

- Breadcrumbs (left)
- Search (center, optional)
- User menu (right)
- Height: 64px

### Main Content Area

- Max-width: none (full available)
- Padding: 24px (desktop), 16px (mobile)
- Grid-based layout for cards
- Responsive grid: 4-col (desktop), 2-col (tablet), 1-col (mobile)

### Breakpoints

```
Mobile:   < 640px
Tablet:   640px - 1023px
Desktop:  >= 1024px
```

---

## Interactive States

### Hover State

- Often `translateY(-2px)` + shadow increase
- Color slightly darker

### Focus State

- Blue outline (2px)
- Outline offset (2px)
- Never remove outline completely

### Active/Selected State

- Darker background color
- Possibly different text color
- Icon/indicator change

### Disabled State

- Opacity 50%
- Cursor `not-allowed`
- No hover effects

### Loading State

- Spinner or skeleton
- Button shows loading text or icon

### Error State

- Border color changes to red
- Error message appears below
- Icon or indicator shows error

---

## Responsive Design

### Mobile-First Approach

**1. Design for mobile first**

```css
/* Mobile (default) */
.container {
  width: 100%;
}

/* Tablet */
@media (min-width: 640px) {
  .container {
    width: 90%;
  }
}

/* Desktop */
@media (min-width: 1024px) {
  .container {
    width: 1200px;
  }
}
```

**2. Sidebar Behavior**

```
Mobile:  Hidden (overlay or drawer)
Tablet:  Collapsed to icons (200px)
Desktop: Full sidebar (280px)
```

**3. Grid Layout**

```
Mobile:  1-column
Tablet:  2-column
Desktop: 4-column (stats), 3-column (other)
```

**4. Navigation**

```
Mobile:  Hamburger menu → drawer/bottom sheet
Tablet:  Sidebar icons + labels
Desktop: Full sidebar
```

---

## Accessibility

### WCAG AA Compliance

**1. Color Contrast**

- Normal text: 4.5:1 ratio (AAA)
- Large text: 3:1 ratio (AA)
- Non-text: 3:1 ratio

**2. Focus Management**

- Focus outline visible (2px, blue)
- Never hidden with `outline: none`
- Logical tab order

**3. Semantic HTML**

- Use `<button>` for buttons
- Use `<a>` for links
- Use proper heading hierarchy (h1, h2, etc.)
- Use `<label>` for form inputs

**4. ARIA**

```html
<!-- For icon buttons -->
<button aria-label="Close menu">×</button>

<!-- For expandable sections -->
<div aria-expanded="true">Content</div>

<!-- For status indicators -->
<span aria-label="Active">●</span>
```

**5. Keyboard Support**

- Tab through form inputs
- Enter/Space for buttons
- Arrow keys for select dropdowns
- Escape to close modals

**6. Screen Reader Testing**

- All images have alt text
- Form labels linked to inputs
- Skip navigation link available
- Language attribute set on html

---

## Implementation Guide

### CSS Structure

```
css/
├── tokens/
│   └── design-tokens.css (variables)
├── components/
│   ├── buttons.css
│   ├── cards.css
│   ├── forms.css
│   ├── tables.css
│   ├── badges.css
│   └── ...
├── layout/
│   ├── sidebar.css
│   ├── main-content.css
│   ├── dashboard-grid.css
│   └── responsive.css
├── utilities/
│   └── utilities.css (spacing, display, etc.)
└── crm-design-system.css (all imports)
```

### Using Components

**In Twig:**

```twig
{# Button #}
<button class="btn btn-primary">Click me</button>

{# Card #}
<div class="card">
  <h3 class="card-title">Title</h3>
  <p>Content</p>
</div>

{# Badge #}
<span class="badge badge-success">Active</span>
```

**In CSS:**

```css
.my-component {
  padding: var(--space-4);
  border-radius: var(--radius-lg);
  background: var(--color-white);
  box-shadow: var(--shadow-md);
  font-size: var(--font-size-16);
  color: var(--color-gray-800);
}
```

---

## Component Showcase

[Link to component playground - to be created]

### Quick Reference

| Component  | Class         | Variants                                                      |
| ---------- | ------------- | ------------------------------------------------------------- |
| Button     | `.btn`        | `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-ghost` |
| Card       | `.card`       | `.card-interactive`, `.card-stat`                             |
| Badge      | `.badge`      | `.badge-success`, `.badge-warning`, `.badge-danger`           |
| Form Field | `.form-field` | -                                                             |
| Table      | `.table`      | `.table-striped`, `.table-hover`                              |
| Avatar     | `.avatar`     | `.avatar-sm`, `.avatar-lg`                                    |
| Input      | `.input`      | -                                                             |
| Select     | `.select`     | -                                                             |

---

## Best Practices

### DO ✅

- Use design tokens for all values
- Follow the spacing scale
- Maintain color consistency
- Test on real devices
- Include focus states
- Write semantic HTML
- Test with screen readers

### DON'T ❌

- Hardcode colors (use tokens)
- Use random spacing values
- Skip focus states
- Use `<div>` for buttons
- Remove outline completely
- Ignore keyboard navigation
- Design desktop-only

---

## Design System Maintenance

### When to Update

- New component needed
- Color change requested
- Typography adjustment
- Spacing refinement

### Who Updates

- Product Designer
- Frontend Lead
- Design System Champion

### Change Process

1. Document change in changelog
2. Update component
3. Test across browsers/devices
4. Update documentation
5. Notify team

---

## Resources

- [Tailwind CSS](https://tailwindcss.com/) - Inspiration for token system
- [Material Design](https://material.io/) - Component patterns
- [Stripe Design](https://stripe.com/) - Modern SaaS aesthetic
- [WCAG Guidelines](https://www.w3.org/WAI/WCAG21/quickref/) - Accessibility

---

**Version History:**

- v1.0 (2026-03-10) - Initial design system

**Next Steps:**

1. Create component CSS files
2. Create layout templates
3. Apply to CRM pages
4. Create component playground

---

**Maintained By:** Design & Frontend Team  
**Last Updated:** March 10, 2026  
**Status:** 🟢 Active
