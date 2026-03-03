#!/usr/bin/env php
<?php

/**
 * @file
 * Data Cleanup Utilities
 * 
 * Provides utilities to clean and maintain CRM data:
 * - Remove duplicate contacts
 * - Archive old activities
 * - Clean orphaned references
 * - Reset sample data
 * 
 * Usage:
 *   # Check for duplicates
 *   ddev drush scr scripts/cleanup_data.php check_duplicates
 *   
 *   # Remove duplicates
 *   ddev drush scr scripts/cleanup_data.php remove_duplicates
 *   
 *   # Archive old activities
 *   ddev drush scr scripts/cleanup_data.php archive_old days=180
 *   
 *   # Clean orphaned references
 *   ddev drush scr scripts/cleanup_data.php clean_orphans
 *   
 *   # Reset all sample data
 *   ddev drush scr scripts/cleanup_data.php reset_samples
 */

use Drupal\node\Entity\Node;

echo "🧹 DATA CLEANUP UTILITIES\n";
echo "════════════════════════════════════════════════════════\n\n";

$operation = $args[0] ?? 'help';
$params = isset($args) ? array_slice($args, 1) : [];

// Parse parameters
$options = [];
foreach ($params as $param) {
  if (strpos($param, '=') !== FALSE) {
    list($key, $value) = explode('=', $param, 2);
    $options[$key] = $value;
  }
}

// ============================================================
// OPERATION: Check Duplicates
// ============================================================

if ($operation === 'check_duplicates') {
  echo "🔍 CHECKING FOR DUPLICATE CONTACTS\n";
  echo "────────────────────────────────────────────────────────\n";
  
  // Find duplicates by email
  $query = \Drupal::database()->select('node__field_email', 'e');
  $query->addField('e', 'field_email_value', 'email');
  $query->addExpression('COUNT(*)', 'count');
  $query->addExpression('GROUP_CONCAT(entity_id)', 'nids');
  $query->condition('bundle', 'contact');
  $query->isNotNull('field_email_value');
  $query->groupBy('field_email_value');
  $query->having('COUNT(*) > 1');
  
  $duplicates = $query->execute()->fetchAll();
  
  if (empty($duplicates)) {
    echo "✅ No duplicate contacts found\n";
  } else {
    echo "⚠️  Found " . count($duplicates) . " duplicate emails:\n\n";
    
    foreach ($duplicates as $dup) {
      echo "  Email: {$dup->email}\n";
      echo "  Count: {$dup->count}\n";
      echo "  NIDs: {$dup->nids}\n";
      
      // Load nodes to show details
      $nids = explode(',', $dup->nids);
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        if ($node) {
          $created = date('Y-m-d H:i', $node->getCreatedTime());
          echo "    → NID $nid: {$node->getTitle()} (created: $created)\n";
        }
      }
      echo "\n";
    }
    
    echo "💡 To remove duplicates: ddev drush scr scripts/cleanup_data.php remove_duplicates\n";
  }
  
  exit(0);
}

// ============================================================
// OPERATION: Remove Duplicates
// ============================================================

if ($operation === 'remove_duplicates') {
  echo "🗑️  REMOVING DUPLICATE CONTACTS\n";
  echo "────────────────────────────────────────────────────────\n";
  
  $dry_run = !isset($options['confirm']) || $options['confirm'] !== 'yes';
  
  if ($dry_run) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n";
    echo "   To actually delete: add confirm=yes\n\n";
  }
  
  // Find duplicates
  $query = \Drupal::database()->select('node__field_email', 'e');
  $query->addField('e', 'field_email_value', 'email');
  $query->addExpression('GROUP_CONCAT(entity_id ORDER BY entity_id)', 'nids');
  $query->condition('bundle', 'contact');
  $query->isNotNull('field_email_value');
  $query->groupBy('field_email_value');
  $query->having('COUNT(*) > 1');
  
  $duplicates = $query->execute()->fetchAll();
  
  $deleted_count = 0;
  
  foreach ($duplicates as $dup) {
    $nids = explode(',', $dup->nids);
    
    // Keep first (oldest), delete others
    $keep_nid = array_shift($nids);
    
    echo "  Email: {$dup->email}\n";
    echo "  Keeping: NID $keep_nid\n";
    echo "  Deleting: " . implode(', ', $nids) . "\n";
    
    if (!$dry_run) {
      foreach ($nids as $nid) {
        $node = Node::load($nid);
        if ($node) {
          $node->delete();
          $deleted_count++;
        }
      }
    }
    
    echo "\n";
  }
  
  if ($dry_run) {
    echo "📊 Would delete: " . count($nids) . " duplicate contacts\n";
  } else {
    echo "✅ Deleted: $deleted_count duplicate contacts\n";
  }
  
  exit(0);
}

