#!/bin/bash

echo "🔍 KIỂM TRA TOÀN BỘ HỆ THỐNG CRM"
echo "=================================="
echo ""

# 1. System Status
echo "1️⃣ DRUPAL SYSTEM STATUS"
echo "------------------------"
ddev drush status
echo ""

# 2. Content Types
echo "2️⃣ CONTENT TYPES"
echo "----------------"
ddev drush eval "
\$content_types = ['contact', 'deal', 'activity', 'organization'];
echo 'Checking content types:' . PHP_EOL;
foreach (\$content_types as \$type) {
  \$entity_type = \Drupal::entityTypeManager()->getStorage('node_type')->load(\$type);
  if (\$entity_type) {
    echo '  ✅ ' . \$type . ': ' . \$entity_type->label() . PHP_EOL;
  } else {
    echo '  ❌ ' . \$type . ': NOT FOUND' . PHP_EOL;
  }
}
"
echo ""

# 3. Check Owner Fields
echo "3️⃣ OWNER FIELDS (Data Privacy)"
echo "-------------------------------"
ddev drush eval "
\$content_types = ['contact', 'deal', 'activity', 'organization'];
foreach (\$content_types as \$type) {
  \$entity_type = \Drupal::entityTypeManager()->getDefinition('node');
  \$field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', \$type);
  
  if (isset(\$field_definitions['field_owner'])) {
    \$field = \$field_definitions['field_owner'];
    \$settings = \$field->getDefaultValueLiteral();
    echo '  ✅ ' . \$type . ': field_owner exists';
    if (!empty(\$settings)) {
      echo ' (default: current user)';
    }
    echo PHP_EOL;
  } else {
    echo '  ❌ ' . \$type . ': field_owner MISSING' . PHP_EOL;
  }
}
"
echo ""

# 4. Views
echo "4️⃣ VIEWS CONFIGURATION"
echo "-----------------------"
ddev drush eval "
\$views = ['contacts', 'deals', 'activities', 'organizations'];
foreach (\$views as \$view_id) {
  \$view = \Drupal::entityTypeManager()->getStorage('view')->load(\$view_id);
  if (\$view) {
    echo '  ✅ ' . \$view_id . ': ' . \$view->label() . PHP_EOL;
  } else {
    echo '  ❌ ' . \$view_id . ': NOT FOUND' . PHP_EOL;
  }
}
"
echo ""

# 5. Taxonomies
echo "5️⃣ TAXONOMIES"
echo "-------------"
ddev drush eval "
\$vocabularies = ['deal_stage', 'activity_type', 'contact_source', 'industry'];
foreach (\$vocabularies as \$vocab) {
  \$vocabulary = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load(\$vocab);
  if (\$vocabulary) {
    \$terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree(\$vocab);
    echo '  ✅ ' . \$vocab . ': ' . \$vocabulary->label() . ' (' . count(\$terms) . ' terms)' . PHP_EOL;
  } else {
    echo '  ❌ ' . \$vocab . ': NOT FOUND' . PHP_EOL;
  }
}
"
echo ""

# 6. User Roles
echo "6️⃣ USER ROLES & PERMISSIONS"
echo "----------------------------"
ddev drush eval "
\$roles = ['sales_representative', 'sales_manager'];
foreach (\$roles as \$role_id) {
  \$role = \Drupal::entityTypeManager()->getStorage('user_role')->load(\$role_id);
  if (\$role) {
    echo '  ✅ ' . \$role_id . ': ' . \$role->label() . PHP_EOL;
  } else {
    echo '  ❌ ' . \$role_id . ': NOT FOUND' . PHP_EOL;
  }
}
"
echo ""

# 7. Sample Users
echo "7️⃣ SAMPLE USERS"
echo "---------------"
ddev drush eval "
\$usernames = ['manager', 'salesrep1', 'salesrep2', 'customer1'];
foreach (\$usernames as \$username) {
  \$users = \Drupal::entityTypeManager()
    ->getStorage('user')
    ->loadByProperties(['name' => \$username]);
  if (\$user = reset(\$users)) {
    \$roles = \$user->getRoles();
    echo '  ✅ ' . \$username . ' (UID: ' . \$user->id() . ', Roles: ' . implode(', ', \$roles) . ')' . PHP_EOL;
  } else {
    echo '  ❌ ' . \$username . ': NOT FOUND' . PHP_EOL;
  }
}
"
echo ""

# 8. Dashboard Node
echo "8️⃣ DASHBOARD NODE"
echo "-----------------"
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  echo '  ✅ Dashboard: Node ID ' . \$node->id() . PHP_EOL;
  echo '     Published: ' . (\$node->isPublished() ? 'Yes' : 'No') . PHP_EOL;
  \$alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . \$node->id());
  echo '     Alias: ' . \$alias . PHP_EOL;
} else {
  echo '  ❌ Dashboard node NOT FOUND' . PHP_EOL;
}
"
echo ""

# 9. Homepage Configuration
echo "9️⃣ HOMEPAGE SETTING"
echo "-------------------"
ddev drush eval "
\$config = \Drupal::config('system.site');
\$front = \$config->get('page.front');
echo '  Homepage: ' . \$front . PHP_EOL;
"
echo ""

# 10. Theme Configuration
echo "🔟 THEME CONFIGURATION"
echo "----------------------"
ddev drush eval "
\$theme_config = \Drupal::config('system.theme');
echo '  Default theme: ' . \$theme_config->get('default') . PHP_EOL;
echo '  Admin theme: ' . \$theme_config->get('admin') . PHP_EOL;

\$gin_config = \Drupal::config('gin.settings');
echo '  Gin toolbar: ' . \$gin_config->get('classic_toolbar') . PHP_EOL;
echo '  Gin preset: ' . \$gin_config->get('preset_accent_color') . PHP_EOL;
echo '  Dark mode: ' . (\$gin_config->get('enable_darkmode') ? 'Enabled' : 'Disabled') . PHP_EOL;
"
echo ""

# 11. Recent Watchdog Errors
echo "1️⃣1️⃣ RECENT ERRORS (Last 10)"
echo "----------------------------"
ddev drush watchdog:show --count=10 --severity=Error
echo ""

# 12. Sample Content Count
echo "1️⃣2️⃣ CONTENT COUNT"
echo "------------------"
ddev drush eval "
\$content_types = ['contact', 'deal', 'activity', 'organization'];
foreach (\$content_types as \$type) {
  \$query = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', \$type);
  \$count = \$query->count()->execute();
  echo '  ' . ucfirst(\$type) . 's: ' . \$count . PHP_EOL;
}
"
echo ""

# 13. Menu Items
echo "1️⃣3️⃣ MAIN MENU ITEMS"
echo "--------------------"
ddev drush eval "
\$menu_tree = \Drupal::menuTree();
\$parameters = \$menu_tree->getCurrentRouteMenuTreeParameters('main');
\$tree = \$menu_tree->load('main', \$parameters);

foreach (\$tree as \$element) {
  \$link = \$element->link;
  echo '  • ' . \$link->getTitle() . ' → ' . \$link->getUrlObject()->toString() . PHP_EOL;
}
"
echo ""

echo "✅ KIỂM TRA HOÀN THÀNH!"
echo ""
echo "📊 SUMMARY:"
echo "   - Nếu thấy ❌: Cần fix ngay"
echo "   - Nếu tất cả ✅: Hệ thống hoạt động tốt"
echo ""
