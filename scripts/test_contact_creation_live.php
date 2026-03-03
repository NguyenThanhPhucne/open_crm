#!/usr/bin/env php
<?php

/**
 * Test Contact Creation with Live Session
 * 
 * Test if we can create a contact as if we're logged in via the quick add form
 * 
 * Usage: drush scr scripts/test_contact_creation_live.php
 */

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

echo "=== TESTING CONTACT CREATION (LIVE SESSION) ===\n\n";

// Get current user
$current_user = \Drupal::currentUser();
echo "Current User:\n";
echo "  - UID: " . $current_user->id() . "\n";
echo "  - Anonymous: " . ($current_user->isAnonymous() ? 'Yes' : 'No') . "\n";

if (!$current_user->isAnonymous()) {
  $account = User::load($current_user->id());
  echo "  - Name: " . $account->getAccountName() . "\n";
  echo "  - Roles: " . implode(', ', $account->getRoles()) . "\n";
  echo "  - Has 'create contact content': " . ($current_user->hasPermission('create contact content') ? 'Yes' : 'No') . "\n";
}

echo "\n";

// Test 1: Check if we can access the quick add form
echo "1. Testing route access...\n";
$route_provider = \Drupal::service('router.route_provider');
$route = $route_provider->getRouteByName('crm_quickadd.contact_form');
$requirements = $route->getRequirement('_permission');
echo "  - Route: /crm/quickadd/contact\n";
echo "  - Required permission: $requirements\n";
echo "  - User has permission: " . ($current_user->hasPermission($requirements) ? 'Yes ✅' : 'No ❌') . "\n";
echo "\n";

// Test 2: Try to create a contact as current user
if ($current_user->hasPermission('create contact content')) {
  echo "2. Testing contact creation...\n";
  
  $test_phone = '+84' . rand(900000000, 999999999);
  $test_data = [
    'name' => 'Test Contact - ' . date('H:i:s'),
    'phone' => $test_phone,
    'email' => 'test.' . time() . '@example.com',
  ];
  
  echo "  - Name: {$test_data['name']}\n";
  echo "  - Phone: {$test_data['phone']}\n";
  echo "  - Email: {$test_data['email']}\n";
  
  try {
    $contact = Node::create([
      'type' => 'contact',
      'title' => $test_data['name'],
      'field_email' => $test_data['email'],
      'field_phone' => $test_data['phone'],
      'field_owner' => ['target_id' => $current_user->id()],
      'uid' => $current_user->id(),
      'status' => 1,
    ]);
    $contact->save();
    
    echo "  ✅ SUCCESS! Contact created:\n";
    echo "     - ID: {$contact->id()}\n";
    echo "     - URL: /node/{$contact->id()}\n";
    echo "     - View at: http://open-crm.ddev.site/node/{$contact->id()}\n\n";
    
    // Clean up
    echo "3. Cleaning up...\n";
    $contact->delete();
    echo "  ✅ Test contact deleted\n\n";
    
  } catch (\Exception $e) {
    echo "  ❌ FAILED: {$e->getMessage()}\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n\n";
  }
} else {
  echo "2. ❌ Current user does not have 'create contact content' permission\n\n";
  echo "Please run as a user with sales_rep or sales_manager role.\n";
  echo "Run: ddev drush scr scripts/test_contact_creation_live.php --user=salesrep1\n\n";
}

// Test 3: Check JavaScript library
echo "3. Checking JavaScript library...\n";
$library_discovery = \Drupal::service('library.discovery');
$library = $library_discovery->getLibraryByName('crm_quickadd', 'quickadd');

if ($library) {
  echo "  ✅ Library 'crm_quickadd/quickadd' is defined\n";
  if (isset($library['js'])) {
    foreach ($library['js'] as $js_file => $options) {
      if ($js_file !== '0') {
        echo "     - JS file: $js_file\n";
        // Check if file exists
        $full_path = DRUPAL_ROOT . '/' . $js_file;
        if (file_exists($full_path)) {
          echo "       (File exists ✅, Size: " . number_format(filesize($full_path)) . " bytes)\n";
        } else {
          echo "       (File NOT found ❌)\n";
        }
      }
    }
  }
} else {
  echo "  ❌ Library is not defined\n";
}

echo "\n=== SUMMARY ===\n";
if ($current_user->isAnonymous()) {
  echo "❌ You are not logged in. The quick add form requires authentication.\n\n";
  echo "🔑 Login link:\n";
  system('cd /var/www/html && drush user:login --name=salesrep1 --no-browser');
  echo "\n";
} else if (!$current_user->hasPermission('create contact content')) {
  echo "❌ Current user does not have permission to create contacts.\n";
  echo "   User needs sales_rep or sales_manager role.\n\n";
} else {
  echo "✅ All checks passed. Quick add form should work.\n\n";
}

echo "📋 Next steps:\n";
echo "1. Login using the link above\n";
echo "2. Visit: http://open-crm.ddev.site/crm/quickadd/contact\n";
echo "3. Open browser DevTools (F12) → Console tab\n";
echo "4. Fill the form and click submit\n";
echo "5. Check Console for JavaScript errors\n";
echo "6. Check Network tab for AJAX request\n\n";
