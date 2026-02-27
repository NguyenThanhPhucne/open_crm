#!/bin/bash

echo "👥 TẠO SAMPLE USERS ĐỂ TEST HỆ THỐNG"
echo "======================================"
echo ""

# Bước 1: Tạo Sales Manager
echo "👔 1. Tạo Sales Manager..."
ddev drush user:create manager --mail="manager@opencrm.test" --password="manager123" || echo "⚠️  User manager đã tồn tại"
ddev drush user:role:add sales_manager manager
echo "✅ User: manager / manager123 (Sales Manager)"
echo ""

# Bước 2: Tạo Sales Rep 1
echo "👤 2. Tạo Sales Rep 1..."
ddev drush user:create salesrep1 --mail="salesrep1@opencrm.test" --password="sales123" || echo "⚠️  User salesrep1 đã tồn tại"
ddev drush user:role:add sales_representative salesrep1
echo "✅ User: salesrep1 / sales123 (Sales Representative)"
echo ""

# Bước 3: Tạo Sales Rep 2
echo "👤 3. Tạo Sales Rep 2..."
ddev drush user:create salesrep2 --mail="salesrep2@opencrm.test" --password="sales123" || echo "⚠️  User salesrep2 đã tồn tại"
ddev drush user:role:add sales_representative salesrep2
echo "✅ User: salesrep2 / sales123 (Sales Representative)"
echo ""

# Bước 4: Tạo Customer
echo "🧑‍💼 4. Tạo Customer..."
ddev drush user:create customer1 --mail="customer1@opencrm.test" --password="customer123" || echo "⚠️  User customer1 đã tồn tại"
ddev drush user:role:add customer customer1
echo "✅ User: customer1 / customer123 (Customer)"
echo ""

# Bước 5: Lấy User IDs
echo "🔍 5. Lấy User IDs..."
ddev drush eval "
\$manager = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'manager')->execute();
\$salesrep1 = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'salesrep1')->execute();
\$salesrep2 = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'salesrep2')->execute();

echo 'manager (UID: ' . reset(\$manager) . ')' . PHP_EOL;
echo 'salesrep1 (UID: ' . reset(\$salesrep1) . ')' . PHP_EOL;
echo 'salesrep2 (UID: ' . reset(\$salesrep2) . ')' . PHP_EOL;
"

echo ""

# Bước 6: Tạo sample data cho Sales Rep 1
echo "📊 6. Tạo sample data cho Sales Rep 1..."
ddev drush eval "
\$salesrep1_uid = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'salesrep1')->execute();
\$salesrep1_uid = reset(\$salesrep1_uid);

