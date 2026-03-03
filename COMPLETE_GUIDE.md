# 🎯 HƯỚNG DẪN HOÀN CHỈNH - OPEN CRM SYSTEM

> **Professional Customer Relationship Management System**  
> Built with Drupal 11 + Modern UI (Tailwind/Stripe/Vercel/Notion style)

---

## 📋 MỤC LỤC

1. [Tổng Quan Hệ Thống](#tổng-quan-hệ-thống)
2. [Kiến Trúc & Tính Năng](#kiến-trúc--tính-năng)
3. [Cài Đặt & Thiết Lập](#cài-đặt--thiết-lập)
4. [Sử Dụng Hệ Thống](#sử-dụng-hệ-thống)
5. [Quyền Hạn & Bảo Mật](#quyền-hạn--bảo-mật)
6. [Code Structure](#code-structure)
7. [Troubleshooting](#troubleshooting)
8. [Mở Rộng](#mở-rộng)

---

## 🌟 TỔNG QUAN HỆ THỐNG

### Mục Đích

Hệ thống CRM chuyên nghiệp để quản lý:

- **Khách hàng (Contacts)**: Thông tin chi tiết, nguồn khách, tổ chức
- **Giao dịch (Deals)**: Pipeline, giá trị, xác suất thành công
- **Hoạt động (Activities)**: Call, Email, Meeting, Task
- **Tổ chức (Organizations)**: Công ty, website, ngành nghề

### Đặc Điểm Nổi Bật

✅ **Data Privacy**: Sales reps chỉ xem data của mình  
✅ **Owner Tracking**: Tự động assign content cho người tạo  
✅ **Role-based Access**: Rep (own data) vs Manager (all data)  
✅ **Modern UI**: Tailwind stat cards, Stripe buttons, Notion tables  
✅ **Professional Theme**: Gin Light Mode - sáng sủa, thanh lịch

---

## 🏗️ KIẾN TRÚC & TÍNH NĂNG

### Content Types

#### 1. **Contact** (Khách hàng)

```yaml
Fields:
  - title: Name (Họ tên)
  - field_email: Email
  - field_phone: Phone
  - field_position: Position (Chức vụ)
  - field_organization: Organization (Entity Reference)
  - field_lead_source: Lead Source (Taxonomy: Website, Referral, Event...)
  - field_owner: Owner (User Reference) - Sales phụ trách
```

#### 2. **Deal** (Giao dịch)

```yaml
Fields:
  - title: Deal Name
  - field_amount: Amount (Giá trị deal)
  - field_probability: Probability (%) - Xác suất thành công
  - field_stage: Deal Stage (Taxonomy: Prospect, Qualified, Proposal, Negotiation, Won, Lost)
  - field_closing_date: Closing Date
  - field_contact: Contact (Entity Reference)
  - field_organization: Organization (Entity Reference)
  - field_owner: Owner (User Reference)
```

#### 3. **Activity** (Hoạt động)

```yaml
Fields:
  - title: Activity Title
  - field_activity_type: Activity Type (Taxonomy: Call, Email, Meeting, Task)
  - field_datetime: Date & Time
  - field_description: Description
  - field_contact: Contact (Entity Reference)
  - field_deal: Deal (Entity Reference)
  - field_assigned_to: Assigned To (User Reference) - Người xử lý
```

#### 4. **Organization** (Tổ chức)

```yaml
Fields:
  - title: Organization Name
  - field_website: Website (Link field)
  - field_address: Address
  - field_industry: Industry (Taxonomy: Technology, Finance, Healthcare, Retail...)
  - field_owner: Owner (User Reference)
```

### Taxonomies

| Vocabulary        | Terms                                                  |
| ----------------- | ------------------------------------------------------ |
| **deal_stage**    | Prospect, Qualified, Proposal, Negotiation, Won, Lost  |
| **activity_type** | Call, Email, Meeting, Task                             |
| **lead_source**   | Website, Referral, Event, Call, Social Media           |
| **industry**      | Technology, Finance, Healthcare, Retail, Manufacturing |

### User Roles

| Role                     | Permissions                | Use Case                          |
| ------------------------ | -------------------------- | --------------------------------- |
| **Administrator**        | Full control               | System admin, configuration       |
| **Sales Manager**        | View/Edit ALL content      | Team leader, reports              |
| **Sales Representative** | View/Edit OWN content only | Individual sales rep              |
| **Customer**             | Limited view               | External customer portal (future) |

---

## ⚙️ CÀI ĐẶT & THIẾT LẬP

### Prerequisites

- DDEV 1.23+
- Docker Desktop running
- Drupal 11.3+
- PHP 8.2+

### Quick Setup (Từ Đầu)

```bash
# Clone project
cd /path/to/open_crm

# Start DDEV
ddev start

# Run complete setup script
bash scripts/setup_crm_complete.sh
```

**Script này sẽ tự động:**

1. ✅ Tạo taxonomies (4 vocabularies)
2. ✅ Tạo content types (4 types)
3. ✅ Thêm owner fields với role filters đúng
4. ✅ Tạo roles & configure permissions
5. ✅ Tạo sample users (admin, manager, salesrep1, salesrep2)
6. ✅ Tạo sample data WITH owners
7. ✅ Tạo views (Contacts, Deals, Activities, Organizations)
8. ✅ Setup professional dashboard
9. ✅ Apply CSS improvements (Tailwind/Stripe/Notion)
10. ✅ Set light mode theme

### Manual Setup (Từng Bước)

```bash
# 1. Taxonomies
bash scripts/create_taxonomies.sh

# 2. Content Types
bash scripts/create_content_types.sh

# 3. Owner Fields (FIXED VERSION)
bash scripts/fix_owner_fields_v2.sh

# 4. Roles & Permissions
bash scripts/create_roles_and_permissions.sh
bash scripts/update_permissions_v2.sh

# 5. Sample Users
bash scripts/create_sample_users.sh

# 6. Sample Data (WITH OWNERS)
bash scripts/create_sample_data_v2.sh

# 7. Views
bash scripts/create_views.sh

# 8. UI Improvements
bash scripts/restore_professional_ui.sh
bash scripts/improve_dashboard_css.sh
bash scripts/improve_table_css.sh
bash scripts/fix_homepage_login.sh

# 9. Light Mode
ddev drush config:set gin.settings enable_darkmode 0 -y
ddev drush cr
```

---

## 🚀 SỬ DỤNG HỆ THỐNG

### Đăng Nhập

**URL**: `http://open-crm.ddev.site`

| User        | Password     | Role          | Data Access                            |
| ----------- | ------------ | ------------- | -------------------------------------- |
| `admin`     | `aa5BLB69Jt` | Administrator | All                                    |
| `manager`   | `manager123` | Sales Manager | All team data                          |
| `salesrep1` | `sales123`   | Sales Rep     | Own data only (Acme, Global)           |
| `salesrep2` | `sales123`   | Sales Rep     | Own data only (Tech Solutions, Retail) |

### Dashboard Features

**Homepage (Anonymous):**

- 🔐 Large "Login to Dashboard" button
- 📋 Demo accounts table with credentials
- 🚀 Quick Start guide
- ✨ Feature highlights

**Dashboard (Authenticated):**

```
┌─────────────────────────────────────┐
│  Welcome to Open CRM                │
├─────────────────────────────────────┤
│  📊 Stats Cards (Tailwind style)   │
│  ┌─────┐ ┌─────┐ ┌─────┐ ┌─────┐  │
│  │ 234 │ │  45 │ │ $2M │ │ 89% │  │
│  │Contc│ │Deals│ │Value│ │ Win │  │
│  └─────┘ └─────┘ └─────┘ └─────┘  │
│                                     │
│  ⚡ Quick Actions (Stripe buttons) │
│  [+ New Contact]  [+ New Deal]     │
│  [+ New Activity] [View Reports]   │
│                                     │
│  🧭 Navigation Cards                │
│  ┌────────┐ ┌────────┐ ┌────────┐ │
│  │Contacts│ │Pipeline│ │Activity│ │
│  └────────┘ └────────┘ └────────┘ │
└─────────────────────────────────────┘
```

**Table Views (Notion/Linear style):**

- Hover effects: rows turn light blue (#f0f9ff)
- Modern headers: gray background, uppercase text
- Responsive: mobile converts to cards
- Clean typography: tabular numbers, readable fonts

### Workflows

#### 1. Sales Rep Tạo Contact Mới

```
1. Login as salesrep1
2. Click "Contacts" → "Add Contact"
3. Fill form:
   - Name: Jane Doe
   - Email: jane@example.com
   - Phone: +1-555-9999
   - Organization: [Select existing]
   - Owner: [Auto-filled = salesrep1]
4. Save
5. ✅ Contact created, visible ONLY to salesrep1 and managers
```

#### 2. Manager Xem Toàn Bộ Deal

```
1. Login as manager
2. Click "Pipeline" (Deals view)
3. See ALL deals from ALL sales reps
4. Filter by Stage, Owner, Amount
5. Can edit ANY deal (reassign owner, update stage)
```

#### 3. Sales Rep Tạo Activity

```
1. Login as salesrep2
2. Click "Activities" → "Add Activity"
3. Fill form:
   - Title: Follow-up call
   - Type: Call
   - Date/Time: Tomorrow 10:00 AM
   - Contact: [Select from own contacts]
   - Deal: [Select from own deals]
   - Assigned To: [Auto-filled = salesrep2]
4. Save
5. ✅ Activity visible in calendar/list
```

---

## 🔒 QUYỀN HẠN & BẢO MẬT

### Data Privacy Model

#### Sales Representative

**Permissions:**

```
✅ Create: contact, deal, activity, organization
✅ Edit OWN: Chỉ edit content mà mình là owner
✅ Delete OWN: Chỉ delete own content
❌ View/Edit OTHER: Không thấy content của sales reps khác
```

**How it works:**

1. **Node Access**: `hook_node_access()` checks `field_owner`
2. **Query Alter**: Views tự động filter để chỉ show own content
3. **Form Default**: Khi tạo mới, owner = current user

#### Sales Manager

**Permissions:**

```
✅ Create: All content types
✅ Edit ANY: Có thể edit content của bất kỳ ai
✅ Delete ANY: Delete any content
✅ View reports: Dashboard với stats của toàn team
```

**BYPASS:** Managers bypass tất cả owner filters trong views.

### Security Best Practices

1. **Owner Field Required**: Tất cả content PHẢI có owner
2. **Role Filter**: Owner field chỉ chọn được Sales Rep/Manager users
3. **API Access**: Nếu có REST API, apply same owner filters
4. **Export Data**: Chỉ export own data
5. **Log Activities**: Track ai edit/delete content của ai

---

## 📁 CODE STRUCTURE

### Scripts (Quan Trọng)

#### ✅ Fixed & Working

- `fix_owner_fields_v2.sh`: Tạo owner fields với role names ĐÚNG
- `create_sample_data_v2.sh`: Sample data VỚI owner assignments
- `update_permissions_v2.sh`: Permissions + access control logic
- `setup_crm_complete.sh`: All-in-one setup script

#### ⚠️ Legacy (Có Lỗi, Không Dùng)

- `add_owner_fields.sh`: Role names sai (`sales_rep` thay vì `sales_representative`)
- `create_sample_data.sh`: Thiếu owner fields
- `update_role_permissions.sh`: Permissions không đủ

### Custom Module (Cần Tạo)

**Location**: `web/modules/custom/crm/`

**Required Files:**

```
crm/
├── crm.info.yml
├── crm.module
└── crm.install
```

**crm.module** (Critical Code):

```php
<?php

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Implements hook_node_access().
 */
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $types = ['contact', 'deal', 'activity', 'organization'];

  if (!in_array($node->bundle(), $types)) {
    return AccessResult::neutral();
  }

  // Admin & Manager: full access
  if ($account->hasRole('administrator') || $account->hasRole('sales_manager')) {
    return AccessResult::neutral();
  }

  // Sales Rep: own content only
  if ($account->hasRole('sales_representative')) {
    $owner_field = $node->bundle() === 'activity' ? 'field_assigned_to' : 'field_owner';

    if ($node->hasField($owner_field)) {
      $owner_id = $node->get($owner_field)->target_id;

      if ($owner_id == $account->id()) {
        return AccessResult::allowed()->cachePerUser()->addCacheableDependency($node);
      } else {
        return AccessResult::forbidden('You can only access your own content')
          ->cachePerUser()
          ->addCacheableDependency($node);
      }
    }
  }

  return AccessResult::neutral();
}

/**
 * Implements hook_form_alter().
 * Set default owner = current user when creating new content.
 */
function crm_form_node_form_alter(&$form, $form_state, $form_id) {
  $node = $form_state->getFormObject()->getEntity();

  if ($node->isNew()) {
    $current_user = \Drupal::currentUser();

    // Set field_owner for Contact, Deal, Organization
    if (in_array($node->bundle(), ['contact', 'deal', 'organization'])) {
      if (isset($form['field_owner'])) {
        $form['field_owner']['widget'][0]['target_id']['#default_value'] =
          \Drupal\user\Entity\User::load($current_user->id());
      }
    }

    // Set field_assigned_to for Activity
    if ($node->bundle() === 'activity') {
      if (isset($form['field_assigned_to'])) {
        $form['field_assigned_to']['widget'][0]['target_id']['#default_value'] =
          \Drupal\user\Entity\User::load($current_user->id());
      }
    }
  }
}
```

**Enable Module:**

```bash
ddev drush en crm -y
ddev drush cr
```

---

## 🐛 TROUBLESHOOTING

### ❌ Lỗi: "Sales rep thấy data của người khác"

**Nguyên nhân:** Owner fields chưa được tạo đúng hoặc data thiếu owner.

**Fix:**

```bash
# 1. Re-create owner fields
bash scripts/fix_owner_fields_v2.sh

# 2. Update existing data với owners
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact']);
foreach (\$nodes as \$node) {
  if (\$node->get('field_owner')->isEmpty()) {
    \$node->set('field_owner', 1); // admin
    \$node->save();
  }
}
"

# 3. Enable CRM module (access control)
ddev drush en crm -y
ddev drush cr
```

### ❌ Lỗi: "Field owner không có default value"

**Nguyên nhân:** Static default value trong field config không hoạt động.

**Fix:** Dùng `hook_form_alter()` trong CRM module (xem Code Structure).

### ❌ Lỗi: "Views không filter theo owner"

**Nguyên nhân:** Views chưa có contextual filter hoặc access plugins.

**Fix:**

```bash
# Option 1: Update views với contextual filter
bash scripts/update_views_data_privacy.sh

# Option 2: Dùng hook_query_alter() trong CRM module
# (Recommended - automatic filtering)
```

### ❌ Lỗi: "Manager không thấy data của team"

**Nguyên nhân:** Permissions thiếu "edit any" hoặc access control quá strict.

**Fix:**

```bash
# Update manager permissions
bash scripts/update_permissions_v2.sh

# Check trong hook_node_access: BYPASS cho manager role
```

### ❌ Lỗi: "CSS không load"

**Nguyên nhân:** Cache chưa clear hoặc file CSS path sai.

**Fix:**

```bash
ddev drush cr
ddev drush cc css-js

# Kiểm tra file tồn tại
ls -la web/sites/default/files/crm_custom_tables.css
```

---

## 🚀 MỞ RỘNG

### Tính Năng Nên Thêm

#### 1. **Email Integration**

- Send emails từ trong CRM
- Track email opens/clicks
- Link emails với Contacts/Deals

#### 2. **Calendar View**

- Full calendar cho Activities
- Drag & drop để reschedule
- Color-code theo activity type

#### 3. **Reports & Analytics**

- Sales funnel chart (deals by stage)
- Revenue forecast (amount × probability)
- Activity heatmap (calls/meetings per day)
- Leaderboard (top performers)

#### 4. **Pipeline Kanban Board**

- Drag deals giữa các stages
- Visual pipeline management
- Quick edit deal details

#### 5. **Mobile App**

- React Native hoặc PWA
- Quick add contacts/activities
- Push notifications

#### 6. **Import/Export**

- CSV import cho contacts/deals
- Excel export với filters
- API endpoints (REST/GraphQL)

### Custom Fields

**Để thêm field mới:**

```bash
# Example: Add "Birthday" field vào Contact
ddev drush eval "
\$storage = \Drupal\field\Entity\FieldStorageConfig::create([
  'field_name' => 'field_birthday',
  'entity_type' => 'node',
  'type' => 'datetime',
  'settings' => ['datetime_type' => 'date'],
]);
\$storage->save();

\$field = \Drupal\field\Entity\FieldConfig::create([
  'field_storage' => \$storage,
  'bundle' => 'contact',
  'label' => 'Birthday',
]);
\$field->save();
"

ddev drush cr
```

### Integrations

**Salesforce, HubSpot, Mailchimp:**

- Dùng Drupal contrib modules hoặc REST API
- Sync contacts/deals 2-way
- Webhook listeners cho real-time updates

---

## 📚 TÀI LIỆU THAM KHẢO

### Scripts & Commands

- `scripts/FIX_ISSUES.md`: Các lỗi đã fix và giải pháp
- `scripts/login_guide.sh`: Hướng dẫn đăng nhập chi tiết
- `scripts/verify_complete_system.sh`: Kiểm tra toàn bộ system

### Drupal Docs

- [Entity API](https://www.drupal.org/docs/drupal-apis/entity-api)
- [Node Access](https://www.drupal.org/docs/drupal-apis/entity-api/access-checking)
- [Views](https://www.drupal.org/docs/user_guide/en/views-chapter.html)
- [Field API](https://www.drupal.org/docs/drupal-apis/field-api)

### UI References

- **Tailwind CSS**: https://tailwindcss.com/docs
- **Stripe Design**: https://stripe.com/docs/design
- **Linear UI**: https://linear.app
- **Notion Tables**: https://notion.so

---

## 🎉 KẾT LUẬN

Hệ thống CRM này cung cấp:

✅ **Complete CRM functionality** - Contacts, Deals, Activities, Organizations  
✅ **Data privacy & security** - Role-based access, owner tracking  
✅ **Modern professional UI** - Tailwind/Stripe/Vercel/Notion style  
✅ **Scalable architecture** - Drupal 11, clean code, documented  
✅ **Production-ready** - Permissions, access control, sample data

**Next Steps:**

1. Test workflow với sample users
2. Customize fields theo nhu cầu
3. Add reports & analytics
4. Deploy to staging/production
5. Train users và collect feedback

**Support:**

- GitHub Issues: (your repo URL)
- Email: (your email)
- Drupal Community: https://drupal.org/community

---

**Built with ❤️ using Drupal 11 + DDEV**  
_Last Updated: March 2026_
