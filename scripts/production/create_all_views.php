<?php

/**
 * @file
 * Create "All Contacts" and "All Deals" views for Managers/Admins.
 * 
 * These complement the "My Contacts" and "My Deals" views used by sales reps.
 * 
 * Usage: ddev drush scr scripts/production/create_all_views.php
 */

use Drupal\views\Entity\View;

echo "\n================================================\n";
echo "CREATING MANAGER VIEWS\n";
echo "================================================\n\n";

// ================================================
// 1. ALL CONTACTS VIEW
// ================================================

echo "[1/2] Creating 'All Contacts' view...\n";

$all_contacts_config = [
  'id' => 'all_contacts',
  'label' => 'All Contacts',
  'module' => 'views',
  'description' => 'View all contacts - for Managers/Admins',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'base_field' => 'nid',
  'display' => [
    'default' => [
      'id' => 'default',
      'display_title' => 'Default',
      'display_plugin' => 'default',
      'position' => 0,
      'display_options' => [
        'title' => 'All Contacts',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'field',
            'label' => 'Name',
            'type' => 'string',
            'settings' => [
              'link_to_entity' => TRUE,
            ],
          ],
          'field_organization' => [
            'id' => 'field_organization',
            'table' => 'node__field_organization',
            'field' => 'field_organization',
            'plugin_id' => 'field',
            'label' => 'Organization',
            'type' => 'entity_reference_label',
            'settings' => [
              'link' => TRUE,
            ],
          ],
          'field_email' => [
            'id' => 'field_email',
            'table' => 'node__field_email',
            'field' => 'field_email',
            'plugin_id' => 'field',
            'label' => 'Email',
            'type' => 'email_mailto',
          ],
          'field_phone' => [
            'id' => 'field_phone',
            'table' => 'node__field_phone',
            'field' => 'field_phone',
            'plugin_id' => 'field',
            'label' => 'Phone',
            'type' => 'string',
          ],
          'field_owner' => [
            'id' => 'field_owner',
            'table' => 'node__field_owner',
            'field' => 'field_owner',
            'plugin_id' => 'field',
            'label' => 'Owner',
            'type' => 'entity_reference_label',
            'settings' => [
              'link' => TRUE,
            ],
          ],
          'field_source' => [
            'id' => 'field_source',
            'table' => 'node__field_source',
            'field' => 'field_source',
            'plugin_id' => 'field',
            'label' => 'Source',
            'type' => 'entity_reference_label',
          ],
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'plugin_id' => 'field',
            'label' => 'Created',
            'type' => 'timestamp',
            'settings' => [
              'date_format' => 'short',
            ],
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'offset' => 0,
            'items_per_page' => 25,
            'tags' => [
              'first' => '« First',
              'previous' => '‹ Previous',
              'next' => 'Next ›',
              'last' => 'Last »',
            ],
          ],
        ],
        'exposed_form' => [
          'type' => 'basic',
          'options' => [
            'submit_button' => 'Filter',
            'reset_button' => TRUE,
            'reset_button_label' => 'Reset',
          ],
        ],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'plugin_id' => 'boolean',
            'value' => '1',
            'group' => 1,
            'expose' => [
              'operator' => FALSE,
            ],
          ],
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'plugin_id' => 'bundle',
            'value' => [
              'contact' => 'contact',
            ],
          ],
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'string',
            'operator' => 'contains',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'title_op',
              'label' => 'Contact name',
              'identifier' => 'title',
              'required' => FALSE,
            ],
          ],
          'field_organization_target_id' => [
            'id' => 'field_organization_target_id',
            'table' => 'node__field_organization',
            'field' => 'field_organization_target_id',
            'plugin_id' => 'entity_reference',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_organization_target_id_op',
              'label' => 'Organization',
              'identifier' => 'field_organization_target_id',
              'required' => FALSE,
            ],
          ],
          'field_owner_target_id' => [
            'id' => 'field_owner_target_id',
            'table' => 'node__field_owner',
            'field' => 'field_owner_target_id',
            'plugin_id' => 'user_name',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_owner_target_id_op',
              'label' => 'Owner',
              'identifier' => 'field_owner_target_id',
              'required' => FALSE,
            ],
          ],
        ],
        'sorts' => [
          'created' => [
            'id' => 'created',
            'table' => 'node_field_data',
            'field' => 'created',
            'plugin_id' => 'date',
            'order' => 'DESC',
          ],
        ],
        'style' => [
          'type' => 'table',
          'options' => [
            'grouping' => [],
            'row_class' => '',
            'default_row_class' => TRUE,
          ],
        ],
        'row' => [
          'type' => 'fields',
        ],
        'cache' => [
          'type' => 'time',
          'options' => [
            'results_lifespan' => 1800,
            'output_lifespan' => 1800,
          ],
        ],
      ],
    ],
    'page_1' => [
      'id' => 'page_1',
      'display_title' => 'Page',
      'display_plugin' => 'page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/contacts',
        'menu' => [
          'type' => 'normal',
          'title' => 'All Contacts',
          'description' => 'View all contacts (Manager only)',
          'weight' => 10,
          'menu_name' => 'main',
        ],
        'access' => [
          'type' => 'role',
          'options' => [
            'role' => [
              'administrator' => 'administrator',
              'crm_manager' => 'crm_manager',
            ],
          ],
        ],
        'header' => [
          'area' => [
            'id' => 'area',
            'table' => 'views',
            'field' => 'area',
            'plugin_id' => 'text',
            'content' => [
              'value' => '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
          <p style="color: #666; margin: 0;">Viewing all contacts across the organization</p>
        </div>
        <a href="/node/add/contact" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #0ea5e9;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
        ">
          <span>+</span> Add Contact
        </a>
      </div>',
              'format' => 'full_html',
            ],
          ],
        ],
      ],
    ],
  ],
  'dependencies' => [
    'config' => [
      'field.storage.node.field_email',
      'field.storage.node.field_organization',
      'field.storage.node.field_owner',
      'field.storage.node.field_phone',
      'field.storage.node.field_source',
      'node.type.contact',
      'system.menu.main',
    ],
    'module' => [
      'node',
      'user',
    ],
  ],
];

