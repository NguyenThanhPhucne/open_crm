# 🎯 HƯỚNG DẪN THÊM DỮ LIỆU THẬT VÀO CRM

## 📂 Dữ Liệu Lưu Ở Đâu?

### MySQL Database (trong DDEV)

```
Database: db
Host: db (internal DDEV)
Port: 3306 (internal), varies (external)

Bảng chính:
├── node                    # Node metadata
├── node_field_data         # Node content (title, type, status)
├── node_revision           # Version history
├── node__field_email       # Email field
├── node__field_phone       # Phone field
├── node__field_amount      # Deal amount
├── node__field_contact     # Contact reference
└── ... (30+ field tables)
```

**Xem database:**

```bash
# Connect to MySQL
ddev mysql

# Or query directly
ddev drush sql-query "SELECT COUNT(*) FROM node WHERE type='contact'"
```

**Current data:**

- ✅ **14 contacts** đã có
- ✅ **7 deals** đã có
- ✅ Organizations, activities

---

## 🚀 CÁCH 1: Import CSV (Khuyến nghị)

### Sample Files Có Sẵn

Bạn đã có 2 files mẫu:

**1. sample_contacts.csv**

```csv
Name,Email,Phone,Position,Organization,Status
Alice Johnson,alice@techcorp.com,0901111111,Marketing Director,TechCorp,new
Bob Wilson,bob@innovate.io,0902222222,Sales Manager,Innovate Solutions,contacted
```

**2. sample_deals.csv**

```csv
Title,Amount,Contact Email,Organization,Stage,Expected Close Date,Probability,Notes
Website Redesign,45000,alice@techcorp.com,TechCorp,Proposal,2026-03-30,60,Modern design
CRM System,120000,bob@innovate.io,Innovate Solutions,Negotiation,2026-04-15,75,Enterprise deal
```

### Cách Import

#### Qua Web UI:

```bash
# 1. Lấy login link
ddev drush uli

# 2. Truy cập:
👉 /admin/crm/import/contacts         # Import contacts
👉 /admin/crm/import/deals            # Import deals

# 3. Upload CSV file
# 4. Chọn options:
   ✅ Skip duplicates
   ✅ Create missing organizations
   ✅ Update existing

# 5. Click "Import"
```

#### Hoặc dùng Drush (nhanh hơn):

```bash
# Import contacts
ddev drush php:eval "
\$form = \Drupal::formBuilder()->getForm('\Drupal\crm_import_export\Form\ImportContactsForm');
"

# Hoặc chạy script có sẵn
ddev exec bash scripts/create_sample_data_v2.sh
```

---

## 🖱️ CÁCH 2: Tạo Manual Qua UI

### Tạo Contact

```bash
# 1. Login
ddev drush uli

# 2. Navigate to:
👉 /node/add/contact

# 3. Điền form:
   - Name: Nguyễn Văn A
   - Email: nguyenvana@example.com
   - Phone: 0912345678
   - Position: Sales Manager
   - Organization: Công ty ABC
   - Status: New

# 4. Save --> Lưu vào database ngay lập tức!
```

### Tạo Deal

```bash
# Navigate to:
👉 /node/add/deal

# Fill:
   - Title: Hợp đồng website
   - Amount: 50,000,000 VND
   - Contact: Chọn từ list có sẵn
   - Stage: Proposal
   - Expected Close: 2026-03-30

# Save --> Tự động set field_owner = current user
```

### Quick Add (nhanh nhất!)

```bash
# 1. Click "Quick Add" button ở top menu
# 2. Chọn: Contact / Deal / Organization
# 3. Popup form hiện ra
# 4. Fill & Save --> Lưu ngay!
```

---

## ⚡ CÁCH 3: Dùng Script Tạo Sample Data

### Script Có Sẵn

**1. Tạo sample contacts & deals:**

```bash
ddev exec bash scripts/create_sample_data_v2.sh
```

**2. Tạo sample users (salesrep, manager):**

```bash
ddev exec bash scripts/create_sample_users.sh
```

**3. Tạo contacts với Drush:**

```bash
ddev drush php:eval "
\$contact = \Drupal\node\Entity\Node::create([
  'type' => 'contact',
  'title' => 'Trần Thị B',
  'field_email' => 'tranthib@gmail.com',
  'field_phone' => '0987654321',
  'field_position' => 'Marketing Manager',
  'field_status' => 'qualified',
  'field_owner' => \Drupal::currentUser()->id(),
  'status' => 1,
]);
\$contact->save();
echo 'Created contact ID: ' . \$contact->id();
"
```

**4. Tạo deal:**

```bash
ddev drush php:eval "
\$deal = \Drupal\node\Entity\Node::create([
  'type' => 'deal',
  'title' => 'Dự án ERP System',
  'field_amount' => 150000000, // 150 triệu VND
  'field_stage' => 69, // Stage term ID
  'field_probability' => 80,
  'field_owner' => 1,
  'status' => 1,
]);
\$deal->save();
echo 'Created deal ID: ' . \$deal->id();
"
```

---

## 🔍 KIỂM TRA DỮ LIỆU

### Xem tất cả contacts:

