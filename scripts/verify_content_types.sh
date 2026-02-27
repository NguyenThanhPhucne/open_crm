#!/bin/bash

echo "=========================================="
echo "VERIFYING Content Types and Fields"
echo "=========================================="

ddev drush eval '
$bundles = ["organization", "contact", "deal", "activity"];

foreach ($bundles as $bundle) {
  $type = \Drupal\node\Entity\NodeType::load($bundle);
  if ($type) {
    echo "\n✅ Content Type: " . $type->label() . " ($bundle)\n";
    
    // Get all fields for this bundle
    $entity_field_manager = \Drupal::service("entity_field.manager");
    $fields = $entity_field_manager->getFieldDefinitions("node", $bundle);
    
    echo "   Fields:\n";
    foreach ($fields as $field_name => $field) {
      if (strpos($field_name, "field_") === 0) {
        $field_type = $field->getType();
        $field_label = $field->getLabel();
        $is_required = $field->isRequired() ? " (Required)" : "";
        
        echo "   - $field_label ($field_name): $field_type$is_required\n";
        
        // Show target bundles for entity references
        if ($field_type === "entity_reference") {
          $settings = $field->getSettings();
          if (isset($settings["handler_settings"]["target_bundles"])) {
            $target_bundles = $settings["handler_settings"]["target_bundles"];
            echo "     → References: " . implode(", ", array_keys($target_bundles)) . "\n";
          }
        }
      }
    }
  } else {
    echo "\n❌ Content Type not found: $bundle\n";
  }
}

// Count total items
echo "\n========================================\n";
echo "SUMMARY:\n";
echo "========================================\n";

$storage = \Drupal::entityTypeManager()->getStorage("node");

foreach ($bundles as $bundle) {
  $count = $storage->getQuery()
    ->condition("type", $bundle)
    ->accessCheck(false)
    ->count()
    ->execute();
  
  $type = \Drupal\node\Entity\NodeType::load($bundle);
  if ($type) {
    echo "✅ " . $type->label() . ": $count items\n";
  }
}
'
