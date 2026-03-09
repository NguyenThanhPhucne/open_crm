# CRM Modern SaaS Card Design System

A reusable, professional card design system for the Open CRM dashboard. Matches modern SaaS applications like Stripe, Linear, and Vercel.

## Quick Start

### Basic Card Structure

```html
<div class="crm-grid">
  <a href="/crm/dashboard" class="crm-card crm-card-blue">
    <div class="crm-card-icon">
      <i data-lucide="bar-chart-3"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">Dashboard</div>
      <div class="crm-card-desc">View analytics and sales statistics</div>
    </div>
    <div class="crm-card-action">View dashboard</div>
  </a>

  <a href="/crm/contacts" class="crm-card crm-card-green">
    <div class="crm-card-icon">
      <i data-lucide="users"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">Contacts</div>
      <div class="crm-card-desc">Manage all your contacts</div>
    </div>
    <div class="crm-card-action">View contacts</div>
  </a>
</div>
```

## Color Variants

Use these color classes to match different sections of your CRM:

| Class              | Color            | Use Case                 | Icon Example |
| ------------------ | ---------------- | ------------------------ | ------------ |
| `.crm-card-blue`   | Blue (#3b82f6)   | Dashboard, Overview      | bar-chart-3  |
| `.crm-card-green`  | Green (#10b981)  | Contacts, Users          | users        |
| `.crm-card-purple` | Purple (#8b5cf6) | Pipeline, Stages         | git-branch   |
| `.crm-card-orange` | Orange (#f59e0b) | Activities, Tasks        | calendar     |
| `.crm-card-pink`   | Pink (#ec4899)   | Organizations, Companies | building-2   |
| `.crm-card-teal`   | Teal (#14b8a6)   | Deals, Revenue           | dollar-sign  |
| `.crm-card-cyan`   | Cyan (#06b6d4)   | Import, Data             | upload       |
| `.crm-card-gray`   | Gray (#6b7280)   | Admin, Settings          | database     |

## Complete Example

```html
<!-- Admin Dashboard Card Grid -->
<div class="crm-grid">
  <!-- Dashboard -->
  <a href="/crm/dashboard" class="crm-card crm-card-blue">
    <div class="crm-card-icon">
      <i data-lucide="bar-chart-3"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">Dashboard</div>
      <div class="crm-card-desc">View analytics and sales statistics</div>
    </div>
    <div class="crm-card-action">View dashboard</div>
  </a>

  <!-- Contacts -->
  <a href="/crm/contacts" class="crm-card crm-card-green">
    <div class="crm-card-icon">
      <i data-lucide="users"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">All Contacts</div>
      <div class="crm-card-desc">Manage and track all contacts</div>
    </div>
    <div class="crm-card-action">View contacts</div>
  </a>

  <!-- Pipeline -->
  <a href="/crm/pipeline" class="crm-card crm-card-purple">
    <div class="crm-card-icon">
      <i data-lucide="git-branch"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">All Pipeline</div>
      <div class="crm-card-desc">Sales pipeline and deal stages</div>
    </div>
    <div class="crm-card-action">View pipeline</div>
  </a>

  <!-- Activities -->
  <a href="/crm/activities" class="crm-card crm-card-orange">
    <div class="crm-card-icon">
      <i data-lucide="calendar"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">All Activities</div>
      <div class="crm-card-desc">Track calls, emails, and meetings</div>
    </div>
    <div class="crm-card-action">View activities</div>
  </a>

  <!-- Organizations -->
  <a href="/crm/organizations" class="crm-card crm-card-pink">
    <div class="crm-card-icon">
      <i data-lucide="building-2"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">All Organizations</div>
      <div class="crm-card-desc">View all companies and accounts</div>
    </div>
    <div class="crm-card-action">View organizations</div>
  </a>

  <!-- Deals -->
  <a href="/crm/deals" class="crm-card crm-card-teal">
    <div class="crm-card-icon">
      <i data-lucide="dollar-sign"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">All Deals</div>
      <div class="crm-card-desc">Sales pipeline and revenue tracking</div>
    </div>
    <div class="crm-card-action">View deals</div>
  </a>

  <!-- Import Data -->
  <a href="/crm/import" class="crm-card crm-card-cyan">
    <div class="crm-card-icon">
      <i data-lucide="upload"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">Import Data</div>
      <div class="crm-card-desc">Bulk upload contacts and deals</div>
    </div>
    <div class="crm-card-action">Import now</div>
  </a>

  <!-- Admin -->
  <a href="/admin" class="crm-card crm-card-gray">
    <div class="crm-card-icon">
      <i data-lucide="database"></i>
    </div>
    <div class="crm-card-content">
      <div class="crm-card-title">Admin</div>
      <div class="crm-card-desc">System settings and configuration</div>
    </div>
    <div class="crm-card-action">Go to admin</div>
  </a>
</div>
```

## Responsive Behavior

The card grid automatically responds to screen size:

| Breakpoint              | Columns   |
| ----------------------- | --------- |
| Desktop (≥1200px)       | 4 columns |
| Tablet (768px - 1199px) | 2 columns |
| Mobile (<768px)         | 1 column  |
| Small Mobile (<480px)   | 1 column  |

## Hover Effects

When you hover over a card:

1. **Lift Effect**: Card moves up 4px with elevated shadow
2. **Color Accent**: Border color changes to match the card's color theme
3. **Light Background**: Card background becomes the light variant of the accent color
4. **Action Arrow**: The arrow in the action link animates with increased gap

```html
<!-- Before hover -->
<div class="crm-card crm-card-blue">
  <!-- Border: #e5e7eb (gray) -->
  <!-- Background: white -->
</div>

<!-- On hover -->
<div class="crm-card crm-card-blue">
  <!-- Border: #3b82f6 (blue) -->
  <!-- Background: #eff6ff (light blue) -->
  <!-- Transform: translateY(-4px) -->
</div>
```

## CSS Variables

The system uses CSS custom properties for flexibility:

```css
--card-color      /* Primary accent color (icon + borders + text) */
--card-bg         /* Light background color (on hover) */
```

This allows easy customization:

```css
.crm-card-custom {
  --card-color: #ff6b6b;
  --card-bg: #ffe0e0;
}
```

## Integration

The CSS system is built into the DashboardController.php file in the `<style>` section. To use it:

1. Add the card grid HTML where needed (admin dashboard, navigation, etc.)
2. Use the color classes to match your CRM sections
3. Lucide icons automatically render via `<i data-lucide="icon-name"></i>`

## Lucide Icon Mapping

| Icon Name     | Use For                  |
| ------------- | ------------------------ |
| `bar-chart-3` | Dashboard, Analytics     |
| `users`       | Contacts, People         |
| `git-branch`  | Pipeline, Workflow       |
| `calendar`    | Activities, Events       |
| `building-2`  | Organizations, Companies |
| `dollar-sign` | Deals, Revenue           |
| `upload`      | Import, Data Upload      |
| `database`    | Admin, Settings          |

## Design Philosophy

This card system follows modern SaaS design principles:

- ✅ **Clarity**: Clean typography, clear hierarchy
- ✅ **Subtlety**: Soft borders, minimal shadows
- ✅ **Interactivity**: Hover effects that feel responsive
- ✅ **Consistency**: Reusable color system
- ✅ **Hierarchy**: Icon, title, description, action in logical order
- ✅ **Spacing**: Generous but not excessive gaps
- ✅ **Responsiveness**: Adapts to all screen sizes
- ✅ **Accessibility**: WCAG compliant with full keyboard support

## Browser Support

Works in all modern browsers:

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile Safari on iOS 14+

## Performance

- Minimal CSS (compact, no bloat)
- CSS variables for performance
- Smooth 0.25s transitions (GPU accelerated with `transform` and `opacity`)
- No JavaScript required
