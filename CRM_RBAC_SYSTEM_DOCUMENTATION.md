# CRM Role-Based Access Control System

## Overview

This document describes the comprehensive role-based access control (RBAC) system implemented in the Open CRM. The system ensures that users only see data they are authorized to access.

## Architecture

### Centralized Access Service

**Service Class:** `Drupal\crm\Service\CRMAccessService`

The centralized service handles all access control decisions. All parts of the system should use this service rather than implementing access logic directly.

**Key Methods:**

- `canUserViewEntity($entity, $account)` - Check if user can view entity
- `canUserEditEntity($entity, $account)` - Check if user can edit entity
- `canUserDeleteEntity($entity, $account)` - Check if user can delete entity
- `getOwnerOfEntity($entity)` - Get owner ID of entity
- `getUserTeam($uid)` - Get user's team
- `isSameTeam($uid1, $uid2)` - Check if users are in same team
- `applyAccessFiltering(&$query, $account, $node_alias)` - Apply filtering to database query
- `getViewableEntitiesQuery($bundle, $account)` - Get pre-filtered query for entity type

### Access Rules

#### Administrator Role

- **Access Level:** Full
- **Rules:**
  - Can view all entities
  - Can edit all entities
  - Can delete all entities
  - No filtering applied

#### Sales Manager Role

- **Access Level:** Team
- **Rules:**
  - Can view all entities (team-based and own)
  - Can edit all entities (team-based and own)
  - Can delete all entities (team-based and own)
  - No filtering applied

#### Sales Representative Role

- **Access Level:** Restricted
- **Rules:**
  - Can view entities they own
  - Can view entities their team owns (if in a team)
  - Cannot view other teams' entities
  - Cannot view entities they don't own
  - Filtering applied to all queries

#### Anonymous User

- **Access Level:** None
- **Rules:**
  - Completely denied access to all CRM entities
  - Query results return empty set

### Owner Fields by Entity Type

| Entity Type  | Owner Field            | Purpose                        |
| ------------ | ---------------------- | ------------------------------ |
| Contact      | `field_owner`          | Owner of the contact           |
| Deal         | `field_owner`          | Owner of the deal              |
| Organization | `field_assigned_staff` | Staff member assigned to org   |
| Activity     | `field_assigned_to`    | Person activity is assigned to |

### Implementation Points

#### 1. Hook: hook_node_access()

Located in `crm.module`, this hook is called whenever Drupal checks entity access. It delegates to `CRMAccessService` for all decisions.

```php
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $access_service = \Drupal::service('crm.access_service');

  $allowed = match ($op) {
    'view' => $access_service->canUserViewEntity($node, $account),
    'update' => $access_service->canUserEditEntity($node, $account),
    'delete' => $access_service->canUserDeleteEntity($node, $account),
  };

  return $allowed ? AccessResult::allowed() : AccessResult::forbidden(...);
}
```

#### 2. Hook: hook_query_node_access_alter()

Located in `crm.module`, this hook intercepts database queries and applies access filtering. This ensures:

- Views respect access rules
- Entity queries are filtered
- REST API calls are filtered

```php
function crm_query_node_access_alter(\Drupal\Core\Database\Query\AlterableInterface $query) {
  $account = \Drupal::currentUser();
  $access_service = \Drupal::service('crm.access_service');
  $access_service->applyAccessFiltering($query, $account, 'n');
}
```

#### 3. Views Filtering

All Views that display CRM entities automatically receive filtering through the query alter hook. When filtering is applied:

- Admins/Managers see all records
- Sales Reps see only their own + team records
- Anonymous users see no records

**Views that are automatically filtered:**

- `all_contacts`
- `all_deals`
- `all_organizations`
- `all_activities`
- `my_contacts`
- `my_deals`
- etc.

#### 4. Form Defaults

In `crm_form_alter()`, new entities auto-populate the owner field:

- Contact/Deal: `field_owner` = current user
- Organization: `field_assigned_staff` = current user
- Activity: `field_assigned_to` = current user

#### 5. AI AutoComplete Service

The AI service respects access control:

- Suggestions only reference entities user can view
- Uses `CRMAccessService::getViewableContacts()`
- Uses `CRMAccessService::getViewableOrganizations()`
- Filters by user access before returning suggestions

#### 6. API Controllers

All API endpoints check access:

- `AIAutoCompleteController::access()` - Checks permissions and authentication
- Returns 403 Forbidden if denied
- Filters responses by access level

## Usage Examples

### Checking access in custom code

```php
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();
$node = Node::load(123);

if ($access_service->canUserViewEntity($node, $account)) {
  // User can view - safe to display
}
```

### Getting filtered entities

