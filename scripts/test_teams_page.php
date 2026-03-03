<?php

/**
 * @file
 * Test script to verify Teams Management page functionality.
 * 
 * Run with: ddev drush php:script test_teams_page
 */

use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;

echo "\n=== Testing Teams Management Page ===\n\n";

// 1. Check if crm_team vocabulary exists
echo "1. Checking crm_team vocabulary...\n";
$vocabulary = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_vocabulary')
  ->load('crm_team');

if ($vocabulary) {
  echo "   ✅ crm_team vocabulary exists: " . $vocabulary->label() . "\n";
} else {
  echo "   ❌ crm_team vocabulary NOT found!\n";
  exit(1);
}

// 2. Get all teams
echo "\n2. Checking teams...\n";
$teams = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_team']);

if (empty($teams)) {
  echo "   ⚠️  No teams found. Creating sample teams...\n";
  
  // Create sample teams
  $team_names = [
    ['name' => 'Sales Team A', 'description' => 'Team handling North region'],
    ['name' => 'Sales Team B', 'description' => 'Team handling South region'],
    ['name' => 'Manager Team', 'description' => 'Management and oversight'],
  ];
  
  foreach ($team_names as $team_data) {
    $term = Term::create([
      'vid' => 'crm_team',
      'name' => $team_data['name'],
      'description' => $team_data['description'],
    ]);
    $term->save();
    echo "   ✅ Created team: " . $team_data['name'] . "\n";
  }
  
  // Re-load teams
  $teams = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'crm_team']);
}

echo "   ✅ Total teams: " . count($teams) . "\n";
foreach ($teams as $team) {
  $user_count = \Drupal::entityQuery('user')
    ->condition('field_team', $team->id())
    ->accessCheck(FALSE)
    ->count()
    ->execute();
  
  echo "      - " . $team->getName() . " (ID: " . $team->id() . ", Users: " . $user_count . ")\n";
}

// 3. Check users
echo "\n3. Checking users...\n";
$users = \Drupal::entityTypeManager()
  ->getStorage('user')
  ->loadByProperties(['status' => 1]);

$active_users = [];
foreach ($users as $user) {
  if ($user->id() == 0 || $user->id() == 1) {
    continue; // Skip anonymous and admin
  }
  $active_users[] = $user;
}

if (empty($active_users)) {
  echo "   ⚠️  No regular users found (besides admin)\n";
} else {
  echo "   ✅ Total active users: " . count($active_users) . "\n";
  
  $assigned = 0;
  $unassigned = 0;
  
  foreach ($active_users as $user) {
    $has_team = $user->hasField('field_team') && !$user->get('field_team')->isEmpty();
    
    if ($has_team) {
      $assigned++;
      $team = $user->get('field_team')->entity;
      $team_name = $team ? $team->getName() : 'Unknown';
    } else {
      $unassigned++;
      $team_name = 'No Team';
    }
    
    if (count($active_users) <= 10) {
      echo "      - " . $user->getDisplayName() . " (" . $user->getEmail() . ") => " . $team_name . "\n";
    }
  }
  
  if (count($active_users) > 10) {
    echo "      (Showing stats only - too many users to list)\n";
  }
  
  echo "   📊 Assigned: $assigned, Unassigned: $unassigned\n";
}

// 4. Check if field_team exists on user entity
echo "\n4. Checking field_team configuration...\n";
$field_storage = \Drupal::entityTypeManager()
  ->getStorage('field_storage_config')
  ->load('user.field_team');

if ($field_storage) {
  echo "   ✅ field_team storage exists\n";
  
  $field_config = \Drupal::entityTypeManager()
    ->getStorage('field_config')
    ->load('user.user.field_team');
  
  if ($field_config) {
    echo "   ✅ field_team is attached to user entity\n";
    echo "      Label: " . $field_config->getLabel() . "\n";
  } else {
    echo "   ❌ field_team NOT attached to user entity!\n";
  }
} else {
  echo "   ❌ field_team storage NOT found!\n";
}

