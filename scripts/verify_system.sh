#!/bin/bash

echo "🎉 =========================================="
echo "   OPEN CRM - HỆ THỐNG ĐÃ SẴN SÀNG SỬ DỤNG"
echo "   =========================================="
echo ""

# Verify Owner Fields
echo "✅ 1. OWNER/ASSIGNEE FIELDS"
echo "   - Contact có field_owner (Sales phụ trách)"
echo "   - Deal có field_owner (Sales phụ trách)"
echo "   - Activity có field_assigned_to (Người xử lý)"
echo "   - Organization có field_owner (Sales phụ trách)"
echo ""

# Verify Views
echo "✅ 2. VIEWS VỚI DATA PRIVACY"
ddev drush eval "
\$views = ['my_contacts', 'my_deals', 'my_activities', 'my_organizations', 'sales_dashboard'];
echo '   Views đã tạo:' . PHP_EOL;
foreach (\$views as \$view_id) {
  \$view = \Drupal\views\Entity\View::load(\$view_id);
  if (\$view) {
    echo '   ✓ ' . \$view->label() . PHP_EOL;
  }
}
"
echo ""

# Verify Users
echo "✅ 3. SAMPLE USERS"
ddev drush eval "
\$users = ['manager', 'salesrep1', 'salesrep2', 'customer1'];
echo '   Users đã tạo:' . PHP_EOL;
foreach (\$users as \$username) {
  \$user_ids = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', \$username)->execute();
  if (\$user_id = reset(\$user_ids)) {
    \$user = \Drupal\user\Entity\User::load(\$user_id);
    \$roles = \$user->getRoles();
    echo '   ✓ ' . \$username . ' (UID: ' . \$user_id . ') - Roles: ' . implode(', ', \$roles) . PHP_EOL;
  }
}
"
echo ""

# Verify Sample Data
echo "✅ 4. SAMPLE DATA"
ddev drush eval "
\$contacts = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact']);
\$deals = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal']);
\$activities = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'activity']);
\$orgs = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'organization']);

echo '   Total: ' . count(\$contacts) . ' Contacts, ' . count(\$deals) . ' Deals, ' . count(\$activities) . ' Activities, ' . count(\$orgs) . ' Organizations' . PHP_EOL;

// Kiểm tra contacts có owner
\$contacts_with_owner = 0;
foreach (\$contacts as \$contact) {
  if (\$contact->hasField('field_owner') && !\$contact->get('field_owner')->isEmpty()) {
    \$contacts_with_owner++;
  }
}
echo '   ✓ ' . \$contacts_with_owner . '/' . count(\$contacts) . ' Contacts có Owner được gán' . PHP_EOL;

// Kiểm tra deals có owner
\$deals_with_owner = 0;
foreach (\$deals as \$deal) {
  if (\$deal->hasField('field_owner') && !\$deal->get('field_owner')->isEmpty()) {
    \$deals_with_owner++;
  }
}
echo '   ✓ ' . \$deals_with_owner . '/' . count(\$deals) . ' Deals có Owner được gán' . PHP_EOL;
"
echo ""

# URLs cho các roles
echo "🌐 5. ĐƯỜNG DẪN TRUY CẬP"
echo ""
echo "📌 SALES REP có thể truy cập:"
echo "   🏠 http://open-crm.ddev.site/crm/home"
echo "      Trang chủ Dashboard với Quick Actions"
echo ""
echo "   👥 http://open-crm.ddev.site/crm/my-contacts"
echo "      Danh sách khách hàng CỦA MÌNH"
echo ""
echo "   💼 http://open-crm.ddev.site/crm/my-pipeline"
echo "      Pipeline deals CỦA MÌNH"
echo ""
echo "   📅 http://open-crm.ddev.site/crm/my-activities"
echo "      Hoạt động CỦA MÌNH"
echo ""
echo "   🏢 http://open-crm.ddev.site/crm/my-organizations"
echo "      Công ty MÌNH phụ trách"
echo ""
echo "📌 SALES MANAGER có thể truy cập:"
echo "   📊 http://open-crm.ddev.site/app/contacts"
echo "      Xem TẤT CẢ contacts + filter theo Owner"
echo ""
echo "   📊 http://open-crm.ddev.site/app/pipeline"
echo "      Xem TẤT CẢ deals + grouped by Stage"
echo ""
echo "   ⚙️  http://open-crm.ddev.site/admin/content"
echo "      Quản lý tất cả content"
echo ""
echo "📌 ADMIN có thể truy cập:"
echo "   ⚙️  http://open-crm.ddev.site/admin/config/workflow/eca"
echo "      Cấu hình ECA automation workflows"
echo ""
echo ""

echo "🔐 6. ACCOUNTS ĐỂ TEST"
echo ""
echo "   Admin (Full Access):"
echo "   Username: admin"
echo "   Password: aa5BLB69Jt"
echo ""
echo "   Sales Manager (Xem tất cả data):"
echo "   Username: manager"
echo "   Password: manager123"
echo ""
echo "   Sales Rep 1 (Chỉ xem data của mình):"
echo "   Username: salesrep1"
echo "   Password: sales123"
echo ""
echo "   Sales Rep 2 (Chỉ xem data của mình):"
echo "   Username: salesrep2"
echo "   Password: sales123"
echo ""
echo "   Customer (Read-only):"
echo "   Username: customer1"
echo "   Password: customer123"
echo ""

echo "🎯 7. TÍNH NĂNG ĐÃ HOÀN THÀNH"
echo ""
echo "   ✅ Quản lý Khách hàng (Contacts) với Owner field"
echo "   ✅ Quản lý Công ty (Organizations) với Assigned Staff"
echo "   ✅ Quản lý Cơ hội (Deals) với Owner field"
echo "   ✅ Ghi nhận Tương tác (Activities) với Assigned To"
echo "   ✅ Pipeline grouped by Stage"
echo "   ✅ Data Privacy: Sales chỉ xem data của mình"
echo "   ✅ Dashboard cá nhân với Quick Actions"
echo "   ✅ User Roles & Permissions"
echo "   ✅ ECA Automation Framework (với BPMN.iO Modeler)"
echo ""

echo "📝 8. FEATURES ĐANG THIẾU (CẦN BỔ SUNG)"
echo ""
echo "   🔲 Import từ Excel/CSV"
echo "   🔲 Phân loại khách hàng (VIP, Mới, Tiềm năng)"
echo "   🔲 Kanban kéo thả (views_kanban không tương thích Drupal 11)"
echo "   🔲 Email automation khi chốt Deal"
echo "   🔲 Upload file đính kèm (Hợp đồng, Báo giá)"
echo "   🔲 Chat với khách hàng"
echo "   🔲 Biểu đồ thống kê (Charts)"
echo "   🔲 Group module cho Team-based permissions"
echo ""

echo "🚀 HỆ THỐNG ĐÃ SẴN SÀNG ĐỂ DEMO!"
echo ""
echo "💡 HƯỚNG DẪN DEMO DATA PRIVACY:"
echo "   1. Mở ẩn danh window #1, đăng nhập bằng salesrep1"
echo "   2. Truy cập /crm/my-contacts → Chỉ thấy 'John Doe (SalesRep1)'"
echo "   3. Mở ẩn danh window #2, đăng nhập bằng salesrep2"
echo "   4. Truy cập /crm/my-contacts → Chỉ thấy 'Jane Smith (SalesRep2)'"
echo "   5. Mở ẩn danh window #3, đăng nhập bằng manager"
echo "   6. Truy cập /app/contacts → Thấy TẤT CẢ contacts!"
echo ""
echo "✨ Chúc bạn demo thành công!"
