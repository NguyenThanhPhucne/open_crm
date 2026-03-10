<?php

/**
 * @file
 * Create "All Deals" view by copying and modifying "My Deals" view.
 * 
 * Usage: ddev drush scr scripts/production/create_all_deals_view.php
 */

use Drupal\views\Entity\View;

echo "\n📊 Creating 'All Deals' view for managers...\n\n";

// Load the my_deals view as a template
$my_deals = View::load('my_deals');

if (!$my_deals) {
  echo "❌ Error: my_deals view not found\n";
  exit(1);
}

// Get the configuration
$config = $my_deals->toArray();

// Modify for all_deals
$config['id'] = 'all_deals';
$config['label'] = 'All Deals';
$config['description'] = 'View all deals - for Managers/Admins';
$config['uuid'] = NULL; // Let Drupal generate new UUID

// Remove the contextual filter (uid argument) that limits to current user
if (isset($config['display']['default']['display_options']['arguments'])) {
  unset($config['display']['default']['display_options']['arguments']);
}
if (isset($config['display']['page_1']['display_options']['arguments'])) {
  unset($config['display']['page_1']['display_options']['arguments']);
}

// Update the path
$config['display']['page_1']['display_options']['path'] = 'crm/deals';

// Update menu
$config['display']['page_1']['display_options']['menu'] = [
  'type' => 'normal',
  'title' => 'All Deals',
  'description' => 'View all deals (Manager only)',
  'weight' => 11,
  'menu_name' => 'main',
];

// Add role-based access
$config['display']['page_1']['display_options']['access'] = [
  'type' => 'role',
  'options' => [
    'role' => [
      'administrator' => 'administrator',
      'crm_manager' => 'crm_manager',
    ],
  ],
];

// Update header
$config['display']['page_1']['display_options']['header'] = [
  'area' => [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
          <h2 style="margin: 0;">All Deals</h2>
          <p style="color: #666; margin: 0;">Viewing all deals across the organization</p>
        </div>
        <a href="/node/add/deal" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #10b981;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
        ">
          <span>+</span> Add Deal
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ],
];

// Update title
$config['display']['default']['display_options']['title'] = 'All Deals';
$config['display']['page_1']['display_options']['title'] = 'All Deals';

// Add caching
$config['display']['default']['display_options']['cache'] = [
  'type' => 'time',
  'options' => [
    'results_lifespan' => 900,  // 15 minutes
    'output_lifespan' => 900,
  ],
];

// Delete existing all_deals view if it exists
$existing = View::load('all_deals');
if ($existing) {
  echo "   Deleting existing all_deals view...\n";
  $existing->delete();
}

// Create the new view
try {
  $view = View::create($config);
  $view->save();
  
  echo "✅ Created 'All Deals' view successfully!\n\n";
  echo "📍 URL: http://open-crm.ddev.site/crm/deals\n";
  echo "🔐 Access: Administrators and CRM Managers only\n";
  echo "💾 Cache: 15 minutes\n";
  echo "📊 Shows: All deals across organization\n\n";
  
  // Count deals
  $deals_count = \Drupal::entityQuery('node')
    ->condition('type', 'deal')
    ->condition('status', 1)
    ->accessCheck(FALSE)
    ->count()
    ->execute();
  
  echo "📈 Current data: {$deals_count} deals\n\n";
  
  // Clear cache
  echo "🔄 Clearing cache...\n";
  drupal_flush_all_caches();
  echo "✅ Cache cleared\n\n";
  
  echo "✨ Done!\n\n";
  
} catch (\Exception $e) {
  echo "❌ Error creating view: " . $e->getMessage() . "\n";
  exit(1);
}