if (\$salesrep1_uid) {
  // Tạo Organization cho SalesRep1
  \$org = \Drupal\node\Entity\Node::create([
    'type' => 'organization',
    'title' => 'Tech Startup ABC (SalesRep1)',
    'field_industry' => 'Technology',
    'field_website' => ['uri' => 'https://techstartup-abc.com'],
    'field_owner' => \$salesrep1_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$org->save();
  
  // Tạo Contact cho SalesRep1
  \$contact = \Drupal\node\Entity\Node::create([
    'type' => 'contact',
    'title' => 'John Doe (SalesRep1)',
    'field_email' => 'john.doe@techstartup-abc.com',
    'field_phone' => '+84 901 234 567',
    'field_organization' => \$org->id(),
    'field_owner' => \$salesrep1_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$contact->save();
  
  // Tạo Deal cho SalesRep1
  \$stage_new = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'pipeline_stage', 'name' => 'New']);
  \$stage_new_id = reset(\$stage_new)->id();
  
  \$deal = \Drupal\node\Entity\Node::create([
    'type' => 'deal',
    'title' => 'Deal - Tech Startup ABC (SalesRep1)',
    'field_amount' => 50000000,
    'field_stage' => \$stage_new_id,
    'field_probability' => 20,
    'field_closing_date' => date('Y-m-d', strtotime('+30 days')),
    'field_contact' => \$contact->id(),
    'field_organization' => \$org->id(),
    'field_owner' => \$salesrep1_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$deal->save();
  
  echo '✅ Đã tạo 1 Organization, 1 Contact, 1 Deal cho SalesRep1' . PHP_EOL;
} else {
  echo '⚠️  Không tìm thấy user salesrep1' . PHP_EOL;
}
"

echo ""

# Bước 7: Tạo sample data cho Sales Rep 2
echo "📊 7. Tạo sample data cho Sales Rep 2..."
ddev drush eval "
\$salesrep2_uid = \Drupal::entityQuery('user')->accessCheck(FALSE)->condition('name', 'salesrep2')->execute();
\$salesrep2_uid = reset(\$salesrep2_uid);

if (\$salesrep2_uid) {
  // Tạo Organization cho SalesRep2
  \$org = \Drupal\node\Entity\Node::create([
    'type' => 'organization',
    'title' => 'E-commerce XYZ (SalesRep2)',
    'field_industry' => 'Retail',
    'field_website' => ['uri' => 'https://ecommerce-xyz.com'],
    'field_owner' => \$salesrep2_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$org->save();
  
  // Tạo Contact cho SalesRep2
  \$contact = \Drupal\node\Entity\Node::create([
    'type' => 'contact',
    'title' => 'Jane Smith (SalesRep2)',
    'field_email' => 'jane.smith@ecommerce-xyz.com',
    'field_phone' => '+84 902 345 678',
    'field_organization' => \$org->id(),
    'field_owner' => \$salesrep2_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$contact->save();
  
  // Tạo Deal cho SalesRep2
  \$stage_qualified = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'pipeline_stage', 'name' => 'Qualified']);
  \$stage_qualified_id = reset(\$stage_qualified)->id();
  
  \$deal = \Drupal\node\Entity\Node::create([
    'type' => 'deal',
    'title' => 'Deal - E-commerce XYZ (SalesRep2)',
    'field_amount' => 75000000,
    'field_stage' => \$stage_qualified_id,
    'field_probability' => 50,
    'field_closing_date' => date('Y-m-d', strtotime('+45 days')),
    'field_contact' => \$contact->id(),
    'field_organization' => \$org->id(),
    'field_owner' => \$salesrep2_uid,
    'status' => 1,
    'uid' => 1,
  ]);
  \$deal->save();
  
  echo '✅ Đã tạo 1 Organization, 1 Contact, 1 Deal cho SalesRep2' . PHP_EOL;
} else {
  echo '⚠️  Không tìm thấy user salesrep2' . PHP_EOL;
}
"

echo ""
echo "✨ HOÀN THÀNH! Sample users và data đã được tạo."
echo ""
echo "📌 ĐĂNG NHẬP ĐỂ TEST:"
echo ""
echo "1️⃣  Sales Manager:"
echo "    Username: manager"
echo "    Password: manager123"
echo "    Có thể xem TẤT CẢ contacts/deals của tất cả Sales Reps"
echo ""
echo "2️⃣  Sales Rep 1:"
echo "    Username: salesrep1"
echo "    Password: sales123"
echo "    Chỉ xem được contacts/deals CỦA MÌNH (về Tech Startup ABC)"
echo ""
echo "3️⃣  Sales Rep 2:"
echo "    Username: salesrep2"
echo "    Password: sales123"
echo "    Chỉ xem được contacts/deals CỦA MÌNH (về E-commerce XYZ)"
echo ""
echo "4️⃣  Customer:"
echo "    Username: customer1"
echo "    Password: customer123"
echo "    Chỉ có quyền xem (Read-only)"
echo ""
echo "🧪 CÁCH TEST DATA PRIVACY:"
echo "   1. Đăng nhập bằng salesrep1"
echo "   2. Truy cập: http://open-crm.ddev.site/crm/my-contacts"
echo "   3. Bạn SẼ CHỈ THẤY: 'John Doe (SalesRep1)'"
echo "   4. Bạn SẼ KHÔNG THẤY: 'Jane Smith (SalesRep2)'"
echo ""
echo "   5. Đăng nhập bằng manager"
echo "   6. Truy cập: http://open-crm.ddev.site/app/contacts"
echo "   7. Bạn SẼ THẤY TẤT CẢ contacts của cả 2 Sales Reps!"
