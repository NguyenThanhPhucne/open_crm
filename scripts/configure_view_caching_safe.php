<?php

/**
 * @file
 * Safely configure views caching without breaking taxonomy filters.
 */

echo "🚀 Configuring Views caching (safe method)...\n\n";

$views_to_cache = [
  'my_contacts',
  'my_deals',
  'my_projects',
  'my_organizations',
  'my_activities',
  'pipeline',
];

$cache_configured = 0;
$errors = [];

foreach ($views_to_cache as $view_id) {
  echo "Processing view: $view_id\n";
  
  try {
    // Load view storage directly
    $view_storage = \Drupal::entityTypeManager()->getStorage('view');
    $view_entity = $view_storage->load($view_id);
    
    if (!$view_entity) {
      echo "  ⚠️  View not found\n";
      continue;
    }
    
    // Get displays array
    $displays = $view_entity->get('display');
    $modified = false;
    
    foreach ($displays as $display_id => &$display) {
      // Only set cache if not already configured
      if (!isset($display['display_options']['cache']) || $display['display_options']['cache']['type'] !== 'tag') {
        $display['display_options']['cache'] = [
          'type' => 'tag',
          'options' => [],
        ];
        $modified = true;
      }
    }
    
    if ($modified) {
      // Save modified displays
      $view_entity->set('display', $displays);
      $view_entity->save();
      echo "  ✅ Cache configured\n";
      $cache_configured++;
    } else {
      echo "  ℹ️  Already cached\n";
    }
    
  } catch (\Exception $e) {
    $error_msg = $e->getMessage();
    echo "  ❌ Error: $error_msg\n";
    $errors[$view_id] = $error_msg;
  }
  
  echo "\n";
}

echo "📊 Summary:\n";
echo "  - Views processed: " . count($views_to_cache) . "\n";
echo "  - Successfully cached: $cache_configured\n";
echo "  - Errors: " . count($errors) . "\n";

if (!empty($errors)) {
  echo "\n⚠️  Errors encountered:\n";
  foreach ($errors as $view_id => $error) {
    echo "  - $view_id: $error\n";
  }
}

echo "\n🎉 View caching configuration complete!\n";
echo "💡 Run 'ddev drush cr' to apply changes\n";
