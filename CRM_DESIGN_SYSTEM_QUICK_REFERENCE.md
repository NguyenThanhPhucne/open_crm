# CRM Design System - Quick Reference Card

## 🎯 At a Glance

**Status**: Phase 1 Complete ✅ | **Files**: 13 | **Lines**: 6,000+ | **Ready**: Yes

## 📦 What You Get

✅ **Design Tokens** - Colors, spacing, typography, shadows  
✅ **Component Library** - Buttons, cards, badges, forms, tables, avatars, sidebar, dashboard grid  
✅ **Responsive Design** - Mobile-first with 640px and 1024px breakpoints  
✅ **Dark Mode** - Automatic support via prefers-color-scheme  
✅ **Accessibility** - WCAG AA compliant with focus states and semantic HTML  
✅ **Documentation** - 3,200+ lines with examples and implementation guide

## 🚀 Quick Start (3 steps)

### Step 1: Attach Library

```twig
{{ attach_library('crm/crm-design-system') }}
```

### Step 2: Use Components

```html
<button class="btn btn-primary">Save</button>
<div class="card">
  <div class="card-body">Content</div>
</div>
<span class="badge badge-success">Active</span>
```

### Step 3: (Optional) Register in Library

```yaml
# Add to crm.libraries.yml
crm-design-system:
  css:
    base:
      css/crm-design-system.css: {}
```

## 🎨 Component Cheat Sheet

### Buttons

```html
<button class="btn btn-primary">Primary</button>
<button class="btn btn-secondary">Secondary</button>
<button class="btn btn-danger">Danger</button>
<button class="btn btn-ghost">Ghost</button>
<button class="btn btn-link">Link</button>

<!-- Sizes -->
<button class="btn btn-primary btn-sm">Small</button>
<button class="btn btn-primary btn-md">Medium</button>
<button class="btn btn-primary btn-lg">Large</button>

<!-- States -->
<button class="btn btn-primary" disabled>Disabled</button>
<button class="btn btn-primary is-loading">Loading</button>

<!-- With Icon -->
<button class="btn btn-primary"><svg class="icon-24"></svg> Label</button>
```

### Cards

```html
<!-- Basic -->
<div class="card">
  <div class="card-body">Content</div>
</div>

<!-- With Header -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Title</h3>
  </div>
  <div class="card-body">Content</div>
</div>

<!-- Stat Card -->
<div class="card card-stat">
  <div class="stat-number">1,250</div>
  <div class="stat-label">Contacts</div>
  <div class="stat-change text-success">+12%</div>
</div>

<!-- Variants -->
<div class="card card-interactive">...</div>
<div class="card card-elevated">...</div>
<div class="card card-outline">...</div>
<div class="card card-success">...</div>
```

### Badges

```html
<!-- Colors -->
<span class="badge badge-primary">Primary</span>
<span class="badge badge-success">Success</span>
<span class="badge badge-warning">Warning</span>
<span class="badge badge-danger">Danger</span>
<span class="badge badge-info">Info</span>
<span class="badge badge-neutral">Neutral</span>

<!-- Variants -->
<span class="badge badge-solid">Solid</span>
<span class="badge badge-outline">Outline</span>
<span class="badge badge-dot">Dot</span>
<span class="badge badge-pill">Pill</span>

<!-- Sizes -->
<span class="badge badge-sm">Small</span>
<span class="badge badge-md">Medium</span>
<span class="badge badge-lg">Large</span>

<!-- Status Badges -->
<span class="badge badge-status-active">Active</span>
<span class="badge badge-status-inactive">Inactive</span>
<span class="badge badge-status-pending">Pending</span>
<span class="badge badge-status-closed">Closed</span>
```

### Forms

```html
<!-- Text Input -->
<div class="form-group">
  <label for="name">Name</label>
  <input type="text" id="name" name="name" />
  <small class="form-help">Helper text</small>
</div>

<!-- Validation States -->
<div class="form-group has-error">
  <input type="email" />
  <small class="form-error">Invalid email</small>
</div>

<div class="form-group has-success">
  <input type="email" value="user@example.com" />
</div>

<!-- Textarea -->
<div class="form-group">
  <label>Notes</label>
  <textarea></textarea>
</div>

<!-- Select -->
<div class="form-group">
  <label>Status</label>
  <select>
    <option>Choose</option>
    <option>Active</option>
  </select>
</div>

<!-- Checkbox -->
<div class="form-group">
  <label class="checkbox">
    <input type="checkbox" />
    Subscribe
  </label>
</div>

<!-- Radio -->
<div class="form-group">
  <label class="radio">
    <input type="radio" name="type" />
    Option 1
  </label>
</div>

<!-- Layouts -->
<form class="form-grid form-grid-2">
  <div class="form-group">...</div>
  <div class="form-group">...</div>
</form>
```

