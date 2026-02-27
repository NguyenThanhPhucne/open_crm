#!/bin/bash

echo "📊 TẠO DASHBOARD CÁ NHÂN CHO SALES"
echo "===================================="
echo ""

# Bước 1: Tạo view Dashboard với summary blocks
echo "📈 1. Tạo Dashboard page..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::create([
  'id' => 'sales_dashboard',
  'label' => 'Sales Dashboard',
  'module' => 'views',
  'description' => 'Dashboard cá nhân cho Sales',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Default',
      'position' => 0,
      'display_options' => [
        'title' => 'My Dashboard',
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
          'field_close_date' => [
            'id' => 'field_close_date',
            'table' => 'node__field_close_date',
            'field' => 'field_close_date',
            'label' => 'Close Date',
            'settings' => ['format_type' => 'short'],
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
            'plugin_id' => 'numeric',
          ],
        ],
        'sorts' => [
          'field_close_date_value' => [
            'id' => 'field_close_date_value',
            'table' => 'node__field_close_date',
            'field' => 'field_close_date_value',
            'order' => 'ASC',
            'plugin_id' => 'date',
          ],
        ],
        'pager' => [
          'type' => 'some',
          'options' => [
            'items_per_page' => 5,
            'offset' => 0,
          ],
        ],
        'style' => [
          'type' => 'table',
        ],
        'header' => [
          'area_text_custom' => [
            'id' => 'area_text_custom',
            'table' => 'views',
            'field' => 'area_text_custom',
            'content' => '<h2>🎯 Deals sắp chốt</h2>',
            'plugin_id' => 'text_custom',
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
        'path' => 'crm/dashboard',
        'menu' => [
          'type' => 'normal',
          'title' => 'Dashboard',
          'description' => 'Trang chủ CRM',
          'menu_name' => 'main',
          'weight' => 0,
        ],
      ],
    ],
  ],
]);
\$view->save();

echo '✅ Dashboard đã được tạo tại /crm/dashboard' . PHP_EOL;
"

echo ""

# Bước 2: Tạo view "My Activities" - upcoming tasks
echo "📅 2. Tạo view 'My Activities'..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::create([
  'id' => 'my_activities',
  'label' => 'My Activities',
  'module' => 'views',
  'description' => 'Hoạt động của tôi',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Default',
      'position' => 0,
      'display_options' => [
        'title' => 'My Activities',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => 'Activity',
            'type' => 'string',
            'settings' => ['link_to_entity' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_activity_type' => [
            'id' => 'field_activity_type',
            'table' => 'node__field_activity_type',
            'field' => 'field_activity_type',
            'label' => 'Type',
            'type' => 'entity_reference_label',
            'plugin_id' => 'field',
          ],
          'field_deal' => [
            'id' => 'field_deal',
            'table' => 'node__field_deal',
            'field' => 'field_deal',
            'label' => 'Deal',
            'type' => 'entity_reference_label',
            'settings' => ['link' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_activity_date' => [
            'id' => 'field_activity_date',
            'table' => 'node__field_activity_date',
            'field' => 'field_activity_date',
            'label' => 'Date',
            'settings' => ['format_type' => 'short'],
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
            'value' => ['activity' => 'activity'],
            'plugin_id' => 'bundle',
          ],
        ],
        'arguments' => [
          'uid' => [
            'id' => 'uid',
            'table' => 'node__field_assigned_to',
            'field' => 'field_assigned_to_target_id',
            'default_action' => 'default',
            'default_argument_type' => 'current_user',
            'plugin_id' => 'numeric',
          ],
        ],
        'sorts' => [
          'field_activity_date_value' => [
            'id' => 'field_activity_date_value',
            'table' => 'node__field_activity_date',
            'field' => 'field_activity_date_value',
            'order' => 'DESC',
            'plugin_id' => 'date',
          ],
        ],
        'pager' => [
          'type' => 'full',
          'options' => [
            'items_per_page' => 20,
            'offset' => 0,
          ],
        ],
        'style' => [
          'type' => 'table',
        ],
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/my-activities',
        'menu' => [
          'type' => 'normal',
          'title' => 'My Activities',
          'description' => 'Hoạt động của tôi',
          'menu_name' => 'main',
          'weight' => 12,
        ],
      ],
    ],
  ],
]);
\$view->save();

echo '✅ View My Activities đã được tạo tại /crm/my-activities' . PHP_EOL;
"

echo ""

