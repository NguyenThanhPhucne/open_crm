<?php

/**
 * @file
 * Assign sample teams to users for testing.
 * 
 * Run with: ddev drush scr scripts/assign_sample_teams.php
 */

use Drupal\user\Entity\User;

echo "\n=== Assigning Sample Teams to Users ===\n\n";

// Get all teams
$teams = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_team']);

if (empty($teams)) {
  echo "❌ No teams found! Please create teams first.\n";
  exit(1);
}

$teams_array = [];
foreach ($teams as $team) {
  $teams_array[$team->id()] = $team->getName();
}

echo "Available teams:\n";
foreach ($teams_array as $id => $name) {
  echo "  - $name (ID: $id)\n";
}
echo "\n";

// Get all active users (except anonymous and admin)
$user_storage = \Drupal::entityTypeManager()->getStorage('user');
$user_ids = \Drupal::entityQuery('user')
  ->condition('status', 1)
  ->condition('uid', 1, '>')
  ->accessCheck(FALSE)
  ->execute();

$users = $user_storage->loadMultiple($user_ids);

if (empty($users)) {
  echo "❌ No users found to assign teams!\n";
  exit(1);
}

echo "Assigning teams to users...\n\n";

// Assign teams to users
$assigned_count = 0;
$team_ids = array_keys($teams_array);

foreach ($users as $user) {
  // Check if user already has a team
  $current_team = NULL;
  if ($user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
    $current_team = $user->get('field_team')->entity;
  }
  
  // Assign a team based on user role or name
  $roles = $user->getRoles(TRUE);
  $team_id = NULL;
  
  if (in_array('manager', $roles)) {
    // Assign managers to Manager Team
    $team_id = array_search('Manager Team', $teams_array);
  } elseif (in_array('sales_rep', $roles) || strpos(strtolower($user->getAccountName()), 'salesrep') !== false) {
    // Assign sales reps to Sales Teams (distribute evenly)
    $sales_teams = array_filter($teams_array, function($name) {
      return strpos($name, 'Sales Team') !== false;
    });
    if (!empty($sales_teams)) {
      // Use user ID to distribute across teams
      $sales_team_ids = array_keys($sales_teams);
      $team_id = $sales_team_ids[$user->id() % count($sales_team_ids)];
    }
  }
  
  // If no specific team, assign to a random team
  if (!$team_id && !empty($team_ids)) {
    $team_id = $team_ids[$user->id() % count($team_ids)];
  }
  
  // Assign the team
  if ($team_id) {
    $user->set('field_team', $team_id);
    $user->save();
    
    $team_name = $teams_array[$team_id];
    echo "✅ Assigned {$user->getDisplayName()} ({$user->getEmail()}) to $team_name\n";
    $assigned_count++;
  }
}

echo "\n✅ Successfully assigned $assigned_count users to teams.\n";

// Display final statistics
echo "\n=== Final Team Assignments ===\n\n";

foreach ($teams as $team) {
  $user_count = \Drupal::entityQuery('user')
    ->condition('field_team', $team->id())
    ->accessCheck(FALSE)
    ->count()
    ->execute();
  
  echo $team->getName() . ": $user_count members\n";
  
  if ($user_count > 0) {
    // List members
    $member_ids = \Drupal::entityQuery('user')
      ->condition('field_team', $team->id())
      ->accessCheck(FALSE)
      ->execute();
    
    $members = $user_storage->loadMultiple($member_ids);
    foreach ($members as $member) {
      echo "  - {$member->getDisplayName()} ({$member->getEmail()})\n";
    }
  }
  echo "\n";
}

echo "✅ Team assignments complete! Visit http://open-crm.ddev.site/admin/crm/teams to see the results.\n\n";

