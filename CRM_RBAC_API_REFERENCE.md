# CRM Access Control API Reference

## Overview

This document provides complete API reference for the CRM's role-based access control system.

## Service: CRMAccessService

**Location:** `Drupal\crm\Service\CRMAccessService`  
**Container ID:** `crm.access_service`

### Public Methods

#### canUserViewEntity()

Determine if a user can view an entity.

**Signature:**

```php
public function canUserViewEntity(
  EntityInterface $entity,
  AccountInterface $account
): bool
```

**Parameters:**

- `$entity` (EntityInterface) - The entity to check access for
- `$account` (AccountInterface) - The user account

**Returns:** `bool` - TRUE if access allowed, FALSE otherwise

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$node = Node::load(123);
$user = User::load(456);

if ($service->canUserViewEntity($node, $user)) {
  // User can view the entity
}
```

---

#### canUserEditEntity()

Determine if a user can edit an entity.

**Signature:**

```php
public function canUserEditEntity(
  EntityInterface $entity,
  AccountInterface $account
): bool
```

**Parameters:**

- `$entity` (EntityInterface) - The entity to check access for
- `$account` (AccountInterface) - The user account

**Returns:** `bool` - TRUE if access allowed, FALSE otherwise

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$node = Node::load(123);

if ($service->canUserEditEntity($node, \Drupal::currentUser())) {
  // Current user can edit
  $form = $node->toArray(); // Safe to show edit form
}
```

---

#### canUserDeleteEntity()

Determine if a user can delete an entity.

**Signature:**

```php
public function canUserDeleteEntity(
  EntityInterface $entity,
  AccountInterface $account
): bool
```

**Parameters:**

- `$entity` (EntityInterface) - The entity to check access for
- `$account` (AccountInterface) - The user account

**Returns:** `bool` - TRUE if access allowed, FALSE otherwise

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$node = Node::load(123);

if ($service->canUserDeleteEntity($node, \Drupal::currentUser())) {
  // Show delete button
  $button = ['#markup' => t('Delete')];
} else {
  // Don't show delete button
  $button = [];
}
```

---

#### getOwnerField()

Get the owner/assignee field name for a bundle.

**Signature:**

```php
public function getOwnerField(string $bundle): string
```

**Parameters:**

- `$bundle` (string) - The node bundle

**Returns:** `string` - The field name

**Example:**

```php
$service = \Drupal::service('crm.access_service');

$contact_field = $service->getOwnerField('contact');        // 'field_owner'
$deal_field = $service->getOwnerField('deal');              // 'field_owner'
$activity_field = $service->getOwnerField('activity');      // 'field_assigned_to'
$org_field = $service->getOwnerField('organization');       // 'field_assigned_staff'
```

---

#### getOwnerOfEntity()

Get the owner/assignee user ID of an entity.

**Signature:**

```php
public function getOwnerOfEntity(
  EntityInterface $entity
): ?int
```

**Parameters:**

- `$entity` (EntityInterface) - The entity

**Returns:** `int|null` - The owner user ID, or NULL if not set

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$node = Node::load(123);

$owner_id = $service->getOwnerOfEntity($node);
if ($owner_id) {
  $owner = User::load($owner_id);
  echo "Owner: " . $owner->getDisplayName();
}
```

---

#### getUserTeam()

Get a user's team ID.

**Signature:**

```php
public function getUserTeam(int $uid): ?int
```

**Parameters:**

- `$uid` (int) - The user ID

**Returns:** `int|null` - The team taxonomy term ID, or NULL

**Example:**

```php
$service = \Drupal::service('crm.access_service');

$team_id = $service->getUserTeam(100);
if ($team_id) {
  $team = Term::load($team_id);
  echo "Team: " . $team->getName();
}
```

---

#### isSameTeam()

Check if two users are in the same team.

**Signature:**

```php
public function isSameTeam(
  int $uid1,
  int $uid2
): bool
```

**Parameters:**

- `$uid1` (int) - First user ID
- `$uid2` (int) - Second user ID

**Returns:** `bool` - TRUE if same team, FALSE otherwise

**Example:**

```php
$service = \Drupal::service('crm.access_service');

if ($service->isSameTeam(100, 200)) {
  // Users 100 and 200 are in the same team
  $contact_owner = User::load(200);
  $current_user = User::load(100);
  // User 100 can see User 200's contacts
}
```

