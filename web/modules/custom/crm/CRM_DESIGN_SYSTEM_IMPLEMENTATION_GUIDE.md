# CRM Design System - Implementation Guide

## Overview

This guide provides step-by-step instructions for implementing the CRM design system across the application. The design system includes reusable CSS components, design tokens, responsive layouts, and accessibility features.

## 📁 File Structure

```
web/modules/custom/crm/css/
├── design-tokens.css              # Design token variables (colors, spacing, typography)
├── crm-design-system.css          # Master CSS file (imports all components)
├── components/
│   ├── buttons.css                # Button component (6 variants, 3 sizes)
│   ├── cards.css                  # Card component (7 variants)
│   ├── badges.css                 # Badge component (6 colors, 4 variants)
│   ├── forms.css                  # Form component (10+ input types)
│   ├── tables.css                 # Table component (sortable, pagination)
│   └── avatars.css                # Avatar component (user images)
└── layout/
    ├── sidebar.css                # Sidebar navigation (collapsible)
    └── dashboard.css              # Dashboard grid layout (responsive)
```

## 🎨 Design Tokens

All design tokens are defined in `design-tokens.css` using CSS custom properties (variables). These are used throughout all components for consistency.

### Colors

```css
/* Primary Color */
var(--color-primary)              /* #4facfe */
var(--color-primary-600)          /* #0284c7 */

/* Semantic Colors */
var(--color-success)              /* #10b981 */
var(--color-warning)              /* #f59e0b */
var(--color-danger)               /* #ef4444 */
var(--color-info)                 /* #3b82f6 */

/* Text Colors */
var(--color-text-primary)         /* #1f2937 (dark mode: #f3f4f6) */
var(--color-text-secondary)       /* #6b7280 */
var(--color-text-tertiary)        /* #9ca3af */

/* Backgrounds */
var(--color-bg-primary)           /* #ffffff (dark mode: #1f2937) */
var(--color-bg-secondary)         /* #f9fafb (dark mode: #111827) */
var(--color-bg-tertiary)          /* #f3f4f6 (dark mode: #1e2937) */
```

### Spacing Scale

Based on 4px base unit:

```css
var(--space-1)   /* 4px  */
var(--space-2)   /* 8px  */
var(--space-3)   /* 12px */
var(--space-4)   /* 16px */
var(--space-6)   /* 24px */
var(--space-8)   /* 32px */
var(--space-10)  /* 40px */
var(--space-12)  /* 48px */
var(--space-16)  /* 64px */
var(--space-20)  /* 80px */
```

### Typography

Font sizes: `--font-size-12` through `--font-size-40`

```css
/* Body Text */
var(--font-size-16)

/* Headings */
var(--font-size-32)  /* h1 */
var(--font-size-28)  /* h2 */
var(--font-size-24)  /* h3 */
var(--font-size-20)  /* h4 */
var(--font-size-18)  /* h5 */
```

## 🔧 Component Usage Examples

### Buttons

```html
<!-- Primary Button -->
<button class="btn btn-primary">Save Contact</button>

<!-- Secondary Button -->
<button class="btn btn-secondary">Cancel</button>

<!-- Danger Button -->
<button class="btn btn-danger">Delete</button>

<!-- Small Button -->
<button class="btn btn-primary btn-sm">Add</button>

<!-- Large Button -->
<button class="btn btn-primary btn-lg">Create Deal</button>

<!-- Button Group -->
<div class="btn-group">
  <button class="btn btn-primary">Export</button>
  <button class="btn btn-secondary">Print</button>
</div>

<!-- With Icon -->
<button class="btn btn-primary">
  <svg class="icon-24"><!-- icon SVG --></svg>
  Add Contact
</button>

<!-- Loading State -->
<button class="btn btn-primary is-loading">Saving...</button>

<!-- Disabled -->
<button class="btn btn-primary" disabled>Completed</button>
```

### Cards

```html
<!-- Basic Card -->
<div class="card">
  <div class="card-body">
    <p>Contact Information</p>
  </div>
</div>

<!-- Card with Header -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Recent Deals</h3>
    <a href="#" class="text-primary">View All</a>
  </div>
  <div class="card-body">
    <!-- Content -->
  </div>
</div>

<!-- Interactive Card (lifts on hover) -->
<div class="card card-interactive">
  <div class="card-body">
    <p>Click to expand</p>
  </div>
</div>

<!-- Stat Card -->
<div class="card card-stat">
  <div class="card-body">
    <div class="stat-number">1,250</div>
    <div class="stat-label">Total Contacts</div>
    <div class="stat-change text-success">+12% from last month</div>
  </div>
</div>

<!-- Colored Card -->
<div class="card card-danger">
  <div class="card-body">
    <p>3 overdue tasks</p>
  </div>
</div>
```

