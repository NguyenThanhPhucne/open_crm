#!/bin/bash

echo "🔐 UPDATE PERMISSIONS - VIEW OWN CONTENT ONLY"
echo "=============================================="
echo ""

echo "📋 1. Sales Representative Permissions..."
ddev drush eval "
\$role = \Drupal::entityTypeManager()->getStorage('user_role')->load('sales_representative');
if (\$role) {
  // Content permissions - chỉ xem own content
  \$permissions = [
    // Contact permissions
    'create contact content',
    'edit own contact content',
    'delete own contact content',
    'view own unpublished contact content',
    
    // Deal permissions
    'create deal content',
    'edit own deal content',
    'delete own deal content',
    'view own unpublished deal content',
    
    // Activity permissions
    'create activity content',
    'edit own activity content',
    'delete own activity content',
    'view own unpublished activity content',
    
    // Organization permissions
    'create organization content',
    'edit own organization content',
    'delete own organization content',
    'view own unpublished organization content',
    
    // General permissions
    'access content',
    'access content overview',
    'view published content',
    'use text format basic_html',
    'access user profiles',
  ];
  
  foreach (\$permissions as \$permission) {
    \$role->grantPermission(\$permission);
  }
  \$role->save();
  
  echo '✅ Sales Representative permissions updated' . PHP_EOL;
  echo '   - Can create/edit/delete OWN content only' . PHP_EOL;
} else {
  echo '❌ Sales Representative role not found!' . PHP_EOL;
}
"

echo ""

echo "📋 2. Sales Manager Permissions..."
ddev drush eval "
\$role = \Drupal::entityTypeManager()->getStorage('user_role')->load('sales_manager');
if (\$role) {
  // Manager có thể xem và edit content của TOÀN BỘ team
  \$permissions = [
    // Contact permissions - ALL
    'create contact content',
    'edit any contact content',
    'delete any contact content',
    'view any unpublished contact content',
    
    // Deal permissions - ALL
    'create deal content',
    'edit any deal content',
    'delete any deal content',
    'view any unpublished deal content',
    
    // Activity permissions - ALL
    'create activity content',
    'edit any activity content',
    'delete any activity content',
    'view any unpublished activity content',
    
    // Organization permissions - ALL
    'create organization content',
    'edit any organization content',
    'delete any organization content',
    'view any unpublished organization content',
    
    // General permissions
    'access content',
    'access content overview',
    'view published content',
    'use text format basic_html',
    'use text format full_html',
    'access user profiles',
    'access toolbar',
    
    // View all team members
    'access user contact forms',
  ];
  
  foreach (\$permissions as \$permission) {
    \$role->grantPermission(\$permission);
  }
  \$role->save();
  
  echo '✅ Sales Manager permissions updated' . PHP_EOL;
  echo '   - Can view/edit/delete ALL team content' . PHP_EOL;
} else {
  echo '❌ Sales Manager role not found!' . PHP_EOL;
}
"

echo ""

echo "🔧 3. Fix node access to check field_owner..."
echo "   (Cần custom module để implement hook_node_access)"
cat > /tmp/crm_access_control.php << 'EOF'
<?php

/**
 * Implements hook_node_access().
 * 
 * Sales Representatives chỉ xem được content mà họ là owner.
 * Sales Managers xem được tất cả content.
 */
function crm_node_access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  $types = ['contact', 'deal', 'activity', 'organization'];
  
  // Chỉ áp dụng cho CRM content types
  if (!in_array($node->bundle(), $types)) {
    return \Drupal\Core\Access\AccessResultNeutral::neutral();
  }
  
  // Admin và Manager: full access
  if ($account->hasRole('administrator') || $account->hasRole('sales_manager')) {
    return \Drupal\Core\Access\AccessResultNeutral::neutral();
  }
  
  // Sales Representative: chỉ access own content
  if ($account->hasRole('sales_representative')) {
    $owner_field = $node->bundle() === 'activity' ? 'field_assigned_to' : 'field_owner';
    
    if ($node->hasField($owner_field)) {
      $owner_id = $node->get($owner_field)->target_id;
      
      if ($op === 'view' || $op === 'update' || $op === 'delete') {
        if ($owner_id == $account->id()) {
          return \Drupal\Core\Access\AccessResultAllowed::allowed()->cachePerUser()->addCacheableDependency($node);
        } else {
          return \Drupal\Core\Access\AccessResultForbidden::forbidden('You can only access your own content')->cachePerUser()->addCacheableDependency($node);
        }
      }
    }
  }
  
  return \Drupal\Core\Access\AccessResultNeutral::neutral();
}

