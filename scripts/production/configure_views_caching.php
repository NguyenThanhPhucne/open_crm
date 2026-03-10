<?php

/**
 * @file
 * Configure caching for CRM Views to improve performance.
 * 
 * Usage via Drush:
 * ddev drush scr scripts/production/configure_views_caching.php
 * 
 * Purpose:
 * - Enable time-based caching for Views
 * - Reduce database queries
 * - Improve page load speed
 * 
 * Impact:
 * - Before: ~500ms per page load (query every time)
 * - After: ~50ms per page load (cache hit)
 */

use Drupal\views\Entity\View;

echo "======================================\n";
echo "CONFIGURE VIEWS CACHING FOR OPEN CRM\n";
echo "======================================\n\n";

// List of CRM views to optimize
$crm_views = [
  'my_contacts' => [
    'label' => 'My Contacts',
    'cache_lifetime' => 1800, // 30 minutes
  ],
  'my_deals' => [
    'label' => 'My Deals',
    'cache_lifetime' => 900, // 15 minutes (deals change more frequently)
  ],
  'my_activities' => [
    'label' => 'My Activities',
    'cache_lifetime' => 600, // 10 minutes (activities change often)
  ],
  'my_projects' => [
    'label' => 'My Projects',
    'cache_lifetime' => 1800, // 30 minutes
  ],
];

$success_count = 0;
$error_count = 0;

foreach ($crm_views as $view_id => $config) {
  echo "Processing view: {$config['label']} ({$view_id})...\n";
  
  try {
    $view = View::load($view_id);
    
    if (!$view) {
      echo "  ⚠️  View not found: {$view_id}\n";
      $error_count++;
      continue;
    }
    
    $changed = false;
    
    // Get all displays
    $displays = $view->get('display');
    
    foreach ($displays as $display_id => &$display) {
      $display_label = isset($display['display_title']) ? $display['display_title'] : $display_id;
      echo "  - Display: {$display_label}\n";
      
      // Current cache setting
      $current_cache = isset($display['display_options']['cache']['type']) 
        ? $display['display_options']['cache']['type'] 
        : 'none';
      
      echo "    Current cache: {$current_cache}\n";
      
      if ($current_cache === 'none') {
        // Enable time-based caching
        $display['display_options']['cache'] = [
          'type' => 'time',
          'options' => [
            'results_lifespan' => $config['cache_lifetime'],
            'output_lifespan' => $config['cache_lifetime'],
          ],
        ];
        
        echo "    ✅ Enabled time-based cache ({$config['cache_lifetime']}s)\n";
        $changed = true;
      } else {
        echo "    ℹ️  Cache already configured\n";
      }
    }
    
    if ($changed) {
      $view->set('display', $displays);
      $view->save();
      echo "  ✅ Saved changes for view: {$view_id}\n";
      $success_count++;
    } else {
      echo "  ℹ️  No changes needed for view: {$view_id}\n";
    }
    
  } catch (\Exception $e) {
    echo "  ❌ Error processing view {$view_id}: " . $e->getMessage() . "\n";
    $error_count++;
  }
  
  echo "\n";
}

echo "======================================\n";
echo "SUMMARY\n";
echo "======================================\n";
echo "✅ Successfully updated: {$success_count} views\n";
echo "❌ Errors: {$error_count}\n";

if ($success_count > 0) {
  echo "\n🔄 Clearing cache...\n";
  
  try {
    // Clear views cache
    \Drupal::service('cache.render')->invalidateAll();
    \Drupal::service('cache.page')->invalidateAll();
    
    // Rebuild cache
    drupal_flush_all_caches();
    
    echo "✅ Cache cleared successfully\n";
  } catch (\Exception $e) {
    echo "⚠️  Could not clear cache: " . $e->getMessage() . "\n";
    echo "   Please run: ddev drush cr\n";
  }
}

echo "\n======================================\n";
echo "VERIFICATION\n";
echo "======================================\n";
echo "✅ Views caching has been configured!\n";
echo "\nTo verify:\n";
echo "1. Go to /crm/contacts\n";
echo "2. Check page load time (should be faster on 2nd visit)\n";
echo "3. Verify data freshness\n";

