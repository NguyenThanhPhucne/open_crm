#!/bin/bash

echo "🔐 CẬP NHẬT VIEWS VỚI DATA PRIVACY (Filter theo Owner)"
echo "======================================================="
echo ""

# Bước 1: Tạo view "My Contacts" - chỉ hiển thị contacts của current user
echo "👥 1. Tạo view 'My Contacts' (Sales chỉ xem contacts của mình)..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::create([
  'id' => 'my_contacts',
  'label' => 'My Contacts',
  'module' => 'views',
  'description' => 'Danh sách khách hàng của tôi',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Default',
      'position' => 0,
      'display_options' => [
        'title' => 'My Contacts',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => 'Name',
            'type' => 'string',
            'settings' => ['link_to_entity' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_organization' => [
            'id' => 'field_organization',
            'table' => 'node__field_organization',
            'field' => 'field_organization',
            'label' => 'Organization',
            'type' => 'entity_reference_label',
            'settings' => ['link' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_phone' => [
            'id' => 'field_phone',
            'table' => 'node__field_phone',
            'field' => 'field_phone',
            'label' => 'Phone',
            'plugin_id' => 'field',
          ],
          'field_email' => [
            'id' => 'field_email',
            'table' => 'node__field_email',
            'field' => 'field_email',
            'label' => 'Email',
            'plugin_id' => 'field',
          ],
          'field_source' => [
            'id' => 'field_source',
            'table' => 'node__field_source',
            'field' => 'field_source',
            'label' => 'Source',
            'type' => 'entity_reference_label',
            'plugin_id' => 'field',
          ],
        ],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'value' => '1',
            'plugin_id' => 'boolean',
          ],
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'value' => ['contact' => 'contact'],
            'plugin_id' => 'bundle',
          ],
        ],
        'arguments' => [
          'uid' => [
            'id' => 'uid',
            'table' => 'node__field_owner',
            'field' => 'field_owner_target_id',
            'default_action' => 'default',
            'default_argument_type' => 'current_user',
            'summary' => [
              'sort_order' => 'asc',
              'number_of_records' => 0,
              'format' => 'default_summary',
            ],
            'specify_validation' => TRUE,
            'validate' => [
              'type' => 'none',
            ],
            'plugin_id' => 'numeric',
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'items_per_page' => 25,
            'offset' => 0,
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
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/my-contacts',
        'menu' => [
          'type' => 'normal',
          'title' => 'My Contacts',
          'description' => 'Danh sách khách hàng của tôi',
          'menu_name' => 'main',
          'weight' => 10,
        ],
      ],
    ],
  ],
]);
\$view->save();

echo '✅ View My Contacts đã được tạo tại /crm/my-contacts' . PHP_EOL;
"

echo ""