### Badges

```html
<!-- Primary Badge -->
<span class="badge badge-primary">High Priority</span>

<!-- Success Badge -->
<span class="badge badge-success">Completed</span>

<!-- Warning Badge -->
<span class="badge badge-warning">In Progress</span>

<!-- Danger Badge -->
<span class="badge badge-danger">Blocked</span>

<!-- Outline Badge -->
<span class="badge badge-primary badge-outline">Draft</span>

<!-- Solid Badge (filled) -->
<span class="badge badge-success badge-solid">Active</span>

<!-- Dot Indicator -->
<span class="badge badge-success badge-dot">Online</span>

<!-- Small Badge -->
<span class="badge badge-sm badge-primary">New</span>

<!-- Large Badge -->
<span class="badge badge-lg badge-success">Verified</span>

<!-- Status Badge -->
<span class="badge badge-status-active">Active</span>
<span class="badge badge-status-inactive">Inactive</span>
```

### Forms

```html
<!-- Form Group -->
<div class="form-group">
  <label for="name">Full Name</label>
  <input type="text" id="name" name="name" placeholder="John Doe" />
  <small class="form-help">Enter the contact's full name</small>
</div>

<!-- Form with Validation Error -->
<div class="form-group has-error">
  <label for="email">Email Address</label>
  <input type="email" id="email" name="email" value="invalid-email" />
  <small class="form-error">Please enter a valid email address</small>
</div>

<!-- Form with Success -->
<div class="form-group has-success">
  <label for="email">Email Address</label>
  <input type="email" id="email" name="email" value="user@example.com" />
</div>

<!-- Textarea -->
<div class="form-group">
  <label for="notes">Notes</label>
  <textarea id="notes" name="notes" placeholder="Add notes..."></textarea>
</div>

<!-- Select Dropdown -->
<div class="form-group">
  <label for="status">Status</label>
  <select id="status" name="status">
    <option>Choose status</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
  </select>
</div>

<!-- Checkbox -->
<div class="form-group">
  <label class="checkbox">
    <input type="checkbox" name="subscribe" />
    Subscribe to email updates
  </label>
</div>

<!-- Radio -->
<div class="form-group">
  <label class="radio">
    <input type="radio" name="type" value="individual" />
    Individual
  </label>
  <label class="radio">
    <input type="radio" name="type" value="company" />
    Company
  </label>
</div>

<!-- Form Layout Grid -->
<form class="form-grid form-grid-2">
  <div class="form-group">
    <label for="first">First Name</label>
    <input type="text" id="first" name="first" />
  </div>
  <div class="form-group">
    <label for="last">Last Name</label>
    <input type="text" id="last" name="last" />
  </div>
</form>

<!-- Form with Submit Button -->
<form>
  <div class="form-group">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" />
  </div>
  <button type="submit" class="btn btn-primary">Save</button>
  <button type="button" class="btn btn-secondary">Cancel</button>
</form>
```

### Tables

```html
<!-- Basic Table -->
<table class="table">
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Status</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>John Doe</td>
      <td>john@example.com</td>
      <td><span class="badge badge-success badge-dot">Active</span></td>
      <td>
        <button class="btn btn-ghost btn-sm">Edit</button>
      </td>
    </tr>
  </tbody>
</table>

<!-- Table with Striped Rows -->
<table class="table table-striped">
  <!-- ... rows ... -->
</table>

<!-- Table with Hover Effect -->
<table class="table table-hover">
  <!-- ... rows ... -->
</table>

<!-- Table with Row Selection -->
<table class="table">
  <thead>
    <tr>
      <th><input type="checkbox" /></th>
      <th>Name</th>
      <!-- ... other columns ... -->
    </tr>
  </thead>
  <tbody>
    <tr class="is-selected">
      <td><input type="checkbox" checked /></td>
      <td>John Doe</td>
    </tr>
  </tbody>
</table>

<!-- Table with Sortable Headers -->
<table class="table">
  <thead>
    <tr>
      <th class="sortable is-sorted-asc">
        Name
        <span class="sort-indicator">↑</span>
      </th>
      <th class="sortable">Email</th>
    </tr>
  </thead>
</table>

<!-- Table with Actions Menu -->
<table class="table">
  <tbody>
    <tr>
      <td>John Doe</td>
      <td>john@example.com</td>
      <td class="action-column">
        <button class="btn btn-ghost btn-icon">⋮</button>
        <div class="action-menu" hidden>
          <a href="#">Edit</a>
          <a href="#">View</a>
          <a href="#" class="action-danger">Delete</a>
        </div>
      </td>
    </tr>
  </tbody>
</table>

<!-- Responsive Mobile Table -->
<table class="table table-responsive">
  <tbody>
    <tr>
      <td data-label="Name">John Doe</td>
      <td data-label="Email">john@example.com</td>
      <td data-label="Status">Active</td>
    </tr>
  </tbody>
</table>

<!-- Table Pagination -->
<div class="table-pagination">
  <span>Showing 1 to 10 of 45 results</span>
  <div class="pagination-controls">
    <button class="btn btn-secondary btn-sm" disabled>Previous</button>
    <button class="btn btn-secondary btn-sm">Next</button>
  </div>
</div>
```