// 5. Check module and routing
echo "\n5. Checking module and routing...\n";
$module_handler = \Drupal::moduleHandler();
if ($module_handler->moduleExists('crm_teams')) {
  echo "   ✅ crm_teams module is enabled\n";
} else {
  echo "   ❌ crm_teams module is NOT enabled!\n";
  exit(1);
}

// Check if route exists
$route_provider = \Drupal::service('router.route_provider');
try {
  $route = $route_provider->getRouteByName('crm_teams.settings');
  echo "   ✅ Route 'crm_teams.settings' exists\n";
  echo "      Path: " . $route->getPath() . "\n";
} catch (\Exception $e) {
  echo "   ❌ Route 'crm_teams.settings' NOT found!\n";
}

// 6. Check permissions
echo "\n6. Checking permissions...\n";
$permissions = \Drupal::service('user.permissions')->getPermissions();

if (isset($permissions['administer crm teams'])) {
  echo "   ✅ Permission 'administer crm teams' exists\n";
} else {
  echo "   ❌ Permission 'administer crm teams' NOT found!\n";
}

if (isset($permissions['bypass crm team access'])) {
  echo "   ✅ Permission 'bypass crm team access' exists\n";
} else {
  echo "   ❌ Permission 'bypass crm team access' NOT found!\n";
}

// 7. Check admin user permissions
echo "\n7. Checking admin user access...\n";
$admin = User::load(1);
if ($admin && $admin->hasPermission('administer crm teams')) {
  echo "   ✅ Admin user can access teams management\n";
} else {
  echo "   ⚠️  Admin user doesn't have 'administer crm teams' permission\n";
  echo "      Granting permission to admin...\n";
  
  // Grant permission to administrator role
  $role = \Drupal\user\Entity\Role::load('administrator');
  if ($role) {
    $role->grantPermission('administer crm teams');
    $role->grantPermission('bypass crm team access');
    $role->save();
    echo "   ✅ Permissions granted to administrator role\n";
  }
}

// 8. Test TeamsManagementController
echo "\n8. Testing TeamsManagementController...\n";
try {
  $controller = \Drupal\crm_teams\Controller\TeamsManagementController::create(\Drupal::getContainer());
  $response = $controller->manageTeams();
  
  if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
    $content = $response->getContent();
    
    // Check if response contains expected elements
    $checks = [
      'Team Management' => strpos($content, 'Team Management') !== false,
      'total-teams' => strpos($content, 'total-teams') !== false,
      'assigned-users' => strpos($content, 'assigned-users') !== false,
      'users-grid' => strpos($content, 'users-grid') !== false,
      'team-list' => strpos($content, 'team-list') !== false,
      'JavaScript functions' => strpos($content, 'function renderTeams()') !== false,
    ];
    
    $all_passed = true;
    foreach ($checks as $check_name => $passed) {
      if ($passed) {
        echo "   ✅ $check_name found in response\n";
      } else {
        echo "   ❌ $check_name NOT found in response\n";
        $all_passed = false;
      }
    }
    
    if ($all_passed) {
      echo "\n   ✅ TeamsManagementController returns valid HTML\n";
    }
    
  } else {
    echo "   ❌ TeamsManagementController did not return a Response object\n";
  }
} catch (\Exception $e) {
  echo "   ❌ Error testing controller: " . $e->getMessage() . "\n";
}

// Final summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "Summary:\n";
echo "- Teams page URL: http://open-crm.ddev.site/admin/crm/teams\n";
echo "- Teams vocabulary: " . ($vocabulary ? '✅' : '❌') . "\n";
echo "- Number of teams: " . count($teams) . "\n";
echo "- Number of users: " . count($active_users) . "\n";
echo "- All checks passed: Review output above\n";
echo "\n✅ You can now visit: http://open-crm.ddev.site/admin/crm/teams\n";
echo str_repeat("=", 50) . "\n\n";

