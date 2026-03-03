<?php

/**
 * Phase 3: Simple approach - Just enable exposed filters on existing views
 */

use Drupal\views\Views;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     PHASE 3: Enable Exposed Filters                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Load and modify contacts view
$view = Views::getView('my_contacts');
if ($view) {
  $display = &$view->storage->getDisplay('default');
  
  // Simple: Just enable the exposed form
  $display['display_options']['exposed_form'] = [
    'type' => 'basic',
    'options' => [
      'submit_button' => 'Search',
      'reset_button' => TRUE,
      'reset_button_label' => 'Reset',
      'exposed_sorts_label' => 'Sort by',
      'expose_sort_order' => TRUE,
      'sort_asc_label' => 'Asc',
      'sort_desc_label' => 'Desc',
    ],
  ];
  
  $view->storage->save();
  echo "✓ Enabled exposed form on My Contacts view\n";
} else {
  echo "✗ Could not load My Contacts view\n";
}

// Load and modify deals view
$view2 = Views::getView('my_deals');
if ($view2) {
  $display2 = &$view2->storage->getDisplay('default');
  
  $display2['display_options']['exposed_form'] = [
    'type' => 'basic',
    'options' => [
      'submit_button' => 'Search',
      'reset_button' => TRUE,
      'reset_button_label' => 'Reset',
    ],
  ];
  
  $view2->storage->save();
  echo "✓ Enabled exposed form on My Deals view\n";
} else {
  echo "✗ Could not load My Deals view\n";
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║         FILTERS MUST BE ADDED VIA UI                     ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  Navigate to: /admin/structure/views                     ║\n";
echo "║  Edit each view and add exposed filters                  ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "MANUAL STEPS:\n\n";
echo "1. My Contacts View (/admin/structure/views/view/my_contacts):\n";
echo "   • Add filter: Content: Title (expose as 'Name')\n";
echo "   • Add filter: Content: Email (expose as 'Email')\n";
echo "   • Add filter: Content: Source (expose checkbox)\n";
echo "   • Add filter: Content: Customer Type (expose dropdown)\n\n";

echo "2. My Deals View (/admin/structure/views/view/my_deals):\n";
echo "   • Add filter: Content: Title (expose as 'Deal Name')\n";
echo "   • Add filter: Content: Amount (expose as range)\n";
echo "   • Add filter: Content: Pipeline Stage (expose dropdown)\n\n";

echo "Exposed forms are now ready - filters just need to be added in UI.\n";