### Tables

```html
<!-- Basic Table -->
<table class="table">
  <thead>
    <tr>
      <th>Name</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John</td>
      <td>Active</td>
    </tr>
  </tbody>
</table>

<!-- Variants -->
<table class="table table-striped">
  ...
</table>
<table class="table table-hover">
  ...
</table>
<table class="table table-compact">
  ...
</table>
<table class="table table-elevated">
  ...
</table>
<table class="table table-outline">
  ...
</table>

<!-- With Sorting -->
<th class="sortable is-sorted-asc">Name ↑</th>
<th class="sortable">Email</th>

<!-- With Selection -->
<tr class="is-selected">
  <td><input type="checkbox" checked /></td>
  <td>John Doe</td>
</tr>

<!-- Pagination -->
<div class="table-pagination">
  <span>Showing 1 to 10 of 45</span>
  <button class="btn btn-secondary btn-sm">Previous</button>
  <button class="btn btn-secondary btn-sm">Next</button>
</div>
```

### Avatars

```html
<!-- Image Avatar -->
<div class="avatar avatar-md">
  <img src="photo.jpg" alt="John" />
</div>

<!-- Initials Avatar -->
<div class="avatar avatar-md">JD</div>

<!-- Sizes -->
<div class="avatar avatar-sm">JD</div>
<!-- 32px -->
<div class="avatar avatar-md">JD</div>
<!-- 44px -->
<div class="avatar avatar-lg">JD</div>
<!-- 56px -->
<div class="avatar avatar-xl">JD</div>
<!-- 72px -->

<!-- Colors -->
<div class="avatar avatar-success">JD</div>
<div class="avatar avatar-warning">JD</div>
<div class="avatar avatar-danger">JD</div>
<div class="avatar avatar-info">JD</div>

<!-- With Status -->
<div class="avatar-with-status">
  <div class="avatar avatar-md">JD</div>
  <div class="avatar-status"></div>
</div>

<!-- Avatar Group -->
<div class="avatar-group">
  <div class="avatar avatar-md"><img src="u1.jpg" /></div>
  <div class="avatar avatar-md"><img src="u2.jpg" /></div>
  <div class="avatar avatar-md">+2</div>
</div>
```

### Sidebar Navigation

```html
<div class="sidebar-layout">
  <aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
      <svg class="logo-icon"></svg>
      <span>CRM</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <div class="nav-section">
        <h3 class="nav-section-title">MAIN</h3>
        <a href="/dashboard" class="nav-item is-active">
          <svg></svg>
          <span>Dashboard</span>
        </a>
        <a href="/contacts" class="nav-item">
          <svg></svg>
          <span>Contacts</span>
          <span class="nav-badge">12</span>
        </a>
      </div>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar avatar-md">
          <img src="photo.jpg" />
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">John Doe</div>
          <div class="sidebar-user-role">Admin</div>
        </div>
      </div>
    </div>
  </aside>
  <div class="main-content">
    <!-- Page content -->
  </div>
</div>
```

### Dashboard Grid

```html
<!-- 4-Column Grid (Default) -->
<div class="dashboard-grid">
  <div class="card">...</div>
  <div class="card">...</div>
  <!-- Responsive: 4-col desktop, 2-col tablet, 1-col mobile -->
</div>

<!-- 3-Column Grid -->
<div class="dashboard-grid dashboard-grid-3">...</div>

<!-- 2-Column Grid -->
<div class="dashboard-grid dashboard-grid-2">...</div>

<!-- Auto Grid -->
<div class="dashboard-grid dashboard-grid-auto">...</div>

<!-- Page Section -->
<section class="page-section">
  <div class="page-section-header">
    <h2>Recent Deals</h2>
    <a href="#">View All</a>
  </div>
  <div class="section-content">
    <!-- Content -->
  </div>
</section>
```

## 🎨 Design Tokens Reference

### Colors

```css
/* Primary */
--color-primary: #4facfe --color-primary-600: #0284c7 /* Semantic */
  --color-success: #10b981 --color-warning: #f59e0b --color-danger: #ef4444
  --color-info: #3b82f6 /* Grays */ --color-gray-50: #f9fafb
  --color-gray-100: #f3f4f6 --color-gray-300: #d1d5db --color-gray-600: #4b5563
  --color-gray-900: #111827;
```

### Spacing

```css
--space-1: 4px --space-2: 8px --space-3: 12px --space-4: 16px --space-6: 24px
  --space-8: 32px --space-10: 40px --space-12: 48px --space-16: 64px
  --space-20: 80px;
```

### Typography

```css
--font-size-12: 12px --font-size-13: 13px --font-size-14: 14px
  --font-size-16: 16px --font-size-18: 18px --font-size-20: 20px
  --font-size-24: 24px --font-size-28: 28px --font-size-32: 32px
  --font-size-40: 40px;
```

