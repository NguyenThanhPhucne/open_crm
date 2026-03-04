# CRM Edit Module - Permissions Guide

## Overview

The CRM Edit module provides inline editing, creation, and deletion functionality for CRM content types with role-based access control. This document explains the permission structure and what each role can do.

## Roles and Permissions

### 1. Administrator

**Full Access to Everything**

Administrators have complete control over all CRM entities regardless of ownership.

**Permissions:**

- ✅ **Create** any Contact, Deal, Organization, or Activity
- ✅ **View** all CRM entities
- ✅ **Edit** any CRM entity (even if owned by others)
- ✅ **Delete** any CRM entity (even if owned by others)

**Use Cases:**

- System administration and maintenance
- Data cleanup and quality control
- Override any restrictions for special cases
- Configure CRM settings and workflows

---

### 2. Sales Manager

**Team-Wide Access**

Sales Managers can manage all CRM entities within their team/organization, regardless of individual ownership.

**Permissions:**

#### Contacts

- ✅ Create new contacts
- ✅ View all contacts
- ✅ Edit any contact (including those owned by sales reps)
- ✅ Delete any contact

#### Deals

- ✅ Create new deals
- ✅ View all deals
- ✅ Edit any deal
- ✅ Delete any deal

#### Organizations

- ✅ Create new organizations
- ✅ View all organizations
- ✅ Edit any organization
- ✅ Delete any organization

#### Activities

- ✅ Create new activities
- ✅ View all activities
- ✅ Edit any activity
- ✅ Delete any activity

**Use Cases:**

- Oversee team performance and pipelines
- Reassign leads and deals between team members
- Quality control for team data
- Intervene in critical deals
- Report on team metrics

**Technical Permissions Required:**

```
create contact content
create deal content
create organization content
create activity content

edit any contact content
edit any deal content
edit any organization content
edit any activity content

delete any contact content
delete any deal content
delete any organization content
delete any activity content

view any contact content
view any deal content
view any organization content
view any activity content
```

---

### 3. Sales Rep (Sales Representative)

**Own Content Only**

Sales Reps can only manage CRM entities that they own or are assigned to them.

**Permissions:**

#### Contacts

- ✅ Create new contacts (automatically assigned as owner)
- ✅ View own contacts (where `field_owner` = current user)
- ✅ Edit own contacts only
- ✅ Delete own contacts only
- ❌ Cannot edit/delete other reps' contacts

#### Deals

- ✅ Create new deals (automatically assigned as owner)
- ✅ View own deals (where `field_owner` = current user)
- ✅ Edit own deals only
- ✅ Delete own deals only
- ❌ Cannot edit/delete other reps' deals

#### Organizations

- ✅ Create new organizations (automatically assigned as staff)
- ✅ View assigned organizations (where `field_assigned_staff` = current user)
- ✅ Edit assigned organizations only
- ✅ Delete assigned organizations only
- ❌ Cannot edit/delete organizations not assigned to them

#### Activities

- ✅ Create new activities (automatically assigned)
- ✅ View assigned activities (where `field_assigned_to` = current user)
- ✅ Edit assigned activities only
- ✅ Delete assigned activities only
- ❌ Cannot edit/delete activities assigned to others

**Use Cases:**

- Manage personal leads and contacts
- Track own sales pipeline
- Update deal stages and information
- Log activities and follow-ups
- Self-service data entry

**Technical Permissions Required:**

```
create contact content
create deal content
create organization content
create activity content

edit own contact content
edit own deal content
edit own organization content
edit own activity content

delete own contact content
delete own deal content
delete own organization content
delete own activity content

view own contact content
view own deal content
view own organization content
view own activity content
```

---

## Ownership Fields

The module determines ownership through specific fields on each content type:

| Content Type | Ownership Field        | Field Type              |
| ------------ | ---------------------- | ----------------------- |
| Contact      | `field_owner`          | Entity Reference (User) |
| Deal         | `field_owner`          | Entity Reference (User) |
| Organization | `field_assigned_staff` | Entity Reference (User) |
| Activity     | `field_assigned_to`    | Entity Reference (User) |

**Important:** When a Sales Rep creates new content, the ownership field is automatically set to their user ID.

---

## Permission Checks in the Module

### Create Permission Check

**Location:** `AddController::checkCreateAccess()`

