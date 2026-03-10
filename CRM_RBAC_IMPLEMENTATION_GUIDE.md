# CRM Access Control - Implementation Guide

This guide explains how to implement and use the centralized role-based access control system across the CRM.

## Quick Start

### 1. Basic Access Check

```php
// Get the access service
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Check if user can view an entity
$node = Node::load(123);
if ($access_service->canUserViewEntity($node, $account)) {
  // Display entity
  echo $node->getTitle();
} else {
  // Denied
  drupal_set_message('Access denied', 'error');
}
```

### 2. List Entities Respecting Access Control

```php
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

// Get pre-filtered query
$query = $access_service->getViewableEntitiesQuery('contact', $account);
$query->condition('status', 1);
$query->orderBy('title', 'ASC');

// Execute
$result = $query->execute();
$nids = [];
foreach ($result as $row) {
  $nids[] = $row->nid;
}

// Load entities
$contacts = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
```

### 3. Filter Form Options by Access

```php
// Show only contacts the user can see
$access_service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

$query = $access_service->getViewableEntitiesQuery('contact', $account);
$result = $query->execute();

$options = [];
foreach ($result as $row) {
  $options[$row->nid] = $row->title;
}

return [
  '#type' => 'select',
  '#title' => t('Select Contact'),
  '#options' => $options,
];
```

### 4. API Controller with Access Checks

```php
<?php

namespace Drupal\my_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class MyAPIController extends ControllerBase {

  /**
   * Get contact data.
   */
  public function getContact($nid) {
    $access_service = $this->container->get('crm.access_service');
    $account = $this->currentUser();

    $node = Node::load($nid);
    if (!$node) {
      return new JsonResponse(['error' => 'Not found'], 404);
    }

    // Check access before returning data
    if (!$access_service->canUserViewEntity($node, $account)) {
      return new JsonResponse(['error' => 'Access denied'], 403);
    }

    return new JsonResponse([
      'id' => $node->id(),
      'title' => $node->getTitle(),
      'email' => $node->get('field_email')->value,
      // ... other fields
    ]);
  }

}
```

### 5. Dashboard Widget with Filtering

```php
<?php

namespace Drupal\my_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

class RecentContactsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $dashboard_service = \Drupal::service('crm.dashboard_security_service');
    $account = \Drupal::currentUser();

    // Get recent contacts visible to user
    $contacts = $dashboard_service->getRecentContacts($account, 5);

    $items = [];
    foreach ($contacts as $contact) {
      $items[] = \Drupal\Core\Link::fromTextAndUrl(
        $contact->getTitle(),
        $contact->toUrl()
      );
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Recent Contacts'),
    ];
  }

}
```

### 6. Views Integration

Views automatically respect access control through `hook_query_node_access_alter()`. No code changes needed.

When a View displays CRM entities:

- **Admins** see all records
- **Managers** see all records
- **Sales Reps** see only their records + team records
- **Anonymous** see no records

### 7. Batch Processing with Access Control

```php
<?php

/**
 * Update contacts in batch.
 */
function my_module_batch_update_contacts() {
  $access_service = \Drupal::service('crm.access_service');
  $account = \Drupal::currentUser();

  // Get contacts user can access
  $query = $access_service->getViewableEntitiesQuery('contact', $account);
  $result = $query->execute();

  $batch = [
    'title' => t('Updating contacts...'),
    'operations' => [],
    'finished' => 'my_module_batch_finished',
  ];

  foreach ($result as $row) {
    // Only add tasks for accessible entities
    $batch['operations'][] = [
      'my_module_update_contact_batch',
      [$row->nid],
    ];
  }

  batch_set($batch);
}

/**
 * Batch operation.
 */
function my_module_update_contact_batch($nid, &$context) {
  $access_service = \Drupal::service('crm.access_service');
  $account = \Drupal::currentUser();

  $node = Node::load($nid);

  // Verify access before updating
  if (!$access_service->canUserEditEntity($node, $account)) {
    $context['results']['denied'][] = $nid;
    return;
  }

  // Update node
  $node->set('field_status', 'updated');
  $node->save();

  $context['results']['updated'][] = $nid;
}
```

### 8. Query Alter Hook Example

For custom queries that need filtering:

```php
<?php

/**
 * Implements hook_query_node_access_alter().
 */
function my_module_query_node_access_alter(&$query) {
  // Let CRM access service handle it
  // The crm module's query alter will apply
  // You don't need to do anything!
}
```

### 9. Custom Entity Query Filtering