```php
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Get query pre-filtered for current user
$query = $access_service->getViewableEntitiesQuery('contact', $account);
$query->condition('status', 1);
$results = $query->execute();
```

### Custom entity query with filtering

```php
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

$query = \Drupal::database()->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title']);

// Apply filtering
$access_service->applyAccessFiltering($query, $account, 'n');

$results = $query->execute();
```

### Filtering form options

```php
use Drupal\crm_ai_autocomplete\Service\AIAccessControlTrait;

class MyController {
  use AIAccessControlTrait;

  public function buildForm() {
    $account = \Drupal::currentUser();

    // Only show contacts user can see
    $contacts = $this->getViewableContacts($account);

    return [
      'contact' => [
        '#type' => 'select',
        '#options' => $contacts,
      ],
    ];
  }
}
```

## Security Considerations

### 1. Entity Access Checks

Always use the service before:

- Displaying entity data
- Allowing entity editing
- Exporting entity data
- Processing entity in workflows

### 2. Query Filtering

Never query `node_field_data` directly without going through the access service:

**❌ Unsafe:**

```php
$query = \Drupal::database()
  ->select('node_field_data', 'n')
  ->condition('type', 'contact')
  ->execute();
```

**✅ Safe:**

```php
$access_service = \Drupal::service('crm.access_service');
$query = $access_service->getViewableEntitiesQuery('contact', $account);
```

### 3. API Responses

All REST API responses must be filtered:

- Check user permission
- Check entity access
- Filter array results
- Return only accessible data

### 4. Team-Based Access Bypass

Users can be assigned to teams using the `field_team` field on user entities. Team members can see each other's records. To disable team-based access:

- Remove the `field_team` field from users, or
- Leave it empty for users who should work independently

## Testing Access Control

### Test Case 1: Admin Views All

```php
$admin = User::load(1); // Administrator
$contact = Node::load(100); // Contact owned by someone else

$service = \Drupal::service('crm.access_service');
$this->assertTrue($service->canUserViewEntity($contact, $admin));
```

### Test Case 2: Sales Rep Views Own Only

```php
$rep = User::load(10); // Sales Rep, UID 10
$rep_contact = Node::load(101); // Owned by UID 10
$other_contact = Node::load(102); // Owned by UID 20

$service = \Drupal::service('crm.access_service');
$this->assertTrue($service->canUserViewEntity($rep_contact, $rep));
$this->assertFalse($service->canUserViewEntity($other_contact, $rep));
```

### Test Case 3: Anonymous Denied All

```php
$anon = User::load(0); // Anonymous
$contact = Node::load(100);

$service = \Drupal::service('crm.access_service');
$this->assertFalse($service->canUserViewEntity($contact, $anon));
```

## Monitoring and Logging

All access decisions are logged to the `crm_access` logger:

```
User 15 attempted view on node 432 - allowed (Entity owner)
User 20 attempted update on deal 789 - denied (Not owner, not same team)
```

View logs in: Admin → Reports → Recent log messages

## Performance Considerations

### Query Optimization

The system uses LEFT JOINs to owner fields, which can impact performance with large datasets:

1. **Indexed fields:** Ensure these fields are indexed:
   - `node__field_owner.field_owner_target_id`
   - `node__field_assigned_to.field_assigned_to_target_id`
   - `node__field_assigned_staff.field_assigned_staff_target_id`
   - `node_field_data.type`

2. **Cache warming:** Use Views caching to reduce query frequency

3. **Query analysis:** Use EXPLAIN to verify JOIN performance

## Troubleshooting

### Symptom: User sees records they shouldn't

1. Check user role via `/admin/people`
2. Check entity owner field: edit entity → check `field_owner`, `field_assigned_to`, etc.
3. Check team assignment: edit user → check `field_team`
4. Verify `crm_node_access` hook is firing: check logs
5. Clear caches: `drush cr`

### Symptom: User can't see own records

1. Verify entity owner field is set to user ID
2. Check user role is `sales_rep`, `sales_manager`, or `administrator`
3. Check Views filters aren't adding additional restrictions
4. Verify no permission restrictions at Drupal level

### Symptom: Views showing wrong data

1. Clear Views cache: `drush cache:clear views`
2. Verify Views has correct field mappings (especially owner fields)
3. Check for custom Views filters that bypass access control
4. Check query alter hook is firing: add debug logs

## Future Enhancements

1. **Field-level access:** Restrict access to specific fields
2. **Activity logging:** Detailed audit trail of all access
3. **Time-based access:** Restrict access by date range
4. **Dynamic team permissions:** Override per-user permissions
5. **Data classification:** Mark entities as sensitive/public
