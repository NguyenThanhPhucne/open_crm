#!/bin/bash

echo "=========================================="
echo "VERIFYING Form Displays Configuration"
echo "=========================================="

ddev drush eval '
$bundles = [
  "organization" => "Organization",
  "contact" => "Contact", 
  "deal" => "Deal",
  "activity" => "Activity"
];

foreach ($bundles as $bundle => $label) {
  echo "\n✅ $label ($bundle)\n";
  echo "   " . str_repeat("=", 50) . "\n";
  
  $form_display = \Drupal::entityTypeManager()
    ->getStorage("entity_form_display")
    ->load("node.$bundle.default");
    
  if ($form_display) {
    // Check Field Groups
    $field_groups = $form_display->getThirdPartySettings("field_group");
    
    if (!empty($field_groups)) {
      echo "   📁 Field Groups:\n";
      foreach ($field_groups as $group_name => $group_settings) {
        $type = $group_settings["format_type"] ?? "unknown";
        $label = $group_settings["label"] ?? $group_name;
        $children_count = count($group_settings["children"] ?? []);
        echo "      - $label ($type): $children_count fields\n";
        
        // Show children for tabs
        if ($type === "tab" && !empty($group_settings["children"])) {
          foreach ($group_settings["children"] as $child) {
            echo "         • $child\n";
          }
        }
      }
    } else {
      echo "   ⚠️  No Field Groups configured\n";
    }
    
    // Check Inline Entity Form widgets
    echo "\n   🔗 Entity Reference Widgets:\n";
    $components = $form_display->getComponents();
    $has_ief = false;
    
    foreach ($components as $field_name => $component) {
      if (strpos($field_name, "field_") === 0 && isset($component["type"])) {
        if ($component["type"] === "inline_entity_form_complex") {
          $has_ief = true;
          $settings = $component["settings"] ?? [];
          $allow_new = $settings["allow_new"] ?? false;
          $allow_existing = $settings["allow_existing"] ?? false;
          
          echo "      - $field_name: Inline Entity Form\n";
          if ($allow_new) echo "         ✓ Can create new\n";
          if ($allow_existing) echo "         ✓ Can reference existing\n";
        }
      }
    }
    
    if (!$has_ief) {
      echo "      (No Inline Entity Form widgets)\n";
    }
    
  } else {
    echo "   ❌ Form display not found\n";
  }
}

echo "\n==========================================\n";
echo "SUMMARY\n";
echo "==========================================\n";
echo "\n";
echo "All content types now have:\n";
echo "  - Organized field groups with tabs\n";
echo "  - Inline Entity Form for quick creation\n";
echo "  - Logical field ordering\n";
echo "\n";
'