```php
protected function checkCreateAccess($type) {
  $account = $this->currentUser();

  // Admin has full access
  if ($account->hasRole('administrator')) {
    return TRUE;
  }

  // Sales manager can create any content
  if ($account->hasRole('sales_manager')) {
    if ($account->hasPermission("create {$type} content")) {
      return TRUE;
    }
  }

  // Sales rep can create own content
  if ($account->hasRole('sales_rep')) {
    if ($account->hasPermission("create {$type} content")) {
      return TRUE;
    }
  }

  return FALSE;
}
```

### Edit Permission Check

**Location:** `ModalEditController::checkEditAccess()` & `InlineEditController::checkEditAccess()`

```php
protected function checkEditAccess(NodeInterface $node) {
  $account = $this->currentUser();

  // Admin has full access
  if ($account->hasRole('administrator')) {
    return TRUE;
  }

  $bundle = $node->bundle();

  // Managers can edit any content
  if ($account->hasRole('sales_manager')) {
    if ($account->hasPermission("edit any {$bundle} content")) {
      return TRUE;
    }
  }

  // Sales reps can only edit own content
  if ($account->hasRole('sales_rep')) {
    $owner_field = $this->getOwnerField($bundle);
    if ($node->hasField($owner_field)) {
      $owner_id = $node->get($owner_field)->target_id;
      if ($owner_id == $account->id() && $account->hasPermission("edit own {$bundle} content")) {
        return TRUE;
      }
    }
  }

  return FALSE;
}
```

### Delete Permission Check

**Location:** `DeleteController::checkDeleteAccess()`

```php
protected function checkDeleteAccess(NodeInterface $node) {
  $account = $this->currentUser();

  // Admin has full access
  if ($account->hasRole('administrator')) {
    return AccessResult::allowed();
  }

  $bundle = $node->bundle();

  // Managers can delete any content
  if ($account->hasRole('sales_manager')) {
    if ($account->hasPermission("delete any {$bundle} content")) {
      return AccessResult::allowed();
    }
  }

  // Sales reps can only delete own content
  if ($account->hasRole('sales_rep')) {
    $owner_field = $this->getOwnerField($bundle);
    if ($node->hasField($owner_field)) {
      $owner_id = $node->get($owner_field)->target_id;
      if ($owner_id == $account->id()) {
        if ($account->hasPermission("delete own {$bundle} content")) {
          return AccessResult::allowed();
        }
      }
    }
  }

  return AccessResult::forbidden('You do not have permission to delete this content.');
}
```

---

## User Interface Features

### Action Buttons

Edit and Delete buttons only appear when the user has permission to perform that action.

**Implementation:** `CrmEditLink.php`

```php
// Check if user can edit
$can_edit = $this->checkPermission($account, $entity, $bundle, 'edit');

// Check if user can delete
$can_delete = $this->checkPermission($account, $entity, $bundle, 'delete');

if (!$can_edit && !$can_delete) {
  return ''; // No buttons shown
}
```

### Add Buttons (Local Actions)

Add buttons appear on CRM views (My Contacts, My Deals, etc.) based on create permissions:

- **Administrator:** Sees all Add buttons
- **Sales Manager:** Sees all Add buttons
- **Sales Rep:** Sees all Add buttons (creates content as owner)

**Routes:**

- `/crm/add/contact` - Add Contact
- `/crm/add/deal` - Add Deal
- `/crm/add/organization` - Add Organization
- `/crm/add/activity` - Add Activity

---

## Security Features

### 3-Step Delete Confirmation

All users (including administrators) must go through a 3-step confirmation process before deleting any CRM entity:

1. **Step 1:** "I want to delete this [type]" - Initial confirmation
2. **Step 2:** Warning modal showing critical information and consequences
3. **Step 3:** Type exact entity name to confirm

**Why?** Prevents accidental deletion of critical business data.

### Title-Based Confirmation

Users must type the exact title of the entity to confirm deletion (not a generic word like "DELETE").

**Example:**

- Entity: "Acme Corp - Q1 Deal"
- Confirmation Required: User must type "Acme Corp - Q1 Deal" exactly

### Audit Logging

All create, edit, and delete operations are logged with:

- User who performed the action
- Entity type and ID
- Entity title
- Timestamp

**Log Messages:**

```
Created new deal: "New Deal" (nid: 123) by user: john_doe
Updated contact: "Jane Smith" (nid: 456) by user: sales_manager
Deleted organization: "Old Corp" (nid: 789) by user: admin
```