// ============================================================
// OPERATION: Archive Old Activities
// ============================================================

if ($operation === 'archive_old') {
  echo "📦 ARCHIVING OLD ACTIVITIES\n";
  echo "────────────────────────────────────────────────────────\n";
  
  $days = $options['days'] ?? 180;
  $dry_run = !isset($options['confirm']) || $options['confirm'] !== 'yes';
  
  if ($dry_run) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n";
    echo "   To actually archive: add confirm=yes\n\n";
  }
  
  echo "Archiving activities older than $days days\n\n";
  
  $cutoff_date = strtotime("-$days days");
  
  // Find old activities
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition('created', $cutoff_date, '<')
    ->condition('status', 1)
    ->accessCheck(FALSE);
  
  $nids = $query->execute();
  
  if (empty($nids)) {
    echo "✅ No activities to archive\n";
    exit(0);
  }
  
  echo "Found " . count($nids) . " old activities\n\n";
  
  if (!$dry_run) {
    $nodes = Node::loadMultiple($nids);
    foreach ($nodes as $node) {
      $node->setUnpublished();
      $node->save();
    }
    
    echo "✅ Archived " . count($nids) . " activities\n";
    echo "   Activities are now unpublished but not deleted\n";
  } else {
    echo "📊 Would archive: " . count($nids) . " activities\n";
  }
  
  exit(0);
}

// ============================================================
// OPERATION: Clean Orphaned References
// ============================================================

