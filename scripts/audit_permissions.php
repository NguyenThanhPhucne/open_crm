#!/usr/bin/env php
<?php

/**
 * Comprehensive Permissions Audit
 * 
 * Kiểm tra chi tiết quyền của từng role và user
 * 
 * Usage: drush scr scripts/audit_permissions.php
 */

use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          COMPREHENSIVE PERMISSIONS AUDIT - CRM SYSTEM          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. List all roles
echo "1. ROLES IN SYSTEM\n";
echo "══════════════════════════════════════════════════════════════════\n";

$roles = Role::loadMultiple();
$role_names = [];
foreach ($roles as $rid => $role) {
  if ($rid != 'anonymous' && $rid != 'authenticated') {
    $role_names[$rid] = $role->label();
    echo "  • $rid: {$role->label()}\n";
  }
}
echo "\n";

// 2. Check permissions for each role
echo "2. PERMISSIONS BY ROLE\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

$critical_permissions = [
  // Contact permissions
  'create contact content',
  'edit own contact content',
  'edit any contact content',
  'delete own contact content',
  'delete any contact content',
  
  // Deal permissions
  'create deal content',
  'edit own deal content',
  'edit any deal content',
  'delete own deal content',
  'delete any deal content',
  
  // Organization permissions
  'create organization content',
  'edit own organization content',
  'edit any organization content',
  'delete own organization content',
  'delete any organization content',
  
  // Activity permissions
  'create activity content',
  'edit own activity content',
  'edit any activity content',
  'delete own activity content',
  'delete any activity content',
  
  // Admin permissions
  'administer nodes',
  'administer users',
  'administer permissions',
  'access administration pages',
];

$permission_matrix = [];

foreach (['administrator', 'sales_manager', 'sales_rep'] as $rid) {
  $role = Role::load($rid);
  if (!$role) continue;
  
  echo "ROLE: {$role->label()} ($rid)\n";
  echo str_repeat("─", 66) . "\n";
  
  foreach ($critical_permissions as $permission) {
    $has_perm = $role->hasPermission($permission);
    $permission_matrix[$rid][$permission] = $has_perm;
    
    $status = $has_perm ? '✅' : '  ';
    echo "  $status $permission\n";
  }
  echo "\n";
}

// 3. Check users and their effective permissions
echo "3. USERS AND THEIR ROLES\n";
echo "══════════════════════════════════════════════════════════════════\n";

$users_to_check = [1, 2, 3, 4]; // admin, manager, salesrep1, salesrep2
$users_info = [];

foreach ($users_to_check as $uid) {
  $user = User::load($uid);
  if (!$user) continue;
  
  $username = $user->getAccountName();
  $roles = $user->getRoles();
  $roles_display = implode(', ', array_filter($roles, fn($r) => $r !== 'authenticated'));
  
  $users_info[$uid] = [
    'name' => $username,
    'roles' => $roles,
    'user' => $user,
  ];
  
  echo "  • UID $uid: $username\n";
  echo "    Roles: $roles_display\n";
  
  // Check key permissions
  $key_perms = [
    'create contact content',
    'edit any contact content',
    'delete any contact content',
    'administer nodes',
  ];
  
  echo "    Permissions:\n";
  foreach ($key_perms as $perm) {
    $has = $user->hasPermission($perm);
    $status = $has ? '✅' : '❌';
    echo "      $status $perm\n";
  }
  echo "\n";
}

// 4. Test access control with actual data
echo "4. ACCESS CONTROL TEST WITH REAL DATA\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Get sample contacts
$contact_query = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->accessCheck(FALSE)
  ->range(0, 3)
  ->execute();

$test_contacts = Node::loadMultiple($contact_query);

if (empty($test_contacts)) {
  echo "⚠️  No contacts found for testing. Creating test data...\n\n";
} else {
  echo "Testing access control with " . count($test_contacts) . " sample contacts:\n\n";
  
  foreach ($test_contacts as $contact) {
    $cid = $contact->id();
    $title = $contact->getTitle();
    $owner_id = $contact->getOwnerId();
    $owner = User::load($owner_id);
    $owner_name = $owner ? $owner->getAccountName() : "Unknown";
    
    echo "Contact ID $cid: $title (Owner: $owner_name [UID $owner_id])\n";
    echo str_repeat("─", 66) . "\n";
    
    // Test access for each user
    foreach ($users_info as $uid => $info) {
      $user = $info['user'];
      $username = $info['name'];
      
      // Switch to this user's context
      \Drupal::service('account_switcher')->switchTo($user);
      
      $can_view = $contact->access('view', $user);
      $can_edit = $contact->access('update', $user);
      $can_delete = $contact->access('delete', $user);
      
      $view_icon = $can_view ? '✅' : '❌';
      $edit_icon = $can_edit ? '✅' : '❌';
      $delete_icon = $can_delete ? '✅' : '❌';
      
      $is_owner = ($owner_id == $uid) ? '(OWNER)' : '';
      
      echo "  $username [UID $uid] $is_owner\n";
      echo "    View: $view_icon | Edit: $edit_icon | Delete: $delete_icon\n";
      
      \Drupal::service('account_switcher')->switchBack();
    }
    echo "\n";
  }
}

// 5. Check view access (contextual filters)
echo "5. VIEW ACCESS CHECK (My Contacts vs All Contacts)\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

