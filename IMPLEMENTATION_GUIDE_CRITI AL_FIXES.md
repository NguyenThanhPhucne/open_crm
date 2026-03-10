# CRM Integrity Module: Implementation Guide

**Created:** March 9, 2026  
**Objective:** Fix critical data integrity issues in open_crm  
**Timeline:** 1-2 weeks for critical issues

---

## Phase 1: Stage Format Normalization (FIRST - DO THIS FIRST)

### Why First?

- Dashboard calculations depend on consistent stage format
- All other validations reference stages
- Quick fix with big impact
- Must update before adding new validation

### Implementation

#### Step 1.1: Create Migration Script

```php
// File: web/modules/custom/crm/src/Commands/DataIntegrityCommands.php

namespace Drupal\crm\Commands;

use Symfony\Component\Console\Output\OutputInterface;
use Drush\Commands\DrushCommands;

class DataIntegrityCommands extends DrushCommands {

  /**
   * Normalize all stage values from integer IDs to string identifiers.
   *
   * @command crm:normalize-stages
   * @aliases crm-norm-stages
   * @description Convert all deals with numeric stage IDs to string format
   */
  public function normalizeStages() {
    $this->output()->writeln('Starting stage format normalization...');

    $stage_map = [
      1 => 'qualified',
      2 => 'proposal',
      3 => 'negotiation',
      5 => 'closed_won',
      6 => 'closed_lost',
    ];

    // Load all deals
    $deal_ids = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(FALSE)
      ->execute();

    $deals = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($deal_ids);

    $updated_count = 0;
    $conversion_log = [];

    foreach ($deals as $deal) {
      $current_stage = $deal->get('field_stage')->value;

      // Skip if already string format
      if (!is_numeric($current_stage)) {
        $this->output()->writeln("Deal {$deal->id()} already string format: {$current_stage}");
        continue;
      }

      // Convert numeric to string
      if (isset($stage_map[$current_stage])) {
        $old_stage = $current_stage;
        $new_stage = $stage_map[$current_stage];

        $deal->get('field_stage')->setValue($new_stage);
        $deal->save();
        $updated_count++;

        $conversion_log[] = [
          'deal_id' => $deal->id(),
          'deal_title' => $deal->getTitle(),
          'old_stage' => $old_stage,
          'new_stage' => $new_stage,
        ];

        $this->output()->writeln("Converted Deal {$deal->id()}: {$old_stage} → {$new_stage}");
      } else {
        $this->logger()->warning("Unknown stage ID {$current_stage} for Deal {$deal->id()}");
      }
    }

    $this->output()->writeln("\n=== STAGE NORMALIZATION COMPLETE ===");
    $this->output()->writeln("Deals updated: {$updated_count}");
    $this->output()->writeln("\nConversion log:");

    foreach ($conversion_log as $entry) {
      $this->output()->writeln(sprintf(
        "  Deal %d (%s): %s → %s",
        $entry['deal_id'],
        $entry['deal_title'],
        $entry['old_stage'],
        $entry['new_stage']
      ));
    }
  }
}
```

#### Step 1.2: Run Migration

```bash
cd /var/www/open_crm
drush crm:normalize-stages

# Output should show all conversions
# Verify:
drush sql:query "SELECT DISTINCT field_stage_value FROM node__field_stage WHERE entity_bundle = 'deal';"
# Should show: qualified, proposal, negotiation, closed_won, closed_lost (all strings)
```

#### Step 1.3: Update Dashboard to Use Consistent Format

```php
// File: web/modules/custom/crm_dashboard/src/Controller/DashboardController.php
// Line 150-170 - REPLACE THE INCONSISTENT CODE

// OLD (REMOVE):
// if ($stage_id === 5) { // Hard-coded ID
//   $won_value += $amount;
//   $won_count++;
// } elseif ($stage_id === 6) {
//   $lost_value += $amount;
//   $lost_count++;
// }

// NEW (REPLACE WITH):
foreach ($deals as $deal) {
  $amount = 0;
  if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
    $amount = floatval($deal->get('field_amount')->value);
    $total_value += $amount;
  }

  if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
    $stage_value = $deal->get('field_stage')->value;

    // Use string comparison (consistent across system)
    if ($stage_value === 'closed_won') {
      $won_value += $amount;
      $won_count++;
    } elseif ($stage_value === 'closed_lost') {
      $lost_value += $amount;
      $lost_count++;
    }
  }
}
```

#### Step 1.4: Update Kanban Controller to Use Consistent Format

