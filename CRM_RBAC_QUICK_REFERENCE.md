# CRM RBAC - Developer Quick Reference Card

## 🚀 One-Page Cheat Sheet

### Check If User Can Access Entity

```php
$service = \Drupal::service('crm.access_service');
if ($service->canUserViewEntity($node, \Drupal::currentUser())) {
  // User can view this entity
}
```

### Get All Entities User Can View

```php
$service = \Drupal::service('crm.access_service');
$query = $service->getViewableEntitiesQuery('contact', \Drupal::currentUser());
$contacts = Node::loadMultiple($query->execute());
```

### Apply Access Filtering to Existing Query

```php
$query = \Drupal::database()->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title']);
$service = \Drupal::service('crm.access_service');
$service->applyAccessFiltering($query, \Drupal::currentUser(), 'n');
```

### Get Current Entity Owner ID

```php
$service = \Drupal::service('crm.access_service');
$owner_id = $service->getOwnerOfEntity($node);
```

### Get Owner Field Name for Bundle

```php
$service = \Drupal::service('crm.access_service');
$field = $service->getOwnerField('contact'); // Returns 'field_owner'
```

### Check If Users Are on Same Team

```php
$service = \Drupal::service('crm.access_service');
if ($service->isSameTeam($user1_id, $user2_id)) {
  // Users are on same team
}
```

### Get Dashboard Data

```php
$dashboard = \Drupal::service('crm.dashboard_security_service');
$recent_contacts = $dashboard->getRecentContacts(\Drupal::currentUser(), 5);
$recent_deals = $dashboard->getRecentDeals(\Drupal::currentUser(), 5);
$pipeline = $dashboard->getSalesPipeline(\Drupal::currentUser());
```

### Log Access Decision

```php
\Drupal::logger('crm_access')->info(
  'User :user accessed :entity of type :type',
  [':user' => $user_id, ':entity' => $entity_id, ':type' => $bundle]
);
```

### Filter AI Suggestions (If Using Trait)

```php
if (in_array(AIAccessControlTrait::class, class_uses(get_class($service)))) {
  $filtered = $service->filterSuggestionsByAccess($suggestions, $account);
}
```

## 📋 Access Rules at a Glance

| Role    | Contacts   | Deals      | Organizations | Activities |
| ------- | ---------- | ---------- | ------------- | ---------- |
| Admin   | All        | All        | All           | All        |
| Manager | Team       | Team       | Team          | Team       |
| Rep     | Own + Team | Own + Team | Own + Team    | Own + Team |
| Anon    | None       | None       | None          | None       |

## 🔐 Owner Fields by Entity Type

```
Contact       → field_owner (entity_reference → user)
Deal          → field_owner (entity_reference → user)
Organization  → field_assigned_staff (entity_reference → user)
Activity      → field_assigned_to (entity_reference → user)
```

## 🔌 Hook Points

### In `crm.module`:

```php
// Runs on entity access check
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $service = \Drupal::service('crm.access_service');
  $allowed = match ($op) {
    'view' => $service->canUserViewEntity($node, $account),
    'update' => $service->canUserEditEntity($node, $account),
    'delete' => $service->canUserDeleteEntity($node, $account),
  };
  return $allowed ? AccessResult::allowed() : AccessResult::forbidden();
}

// Runs when database query is executed
function crm_query_node_access_alter(&$query, $op, AccountInterface $account) {
  $service = \Drupal::service('crm.access_service');
  $service->applyAccessFiltering($query, $account, 'n');
}
```

## 🎯 Common Patterns

### Pattern 1: List Entities for Form Options

```php
$service = \Drupal::service('crm.access_service');
$query = $service->getViewableEntitiesQuery('contact', \Drupal::currentUser());
$contact_ids = $query->execute();
$contacts = Node::loadMultiple($contact_ids);

$options = [];
foreach ($contacts as $contact) {
  $options[$contact->id()] = $contact->label();
}
// Use $options in form select
```

### Pattern 2: Restrict View Results

Views usually handle this automatically via hook_query_node_access_alter(), but if needed:

```php
$query = \Drupal::database()->select('node_field_data', 'nfd');
$service = \Drupal::service('crm.access_service');
$service->applyAccessFiltering($query, \Drupal::currentUser(), 'nfd');
```

