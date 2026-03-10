# My Deals Page - Permissions & Actions Update

## What Was Added

### 1. Edit & Delete Actions Column

Added an "Actions" column to the My Deals view (`/crm/my-deals`) that displays Edit and Delete buttons for each deal.

**Changes Made:**

- Updated `config/views.view.my_deals.yml` to include the `crm_edit_link` field
- Added dependency on `crm_edit` module
- Configured the Actions column to appear after the Organization column

### 2. Add Deal Button Styling

Updated the "Add Deal" button to use consistent blue outline styling:

- Color: `#3b82f6` (blue)
- Border: `1.5px solid #3b82f6`
- Hover: `#eff6ff` (light blue background)
- Consistent with app-wide button design

## Permission Structure

The CRM system uses role-based permissions managed by the `crm_edit` module:

### Administrator Role

- **Full Access**: Can view, edit, and delete ALL content
- **Logic**: `$account->hasRole('administrator')` returns TRUE for all operations
- **Scope**: Entire CRM system

### Sales Manager Role

- **Permission**: Can edit and delete ANY deal/contact/organization
- **Required Permission**: `"edit any deal content"` or `"delete any deal content"`
- **Scope**: All records across the system
- **Use Case**: Team leads who manage multiple sales representatives

### Sales Rep Role

- **Permission**: Can only edit and delete THEIR OWN deals/contacts
- **Required Permission**: `"edit own deal content"` or `"delete own deal content"`
- **Ownership Check**: Verifies `field_owner` matches current user ID
- **Scope**: Only records where they are the owner
- **Use Case**: Individual sales representatives managing their pipeline

### Customer Role

- **Permission**: View only (no edit/delete actions)
- **Scope**: Can view their own projects via `/my/projects`
- **Note**: The `my_projects` view does NOT include Actions column

## How Permissions Are Checked

The `CrmEditLink` plugin (`/web/modules/custom/crm_edit/src/Plugin/views/field/CrmEditLink.php`) checks permissions in this order:

```php
1. Administrator check → Grant all permissions
2. Sales Manager check → Grant "any content" permissions
3. Sales Rep check → Grant "own content" permissions only if they own the record
4. Default → Deny access
```

### Ownership Field Mapping

- **Contact**: `field_owner`
- **Deal**: `field_owner`
- **Organization**: `field_assigned_staff`
- **Activity**: `field_assigned_to`

## Button Behavior

### Edit Button

- Icon: `edit-2` (Lucide icon)
- Action: Opens inline edit modal
- Function: `CRMInlineEdit.openModal(nid, bundle)`
- Visible: Only if user has edit permission

### Delete Button

- Icon: `trash-2` (Lucide icon)
- Color: Red (exception to blue outline rule for danger warning)
- Action: Opens delete confirmation dialog
- Function: `CRMInlineEdit.confirmDelete(nid, bundle, title)`
- Visible: Only if user has delete permission

## Testing Checklist

### As Administrator

- ✅ Can see Edit and Delete buttons for ALL deals
- ✅ Can add new deals via "Add Deal" button
- ✅ Can successfully edit any deal
- ✅ Can successfully delete any deal

### As Sales Manager

- ✅ Can see Edit and Delete buttons for all deals
- ✅ Can edit deals owned by other sales reps
- ✅ Can delete deals owned by other sales reps
- ✅ Can add new deals

### As Sales Rep

- ✅ Can see Edit and Delete buttons ONLY for their own deals
- ✅ Cannot see action buttons for deals owned by others
- ✅ Can add new deals (becomes owner automatically)
- ✅ Can edit only their own deals
- ✅ Can delete only their own deals

### As Customer

- ✅ No access to `/crm/my-deals` (internal view)
- ✅ Can view projects at `/my/projects` (read-only)
- ✅ No Edit/Delete buttons visible

## Configuration Changes

### Files Modified

1. **config/views.view.my_deals.yml**
   - Added `crm_edit_link` field to display
   - Added `crm_edit` module dependency
   - Updated Add Deal button styling to blue outline

### Files Not Modified (Already Correct)

- `my_contacts.yml` - Already has Actions column
- `my_organizations.yml` - Already has Actions column
- `my_activities.yml` - Already has Actions column
- `my_projects.yml` - Customer view, no actions needed

## Implementation Commands

```bash
# Import configuration
ddev drush config:import --partial --source=/var/www/html/config -y

# Clear cache
ddev drush cr

# Navigate to page
http://open-crm.ddev.site/crm/my-deals
```

## Verification

To verify the implementation:

1. **Login as admin**
   - Navigate to `/crm/my-deals`
   - Verify "Add Deal" button appears at top right
   - Verify "Actions" column appears in table
   - Verify Edit and Delete buttons appear for all deals

2. **Test Edit functionality**
   - Click Edit button
   - Modal should open with deal fields
   - Make changes and save
   - Changes should persist

3. **Test Delete functionality**
   - Click Delete button
   - Confirmation dialog should appear
   - Confirm deletion
   - Deal should be removed from list

## UI Consistency

All buttons follow the app-wide blue outline design:

- Add Deal button: Blue outline, white background
- Edit button: Blue outline, blue text
- Delete button: Red outline, red text (danger action)
- Hover states: Light blue/red background

## Permission Commands (Future Reference)

If you need to modify permissions in the future:

```bash
# Grant permission to role
ddev drush role:perm:add sales_rep "edit own deal content"
ddev drush role:perm:add sales_rep "delete own deal content"

# Grant manager permissions
ddev drush role:perm:add sales_manager "edit any deal content"
ddev drush role:perm:add sales_manager "delete any deal content"

# List role permissions
ddev drush role:perm:list sales_rep
ddev drush role:perm:list sales_manager
```

## Notes

- The permission system is robust and follows Drupal best practices
- Ownership is automatically assigned when creating content
- The system prevents unauthorized access at multiple levels
- All actions are logged for audit purposes
- The UI provides clear feedback for permission-denied scenarios