```bash
ddev drush sql-query "
SELECT
  nfd.nid,
  nfd.title as name,
  email.field_email_value as email,
  phone.field_phone_value as phone
FROM node_field_data nfd
LEFT JOIN node__field_email email ON nfd.nid = email.entity_id
LEFT JOIN node__field_phone phone ON nfd.nid = phone.entity_id
WHERE nfd.type = 'contact'
ORDER BY nfd.created DESC
LIMIT 10
"
```

### Đếm data:

```bash
# Contacts
ddev drush sql-query "SELECT COUNT(*) FROM node_field_data WHERE type='contact'"

# Deals
ddev drush sql-query "SELECT COUNT(*) FROM node_field_data WHERE type='deal'"

# Organizations
ddev drush sql-query "SELECT COUNT(*) FROM node_field_data WHERE type='organization'"
```

### Xem qua UI:

```bash
ddev drush uli

# Then visit:
👉 /contacts             # View all contacts
👉 /deals                # View all deals
👉 /admin/crm/dashboard  # Dashboard
```

---

## 📤 EXPORT DỮ LIỆU

### Export ra CSV

```bash
# 1. Login
ddev drush uli

# 2. Navigate to:
👉 /admin/crm/export/contacts
👉 /admin/crm/export/deals

# 3. Click "Export CSV"
# 4. File tải về máy
```

### Export database

```bash
# Backup toàn bộ database
ddev export-db --file=backup.sql.gz

# Hoặc chỉ export CRM data
ddev drush sql-query "
SELECT * FROM node_field_data
WHERE type IN ('contact', 'deal', 'organization')
" > crm_data.csv
```

---

## 💾 LƯU Ý QUAN TRỌNG

### 1. Field Owner (Quyền Sở Hữu)

```php
// Khi tạo contact/deal, PHẢI set field_owner:
'field_owner' => \Drupal::currentUser()->id()

// Nếu không set, hook_node_presave sẽ tự set
// Nhưng best practice là set ngay lúc tạo
```

### 2. Access Control

- **Admin:** Thấy tất cả data
- **Sales Manager:** Thấy data của team
- **Sales Rep:** CHỈ thấy data của mình (field_owner = user ID)

```bash
# Test với user khác nhau:
ddev drush uli --name=salesrep1    # Login as rep 1
ddev drush uli --name=salesrep2    # Login as rep 2
ddev drush uli --name=manager      # Login as manager
```

### 3. Views Auto-Filter

Module CRM Core tự động filter views:

```php
// hook_query_alter() đã implement
// Sales reps chỉ thấy WHERE field_owner = current_user_id
```

### 4. Duplicate Check

Import form có option:

- ✅ Skip duplicates (check by email for contacts)
- ✅ Update existing (overwrite data)
- ✅ Create missing organizations (auto-create)

---

## 🧪 DEMO: Thêm Dữ Liệu Thật Ngay

### Quick Demo Script:

```bash
# 1. Tạo 10 contacts mẫu
ddev exec bash -c '
for i in {1..10}; do
  ddev drush php:eval "
    \$contact = \Drupal\node\Entity\Node::create([
      \"type\" => \"contact\",
      \"title\" => \"Khách hàng $i\",
      \"field_email\" => \"khach$i@example.com\",
      \"field_phone\" => \"09$(printf \"%08d\" $i)\",
      \"field_status\" => \"new\",
      \"field_owner\" => 1,
      \"status\" => 1,
    ]);
    \$contact->save();
    echo \"Created: Khách hàng $i\n\";
  "
done
'

# 2. Import CSV có sẵn
# Go to: /admin/crm/import/contacts
# Upload: sample_contacts.csv

# 3. Xem kết quả
ddev drush uli
# Visit: /contacts
```

---

## 📊 MONITORING

### Check logs:

```bash
# Xem import logs
ddev drush watchdog-show --type=crm_import_export

# Xem tất cả errors
ddev drush watchdog-show --severity=Error

# Tail logs real-time
ddev logs -f
```

### Performance:

```bash
# Count records
ddev drush sql-query "
SELECT
  type,
  COUNT(*) as count
FROM node_field_data
WHERE type IN ('contact', 'deal', 'organization', 'activity')
GROUP BY type
"
```

---

## 🎯 TÓM TẮT

| Phương pháp      | Tốc độ           | Dễ dùng        | Use case                   |
| ---------------- | ---------------- | -------------- | -------------------------- |
| **Import CSV**   | ⚡⚡⚡ Fast      | 😊 Easy        | Bulk import (100+ records) |
| **Web UI**       | 🐌 Slow          | 😊😊 Very Easy | < 10 records, manual       |
| **Quick Add**    | ⚡ Fast          | 😊😊 Very Easy | 1-2 records, quick         |
| **Drush Script** | ⚡⚡⚡ Very Fast | 🤓 Technical   | Automation, testing        |

**Khuyến nghị:**

- < 10 records: Dùng Quick Add hoặc Web UI
- 10-100 records: Import CSV
- 100+ records: Drush script hoặc API

---

## 🚀 READY TO GO!

Bạn có thể bắt đầu thêm data thật ngay:

```bash
# 1. Login
ddev drush uli

# 2. Import sample data
# Go to: /admin/crm/import/contacts
# Upload: sample_contacts.csv

# 3. Or create manually
# Go to: /node/add/contact

# 4. View your data
# Go to: /contacts
```

**Data sẽ lưu ngay vào MySQL database trong DDEV!** ✅
