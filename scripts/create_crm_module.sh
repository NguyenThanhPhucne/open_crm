#!/bin/bash

echo "🔐 TẠO CRM ACCESS CONTROL MODULE"
echo "=================================="
echo ""

MODULE_DIR="web/modules/custom/crm"

# Create module directory
echo "📁 Tạo module directory..."
mkdir -p $MODULE_DIR

# Create .info.yml
echo "📝 Tạo crm.info.yml..."
cat > $MODULE_DIR/crm.info.yml << 'EOF'
name: 'CRM Core'
type: module
description: 'Core CRM functionality: access control, data privacy, owner tracking'
package: CRM
core_version_requirement: ^10 || ^11
dependencies:
  - drupal:node
  - drupal:user
  - drupal:views
EOF

# Create .module file with hooks
echo "📝 Tạo crm.module..."
cat > $MODULE_DIR/crm.module << 'EOF'
<?php

/**
 * @file
 * CRM Core module - Access control and data privacy.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_node_access().
 *
 * Controls access to CRM content based on field_owner/field_assigned_to.
 * - Administrators: Full access
 * - Sales Managers: Can view/edit all team content
 * - Sales Representatives: Can only view/edit their own content
 */
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $crm_types = ['contact', 'deal', 'activity', 'organization'];

  // Only apply to CRM content types.
  if (!in_array($node->bundle(), $crm_types)) {
    return AccessResult::neutral();
  }

  // Administrators and managers: full access.
  if ($account->hasRole('administrator') || $account->hasRole('sales_manager')) {
    return AccessResult::neutral();
  }

  // Sales representatives: check ownership.
  if ($account->hasRole('sales_representative')) {
    $owner_field = $node->bundle() === 'activity' ? 'field_assigned_to' : 'field_owner';

    if (!$node->hasField($owner_field)) {
      return AccessResult::neutral();
    }

    $owner_id = $node->get($owner_field)->target_id;

    // Check operations.
    if (in_array($op, ['view', 'update', 'delete'])) {
      if ($owner_id == $account->id()) {
        return AccessResult::allowed()
          ->cachePerUser()
          ->addCacheableDependency($node);
      }
      else {
        return AccessResult::forbidden('You can only access your own content')
          ->cachePerUser()
          ->addCacheableDependency($node);
      }
    }
  }

  return AccessResult::neutral();
}

/**
 * Implements hook_query_TAG_alter() for 'node_access'.
 *
 * Automatically filters node queries for sales representatives
 * to show only their own content in views and entity queries.
 */
function crm_query_node_access_alter(\Drupal\Core\Database\Query\AlterableInterface $query) {
  $account = \Drupal::currentUser();

  // Skip for admins and managers.
  if ($account->hasRole('administrator') || $account->hasRole('sales_manager')) {
    return;
  }

  // Only filter for sales representatives.
  if (!$account->hasRole('sales_representative')) {
    return;
  }

  // Add owner filter.
  $tables = $query->getTables();
  foreach ($tables as $table) {
    if (isset($table['table']) && $table['table'] === 'node_field_data') {
      $alias = $table['alias'];

      // Left join with field_owner and field_assigned_to.
      $query->leftJoin('node__field_owner', 'crm_owner', "$alias.nid = crm_owner.entity_id");
      $query->leftJoin('node__field_assigned_to', 'crm_assigned', "$alias.nid = crm_assigned.entity_id");

      // Filter: current user is owner OR assigned_to.
      $or = $query->orConditionGroup()
        ->condition('crm_owner.field_owner_target_id', $account->id(), '=')
        ->condition('crm_assigned.field_assigned_to_target_id', $account->id(), '=');

      $query->condition($or);
      break;
    }
  }
}

/**
 * Implements hook_form_alter().
 *
 * Sets default owner = current user when creating new CRM content.
 */
function crm_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Only apply to node forms.
  if (strpos($form_id, 'node_') !== 0) {
    return;
  }

  $form_object = $form_state->getFormObject();
  if (!method_exists($form_object, 'getEntity')) {
    return;
  }

  $node = $form_object->getEntity();

  // Only for new nodes.
  if (!$node->isNew()) {
    return;
  }

  $crm_types = ['contact', 'deal', 'organization'];
  $current_user = \Drupal::currentUser();

  // Set field_owner for Contact, Deal, Organization.
  if (in_array($node->bundle(), $crm_types)) {
    if (isset($form['field_owner'])) {
      $form['field_owner']['widget'][0]['target_id']['#default_value'] =
        \Drupal\user\Entity\User::load($current_user->id());
    }
  }

  // Set field_assigned_to for Activity.
  if ($node->bundle() === 'activity') {
    if (isset($form['field_assigned_to'])) {
      $form['field_assigned_to']['widget'][0]['target_id']['#default_value'] =
        \Drupal\user\Entity\User::load($current_user->id());
    }
  }
}

/**
 * Implements hook_node_presave().
 *
 * Ensures owner is set when saving CRM content.
 */
function crm_node_presave(NodeInterface $node) {
  $crm_types = ['contact', 'deal', 'organization'];
  
  // Check if this is CRM content.
  if (!in_array($node->bundle(), $crm_types) && $node->bundle() !== 'activity') {
    return;
  }

  $current_user = \Drupal::currentUser();

  // Set owner for Contact, Deal, Organization if empty.
  if (in_array($node->bundle(), $crm_types)) {
    if ($node->hasField('field_owner') && $node->get('field_owner')->isEmpty()) {
      $node->set('field_owner', $current_user->id());
    }
  }

  // Set assigned_to for Activity if empty.
  if ($node->bundle() === 'activity') {
    if ($node->hasField('field_assigned_to') && $node->get('field_assigned_to')->isEmpty()) {
      $node->set('field_assigned_to', $current_user->id());
    }
  }
}
EOF

# Create .install file
echo "📝 Tạo crm.install..."
cat > $MODULE_DIR/crm.install << 'EOF'
<?php

/**
 * @file
 * Install, update and uninstall functions for CRM module.
 */

/**
 * Implements hook_install().
 */
function crm_install() {
  \Drupal::messenger()->addStatus(
    t('CRM Core module installed successfully. Access control and data privacy are now active.')
  );
}

/**
 * Implements hook_uninstall().
 */
function crm_uninstall() {
  \Drupal::messenger()->addStatus(
    t('CRM Core module uninstalled. Access control has been removed.')
  );
}
EOF

echo ""
echo "✅ Module files created!"
echo ""
echo "📂 Files created:"
echo "   $MODULE_DIR/crm.info.yml"
echo "   $MODULE_DIR/crm.module"
echo "   $MODULE_DIR/crm.install"
echo ""
echo "🔧 Enable module:"
echo "   ddev drush en crm -y"
echo "   ddev drush cr"
echo ""
echo "🧪 Test access control:"
echo "   1. Login as salesrep1"
echo "   2. Try to view contact owned by salesrep2"
echo "   3. Should see 'Access Denied'"
echo ""
echo "✨ Features:"
echo "   ✅ hook_node_access() - Per-node access control"
echo "   ✅ hook_query_alter() - Auto-filter views"
echo "   ✅ hook_form_alter() - Default owner = current user"
echo "   ✅ hook_node_presave() - Ensure owner is set"
echo ""