---

#### getViewableEntitiesQuery()

Get a pre-filtered database query for entities a user can view.

**Signature:**

```php
public function getViewableEntitiesQuery(
  string $bundle,
  AccountInterface $account
): SelectInterface
```

**Parameters:**

- `$bundle` (string) - The node bundle to query
- `$account` (AccountInterface) - The user account

**Returns:** `SelectInterface` - A Drupal database query

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Get all contacts user can view
$query = $service->getViewableEntitiesQuery('contact', $account);
$result = $query->execute();

foreach ($result as $row) {
  echo "Contact: " . $row->title . "\n";
}
```

**Advanced Example:**

```php
$service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Get recent contacts with custom filtering
$query = $service->getViewableEntitiesQuery('contact', $account);
$query->condition('n.status', 1);  // Published only
$query->condition('n.created', time() - (30 * 24 * 3600), '>'); // Last 30 days
$query->orderBy('n.created', 'DESC');
$query->range(0, 10);  // Limit to 10

$result = $query->execute();
$nids = array_map(function($row) { return $row->nid; }, $result->fetchAll());

$contacts = Node::loadMultiple($nids);
```

---

#### applyAccessFiltering()

Apply access filtering to an existing database query.

**Signature:**

```php
public function applyAccessFiltering(
  &$query,
  AccountInterface $account,
  string $node_alias = 'n'
): void
```

**Parameters:**

- `&$query` (SelectInterface) - The database query (by reference)
- `$account` (AccountInterface) - The user account
- `$node_alias` (string) - The alias for node_field_data table

**Returns:** `void` - Modifies query in place

**Example:**

```php
$service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Build custom query
$query = \Drupal::database()
  ->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title']);
$query->condition('n.type', 'deal');
$query->condition('n.status', 1);

// Apply access filtering
$service->applyAccessFiltering($query, $account, 'n');

// Now query only returns entities user can view
$result = $query->execute();
```

---

## Service: CRMDashboardSecurityService

**Location:** `Drupal\crm\Service\CRMDashboardSecurityService`  
**Container ID:** `crm.dashboard_security_service`

### Public Methods

#### getRecentContacts()

Get recent contacts visible to user.

**Signature:**

```php
public function getRecentContacts(
  AccountInterface $account,
  int $limit = 5
): array
```

**Returns:** Array of Contact Node objects

#### getRecentDeals()

Get recent deals visible to user.

**Signature:**

```php
public function getRecentDeals(
  AccountInterface $account,
  int $limit = 5
): array
```

**Returns:** Array of Deal Node objects

#### getRecentActivities()

Get recent activities visible to user.

**Signature:**

```php
public function getRecentActivities(
  AccountInterface $account,
  int $limit = 5
): array
```

**Returns:** Array of Activity Node objects

#### getSalesPipeline()

Get pipeline statistics by stage.

**Signature:**

```php
public function getSalesPipeline(
  AccountInterface $account
): array
```

**Returns:** Array with structure:

```php
[
  'qualified' => ['count' => 5, 'total_value' => 50000],
  'proposal' => ['count' => 3, 'total_value' => 75000],
  // ...
]
```

#### getTeamPerformance()

Get performance stats grouped by team member (managers only).

**Signature:**

```php
public function getTeamPerformance(
  AccountInterface $account
): array
```

**Returns:** Array with user stats:

```php
[
  ['user' => 'John Doe', 'deals' => 5, 'total_value' => 50000],
  ['user' => 'Jane Smith', 'deals' => 8, 'total_value' => 120000],
]
```

#### getUserStats()

Get aggregate statistics for a user.

**Signature:**

```php
public function getUserStats(
  AccountInterface $account
): array
```

**Returns:** Array with stats:

```php
[
  'contacts' => 42,
  'deals' => 12,
  'activities' => 156,
  'pipeline_value' => 250000,
]
```

---

## Hooks

### hook_node_access()

Determines access to a node operation. The CRM module implements this hook to enforce role-based access.

**Example of hook implementation:**

```php
function my_module_node_access(NodeInterface $node, $op, AccountInterface $account) {
  // Delegate to CRM access service
  $access_service = \Drupal::service('crm.access_service');

  if ($access_service->canUserViewEntity($node, $account)) {
    return \Drupal\Core\Access\AccessResult::allowed();
  }

  return \Drupal\Core\Access\AccessResult::forbidden();
}
```

---

### hook_query_node_access_alter()

Alters node queries to apply access filtering before results are returned.

**Example of hook implementation:**

```php
function my_module_query_node_access_alter(&$query) {
  // Apply filtering through CRM access service
  $access_service = \Drupal::service('crm.access_service');
  $account = \Drupal::currentUser();

  $access_service->applyAccessFiltering($query, $account, 'n');
}
```

---

## Trait: AIAccessControlTrait

**Location:** `Drupal\crm_ai_autocomplete\Service\AIAccessControlTrait`

Methods for filtering AI suggestions by access control.

### filterSuggestionsByAccess()

Filter AI suggestions to only include accessible entities.

**Signature:**

```php
public function filterSuggestionsByAccess(
  array $suggestions,
  AccountInterface $account
): array
```

### getViewableContacts()

Get contacts visible to user for autocomplete.

**Signature:**

```php
public function getViewableContacts(
  AccountInterface $account
): array
```

**Returns:** Array of [nid => title]

### getViewableOrganizations()

Get organizations visible to user for autocomplete.

**Signature:**

```php
public function getViewableOrganizations(
  AccountInterface $account
): array
```

**Returns:** Array of [nid => title]

---

## Database Query Tags

Queries tagged with `node_access` automatically get filtered:

```php
$query = \Drupal::database()
  ->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title'])
  ->addTag('node_access');  // Enables automatic filtering