```php
<?php

/**
 * Get contact deals with access control.
 */
function get_contact_deals($contact_nid) {
  $access_service = \Drupal::service('crm.access_service');
  $account = \Drupal::currentUser();

  // Build query
  $query = \Drupal::database()->select('node_field_data', 'n')
    ->fields('n', ['nid', 'title']);

  // Add custom conditions
  $query->leftJoin('node__field_contact', 'contact_ref', 'n.nid = contact_ref.entity_id');
  $query->condition('contact_ref.field_contact_target_id', $contact_nid);
  $query->condition('n.type', 'deal');

  // Apply access filtering - this is crucial!
  $access_service->applyAccessFiltering($query, $account, 'n');

  // Execute
  $result = $query->execute();
  $nids = [];
  foreach ($result as $row) {
    $nids[] = $row->nid;
  }

  return \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
}
```

### 10. AI AutoComplete with Access Filter

```php
<?php

use Drupal\crm_ai_autocomplete\Service\AIAccessControlTrait;

class MyAIController {
  use AIAccessControlTrait;

  /**
   * Get contact suggestions respecting access.
   */
  public function suggestContacts() {
    $account = \Drupal::currentUser();

    // Only returns contacts user can view
    $contacts = $this->getViewableContacts($account);

    // Only returns organizations user can view
    $orgs = $this->getViewableOrganizations($account);

    return ['contacts' => $contacts, 'organizations' => $orgs];
  }
}
```

## Common Patterns

### Pattern 1: Display Entity if Accessible

```php
if ($access_service->canUserViewEntity($entity, $account)) {
  return render($entity);
} else {
  return ['#markup' => t('Access denied')];
}
```

### Pattern 2: Filter Array of Entities

```php
$filtered_entities = array_filter($entities, function($entity) use ($access_service, $account) {
  return $access_service->canUserViewEntity($entity, $account);
});
```

### Pattern 3: Get Owner Name

```php
$owner_id = $access_service->getOwnerOfEntity($entity);
$owner = User::load($owner_id);
$owner_name = $owner ? $owner->getDisplayName() : 'Unknown';
```

### Pattern 4: Check Team Membership

```php
if ($access_service->isSameTeam($user1_id, $user2_id)) {
  // Users are in same team
}
```

### Pattern 5: Count Accessible Records

```php
$count = (int) $access_service->getViewableEntitiesQuery('contact', $account)
  ->countQuery()
  ->execute()
  ->fetchField();
```

## Logging Access Decisions

All access decisions are logged to `crm_access` channel:

```php
// View logs in Drupal
// Admin > Reports > Recent log messages

// Or from drush:
drush log:tail --channel=crm_access
```

Log format:

```
ALLOWED: User 15 attempted view on node 432 (Entity owner)
DENIEDL User 20 attempted update on deal 789 (Not owner, not same team)
```

## Performance Optimization

### 1. Index Owner Fields

```sql
-- Add indexes for faster filtering
ALTER TABLE node__field_owner ADD INDEX (field_owner_target_id);
ALTER TABLE node__field_assigned_to ADD INDEX (field_assigned_to_target_id);
ALTER TABLE node__field_assigned_staff ADD INDEX (field_assigned_staff_target_id);
```

### 2. Use Views Caching

Configure Views to cache results:

- Admin > Structure > Views > Edit view
- Settings > Caching > Check "Output" cache

### 3. Limit Query Results

When filtering large datasets, use LIMIT:

```php
$query = $access_service->getViewableEntitiesQuery('contact', $account);
$query->range(0, 50); // Limit to 50 results
$result = $query->execute();
```

### 4. Denormalize Common Calculations

If querying pipeline value frequently, consider storing in the node.

## Troubleshooting

### Issue: User sees records they shouldn't

**Check:**

1. User's role: `/admin/people`
2. Entity owner field: Edit entity → Check `field_owner`
3. User's team: Edit user → Check `field_team`
4. Access logs: `/admin/reports/dblog`

**Solution:**

- Correct entity owner field
- Correct user role
- Clear caches: `drush cr`

### Issue: User can't see own records

**Check:**

1. Entity is published: Edit entity → Published checkbox
2. Owner field matches user ID
3. User has role (`sales_rep`, `sales_manager`, `administrator`)

**Solution:**

- Publish entity
- Set correct owner ID
- Assign correct role

### Issue: Views showing wrong data

**Check:**

1. Views cache: Clear `/admin/structure/views`
2. Query alter hook firing: Check logs for access filtering
3. Views field configuration: Verify owner field is correct

**Solution:**

- Clear Views cache: `drush cache:clear views`
- Clear all caches: `drush cr`
- Verify Views field mappings

## Best Practices

1. **Always** use CRMAccessService for access decisions
2. **Never** query `node_field_data` directly without filtering
3. **Always** check access before returning entity data in APIs
4. **Test** access control in all workflows
5. **Log** important access decisions for audit trail
6. **Cache** results when possible to improve performance
7. **Document** any custom access rules in code comments

## Support Resources

- **API Documentation:** `CRM_RBAC_SYSTEM_DOCUMENTATION.md`
- **Service Class:** `Drupal\crm\Service\CRMAccessService`
- **Tests:** `web/modules/custom/crm/tests/`
- **Examples:** This document