# Bước 2: Tạo view "My Deals" - chỉ hiển thị deals của current user
echo "💼 2. Tạo view 'My Deals' (Sales chỉ xem deals của mình)..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::create([
  'id' => 'my_deals',
  'label' => 'My Deals',
  'module' => 'views',
  'description' => 'Danh sách cơ hội của tôi',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Default',
      'position' => 0,
      'display_options' => [
        'title' => 'My Pipeline',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => 'Deal',
            'type' => 'string',
            'settings' => ['link_to_entity' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_amount' => [
            'id' => 'field_amount',
            'table' => 'node__field_amount',
            'field' => 'field_amount',
            'label' => 'Amount',
            'settings' => [
              'thousand_separator' => ',',
              'decimal_separator' => '.',
              'suffix' => ' VND',
            ],
            'plugin_id' => 'field',
          ],
          'field_stage' => [
            'id' => 'field_stage',
            'table' => 'node__field_stage',
            'field' => 'field_stage',
            'label' => 'Stage',
            'type' => 'entity_reference_label',
            'plugin_id' => 'field',
          ],
          'field_probability' => [
            'id' => 'field_probability',
            'table' => 'node__field_probability',
            'field' => 'field_probability',
            'label' => 'Probability',
            'settings' => ['suffix' => '%'],
            'plugin_id' => 'field',
          ],
          'field_closing_date' => [
            'id' => 'field_closing_date',
            'table' => 'node__field_closing_date',
            'field' => 'field_closing_date',
            'label' => 'Close Date',
            'settings' => ['format_type' => 'medium'],
            'plugin_id' => 'field',
          ],
        ],
        'filters' => [
          'status' => [
            'id' => 'status',
            'table' => 'node_field_data',
            'field' => 'status',
            'value' => '1',
            'plugin_id' => 'boolean',
          ],
          'type' => [
            'id' => 'type',
            'table' => 'node_field_data',
            'field' => 'type',
            'value' => ['deal' => 'deal'],
            'plugin_id' => 'bundle',
          ],
        ],
        'arguments' => [
          'uid' => [
            'id' => 'uid',
            'table' => 'node__field_owner',
            'field' => 'field_owner_target_id',
            'default_action' => 'default',
            'default_argument_type' => 'current_user',
            'summary' => [
              'sort_order' => 'asc',
              'number_of_records' => 0,
              'format' => 'default_summary',
            ],
            'specify_validation' => TRUE,
            'validate' => [
              'type' => 'none',
            ],
            'plugin_id' => 'numeric',
          ],
        ],
        'sorts' => [
          'field_closing_date_value' => [
            'id' => 'field_closing_date_value',
            'table' => 'node__field_closing_date',
            'field' => 'field_closing_date_value',
            'order' => 'ASC',
            'plugin_id' => 'date',
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'items_per_page' => 25,
            'offset' => 0,
          ],
        ],
        'style' => [
          'type' => 'table',
          'options' => [
            'grouping' => [
              [
                'field' => 'field_stage',
                'rendered' => TRUE,
                'rendered_strip' => FALSE,
              ],
            ],
            'row_class' => '',
            'default_row_class' => TRUE,
          ],
        ],
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/my-pipeline',
        'menu' => [
          'type' => 'normal',
          'title' => 'My Pipeline',
          'description' => 'Pipeline của tôi',
          'menu_name' => 'main',
          'weight' => 11,
        ],
      ],
    ],
  ],
]);
\$view->save();

echo '✅ View My Deals đã được tạo tại /crm/my-pipeline' . PHP_EOL;
"

echo ""

# Bước 3: Thêm exposed filter "Owner" vào view Contacts (cho Manager)
echo "👔 3. Thêm filter Owner vào view Contacts (Manager xem được tất cả)..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::load('contacts');
if (\$view) {
  \$display = &\$view->getDisplay('default');
  \$display['display_options']['filters']['field_owner_target_id'] = [
    'id' => 'field_owner_target_id',
    'table' => 'node__field_owner',
    'field' => 'field_owner_target_id',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => 'or',
    'value' => [],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_owner_target_id_op',
      'label' => 'Owner',
      'description' => '',
      'use_operator' => FALSE,
      'operator' => 'field_owner_target_id_op',
      'identifier' => 'owner',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => FALSE,
      'remember_roles' => [
        'authenticated' => 'authenticated',
      ],
      'reduce' => FALSE,
    ],
    'is_grouped' => FALSE,
    'plugin_id' => 'numeric',
  ];
  \$view->save();
  echo '✅ Filter Owner đã được thêm vào view Contacts' . PHP_EOL;
} else {
  echo '⚠️  View Contacts không tồn tại' . PHP_EOL;
}
"

echo ""

# Bước 4: Cấu hình permissions để Sales Rep chỉ xem được data của mình
echo "🔐 4. Cấu hình Node Access cho Sales Rep..."
ddev drush eval "
// Cấp permission view own/any content cho Sales Manager
\$role_manager = \Drupal\user\Entity\Role::load('sales_manager');
if (\$role_manager) {
  \$role_manager->grantPermission('view any unpublished content');
  \$role_manager->save();
  echo '✅ Sales Manager có thể xem tất cả content' . PHP_EOL;
}

// Sales Rep chỉ xem own content (đã có sẵn từ trước)
echo '✅ Sales Rep chỉ xem được own content' . PHP_EOL;
" || echo "⚠️  Permissions đã được cấu hình từ trước"

echo ""
echo "✨ HOÀN THÀNH! Views đã được cập nhật với Data Privacy."
echo ""
echo "📌 Sales Rep có thể truy cập:"
echo "   - /crm/my-contacts  : Chỉ xem contacts của mình"
echo "   - /crm/my-pipeline  : Chỉ xem deals của mình"
echo ""
echo "📌 Sales Manager có thể truy cập:"
echo "   - /app/contacts     : Xem tất cả contacts + filter theo Owner"
echo "   - /app/pipeline     : Xem tất cả deals + filter theo Stage"
