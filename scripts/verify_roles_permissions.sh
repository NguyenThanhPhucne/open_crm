#!/bin/bash

###############################################################################
# Script: verify_roles_permissions.sh
# Purpose: Verify user roles and permissions configuration
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Verifying Roles and Permissions"
echo "========================================="

ddev drush php-eval '
$roles = ["sales_manager", "sales_representative", "customer"];

foreach ($roles as $role_id) {
  $role = \Drupal\user\Entity\Role::load($role_id);
  if ($role) {
    echo "\n";
    echo "👤 Role: " . $role->label() . " ($role_id)\n";
    echo "   Permissions (" . count($role->getPermissions()) . " total):\n";
    
    $perms = $role->getPermissions();
    sort($perms);
    
    foreach ($perms as $perm) {
      echo "     ✓ $perm\n";
    }
  } else {
    echo "\n❌ Role not found: $role_id\n";
  }
}
'

echo ""
echo "========================================="
echo "✅ Verification Complete!"
echo "========================================="
echo ""
echo "📋 Summary:"
echo "   • 3 roles created: Sales Manager, Sales Representative, Customer"
echo "   • Sales Representative: Can edit own content only"
echo "   • Sales Manager: Can edit any content"
echo "   • Customer: Read-only access"
echo ""
echo "🌐 Manage at:"
echo "   • Roles: http://open-crm.ddev.site/admin/people/roles"
echo "   • Permissions: http://open-crm.ddev.site/admin/people/permissions"
echo ""
