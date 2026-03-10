# CRM Empty State Component Guide

## Overview

The CRM Empty State component is a reusable, professional UI element that appears when a page has no data. It provides users with:

- **Visual context** - A friendly icon that conveys what type of data is missing
- **Clear guidance** - Explanatory text that helps users understand the empty state
- **Call-to-action** - Primary and secondary buttons directing users to next actions
- **Helpful tips** - Optional contextual tips for new users

## Quick Start

### 1. Add Library Dependency

Add the `empty_states` library to your template or module that uses empty states:

```twig
{{ attach_library('crm/empty_states') }}
```

### 2. Include Component

Use the component in your template with a simple condition:

```twig
{% if not items or items|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'users',
    title: 'No contacts yet',
    description: 'Start building your CRM by adding your first contact.',
    primary_action_url: '/crm/contacts/add',
    primary_action_label: 'Add Contact'
  } only %}
{% else %}
  {# Display your items here #}
{% endif %}
```

## Component Parameters

All parameters except `title` and `primary_action_url` are optional.

| Parameter                | Type   | Required | Description                                                                                                          |
| ------------------------ | ------ | -------- | -------------------------------------------------------------------------------------------------------------------- |
| `icon_type`              | string | No       | Icon to display: `'users'`, `'activity'`, `'briefcase'`, `'inbox'`, `'landmark'`, `'custom'` (default: `'landmark'`) |
| `title`                  | string | **Yes**  | Main heading for the empty state                                                                                     |
| `description`            | string | No       | Explanatory text (1-2 sentences)                                                                                     |
| `primary_action_url`     | string | **Yes**  | URL for primary/main CTA button                                                                                      |
| `primary_action_label`   | string | **Yes**  | Text displayed on primary button                                                                                     |
| `secondary_action_url`   | string | No       | URL for secondary/alternative link                                                                                   |
| `secondary_action_label` | string | No       | Text for secondary link (only shown if URL provided)                                                                 |
| `tip`                    | string | No       | Helpful tip to guide users (appears in yellow box)                                                                   |
| `icon`                   | string | No       | Custom SVG icon markup (only used when `icon_type: 'custom'`)                                                        |

## Icon Types

### Built-in Icon Types

```twig
{# Users/Contacts Icon #}
icon_type: 'users'

{# Activity/Calendar Icon #}
icon_type: 'activity'

{# Briefcase/Deals Icon #}
icon_type: 'briefcase'

{# Inbox/General Icon #}
icon_type: 'inbox'

{# Landmark/Organizations Icon #}
icon_type: 'landmark'
```

### Custom Icon

Use your own SVG icon:

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'custom',
  icon: '<svg><!-- your svg here --></svg>',
  title: 'Custom Empty State',
  primary_action_url: '#',
  primary_action_label: 'Action'
} only %}
```

## Usage Examples

### Contacts Empty State

```twig
{{ attach_library('crm/empty_states') }}