```php
// File: web/modules/custom/crm_kanban/src/Controller/KanbanController.php
// Line 1438 - REPLACE:

// OLD:
// if ($stage_id == 5) {

// NEW:
if ($stage_value === 'closed_won') { // Use string comparison
```

---

## Phase 2: Add Orphan Detection Service

### Step 2.1: Create Data Integrity Service

```php
// File: web/modules/custom/crm/src/Service/DataIntegrityService.php

namespace Drupal\crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class DataIntegrityService {

  protected $entityTypeManager;
  protected $loggerFactory;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Find all orphaned entities (entities without required owner/assignment).
   */
  public function findOrphanedEntities() {
    $issues = [];

    // DEALS WITHOUT OWNERS
    $deal_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'deal')
      ->condition('field_owner', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($deal_ids)) {
      $issues['orphaned_deals_without_owner'] = [
        'count' => count($deal_ids),
        'entity_ids' => array_values($deal_ids),
        'severity' => 'critical',
        'description' => 'Deals without assigned owner - dashboard filters exclude these',
        'action' => 'Assign to creator or sales team member',
      ];
    }

    // ACTIVITIES WITHOUT ASSIGNMENTS
    $activity_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'activity')
      ->condition('field_assigned_to', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($activity_ids)) {
      $issues['orphaned_activities_unassigned'] = [
        'count' => count($activity_ids),
        'entity_ids' => array_values($activity_ids),
        'severity' => 'critical',
        'description' => 'Activities without assigned user - no one responsible',
        'action' => 'Assign to user or delete if no longer needed',
      ];
    }

    // DEALS WITHOUT CONTACT OR ORGANIZATION
    $all_deals = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'deal']);

    $unlinked_deals = [];
    foreach ($all_deals as $deal) {
      $has_contact = !$deal->get('field_contact')->isEmpty();
      $has_org = !$deal->get('field_organization')->isEmpty();

      if (!$has_contact && !$has_org) {
        $unlinked_deals[] = $deal->id();
      }
    }

    if (!empty($unlinked_deals)) {
      $issues['deals_without_contact_or_org'] = [
        'count' => count($unlinked_deals),
        'entity_ids' => $unlinked_deals,
        'severity' => 'major',
        'description' => 'Deals not linked to contact or organization',
        'action' => 'Link to contact or delete if duplicate',
      ];
    }

    return $issues;
  }

  /**
   * Find broken entity references.
   */
  public function findBrokenReferences() {
    $broken = [];

    // DEALS REFERENCING DELETED CONTACTS
    $deals = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'deal']);

    foreach ($deals as $deal) {
      if (!$deal->get('field_contact')->isEmpty()) {
        $contact = $deal->get('field_contact')->entity;
        if (!$contact) {
          $broken[] = [
            'type' => 'deal',
            'id' => $deal->id(),
            'title' => $deal->getTitle(),
            'field' => 'field_contact',
            'target_id' => $deal->get('field_contact')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete deal',
          ];
        }
      }

      if (!$deal->get('field_organization')->isEmpty()) {
        $org = $deal->get('field_organization')->entity;
        if (!$org) {
          $broken[] = [
            'type' => 'deal',
            'id' => $deal->id(),
            'title' => $deal->getTitle(),
            'field' => 'field_organization',
            'target_id' => $deal->get('field_organization')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete deal',
          ];
        }
      }
    }

    // ACTIVITIES REFERENCING DELETED DEALS
    $activities = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'activity']);

    foreach ($activities as $activity) {
      if (!$activity->get('field_deal')->isEmpty()) {
        $deal = $activity->get('field_deal')->entity;
        if (!$deal) {
          $broken[] = [
            'type' => 'activity',
            'id' => $activity->id(),
            'title' => $activity->getTitle(),
            'field' => 'field_deal',
            'target_id' => $activity->get('field_deal')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete activity',
          ];
        }
      }
    }

    return $broken;
  }

  /**
   * Validate stage values are in correct format.
   */
  public function validateStageFormat() {
    $valid_stages = ['qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

    $deals_query = \Drupal::database()->select('node__field_stage', 'fs')
      ->fields('fs', ['entity_id', 'field_stage_value'])
      ->condition('fs.bundle', 'deal')
      ->execute();

    $invalid = [];
    foreach ($deals_query as $row) {
      if (!in_array($row->field_stage_value, $valid_stages)) {
        $invalid[] = [
          'deal_id' => $row->entity_id,
          'stage_value' => $row->field_stage_value,
          'valid_options' => implode(', ', $valid_stages),
        ];
      }
    }

    return $invalid;
  }

  /**
   * Verify dashboard statistics match actual entity counts.
   */
  public function verifySyncStatistics($user_id = NULL) {
    $issues = [];

    // Get actual deal counts
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'deal')
      ->accessCheck(FALSE);

    if ($user_id) {
      $query->condition('field_owner', $user_id);
    }

    $won_count = $query
      ->condition('field_stage', 'closed_won')
      ->count()
      ->execute();

    $lost_count = $query
      ->condition('field_stage', 'closed_lost')
      ->count()
      ->execute();

    // Store for comparison (dashboard should match)
    return [
      'actual_won' => $won_count,
      'actual_lost' => $lost_count,
      'total_closed' => $won_count + $lost_count,
    ];
  }

  /**
   * Log integrity issue.
   */
  public function logIssue($category, $entity_type, $entity_id, $issue_description) {
    $this->loggerFactory->get('crm_integrity')->warning(
      'Integrity issue: [@category] @type #@id - @description',
      [
        '@category' => $category,
        '@type' => $entity_type,
        '@id' => $entity_id,
        '@description' => $issue_description,
      ]
    );
  }
}
```

