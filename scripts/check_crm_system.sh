#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║          CRM SYSTEM AUDIT REPORT                                 ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# Section 1: Content Types
echo "┌──────────────────────────────────────────────────────────────────┐"
echo "│ 1️⃣   CONTENT TYPES & DATA                                          │"
echo "└──────────────────────────────────────────────────────────────────┘"
echo ""

ddev drush eval '
$types = ["contact", "deal", "organization", "activity"];
foreach ($types as $type) {
  $node_type = \Drupal\node\Entity\NodeType::load($type);
  if ($node_type) {
    echo "✅ " . $node_type->label() . " ($type)\n";
    
    // Count records
    $query = \Drupal::entityQuery("node")
      ->condition("type", $type)
      ->accessCheck(FALSE);
    $count = $query->count()->execute();
    echo "   📊 Records: $count\n";
    
    // Count fields
    $fields = \Drupal::service("entity_field.manager")->getFieldDefinitions("node", $type);
    $custom_fields = 0;
    foreach ($fields as $field_name => $field) {
      if (strpos($field_name, "field_") === 0) {
        $custom_fields++;
      }
    }
    echo "   📝 Custom Fields: $custom_fields\n\n";
  }
}
'

# Section 2: Roles & Users
echo "┌──────────────────────────────────────────────────────────────────┐"
echo "│ 2️⃣   ROLES & USERS                                                 │"
echo "└──────────────────────────────────────────────────────────────────┘"
echo ""

ddev drush eval '
$roles = \Drupal\user\Entity\Role::loadMultiple();
foreach ($roles as $rid => $role) {
  if (in_array($rid, ["anonymous", "authenticated"])) continue;
  
  echo "👤 " . $role->label() . " ($rid)\n";
  
  // Count users
  $query = \Drupal::entityQuery("user")
    ->condition("roles", $rid)
    ->condition("status", 1)
    ->accessCheck(FALSE);
  $count = $query->count()->execute();
  echo "   👥 Active Users: $count\n";
  
  // Count permissions
  $perms = $role->getPermissions();
  echo "   🔒 Total Permissions: " . count($perms) . "\n";
  
  // Count CRM permissions
  $crm_perms = array_filter($perms, function($p) {
    return strpos($p, "contact") !== false || 
           strpos($p, "deal") !== false || 
           strpos($p, "organization") !== false ||
           strpos($p, "activity") !== false;
  });
  echo "   📋 CRM Permissions: " . count($crm_perms) . "\n\n";
}
'

# Section 3: Permissions Breakdown
echo "┌──────────────────────────────────────────────────────────────────┐"
echo "│ 3️⃣   PERMISSIONS BREAKDOWN                                         │"
echo "└──────────────────────────────────────────────────────────────────┘"
echo ""

ddev drush eval '
$roles = ["sales_manager", "sales_representative", "sales_rep"];
$types = ["contact", "deal", "organization", "activity"];

foreach ($roles as $rid) {
  $role = \Drupal\user\Entity\Role::load($rid);
  if (!$role) continue;
  
  echo "📊 " . $role->label() . ":\n";
  
  foreach ($types as $type) {
    $can_create = $role->hasPermission("create $type content");
    $can_edit_any = $role->hasPermission("edit any $type content");
    $can_edit_own = $role->hasPermission("edit own $type content");
    $can_delete_any = $role->hasPermission("delete any $type content");
    $can_delete_own = $role->hasPermission("delete own $type content");
    
    if ($can_create || $can_edit_any || $can_edit_own) {
      echo "   " . ucfirst($type) . ": ";
      $actions = [];
      if ($can_create) $actions[] = "Create";
      if ($can_edit_any) $actions[] = "Edit Any";
      elseif ($can_edit_own) $actions[] = "Edit Own";
      if ($can_delete_any) $actions[] = "Delete Any";
      elseif ($can_delete_own) $actions[] = "Delete Own";
      echo implode(", ", $actions) . "\n";
    }
  }
  echo "\n";
}
'

# Section 4: Ownership Fields
echo "┌──────────────────────────────────────────────────────────────────┐"
echo "│ 4️⃣   OWNERSHIP FIELDS                                              │"
echo "└──────────────────────────────────────────────────────────────────┘"
echo ""

ddev drush eval '
$ownership = [
  "contact" => "field_owner",
  "deal" => "field_owner",
  "organization" => "field_assigned_staff",
  "activity" => "field_assigned_to",
];

foreach ($ownership as $type => $field_name) {
  $field = \Drupal\field\Entity\FieldConfig::loadByName("node", $type, $field_name);
  
  if ($field) {
    echo "✅ " . ucfirst($type) . ": $field_name\n";
    
    // Count with owner
    $query = \Drupal::entityQuery("node")
      ->condition("type", $type)
      ->exists($field_name)
      ->accessCheck(FALSE);
    $with_owner = $query->count()->execute();
    
    // Count total
    $total_query = \Drupal::entityQuery("node")
      ->condition("type", $type)
      ->accessCheck(FALSE);
    $total = $total_query->count()->execute();
    
    $pct = $total > 0 ? round(($with_owner / $total) * 100, 1) : 0;
    echo "   📊 Coverage: $with_owner/$total ($pct%)\n\n";
  } else {
    echo "❌ " . ucfirst($type) . ": Missing $field_name\n\n";
  }
}
'

# Section 5: CRM Modules
echo "┌──────────────────────────────────────────────────────────────────┐"
echo "│ 5️⃣   CRM MODULES                                                   │"
echo "└──────────────────────────────────────────────────────────────────┘"
echo ""

ddev drush eval '
$modules = ["crm", "crm_teams", "crm_import_export", "crm_activity_log"];

foreach ($modules as $module) {
  $installed = \Drupal::moduleHandler()->moduleExists($module);
  if ($installed) {
    echo "✅ $module\n";
  } else {
    echo "❌ $module (not enabled)\n";
  }
}
'

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    AUDIT COMPLETE                                ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