{% if not contacts or contacts|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'users',
    title: 'No contacts yet',
    description: 'You haven\'t added any contacts to your CRM yet. Start building your contact list by adding your first contact or importing from an external source.',
    primary_action_url: '/crm/contacts/add',
    primary_action_label: 'Add Contact',
    secondary_action_url: '/crm/import/contacts',
    secondary_action_label: 'Import Contacts',
    tip: 'Pro tip: You can bulk import up to 1,000 contacts at once from CSV or Excel files.'
  } only %}
{% else %}
  {# Display contacts table #}
  {% include '@crm/contacts-table.html.twig' %}
{% endif %}
```

### Deals Empty State

```twig
{{ attach_library('crm/empty_states') }}

{% if not deals or deals|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'briefcase',
    title: 'No deals yet',
    description: 'Start creating deals to track sales opportunities and manage your sales pipeline.',
    primary_action_url: '/crm/deals/add',
    primary_action_label: 'Create Deal',
    secondary_action_url: '/crm/deals/import',
    secondary_action_label: 'Import Deals',
    tip: 'Deals help you track sales opportunities from initial contact through closing.'
  } only %}
{% else %}
  {# Display deals list #}
{% endif %}
```

### Activities Empty State

```twig
{{ attach_library('crm/empty_states') }}

{% if not activities or activities|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'activity',
    title: 'No activities yet',
    description: 'Begin logging activities to create a complete history of all your interactions.',
    primary_action_url: '/crm/activities/add',
    primary_action_label: 'Log Activity',
    secondary_action_url: '/help/activities',
    secondary_action_label: 'Learn More',
    tip: 'Track calls, emails, meetings, and notes to maintain a complete audit trail.'
  } only %}
{% else %}
  {# Display activities timeline #}
{% endif %}
```

### Tasks Empty State

```twig
{{ attach_library('crm/empty_states') }}

{% if not tasks or tasks|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'activity',
    title: 'No tasks yet',
    description: 'Keep your team focused by creating tasks for follow-ups and action items.',
    primary_action_url: '/crm/tasks/add',
    primary_action_label: 'Create Task',
    secondary_action_url: '/crm/tasks/import',
    secondary_action_label: 'Import Tasks',
    tip: 'Assign tasks to team members and set due dates to stay organized.'
  } only %}
{% else %}
  {# Display tasks list #}
{% endif %}
```

### Organizations Empty State

```twig
{{ attach_library('crm/empty_states') }}

{% if not organizations or organizations|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'landmark',
    title: 'No organizations yet',
    description: 'Build your company database by adding organizations to group contacts and deals.',
    primary_action_url: '/crm/organizations/add',
    primary_action_label: 'Add Organization',
    secondary_action_url: '/crm/import/organizations',
    secondary_action_label: 'Import Organizations',
    tip: 'Each organization can have multiple contacts and deals.'
  } only %}
{% else %}
  {# Display organizations list #}
{% endif %}
```

## Design Features

### Responsive Design

The component automatically adapts to different screen sizes:

- **Desktop** (~1200px+): Full centered card with 40px padding
- **Tablet** (~768px): Reduced padding to 30px
- **Mobile** (~480px): Full-width layout with stacked buttons

### Dark Mode Support

The component includes automatic dark mode styles that respect the user's system preference. Colors and contrasts adjust automatically.

### Animations

- **Fade-in animation**: Content fades up from bottom (0.6s)
- **Icon bounce**: Icon scales and bounces on entry (0.8s)
- **Button hover**: Buttons lift and gain shadow on hover
- **Reduced motion**: Animations disabled if user prefers reduced motion

### Accessibility

- **Semantic HTML**: Uses proper heading levels (h2 for title)
- **Focus indicators**: Clear outline on focused buttons
- **Color not only**: Icon type provides visual meaning beyond color
- **Screen reader friendly**: All text content is visible and properly structured
- **Keyboard navigation**: All elements accessible via Tab key

## Styling & Customization

### CSS Classes

The component outputs these CSS classes for advanced customization:

```
.crm-empty-state              - Main container
.crm-empty-state-container    - Inner wrapper
.crm-empty-icon               - Icon container
.crm-empty-icon svg           - Icon SVG
.crm-empty-content            - Content section
.crm-empty-title              - Title heading
.crm-empty-description        - Description text
.crm-empty-tip                - Tip box
.crm-empty-actions            - Button container
.crm-empty-btn                - Base button class
.crm-empty-btn-primary        - Primary button (blue gradient)
.crm-empty-btn-secondary      - Secondary button (gray outline)
```

### Custom CSS

Override styles with your own CSS:

```css
/* Larger empty state */
.crm-empty-state {
  min-height: 500px;
}

/* Different gradient background */
.crm-empty-state {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Custom icon color */
.crm-empty-icon svg {
  color: #667eea;
}

/* Bigger title font */
.crm-empty-title {
  font-size: 36px;
}
```

## Variants

### In Table/List Context

For empty states inside existing data tables:

```css
.crm-view-empty {
  display: flex;
  min-height: 300px;
}

.crm-view-empty .crm-empty-state {
  margin: 0;
  background: transparent;
}
```

### Card-Style Variant

For pages with card-based layouts:

```twig
<div class="crm-empty-state-card">
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'users',
    title: 'No contacts',
    primary_action_url: '/add',
    primary_action_label: 'Add'
  } only %}
</div>
```

## Best Practices

### 1. **Always Use Clear, Friendly Copy**

```twig
✅ Good:
title: 'No deals yet'
description: 'Create your first deal to start tracking sales opportunities.'