/**
 * Implements hook_query_TAG_alter() for 'node_access'.
 * 
 * Filter node queries để Sales Reps chỉ thấy own content trong views.
 */
function crm_query_node_access_alter(Drupal\Core\Database\Query\AlterableInterface $query) {
  $account = \Drupal::currentUser();
  
  // Chỉ filter cho Sales Representatives
  if (!$account->hasRole('sales_representative') || $account->hasRole('administrator') || $account->hasRole('sales_manager')) {
    return;
  }
  
  // Thêm JOIN với field_owner hoặc field_assigned_to
  $tables = $query->getTables();
  foreach ($tables as $table) {
    if (isset($table['table']) && $table['table'] === 'node_field_data') {
      $alias = $table['alias'];
      
      // Join với field_owner
      $query->leftJoin('node__field_owner', 'nfo', "$alias.nid = nfo.entity_id");
      $query->leftJoin('node__field_assigned_to', 'nfa', "$alias.nid = nfa.entity_id");
      
      // Filter: owner = current user OR assigned_to = current user
      $or = $query->orConditionGroup()
        ->condition('nfo.field_owner_target_id', $account->id(), '=')
        ->condition('nfa.field_assigned_to_target_id', $account->id(), '=');
      
      $query->condition($or);
      break;
    }
  }
}
EOF

echo "✅ Created access control logic (file: /tmp/crm_access_control.php)"
echo "   ⚠️  Cần tạo custom module 'crm' và copy code này vào"

echo ""

echo "📊 4. Verify permissions..."
ddev drush eval "
\$roles = ['sales_representative', 'sales_manager'];
foreach (\$roles as \$role_id) {
  \$role = \Drupal::entityTypeManager()->getStorage('user_role')->load(\$role_id);
  if (\$role) {
    \$perms = \$role->getPermissions();
    echo '\\n' . \$role->label() . ' (' . \$role_id . '):' . PHP_EOL;
    echo '  Permissions count: ' . count(\$perms) . PHP_EOL;
    
    \$content_types = ['contact', 'deal', 'activity', 'organization'];
    foreach (\$content_types as \$type) {
      \$has_create = in_array('create ' . \$type . ' content', \$perms);
      \$has_edit_own = in_array('edit own ' . \$type . ' content', \$perms);
      \$has_edit_any = in_array('edit any ' . \$type . ' content', \$perms);
      
      echo '  ' . ucfirst(\$type) . ': ';
      echo (\$has_create ? '✅ create ' : '  ');
      echo (\$has_edit_own ? '✅ edit-own ' : '  ');
      echo (\$has_edit_any ? '✅ edit-any' : '');
      echo PHP_EOL;
    }
  }
}
"

echo ""
echo "✅ HOÀN THÀNH!"
echo ""
echo "📌 ĐASET UP:"
echo "   ✅ Sales Representative: edit own content only"
echo "   ✅ Sales Manager: edit any content"
echo ""
echo "⚠️  CẦN THÊM:"
echo "   1. Tạo custom module 'crm'"
echo "   2. Copy code từ /tmp/crm_access_control.php"
echo "   3. Enable module: ddev drush en crm"
echo "   4. Clear cache"
echo ""
echo "📖 HOW IT WORKS:"
echo "   - Drupal permissions: create/edit/delete own vs any"
echo "   - hook_node_access: kiểm tra field_owner khi view/edit node"
echo "   - hook_query_alter: filter views để chỉ hiển thị own content"
echo "   - Manager bypass tất cả filters"
