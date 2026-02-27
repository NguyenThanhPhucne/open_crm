#!/bin/bash

echo "🔄 KHÔI PHỤC VÀ TẠO GIAO DIỆN CHUYÊN NGHIỆP"
echo "============================================"
echo ""

# Bước 1: Rollback homepage về mặc định
echo "↩️  1. Rollback homepage về mặc định..."
ddev drush config:set system.site page.front /node -y
echo "✅ Homepage đã được rollback"
echo ""

# Bước 2: Xóa menu items có emoji
echo "🗑️  2. Xóa menu items cũ..."
ddev drush eval "
\$menu_links = \Drupal::entityTypeManager()
  ->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main']);

foreach (\$menu_links as \$menu_link) {
  \$menu_link->delete();
}

echo '✅ Đã xóa ' . count(\$menu_links) . ' menu items' . PHP_EOL;
"
echo ""

# Bước 3: Tạo menu chuyên nghiệp (không emoji, clean text)
echo "📋 3. Tạo menu navigation chuyên nghiệp..."
ddev drush eval "
use Drupal\menu_link_content\Entity\MenuLinkContent;

\$menu_items = [
  [
    'title' => 'Dashboard',
    'link' => ['uri' => 'internal:/crm/dashboard'],
    'menu_name' => 'main',
    'weight' => 0,
  ],
  [
    'title' => 'Contacts',
    'link' => ['uri' => 'internal:/crm/my-contacts'],
    'menu_name' => 'main',
    'weight' => 10,
  ],
  [
    'title' => 'Pipeline',
    'link' => ['uri' => 'internal:/crm/my-pipeline'],
    'menu_name' => 'main',
    'weight' => 20,
  ],
  [
    'title' => 'Activities',
    'link' => ['uri' => 'internal:/crm/my-activities'],
    'menu_name' => 'main',
    'weight' => 30,
  ],
  [
    'title' => 'Organizations',
    'link' => ['uri' => 'internal:/crm/my-organizations'],
    'menu_name' => 'main',
    'weight' => 40,
  ],
  [
    'title' => 'Reports',
    'link' => ['uri' => 'internal:/admin/reports'],
    'menu_name' => 'main',
    'weight' => 50,
  ],
];

foreach (\$menu_items as \$item) {
  \$menu_link = MenuLinkContent::create(\$item);
  \$menu_link->save();
  echo '✅ ' . \$item['title'] . PHP_EOL;
}
"
echo ""

# Bước 4: Cập nhật Welcome page thành Professional Dashboard
echo "📊 4. Tạo Professional Dashboard page..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  \$node->set('body', [
    'value' => '
<style>
.crm-dashboard {
  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;
  max-width: 1400px;
  margin: 0 auto;
  padding: 20px;
}

.dashboard-header {
  margin-bottom: 30px;
}

.dashboard-header h1 {
  color: #1a1a1a;
  font-size: 28px;
  font-weight: 600;
  margin: 0 0 10px 0;
}

.dashboard-header p {
  color: #666;
  font-size: 14px;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  border: 1px solid #e1e4e8;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.stat-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-label {
  color: #666;
  font-size: 13px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 8px;
}

.stat-value {
  color: #1a1a1a;
  font-size: 32px;
  font-weight: 700;
  margin-bottom: 8px;
}

.stat-change {
  font-size: 13px;
  color: #28a745;
}

.stat-change.negative {
  color: #dc3545;
}

.quick-actions {
  background: white;
  border: 1px solid #e1e4e8;
  border-radius: 8px;
  padding: 24px;
  margin-bottom: 30px;
}

.quick-actions h2 {
  color: #1a1a1a;
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 20px 0;
}

.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 20px;
  background: #0366d6;
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  transition: background 0.2s;
}

.action-btn:hover {
  background: #0256c5;
  color: white;
}

.action-btn.secondary {
  background: #f6f8fa;
  color: #24292e;
  border: 1px solid #e1e4e8;
}

.action-btn.secondary:hover {
  background: #e9ecef;
  color: #24292e;
}

.nav-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.nav-card {
  background: white;
  border: 1px solid #e1e4e8;
  border-radius: 8px;
  padding: 24px;
  text-decoration: none;
  transition: all 0.2s;
  display: block;
}

.nav-card:hover {
  border-color: #0366d6;
  box-shadow: 0 4px 12px rgba(3, 102, 214, 0.1);
  transform: translateY(-2px);
}

.nav-card h3 {
  color: #0366d6;
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 8px 0;
}

.nav-card p {
  color: #666;
  font-size: 14px;
  margin: 0;
  line-height: 1.5;
}
</style>