❌ Avoid:
title: 'Empty State'
description: 'No data available.'
```

### 2. **Include Helpful Tips for New Users**

```twig
tip: 'You can import up to 1,000 contacts at once from CSV files.'
```

### 3. **Match Icon Type to Content**

```twig
Contacts      → icon_type: 'users'
Deals         → icon_type: 'briefcase'
Activities    → icon_type: 'activity'
Tasks         → icon_type: 'activity'
Organizations → icon_type: 'landmark'
```

### 4. **Primary Action Should Be Next Logical Step**

```twig
primary_action_label: 'Add Contact'  ✅ Direct action
primary_action_label: 'Learn More'   ❌ Indirect
```

### 5. **Provide Secondary Options**

```twig
secondary_action_label: 'Import Contacts'    ✅ Alternative path
secondary_action_label: 'Go Home'            ❌ Navigation away
```

### 6. **Use Consistent Action Naming**

```twig
Create/Add
Import
Learn More
View Examples
Browse Templates
```

## Common Issues

### Component Not Appearing

**Check:**

1. Is the `empty_states` library attached? `{{ attach_library('crm/empty_states') }}`
2. Is the condition correct? `{% if not items or items|length == 0 %}`
3. Is the component path correct? `components/crm-empty-state.html.twig`

### Styling Looks Wrong

**Check:**

1. Library is attached: `{{ attach_library('crm/empty_states') }}`
2. No conflicting CSS from other modules
3. Theme CSS isn't overriding component styles

### Icon Not Showing

**Check:**

1. `icon_type` is spelled correctly: `'users'`, `'activity'`, `'briefcase'`, `'inbox'`, `'landmark'`
2. For custom icons, SVG is properly formatted
3. CSS file loaded in browser DevTools Network tab

### Buttons Not Working

**Check:**

1. `primary_action_url` and `primary_action_label` are both provided
2. URLs are correct and routable
3. Secondary action only appears if both URL and label provided

## Advanced Usage

### Dynamic Data Conditions

```twig
{# Show empty state based on filter #}
{% set filtered_contacts = contacts|filter(c => c.status == 'active') %}

{% if filtered_contacts|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'users',
    title: 'No active contacts',
    description: 'Adjust your filters or add new contacts.',
    primary_action_url: '/reset-filters',
    primary_action_label: 'Reset Filters'
  } only %}
{% else %}
  {# Display filtered results #}
{% endif %}
```

### Role-Based Empty States

```twig
{% if is_admin %}
  {% set title = 'No data to manage' %}
  {% set action = 'Add Item' %}
{% else %}
  {% set title = 'Waiting for admin to add items' %}
  {% set action = 'Contact Admin' %}
{% endif %}

{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'inbox',
  title: title,
  primary_action_url: action_url,
  primary_action_label: action
} only %}
```

### Multiple Sections with Empty States

```twig
<div class="crm-dashboard">
  {% if contacts|length == 0 %}
    <section class="crm-section-contacts">
      {% include 'components/crm-empty-state.html.twig' with {
        icon_type: 'users',
        title: 'No contacts',
        primary_action_url: '/add-contact',
        primary_action_label: 'Add Contact'
      } only %}
    </section>
  {% else %}
    {# Show contacts #}
  {% endif %}

  {% if deals|length == 0 %}
    <section class="crm-section-deals">
      {% include 'components/crm-empty-state.html.twig' with {
        icon_type: 'briefcase',
        title: 'No deals',
        primary_action_url: '/add-deal',
        primary_action_label: 'Create Deal'
      } only %}
    </section>
  {% else %}
    {# Show deals #}
  {% endif %}
</div>
```

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

The component uses modern CSS (Flexbox, CSS Grid, Gradients) and ES6+ JavaScript but gracefully degrades in older browsers.

## Performance Notes

- Single reusable component (DRY principle)
- No external dependencies beyond Drupal core
- CSS file: ~8 KB minified
- Animations use CSS (GPU-accelerated)
- No JavaScript required for basic functionality

## Files Included

```
templates/components/
  └── crm-empty-state.html.twig          # Main component (470+ lines)

css/
  └── crm-empty-states.css               # Styling (600+ lines)

templates/examples/
  ├── empty-state-contacts.html.twig     # Contacts example
  ├── empty-state-deals.html.twig        # Deals example
  ├── empty-state-activities.html.twig   # Activities example
  ├── empty-state-tasks.html.twig        # Tasks example
  └── empty-state-organizations.html.twig # Organizations example

crm.libraries.yml                         # Library registration
EMPTY_STATE_GUIDE.md                     # This guide
```

## Support & Questions

For issues, feature requests, or questions:

1. Check the examples in `templates/examples/`
2. Review the component documentation in `crm-empty-state.html.twig`
3. Check CSS classes in `css/crm-empty-states.css`

---

**Version:** 1.0  
**Last Updated:** 2026-03-09  
**Status:** Production Ready
