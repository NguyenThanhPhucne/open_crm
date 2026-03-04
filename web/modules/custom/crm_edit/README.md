# CRM Edit Module

## 📝 Overview

The **CRM Edit** module provides inline editing functionality for CRM content with role-based permissions. It allows authorized users to edit Contacts, Deals, Organizations, and Activities directly from a clean, AJAX-powered interface.

## ✨ Features

### 1. **Inline Edit Forms**

- Clean, modern edit interface
- AJAX-powered form submission
- Real-time validation
- Auto-save indicators
- Unsaved changes warning

### 2. **Role-Based Permissions**

- **Sales Manager**: Can edit ANY content
- **Sales Representative**: Can edit OWN content only
- **Administrator**: Full system access
- **Customer**: No edit access (read-only)

### 3. **Content Type Support**

- ✅ Contact
- ✅ Deal
- ✅ Organization
- ✅ Activity

### 4. **UI Components**

- Floating "Quick Edit" button on detail pages
- Edit links in Views (via custom field)
- Responsive design for mobile/tablet
- Professional styling with gradients and transitions

## 📦 Installation

### Enable the module:

```bash
cd /path/to/open_crm
ddev drush en crm_edit -y
ddev drush cr
```

Or use the installation script:

```bash
bash scripts/enable_crm_edit.sh
```

## 🚀 Usage

### For End Users

#### View a Contact/Deal/Organization/Activity:

1. Navigate to any CRM content page (e.g., `/node/1`)
2. If you have edit permission, you'll see a floating "Quick Edit" button
3. Click the button to open the inline edit form

#### Edit Form Features:

- **Title**: Edit the content title
- **Fields**: All custom fields are editable
- **Required Fields**: Marked with red asterisk (\*)
- **Save**: Click "Save Changes" to update
- **Cancel**: Click "Cancel" or use back button

#### Edit Links in Views:

- Edit links appear in list views (My Contacts, My Deals, etc.)
- Only visible if you have permission to edit that specific content
- One-click access to inline editor

### For Developers

#### Access Control Logic:

```php
// Administrator: Full access
if ($account->hasRole('administrator')) {
  return AccessResult::allowed();
}

// Sales Manager: Can edit any content
if ($account->hasRole('sales_manager') &&
    $account->hasPermission("edit any {$bundle} content")) {
  return AccessResult::allowed();
}

// Sales Rep: Can edit own content only
if ($account->hasRole('sales_rep') &&
    $account->hasPermission("edit own {$bundle} content")) {
  $owner_id = $node->get($owner_field)->target_id;
  if ($owner_id == $account->id()) {
    return AccessResult::allowed();
  }
}
```

#### Routes:

- `/crm/edit/contact/{node}` - Edit Contact
- `/crm/edit/deal/{node}` - Edit Deal
- `/crm/edit/organization/{node}` - Edit Organization
- `/crm/edit/activity/{node}` - Edit Activity
- `/crm/edit/ajax/save` - AJAX Save endpoint
- `/crm/edit/ajax/validate` - AJAX Validation endpoint

#### AJAX Save Example:

```javascript
const formData = new FormData(form);
const data = {};
formData.forEach((value, key) => {
  data[key] = value;
});

const response = await fetch("/crm/edit/ajax/save", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
  },
  body: JSON.stringify(data),
});

const result = await response.json();
if (result.success) {
  // Handle success
}
```

## 🎨 Customization

### Styling

Edit the CSS file:

```
web/modules/custom/crm_edit/css/inline-edit.css
```

### JavaScript Behavior

Edit the JS file:

```
web/modules/custom/crm_edit/js/inline-edit.js
```

### Form Layout

Modify the controller:

```
web/modules/custom/crm_edit/src/Controller/InlineEditController.php
```

Method: `generateEditFormHTML()`

### Field Rendering

Customize field display in:

```php
protected function renderField($field) {
  // Your custom logic here
}
```

## 🔐 Permissions

The module respects Drupal's core node permissions:

| Role              | Contact     | Deal        | Organization | Activity    |
| ----------------- | ----------- | ----------- | ------------ | ----------- |
| **Sales Manager** | Edit Any    | Edit Any    | Edit Any     | Edit Any    |
| **Sales Rep**     | Edit Own    | Edit Own    | Edit Own     | Edit Own    |
| **Administrator** | Full Access | Full Access | Full Access  | Full Access |
| **Customer**      | View Only   | View Only   | View Only    | View Only   |

## 📊 Testing

### Test Script:

```bash
bash scripts/test_crm_edit.sh
```

### Manual Testing:

1. **As Sales Manager**:
   - Login as sales manager (manager/password)
   - Navigate to any Contact/Deal
   - Should see "Quick Edit" button
   - Should be able to edit ANY content

2. **As Sales Rep**:
   - Login as sales rep (salesrep1/password)
   - Navigate to own Contact/Deal
   - Should see "Quick Edit" button
   - Navigate to another user's content
   - Should NOT see "Quick Edit" button

3. **As Customer**:
   - Login as customer
   - Navigate to any content
   - Should NOT see "Quick Edit" button

## 🐛 Troubleshooting

### Issue: Edit button not showing

**Solution**: Check permissions with:

```bash
ddev drush role-list
ddev drush role:perm sales_rep
```

### Issue: AJAX save failing

**Solution**: Check browser console for errors. Verify CSRF token and permissions.

### Issue: Fields not updating

**Solution**:

1. Clear cache: `ddev drush cr`
2. Check field machine names
3. Verify field access permissions

## 🔄 Integration with Views

To add edit links to your Views:

1. Go to `/admin/structure/views`
2. Edit your view (e.g., "My Contacts")
3. Add field: "CRM Quick Edit Link"
4. Save view
5. Clear cache

## 📈 Future Enhancements

- [ ] Inline field editing (single-click edit)
- [ ] Bulk edit functionality
- [ ] Edit history/audit trail
- [ ] Field-level permissions
- [ ] Auto-save on blur
- [ ] Keyboard shortcuts
- [ ] Mobile-optimized editor
- [ ] Rich text editor integration
- [ ] File upload support
- [ ] Entity reference autocomplete

## 🤝 Contributing

To contribute:

1. Create feature branch
2. Implement changes
3. Test with all roles
4. Submit pull request

## 📄 License

GPL-2.0-or-later

## 👥 Credits

Developed for Open CRM System