# Bước 3: Tạo view "My Organizations" - organizations assigned to me
echo "🏢 3. Tạo view 'My Organizations'..."
ddev drush eval "
\$view = \Drupal\views\Entity\View::create([
  'id' => 'my_organizations',
  'label' => 'My Organizations',
  'module' => 'views',
  'description' => 'Công ty của tôi phụ trách',
  'tag' => 'CRM',
  'base_table' => 'node_field_data',
  'display' => [
    'default' => [
      'display_plugin' => 'default',
      'id' => 'default',
      'display_title' => 'Default',
      'position' => 0,
      'display_options' => [
        'title' => 'My Organizations',
        'fields' => [
          'title' => [
            'id' => 'title',
            'table' => 'node_field_data',
            'field' => 'title',
            'label' => 'Organization',
            'type' => 'string',
            'settings' => ['link_to_entity' => TRUE],
            'plugin_id' => 'field',
          ],
          'field_industry' => [
            'id' => 'field_industry',
            'table' => 'node__field_industry',
            'field' => 'field_industry',
            'label' => 'Industry',
            'plugin_id' => 'field',
          ],
          'field_website' => [
            'id' => 'field_website',
            'table' => 'node__field_website',
            'field' => 'field_website',
            'label' => 'Website',
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
            'value' => ['organization' => 'organization'],
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
        ],
      ],
    ],
    'page_1' => [
      'display_plugin' => 'page',
      'id' => 'page_1',
      'display_title' => 'Page',
      'position' => 1,
      'display_options' => [
        'path' => 'crm/my-organizations',
        'menu' => [
          'type' => 'normal',
          'title' => 'My Organizations',
          'description' => 'Công ty tôi phụ trách',
          'menu_name' => 'main',
          'weight' => 13,
        ],
      ],
    ],
  ],
]);
\$view->save();

echo '✅ View My Organizations đã được tạo tại /crm/my-organizations' . PHP_EOL;
"

echo ""

# Bước 4: Tạo custom page dashboard với statistics
echo "📊 4. Tạo dashboard page với statistics..."
ddev drush eval "
// Tạo một node làm dashboard landing page
\$node = \Drupal\node\Entity\Node::create([
  'type' => 'page',
  'title' => 'Welcome to Open CRM',
  'body' => [
    'value' => '
<div style=\"padding: 20px;\">
  <h1>🎯 Welcome to Open CRM</h1>
  <p>Hệ thống quản lý khách hàng dành cho Sales Professionals</p>
  
  <div style=\"display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 30px 0;\">
    <div style=\"background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #1976d2;\">
      <h3>👥 My Contacts</h3>
      <p>Quản lý danh sách khách hàng</p>
      <a href=\"/crm/my-contacts\" style=\"color: #1976d2; font-weight: bold;\">Xem danh sách →</a>
    </div>
    
    <div style=\"background: #e8f5e9; padding: 20px; border-radius: 8px; border-left: 4px solid #388e3c;\">
      <h3>💼 My Pipeline</h3>
      <p>Theo dõi cơ hội bán hàng</p>
      <a href=\"/crm/my-pipeline\" style=\"color: #388e3c; font-weight: bold;\">Xem pipeline →</a>
    </div>
    
    <div style=\"background: #fff3e0; padding: 20px; border-radius: 8px; border-left: 4px solid #f57c00;\">
      <h3>📅 My Activities</h3>
      <p>Lịch sử tương tác</p>
      <a href=\"/crm/my-activities\" style=\"color: #f57c00; font-weight: bold;\">Xem hoạt động →</a>
    </div>
    
    <div style=\"background: #f3e5f5; padding: 20px; border-radius: 8px; border-left: 4px solid #7b1fa2;\">
      <h3>🏢 My Organizations</h3>
      <p>Công ty phụ trách</p>
      <a href=\"/crm/my-organizations\" style=\"color: #7b1fa2; font-weight: bold;\">Xem công ty →</a>
    </div>
  </div>
  
  <h2>🚀 Quick Actions</h2>
  <ul style=\"list-style: none; padding: 0;\">
    <li style=\"margin: 10px 0;\">➕ <a href=\"/node/add/contact\">Thêm Contact mới</a></li>
    <li style=\"margin: 10px 0;\">➕ <a href=\"/node/add/deal\">Tạo Deal mới</a></li>
    <li style=\"margin: 10px 0;\">➕ <a href=\"/node/add/activity\">Log Activity</a></li>
    <li style=\"margin: 10px 0;\">➕ <a href=\"/node/add/organization\">Thêm Organization</a></li>
  </ul>
</div>
',
    'format' => 'full_html',
  ],
  'status' => 1,
  'uid' => 1,
]);
\$node->save();

echo '✅ Dashboard landing page đã được tạo (Node ID: ' . \$node->id() . ')' . PHP_EOL;
" || echo "⚠️  Dashboard page có thể đã tồn tại"

echo ""

# Bước 5: Cấu hình path alias cho dashboard
echo "🔗 5. Cấu hình path alias..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
  'title' => 'Welcome to Open CRM',
  'type' => 'page',
]);

if (\$node = reset(\$nodes)) {
  \$path_alias = \Drupal\path_alias\Entity\PathAlias::create([
    'path' => '/node/' . \$node->id(),
    'alias' => '/crm/home',
  ]);
  \$path_alias->save();
  echo '✅ Path alias /crm/home đã được tạo' . PHP_EOL;
}
" || echo "⚠️  Path alias có thể đã tồn tại"

echo ""
echo "✨ HOÀN THÀNH! Dashboard và các views cá nhân đã được tạo."
echo ""
echo "📌 Sales có thể truy cập:"
echo "   🏠 /crm/home           : Trang chủ Dashboard"
echo "   👥 /crm/my-contacts    : Contacts của tôi"
echo "   💼 /crm/my-pipeline    : Pipeline của tôi"
echo "   📅 /crm/my-activities  : Activities của tôi"
echo "   🏢 /crm/my-organizations : Organizations phụ trách"