// Check if view already exists
$existing_view = View::load('all_contacts');
if ($existing_view) {
  echo "   Deleting existing view...\n";
  $existing_view->delete();
}

// Create the view
$view = View::create($all_contacts_config);
$view->save();

echo "   ✅ Created 'All Contacts' view\n";
echo "   Path: /crm/contacts\n";
echo "   Access: Administrators and CRM Managers only\n\n";

// ================================================
// 2. ALL DEALS VIEW
// ================================================

echo "[2/2] Creating 'All Deals' view...\n";

$all_deals_config = [
  'id' => 'all_deals',
  'label' => 'All Deals',
  'module' => 'views',
  'description' => 'View all deals - for Managers/Admins',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'base_field' => 'nid',
  'display' => [
    'default' => [
      'id' => 'default',
      'display_title' => 'Default',
      'display_plugin' => 'default',
      'position' => 0,
      'display_options' => [
        'title' => 'All Deals',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'field',
            'label' => 'Deal Name',
            'type' => 'string',
            'settings' => [
              'link_to_entity' => TRUE,
            ],
          ],
          'field_organization' => [
            'id' => 'field_organization',
            'table' => 'node__field_organization',
            'field' => 'field_organization',
            'plugin_id' => 'field',
            'label' => 'Organization',
            'type' => 'entity_reference_label',
            'settings' => [
              'link' => TRUE,
            ],
          ],
          'field_amount' => [
            'id' => 'field_amount',
            'table' => 'node__field_amount',
            'field' => 'field_amount',
            'plugin_id' => 'field',
            'label' => 'Amount',
            'type' => 'number_decimal',
            'settings' => [
              'thousand_separator' => ',',
              'decimal_separator' => '.',
              'prefix_suffix' => TRUE,
            ],
          ],
          'field_stage' => [
            'id' => 'field_stage',
            'table' => 'node__field_stage',
            'field' => 'field_stage',
            'plugin_id' => 'field',
            'label' => 'Stage',
            'type' => 'entity_reference_label',
          ],
          'field_probability' => [
            'id' => 'field_probability',
            'table' => 'node__field_probability',
            'field' => 'field_probability',
            'plugin_id' => 'field',
            'label' => 'Probability',
            'type' => 'number_integer',
            'settings' => [
              'suffix' => '%',
            ],
          ],
          'field_closing_date' => [
            'id' => 'field_closing_date',
            'table' => 'node__field_closing_date',
            'field' => 'field_closing_date',
            'plugin_id' => 'field',
            'label' => 'Close Date',
            'type' => 'datetime_default',
            'settings' => [
              'format_type' => 'short',
            ],
          ],
          'field_owner' => [
            'id' => 'field_owner',
            'table' => 'node__field_owner',
            'field' => 'field_owner',
            'plugin_id' => 'field',
            'label' => 'Owner',
            'type' => 'entity_reference_label',
            'settings' => [
              'link' => TRUE,
            ],
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'offset' => 0,
            'items_per_page' => 25,
            'tags' => [
              'first' => '« First',
              'previous' => '‹ Previous',
              'next' => 'Next ›',
              'last' => 'Last »',
            ],
          ],
        ],
        'exposed_form' => [
          'type' => 'basic',
          'options' => [
            'submit_button' => 'Filter',
            'reset_button' => TRUE,
            'reset_button_label' => 'Reset',
          ],
        ],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'plugin_id' => 'boolean',
            'value' => '1',
            'group' => 1,
            'expose' => [
              'operator' => FALSE,
            ],
          ],
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'plugin_id' => 'bundle',
            'value' => [
              'deal' => 'deal',
            ],
          ],
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'string',
            'operator' => 'contains',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'title_op',
              'label' => 'Deal name',
              'identifier' => 'title',
              'required' => FALSE,
            ],
          ],
          'field_stage_target_id' => [
            'id' => 'field_stage_target_id',
            'table' => 'node__field_stage',
            'field' => 'field_stage_target_id',
            'plugin_id' => 'taxonomy_index_tid',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_stage_target_id_op',
              'label' => 'Stage',
              'identifier' => 'field_stage_target_id',
              'required' => FALSE,
              'reduce' => FALSE,
            ],
            'type' => 'select',
            'vocabulary' => 'pipeline_stage',
          ],
          'field_owner_target_id' => [
            'id' => 'field_owner_target_id',
            'table' => 'node__field_owner',
            'field' => 'field_owner_target_id',
            'plugin_id' => 'user_name',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_owner_target_id_op',
              'label' => 'Owner',
              'identifier' => 'field_owner_target_id',
              'required' => FALSE,
            ],
          ],
        ],
        'sorts' => [
          'field_closing_date_value' => [
            'id' => 'field_closing_date_value',
            'table' => 'node__field_closing_date',
            'field' => 'field_closing_date_value',
            'plugin_id' => 'date',
            'order' => 'DESC',
          ],
        ],
        'style' => [
          'type' => 'table',
          'options' => [
            'grouping' => [],
            'row_class' => '',
            'default_row_class' => TRUE,
          ],
        ],
        'row' => [
          'type' => 'fields',
        ],
        'cache' => [
          'type' => 'time',
          'options' => [
            'results_lifespan' => 900,
            'output_lifespan' => 900,
          ],
        ],
      ],
    ],
    'page_1' => [
      'id' => 'page_1',
      'display_title' => 'Page',
      'display_plugin' => 'page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/deals',
        'menu' => [
          'type' => 'normal',
          'title' => 'All Deals',
          'description' => 'View all deals (Manager only)',
          'weight' => 11,
          'menu_name' => 'main',
        ],
        'access' => [
          'type' => 'role',
          'options' => [
            'role' => [
              'administrator' => 'administrator',
              'crm_manager' => 'crm_manager',
            ],
          ],
        ],
        'header' => [
          'area' => [
            'id' => 'area',
            'table' => 'views',
            'field' => 'area',
            'plugin_id' => 'text',
            'content' => [
              'value' => '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
          <p style="color: #666; margin: 0;">Viewing all deals across the organization</p>
        </div>
        <a href="/node/add/deal" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #10b981;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
        ">
          <span>+</span> Add Deal
        </a>
      </div>',
              'format' => 'full_html',
            ],
          ],
        ],
      ],
    ],
  ],
  'dependencies' => [
    'config' => [
      'field.storage.node.field_amount',
      'field.storage.node.field_closing_date',
      'field.storage.node.field_organization',
      'field.storage.node.field_owner',
      'field.storage.node.field_probability',
      'field.storage.node.field_stage',
      'node.type.deal',
      'system.menu.main',
      'taxonomy.vocabulary.pipeline_stage',
    ],
    'module' => [
      'datetime',
      'node',
      'taxonomy',
      'user',
    ],
  ],
];

