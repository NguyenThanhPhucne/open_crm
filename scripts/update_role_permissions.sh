#!/bin/bash

###############################################################################
# Script: update_role_permissions.sh
# Purpose: Update role permissions with valid permissions only
# Issue: Previous script had non-existent permissions
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Updating Role Permissions"
echo "========================================="

ddev drush php-eval '
// Sales Representative Permissions - Edit own content only
$sales_representative = \Drupal\user\Entity\Role::load("sales_representative");
if ($sales_representative) {
  // Clear existing permissions first
  foreach ($sales_representative->getPermissions() as $perm) {
    $sales_representative->revokePermission($perm);
  }
  
  $permissions = [
    // Basic access
    "access content",
    "access toolbar",
    
    // Contact permissions
    "create contact content",
    "edit own contact content",
    "delete own contact content",
    
    // Deal permissions
    "create deal content",
    "edit own deal content",
    "delete own deal content",
    
    // Activity permissions
    "create activity content",
    "edit own activity content",
    "delete own activity content",
    
    // Organization - can view and create but not edit others
    "create organization content",
    "edit own organization content",
    "delete own organization content",
  ];
  
  foreach ($permissions as $permission) {
    $sales_representative->grantPermission($permission);
  }
  $sales_representative->save();
  echo "✅ Sales Representative permissions updated\n";
}

// Sales Manager Permissions - Edit any content
$sales_manager = \Drupal\user\Entity\Role::load("sales_manager");
if ($sales_manager) {
  // Clear existing permissions first
  foreach ($sales_manager->getPermissions() as $perm) {
    $sales_manager->revokePermission($perm);
  }
  
  $permissions = [
    // Basic access
    "access content",
    "access toolbar",
    "access administration pages",
    "view the administration theme",
    "access content overview",
    
    // Contact permissions - full control
    "create contact content",
    "edit own contact content",
    "edit any contact content",
    "delete own contact content",
    "delete any contact content",
    "view contact revisions",
    "revert contact revisions",
    
    // Deal permissions - full control
    "create deal content",
    "edit own deal content",
    "edit any deal content",
    "delete own deal content",
    "delete any deal content",
    "view deal revisions",
    "revert deal revisions",
    "configure editable deal node layout overrides",
    
    // Activity permissions - full control
    "create activity content",
    "edit own activity content",
    "edit any activity content",
    "delete own activity content",
    "delete any activity content",
    "view activity revisions",
    "revert activity revisions",
    
    // Organization permissions - full control
    "create organization content",
    "edit own organization content",
    "edit any organization content",
    "delete own organization content",
    "delete any organization content",
    "view organization revisions",
    "revert organization revisions",
  ];
  
  foreach ($permissions as $permission) {
    $sales_manager->grantPermission($permission);
  }
  $sales_manager->save();
  echo "✅ Sales Manager permissions updated\n";
}

// Customer Permissions - Read-only via content access
$customer = \Drupal\user\Entity\Role::load("customer");
if ($customer) {
  // Clear existing permissions first
  foreach ($customer->getPermissions() as $perm) {
    $customer->revokePermission($perm);
  }
  
  $permissions = [
    // Basic access only
    "access content",
  ];
  
  foreach ($permissions as $permission) {
    $customer->grantPermission($permission);
  }
  $customer->save();
  echo "✅ Customer permissions updated\n";
}
'

echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ Role Permissions Updated!"
echo "========================================="
echo ""
echo "🔐 Updated Permission Matrix:"
echo ""
echo "   📊 Sales Rep (Edit Own Only):"
echo "     ✓ Create/Edit/Delete OWN Contacts"
echo "     ✓ Create/Edit/Delete OWN Deals"
echo "     ✓ Create/Edit/Delete OWN Activities"
echo "     ✓ Create/Edit/Delete OWN Organizations"
echo "     ✓ Access toolbar"
echo ""
echo "   👔 Sales Manager (Full Control):"
echo "     ✓ Create/Edit/Delete ANY Contacts"
echo "     ✓ Create/Edit/Delete ANY Deals"
echo "     ✓ Create/Edit/Delete ANY Activities"
echo "     ✓ Create/Edit/Delete ANY Organizations"
echo "     ✓ View/Revert revisions"
echo "     ✓ Configure Deal layouts"
echo "     ✓ Access admin pages"
echo ""
echo "   👤 Customer (Read Only):"
echo "     ✓ Access content (basic view)"
echo ""
echo "📋 Verify at: http://open-crm.ddev.site/admin/people/permissions"
echo ""
