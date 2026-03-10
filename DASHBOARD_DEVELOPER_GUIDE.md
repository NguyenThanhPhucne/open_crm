# CRM Dashboard - Developer Extension Guide

## Overview

This guide helps developers extend the real-time dashboard system and implement custom widgets or features.

---

## Real-Time Event System Architecture

### Available Hooks

All hooks are invoked via Drupal's `moduleHandler->invokeAll()` pattern and are defined in `crm_dashboard.module`.

#### 1. `crm_dashboard_activity_created`

**When triggered:** When a new activity node is created
**Context:**

```php
[
  'entity' => $node,        // The activity node object
  'operation' => 'insert',  // Always 'insert' for this hook
]
```

**Example implementation:**

```php
function my_module_crm_dashboard_activity_created($context) {
  // Send notification when activity is created
  $activity = $context['entity'];
  \Drupal::logger('my_module')->info('Activity created: ' . $activity->getTitle());

  // Could trigger email, webhook, etc.
}
```

#### 2. `crm_dashboard_activity_updated`

**When triggered:** When an existing activity node is modified
**Context:**

```php
[
  'entity' => $node,        // The updated activity node
  'operation' => 'update',  // Always 'update' for this hook
]
```

#### 3. `crm_dashboard_activity_deleted`

**When triggered:** When an activity node is deleted
**Context:**

```php
[
  'entity' => $node,        // The deleted activity node
  'operation' => 'delete',  // Always 'delete' for this hook
]
```

#### 4. `crm_dashboard_deal_updated`

**When triggered:** When a deal is created, updated, or deleted
**Context:**

```php
[
  'entity' => $node,              // The deal node
  'operation' => 'insert|update|delete'  // Operation type
]
```

#### 5. `crm_dashboard_stage_changed` (PRIORITY EVENT)

**When triggered:** When a deal's pipeline stage changes
**Context:**

```php
[
  'entity' => $node,       // The deal node
  'old_stage' => '...',    // Previous stage value/ID
  'new_stage' => '...',    // New stage value/ID
]
```

**Special handling:**
This is the most critical event for dashboard updates. Implement this hook for:

- Pipeline visualization updates
- Stage-specific validations
- Workflow automations

---

## Implementing Custom Event Listeners

### Example 1: Send Email on Deal Stage Change

```php
// In my_module.module
function my_module_crm_dashboard_stage_changed($context) {
  $deal = $context['entity'];
  $old_stage = $context['old_stage'];
  $new_stage = $context['new_stage'];

  // Only notify on specific transitions
  if ($old_stage === 'qualified' && $new_stage === 'proposal') {
    $owner = $deal->get('field_owner')->entity;

    $mail_manager = \Drupal::service('plugin.manager.mail');
    $email = $owner->getEmail();

    $mail_manager->mail(
      'my_module',
      'deal_moved_to_proposal',
      $email,
      'en',
      ['deal' => $deal],
      NULL
    );
  }
}
```

### Example 2: Update External System on Activity Create

```php
function my_module_crm_dashboard_activity_created($context) {
  $activity = $context['entity'];

  // Sync with external CRM API
  $external_api = new ExternalCRMAPI();
  $external_api->createActivity([
    'title' => $activity->getTitle(),
    'type' => $activity->get('field_type')->target_id,
    'contact_id' => $activity->get('field_contact')->target_id,
    'created' => $activity->getCreatedTime(),
  ]);
}
```

### Example 3: Real-Time WebSocket Broadcasting

```php
function my_module_crm_dashboard_deal_updated($context) {
  $deal = $context['entity'];
  $operation = $context['operation'];

  // Broadcast to connected WebSocket clients
  $broadcaster = \Drupal::service('websocket.broadcaster');
  $broadcaster->broadcast('dashboard:deal:' . $operation, [
    'deal_id' => $deal->id(),
    'title' => $deal->getTitle(),
    'timestamp' => time(),
  ]);
}
```

---

## JavaScript Dashboard Synchronization

### Accessing the Dashboard Sync Class

```javascript
// The DashboardSync instance is available globally after page load
window.dashboardSync;

// Check if connected
console.log(window.dashboardSync.lastRefreshTime);

// Manually trigger refresh
window.dashboardSync.refreshDashboard();

// Listen for custom events
document.addEventListener("crm:activity-created", () => {
  console.log("Activity created - dashboard refreshing");
});
```

### Available Custom Events