### Avatars

```html
<!-- Avatar with Image -->
<div class="avatar avatar-md">
  <img src="/path/to/user-photo.jpg" alt="John Doe" />
</div>

<!-- Avatar with Initials -->
<div class="avatar avatar-md">JD</div>

<!-- Avatar Sizes -->
<div class="avatar avatar-sm">JD</div>
<!-- 32px -->
<div class="avatar avatar-md">JD</div>
<!-- 44px -->
<div class="avatar avatar-lg">JD</div>
<!-- 56px -->
<div class="avatar avatar-xl">JD</div>
<!-- 72px -->

<!-- Avatar Colors -->
<div class="avatar avatar-md avatar-primary">JD</div>
<div class="avatar avatar-md avatar-success">JD</div>
<div class="avatar avatar-md avatar-warning">JD</div>
<div class="avatar avatar-md avatar-danger">JD</div>
<div class="avatar avatar-md avatar-info">JD</div>

<!-- Avatar with Status -->
<div class="avatar-with-status">
  <div class="avatar avatar-md">
    <img src="/path/to/photo.jpg" alt="John" />
  </div>
  <div class="avatar-status"></div>
</div>

<!-- Avatar with Status Indicator -->
<div class="avatar-with-status">
  <div class="avatar avatar-md avatar-primary">JD</div>
  <div class="avatar-status offline"></div>
</div>

<!-- Avatar Group -->
<div class="avatar-group">
  <div class="avatar avatar-md">
    <img src="/path/to/user1.jpg" alt="User 1" />
  </div>
  <div class="avatar avatar-md">
    <img src="/path/to/user2.jpg" alt="User 2" />
  </div>
  <div class="avatar avatar-md">
    <img src="/path/to/user3.jpg" alt="User 3" />
  </div>
  <div class="avatar avatar-md">+2</div>
</div>
```

### Sidebar Navigation

```html
<!-- Full Sidebar Layout -->
<div class="sidebar-layout">
  <aside class="sidebar">
    <!-- Logo -->
    <div class="sidebar-logo">
      <svg class="logo-icon"><!-- Logo SVG --></svg>
      <span class="logo-text">CRM</span>
    </div>

    <!-- Navigation Sections -->
    <nav class="sidebar-nav">
      <!-- Section 1 -->
      <div class="nav-section">
        <h3 class="nav-section-title">MAIN</h3>
        <a href="/dashboard" class="nav-item is-active">
          <svg class="nav-icon"><!-- Icon SVG --></svg>
          <span>Dashboard</span>
        </a>
        <a href="/contacts" class="nav-item">
          <svg class="nav-icon"><!-- Icon SVG --></svg>
          <span>Contacts</span>
          <span class="nav-badge">12</span>
        </a>
        <a href="/deals" class="nav-item">
          <svg class="nav-icon"><!-- Icon SVG --></svg>
          <span>Deals</span>
        </a>
      </div>

      <!-- Section 2 -->
      <div class="nav-section">
        <h3 class="nav-section-title">SECONDARY</h3>
        <a href="/activities" class="nav-item">
          <svg class="nav-icon"><!-- Icon SVG --></svg>
          <span>Activities</span>
        </a>
        <a href="/tasks" class="nav-item">
          <svg class="nav-icon"><!-- Icon SVG --></svg>
          <span>Tasks</span>
          <span class="nav-badge alert">5</span>
        </a>
      </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="avatar avatar-md">
          <img src="/path/to/user.jpg" alt="User" />
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name">John Doe</div>
          <div class="sidebar-user-role">Admin</div>
        </div>
      </div>
    </div>

    <!-- Collapse Toggle -->
    <button class="sidebar-toggle">
      <svg><!-- Toggle Icon --></svg>
    </button>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Dashboard content here -->
  </div>
</div>
```

### Dashboard Grid

