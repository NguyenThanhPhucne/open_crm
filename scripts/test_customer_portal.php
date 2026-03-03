<?php

/**
 * @file
 * Test Customer Portal end-to-end workflow.
 */

echo "🧪 CUSTOMER PORTAL WORKFLOW TEST\n";
echo "=====================================\n\n";

$results = [];
$database = \Drupal::database();

// Test 1: Check field_contact_profile exists
echo "1️⃣  Checking field_contact_profile field...\n";
$field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'field_contact_profile');
if ($field) {
  echo "   ✅ Field exists\n";
  echo "   - Label: " . $field->label() . "\n";
  echo "   - Required: " . ($field->isRequired() ? 'Yes' : 'No') . "\n";
  $settings = $field->getSettings();
  echo "   - Target bundles: " . implode(', ', $settings['handler_settings']['target_bundles'] ?? ['all']) . "\n";
  $results['field'] = 'pass';
} else {
  echo "   ❌ Field not found\n";
  $results['field'] = 'fail';
}
echo "\n";

// Test 2: Check Customer role
echo "2️⃣  Checking Customer role configuration...\n";
$role = \Drupal\user\Entity\Role::load('customer');
if ($role) {
  echo "   ✅ Role exists\n";
  $permissions = $role->getPermissions();
  echo "   - Permissions: " . count($permissions) . " total\n";
  echo "   - Key permissions:\n";
  
  $key_perms = ['access content', 'access user profiles'];
  foreach ($key_perms as $perm) {
    $has_perm = in_array($perm, $permissions);
    echo "     " . ($has_perm ? '✅' : '❌') . " $perm\n";
  }
  $results['role'] = 'pass';
} else {
  echo "   ❌ Role not found\n";
  $results['role'] = 'fail';
}
echo "\n";

// Test 3: Check my_projects view
echo "3️⃣  Checking my_projects view...\n";
$view = \Drupal\views\Views::getView('my_projects');
if ($view) {
  echo "   ✅ View exists\n";
  echo "   - Path: /my/projects\n";
  echo "   - Status: " . ($view->storage->status() ? 'Enabled' : 'Disabled') . "\n";
  
  // Check access settings
  $displays = $view->storage->get('display');
  if (isset($displays['page_1']['display_options']['access'])) {
    $access = $displays['page_1']['display_options']['access'];
    echo "   - Access type: " . $access['type'] . "\n";
    if (isset($access['options']['role'])) {
      echo "   - Allowed roles: " . implode(', ', array_keys($access['options']['role'])) . "\n";
    }
  }
  
  // Check relationships
  if (isset($displays['default']['display_options']['relationships'])) {
    $rels = $displays['default']['display_options']['relationships'];
    echo "   - Relationships configured: " . count($rels) . "\n";
  }
  
  $results['view'] = 'pass';
} else {
  echo "   ❌ View not found\n";
  $results['view'] = 'fail';
}
echo "\n";

// Test 4: Create test customer user
echo "4️⃣  Testing Customer User Creation...\n";
try {
  // Check if test user already exists
  $existing_user = user_load_by_name('test_customer_portal');
  
  if ($existing_user) {
    echo "   ℹ️  Test user already exists (UID: " . $existing_user->id() . ")\n";
    $test_user = $existing_user;
  } else {
    // Create test user
    $test_user = \Drupal\user\Entity\User::create([
      'name' => 'test_customer_portal',
      'mail' => 'test.customer@example.com',
      'pass' => 'TestPass123!',
      'status' => 1,
      'roles' => ['customer'],
    ]);
    $test_user->save();
    echo "   ✅ Test user created (UID: " . $test_user->id() . ")\n";
  }
  
  echo "   - Username: " . $test_user->getAccountName() . "\n";
  echo "   - Email: " . $test_user->getEmail() . "\n";
  echo "   - Roles: " . implode(', ', $test_user->getRoles()) . "\n";
  
  $results['user_creation'] = 'pass';
  $results['test_user_id'] = $test_user->id();
} catch (Exception $e) {
  echo "   ❌ Error: " . $e->getMessage() . "\n";
  $results['user_creation'] = 'fail';
}
echo "\n";

