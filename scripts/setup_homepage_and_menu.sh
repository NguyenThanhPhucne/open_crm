#!/bin/bash

echo "🏠 SETUP HOMEPAGE VÀ MENU NAVIGATION"
echo "======================================"
echo ""

# Bước 1: Set front page = /crm/home
echo "📍 1. Cấu hình trang chủ mặc định..."
ddev drush config:set system.site page.front /crm/home -y
echo "✅ Trang chủ đã được set thành /crm/home"
echo ""

# Bước 2: Xóa menu items cũ trong main menu
echo "🗑️  2. Xóa menu items mặc định..."
ddev drush eval "
\$menu_link_manager = \Drupal::service('plugin.manager.menu.link');
\$menu_name = 'main';

// Load all menu links
\$menu_links = \$menu_link_manager->loadLinksByRoute('<front>', [], \$menu_name);
foreach (\$menu_links as \$menu_link) {
  if (\$menu_link->isDeletable()) {
    \$menu_link->deleteLink();
  }
}

echo '✅ Đã xóa menu items cũ' . PHP_EOL;
" || echo "⚠️  Không có menu items để xóa"
echo ""

# Bước 3: Tạo menu items mới cho CRM
echo "📋 3. Tạo menu navigation cho CRM..."
ddev drush eval "
use Drupal\menu_link_content\Entity\MenuLinkContent;

// Clear existing CRM menu items
\$menu_links = \Drupal::entityTypeManager()
  ->getStorage('menu_link_content')
  ->loadByProperties(['menu_name' => 'main']);

foreach (\$menu_links as \$menu_link) {
  \$title = \$menu_link->getTitle();
  if (strpos(\$title, 'Dashboard') !== FALSE || 
      strpos(\$title, 'Contact') !== FALSE || 
      strpos(\$title, 'Pipeline') !== FALSE || 
      strpos(\$title, 'Activities') !== FALSE || 
      strpos(\$title, 'Organizations') !== FALSE ||
      strpos(\$title, 'My') !== FALSE) {
    \$menu_link->delete();
  }
}

// Create new menu structure
\$menu_items = [
  [
    'title' => '🏠 Dashboard',
    'link' => ['uri' => 'internal:/crm/home'],
    'menu_name' => 'main',
    'weight' => 0,
    'expanded' => FALSE,
  ],
  [
    'title' => '👥 My Contacts',
    'link' => ['uri' => 'internal:/crm/my-contacts'],
    'menu_name' => 'main',
    'weight' => 10,
    'expanded' => FALSE,
  ],
  [
    'title' => '💼 My Pipeline',
    'link' => ['uri' => 'internal:/crm/my-pipeline'],
    'menu_name' => 'main',
    'weight' => 20,
    'expanded' => FALSE,
  ],
  [
    'title' => '📅 My Activities',
    'link' => ['uri' => 'internal:/crm/my-activities'],
    'menu_name' => 'main',
    'weight' => 30,
    'expanded' => FALSE,
  ],
  [
    'title' => '🏢 My Organizations',
    'link' => ['uri' => 'internal:/crm/my-organizations'],
    'menu_name' => 'main',
    'weight' => 40,
    'expanded' => FALSE,
  ],
];

foreach (\$menu_items as \$item) {
  \$menu_link = MenuLinkContent::create(\$item);
  \$menu_link->save();
  echo '✅ Created: ' . \$item['title'] . PHP_EOL;
}
"
echo ""

# Bước 4: Enable Main menu trong Gin theme
echo "🎨 4. Enable Main menu trong Gin theme..."
ddev drush config:set block.block.gin_primary_local_tasks region header -y
ddev drush config:set block.block.gin_main_menu region header -y || echo "⚠️  Block chưa tồn tại, sẽ tạo mới..."

# Tạo block cho main menu nếu chưa có
ddev drush eval "
\$block_storage = \Drupal::entityTypeManager()->getStorage('block');

// Check if main menu block exists
\$main_menu_block = \$block_storage->load('gin_main_menu');

if (!\$main_menu_block) {
  // Create main menu block
  \$main_menu_block = \$block_storage->create([
    'id' => 'gin_main_menu',
    'plugin' => 'system_menu_block:main',
    'region' => 'header',
    'theme' => 'gin',
    'weight' => 0,
    'settings' => [
      'label' => 'Main navigation',
      'label_display' => '0',
      'level' => 1,
      'depth' => 0,
      'expand_all_items' => FALSE,
    ],
  ]);
  \$main_menu_block->save();
  echo '✅ Đã tạo Main menu block' . PHP_EOL;
} else {
  \$main_menu_block->setRegion('header');
  \$main_menu_block->setWeight(0);
  \$main_menu_block->save();
  echo '✅ Đã cập nhật Main menu block' . PHP_EOL;
}
"
echo ""

# Bước 5: Cấu hình Gin settings cho better UX
echo "⚙️  5. Cấu hình Gin theme settings..."
ddev drush config:set gin.settings classic_toolbar horizontal -y
ddev drush config:set gin.settings darkmode 0 -y
ddev drush config:set gin.settings preset blue -y
ddev drush config:set gin.settings high_contrast_mode FALSE -y
echo "✅ Gin theme đã được cấu hình: Horizontal toolbar, Blue preset"
echo ""

# Bước 6: Publish welcome page
echo "📄 6. Publish welcome page..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  if (!\$node->isPublished()) {
    \$node->setPublished()->save();
  }
  echo '✅ Welcome page đã được publish (Node ID: ' . \$node->id() . ')' . PHP_EOL;
} else {
  echo '⚠️  Welcome page không tồn tại' . PHP_EOL;
}
"
echo ""

# Bước 7: Clear cache
echo "🧹 7. Clear cache..."
ddev drush cr
echo ""

echo "✨ HOÀN THÀNH! Homepage và navigation đã được cấu hình."
echo ""
echo "📌 KIỂM TRA:"
echo "   1. Truy cập: http://open-crm.ddev.site"
echo "   2. Bạn sẽ thấy Dashboard với Quick Actions"
echo "   3. Menu navigation ở header: Dashboard, My Contacts, My Pipeline, My Activities, My Organizations"
echo ""
echo "🎯 ĐĂNG NHẬP ĐỂ XEM MENU:"
echo "   - Admin: admin / aa5BLB69Jt"
echo "   - Manager: manager / manager123"
echo "   - Sales Rep: salesrep1 / sales123"