```javascript
// Dispatch when activity is created
document.dispatchEvent(new Event("crm:activity-created"));

// Dispatch when deal is updated
document.dispatchEvent(new Event("crm:deal-updated"));

// Dispatch when stage changes
document.dispatchEvent(new Event("crm:stage-changed"));
```

### Example: Custom Widget Listening to Events

```javascript
class CustomWidget {
  constructor() {
    document.addEventListener("crm:activity-created", () =>
      this.onActivityCreated(),
    );
    document.addEventListener("crm:stage-changed", () => this.onStageChanged());
  }

  onActivityCreated() {
    console.log("Activity created - updating widget");
    this.refreshData();
  }

  onStageChanged() {
    console.log("Deal stage changed - refreshing widget");
    this.refreshData();
  }

  async refreshData() {
    const response = await fetch("/crm/dashboard/refresh");
    const data = await response.json();
    this.render(data);
  }
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", () => {
  window.customWidget = new CustomWidget();
});
```

---

## Custom Metrics & Widgets

### Adding a New Metric Card

To add a new metric to the dashboard KPI cards:

**1. Update DashboardController.php:**

```php
// Add query for new metric
$new_metric_count = \Drupal::entityQuery('node')
  ->condition('type', 'some_entity_type')
  ->accessCheck(FALSE);
if (!$is_admin) {
  $new_metric_count->condition('field_owner', $user_id);
}
$new_metric_count = $new_metric_count->count()->execute();

// Calculate trend
$this_week_query = \Drupal::entityQuery('node')
  ->condition('type', 'some_entity_type')
  ->condition('created', $this_week_start, '>=')
  ->accessCheck(FALSE);
if (!$is_admin) {
  $this_week_query->condition('field_owner', $user_id);
}
$new_metric_this_week = $this_week_query->count()->execute();
```

**2. Add to HTML:**

```html
<a href="/path/to/view" class="stat-card">
  <div class="stat-header">
    <div class="stat-icon custom-color">
      <i data-lucide="icon-name" width="24" height="24"></i>
    </div>
    <div class="stat-label">Custom Metric</div>
  </div>
  <div class="stat-value">{$new_metric_count}</div>
  <div class="stat-desc">Description</div>
  <div class="stat-trend positive">
    <span class="stat-trend-icon">↑</span>
    <span>+{$new_metric_this_week} this week</span>
  </div>
</a>
```

**3. Add custom styling:**

```css
.stat-icon.custom-color {
  background: linear-gradient(135deg, #yourcolor1 0%, #yourcolor2 100%);
  color: #textcolor;
}
```

---

## Extending the Real-Time Refresh System

### Option A: Replace Polling with WebSockets

Current implementation uses 30-second polling. To implement WebSockets:

**1. Create a WebSocket service provider:**

```php
// services.yml
services:
  dashboard.websocket:
    class: Drupal\crm_dashboard\WebSocketServer
    arguments: ['@logger.factory']
```

**2. Update DashboardSync class:**

```javascript
class DashboardSyncWebSocket extends DashboardSync {
  init() {
    // Connect to WebSocket server
    this.ws = new WebSocket("wss://your-domain/dashboard-sync");

    this.ws.onopen = () => {
      console.log("WebSocket connected");
      this.showRefreshStatus("Connected");
    };

    this.ws.onmessage = (event) => {
      const data = JSON.parse(event.data);
      if (data.type === "refresh") {
        this.refreshDashboard();
      }
    };

    this.ws.onerror = () => {
      this.fallbackToPolling();
    };
  }

  fallbackToPolling() {
    // Fall back to regular polling if WebSocket fails
    super.init();
  }
}
```

### Option B: Implement Push Notifications

```php
function my_module_crm_dashboard_stage_changed($context) {
  // Push notification to user
  $deal = $context['entity'];
  $owner = $deal->get('field_owner')->entity;

  $notification = \Drupal::service('notifications.service')->create([
    'uid' => $owner->id(),
    'title' => 'Deal moved: ' . $deal->getTitle(),
    'message' => 'Deal moved to ' . $context['new_stage'],
    'type' => 'dashboard_update',
  ]);

  $notification->save();
}
```

---

## Cache Management

### Cache Tags

Dashboard uses these cache tags for invalidation:

```
crm_dashboard:pipeline       - Invalidated when deals change stage/amount
crm_dashboard:metrics        - Invalidated when deals are created/deleted
crm_dashboard:recent_activities - Invalidated when activities change
```

