# CRM Empty State - Quick Reference

## One-Minute Setup

```twig
{{ attach_library('crm/empty_states') }}

{% if not items or items|length == 0 %}
  {% include 'components/crm-empty-state.html.twig' with {
    icon_type: 'users',
    title: 'No items yet',
    primary_action_url: '/add',
    primary_action_label: 'Add Item'
  } only %}
{% endif %}
```

## Parameters Cheat Sheet

| Param                    | Required | Options                                                         |
| ------------------------ | -------- | --------------------------------------------------------------- |
| `icon_type`              | No       | `users`, `activity`, `briefcase`, `inbox`, `landmark`, `custom` |
| `title`                  | **Yes**  | Any string                                                      |
| `primary_action_url`     | **Yes**  | URL path                                                        |
| `primary_action_label`   | **Yes**  | Button text                                                     |
| `description`            | No       | Explanatory text                                                |
| `secondary_action_url`   | No       | URL (shows button if both url + label provided)                 |
| `secondary_action_label` | No       | Secondary button text                                           |
| `tip`                    | No       | Helpful hint (displays in yellow box)                           |
| `icon`                   | No       | Custom SVG (only if icon_type = 'custom')                       |

## Icon Types at a Glance

| Icon | Type          | Use For                 |
| ---- | ------------- | ----------------------- |
| 👥   | `'users'`     | Contacts                |
| 📋   | `'activity'`  | Activities, Tasks       |
| 💼   | `'briefcase'` | Deals                   |
| 📬   | `'inbox'`     | General fallback        |
| 🏢   | `'landmark'`  | Organizations (default) |
| 🎨   | `'custom'`    | Your own SVG            |

## Ready-Made Examples

### Contacts

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'users',
  title: 'No contacts yet',
  description: 'Start building your CRM by adding your first contact.',
  primary_action_url: '/crm/contacts/add',
  primary_action_label: 'Add Contact',
  secondary_action_url: '/crm/import/contacts',
  secondary_action_label: 'Import Contacts',
  tip: 'You can bulk import contacts from CSV.'
} only %}
```

### Deals

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'briefcase',
  title: 'No deals yet',
  primary_action_url: '/crm/deals/add',
  primary_action_label: 'Create Deal',
  secondary_action_url: '/crm/deals/import',
  secondary_action_label: 'Import Deals'
} only %}
```

### Activities

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'activity',
  title: 'No activities yet',
  primary_action_url: '/crm/activities/add',
  primary_action_label: 'Log Activity',
  secondary_action_url: '/help/activities',
  secondary_action_label: 'Learn More'
} only %}
```

### Tasks

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'activity',
  title: 'No tasks yet',
  primary_action_url: '/crm/tasks/add',
  primary_action_label: 'Create Task'
} only %}
```

### Organizations

```twig
{% include 'components/crm-empty-state.html.twig' with {
  icon_type: 'landmark',
  title: 'No organizations yet',
  primary_action_url: '/crm/organizations/add',
  primary_action_label: 'Add Organization'
} only %}
```

## CSS Customization

Override colors/sizes in your theme CSS:

```css
/* Change background gradient */
.crm-empty-state {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Change icon color */
.crm-empty-icon svg {
  color: #667eea;
}

/* Make title bigger */
.crm-empty-title {
  font-size: 36px;
}

/* Change button gradient */
.crm-empty-btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
```

## Checklist

- [ ] Add `{{ attach_library('crm/empty_states') }}` to template
- [ ] Check condition: `{% if not items or items|length == 0 %}`
- [ ] Set required params: `title`, `primary_action_url`, `primary_action_label`
- [ ] Pick matching icon type for content
- [ ] Add helpful description text
- [ ] Provide secondary action option
- [ ] Add tip for new users
- [ ] Test on mobile (buttons should stack)
- [ ] Test in dark mode (colors should adjust)
- [ ] Verify URLs are correct and routable

## Files

| File                                             | Purpose              |
| ------------------------------------------------ | -------------------- |
| `templates/components/crm-empty-state.html.twig` | Main component       |
| `css/crm-empty-states.css`                       | All styling          |
| `crm.libraries.yml`                              | Library registration |
| `templates/examples/empty-state-*.html.twig`     | Usage examples       |
| `EMPTY_STATE_GUIDE.md`                           | Full documentation   |
| `EMPTY_STATE_QUICK_REFERENCE.md`                 | This file            |

## Troubleshooting

**Component not showing?**

- Is the library attached? `{{ attach_library('crm/empty_states') }}`
- Is the condition correct? Check for syntax errors
- Are required parameters set?

**Styling looks broken?**

- Check if `crm-empty-states.css` is loading (DevTools Network tab)
- Look for conflicting CSS
- Check browser console for errors

**Icon not displaying?**

- Verify `icon_type` spelling: `users`, `activity`, `briefcase`, `inbox`, `landmark`
- For custom, ensure SVG is properly formatted

**Buttons don't work?**

- Check URLs are correct: `/path/to/page`
- Ensure both `_url` and `_label` are provided
- Secondary button only shows if both included

## Performance

- ✅ Single CSS file (~8 KB)
- ✅ No external dependencies
- ✅ DRY principle (one reusable component)
- ✅ GPU-accelerated animations
- ✅ Responsive design included
- ✅ Dark mode included

---

**See also:** [Full Documentation](EMPTY_STATE_GUIDE.md)
