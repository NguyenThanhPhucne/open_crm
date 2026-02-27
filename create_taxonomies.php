<?php

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

// Create Pipeline Stage vocabulary
$vocabulary = Vocabulary::create([
  'vid' => 'pipeline_stage',
  'name' => 'Pipeline Stage',
  'description' => 'Stages of the sales pipeline',
]);
$vocabulary->save();
echo "Created vocabulary: Pipeline Stage\n";

// Create terms for Pipeline Stage
$terms = ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'];
foreach ($terms as $weight => $term_name) {
  $term = Term::create([
    'vid' => 'pipeline_stage',
    'name' => $term_name,
    'weight' => $weight,
  ]);
  $term->save();
  echo "  - Created term: $term_name\n";
}

// Create Lead Source vocabulary
$vocabulary = Vocabulary::create([
  'vid' => 'lead_source',
  'name' => 'Lead Source',
  'description' => 'Sources of leads',
]);
$vocabulary->save();
echo "Created vocabulary: Lead Source\n";

// Create terms for Lead Source
$terms = ['Website', 'Referral', 'Event', 'Call'];
foreach ($terms as $weight => $term_name) {
  $term = Term::create([
    'vid' => 'lead_source',
    'name' => $term_name,
    'weight' => $weight,
  ]);
  $term->save();
  echo "  - Created term: $term_name\n";
}

// Create Activity Type vocabulary
$vocabulary = Vocabulary::create([
  'vid' => 'activity_type',
  'name' => 'Activity Type',
  'description' => 'Types of activities',
]);
$vocabulary->save();
echo "Created vocabulary: Activity Type\n";

// Create terms for Activity Type
$terms = ['Call', 'Email', 'Meeting', 'Task'];
foreach ($terms as $weight => $term_name) {
  $term = Term::create([
    'vid' => 'activity_type',
    'name' => $term_name,
    'weight' => $weight,
  ]);
  $term->save();
  echo "  - Created term: $term_name\n";
}

echo "\nAll taxonomies created successfully!\n";
