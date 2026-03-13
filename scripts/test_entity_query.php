<?php

/**
 * Test entity queries with field_deleted_at
 */

echo "Testing entity queries...\n\n";

// Test 1: Simple node query WITHOUT field condition
echo "[1/3] Simple node query without field condition...\n";
try {
  $result = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'contact')
    ->range(0, 5)
    ->execute();
  echo "✅ Found " . count($result) . " contacts\n";
} catch (\Exception $e) {
  echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Query WITH field_deleted_at condition
echo "\n[2/3] Entity query WITH field_deleted_at condition...\n";
try {
  $result = \Drupal::entityQuery('node')
    ->accessCheck(FALSE)
    ->condition('type', 'contact')
    ->condition('field_deleted_at', NULL, 'IS NULL')  // This is what the module does
    ->range(0, 5)
    ->execute();
  echo "✅ Found " . count($result) . " active contacts\n";
} catch (\Exception $e) {
  echo "❌ Error: " . substr($e->getMessage(), 0, 100) . "...\n";
}

// Test 3: Attempt to disable module to see if queries work
echo "\n[3/3] Module status check...\n";
$modules = \Drupal::moduleHandler()->getModuleList();
if (isset($modules['crm_data_quality'])) {
  echo "✅ crm_data_quality module is enabled\n";
} else {
  echo "⚠️  crm_data_quality module is NOT enabled\n";
}
