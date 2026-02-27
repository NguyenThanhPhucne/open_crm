<?php

/**
 * Add local action links for adding content
 * Run with: ddev drush scr scripts/add_local_actions.php
 */

// Create/update routing file
$routes_content = <<<'YAML'
# Routes for CRM content types

crm.add_contact_route:
  path: '/crm/my-contacts'
  defaults:
    _controller: '\Drupal\views\Routing\ViewPageController::handle'
    view_id: 'my_contacts'
    display_id: 'page_1'
  requirements:
    _permission: 'create contact content'

crm.add_deal_route:
  path: '/crm/my-pipeline'
  defaults:
    _controller: '\Drupal\views\Routing\ViewPageController::handle'
    view_id: 'my_deals'
    display_id: 'page_1'
  requirements:
    _permission: 'create deal content'

crm.add_activity_route:
  path: '/crm/my-activities'
  defaults:
    _controller: '\Drupal\views\Routing\ViewPageController::handle'
    view_id: 'my_activities'
    display_id: 'page_1'
  requirements:
    _permission: 'create activity content'

crm.add_organization_route:
  path: '/crm/my-organizations'
  defaults:
    _controller: '\Drupal\views\Routing\ViewPageController::handle'
    view_id: 'my_organizations'
    display_id: 'page_1'
  requirements:
    _permission: 'create organization content'
YAML;

// Create links file
$links_content = <<<'YAML'
# Local actions for CRM

crm.add_contact:
  route_name: 'node.add'
  route_parameters:
    node_type: 'contact'
  title: 'Add Contact'
  appears_on:
    - crm.add_contact_route
  class: Drupal\Core\Menu\LocalActionDefault
  weight: 0

crm.add_deal:
  route_name: 'node.add'
  route_parameters:
    node_type: 'deal'
  title: 'Add Deal'
  appears_on:
    - crm.add_deal_route
  class: Drupal\Core\Menu\LocalActionDefault
  weight: 0

crm.add_activity:
  route_name: 'node.add'
  route_parameters:
    node_type: 'activity'
  title: 'Add Activity'
  appears_on:
    - crm.add_activity_route
  class: Drupal\Core\Menu\LocalActionDefault
  weight: 0

crm.add_organization:
  route_name: 'node.add'
  route_parameters:
    node_type: 'organization'
  title: 'Add Organization'
  appears_on:
    - crm.add_organization_route
  class: Drupal\Core\Menu\LocalActionDefault
  weight: 0
YAML;

$module_dir = 'web/modules/custom/crm_actions';
if (!is_dir($module_dir)) {
  mkdir($module_dir, 0755, true);
  echo "✅ Created module directory: $module_dir\n";
}

// Create .info.yml
$info_content = <<<'YAML'
name: 'CRM Actions'
type: module
description: 'Provides action buttons for CRM views'
core_version_requirement: ^11
package: 'CRM'
YAML;

file_put_contents("$module_dir/crm_actions.info.yml", $info_content);
echo "✅ Created crm_actions.info.yml\n";

file_put_contents("$module_dir/crm_actions.routing.yml", $routes_content);
echo "✅ Created crm_actions.routing.yml\n";

file_put_contents("$module_dir/crm_actions.links.action.yml", $links_content);
echo "✅ Created crm_actions.links.action.yml\n";

echo "\n✅ Module files created. Now enabling module...\n";

// Enable the module
\Drupal::service('module_installer')->install(['crm_actions']);
echo "✅ Module enabled!\n";

// Clear all caches
drupal_flush_all_caches();
echo "✅ Caches cleared!\n";

echo "\n✅ Action buttons should now appear at the top of each view!\n";