#### Step 2.2: Register Service

```yaml
# File: web/modules/custom/crm/crm.services.yml

services:
  crm.data_integrity:
    class: Drupal\crm\Service\DataIntegrityService
    arguments:
      - "@entity_type.manager"
      - "@logger.factory"
```

#### Step 2.3: Create Drush Commands

```php
// Add to DataIntegrityCommands.php

  /**
   * Find orphaned entities.
   *
   * @command crm:find-orphans
   * @aliases crm-orphans
   */
  public function findOrphans() {
    $service = \Drupal::service('crm.data_integrity');
    $orphans = $service->findOrphanedEntities();

    if (empty($orphans)) {
      $this->output()->writeln('✓ No orphaned entities found!');
      return;
    }

    foreach ($orphans as $category => $data) {
      $this->output()->writeln("\n<fg=red>[" . strtoupper($data['severity']) . "]</> {$category}");
      $this->output()->writeln($data['description']);
      $this->output()->writeln("Count: {$data['count']}");
      $this->output()->writeln("Action: {$data['action']}");

      // Show sample IDs
      $sample = array_slice($data['entity_ids'], 0, 5);
      $this->output()->writeln("Sample IDs: " . implode(', ', $sample));
      if (count($data['entity_ids']) > 5) {
        $this->output()->writeln("... and " . (count($data['entity_ids']) - 5) . " more");
      }
    }
  }

  /**
   * Find broken entity references.
   *
   * @command crm:find-broken-refs
   * @aliases crm-broken
   */
  public function findBrokenRefs() {
    $service = \Drupal::service('crm.data_integrity');
    $broken = $service->findBrokenReferences();

    if (empty($broken)) {
      $this->output()->writeln('✓ No broken references found!');
      return;
    }

    $this->output()->writeln("<fg=red>Found " . count($broken) . " broken references:</>\n");

    foreach ($broken as $ref) {
      $this->output()->writeln("  {$ref['type']} #{$ref['id']} ({$ref['title']})");
      $this->output()->writeln("    Field: {$ref['field']} → Target: {$ref['target_id']}");
      $this->output()->writeln("    Action: {$ref['action']}\n");
    }
  }
```

---

## Phase 3: Add Validation Hooks

### Step 3.1: Pre-Save Validation Hook

