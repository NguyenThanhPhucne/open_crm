# 🔧 CÁC LỖI CẦN FIX VÀ GIẢI PHÁP

## ❌ LỖI 1: Role Machine Names không nhất quán

### Vấn đề:

- Script `add_owner_fields.sh` dùng: `'sales_manager'` và `'sales_rep'`
- Script `create_roles_and_permissions.sh` tạo: `'sales_representative'` và `'sales_manager'`
- ➡️ Filter owner field sẽ không hoạt động vì role names sai

### Giải pháp:

Thống nhất dùng: `sales_representative` và `sales_manager`

---

## ❌ LỖI 2: Sample Data không có Owner fields

### Vấn đề:

- Script `create_sample_data.sh` tạo Contacts, Deals, Activities, Organizations
- NHƯNG không set `field_owner`, `field_assigned_to`, `field_assigned_staff`
- ➡️ Data không có owner ➡️ Views filter bị lỗi ➡️ Sales reps không xem được gì

### Giải pháp:

Cập nhật `create_sample_data.sh` để set owner khi tạo data

---

## ❌ LỖI 3: Field default value không hoạt động

### Vấn đề:

```php
'default_value' => [
  [
    'target_id' => \Drupal::currentUser()->id(),
  ],
],
```

- Code này chạy lúc CREATE FIELD (admin đang đăng nhập)
- Không phải lúc CREATE NODE (sales rep đang đăng nhập)
- ➡️ Default value = admin uid, không phải current user

### Giải pháp:

Dùng `default_value_callback` hoặc hook_form_alter để set dynamic

---

## ❌ LỖI 4: Taxonomy field names sai

### Vấn đề:

- Create taxonomy: `deal_stage`, `activity_type`, `contact_source`, `industry`
- Sample data dùng: `field_stage`, `field_type`, `field_source`, `field_industry`
- Nhưng content types có thể dùng tên khác: `field_lead_source` vs `field_source`

### Giải pháp:

Kiểm tra field names chính xác từ content types

---

## ❌ LỖI 5: Views arguments configuration phức tạp

### Vấn đề:

- `update_views_data_privacy.sh` tạo views với contextual filter uid
- Code quá dài, dễ sai
- Không có views cho Manager (xem all team)

### Giải pháp:

- Tạo 2 views riêng: "My Data" (sales rep) và "Team Data" (manager)
- Dùng access control thay vì contextual filter

---

## ❌ LỖI 6: Organization field name

### Vấn đề:

- Script dùng `field_assigned_staff` cho Organization
- Nên dùng `field_owner` để nhất quán với Contact/Deal

### Giải pháp:

Đổi Organization dùng `field_owner` thay vì `field_assigned_staff`

---

## ✅ CÁC SCRIPT CẦN FIX

1. **add_owner_fields.sh**
   - Fix role names: `sales_rep` → `sales_representative`
   - Bỏ default_value (không hoạt động)
   - Đổi Organization: `field_assigned_staff` → `field_owner`

2. **create_sample_data.sh**
   - Thêm `field_owner` khi tạo Contact/Deal/Organization
   - Thêm `field_assigned_to` khi tạo Activity
   - Assign data cho đúng user (salesrep1, salesrep2)

3. **update_views_data_privacy.sh**
   - Đơn giản hóa view configuration
   - Tạo views cho cả Sales Rep và Manager
   - Add proper access control

4. **Tạo script mới: fix_owner_default_value.sh**
   - Hook vào form để set default = current user
   - Hoặc dùng field widget default value callback

---

## 🎯 WORKFLOW ĐÚNG

1. Create taxonomies ✅
2. Create content types ✅
3. Create roles & permissions ✅
4. **Add owner fields** (với role names + field names ĐÚNG)
5. **Configure form displays** (default value callback)
6. **Create sample users** ✅
7. **Create sample data** (với owner fields)
8. **Update views** (với data privacy filters)
9. **Configure permissions** (view own content only)
10. **Setup dashboard** ✅

---

## 🚀 NEXT STEPS

1. Fix `add_owner_fields.sh`
2. Fix `create_sample_data.sh`
3. Create `fix_field_default_values.sh`
4. Test complete workflow
5. Create unified `setup_crm_complete.sh`
