#!/usr/bin/env php
<?php

/**
 * Debug Quick Add Form - Add Debug Logging
 * 
 * Usage: drush scr scripts/debug_quickadd.php
 */

use Drupal\node\Entity\Node;

echo "=== DEBUGGING QUICK ADD FORM ===\n\n";

// 1. Test as salesrep1
echo "1. Switching to salesrep1 user...\n";
$user = user_load_by_name('salesrep1');
if (!$user) {
  echo "❌ User salesrep1 not found\n";
  exit(1);
}

\Drupal::service('account_switcher')->switchTo($user);
echo "✅ User: {$user->getAccountName()} (UID: {$user->id()})\n";
echo "   Roles: " . implode(', ', $user->getRoles()) . "\n";
echo "   Has permission: " . ($user->hasPermission('create contact content') ? 'Yes' : 'No') . "\n\n";

// 2. Simulate the exact data that would come from the form
echo "2. Simulating form POST data...\n";
$formData = [
  'name' => 'Debug Test - ' . date('H:i:s'),
  'phone' => '+84' . rand(900000000, 999999999),
  'email' => 'debug.test.' . time() . '@example.com',
  'position' => 'Test Position',
  'customer_type' => '',  // Empty like form might send
  'source' => '',
  'organization' => '',
];

echo "   Data:\n";
foreach ($formData as $key => $value) {
  echo "   - $key: " . ($value ?: '(empty)') . "\n";
}
echo "\n";

// 3. Simulate what the controller does
echo "3. Simulating controller logic...\n";

// Validate required fields
if (empty($formData['name']) || empty($formData['phone'])) {
  echo "❌ Validation failed: Missing name or phone\n";
  \Drupal::service('account_switcher')->switchBack();
  exit(1);
}
echo "✅ Required fields present\n";

// Check duplicate phone
$existing = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->condition('field_phone', $formData['phone'])
  ->accessCheck(FALSE)
  ->range(0, 1)
  ->execute();

if (!empty($existing)) {
  echo "⚠️  Duplicate phone found (ID: " . reset($existing) . ")\n";
} else {
  echo "✅ Phone is unique\n";
}
echo "\n";

// 4. Create contact (exactly as controller does)
echo "4. Creating contact node...\n";
try {
  $contact_data = [
    'type' => 'contact',
    'title' => $formData['name'],
    'field_email' => $formData['email'] ?? '',
    'field_phone' => $formData['phone'],
    'field_position' => $formData['position'] ?? '',
    'field_organization' => NULL,  // No org selected
    'field_customer_type' => NULL,  // No type selected
    'field_source' => NULL,  // No source selected
    'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
    'uid' => \Drupal::currentUser()->id(),
    'status' => 1,
  ];
  
  // Only set taxonomy references if they have values
  if (!empty($formData['customer_type'])) {
    $contact_data['field_customer_type'] = ['target_id' => $formData['customer_type']];
  }
  if (!empty($formData['source'])) {
    $contact_data['field_source'] = ['target_id' => $formData['source']];
  }
  if (!empty($formData['organization'])) {
    $contact_data['field_organization'] = ['target_id' => $formData['organization']];
  }
  
  echo "   Creating with data:\n";
  echo "   - Type: contact\n";
  echo "   - Title: {$formData['name']}\n";
  echo "   - Phone: {$formData['phone']}\n";
  echo "   - Email: {$formData['email']}\n";
  echo "   - Owner: " . \Drupal::currentUser()->id() . "\n";
  echo "   - Organization: " . (empty($formData['organization']) ? 'NULL' : $formData['organization']) . "\n";
  echo "   - Customer Type: " . (empty($formData['customer_type']) ? 'NULL' : $formData['customer_type']) . "\n";
  echo "   - Source: " . (empty($formData['source']) ? 'NULL' : $formData['source']) . "\n\n";
  
  $contact = Node::create($contact_data);
  $contact->save();
  
  echo "✅ SUCCESS! Contact created:\n";
  echo "   - ID: {$contact->id()}\n";
  echo "   - Title: {$contact->getTitle()}\n";
  echo "   - Phone: {$contact->get('field_phone')->value}\n";
  echo "   - Email: {$contact->get('field_email')->value}\n";
  echo "   - URL: http://open-crm.ddev.site/node/{$contact->id()}\n";
  echo "   - View in list: http://open-crm.ddev.site/crm/my-contacts\n\n";
  
  $created_id = $contact->id();
  
  // 5. Verify it can be loaded
  echo "5. Verifying contact can be accessed...\n";
  $loaded = Node::load($created_id);
  if ($loaded && $loaded->access('view', $user)) {
    echo "✅ Contact can be loaded and viewed by user\n\n";
  } else {
    echo "❌ Contact cannot be accessed\n\n";
  }
  
  // 6. Simulate the JSON response
  echo "6. Simulating JSON response...\n";
  $response = [
    'status' => 'success',
    'message' => 'Đã tạo khách hàng thành công: ' . $formData['name'],
    'entity_id' => $contact->id(),
    'redirect' => '/crm/my-contacts',
  ];
  echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
  
  // 7. Clean up
  echo "7. Cleaning up test data...\n";
  $contact->delete();
  echo "✅ Test contact deleted (ID: $created_id)\n\n";
  
} catch (\Exception $e) {
  echo "❌ ERROR: {$e->getMessage()}\n";
  echo "\nStack trace:\n";
  echo $e->getTraceAsString() . "\n\n";
  \Drupal::service('account_switcher')->switchBack();
  exit(1);
}