**Manual invalidation example:**

```php
\Drupal\Core\Cache\Cache::invalidateTags([
  'crm_dashboard:pipeline',
  'crm_dashboard:metrics'
]);
```

### Cache Control in Hooks

```php
function my_module_node_presave($node) {
  if ($node->bundle() === 'deal') {
    // Clear dashboard cache before save
    \Drupal\Core\Cache\Cache::invalidateTags(['crm_dashboard:pipeline']);
  }
}
```

---

## Testing Event Hooks

### Unit Test Example

```php
namespace Drupal\Tests\crm_dashboard\Unit;

use Drupal\Tests\UnitTestCase;

class DashboardHooksTest extends UnitTestCase {

  public function testDealStageChangeEvent() {
    $deal = $this->createMock('Drupal\node\Entity\Node');
    $deal->method('getTitle')->willReturn('Test Deal');

    $context = [
      'entity' => $deal,
      'old_stage' => 'qualified',
      'new_stage' => 'proposal',
    ];

    // Verify hook is called
    \Drupal::moduleHandler()->invokeAll('crm_dashboard_stage_changed', [$context]);

    // Assert expected behavior
    $this->assertTrue(true);
  }
}
```

### Manual Testing

```bash
# 1. Edit a deal and change its stage
# 2. Check Drupal logs
drush log:watch

# 3. Monitor real-time refresh
# Open browser console and watch DashboardSync class
> window.dashboardSync.lastRefreshTime

# 4. Test webhook/external API calls
# Add logging to your custom hook implementation
drupal_set_message('hook_crm_dashboard_stage_changed fired');
```

---

## Performance Optimization Tips

### 1. Optimize Entity Queries

```php
// ❌ Bad: Loads full entities
$deals = \Drupal::entityTypeManager()->getStorage('node')
  ->loadByProperties(['type' => 'deal']);

// ✅ Good: Query only IDs
$deal_ids = \Drupal::entityQuery('node')
  ->condition('type', 'deal')
  ->execute();
```

### 2. Use Cache Properly

```php
// Cache expensive calculations
$cache = \Drupal::cache()->get('my_dashboard_data');
if (!$cache) {
  $data = expensiveCalculation();
  \Drupal::cache()->set('my_dashboard_data', $data, \Drupal\Core\Cache\Cache::PERMANENT);
} else {
  $data = $cache->data;
}
```

### 3. Implement Rate Limiting

```php
function my_module_crm_dashboard_activity_created($context) {
  static $call_count = 0;
  static $last_reset = 0;

  $now = time();
  if ($now - $last_reset > 60) {
    $call_count = 0;
    $last_reset = $now;
  }

  if ($call_count > 100) {
    \Drupal::logger('my_module')->warning('Dashboard hook rate limit exceeded');
    return;
  }

  $call_count++;
  // Process hook...
}
```

---

## Troubleshooting

### Real-time Events Not Triggering

**Check:**

1. Module `crm_dashboard` is enabled
2. `crm_dashboard.module` exists in correct directory
3. Hooks are spelled correctly (no typos)
4. Entity type is 'activity' or 'deal' (checks are case-sensitive)

```bash
# Disable and re-enable
drush pm:uninstall crm_dashboard
drush pm:install crm_dashboard
```

### Dashboard Not Refreshing

**Check:**

1. Browser console for errors (F12)
2. DashboardSync is initialized: `window.dashboardSync`
3. Polling interval (30 seconds default)
4. `/crm/dashboard/refresh` endpoint accessible

```javascript
// Test endpoint manually
fetch("/crm/dashboard/refresh")
  .then((r) => r.json())
  .then((d) => console.log(d));
```

### Cache Not Invalidating

**Check:**

1. Cache tags are correct
2. Internal page cache is enabled (`admin/config/development/performance`)
3. Clear all caches after changes

```bash
drush cc all
```

---

## Further Documentation

- Drupal Hooks: https://www.drupal.org/docs/drupal-apis/entity-api/hooks
- Drupal Cache API: https://www.drupal.org/docs/drupal-apis/cache-api
- Entity API: https://www.drupal.org/docs/drupal-apis/entity-api

---

## Support & Questions

For implementation questions or issues:

1. Check this guide's troubleshooting section
2. Review `DASHBOARD_REFACTORING_SUMMARY.md`
3. Check `crm_dashboard.module` for working examples
4. Review DashboardController.php for frontend patterns
