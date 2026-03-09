<?php

/**
 * @file
 * Migration script to normalize stage format from numeric IDs to string values.
 * 
 * This script converts existing numeric stage IDs (0-6) to proper string values:
 * - 0,1 -> qualified
 * - 2 -> proposal
 * - 3,4 -> negotiation
 * - 5 -> closed_won
 * - 6 -> closed_lost
 * 
 * Usage: 
 *   php -d memory_limit=-1 /path/to/migrate_stages.php
 */

// Drupal bootstrap.
define('DRUPAL_ROOT', dirname(__FILE__) . '/../../..');
require_once DRUPAL_ROOT . '/core/includes/bootstrap.inc';

// Initialize Drupal.
\Drupal\Core\DrupalKernel::bootEnvironment();
$kernel = new \Drupal\Core\DrupalKernel(
  'prod',
  \Drupal\Core\File\FileSystemInterface::getOsTemporaryDirectory()
);
\Drupal::setContainer($kernel->getContainer());
$kernel->boot();

// Stage mapping.
$stage_mapping = [
  '0' => 'qualified',
  '1' => 'qualified',
  '2' => 'proposal',
  '3' => 'negotiation',
  '4' => 'negotiation',
  '5' => 'closed_won',
  '6' => 'closed_lost',
];

echo "Starting stage format migration...\n";
echo "=====================================\n\n";

// Get all deals.
$entity_type_manager = \Drupal::entityTypeManager();
$deals = $entity_type_manager->getStorage('node')->loadByProperties(['type' => 'deal']);

if (empty($deals)) {
  echo "✓ No deals found to migrate.\n";
  exit(0);
}

$count = 0;
$errors = [];
$unchanged = 0;

foreach ($deals as $deal) {
  $stage = $deal->get('field_stage')->value;

  // Skip if empty.
  if (empty($stage)) {
    $unchanged++;
    continue;
  }

  // Skip if already string value.
  if (array_search($stage, $stage_mapping) === FALSE && in_array($stage, $stage_mapping)) {
    $unchanged++;
    continue;
  }

  // Convert numeric to string.
  if (isset($stage_mapping[$stage])) {
    try {
      $deal->set('field_stage', $stage_mapping[$stage]);
      $deal->save();
      $count++;
      printf(
        "[%d] Deal '%s' (ID: %d): %s → %s\n",
        $count,
        $deal->getTitle(),
        $deal->id(),
        $stage,
        $stage_mapping[$stage]
      );
    }
    catch (\Exception $e) {
      $errors[] = "Deal #{$deal->id()}: {$e->getMessage()}";
      printf("[ERROR] Deal '%s' (ID: %d): %s\n", $deal->getTitle(), $deal->id(), $e->getMessage());
    }
  }
  else {
    // Unknown stage value.
    $errors[] = "Deal #{$deal->id()}: Unknown stage value '{$stage}'";
    printf("[WARN] Deal '%s' (ID: %d): Unknown stage value '%s'\n", $deal->getTitle(), $deal->id(), $stage);
  }
}

// Summary.
echo "\n=====================================\n";
echo "Migration Summary:\n";
echo "  Total deals processed: " . count($deals) . "\n";
echo "  Successfully migrated: $count\n";
echo "  Unchanged (already correct): $unchanged\n";
echo "  Errors: " . count($errors) . "\n";

if (!empty($errors)) {
  echo "\nErrors encountered:\n";
  foreach ($errors as $error) {
    echo "  - $error\n";
  }
}

echo "\n✓ Stage format migration completed.\n";
exit(0);
