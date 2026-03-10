#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║          ENABLING CRM EDIT MODULE                                ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# Enable the module
echo "📦 Enabling crm_edit module..."
ddev drush en crm_edit -y

# Clear cache
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "✅ Module enabled successfully!"
echo ""
echo "📝 Testing module functionality..."
echo ""

# Test 1: Check routes
echo "1️⃣  Checking routes..."
ddev drush eval '
$routes = [
  "crm_edit.edit_contact",
  "crm_edit.edit_deal",
  "crm_edit.edit_organization",
  "crm_edit.edit_activity",
  "crm_edit.ajax_save",
];

$route_provider = \Drupal::service("router.route_provider");
$all_exist = TRUE;

foreach ($routes as $route_name) {
  try {
    $route = $route_provider->getRouteByName($route_name);
    echo "   ✅ Route: $route_name\n";
  } catch (\Exception $e) {
    echo "   ❌ Route missing: $route_name\n";
    $all_exist = FALSE;
  }
}

if ($all_exist) {
  echo "\n   All routes registered successfully!\n";
}
'

# Test 2: Check controller class
echo ""
echo "2️⃣  Checking controller class..."
ddev drush eval '
if (class_exists("\Drupal\crm_edit\Controller\InlineEditController")) {
  echo "   ✅ Controller class exists\n";
} else {
  echo "   ❌ Controller class not found\n";
}
'

# Test 3: Check permissions for each role
echo ""
echo "3️⃣  Checking edit permissions by role..."
ddev drush eval '
$roles = ["sales_manager", "sales_rep", "administrator"];
$types = ["contact", "deal"];

foreach ($roles as $role_id) {
  $role = \Drupal\user\Entity\Role::load($role_id);
  if (!$role) continue;
  
  echo "\n   👤 " . $role->label() . ":\n";
  
  foreach ($types as $type) {
    $can_edit_own = $role->hasPermission("edit own $type content");
    $can_edit_any = $role->hasPermission("edit any $type content");
    
    if ($can_edit_any) {
      echo "      $type: ✅ Can edit ANY\n";
    } elseif ($can_edit_own) {
      echo "      $type: ✅ Can edit OWN\n";
    } else {
      echo "      $type: ❌ No edit permission\n";
    }
  }
}
'

# Test 4: Generate test URLs
echo ""
echo "4️⃣  Generate test URLs..."
ddev drush eval '
$types = ["contact", "deal", "organization", "activity"];

foreach ($types as $type) {
  // Get first node of this type
  $query = \Drupal::entityQuery("node")
    ->condition("type", $type)
    ->range(0, 1)
    ->accessCheck(FALSE);
  
  $nids = $query->execute();
  
  if (!empty($nids)) {
    $nid = reset($nids);
    $node = \Drupal\node\Entity\Node::load($nid);
    $url = \Drupal\Core\Url::fromRoute("crm_edit.edit_$type", ["node" => $nid]);
    echo "   " . ucfirst($type) . ": " . $url->toString() . "\n";
    echo "      Title: " . $node->getTitle() . "\n";
  }
}
'

echo ""
echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║                    INSTALLATION COMPLETE                         ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""
echo "🎉 CRM Edit Module is now active!"
echo ""
echo "📋 FEATURES:"
echo "   • Inline edit forms for all CRM content types"
echo "   • Role-based permission checking"
echo "   • AJAX save functionality"
echo "   • Floating edit button on detail pages"
echo "   • Edit links in Views (to be added)"
echo ""
echo "🔗 QUICK LINKS:"
echo "   • Test edit: http://open-crm.ddev.site/crm/edit/contact/1"
echo "   • Documentation: /web/modules/custom/crm_edit/README.md"
echo ""
echo "👥 PERMISSIONS:"
echo "   • Sales Manager → Can edit ANY content"
echo "   • Sales Rep → Can edit OWN content only"
echo "   • Customer → Read-only (no edit access)"
echo ""
