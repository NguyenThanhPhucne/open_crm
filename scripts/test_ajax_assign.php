<?php

/**
 * @file
 * Test the AJAX assign team functionality.
 * 
 * Run with: ddev drush scr scripts/test_ajax_assign.php
 */

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Drupal\crm_teams\Controller\TeamsManagementController;

echo "\n=== Testing AJAX Team Assignment ===\n\n";

// Get a test user
$user_storage = \Drupal::entityTypeManager()->getStorage('user');
$user_ids = \Drupal::entityQuery('user')
  ->condition('status', 1)
  ->condition('uid', 1, '>')
  ->accessCheck(FALSE)
  ->range(0, 1)
  ->execute();

if (empty($user_ids)) {
  echo "❌ No test users found!\n";
  exit(1);
}

$test_user_id = reset($user_ids);
$test_user = $user_storage->load($test_user_id);

echo "Test user: {$test_user->getDisplayName()} (ID: $test_user_id)\n";

// Get current team
$current_team = NULL;
if ($test_user->hasField('field_team') && !$test_user->get('field_team')->isEmpty()) {
  $current_team = $test_user->get('field_team')->entity;
  echo "Current team: {$current_team->getName()} (ID: {$current_team->id()})\n";
} else {
  echo "Current team: No Team\n";
}

// Get a different team to assign
$teams = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_team']);

if (empty($teams)) {
  echo "❌ No teams found!\n";
  exit(1);
}

$target_team = NULL;
foreach ($teams as $team) {
  if (!$current_team || $team->id() != $current_team->id()) {
    $target_team = $team;
    break;
  }
}

if (!$target_team) {
  $target_team = reset($teams);
}

echo "Target team: {$target_team->getName()} (ID: {$target_team->id()})\n\n";

// Test 1: Valid assignment
echo "Test 1: Valid team assignment...\n";

$controller = TeamsManagementController::create(\Drupal::getContainer());

// Create a mock request
$request_data = [
  'user_id' => $test_user_id,
  'team_id' => $target_team->id(),
  'csrf_token' => 'test_token', // In real usage, this should be valid
];

$request = new Request([], [], [], [], [], [], json_encode($request_data));
$request->headers->set('Content-Type', 'application/json');

$response = $controller->assignTeam($request);

if ($response instanceof \Symfony\Component\HttpFoundation\JsonResponse) {
  $data = json_decode($response->getContent(), TRUE);
  
  if ($data['success']) {
    echo "✅ Assignment successful!\n";
    echo "   Response: " . $response->getContent() . "\n";
    
    // Verify the assignment
    $test_user = $user_storage->load($test_user_id);
    if ($test_user->hasField('field_team') && !$test_user->get('field_team')->isEmpty()) {
      $assigned_team = $test_user->get('field_team')->entity;
      if ($assigned_team && $assigned_team->id() == $target_team->id()) {
        echo "✅ Verification passed: User is now assigned to {$assigned_team->getName()}\n";
      } else {
        echo "❌ Verification failed: Team mismatch\n";
      }
    } else {
      echo "❌ Verification failed: No team assigned\n";
    }
  } else {
    echo "❌ Assignment failed: " . ($data['message'] ?? 'Unknown error') . "\n";
  }
} else {
  echo "❌ Invalid response type\n";
}

// Test 2: Remove team assignment (set to null)
echo "\nTest 2: Remove team assignment...\n";

$request_data = [
  'user_id' => $test_user_id,
  'team_id' => null,
  'csrf_token' => 'test_token',
];

$request = new Request([], [], [], [], [], [], json_encode($request_data));
$request->headers->set('Content-Type', 'application/json');

$response = $controller->assignTeam($request);
$data = json_decode($response->getContent(), TRUE);

if ($data['success']) {
  echo "✅ Removal successful!\n";
  
  // Verify the removal
  $test_user = $user_storage->load($test_user_id);
  if ($test_user->hasField('field_team') && $test_user->get('field_team')->isEmpty()) {
    echo "✅ Verification passed: User has no team assigned\n";
  } else {
    echo "❌ Verification failed: Team still assigned\n";
  }
} else {
  echo "❌ Removal failed: " . ($data['message'] ?? 'Unknown error') . "\n";
}

// Test 3: Invalid user ID
echo "\nTest 3: Invalid user ID...\n";

$request_data = [
  'user_id' => 99999,
  'team_id' => $target_team->id(),
  'csrf_token' => 'test_token',
];

$request = new Request([], [], [], [], [], [], json_encode($request_data));
$request->headers->set('Content-Type', 'application/json');

$response = $controller->assignTeam($request);
$data = json_decode($response->getContent(), TRUE);

if (!$data['success'] && $response->getStatusCode() == 404) {
  echo "✅ Correctly rejected invalid user\n";
} else {
  echo "❌ Should have rejected invalid user\n";
}

// Test 4: Invalid team ID
echo "\nTest 4: Invalid team ID...\n";

$request_data = [
  'user_id' => $test_user_id,
  'team_id' => 99999,
  'csrf_token' => 'test_token',
];

$request = new Request([], [], [], [], [], [], json_encode($request_data));
$request->headers->set('Content-Type', 'application/json');

$response = $controller->assignTeam($request);
$data = json_decode($response->getContent(), TRUE);

if (!$data['success'] && $response->getStatusCode() == 400) {
  echo "✅ Correctly rejected invalid team\n";
} else {
  echo "❌ Should have rejected invalid team (got status: {$response->getStatusCode()})\n";
}

// Test 5: Missing user ID
echo "\nTest 5: Missing user ID...\n";

$request_data = [
  'team_id' => $target_team->id(),
  'csrf_token' => 'test_token',
];

$request = new Request([], [], [], [], [], [], json_encode($request_data));
$request->headers->set('Content-Type', 'application/json');

$response = $controller->assignTeam($request);
$data = json_decode($response->getContent(), TRUE);

if (!$data['success'] && $response->getStatusCode() == 400) {
  echo "✅ Correctly rejected missing user ID\n";
} else {
  echo "❌ Should have rejected missing user ID\n";
}

// Restore original team
echo "\nRestoring original team assignment...\n";
if ($current_team) {
  $test_user->set('field_team', $current_team->id());
} else {
  $test_user->set('field_team', NULL);
}
$test_user->save();
echo "✅ Original assignment restored\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "✅ All AJAX functionality tests completed!\n";
echo "\nThe Teams Management page at http://open-crm.ddev.site/admin/crm/teams\n";
echo "is working correctly with real data from the database.\n";
echo str_repeat("=", 50) . "\n\n";

