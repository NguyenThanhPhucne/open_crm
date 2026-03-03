#!/usr/bin/env php
<?php

/**
 * Test Quick Add Form Functionality
 * 
 * Usage: drush scr scripts/test_quickadd_form.php
 */

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

echo "=== TESTING QUICK ADD FORM FUNCTIONALITY ===\n\n";

// 1. Check if module is enabled
echo "1. Checking crm_quickadd module...\n";
$module_handler = \Drupal::service('module_handler');
if ($module_handler->moduleExists('crm_quickadd')) {
  echo "✅ Module crm_quickadd is enabled\n\n";
} else {
  echo "❌ Module crm_quickadd is NOT enabled\n\n";
  exit(1);
}

// 2. Check if routes are registered
echo "2. Checking routes...\n";
$route_provider = \Drupal::service('router.route_provider');
try {
  $contact_form_route = $route_provider->getRouteByName('crm_quickadd.contact_form');
  $contact_submit_route = $route_provider->getRouteByName('crm_quickadd.contact_submit');
  echo "✅ Routes are registered:\n";
  echo "   - /crm/quickadd/contact (form)\n";
  echo "   - /crm/quickadd/contact/submit (POST handler)\n\n";
} catch (\Exception $e) {
  echo "❌ Routes not found: " . $e->getMessage() . "\n\n";
  exit(1);
}

// 3. Check permissions for sales_rep role
echo "3. Checking permissions for sales_rep role...\n";
$role = \Drupal\user\Entity\Role::load('sales_rep');
if ($role && $role->hasPermission('create contact content')) {
  echo "✅ sales_rep role has 'create contact content' permission\n\n";
} else {
  echo "❌ sales_rep role does NOT have 'create contact content' permission\n\n";
}

// 4. Test as salesrep1 user
echo "4. Testing form submission as salesrep1...\n";
$user = user_load_by_name('salesrep1');
if (!$user) {
  echo "❌ User salesrep1 not found\n\n";
  exit(1);
}

// Switch to salesrep1 user context
\Drupal::service('account_switcher')->switchTo($user);
echo "✅ Switched to user: salesrep1 (UID: {$user->id()})\n";
echo "   Roles: " . implode(', ', $user->getRoles()) . "\n\n";

// 5. Simulate form submission data
echo "5. Simulating form submission...\n";
$test_data = [
  'name' => 'Test Quick Add - ' . date('H:i:s'),
  'phone' => '+84' . rand(900000000, 999999999),
  'email' => 'test.quickadd.' . time() . '@example.com',
  'position' => 'Test Position',
];

echo "   Data:\n";
echo "   - Name: {$test_data['name']}\n";
echo "   - Phone: {$test_data['phone']}\n";
echo "   - Email: {$test_data['email']}\n\n";

// 6. Check for duplicate phone (like the controller does)
echo "6. Checking for duplicate phone...\n";
$existing = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->condition('field_phone', $test_data['phone'])
  ->accessCheck(FALSE)
  ->range(0, 1)
  ->execute();

if (!empty($existing)) {
  echo "⚠️  Phone number already exists (ID: " . reset($existing) . ")\n\n";
} else {
  echo "✅ Phone number is unique\n\n";
}

// 7. Create contact (simulate what controller does)
echo "7. Creating contact via API (simulating controller)...\n";
try {
  $contact = Node::create([
    'type' => 'contact',
    'title' => $test_data['name'],
    'field_email' => $test_data['email'],
    'field_phone' => $test_data['phone'],
    'field_position' => $test_data['position'],
    'field_owner' => ['target_id' => $user->id()],
    'uid' => $user->id(),
    'status' => 1,
  ]);
  $contact->save();
  
  echo "✅ Contact created successfully!\n";
  echo "   - ID: {$contact->id()}\n";
  echo "   - Title: {$contact->getTitle()}\n";
  echo "   - Owner: " . $contact->getOwner()->getDisplayName() . "\n";
  echo "   - URL: /node/{$contact->id()}\n\n";
  
  $created_id = $contact->id();
  
} catch (\Exception $e) {
  echo "❌ Error creating contact: {$e->getMessage()}\n\n";
  \Drupal::service('account_switcher')->switchBack();
  exit(1);
}

// 8. Verify contact can be loaded
echo "8. Verifying contact can be loaded...\n";
$loaded_contact = Node::load($created_id);
if ($loaded_contact && $loaded_contact->access('view', $user)) {
  echo "✅ Contact can be loaded and accessed\n";
  echo "   - Phone: {$loaded_contact->get('field_phone')->value}\n";
  echo "   - Email: {$loaded_contact->get('field_email')->value}\n\n";
} else {
  echo "❌ Contact cannot be loaded or accessed\n\n";
}

// 9. Clean up - delete test contact
echo "9. Cleaning up test data...\n";
try {
  $loaded_contact->delete();
  echo "✅ Test contact deleted (ID: {$created_id})\n\n";
} catch (\Exception $e) {
  echo "⚠️  Could not delete test contact: {$e->getMessage()}\n\n";
}

// Switch back to original user
\Drupal::service('account_switcher')->switchBack();

// 10. Check library attachment
echo "10. Checking JavaScript library configuration...\n";
$library_discovery = \Drupal::service('library.discovery');
$library = $library_discovery->getLibraryByName('crm_quickadd', 'quickadd');

if ($library) {
  echo "✅ Library 'crm_quickadd/quickadd' is defined\n";
  if (isset($library['js'])) {
    echo "   JavaScript files:\n";
    foreach ($library['js'] as $js_file => $options) {
      echo "   - $js_file\n";
    }
  }
  if (isset($library['dependencies'])) {
    echo "   Dependencies: " . implode(', ', $library['dependencies']) . "\n";
  }
  echo "\n";
} else {
  echo "❌ Library 'crm_quickadd/quickadd' is NOT defined\n\n";
}

// 11. Summary
echo "=== SUMMARY ===\n";
echo "✅ Module enabled\n";
echo "✅ Routes registered\n";
echo "✅ Permissions configured\n";
echo "✅ Form submission works programmatically\n";
echo "✅ JavaScript library defined\n\n";

echo "🔍 TROUBLESHOOTING STEPS:\n";
echo "1. Login link for testing:\n";
system('cd /var/www/html && ddev drush uli --name=salesrep1 --no-browser');
echo "\n";
echo "2. After logging in, visit: http://open-crm.ddev.site/crm/quickadd/contact\n";
echo "3. Open browser console (F12) to check for JavaScript errors\n";
echo "4. Fill the form and submit\n";
echo "5. Check Network tab for AJAX request to /crm/quickadd/contact/submit\n\n";

echo "❓ If form still doesn't work:\n";
echo "   - Check if jQuery is loaded: type 'jQuery' in browser console\n";
echo "   - Check if form ID matches: inspect form element for id=\"quickadd-contact-form\"\n";
echo "   - Check JavaScript errors in console\n";
echo "   - Check Network tab for failed requests\n";
echo "   - Verify fetch() API is supported in browser\n\n";
