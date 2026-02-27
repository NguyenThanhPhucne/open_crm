#!/bin/bash

# Create Pipeline Stage vocabulary and terms
echo "Creating Pipeline Stage taxonomy..."
ddev drush eval '$vocab = \Drupal\taxonomy\Entity\Vocabulary::load("pipeline_stage"); if (!$vocab) { $vocab = \Drupal\taxonomy\Entity\Vocabulary::create(["vid" => "pipeline_stage", "name" => "Pipeline Stage", "description" => "Stages of the sales pipeline"]); $vocab->save(); echo "Created vocabulary: Pipeline Stage\n"; } else { echo "Vocabulary Pipeline Stage already exists\n"; }'

ddev drush eval '$terms = ["New", "Qualified", "Proposal", "Negotiation", "Won", "Lost"]; foreach ($terms as $weight => $name) { $existing = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "pipeline_stage", "name" => $name]); if (empty($existing)) { $term = \Drupal\taxonomy\Entity\Term::create(["vid" => "pipeline_stage", "name" => $name, "weight" => $weight]); $term->save(); echo "  - Created term: $name\n"; } else { echo "  - Term already exists: $name\n"; } }'

# Create Lead Source vocabulary and terms
echo -e "\nCreating Lead Source taxonomy..."
ddev drush eval '$vocab = \Drupal\taxonomy\Entity\Vocabulary::load("lead_source"); if (!$vocab) { $vocab = \Drupal\taxonomy\Entity\Vocabulary::create(["vid" => "lead_source", "name" => "Lead Source", "description" => "Sources of leads"]); $vocab->save(); echo "Created vocabulary: Lead Source\n"; } else { echo "Vocabulary Lead Source already exists\n"; }'

ddev drush eval '$terms = ["Website", "Referral", "Event", "Call"]; foreach ($terms as $weight => $name) { $existing = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "lead_source", "name" => $name]); if (empty($existing)) { $term = \Drupal\taxonomy\Entity\Term::create(["vid" => "lead_source", "name" => $name, "weight" => $weight]); $term->save(); echo "  - Created term: $name\n"; } else { echo "  - Term already exists: $name\n"; } }'

# Create Activity Type vocabulary and terms
echo -e "\nCreating Activity Type taxonomy..."
ddev drush eval '$vocab = \Drupal\taxonomy\Entity\Vocabulary::load("activity_type"); if (!$vocab) { $vocab = \Drupal\taxonomy\Entity\Vocabulary::create(["vid" => "activity_type", "name" => "Activity Type", "description" => "Types of activities"]); $vocab->save(); echo "Created vocabulary: Activity Type\n"; } else { echo "Vocabulary Activity Type already exists\n"; }'

ddev drush eval '$terms = ["Call", "Email", "Meeting", "Task"]; foreach ($terms as $weight => $name) { $existing = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "activity_type", "name" => $name]); if (empty($existing)) { $term = \Drupal\taxonomy\Entity\Term::create(["vid" => "activity_type", "name" => $name, "weight" => $weight]); $term->save(); echo "  - Created term: $name\n"; } else { echo "  - Term already exists: $name\n"; } }'

echo -e "\n✅ All taxonomies created successfully!"