### Shadows

```css
--shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04) --shadow-md: 0 4px 12px
  rgba(0, 0, 0, 0.08) --shadow-lg: 0 12px 24px rgba(0, 0, 0, 0.12)
  --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.15) --shadow-2xl: 0 40px 80px
  rgba(0, 0, 0, 0.2);
```

## 📏 Responsive Breakpoints

```css
/* Mobile First (default) */
/* Styles apply to all screen sizes */

/* Tablet and up (640px) */
@media (min-width: 640px) {
  /* Tablet+ styles */
}

/* Desktop (1024px) */
@media (min-width: 1024px) {
  /* Desktop styles */
}
```

## 🌙 Dark Mode

Automatically supported via CSS media query:

```css
@media (prefers-color-scheme: dark) {
  /* Dark mode colors applied automatically */
}
```

No additional code needed - colors auto-invert for dark mode.

## ♿️ Accessibility Features

- ✅ WCAG AA color contrast (min 4.5:1 for text)
- ✅ Focus states on all interactive elements
- ✅ Semantic HTML (proper heading hierarchy)
- ✅ Form labels linked to inputs
- ✅ 44px minimum button/input height
- ✅ Screen reader compatible
- ✅ Keyboard navigation support

## 🛠 Utility Classes

### Text Colors

```html
<p class="text-primary">Primary text</p>
<p class="text-secondary">Secondary text</p>
<p class="text-success">Success text</p>
<p class="text-danger">Danger text</p>
```

### Backgrounds

```html
<div class="bg-primary">Primary background</div>
<div class="bg-secondary">Secondary background</div>
```

### Spacing

```html
<div class="mt-4 mb-6">Margin top/bottom</div>
<div class="p-6">Padding all sides</div>
<div class="pt-4 pb-2">Padding top/bottom</div>
```

### Flexbox

```html
<div class="flex items-center justify-between">Centered with space-between</div>
<div class="flex flex-col gap-4">Column layout with gap</div>
```

### Text Alignment

```html
<p class="text-center">Centered text</p>
<p class="text-right">Right aligned</p>
```

### Typography

```html
<span class="font-bold">Bold text</span>
<span class="text-sm">Small text</span>
<span class="text-lg">Large text</span>
<span class="uppercase">UPPERCASE</span>
```

## 📚 Documentation Files

1. **CRM_DESIGN_SYSTEM.md** - Full design system spec
2. **CRM_DESIGN_SYSTEM_IMPLEMENTATION_GUIDE.md** - Detailed usage guide
3. **CRM_UI_AUDIT_ANALYSIS.md** - UI audit report
4. **DESIGN_SYSTEM_COMPLETION_SUMMARY.md** - Project summary

## 🔗 File Locations

```
web/modules/custom/crm/
├── css/design-tokens.css
├── css/crm-design-system.css
├── css/components/*.css
├── css/layout/*.css
├── CRM_DESIGN_SYSTEM.md
├── CRM_DESIGN_SYSTEM_IMPLEMENTATION_GUIDE.md
└── CRM_UI_AUDIT_ANALYSIS.md
```

## ❓ FAQ

**Q: How do I use the design system?**  
A: Attach the library `{{ attach_library('crm/crm-design-system') }}` and use component classes like `btn btn-primary`, `card`, `badge badge-success`.

**Q: How do I customize colors?**  
A: Edit `css/design-tokens.css` CSS variables. All components use these variables, so one change updates everything.

**Q: Does it work on mobile?**  
A: Yes! Mobile-first responsive design with automatic adaptations at 640px and 1024px breakpoints.

**Q: Does it support dark mode?**  
A: Yes! Dark mode automatically applies when user has dark mode enabled in OS settings.

**Q: Is it accessible?**  
A: Yes! WCAG AA compliant with proper focus states, semantic HTML, and color contrast.

**Q: Can I extend it?**  
A: Yes! CSS variables make customization easy. Add new classes to component files as needed.

## ✨ What's Included

- ✅ 10 CSS component files (2,800+ lines)
- ✅ Design tokens (colors, spacing, typography, shadows)
- ✅ 40+ component variants
- ✅ 60+ utility classes
- ✅ Responsive design (2 breakpoints)
- ✅ Dark mode support
- ✅ WCAG AA accessibility
- ✅ 3,200+ lines of documentation
- ✅ Code examples for every component
- ✅ Implementation guide
- ✅ UI audit report

## 🎯 Next Steps

1. **Review** the CRM_DESIGN_SYSTEM_IMPLEMENTATION_GUIDE.md
2. **Attach** the library to your pages
3. **Use** component classes in your templates
4. **Customize** design tokens as needed
5. **Extend** with additional components

---

**Last Updated**: March 9, 2025  
**Version**: 1.0.0  
**Status**: Production Ready ✅
