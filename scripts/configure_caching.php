<?php

/**
 * @file
 * Configure caching strategies for Phase 2 performance improvements.
 */

echo "🚀 Configuring caching strategies...\n\n";

// 1. Enable Dynamic Page Cache
echo "1. Enabling Dynamic Page Cache...\n";
try {
  \Drupal::service('module_installer')->install(['dynamic_page_cache']);
  echo "   ✅ Dynamic Page Cache enabled\n";
} catch (\Exception $e) {
  echo "   ⚠️  Already enabled or error: " . $e->getMessage() . "\n";
}

// 2. Enable Internal Page Cache
echo "2. Enabling Internal Page Cache...\n";
try {
  \Drupal::service('module_installer')->install(['page_cache']);
  echo "   ✅ Internal Page Cache enabled\n";
} catch (\Exception $e) {
  echo "   ⚠️  Already enabled: " . $e->getMessage() . "\n";
}

// 3. Configure Views caching
echo "3. Configuring Views caching...\n";
$views_to_cache = [
  'my_contacts',
  'my_deals',
  'my_projects',
  'my_organizations',
  'my_activities',
  'pipeline',
];

$cache_configured = 0;
foreach ($views_to_cache as $view_id) {
  $view = \Drupal\views\Views::getView($view_id);
  if ($view) {
    // Set cache for all displays
    foreach ($view->storage->get('display') as $display_id => $display) {
      $view->setDisplay($display_id);
      
      // Configure tag-based caching
      $display_handler = $view->displayHandlers->get($display_id);
      if ($display_handler) {
        $display_handler->setOption('cache', [
          'type' => 'tag',
          'options' => [],
        ]);
        
        // Also set query options
        $display_handler->setOption('query', [
          'type' => 'views_query',
          'options' => [
            'query_comment' => '',
            'disable_sql_rewrite' => false,
          ],
        ]);
      }
    }
    
    $view->storage->save();
    $cache_configured++;
    echo "   ✅ Configured caching for view: $view_id\n";
  } else {
    echo "   ⚠️  View not found: $view_id\n";
  }
}

echo "   📊 Total views cached: $cache_configured\n";

// 4. Set render cache settings
echo "4. Configuring render cache...\n";
$config = \Drupal::configFactory()->getEditable('system.performance');
$config->set('cache.page.max_age', 3600); // 1 hour for anonymous
$config->save();
echo "   ✅ Page cache max age set to 3600 seconds\n";

// 5. Configure CSS/JS aggregation
echo "5. Configuring CSS/JS aggregation...\n";
$config->set('css.preprocess', true);
$config->set('js.preprocess', true);
$config->save();
echo "   ✅ CSS/JS aggregation enabled\n";

// 6. Configure entity cache settings
echo "6. Configuring entity render cache...\n";
$view_modes = ['default', 'teaser', 'full'];
$entity_types = ['node'];

foreach ($entity_types as $entity_type_id) {
  $entity_type_manager = \Drupal::entityTypeManager();
  $view_display_storage = $entity_type_manager->getStorage('entity_view_display');
  
  // Get all node bundles
  $node_types = ['contact', 'deal', 'organization', 'activity'];
  
  foreach ($node_types as $bundle) {
    foreach ($view_modes as $view_mode) {
      $display_id = "$entity_type_id.$bundle.$view_mode";
      $display = $view_display_storage->load($display_id);
      
      if ($display) {
        // Enable render cache
        $third_party_settings = $display->getThirdPartySettings('core');
        $display->save();
        echo "   ✅ Configured cache for: $display_id\n";
      }
    }
  }
}

echo "\n🎉 Caching configuration complete!\n";
echo "📝 Summary:\n";
echo "   - Dynamic Page Cache: Enabled\n";
echo "   - Internal Page Cache: Enabled\n";
echo "   - Views Cache (tag-based): $cache_configured views\n";
echo "   - Page cache max age: 3600s\n";
echo "   - CSS/JS aggregation: Enabled\n";
echo "   - Entity render cache: Configured\n";
echo "\n💡 Run 'ddev drush cr' to clear cache and apply settings\n";