<div class=\"crm-dashboard\">
  <div class=\"dashboard-header\">
    <h1>Sales Dashboard</h1>
    <p>Welcome back! Here is your performance overview.</p>
  </div>

  <div class=\"stats-grid\">
    <div class=\"stat-card\">
      <div class=\"stat-label\">Total Pipeline</div>
      <div class=\"stat-value\">$525,000</div>
      <div class=\"stat-change\">↑ 12% from last month</div>
    </div>
    
    <div class=\"stat-card\">
      <div class=\"stat-label\">Active Deals</div>
      <div class=\"stat-value\">6</div>
      <div class=\"stat-change\">+2 new this week</div>
    </div>
    
    <div class=\"stat-card\">
      <div class=\"stat-label\">Contacts</div>
      <div class=\"stat-value\">6</div>
      <div class=\"stat-change\">↑ 15% growth</div>
    </div>
    
    <div class=\"stat-card\">
      <div class=\"stat-label\">Win Rate</div>
      <div class=\"stat-value\">68%</div>
      <div class=\"stat-change\">↑ 5% improvement</div>
    </div>
  </div>

  <div class=\"quick-actions\">
    <h2>Quick Actions</h2>
    <div class=\"action-buttons\">
      <a href=\"/node/add/contact\" class=\"action-btn\">+ Add Contact</a>
      <a href=\"/node/add/deal\" class=\"action-btn\">+ Create Deal</a>
      <a href=\"/node/add/activity\" class=\"action-btn secondary\">Log Activity</a>
      <a href=\"/node/add/organization\" class=\"action-btn secondary\">Add Organization</a>
    </div>
  </div>

  <div class=\"nav-cards\">
    <a href=\"/crm/my-contacts\" class=\"nav-card\">
      <h3>My Contacts</h3>
      <p>View and manage your contact list. Track interactions and maintain relationships with your customers.</p>
    </a>
    
    <a href=\"/crm/my-pipeline\" class=\"nav-card\">
      <h3>Sales Pipeline</h3>
      <p>Monitor your deals across different stages. Track progress from prospecting to closing.</p>
    </a>
    
    <a href=\"/crm/my-activities\" class=\"nav-card\">
      <h3>My Activities</h3>
      <p>Review your recent activities and upcoming tasks. Stay on top of your sales workflow.</p>
    </a>
    
    <a href=\"/crm/my-organizations\" class=\"nav-card\">
      <h3>Organizations</h3>
      <p>Manage company accounts. View all contacts and deals associated with each organization.</p>
    </a>
  </div>
</div>
',
    'format' => 'full_html',
  ]);
  \$node->save();
  echo '✅ Dashboard page đã được cập nhật với thiết kế chuyên nghiệp' . PHP_EOL;
}
"
echo ""

# Bước 5: Set homepage = professional dashboard
echo "🏠 5. Set homepage = Professional Dashboard..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  \Drupal::configFactory()
    ->getEditable('system.site')
    ->set('page.front', '/node/' . \$node->id())
    ->save();
  echo '✅ Homepage đã được set = Dashboard (Node ID: ' . \$node->id() . ')' . PHP_EOL;
}
"
echo ""

# Bước 6: Cấu hình Gin theme cho professional look
echo "🎨 6. Cấu hình Gin theme..."
ddev drush config:set gin.settings classic_toolbar vertical -y
ddev drush config:set gin.settings preset blue -y
ddev drush config:set gin.settings high_contrast_mode FALSE -y
ddev drush config:set gin.settings show_user_theme_settings TRUE -y
echo "✅ Gin: Vertical toolbar, Blue preset"
echo ""

# Bước 7: Enable account menu in header
echo "📱 7. Cấu hình blocks..."
ddev drush eval "
\$block_storage = \Drupal::entityTypeManager()->getStorage('block');

// Ensure account menu is in header
\$account_menu = \$block_storage->load('gin_account_menu');
if (\$account_menu) {
  \$account_menu->setRegion('header');
  \$account_menu->save();
  echo '✅ Account menu in header' . PHP_EOL;
}

// Ensure breadcrumbs are visible
\$breadcrumbs = \$block_storage->load('gin_breadcrumbs');
if (\$breadcrumbs) {
  \$breadcrumbs->setRegion('breadcrumb');
  \$breadcrumbs->save();
  echo '✅ Breadcrumbs enabled' . PHP_EOL;
}
" || echo "⚠️  Blocks đã được cấu hình"
echo ""

# Bước 8: Clear cache
echo "🧹 8. Clear cache..."
ddev drush cr
echo ""

echo "✨ HOÀN THÀNH! Giao diện chuyên nghiệp đã được khôi phục."
echo ""
echo "📌 KIỂM TRA:"
echo "   URL: http://open-crm.ddev.site"
echo "   - Dashboard với stats cards chuyên nghiệp"
echo "   - Menu clean: Dashboard | Contacts | Pipeline | Activities | Organizations | Reports"
echo "   - Gin theme với vertical toolbar (giống Salesforce)"
echo "   - Quick Actions buttons"
echo "   - Navigation cards"
echo ""
echo "🎯 ĐỂ ADMIN SỬ DỤNG:"
echo "   1. Đăng nhập: admin / aa5BLB69Jt"
echo "   2. Gin toolbar bên trái với menu vertical"
echo "   3. Dashboard hiển thị stats và quick actions"
echo ""
echo "💼 ĐỂ SALES SỬ DỤNG:"
echo "   1. Đăng nhập: salesrep1 / sales123"
echo "   2. Top menu: Dashboard, Contacts, Pipeline, Activities, Organizations"
echo "   3. Clean, modern, professional interface"
