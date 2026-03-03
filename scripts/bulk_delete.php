#!/usr/bin/env php
<?php

/**
 * @file
 * Bulk Delete Operations
 * 
 * Safely delete nodes in bulk using queue system.
 * Prevents timeout on large deletions.
 * 
 * Usage:
 *   # Delete all sample data (tagged nodes)
 *   ddev drush scr scripts/bulk_delete.php sample
 *   
 *   # Delete nodes by type
 *   ddev drush scr scripts/bulk_delete.php activity
 *   ddev drush scr scripts/bulk_delete.php contact
 *   
 *   # Delete nodes older than X days
 *   ddev drush scr scripts/bulk_delete.php older_than 90
 */

use Drupal\node\Entity\Node;

echo "🗑️  BULK DELETE OPERATIONS\n";
echo "════════════════════════════════════════════════════════\n\n";

$operation = $args[0] ?? null;
$param = $args[1] ?? null;

if (!$operation) {
  echo "❌ Usage: ddev drush scr scripts/bulk_delete.php <operation> [parameter]\n";
  echo "\nOperations:\n";
  echo "  sample              - Delete all sample/development data\n";
  echo "  activity|contact    - Delete all nodes of specific type\n";  echo "  older_than <days>   - Delete nodes older than X days\n";
  exit(1);
}

$queue = \Drupal::service('queue')->get('crm_bulk_delete');
$nids = [];

// Determine which nodes to delete
switch ($operation) {
  case 'sample':
    echo "🔍 Finding sample/development data...\n";
    // Sample data typically has NIDs > 48 (after initial setup)
    // In production, use a better tagging mechanism
    $query = \Drupal::entityQuery('node')
      ->condition('nid', 48, '>')
      ->accessCheck(FALSE);
    $nids = $query->execute();
    echo "   Found " . count($nids) . " sample nodes\n";
    break;
    
  case 'activity':
  case 'contact':
  case 'deal':
  case 'organization':
    echo "🔍 Finding $operation nodes...\n";
    $query = \Drupal::entityQuery('node')
      ->condition('type', $operation)
      ->accessCheck(FALSE);
    $nids = $query->execute();
    echo "   Found " . count($nids) . " $operation nodes\n";
    break;
    
  case 'older_than':
    if (!$param || !is_numeric($param)) {
      echo "❌ Please specify number of days\n";
      exit(1);
    }
    
    $days_ago = strtotime("-$param days");
    echo "🔍 Finding nodes older than $param days...\n";
    
    $query = \Drupal::entityQuery('node')
      ->condition('created', $days_ago, '<')
      ->accessCheck(FALSE);
    $nids = $query->execute();
    echo "   Found " . count($nids) . " old nodes\n";
    break;
    
  default:
    echo "❌ Unknown operation: $operation\n";
    exit(1);
}

if (empty($nids)) {
  echo "✅ No nodes to delete\n";
  exit(0);
}

// Add to queue
echo "\n📤 Adding " . count($nids) . " items to delete queue...\n";

$batch_size = 50;
$batches = array_chunk($nids, $batch_size);

foreach ($batches as $batch_index => $batch_nids) {
  $queue->createItem([
    'operation' => 'delete_nodes',
    'nids' => $batch_nids,
    'batch' => $batch_index + 1,
    'total_batches' => count($batches),
  ]);
}

echo "✅ Added " . count($batches) . " batches to queue\n";echo "   Batch size: $batch_size nodes per batch\n";
echo "\n";
echo "💡 Process the queue:\n";
echo "   ddev drush scr scripts/process_queue.php crm_bulk_delete\n";
echo "\n";
