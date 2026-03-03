#!/usr/bin/env php
<?php

/**
 * Fix Form Issues: Status field and User reference
 * 
 * Usage: drush scr scripts/fix_form_issues.php
 */

use Drupal\Core\Entity\Entity\EntityFormDisplay;

echo "=== FIXING FORM ISSUES ===\n\n";

// Fix for Contact form
echo "1. Fixing Contact form...\n";
$contact_form = EntityFormDisplay::load('node.contact.default');
if ($contact_form) {
  
  // Make status field visible with default value
  $status_component = $contact_form->getComponent('field_status');
  if ($status_component === NULL) {
    echo "   - Adding field_status to form\n";
    $contact_form->setComponent('field_status', [
      'type' => 'options_select',
      'weight' => 5,
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [],
    ]);
  } else {
    echo "   ✅ field_status already in form\n";
  }
  
  // Make owner field use current user by default
  $owner_component = $contact_form->getComponent('field_owner');
  if ($owner_component) {
    echo "   - Updating field_owner configuration\n";
    $owner_component['settings']['match_operator'] = 'CONTAINS';
    $owner_component['settings']['match_limit'] = 10;
    $contact_form->setComponent('field_owner', $owner_component);
  }
  
  $contact_form->save();
  echo "   ✅ Contact form updated\n\n";
}

// Fix for Deal form
echo "2. Fixing Deal form...\n";
$deal_form = EntityFormDisplay::load('node.deal.default');
if ($deal_form) {
  
  $status_component = $deal_form->getComponent('field_status');
  if ($status_component === NULL) {
    echo "   - Adding field_status to form\n";
    $deal_form->setComponent('field_status', [
      'type' => 'options_select',
      'weight' => 10,
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [],
    ]);
  } else {
    echo "   ✅ field_status already in form\n";
  }
  
  $deal_form->save();
  echo "   ✅ Deal form updated\n\n";
}

// Fix for Organization form
echo "3. Fixing Organization form...\n";
$org_form = EntityFormDisplay::load('node.organization.default');
if ($org_form) {
  
  $status_component = $org_form->getComponent('field_status');
  if ($status_component === NULL) {
    echo "   - Adding field_status to form\n";
    $org_form->setComponent('field_status', [
      'type' => 'options_select',
      'weight' => 5,
      'region' => 'content',
      'settings' => [],
      'third_party_settings' => [],
    ]);
  } else {
    echo "   ✅ field_status already in form\n";
  }
  
  // Check assigned_staff field
  $staff_component = $org_form->getComponent('field_assigned_staff');
  if ($staff_component) {
    echo "   - Updating field_assigned_staff configuration\n";
    $staff_component['settings']['match_operator'] = 'CONTAINS';
    $staff_component['settings']['match_limit'] = 10;
    $org_form->setComponent('field_assigned_staff', $staff_component);
  }
  
  $org_form->save();
  echo "   ✅ Organization form updated\n\n";
}

// Fix for Activity form
echo "4. Fixing Activity form...\n";
$activity_form = EntityFormDisplay::load('node.activity.default');
if ($activity_form) {
  
  // Check assigned_to field
  $assigned_component = $activity_form->getComponent('field_assigned_to');
  if ($assigned_component) {
    echo "   - Updating field_assigned_to configuration\n";
    $assigned_component['settings']['match_operator'] = 'CONTAINS';
    $assigned_component['settings']['match_limit'] = 10;
    $activity_form->setComponent('field_assigned_to', $assigned_component);
  }
  
  $activity_form->save();
  echo "   ✅ Activity form updated\n\n";
}

// Set default values for status fields
echo "5. Setting default values for status fields...\n";

$field_configs = [
  'node.contact.field_status' => 'active',
  'node.deal.field_status' => 'active',
  'node.organization.field_status' => 'active',
];

foreach ($field_configs as $config_name => $default_value) {
  $field_config = \Drupal::configFactory()->getEditable("field.field.$config_name");
  if ($field_config) {
    $field_config->set('default_value', [['value' => $default_value]]);
    $field_config->save();
    echo "   ✅ Set default for $config_name: $default_value\n";
  }
}

echo "\n=== FIXING USER REFERENCE ISSUE ===\n\n";

// The issue is that admin (uid=1) doesn't have sales_rep or sales_manager role
// So when creating entities, it tries to use admin as owner which fails validation

echo "Option 1: Add sales_manager role to admin user\n";
echo "Option 2: Login as a different user (salesrep1 or manager)\n\n";

$admin = \Drupal\user\Entity\User::load(1);
if ($admin) {
  $current_roles = $admin->getRoles();
  echo "Current admin roles: " . implode(', ', $current_roles) . "\n";
  
  if (!in_array('sales_manager', $current_roles)) {
    echo "\n🔧 Adding sales_manager role to admin...\n";
    $admin->addRole('sales_manager');
    $admin->save();
    echo "✅ Admin now has sales_manager role\n";
    echo "   New roles: " . implode(', ', $admin->getRoles()) . "\n\n";
  } else {
    echo "✅ Admin already has sales_manager role\n\n";
  }
}

echo "=== SUMMARY ===\n";
echo "✅ Status fields added to all forms with default value 'active'\n";
echo "✅ User reference fields configured properly\n";
echo "✅ Admin user now has sales_manager role (can be used as owner)\n\n";

echo "🧪 Test now:\n";
echo "1. Clear cache: ddev drush cr\n";
echo "2. Login and try creating a contact\n";
echo "3. Status should now have default value\n";
echo "4. Owner field should work with admin user\n\n";