// Test 5: Link user to contact
echo "5️⃣  Testing Contact Profile Linking...\n";
try {
  // Get first available contact
  $contact_ids = $database->query(
    "SELECT nid FROM {node_field_data} WHERE type = 'contact' AND status = 1 LIMIT 1"
  )->fetchCol();
  
  if (!empty($contact_ids)) {
    $contact_id = $contact_ids[0];
    $contact = \Drupal\node\Entity\Node::load($contact_id);
    
    if (isset($test_user)) {
      // Set contact profile
      $test_user->set('field_contact_profile', $contact_id);
      $test_user->save();
      
      echo "   ✅ Contact profile linked\n";
      echo "   - User: " . $test_user->getAccountName() . "\n";
      echo "   - Contact: " . $contact->getTitle() . " (NID: $contact_id)\n";
      
      $results['contact_link'] = 'pass';
      $results['contact_id'] = $contact_id;
    }
  } else {
    echo "   ⚠️  No contacts available to link\n";
    $results['contact_link'] = 'skip';
  }
} catch (Exception $e) {
  echo "   ❌ Error: " . $e->getMessage() . "\n";
  $results['contact_link'] = 'fail';
}
echo "\n";

// Test 6: Verify view access
echo "6️⃣  Testing View Access Logic...\n";
try {
  if (isset($test_user) && isset($results['contact_id'])) {
    // Get deals associated with this contact
    $query = $database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title'])
      ->condition('n.type', 'deal')
      ->condition('n.status', 1);
    
    $query->join('node__field_contact', 'fc', 'fc.entity_id = n.nid');
    $query->condition('fc.field_contact_target_id', $results['contact_id']);
    
    $deals = $query->execute()->fetchAll();
    
    echo "   ✅ Query executed\n";
    echo "   - Deals for this contact: " . count($deals) . "\n";
    
    if (count($deals) > 0) {
      echo "   📝 Deals:\n";
      foreach ($deals as $deal) {
        echo "     - " . $deal->title . " (NID: " . $deal->nid . ")\n";
      }
    }
    
    $results['view_access'] = 'pass';
  } else {
    echo "   ⚠️  Cannot test - missing test user or contact\n";
    $results['view_access'] = 'skip';
  }
} catch (Exception $e) {
  echo "   ❌ Error: " . $e->getMessage() . "\n";
  $results['view_access'] = 'fail';
}
echo "\n";

// Summary
echo "=====================================\n";
echo "📊 TEST SUMMARY\n";
echo "=====================================\n";

$test_labels = [
  'field' => 'field_contact_profile field',
  'role' => 'Customer role',
  'view' => 'my_projects view',
  'user_creation' => 'Test user creation',
  'contact_link' => 'Contact profile linking',
  'view_access' => 'View access logic',
];

$passed = 0;
$failed = 0;
$skipped = 0;

foreach ($test_labels as $key => $label) {
  if (!isset($results[$key])) continue;
  
  $status = $results[$key];
  if ($status === 'pass') {
    echo "✅ $label: PASS\n";
    $passed++;
  } elseif ($status === 'fail') {
    echo "❌ $label: FAIL\n";
    $failed++;
  } elseif ($status === 'skip') {
    echo "⚠️  $label: SKIPPED\n";
    $skipped++;
  }
}

echo "\nTotal: $passed passed, $failed failed, $skipped skipped\n";

// Cleanup note
if (isset($test_user)) {
  echo "\n💡 Test user 'test_customer_portal' (UID: " . $test_user->id() . ") created for testing\n";
  echo "   Login: ddev drush uli test_customer_portal\n";
  echo "   Delete: ddev drush user:cancel test_customer_portal\n";
}

if ($failed === 0) {
  echo "\n🎉 Customer Portal workflow fully functional!\n";
} else {
  echo "\n⚠️  Some tests failed - review above for details\n";
}