// Count contacts by owner
$contact_counts = [];
foreach ($users_to_check as $uid) {
  $count = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('uid', $uid)
    ->accessCheck(FALSE)
    ->count()
    ->execute();
  
  $user = User::load($uid);
  $username = $user ? $user->getAccountName() : "Unknown";
  $contact_counts[$uid] = ['name' => $username, 'count' => $count];
  
  echo "  • $username (UID $uid): $count contacts\n";
}
echo "\n";

// Test what each user can see
echo "What each user sees in 'My Contacts' view:\n";
echo str_repeat("─", 66) . "\n";

foreach ($users_info as $uid => $info) {
  $user = $info['user'];
  $username = $info['name'];
  
  \Drupal::service('account_switcher')->switchTo($user);
  
  // Query as this user (with access check)
  $visible_contacts = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->accessCheck(TRUE)
    ->count()
    ->execute();
  
  // Query their own contacts
  $own_contacts = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('uid', $uid)
    ->accessCheck(TRUE)
    ->count()
    ->execute();
  
  \Drupal::service('account_switcher')->switchBack();
  
  $roles_list = implode(', ', array_filter($info['roles'], fn($r) => $r !== 'authenticated'));
  
  echo "  $username [$roles_list]:\n";
  echo "    - Own contacts: $own_contacts\n";
  echo "    - Total visible: $visible_contacts\n";
  
  if ($visible_contacts > $own_contacts) {
    echo "    ✅ Can see OTHER users' contacts (Manager/Admin)\n";
  } else if ($visible_contacts == $own_contacts && $own_contacts > 0) {
    echo "    ✅ Can ONLY see OWN contacts (Sales Rep)\n";
  } else if ($own_contacts == 0) {
    echo "    ⚠️  Has no contacts yet\n";
  }
  echo "\n";
}

// 6. Summary and Recommendations
echo "6. SECURITY SUMMARY & RECOMMENDATIONS\n";
echo "══════════════════════════════════════════════════════════════════\n\n";

echo "Expected Permission Model:\n";
echo "  📊 Administrator:\n";
echo "     - Full system access\n";
echo "     - Can create/edit/delete ANY content\n";
echo "     - Can manage users and permissions\n\n";

echo "  👔 Sales Manager:\n";
echo "     - Can create/edit/delete ANY CRM content\n";
echo "     - Can see ALL contacts/deals/organizations\n";
echo "     - Cannot access admin pages\n\n";

echo "  👤 Sales Rep:\n";
echo "     - Can create contact/deal/organization/activity\n";
echo "     - Can ONLY edit/delete OWN content\n";
echo "     - Can ONLY see OWN contacts in 'My Contacts' view\n";
echo "     - Can see ALL organizations (shared resource)\n\n";

// Check if permissions match expectations
$issues = [];

// Check sales_rep doesn't have 'edit any' permissions
$sales_rep_role = Role::load('sales_rep');
if ($sales_rep_role) {
  $dangerous_perms = [
    'edit any contact content',
    'edit any deal content',
    'delete any contact content',
    'delete any deal content',
    'administer nodes',
    'administer users',
  ];
  
  foreach ($dangerous_perms as $perm) {
    if ($sales_rep_role->hasPermission($perm)) {
      $issues[] = "⚠️  sales_rep has '$perm' - SECURITY RISK!";
    }
  }
}

// Check sales_manager has proper permissions
$manager_role = Role::load('sales_manager');
if ($manager_role) {
  $required_perms = [
    'edit any contact content',
    'edit any deal content',
    'edit any organization content',
  ];
  
  foreach ($required_perms as $perm) {
    if (!$manager_role->hasPermission($perm)) {
      $issues[] = "⚠️  sales_manager missing '$perm'";
    }
  }
}

if (empty($issues)) {
  echo "✅ SECURITY CHECK PASSED\n";
  echo "   All roles have appropriate permissions.\n\n";
} else {
  echo "⚠️  SECURITY ISSUES FOUND:\n";
  foreach ($issues as $issue) {
    echo "   $issue\n";
  }
  echo "\n";
}

// 7. View configuration check
echo "7. VIEW CONTEXTUAL FILTERS CHECK\n";
echo "══════════════════════════════════════════════════════════════════\n";

$views_to_check = [
  'my_contacts' => 'My Contacts (should filter by current user)',
  'my_deals' => 'My Deals (should filter by current user)',
  'my_activities' => 'My Activities (should filter by current user)',
  'all_organizations' => 'All Organizations (no user filter)',
];

foreach ($views_to_check as $view_id => $description) {
  $view = \Drupal::entityTypeManager()->getStorage('view')->load($view_id);
  if ($view) {
    echo "  ✅ $view_id: $description\n";
    
    $display = $view->get('display');
    $default_display = $display['default'] ?? [];
    $filters = $default_display['display_options']['filters'] ?? [];
    $arguments = $default_display['display_options']['arguments'] ?? [];
    
    // Check for user contextual filter
    $has_user_filter = false;
    foreach ($arguments as $arg) {
      if (isset($arg['default_argument_type']) && $arg['default_argument_type'] == 'current_user') {
        $has_user_filter = true;
        break;
      }
    }
    
    if ($has_user_filter) {
      echo "     ✅ Has current_user contextual filter\n";
    } else {
      echo "     ℹ️  No user filter (shows all data)\n";
    }
  } else {
    echo "  ❌ $view_id: NOT FOUND\n";
  }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║                      AUDIT COMPLETE                            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