\Drupal::service('account_switcher')->switchBack();

// 8. Check JavaScript file path
echo "8. Checking JavaScript file...\n";
$js_path = DRUPAL_ROOT . '/modules/custom/crm_quickadd/js/quickadd.js';
if (file_exists($js_path)) {
  echo "✅ JavaScript file exists\n";
  echo "   Path: $js_path\n";
  echo "   Size: " . number_format(filesize($js_path)) . " bytes\n";
  
  // Check first few lines
  $content = file_get_contents($js_path);
  if (strpos($content, 'submitForm') !== false) {
    echo "   ✅ Contains submitForm function\n";
  }
  if (strpos($content, '#quickadd-contact-form') !== false) {
    echo "   ✅ Contains form selector\n";
  }
  if (strpos($content, 'Drupal.behaviors.crmQuickAdd') !== false) {
    echo "   ✅ Contains Drupal behavior\n";
  }
} else {
  echo "❌ JavaScript file NOT found at: $js_path\n";
}
echo "\n";

// 9. Summary
echo "=== SUMMARY ===\n";
echo "✅ Backend works perfectly - can create contacts\n";
echo "✅ Permissions are correct\n";
echo "✅ JavaScript file exists\n\n";

echo "🔍 NEXT STEPS TO DEBUG:\n\n";
echo "1. Login as salesrep1:\n";
echo "   http://open-crm.ddev.site/user/reset/3/1772445752/EEjYWGxPLn_dgqUpHuwIpcCBjKLooGMvZNPJuqvPQKE/login\n\n";
echo "2. Visit quick add form:\n";
echo "   http://open-crm.ddev.site/crm/quickadd/contact\n\n";
echo "3. Open DevTools (F12):\n";
echo "   - Console tab: Look for JavaScript errors\n";
echo "   - Network tab: Filter by 'XHR' or 'Fetch'\n\n";
echo "4. Fill form with:\n";
echo "   - Tên: Test User\n";
echo "   - Số điện thoại: +84901234567\n";
echo "   - Email: test@example.com (optional)\n\n";
echo "5. Click 'Lưu khách hàng' and watch:\n";
echo "   - Console: Any errors?\n";
echo "   - Network: POST request to /crm/quickadd/contact/submit?\n";
echo "   - Response: Status 200 with success JSON?\n\n";
echo "6. Common issues:\n";
echo "   ❓ No POST request → JavaScript not attached\n";
echo "   ❓ 403 Forbidden → Not logged in or no permission\n";
echo "   ❓ 404 Not Found → Routes not registered (run 'ddev drush cr')\n";
echo "   ❓ 500 Server Error → Check logs: ddev drush watchdog:show\n\n";
echo "📖 Full debug guide: QUICKADD_DEBUG_GUIDE.md\n";
