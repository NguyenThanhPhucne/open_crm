<?php

/**
 * @file
 * Enable content_access processor for Search API indexes.
 * 
 * Usage: ddev drush scr scripts/production/enable_search_access.php
 */

use Drupal\Core\Config\ConfigFactoryInterface;

// Index machine names
$indexes = [
  'crm_contacts_index',
  'crm_deals_index',
  'crm_organizations_index',
];

/** @var ConfigFactoryInterface $config_factory */
$config_factory = \Drupal::service('config.factory');

$fixed_count = 0;
$error_count = 0;

echo "\n================================================\n";
echo "ENABLE SEARCH API ACCESS CONTROL\n";
echo "================================================\n\n";

foreach ($indexes as $index_id) {
  echo "Processing: $index_id\n";
  echo str_repeat('-', 60) . "\n";
  
  try {
    $config = $config_factory->getEditable("search_api.index.$index_id");
    
    if (!$config->get('id')) {
      echo "❌ Index not found: $index_id\n\n";
      $error_count++;
      continue;
    }
    
    $processor_settings = $config->get('processor_settings') ?: [];
    
    // Check if content_access already enabled
    if (isset($processor_settings['content_access'])) {
      echo "ℹ️  content_access already enabled\n";
      
      // Check weights
      if (isset($processor_settings['content_access']['weights']['preprocess_query'])) {
        $weight = $processor_settings['content_access']['weights']['preprocess_query'];
        echo "   Current weight: $weight\n";
        
        if ($weight == -30) {
          echo "✅ Already configured correctly\n\n";
          continue;
        }
      }
    }
    
    // Enable content_access processor
    $processor_settings['content_access'] = [
      'weights' => [
        'preprocess_query' => -30,
      ],
    ];
    
    $config->set('processor_settings', $processor_settings);
    $config->save();
    
    echo "✅ Successfully enabled content_access\n";
    echo "   Weight: -30 (runs early in query processing)\n\n";
    
    $fixed_count++;
    
  } catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    $error_count++;
  }
}

echo "================================================\n";
echo "REINDEX ALL CRM INDEXES\n";
echo "================================================\n\n";

echo "Clearing Search API index items...\n";
foreach ($indexes as $index_id) {
  try {
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load($index_id);
    
    if ($index) {
      $index->clear();
      echo "✅ Cleared: $index_id\n";
    }
  } catch (\Exception $e) {
    echo "❌ Failed to clear: $index_id - " . $e->getMessage() . "\n";
  }
}

echo "\nQueuing items for reindexing...\n";
foreach ($indexes as $index_id) {
  try {
    $index = \Drupal::entityTypeManager()
      ->getStorage('search_api_index')
      ->load($index_id);
    
    if ($index) {
      $index->reindex();
      echo "✅ Queued: $index_id\n";
    }
  } catch (\Exception $e) {
    echo "❌ Failed to queue: $index_id - " . $e->getMessage() . "\n";
  }
}

echo "\n================================================\n";
echo "SUMMARY\n";
echo "================================================\n\n";

echo "✅ Fixed: $fixed_count indexes\n";
echo "❌ Errors: $error_count indexes\n\n";

if ($fixed_count > 0) {
  echo "Next steps:\n";
  echo "1. Run: ddev drush search-api:index\n";
  echo "2. Test: Login as sales_rep and search\n";
  echo "3. Verify: Should only see own records\n";
}

echo "\n✨ Done!\n\n";

// Return exit code
if ($error_count > 0) {
  exit(1);
} else {
  exit(0);
}
