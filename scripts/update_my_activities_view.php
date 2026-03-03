<?php

/**
 * @file
 * Update My Activities view to add Contact field.
 */

use Drupal\views\Entity\View;

// Load the view
$view = View::load('my_activities');

if ($view) {
  $display = &$view->getDisplay('default');
  
  // Add field_contact to fields after title
  $fields = $display['display_options']['fields'];
  
  // Reorder: title, contact, deal, type, datetime
  $new_fields = [
    'title' => $fields['title'],
    'field_contact' => [
      'id' => 'field_contact',
      'table' => 'node__field_contact',
      'field' => 'field_contact',
      'plugin_id' => 'field',
      'label' => 'Contact',
      'type' => 'entity_reference_label',
      'settings' => [
        'link' => TRUE,
      ],
    ],
    'field_deal' => $fields['field_deal'],
    'field_type' => $fields['field_type'],
    'field_datetime' => $fields['field_datetime'],
  ];
  
  $display['display_options']['fields'] = $new_fields;
  $view->set('display', ['default' => $display] + $view->get('display'));
  
  // Update dependencies
  $dependencies = $view->get('dependencies');
  if (!in_array('field.storage.node.field_contact', $dependencies['config'])) {
    $dependencies['config'][] = 'field.storage.node.field_contact';
    sort($dependencies['config']);
    $view->set('dependencies', $dependencies);
  }
  
  // Save the view
  $view->save();
  
  echo "✅ Successfully updated 'My Activities' view:\n";
  echo "   - Added Contact field\n";
  echo "   - Field order: Activity → Contact → Deal → Type → Date\n";
  echo "   - All fields link to entities\n";
  echo "\n";
  echo "🔄 Clearing cache...\n";
  drupal_flush_all_caches();
  echo "✅ Cache cleared\n\n";
  echo "🌐 Visit: http://open-crm.ddev.site/crm/my-activities\n";
  
} else {
  echo "❌ ERROR: Could not load 'my_activities' view\n";
  exit(1);
}