$result = $query->execute();
```

---

## Access Control Decision Tree

```
┌─ Is user Administrator?
│  └─ YES → ALLOW
│
├─ Is user Sales Manager?
│  └─ YES → ALLOW (all team data)
│
├─ Has bypass permission?
│  └─ YES → ALLOW
│
├─ Is user Anonymous (UID=0)?
│  └─ YES → DENY
│
├─ Is user Sales Rep?
│  ├─ Is user the owner?
│  │  └─ YES → ALLOW
│  │
│  └─ Is user in same team as owner?
│     └─ YES → ALLOW
│
└─ DEFAULT → DENY
```

---

## Error Responses

### 403 Forbidden

Returned when user doesn't have permission to access an entity.

```json
{
  "error": "Access denied",
  "message": "You do not have permission to access this record"
}
```

### 404 Not Found

Returned when entity doesn't exist or access is denied.

```json
{
  "error": "Not found",
  "message": "The requested entity does not exist"
}
```

### 401 Unauthorized

Returned when user is not authenticated.

```json
{
  "error": "Unauthorized",
  "message": "You must be logged in to access this resource"
}
```

---

## Performance Considerations

### Query Optimization

The access filtering uses LEFT JOINs on owner fields. For optimal performance:

1. Index owner fields (e.g., `field_owner_target_id`)
2. Index node type (e.g., `node_field_data.type`)
3. Use LIMIT to restrict result sets
4. Enable Views caching

### Caching

Results are cached per user:

```php
$query = $service->getViewableEntitiesQuery('contact', $account);
// Result is cached per user account
```

### Query Examples

**Slow query (without filtering):**

```sql
SELECT nid, title FROM node_field_data WHERE type = 'contact' LIMIT 1000;
```

**Optimized query (with access filtering + limit):**

```sql
SELECT n.nid, n.title FROM node_field_data n
LEFT JOIN node__field_owner o ON n.nid = o.entity_id
WHERE n.type = 'contact' AND o.field_owner_target_id = 100
LIMIT 50;
```

---

## Logging

All access decisions logged to `crm_access` channel:

**View logs:**

```bash
drush log:tail --channel=crm_access
```

**Log entries include:**

- User ID
- Operation (view/edit/delete)
- Entity type and ID
- Allow/deny decision
- Reason for decision

---

## See Also

- [CRM RBAC System Documentation](CRM_RBAC_SYSTEM_DOCUMENTATION.md)
- [CRM RBAC Implementation Guide](CRM_RBAC_IMPLEMENTATION_GUIDE.md)
- [Security Audit Checklist](CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md)