### Pattern 3: Dashboard Widget

```php
$dashboard = \Drupal::service('crm.dashboard_security_service');
$recent = $dashboard->getRecentContacts(\Drupal::currentUser(), 10);

$items = [];
foreach ($recent as $contact) {
  $items[] = [
    'title' => $contact->label(),
    'url' => $contact->toUrl(),
    'date' => $contact->changed->value,
  ];
}
```

### Pattern 4: API Response

```php
$service = \Drupal::service('crm.access_service');

if (!$service->canUserViewEntity($contact, $account)) {
  throw new AccessDeniedHttpException('Access denied');
}

return new JsonResponse($contact->toArray());
```

### Pattern 5: Bulk Operation with Access Check

```php
$service = \Drupal::service('crm.access_service');
$account = \Drupal::currentUser();

$query = \Drupal::database()->select('node_field_data', 'n')
  ->fields('n', ['nid']);
$service->applyAccessFiltering($query, $account, 'n');

foreach ($query->execute() as $row) {
  $contact = Node::load($row->nid);
  if ($account->id() == $service->getOwnerOfEntity($contact)) {
    // Only process user's own entities
    $this->processContact($contact);
  }
}
```

## 🧪 Testing Access

### Test 1: Admin Sees All

```bash
# Log in as admin
# Visit /crm/all-contacts
# Should see all contacts
```

### Test 2: Manager Sees Team

```bash
# Log in as manager
# Visit /crm/all-contacts
# Should see only team members' contacts
```

### Test 3: Rep Sees Own

```bash
# Log in as sales rep
# Visit /crm/all-contacts
# Should see only own contacts + team contacts
```

### Test 4: Anonymous Denied

```bash
# Log out (anonymous)
# Try to access /crm/all-contacts
# Should get 403 Forbidden
```

## 📊 Performance Tips

1. **Use getViewableEntitiesQuery()** for pre-filtered queries
2. **Cache results** when displaying same data multiple times
3. **Use LIMIT** on large result sets
4. **Check indexes** exist on owner fields
5. **Monitor query logs** for slow access filtering

## 🐛 Quick Troubleshooting

### "User can't see their own entity"

- [ ] Is entity published?
- [ ] Is user's ID = field_owner value?
- [ ] Did you clear caches? (`drush cr`)

### "User sees data they shouldn't"

- [ ] Check user's role at /admin/people
- [ ] Check entity's owner field
- [ ] Clear Views cache
- [ ] Check access logs: `drush log:tail --channel=crm_access`

### "Query performance is slow"

- [ ] Check database indexes exist
- [ ] Check query with EXPLAIN
- [ ] Reduce result set size
- [ ] Use Views caching

## 📚 Full Documentation Files

| File                                 | Purpose                       |
| ------------------------------------ | ----------------------------- |
| CRM_RBAC_SYSTEM_DOCUMENTATION.md     | Complete system overview      |
| CRM_RBAC_IMPLEMENTATION_GUIDE.md     | Detailed implementation guide |
| CRM_RBAC_API_REFERENCE.md            | Complete API documentation    |
| CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md | Pre-deployment checklist      |

## 🔗 Service Injection

In Drupal, inject services like this:

```php
class MyController implements ControllerInterface {
  public function __construct(
    private CRMAccessService $accessService,
    private CRMDashboardSecurityService $dashboardService,
  ) {}

  public function someAction() {
    $this->accessService->canUserViewEntity($entity, $account);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm.access_service'),
      $container->get('crm.dashboard_security_service'),
    );
  }
}
```

## 💡 Tips & Best Practices

✅ **DO:**

- Always check access before showing data
- Use service for consistency
- Cache access decisions
- Log access violations
- Test with different roles

❌ **DON'T:**

- Bypass access checks in custom code
- Create separate access logic
- Trust client-side filtering
- Cache results without user context
- Hardcode role names

## 📞 Need Help?

1. Check service docstrings: `CRMAccessService::canUserViewEntity()`
2. Review usage examples in implementation guide
3. Check test cases for real-world scenarios
4. Run `drush log:tail --channel=crm_access` for debugging
5. See troubleshooting section in main documentation

---

**Last Updated:** March 10, 2026  
**Print this card and keep it handy!**
