#!/bin/bash

###############################################################################
# Script: create_roles_and_permissions.sh
# Purpose: Create user roles and configure permissions (ROLE-01, ROLE-02)
# Tasks:
#   - ROLE-01: Create Sales Manager, Sales Rep, Customer roles
#   - ROLE-02: Configure content permissions for each role
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Creating User Roles and Permissions"
echo "========================================="

# ============================================
# ROLE-01: Create Roles
# ============================================
echo ""
echo "👥 [ROLE-01] Creating User Roles..."

ddev drush php-eval '
$roles = [
  "sales_manager" => "Sales Manager",
  "sales_representative" => "Sales Representative",
  "customer" => "Customer",
];

foreach ($roles as $id => $label) {
  $role = \Drupal\user\Entity\Role::load($id);
  if (!$role) {
    $role = \Drupal\user\Entity\Role::create([
      "id" => $id,
      "label" => $label,
      "weight" => 2,
    ]);
    $role->save();
    echo "✅ Created role: $label ($id)\n";
  } else {
    echo "⚠️  Role already exists: $label ($id)\n";
  }
}
'

# ============================================
# ROLE-02: Configure Permissions
# ============================================
echo ""
echo "🔐 [ROLE-02] Configuring Content Permissions..."

ddev drush php-eval '
// Sales Representative Permissions
$sales_representative = \Drupal\user\Entity\Role::load("sales_representative");
if ($sales_representative) {
  $permissions = [
    // Basic access
    "access content",
    "access toolbar",
    "access administration pages",
    
    // Contact permissions
    "create contact content",
    "edit own contact content",
    "delete own contact content",
    "view own unpublished contact content",
    
    // Deal permissions
    "create deal content",
    "edit own deal content",
    "delete own deal content",
    "view own unpublished deal content",
    
    // Activity permissions
    "create activity content",
    "edit own activity content",
    "delete own activity content",
    "view own unpublished activity content",
    
    // Organization - view only
    "view any organization content",
  ];
  
  foreach ($permissions as $permission) {
    $sales_representative->grantPermission($permission);
  }
  $sales_representative->save();
  echo "✅ Configured permissions for Sales Representative\n";
  echo "   - Can create/edit/delete own Contacts, Deals, Activities\n";
  echo "   - Can view Organizations\n";
}

// Sales Manager Permissions
$sales_manager = \Drupal\user\Entity\Role::load("sales_manager");
if ($sales_manager) {
  $permissions = [
    // Basic access
    "access content",
    "access toolbar",
    "access administration pages",
    "view the administration theme",
    
    // Contact permissions
    "create contact content",
    "edit own contact content",
    "edit any contact content",
    "delete own contact content",
    "delete any contact content",
    "view own unpublished contact content",
    "view any unpublished contact content",
    
    // Deal permissions
    "create deal content",
    "edit own deal content",
    "edit any deal content",
    "delete own deal content",
    "delete any deal content",
    "view own unpublished deal content",
    "view any unpublished deal content",
    
    // Activity permissions
    "create activity content",
    "edit own activity content",
    "edit any activity content",
    "delete own activity content",
    "delete any activity content",
    "view own unpublished activity content",
    "view any unpublished activity content",
    
    // Organization permissions
    "create organization content",
    "edit own organization content",
    "edit any organization content",
    "delete own organization content",
    "view any organization content",
    "view own unpublished organization content",
    
    // Views access
    "access content overview",
  ];
  
  foreach ($permissions as $permission) {
    $sales_manager->grantPermission($permission);
  }
  $sales_manager->save();
  echo "✅ Configured permissions for Sales Manager\n";
  echo "   - Can create/edit/delete ANY content\n";
  echo "   - Full access to all CRM entities\n";
}

// Customer Permissions (Read-only)
$customer = \Drupal\user\Entity\Role::load("customer");
if ($customer) {
  $permissions = [
    // Basic access
    "access content",
    
    // View only permissions
    "view own contact content",
    "view own deal content",
    "view own activity content",
  ];
  
  foreach ($permissions as $permission) {
    $customer->grantPermission($permission);
  }
  $customer->save();
  echo "✅ Configured permissions for Customer\n";
  echo "   - Read-only access to own content\n";
}
'

echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ Roles and Permissions Configured!"
echo "========================================="
echo ""
echo "👥 Created Roles:"
echo "   1. Sales Manager - Full CRM access"
echo "   2. Sales Rep - Edit own content only"
echo "   3. Customer - Read-only access"
echo ""
echo "🔐 Permission Summary:"
echo ""
echo "   Sales Rep:"
echo "     ✓ Create/Edit/Delete own Contacts"
echo "     ✓ Create/Edit/Delete own Deals"
echo "     ✓ Create/Edit/Delete own Activities"
echo "     ✓ View Organizations"
echo ""
echo "   Sales Manager:"
echo "     ✓ Create/Edit/Delete ANY Contacts"
echo "     ✓ Create/Edit/Delete ANY Deals"
echo "     ✓ Create/Edit/Delete ANY Activities"
echo "     ✓ Create/Edit Organizations"
echo "     ✓ Access admin pages and content overview"
echo ""
echo "   Customer:"
echo "     ✓ View own Contacts, Deals, Activities"
echo ""
echo "📋 Manage roles and permissions at:"
echo "   - Roles: http://open-crm.ddev.site/admin/people/roles"
echo "   - Permissions: http://open-crm.ddev.site/admin/people/permissions"
echo ""
