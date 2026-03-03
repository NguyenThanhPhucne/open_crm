<?php

/**
 * @file
 * Optimize all CRM views for instant updates and better performance.
 * 
 * This script:
 * 1. Adds tag-based caching to all views (instant invalidation on data change)
 * 2. Optimizes query settings for better performance
 * 3. Ensures views update immediately when data is added/edited/deleted
 */

use Drupal\views\Views;

$views_to_optimize = [
  'all_organizations',
  'my_contacts',
  'my_deals',
  'my_organizations',
  'my_activities',
];

$updated = [];
$skipped = [];

foreach ($views_to_optimize as $view_id) {
  $view = Views::getView($view_id);
  
  if (!$view) {
    $skipped[] = "$view_id (not found)";
    continue;
  }
  
  $config = \Drupal::configFactory()->getEditable("views.view.$view_id");
  $displays = $config->get('display');
  
  $changes_made = false;
  
  foreach ($displays as $display_id => $display) {
    // Add tag-based caching for instant invalidation
    $current_cache = $display['display_options']['cache']['type'] ?? null;
    
    if ($current_cache !== 'tag') {
      $displays[$display_id]['display_options']['cache'] = [
        'type' => 'tag',
        'options' => [],
      ];
      $changes_made = true;
    }
    
    // Optimize query settings
    if (!isset($display['display_options']['query']['options']['disable_sql_rewrite'])) {
      $displays[$display_id]['display_options']['query']['options']['disable_sql_rewrite'] = FALSE;
      $changes_made = true;
    }
    
    // Ensure proper query distinct setting
    if (!isset($display['display_options']['query']['options']['distinct'])) {
      $displays[$display_id]['display_options']['query']['options']['distinct'] = FALSE;
      $changes_made = true;
    }
  }
  
  if ($changes_made) {
    $config->set('display', $displays);
    $config->save();
    $updated[] = $view_id;
    echo "✅ Updated: $view_id\n";
  } else {
    $skipped[] = "$view_id (already optimized)";
    echo "⏭️  Skipped: $view_id (already optimized)\n";
  }
}

// Clear caches to apply changes
drupal_flush_all_caches();

echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 VIEWS CACHING OPTIMIZATION COMPLETE\n";
echo str_repeat('=', 60) . "\n\n";

if (!empty($updated)) {
  echo "✅ Updated " . count($updated) . " views:\n";
  foreach ($updated as $view) {
    echo "   - $view\n";
  }
  echo "\n";
}

if (!empty($skipped)) {
  echo "⏭️  Skipped " . count($skipped) . " views:\n";
  foreach ($skipped as $view) {
    echo "   - $view\n";
  }
  echo "\n";
}

echo "🎯 Cache Configuration:\n";
echo "   Type: tag (automatic invalidation)\n";
echo "   Behavior: Views update instantly when data changes\n";
echo "   Performance: Cached until content is modified\n\n";

echo "🔄 Cache cleared\n\n";

// Verify configuration
echo "📋 Current Configuration:\n";
foreach ($views_to_optimize as $view_id) {
  $config = \Drupal::config("views.view.$view_id");
  $cache_type = $config->get('display.default.display_options.cache.type');
  $status = $cache_type === 'tag' ? '✅' : '⚠️';
  echo "   $status $view_id: " . ($cache_type ?? 'none') . "\n";
}

echo "\n✨ All views optimized for instant updates!\n";

