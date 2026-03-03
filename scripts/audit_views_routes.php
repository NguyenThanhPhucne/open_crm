<?php

/**
 * Audit all CRM views for routes and contextual filters
 */

use Drupal\views\Views;

$views = [
  'my_contacts',
  'my_deals', 
  'my_activities',
  'my_organizations',
  'all_organizations',
  'my_projects',
  'contacts',
  'pipeline'
];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         CRM VIEWS - ROUTES & CONTEXTUAL FILTERS AUDIT          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$empty_views = [];
$filtered_views = [];

foreach ($views as $view_id) {
  $view = Views::getView($view_id);
  if (!$view) continue;
  
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
  echo "VIEW: {$view_id}\n";
  echo "Label: " . $view->storage->label() . "\n";
  echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
  
  $displays = $view->storage->get('display');
  
  foreach ($displays as $display_id => $display) {
    if ($display_id === 'default') continue;
    
    echo "  Display: {$display_id} ({$display['display_plugin']})\n";
    
    // Get path
    if (isset($display['display_options']['path'])) {
      echo "  📍 Path: /{$display['display_options']['path']}\n";
    }
    
    // Check for arguments (contextual filters)
    $has_arguments = false;
    $args = [];
    
    if (isset($display['display_options']['arguments'])) {
      $has_arguments = true;
      $args = $display['display_options']['arguments'];
    } elseif (isset($displays['default']['display_options']['arguments'])) {
      $has_arguments = true;
      $args = $displays['default']['display_options']['arguments'];
    }
    
    if ($has_arguments && !empty($args)) {
      echo "  ⚙️  Contextual Filters (CÓ THỂ TRỐNG nếu user không match):\n";
      $filtered_views[$view_id] = true;
      foreach ($args as $arg_id => $arg) {
        $default_arg = $arg['default_argument_type'] ?? 'none';
        $field_name = $arg['field'] ?? $arg_id;
        echo "      - {$field_name} (default: {$default_arg})\n";
        
        if ($default_arg === 'current_user') {
          echo "        ⚠️  WARNING: Trang sẽ TRỐNG nếu user không có data!\n";
        }
      }
    } else {
      echo "  ✅ No filters - Shows ALL data\n";
    }
    
    echo "\n";
  }
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "SUMMARY - VIEWS CÓ CONTEXTUAL FILTER\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

if (!empty($filtered_views)) {
  echo "⚠️  Views này có thể hiển thị TRỐNG nếu user không có data:\n\n";
  foreach (array_keys($filtered_views) as $view_id) {
    echo "  - {$view_id}\n";
  }
  
  echo "\n💡 GIẢI PHÁP:\n";
  echo "   1. User cần có data được assign cho họ\n";
  echo "   2. Hoặc tạo data mới với user đó làm owner\n";
  echo "   3. Admin/Manager cũng cần data được assign\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "TESTING CHECKLIST\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Để đảm bảo không bị trang trống:\n\n";
echo "□ my_contacts - User phải có contacts với field_owner = user.uid\n";
echo "□ my_deals - User phải có deals với field_owner = user.uid\n";
echo "□ my_activities - User phải có activities với field_assigned_to = user.uid\n";
echo "□ my_organizations - User phải có orgs với field_owner = user.uid\n";
echo "□ my_projects - Contact phải được link với deals (for customer portal)\n\n";

echo "✅ Views KHÔNG BỊ TRỐNG (no filter):\n";
echo "□ all_organizations - Hiển thị tất cả organizations\n";
echo "□ contacts - Hiển thị tất cả contacts\n";
echo "□ pipeline - Hiển thị tất cả deals\n\n";
