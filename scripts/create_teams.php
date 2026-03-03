<?php

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

// Create team vocabulary if not exists
$vocabulary = Vocabulary::load('crm_team');

if (!$vocabulary) {
  $vocabulary = Vocabulary::create([
    'vid' => 'crm_team',
    'name' => 'CRM Teams',
    'description' => 'Sales teams for access control',
  ]);
  $vocabulary->save();
  echo "Created CRM Teams vocabulary\n";
} else {
  echo "CRM Teams vocabulary already exists\n";
}

// Create default teams
$teams = [
  'Sales Team A' => 'Primary sales team',
  'Sales Team B' => 'Secondary sales team',
  'Sales Team C' => 'Third sales team',
  'Manager Team' => 'Management team with full access',
];

foreach ($teams as $name => $description) {
  // Check if term already exists
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'crm_team', 'name' => $name]);
  
  if (empty($terms)) {
    $term = Term::create([
      'vid' => 'crm_team',
      'name' => $name,
      'description' => $description,
    ]);
    $term->save();
    echo "Created team: $name\n";
  } else {
    echo "Team already exists: $name\n";
  }
}

echo "\nAll teams created successfully!\n";