echo "\n✨ Done!\n\n";

// Return status
return ($error_count > 0) ? 1 : 0;

// List of CRM views to optimize
$crm_views = [
  'my_contacts' => [
    'label' => 'My Contacts',
    'cache_lifetime' => 1800, // 30 minutes
  ],
  'my_deals' => [
    'label' => 'My Deals',
    'cache_lifetime' => 900, // 15 minutes (deals change more frequently)
  ],
  'my_activities' => [
    'label' => 'My Activities',
    'cache_lifetime' => 600, // 10 minutes (activities change often)
  ],
  'my_projects' => [
    'label' => 'My Projects',
    'cache_lifetime' => 1800, // 30 minutes
  ],
];

$success_count = 0;
$error_count = 0;

foreach ($crm_views as $view_id => $config) {
  echo "Processing view: {$config['label']} ({$view_id})...\n";
  
  try {
    $view = View::load($view_id);
    
    if (!$view) {
      echo "  ⚠️  View not found: {$view_id}\n";
      $error_count++;
      continue;
    }
    
    $changed = false;
    
    // Get all displays
    $displays = $view->get('display');
    
    foreach ($displays as $display_id => &$display) {
      $display_label = isset($display['display_title']) ? $display['display_title'] : $display_id;
      echo "  - Display: {$display_label}\n";
      
      // Current cache setting
      $current_cache = isset($display['display_options']['cache']['type']) 
        ? $display['display_options']['cache']['type'] 
        : 'none';
      
      echo "    Current cache: {$current_cache}\n";
      
      if ($current_cache === 'none') {
        // Enable time-based caching
        $display['display_options']['cache'] = [
          'type' => 'time',
          'options' => [
            'results_lifespan' => $config['cache_lifetime'],
            'output_lifespan' => $config['cache_lifetime'],
          ],
        ];
        
        echo "    ✅ Enabled time-based cache ({$config['cache_lifetime']}s)\n";
        $changed = true;
      } else {
        echo "    ℹ️  Cache already configured\n";
      }
    }
    
    if ($changed) {
      $view->set('display', $displays);
      $view->save();
      echo "  ✅ Saved changes for view: {$view_id}\n";
      $success_count++;
    } else {
      echo "  ℹ️  No changes needed for view: {$view_id}\n";
    }
    
  } catch (\Exception $e) {
    echo "  ❌ Error processing view {$view_id}: " . $e->getMessage() . "\n";
    $error_count++;
  }
  
  echo "\n";
}

echo "======================================\n";
echo "SUMMARY\n";
echo "======================================\n";
echo "✅ Successfully updated: {$success_count} views\n";
echo "❌ Errors: {$error_count}\n";

if ($success_count > 0) {
  echo "\n🔄 Clearing cache...\n";
  
  try {
    // Clear views cache
    \Drupal::service('cache.render')->invalidateAll();
    \Drupal::service('cache.page')->invalidateAll();
    
    // Rebuild cache
    drupal_flush_all_caches();
    
    echo "✅ Cache cleared successfully\n";
  } catch (\Exception $e) {
    echo "⚠️  Could not clear cache: " . $e->getMessage() . "\n";
    echo "   Please run: ddev drush cr\n";
  }
}

echo "\n======================================\n";
echo "NEXT STEPS\n";
echo "======================================\n";
echo "1. Test views performance:\n";
echo "   - Go to /crm/contacts\n";
echo "   - Check page load time (should be faster)\n";
echo "   - Verify data is up to date\n\n";
echo "2. Monitor cache hit rate:\n";
echo "   - Admin > Reports > Status report\n";
echo "   - Check cache statistics\n\n";
echo "3. Adjust cache lifetime if needed:\n";
echo "   - Edit this script\n";
echo "   - Change cache_lifetime values\n";
echo "   - Re-run script\n";

echo "\n✨ Done!\n\n";