```php
// File: web/modules/custom/crm/crm.module

/**
 * Implements hook_node_presave().
 */
function crm_node_presave(NodeInterface $node) {
  $bundle = $node->bundle();

  // DEALS - Ensure owner is set
  if ($bundle === 'deal') {
    if ($node->get('field_owner')->isEmpty()) {
      // Auto-assign to current user if missing
      $current_user = \Drupal::currentUser();
      if ($current_user->id() > 0) {
        $node->get('field_owner')->setValue($current_user->id());
        \Drupal::logger('crm')->info(
          'Auto-assigned deal #@id to user @user',
          ['@id' => $node->id(), '@user' => $current_user->getDisplayName()]
        );
      }
    }

    // Validate stage format (must be string)
    if (!$node->get('field_stage')->isEmpty()) {
      $stage = $node->get('field_stage')->value;
      $valid_stages = ['qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];

      if (!in_array($stage, $valid_stages)) {
        throw new \InvalidArgumentException(
          sprintf('Invalid stage: %s. Must be one of: %s', $stage, implode(', ', $valid_stages))
        );
      }
    }

    // Validate amount >= 0
    if (!$node->get('field_amount')->isEmpty()) {
      $amount = floatval($node->get('field_amount')->value);
      if ($amount < 0) {
        throw new \InvalidArgumentException('Deal amount cannot be negative');
      }
    }

    // Validate at least contact OR organization
    $has_contact = !$node->get('field_contact')->isEmpty();
    $has_org = !$node->get('field_organization')->isEmpty();

    if (!$has_contact && !$has_org) {
      throw new \InvalidArgumentException('Deal must reference at least Contact or Organization');
    }
  }

  // ACTIVITIES - Ensure assigned user is set
  if ($bundle === 'activity') {
    if ($node->get('field_assigned_to')->isEmpty()) {
      // Auto-assign to current user if missing
      $current_user = \Drupal::currentUser();
      if ($current_user->id() > 0) {
        $node->get('field_assigned_to')->setValue($current_user->id());
      }
    }

    // Validate at least contact OR deal
    $has_contact = !$node->get('field_contact')->isEmpty();
    $has_deal = !$node->get('field_deal')->isEmpty();

    if (!$has_contact && !$has_deal) {
      throw new \InvalidArgumentException('Activity must reference Contact or Deal');
    }
  }

  // CONTACTS - Ensure required fields
  if ($bundle === 'contact') {
    if ($node->get('field_phone')->isEmpty()) {
      throw new \InvalidArgumentException('Phone number is required');
    }
  }
}
```

---

## Phase 4: Role-Based Access Control

### Step 4.1: Implement Node Access Hook

```php
// In crm.module (or dedicated access.module)

/**
 * Implements hook_node_access().
 */
function crm_node_access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  $bundle = $node->bundle();

  // Only apply to CRM entities
  if (!in_array($bundle, ['contact', 'deal', 'organization', 'activity'])) {
    return \Drupal\Core\Access\AccessResult::neutral();
  }

  // Admin can do everything
  if ($account->hasRole('administrator')) {
    return \Drupal\Core\Access\AccessResult::allowed()->addCacheContexts(['user.roles']);
  }

  // View operation
  if ($op === 'view') {
    // Owner can always view their own
    if (_crm_is_owner($node, $account)) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    // Manager can view team member data
    if ($account->hasRole('sales_manager')) {
      if (_crm_is_team_member_data($node, $account)) {
        return \Drupal\Core\Access\AccessResult::allowed();
      }
    }

    // Default deny
    return \Drupal\Core\Access\AccessResult::forbidden();
  }

  // Edit operation
  if ($op === 'update') {
    // Owner can edit own
    if ($account->hasRole('sales_rep') && _crm_is_owner($node, $account)) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    // Manager can edit team data
    if ($account->hasRole('sales_manager') && _crm_is_team_member_data($node, $account)) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    return \Drupal\Core\Access\AccessResult::forbidden();
  }

  return \Drupal\Core\Access\AccessResult::neutral();
}

/**
 * Check if user is the owner of the entity.
 */
function _crm_is_owner(\Drupal\node\NodeInterface $node, $account) {
  if (!$node->hasField('field_owner')) {
    return FALSE;
  }

  $owner = $node->get('field_owner')->entity;
  return $owner && $owner->id() == $account->id();
}

/**
 * Check if entity belongs to user's team members.
 */
function _crm_is_team_member_data(\Drupal\node\NodeInterface $node, $account) {
  if (!$node->hasField('field_owner')) {
    return FALSE;
  }

  // Get team members for this manager
  $team_members = \Drupal::service('crm.teams')
    ->getTeamMembers($account->id());

  if (empty($team_members)) {
    return FALSE;
  }

  $owner = $node->get('field_owner')->entity;
  return $owner && in_array($owner->id(), $team_members);
}
```

---

## Testing Checklist

- [ ] Run stage normalization script
- [ ] Verify all deals have string stage values
- [ ] Find orphan entities using Drush command
- [ ] Identify and manually fix orphaned deals
- [ ] Confirm validation rejects NULL owners
- [ ] Test dashboard stats match query counts
- [ ] Verify access control works for each role
- [ ] Test that sales rep can't see competitor data

---

## Rollback Plan

If issues occur:

```bash
# Revert stage format (create reverse migration)
drush cr

# Reload from backup if data corrupted
drush sql:sync @production @local

# Disable module if fatal error
drush pm:uninstall crm_integrity
```

---

**Status:** Ready for implementation  
**Estimated Time:** 8-10 hours developer time  
**Risk Level:** Medium (database modifications - backup first)
