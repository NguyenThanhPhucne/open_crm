<?php

/**
 * @file
 * Create All Organizations view for Managers at /app/organizations
 */

use Drupal\views\Entity\View;

// Create a comprehensive view for Managers to see ALL organizations
$view_config = [
  'id' => 'all_organizations',
  'label' => 'All Organizations',
  'module' => 'views',
  'description' => 'View all organizations - for Managers',
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
        'title' => 'All Organizations',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'field',
            'label' => 'Organization',
            'type' => 'string',
            'settings' => [
              'link_to_entity' => TRUE,
            ],
          ],
          'field_industry' => [
            'id' => 'field_industry',
            'table' => 'node__field_industry',
            'field' => 'field_industry',
            'plugin_id' => 'field',
            'label' => 'Industry',
            'type' => 'entity_reference_label',
          ],
          'field_website' => [
            'id' => 'field_website',
            'table' => 'node__field_website',
            'field' => 'field_website',
            'plugin_id' => 'field',
            'label' => 'Website',
            'type' => 'string',
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
          'field_assigned_staff' => [
            'id' => 'field_assigned_staff',
            'table' => 'node__field_assigned_staff',
            'field' => 'field_assigned_staff',
            'plugin_id' => 'field',
            'label' => 'Owner',
            'type' => 'entity_reference_label',
            'settings' => [
              'link' => TRUE,
            ],
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
            'exposed_sorts_label' => 'Sort by',
            'sort_asc_label' => 'Asc',
            'sort_desc_label' => 'Desc',
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
              'organization' => 'organization',
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
              'label' => 'Organization name',
              'identifier' => 'title',
              'required' => FALSE,
            ],
          ],
          'field_industry_target_id' => [
            'id' => 'field_industry_target_id',
            'table' => 'node__field_industry',
            'field' => 'field_industry_target_id',
            'plugin_id' => 'taxonomy_index_tid',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_industry_target_id_op',
              'label' => 'Industry',
              'identifier' => 'field_industry_target_id',
              'required' => FALSE,
              'reduce' => FALSE,
            ],
            'type' => 'select',
            'vocabulary' => 'industry',
          ],
          'field_assigned_staff_target_id' => [
            'id' => 'field_assigned_staff_target_id',
            'table' => 'node__field_assigned_staff',
            'field' => 'field_assigned_staff_target_id',
            'plugin_id' => 'user_name',
            'exposed' => TRUE,
            'expose' => [
              'operator_id' => 'field_assigned_staff_target_id_op',
              'label' => 'Owner',
              'identifier' => 'field_assigned_staff_target_id',
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
            'exposed' => TRUE,
            'expose' => [
              'label' => 'Created date',
            ],
          ],
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'plugin_id' => 'standard',
            'order' => 'ASC',
            'exposed' => TRUE,
            'expose' => [
              'label' => 'Organization name',
            ],
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
        'display_extenders' => [],
      ],
      'cache_metadata' => [
        'max-age' => -1,
        'contexts' => [
          'languages:language_content',
          'languages:language_interface',
          'url',
          'url.query_args',
          'user',
          'user.permissions',
        ],
      ],
    ],
    'page_1' => [
      'id' => 'page_1',
      'display_title' => 'Page',
      'display_plugin' => 'page',
      'position' => 1,
      'display_options' => [
        'path' => 'app/organizations',
        'menu' => [
          'type' => 'normal',
          'title' => 'Organizations',
          'description' => 'View all organizations',
          'weight' => 15,
          'menu_name' => 'main',
        ],
        'header' => [
          'area' => [
            'id' => 'area',
            'table' => 'views',
            'field' => 'area',
            'plugin_id' => 'text',
            'content' => [
              'value' => '<div style="text-align: right; margin-bottom: 20px;">
        <a href="/node/add/organization" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #ec4899;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          box-shadow: 0 2px 4px rgba(236, 72, 153, 0.2);
        " onmouseover="this.style.background=\'#db2777\'" onmouseout="this.style.background=\'#ec4899\'">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Add Organization
        </a>
      </div>',
              'format' => 'full_html',
            ],
          ],
        ],
        'display_extenders' => [],
      ],
      'cache_metadata' => [
        'max-age' => -1,
        'contexts' => [
          'languages:language_content',
          'languages:language_interface',
          'url',
          'url.query_args',
          'user',
          'user.permissions',
        ],
      ],
    ],
  ],
  'dependencies' => [
    'config' => [
      'field.storage.node.field_assigned_staff',
      'field.storage.node.field_email',
      'field.storage.node.field_industry',
      'field.storage.node.field_phone',
      'field.storage.node.field_website',
      'node.type.organization',
      'system.menu.main',
      'taxonomy.vocabulary.industry',
    ],
    'module' => [
      'node',
      'taxonomy',
      'user',
    ],
  ],
];

// Create the view
$view = View::create($view_config);
$view->save();

echo "✅ Created 'All Organizations' view\n\n";
echo "📊 View Details:\n";
echo "   ID: all_organizations\n";
echo "   Label: All Organizations\n";
echo "   Path: /app/organizations\n";
echo "   Description: View all organizations - for Managers\n\n";

echo "📋 Fields:\n";
echo "   - Organization (title) - links to node\n";
echo "   - Industry (taxonomy)\n";
echo "   - Website\n";
echo "   - Email\n";
echo "   - Phone\n";
echo "   - Owner (field_assigned_staff)\n";
echo "   - Created date\n\n";

echo "🔍 Exposed Filters:\n";
echo "   - Organization name (text search)\n";
echo "   - Industry (dropdown)\n";
echo "   - Owner (autocomplete)\n\n";

echo "📊 Pagination: 25 items per page\n\n";

echo "🔄 Clearing cache...\n";
drupal_flush_all_caches();
echo "✅ Cache cleared\n\n";

// Verify organization count
$count = \Drupal::entityQuery('node')
  ->condition('type', 'organization')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->count()
  ->execute();

echo "📊 Current data: {$count} organizations\n\n";
echo "🌐 Visit: http://open-crm.ddev.site/app/organizations\n";