```html
<!-- Dashboard Layout with Header -->
<div class="page-header">
  <div>
    <h1 class="page-title">Contacts</h1>
    <p class="page-subtitle">Manage all your contacts</p>
  </div>
  <div class="page-actions">
    <button class="btn btn-primary">Add Contact</button>
  </div>
</div>

<!-- Dashboard Grid -->
<div class="dashboard-grid">
  <!-- Stat Card 1 -->
  <div class="card card-stat">
    <div class="card-body">
      <div class="stat-number">1,250</div>
      <div class="stat-label">Total Contacts</div>
      <div class="stat-change text-success">+12%</div>
    </div>
  </div>

  <!-- Stat Card 2 -->
  <div class="card card-stat">
    <div class="card-body">
      <div class="stat-number">$245K</div>
      <div class="stat-label">Total Value</div>
      <div class="stat-change text-success">+8%</div>
    </div>
  </div>

  <!-- Stat Card 3 -->
  <div class="card card-stat">
    <div class="card-body">
      <div class="stat-number">92%</div>
      <div class="stat-label">Win Rate</div>
      <div class="stat-change text-success">+3%</div>
    </div>
  </div>

  <!-- Stat Card 4 -->
  <div class="card card-stat">
    <div class="card-body">
      <div class="stat-number">18</div>
      <div class="stat-label">Avg Deal Size</div>
      <div class="stat-change text-warning">-2%</div>
    </div>
  </div>
</div>

<!-- 2-Column Grid -->
<div class="dashboard-grid dashboard-grid-2">
  <!-- Left column content -->
  <!-- Right column content -->
</div>

<!-- 3-Column Grid -->
<div class="dashboard-grid dashboard-grid-3">
  <!-- Content -->
</div>

<!-- Auto Grid (flexible columns) -->
<div class="dashboard-grid dashboard-grid-auto">
  <!-- Content adapts to screen width -->
</div>

<!-- Page Section -->
<section class="page-section">
  <div class="page-section-header">
    <h2 class="section-title">Recent Deals</h2>
    <a href="#" class="text-primary">View All</a>
  </div>
  <div class="section-content">
    <!-- Table or other content -->
  </div>
</section>
```

## 📦 Library Registration

To use the design system, add the following to `crm.libraries.yml`:

```yaml
crm-design-system:
  css:
    base:
      css/crm-design-system.css: {}
```

Then attach the library to your templates:

```twig
{{ attach_library('crm/crm-design-system') }}
```

## 🎯 Implementation Checklist

### Phase 1: Setup (Current)

- [x] Create design tokens file
- [x] Create component CSS files
- [x] Create layout CSS files
- [x] Create master CSS file
- [x] Document all components

### Phase 2: Page Integration (Next)

- [ ] Update Contacts list page
- [ ] Update Deals list page
- [ ] Update Activities page
- [ ] Update Tasks page
- [ ] Update Dashboard
- [ ] Update User profile
- [ ] Apply sidebar navigation to all pages

### Phase 3: Enhancements

- [ ] Create Twig component templates
- [ ] Add JavaScript interactions (sidebar toggle, etc.)
- [ ] Create component showcase/style guide
- [ ] Add loading skeletons
- [ ] Add modal/dialog component
- [ ] Add toast notifications

### Phase 4: Polish

- [ ] Test responsive design (mobile, tablet, desktop)
- [ ] Test dark mode
- [ ] Accessibility audit (WCAG AA)
- [ ] Performance optimization
- [ ] Browser compatibility testing

## 🚀 Quick Start

1. **Add the library to your page:**

   ```twig
   {{ attach_library('crm/crm-design-system') }}
   ```

2. **Use components in your templates:**

   ```twig
   <button class="btn btn-primary">Save</button>
   <div class="card">
     <div class="card-body">Content</div>
   </div>
   ```

3. **Use spacing utilities:**

   ```twig
   <div class="mt-4 mb-6 p-4">Content with spacing</div>
   ```

4. **Use color utilities:**
   ```twig
   <p class="text-primary">Primary text</p>
   <div class="bg-secondary">Secondary background</div>
   ```

## 📱 Responsive Breakpoints

- **Mobile**: < 640px (default, mobile-first)
- **Tablet**: 640px - 1024px
- **Desktop**: > 1024px

All components use mobile-first responsive design. Styles defined at the base level apply to mobile, then override at breakpoints for larger screens.

## ♿️ Accessibility Features

- WCAG AA compliant color contrast ratios
- Focus states on all interactive elements (blue outline, 2px offset)
- Semantic HTML (proper heading hierarchy, form labels linked to inputs)
- Dark mode support (prefers-color-scheme: dark)
- 44px minimum button/input height (mobile touch targets)
- Proper font sizes for readability (16px minimum for body text)
- Clear error messages with redundant color + text
- Keyboard navigation support

## 🌙 Dark Mode

Dark mode is automatically supported through `prefers-color-scheme: dark` CSS media query. Colors automatically invert when user has dark mode enabled in OS settings.

## 📝 Notes

- All components use CSS custom properties for easy customization
- No external dependencies (pure CSS)
- Mobile-first responsive design
- Production-ready with professional aesthetics
- Extensible for future enhancements

---

**Last Updated**: 2025-03-09  
**Version**: 1.0.0  
**Status**: Complete - Ready for Page Integration