---

## Configuring Permissions

### Via Drupal UI

1. Navigate to: **Administration → People → Permissions** (`/admin/people/permissions`)
2. Find the role you want to configure
3. Check/uncheck permissions for each content type

### Recommended Permission Sets

#### For Sales Rep Role:

```
☑ create contact content
☑ create deal content
☑ create organization content
☑ create activity content

☑ edit own contact content
☑ edit own deal content
☑ edit own organization content
☑ edit own activity content

☑ delete own contact content
☑ delete own deal content
☑ delete own organization content
☑ delete own activity content

☑ view own contact content
☑ view own deal content
☑ view own organization content
☑ view own activity content
```

#### For Sales Manager Role:

```
☑ create contact content
☑ create deal content
☑ create organization content
☑ create activity content

☑ edit any contact content
☑ edit any deal content
☑ edit any organization content
☑ edit any activity content

☑ delete any contact content
☑ delete any deal content
☑ delete any organization content
☑ delete any activity content

☑ view any contact content
☑ view any deal content
☑ view any organization content
☑ view any activity content
```

#### For Administrator Role:

```
All permissions are granted by default (bypass node access)
```

---

## Common Use Cases

### Use Case 1: Sales Rep Creates and Manages Lead

1. Sales Rep logs in
2. Navigates to "My Contacts" view
3. Clicks "Add Contact" button
4. Fills out contact form in modal
5. Saves → Entity created with `field_owner` = Sales Rep's user ID
6. Can now view, edit, and delete this contact
7. ❌ Cannot see or edit other reps' contacts

### Use Case 2: Sales Manager Reassigns Deal

1. Sales Manager logs in
2. Views all deals (including those owned by reps)
3. Opens deal owned by Sales Rep A
4. Edits the deal
5. Changes `field_owner` from Sales Rep A to Sales Rep B
6. Saves → Deal now visible to Sales Rep B
7. Sales Rep A loses access (unless manager grants it back)

### Use Case 3: Administrator Deletes Duplicate Contact

1. Administrator logs in
2. Finds duplicate contact (owned by any user)
3. Clicks "Delete" button
4. Goes through 3-step confirmation:
   - Step 1: "I want to delete this contact"
   - Step 2: Reads warnings about related data
   - Step 3: Types exact contact name "John Duplicate"
5. Confirms → Contact deleted
6. Action logged in system logs

---

## Troubleshooting

### Problem: User Can't See Edit/Delete Buttons

**Check:**

1. User role assignment (Administrator, Sales Manager, or Sales Rep?)
2. Drupal permissions for that role (`/admin/people/permissions`)
3. Ownership field value on the entity
4. Module is enabled and cache is clear

**Solution:**

```bash
ddev drush cr
```

### Problem: Sales Rep Can Edit Other Reps' Content

**Check:**

1. Permissions are set to "edit **own** content" not "edit **any** content"
2. Ownership field is correctly populated
3. Permission check logic in controllers

**Solution:**

```bash
# Check permissions
ddev drush role:perm sales_rep

# Should NOT include "edit any [type] content"
```

### Problem: Add Button Doesn't Appear

**Check:**

1. User has "create [type] content" permission
2. Local actions are configured in `crm_edit.links.action.yml`
3. Route exists in `crm_edit.routing.yml`
4. Cache is cleared

**Solution:**

```bash
ddev drush cr
```

---

## Module Information

**Module Name:** CRM Edit  
**Version:** 3.0  
**Maintainer:** CRM Development Team  
**Last Updated:** 2024

**Files:**

- Controllers: `src/Controller/AddController.php`, `DeleteController.php`, `ModalEditController.php`, `InlineEditController.php`
- Views Plugin: `src/Plugin/views/field/CrmEditLink.php`
- JavaScript: `js/inline-edit.js`
- Styles: `css/inline-edit.css`
- Routes: `crm_edit.routing.yml`
- Local Actions: `crm_edit.links.action.yml`

**Documentation:**

- This file: `PERMISSIONS.md`
- Main README: `README.md`
- Security Audit: `SECURITY_AUDIT_REPORT.md`
- Features Guide: `FEATURES_GUIDE.md`

---

## Support

For questions or issues:

1. Check this documentation first
2. Review Drupal core permission documentation
3. Check module logs: `Reports → Recent log messages`
4. Contact your system administrator