// Check if view already exists
$existing_view = View::load('all_deals');
if ($existing_view) {
  echo "   Deleting existing view...\n";
  $existing_view->delete();
}

// Create the view
$view = View::create($all_deals_config);
$view->save();

echo "   ✅ Created 'All Deals' view\n";
echo "   Path: /crm/deals\n";
echo "   Access: Administrators and CRM Managers only\n\n";

// ================================================
// SUMMARY
// ================================================

echo "================================================\n";
echo "SUMMARY\n";
echo "================================================\n\n";

echo "✅ Created 2 new manager views:\n\n";

echo "1. All Contacts\n";
echo "   URL: http://open-crm.ddev.site/crm/contacts\n";
echo "   Access: Admins & CRM Managers\n";
echo "   Cache: 30 min\n";
echo "   Features: Full text search, filters by organization/owner\n\n";

echo "2. All Deals\n";
echo "   URL: http://open-crm.ddev.site/crm/deals\n";
echo "   Access: Admins & CRM Managers\n";
echo "   Cache: 15 min\n";
echo "   Features: Full text search, filters by stage/owner\n\n";

echo "📝 Note:\n";
echo "   - These views show ALL records (organization-wide)\n";
echo "   - Sales reps should use /crm/my-contacts and /crm/my-deals\n";
echo "   - Access control enforced at view level (role-based)\n\n";

// Count records
$contacts_count = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->count()
  ->execute();

$deals_count = \Drupal::entityQuery('node')
  ->condition('type', 'deal')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->count()
  ->execute();

echo "📊 Current Data:\n";
echo "   - {$contacts_count} contacts\n";
echo "   - {$deals_count} deals\n\n";

echo "🔄 Clearing cache...\n";
drupal_flush_all_caches();
echo "✅ Cache cleared\n\n";

echo "✨ Done!\n\n";
