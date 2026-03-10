# CRM Professional Section Headers - Developer Guide

## Overview

This guide explains how to use the **professional Lucide icon headers** in the CRM dashboard for a modern, clean appearance inspired by SaaS dashboards like Linear, Notion, Hubspot, and Stripe.

## Key Features

✅ **Lucide Icons** - Professional SVG icons instead of emojis  
✅ **Consistent Styling** - Clean, minimal design across all sections  
✅ **Multiple Variants** - Default, compact, accent, and with-action layouts  
✅ **Color Options** - 10+ icon color variants  
✅ **Responsive** - Mobile-friendly on all screen sizes  
✅ **Easy Integration** - Simple HTML structure, minimal CSS classes

---

## Quick Start

### Basic Header Structure

```html
<div class="crm-section-header icon-blue">
  <div class="crm-header-row">
    <i data-lucide="rocket"></i>
    <h2>Section Title</h2>
  </div>
  <p class="crm-subtitle">Optional description text</p>
</div>
```

### Required Setup

Make sure your page includes:

```html
<script src="https://unpkg.com/lucide@latest"></script>
<link rel="stylesheet" href="/modules/custom/crm/css/crm-section-headers.css" />

<script>
  lucide.createIcons();
</script>
```

---

## Header Variants

### 1. Default Header (Most Common)

Used for main section headings with title + description.

```html
<div class="crm-section-header icon-blue">
  <div class="crm-header-row">
    <i data-lucide="rocket"></i>
    <h2>Quick Access</h2>
  </div>
  <p class="crm-subtitle">Quickly access the main CRM features</p>
</div>
```

**Output:**

- Rocket icon in blue
- Title "Quick Access" in bold
- Subtitle below

---

### 2. Compact Header (Sidebar Sections)

Smaller version without subtitle, ideal for sidebars and narrow sections.

```html
<div class="crm-section-header compact icon-green">
  <div class="crm-header-row">
    <i data-lucide="users"></i>
    <h2>Recent Contacts</h2>
  </div>
</div>
```

**Properties:**

- Smaller font size (18px)
- No subtitle displayed
- Same icon and spacing

---

### 3. With Action Link

Header with a "View all" or action button on the right side.

```html
<div class="crm-section-header with-action icon-orange">
  <div class="crm-header-content">
    <div class="crm-header-row">
      <i data-lucide="calendar"></i>
      <h2>Recent Activities</h2>
    </div>
    <p class="crm-subtitle">Your latest work schedule and tasks</p>
  </div>
  <a href="/crm/all-activities" class="crm-section-action">
    View all
    <i data-lucide="arrow-right"></i>
  </a>
</div>
```

**Output:**

- Header on left
- "View all" link on right
- Icon slides on hover

---

### 4. Accent Variant (Featured Section)

Highlighted section with background color and left border.

```html
<div class="crm-section-header accent icon-indigo">
  <div class="crm-header-row">
    <i data-lucide="trending-up"></i>
    <h2>Sales Pipeline</h2>
  </div>
  <p class="crm-subtitle">Monitor your deal progression and close rates</p>
</div>
```

**Properties:**

- Light blue background
- Left border accent
- Great for highlighting important sections

---

## Icon Color Variants

Choose the appropriate color for your section:

| Class         | Color            | Best For                     |
| ------------- | ---------------- | ---------------------------- |
| `icon-blue`   | Blue (#2563eb)   | Main features, Dashboard     |
| `icon-green`  | Green (#10b981)  | Success, Contacts, Verified  |
| `icon-purple` | Purple (#8b5cf6) | Pipeline, Admin, Premium     |
| `icon-orange` | Orange (#f59e0b) | Activities, Alerts, Schedule |
| `icon-red`    | Red (#ef4444)    | Issues, Errors, Lost deals   |
| `icon-pink`   | Pink (#ec4899)   | Teams, Organizations         |
| `icon-teal`   | Teal (#14b8a6)   | Data, Companies              |
| `icon-cyan`   | Cyan (#06b6d4)   | Cloud, Upload, Import        |
| `icon-indigo` | Indigo (#4f46e5) | Analytics, Deals             |
| `icon-gray`   | Gray (#6b7280)   | Settings, Admin tools        |

---

## Icon Selection Guide

### Dashboard & Analytics

```html
<i data-lucide="bar-chart-3"></i>
<!-- Dashboard -->
<i data-lucide="trending-up"></i>
<!-- Sales growth -->
<i data-lucide="trending-down"></i>
<!-- Issues -->
<i data-lucide="target"></i>
<!-- Goals KPIs -->
<i data-lucide="git-branch"></i>
<!-- Pipeline -->
```

### People & Contacts

```html
<i data-lucide="users"></i>
<!-- Team, Contacts -->
<i data-lucide="user"></i>
<!-- Single user -->
<i data-lucide="user-plus"></i>
<!-- Add contact -->
<i data-lucide="user-check"></i>
<!-- Verified, Approved -->
```

### Business

```html
<i data-lucide="briefcase"></i>
<!-- Deals, Projects -->
<i data-lucide="building-2"></i>
<!-- Organizations -->
<i data-lucide="crown"></i>
<!-- Admin, VIP -->
<i data-lucide="dollar-sign"></i>
<!-- Revenue, Pricing -->
```

### Time & Schedule

```html
<i data-lucide="calendar"></i>
<!-- Activities, Events -->
<i data-lucide="clock"></i>
<!-- Due dates, Time -->
<i data-lucide="bell"></i>
<!-- Notifications -->
<i data-lucide="activity"></i>
<!-- Recent activity -->
```

### Data Management

```html
<i data-lucide="database"></i>
<!-- Data, Storage -->
<i data-lucide="upload"></i>
<!-- Import process -->
<i data-lucide="download"></i>
<!-- Export data -->
<i data-lucide="trash-2"></i>
<!-- Delete, Archive -->
```

### Navigation

```html
<i data-lucide="arrow-right"></i>
<!-- Next, Continue -->
<i data-lucide="arrow-left"></i>
<!-- Back, Previous -->
<i data-lucide="menu"></i>
<!-- Navigation menu -->
<i data-lucide="search"></i>
<!-- Find, Search -->
```

---

## Real-World Examples

### Example 1: Dashboard Section

```php
$html = <<<HTML
<div class="section-card">
  <div class="crm-section-header icon-blue with-action mb-lg">
    <div class="crm-header-content">
      <div class="crm-header-row">
        <i data-lucide="bar-chart-3"></i>
        <h2>Dashboard</h2>
      </div>
      <p class="crm-subtitle">View analytics and sales statistics</p>
    </div>
    <a href="/crm/dashboard" class="crm-section-action">
      Open
      <i data-lucide="arrow-right"></i>
    </a>
  </div>
  <!-- Content here -->
</div>
HTML;
```

### Example 2: Compact Sidebar

```php
$html = <<<HTML
<div class="crm-section-header compact icon-green">
  <div class="crm-header-row">
    <i data-lucide="users"></i>
    <h2>Recent Contacts</h2>
  </div>
</div>
<!-- Contact list here -->
HTML;
```

### Example 3: Featured Section

```php
$html = <<<HTML
<div class="crm-section-header accent icon-indigo mb-xl">
  <div class="crm-header-row">
    <i data-lucide="trending-up"></i>
    <h2>Quick Access</h2>
  </div>
  <p class="crm-subtitle">Top features for your workflow</p>
</div>
<!-- Feature cards here -->
HTML;
```

---

## Spacing & Size Utilities

### Margin Bottom

```html
<!-- Small spacing (12px) -->
<div class="crm-section-header mb-sm">...</div>

<!-- Default spacing (24px) -->
<div class="crm-section-header">...</div>

<!-- Large spacing (32px) -->
<div class="crm-section-header mb-lg">...</div>

<!-- Extra large spacing (40px) -->
<div class="crm-section-header mb-xl">...</div>
```

### Icon Sizes

```html
<!-- Small icon (24px) -->
<div class="crm-section-header icon-sm">
  <div class="crm-header-row">
    <i data-lucide="rocket"></i>
    <h2>Title</h2>
  </div>
</div>

<!-- Default icon (28px) -->
<div class="crm-section-header">...</div>

<!-- Large icon (32px) -->
<div class="crm-section-header icon-lg">...</div>
```

---

## Integration with Drupal

### In PHP Controllers

```php
namespace Drupal\crm_dashboard\Controller;

class DashboardController extends ControllerBase {
  public function view() {
    $html = <<<HTML
    <div class="crm-section-header icon-blue">
      <div class="crm-header-row">
        <i data-lucide="rocket"></i>
        <h2>Quick Access</h2>
      </div>
      <p class="crm-subtitle">Quickly access the main CRM features</p>
    </div>
    HTML;

    return [
      '#markup' => \Drupal\Core\Render\Markup::create($html),
      '#attached' => [
        'library' => ['crm/crm_section_headers'],
      ],
    ];
  }
}
```

### In Twig Templates

```twig
<div class="crm-section-header icon-green">
  <div class="crm-header-row">
    <i data-lucide="users"></i>
    <h2>{{ title }}</h2>
  </div>
  <p class="crm-subtitle">{{ subtitle }}</p>
</div>
```

---

## CSS Classes Reference

| Class                        | Purpose                       |
| ---------------------------- | ----------------------------- |
| `.crm-section-header`        | Main container                |
| `.crm-header-row`            | Icon + title row              |
| `.crm-subtitle`              | Description text              |
| `.crm-section-action`        | Action link                   |
| `.crm-header-content`        | Wraps title+subtitle          |
| `.compact`                   | Smaller variant               |
| `.accent`                    | Highlighted variant           |
| `.with-action`               | Has action link               |
| `.icon-{color}`              | Icon color (blue, green, etc) |
| `.icon-sm`, `.icon-lg`       | Icon size                     |
| `.mb-sm`, `.mb-lg`, `.mb-xl` | Spacing                       |
| `.center`                    | Center alignment              |

---

## Common Patterns

### Admin Section Header

```html
<div class="crm-section-header icon-purple">
  <div class="crm-header-row">
    <i data-lucide="crown"></i>
    <h2>Admin Dashboard</h2>
  </div>
  <p class="crm-subtitle">Manage all CRM data and system-wide operations</p>
</div>
```

### Recent Items Section

```html
<div class="section-card">
  <div class="crm-section-header with-action icon-orange">
    <div class="crm-header-content">
      <div class="crm-header-row">
        <i data-lucide="activity"></i>
        <h2>Recent Activities</h2>
      </div>
      <p class="crm-subtitle">Latest work schedule and tasks</p>
    </div>
    <a href="/crm/all-activities" class="crm-section-action">
      View all <i data-lucide="arrow-right"></i>
    </a>
  </div>
  <!-- Activity list -->
</div>
```

### Feature Showcase

```html
<div class="crm-section-header accent mb-xl">
  <div class="crm-header-row">
    <i data-lucide="rocket"></i>
    <h2>Quick Access</h2>
  </div>
  <p class="crm-subtitle">Quickly access the main CRM features</p>
</div>
```

---

## Browser Support

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Android)

---

## Migration from Old Headers

### Before (with emojis)

```html
<div class="section-header">🚀 Quick Access</div>
```

### After (with Lucide icons)

```html
<div class="crm-section-header icon-blue">
  <div class="crm-header-row">
    <i data-lucide="rocket"></i>
    <h2>Quick Access</h2>
  </div>
  <p class="crm-subtitle">Quickly access the main CRM features</p>
</div>
```

---

## Best Practices

1. **Always include subtitle** - Provides context for users
2. **Choose appropriate icon color** - Use consistent colors for similar sections
3. **Initialize Lucide icons** - Call `lucide.createIcons()` after DOM changes
4. **Use semantic icons** - Match icon to section content
5. **Maintain spacing** - Use consistent margin-bottom values
6. **Mobile responsive** - Test on mobile devices
7. **Accessibility** - Icons are decorative, titles are semantic

---

## Troubleshooting

### Icons not showing

Make sure you have:

1. ✅ Lucide script loaded: `<script src="https://unpkg.com/lucide@latest"></script>`
2. ✅ Called `lucide.createIcons()`
3. ✅ Icon name is correct: `data-lucide="rocket"` (not emojis)

### Styling not applied

Make sure you have:

1. ✅ CSS loaded: `<link rel="stylesheet" href="/modules/custom/crm/css/crm-section-headers.css">`
2. ✅ Correct classes: `crm-section-header` + color variant
3. ✅ No conflicting CSS from other stylesheets

### Text alignment issues

Check that parent containers don't have conflicting `text-align` properties.

---

## Questions?

Refer to the example templates in:  
`/web/modules/custom/crm/templates/crm-section-header-examples.php`

For Lucide icon options, visit: https://lucide.dev/