if ($operation === 'clean_orphans') {
  echo "🔗 CLEANING ORPHANED REFERENCES\n";
  echo "────────────────────────────────────────────────────────\n";
  
  $dry_run = !isset($options['confirm']) || $options['confirm'] !== 'yes';
  
  if ($dry_run) {
    echo "⚠️  DRY RUN MODE - No changes will be made\n";
    echo "   To actually clean: add confirm=yes\n\n";
  }
  
  $issues_found = 0;
  $issues_fixed = 0;
  
  // Check Contacts with deleted Organizations
  echo "Checking Contacts with deleted Organizations...\n";
  
  $query = \Drupal::database()->query("
    SELECT c.entity_id, c.field_organization_target_id
    FROM node__field_organization c
    LEFT JOIN node_field_data n ON c.field_organization_target_id = n.nid
    WHERE c.bundle = 'contact'
      AND c.field_organization_target_id IS NOT NULL
      AND n.nid IS NULL
  ");
  
  $orphans = $query->fetchAll();
  
  if (!empty($orphans)) {
    $issues_found += count($orphans);
    echo "  ⚠️  Found " . count($orphans) . " contacts with deleted organizations\n";
    
    if (!$dry_run) {
      foreach ($orphans as $orphan) {
        $node = Node::load($orphan->entity_id);
        if ($node) {
          $node->set('field_organization', NULL);
          $node->save();
          $issues_fixed++;
        }
      }
      echo "  ✅ Fixed " . $issues_fixed . " orphaned organization references\n";
    }
  } else {
    echo "  ✅ No orphaned organization references\n";
  }
  
  // Check Deals with deleted Contacts
  echo "\nChecking Deals with deleted Contacts...\n";
  
  $query = \Drupal::database()->query("
    SELECT d.entity_id, d.field_contact_target_id
    FROM node__field_contact d
    LEFT JOIN node_field_data n ON d.field_contact_target_id = n.nid
    WHERE d.bundle = 'deal'
      AND d.field_contact_target_id IS NOT NULL
      AND n.nid IS NULL
  ");
  
  $orphans = $query->fetchAll();
  
  if (!empty($orphans)) {
    $issues_found += count($orphans);
    echo "  ⚠️  Found " . count($orphans) . " deals with deleted contacts\n";
    
    if (!$dry_run) {
      foreach ($orphans as $orphan) {
        $node = Node::load($orphan->entity_id);
        if ($node) {
          $node->set('field_contact', NULL);
          $node->save();
          $issues_fixed++;
        }
      }
      echo "  ✅ Fixed orphaned contact references\n";
    }
  } else {
    echo "  ✅ No orphaned contact references\n";
  }
  
  // Check Activities with deleted entities
  echo "\nChecking Activities with deleted references...\n";
  
  $query = \Drupal::database()->query("
    SELECT a.entity_id, a.field_contact_target_id
    FROM node__field_contact a
    LEFT JOIN node_field_data n ON a.field_contact_target_id = n.nid
    WHERE a.bundle = 'activity'
      AND a.field_contact_target_id IS NOT NULL
      AND n.nid IS NULL
  ");
  
  $orphans = $query->fetchAll();
  
  if (!empty($orphans)) {
    $issues_found += count($orphans);
    echo "  ⚠️  Found " . count($orphans) . " activities with deleted contacts\n";
    
    if (!$dry_run) {
      foreach ($orphans as $orphan) {
        $node = Node::load($orphan->entity_id);
        if ($node) {
          $node->set('field_contact', NULL);
          $node->save();
          $issues_fixed++;
        }
      }
    }
  } else {
    echo "  ✅ No orphaned activity references\n";
  }
  
  echo "\n";
  
  if ($dry_run && $issues_found > 0) {
    echo "📊 Would fix: $issues_found orphaned references\n";
  } elseif (!$dry_run && $issues_fixed > 0) {
    echo "✅ Fixed: $issues_fixed orphaned references\n";
  } else {
    echo "✅ No orphaned references found\n";
  }
  
  exit(0);
}

// ============================================================
// OPERATION: Reset Sample Data
// ============================================================

if ($operation === 'reset_samples') {
  echo "🔄 RESETTING SAMPLE DATA\n";
  echo "────────────────────────────────────────────────────────\n";
  
  $confirm = $options['confirm'] ?? '';
  
  if ($confirm !== 'DELETE_ALL_SAMPLES') {
    echo "⚠️  WARNING: This will DELETE ALL sample data!\n\n";
    echo "To confirm, run:\n";
    echo "  ddev drush scr scripts/cleanup_data.php reset_samples confirm=DELETE_ALL_SAMPLES\n";
    exit(1);
  }
  
  echo "Deleting all sample nodes...\n\n";
  
  $types = ['activity', 'deal', 'contact', 'organization'];
  $total_deleted = 0;
  
  foreach ($types as $type) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->accessCheck(FALSE);
    $nids = $query->execute();
    
    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $node->delete();
      }
      $count = count($nids);
      $total_deleted += $count;
      echo "  🗑️  Deleted $count $type nodes\n";
    }
  }
  
  echo "\n✅ Deleted $total_deleted nodes total\n\n";
  
  echo "💡 To reload fixtures:\n";
  echo "  ddev drush scr scripts/load_fixtures.php development\n";
  
  exit(0);
}

// ============================================================
// OPERATION: Help
// ============================================================

if ($operation === 'help' || !in_array($operation, ['check_duplicates', 'remove_duplicates', 'archive_old', 'clean_orphans', 'reset_samples'])) {
  echo "📖 AVAILABLE OPERATIONS:\n";
  echo "────────────────────────────────────────────────────────\n\n";
  
  echo "check_duplicates\n";
  echo "  Check for duplicate contacts by email\n";
  echo "  Usage: ddev drush scr scripts/cleanup_data.php check_duplicates\n\n";
  
  echo "remove_duplicates\n";
  echo "  Remove duplicate contacts (keeps oldest)\n";
  echo "  Usage: ddev drush scr scripts/cleanup_data.php remove_duplicates confirm=yes\n\n";
  
  echo "archive_old\n";
  echo "  Archive activities older than X days\n";
  echo "  Usage: ddev drush scr scripts/cleanup_data.php archive_old days=180 confirm=yes\n\n";
  
  echo "clean_orphans\n";
  echo "  Clean orphaned entity references\n";
  echo "  Usage: ddev drush scr scripts/cleanup_data.php clean_orphans confirm=yes\n\n";
  
  echo "reset_samples\n";
  echo "  Delete ALL sample data (requires confirmation)\n";
  echo "  Usage: ddev drush scr scripts/cleanup_data.php reset_samples confirm=DELETE_ALL_SAMPLES\n\n";
  
  exit(0);
}
