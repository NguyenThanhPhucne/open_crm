<?php

/**
 * Verify crm_edit_link field has proper configuration.
 */

$view = \Drupal\views\Views::getView('my_activities');
$display = $view->getDisplay();
$fields = $display->getOption('fields');

echo "=== My Activities View - crm_edit_link field ===\n\n";

if (isset($fields['crm_edit_link'])) {
  echo "Field exists: YES\n";
  echo "Keys present: " . implode(', ', array_keys($fields['crm_edit_link'])) . "\n\n";
  
  if (isset($fields['crm_edit_link']['alter'])) {
    echo "✅ 'alter' key exists!\n";
  } else {
    echo "❌ 'alter' key is MISSING\n";
  }
} else {
  echo "❌ Field does not exist!\n";
}

echo "\n=== My Contacts View - crm_edit_link field ===\n\n";

$view2 = \Drupal\views\Views::getView('my_contacts');
$display2 = $view2->getDisplay();
$fields2 = $display2->getOption('fields');

if (isset($fields2['crm_edit_link'])) {
  echo "Field exists: YES\n";
  
  if (isset($fields2['crm_edit_link']['alter'])) {
    echo "✅ 'alter' key exists!\n";
  } else {
    echo "❌ 'alter' key is MISSING\n";
  }
} else {
  echo "❌ Field does not exist!\n";
}

echo "\nDone!\n";
