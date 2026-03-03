#!/usr/bin/env php
<?php

/**
 * @file
 * Bulk Operations Queue Manager
 * 
 * Implements Drupal Queue API for bulk operations:
 * - CSV import queue
 * - Bulk delete queue
 * - Bulk update queue
 * - Email notification queue
 * 
 * Usage:
 *   # Process specific queue
 *   ddev drush scr scripts/process_queue.php csv_import
 *   ddev drush scr scripts/process_queue.php bulk_delete
 *   
 *   # Process all queues
 *   ddev drush scr scripts/process_queue.php all
 *   
 *   # Check queue status
 *   ddev drush scr scripts/process_queue.php status
 */

use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

echo "⚙️  BULK OPERATIONS QUEUE MANAGER\n";
echo "════════════════════════════════════════════════════════\n\n";

$queue_factory = \Drupal::service('queue');
$queue_manager = \Drupal::service('plugin.manager.queue_worker');

// Define available queues
$available_queues = [
  'crm_csv_import' => 'CSV Import Queue',
  'crm_bulk_delete' => 'Bulk Delete Queue',
  'crm_bulk_update' => 'Bulk Update Queue',
  'crm_email_notification' => 'Email Notification Queue',
];

$operation = $args[0] ?? 'status';

// Handle status command
if ($operation === 'status') {
  echo "📊 QUEUE STATUS\n";
  echo "────────────────────────────────────────────────────────\n";
  
  foreach ($available_queues as $queue_name => $queue_label) {
    $queue = $queue_factory->get($queue_name);
    $count = $queue->numberOfItems();
    
    $status_icon = $count > 0 ? '⚠️' : '✅';
    echo "$status_icon $queue_label: $count items\n";
  }
  
  echo "\n💡 Commands:\n";
  echo "  Process queue: ddev drush scr scripts/process_queue.php <queue_name>\n";
  echo "  Process all: ddev drush scr scripts/process_queue.php all\n";
  echo "\n";
  exit(0);
}

// Handle process command
$queues_to_process = [];

if ($operation === 'all') {
  $queues_to_process = array_keys($available_queues);
} elseif (isset($available_queues[$operation])) {
  $queues_to_process = [$operation];
} else {
  echo "❌ Unknown operation: $operation\n";
  echo "   Available: " . implode(', ', array_keys($available_queues)) . ", all, status\n";
  exit(1);
}

// Process queues
foreach ($queues_to_process as $queue_name) {
  $queue_label = $available_queues[$queue_name];
  $queue = $queue_factory->get($queue_name);
  $count = $queue->numberOfItems();
  
  echo "🔄 Processing $queue_label ($queue_name)\n";
  echo "   Items in queue: $count\n";
  
  if ($count === 0) {
    echo "   ✅ Queue is empty\n\n";
    continue;
  }
  
  // Get queue worker plugin
  try {
    $queue_worker = $queue_manager->createInstance($queue_name);
  } catch (Exception $e) {
    echo "   ⚠️  No worker plugin found: " . $e->getMessage() . "\n\n";
    continue;
  }
  
  // Process items
  $processed = 0;
  $failed = 0;
  $start_time = microtime(true);
  
  while ($item = $queue->claimItem()) {
    try {
      $queue_worker->processItem($item->data);
      $queue->deleteItem($item);
      $processed++;
      echo "   ✅ Processed item $processed\n";
    } catch (Exception $e) {
      $failed++;
      echo "   ❌ Failed: " . $e->getMessage() . "\n";
      // Release item back to queue
      $queue->releaseItem($item);
    }
  }
  
  $elapsed = round(microtime(true) - $start_time, 2);
  
  echo "   📊 Summary:\n";
  echo "      Processed: $processed\n";
  echo "      Failed: $failed\n";
  echo "      Time: {$elapsed}s\n";
  echo "\n";
}

echo "═══════════════════════════════════════════════════════\n";
echo "✅ QUEUE PROCESSING COMPLETE\n";
echo "═══════════════════════════════════════════════════════\n";
