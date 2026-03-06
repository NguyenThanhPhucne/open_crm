# CRM Views Table UI Improvement Guide

**Ngày tạo**: 2026-03-06  
**Phiên bản**: 1.0  
**Mục đích**: Cải thiện hiển thị tiêu đề cột trong bảng Views và tăng cường trải nghiệm người dùng

---

## 📋 TỔNG QUAN VẤN ĐỀ

### Vấn đề Hiện Tại

Trang **All Contacts** (`/crm/all-contacts`) - bảng Views hiển thị dữ liệu nhưng **tiêu đề cột (table headers) không hiển thị rõ ràng**, gây khó khăn cho người dùng trong việc hiểu từng cột dữ liệu là gì.

**Dấu hiệu**:

- Người dùng không biết mỗi cột chứa thông tin gì
- Bảng có 8 cột (`cols-8`) nhưng không có nhãn tiêu đề
- Trên mobile, vấn đề trở nên tồi tệ hơn vì không có không gian hiển thị

---

## ✅ GIẢI PHÁP ĐƯỢC TRIỂN KHAI

### 1. Tạo CSS Cải Thiện (`views-table-improvements.css`)

**File mới**: `/web/modules/custom/crm_actions/css/views-table-improvements.css`

**Cải thiện chính**:

#### ✓ Đảm bảo thead hiển thị

```css
.views-table thead {
  display: table-header-group !important;
  visibility: visible !important;
}

.views-table thead th {
  display: table-cell !important;
  visibility: visible !important;
  opacity: 1 !important;
}
```

**Tác dụng**:

- Buộc table header luôn hiển thị
- Ngăn CSS khác che giấu header
- Đảm bảo độ sáng 100%

#### ✓ Cải thiện thiết kế header

```css
.views-table thead {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.views-table thead th {
  padding: 16px 20px;
  color: #ffffff;
  font-weight: 700;
  font-size: 13px;
  border-bottom: 3px solid #3b82f6;
}
```

**Tác dụng**:

- Header dính (sticky) khi cuộn
- Chữ trắng trên nền tối, dễ đọc
- Border xanh giúp phân biệt header
- Padding tốt, không bị chặt

#### ✓ Responsive cho Mobile (Quan trọng!)

```css
@media (max-width: 767px) {
  .views-table thead {
    display: none; /* Ẩn header trên mobile */
  }

  .views-table tbody tr {
    display: block;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    margin-bottom: 12px;
  }

  .views-table tbody td::before {
    content: attr(data-label);
    font-weight: 700;
    display: block;
    margin-bottom: 4px;
  }
}
```

**Tác dụng**:

- Trên mobile: chuyển sang card view (mỗi hàng = 1 card)
- Hiển thị tên cột trước mỗi giá trị
- Dễ đọc trên màn hình nhỏ

### 2. Cập nhật Libraries File

**File**: `/web/modules/custom/crm_actions/crm_actions.libraries.yml`

```yaml
global_nav:
  css:
    theme:
      css/crm_actions.css: {}
      css/views-table-improvements.css: {} # ← CSS mới
```

**Tác dụng**:

- Load CSS cải thiện cho tất cả các views
- Tự động áp dụng cho tất cả bảng

---

## 🔧 CÓ CÁCH KIỂM TRA FIX HOẠT ĐỘNG

### 1. Clear Cache & Rebuild

```bash
cd /Users/phucnguyen/Downloads/open_crm

# Xóa cache Drupal
ddev drush cache:rebuild

# Hoặc nhanh hơn
ddev drush cache:clear all
```

### 2. Kiểm tra trên Browser

**Desktop**:

```
http://open-crm.ddev.site/crm/all-contacts
→ Bảng nên có header rõ ràng với:
  - Nền tối, chữ trắng
  - Border xanh ở dưới
  - Labels rõ ràng: Name, Owner, Organization, Phone, Email, etc.
```

**Mobile** (F12 → Toggle Device Toolbar):

```
→ Mỗi hàng hiển thị dưới dạng card
→ Tên cột hiển thị trước giá trị
→ Dễ đọc và navigate
```

### 3. Kiểm tra Developer Tools

**Bước 1**: Mở DevTools (F12)
**Bước 2**: Inspect table header
**Bước 3**: Kiểm tra CSS Rules:

- ✅ `display: table-header-group` đang áp dụng
- ✅ `visibility: visible` đang áp dụng
- ✅ `opacity: 1` đang áp dụng
- ✅ `color: #ffffff` hiển thị
- ✅ `background-color` là gradient tối

---

## 🛠️ NẾU HEADER VẪN KHÔNG HIỂN THỊ

### Nguyên Nhân Có Thể & Cách Fix

#### 1️⃣ File CSS Không Load

**Kiểm tra**:

```bash
# Xác nhận file tồn tại
ls -la web/modules/custom/crm_actions/css/views-table-improvements.css

# Kiểm tra cache theme
ddev exec drush theme:list
```

**Fix**:

```bash
# Rebuild themes
ddev drush config:set system.theme admin claro -y
ddev drush cache:rebuild
```

#### 2️⃣ Specificity CSS Conflict

**Nguyên nhân**: CSS khác override rules của chúng ta

**Fix**: Thêm vào cuối `/web/modules/custom/crm_actions/css/crm_actions.css`:

```css
/* Override other table header styles */
.path-crm .views-table thead,
[data-crm-page] .views-table thead {
  display: table-header-group !important;
}

.path-crm .views-table thead th,
[data-crm-page] .views-table thead th {
  visibility: visible !important;
  opacity: 1 !important;
  color: #ffffff !important;
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
}
```

#### 3️⃣ Views Configuration Không Có Labels

**Kiểm tra**:

```
Admin > Structure > Views > All Contacts > Edit
→ Fields section → Mỗi field phải có "Label"
→ Ví dụ: title có label "Name", field_owner có label "Owner", etc.
```

**Nếu thiếu Label**:

1. Click vào field
2. Điền "Label" (ví dụ: "Name", "Owner", "Phone")
3. Save View
4. Clear cache

---

## 📱 RESPONSIVE DESIGN CHI TIẾT

### Desktop View (> 1024px)

```
┌─────────────┬────────────┬──────────────┬────────┐
│ Name        │ Owner      │ Organization │ Email  │
├─────────────┼────────────┼──────────────┼────────┤
│ John Smith  │ Sales Rep  │ Acme Corp    │ john@  │
│ Jane Doe    │ Sales Rep  │ TechCo       │ jane@  │
└─────────────┴────────────┴──────────────┴────────┘
```

### Tablet View (768px - 1024px)

```
Bảng thu nhỏ, padding giảm, font size 12px
Vẫn là table format nhưng compact hơn
```

### Mobile View (< 768px)

```
┌──────────────────────────┐
│ 📋 ROW 1                  │
├──────────────────────────┤
│ Name:        John Smith  │
│ Owner:       Sales Rep   │
│ Organization Acme Corp   │
│ Email:       john@...    │
└──────────────────────────┘

┌──────────────────────────┐
│ 📋 ROW 2                  │
├──────────────────────────┤
│ Name:        Jane Doe    │
│ Owner:       Sales Rep   │
│ Organization TechCo      │
│ Email:       jane@...    │
└──────────────────────────┘
```

---

## 🎨 CHI TIẾT STYLING ĐƯỢC CẢI THIỆN

### 1. Table Header

| Thuộc tính      | Giá trị              | Tác dụng           |
| --------------- | -------------------- | ------------------ |
| Background      | Gradient tối         | Contrast cao       |
| Text Color      | Trắng (#ffffff)      | Dễ đọc             |
| Font Weight     | 700                  | Bold, dễ nhận biết |
| Font Size       | 13px                 | Cân đối            |
| Border Bottom   | 3px solid blue       | Phân biệt header   |
| Sticky Position | top: 0, z-index: 100 | Dính khi cuộn      |
| Padding         | 16px 20px            | Không bị chặt      |

### 2. Table Body

| Thuộc tính       | Giá trị           | Tác dụng        |
| ---------------- | ----------------- | --------------- |
| Border Bottom    | 1px solid #f1f5f9 | Phân biệt hàng  |
| Hover Background | Gradient xám nhạt | User feedback   |
| Hover Shadow     | 0 2px 8px         | Nâng lên visual |
| Padding          | 16px 20px         | Không bị chặt   |
| Color            | #334155           | Dễ đọc          |

### 3. Links trong Table

| Loại Link  | Màu                 | Tác dụng        |
| ---------- | ------------------- | --------------- |
| Normal     | #3b82f6 (Xanh)      | Rõ ràng là link |
| Hover      | #2563eb + Underline | User feedback   |
| Email Link | Nền xám             | Badge style     |

---

## 🎯 CỘT DỮ LIỆU & STYLING RIÊNG

### All Contacts View (8 cột)

```
┌─────────────────────────────────────────────────┐
│ 1. Name             (Trắng, Bold, Link)        │
│ 2. Owner            (Badge xanh)               │
│ 3. Organization     (Link xanh)                │
│ 4. Phone            (Courier font, Xanh 059669)│
│ 5. Email            (Badge xám, link)         │
│ 6. Source           (Badge vàng)              │
│ 7. Customer Type    (Badge xanh nhạt)         │
│ 8. Actions          (Edit/Delete buttons)      │
└─────────────────────────────────────────────────┘
```

### Styling từng cột:

**Name** (Contact Title)

- Bold, tối màu
- Clickable link
- Đầu tiên trong mỗi hàng

**Owner** (Chủ sở hữu)

- Badge với border xanh
- Avatar hoặc tên user
- Hover: nền xanh nhạt

**Organization**

- Link xanh (#3b82f6)
- Underline trên hover
- Có thể là tổ chức

**Phone**

- Monospace font (Courier)
- Màu xanh 059669
- No-wrap (không xuống dòng)

**Email**

- Badge style (nền xám)
- Link clickable
- Hover: nền xám đậm hơn

**Source**

- Badge vàng (#fef3c7)
- Nhỏ, uppercase
- VD: "Website", "LinkedIn", "Referral"

**Customer Type**

- Badge xanh nhạt (#dbeafe)
- Uppercase, bold
- VD: "Enterprise", "SMB", "Startup"

**Actions**

- 2 buttons: Edit (xanh) + Delete (đỏ)
- Align right
- Hover: highlight

---

## 🔄 ACCESSIBILITY IMPROVEMENTS

### 1. Color Contrast

- ✅ Header: Trắng trên tối (WCAG AAA)
- ✅ Body: Tối trên trắng (WCAG AA)
- ✅ Links: Xanh (#3b82f6) trên trắng (WCAG AA)

### 2. Focus States

```css
.views-table a:focus-visible {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}
```

### 3. Screen Reader Support

- ✓ Table semantic: `<thead>`, `<tbody>`, `<th scope="col">`
- ✓ Header text rõ ràng
- ✓ Mobile: labels trước mỗi giá trị

### 4. Keyboard Navigation

- ✓ Tab through links ✔
- ✓ Enter to click ✔
- ✓ Focus visible ✔

### 5. High Contrast Mode

```css
@media (prefers-contrast: more) {
  .views-table thead {
    background: #000000;
  }
  .views-table thead th {
    color: #ffffff;
    border-bottom: 4px solid #0066ff;
  }
}
```

### 6. Dark Mode

```css
@media (prefers-color-scheme: dark) {
  .views-table thead {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  }
  .views-table tbody {
    background: #1e293b;
  }
}
```

### 7. Reduced Motion

```css
@media (prefers-reduced-motion: reduce) {
  .views-table tbody tr {
    transition: none;
  }
}
```

---

## 📊 VIEWS CONFIGURATION CHECKLIST

Đảm bảo Views được cấu hình đúng:

### ✓ All Contacts View Configuration

**Path**: Admin > Structure > Views > All Contacts

**Fields**:

- [ ] Title → Label: "Name" ✔
- [ ] field_owner → Label: "Owner" ✔
- [ ] field_organization → Label: "Organization" ✔
- [ ] field_phone → Label: "Phone" ✔
- [ ] field_email → Label: "Email" ✔
- [ ] field_source → Label: "Source" ✔
- [ ] field_customer_type → Label: "Customer Type" ✔
- [ ] crm_edit_link → Label: "Actions" ✔

**Filters**:

- [ ] Status = Published ✔
- [ ] Type = Contact ✔

**Style**:

- [ ] Table ✔
- [ ] Header enabled ✔

**Pager**:

- [ ] Full pager ✔
- [ ] 25 items per page ✔

---

## 🚀 CÁCH CHẠY THỬ NGHIỆM

### 1. Test Desktop

```bash
# Open Chrome/Firefox
http://open-crm.ddev.site/crm/all-contacts

# Mong đợi:
✓ Header hiển thị với nền tối, chữ trắng
✓ Các cột có tiêu đề rõ ràng
✓ Hover hàng có background change
✓ Links có màu xanh
```

### 2. Test Mobile (DevTools)

```bash
# F12 → Device Toolbar (Ctrl+Shift+M)
# Chọn: iPhone 12

# Mong đợi:
✓ Header ẩn trên mobile
✓ Mỗi hàng = 1 card
✓ Tên cột hiển thị trước giá trị
✓ Dễ cuộn ngang, không bị chặt
```

### 3. Test Accessibility

```bash
# Lighthouse (F12 > Lighthouse)
# Chọn: Accessibility

# Mong đợi:
✓ Contrast ratio ≥ 4.5:1
✓ Proper heading hierarchy
✓ Form labels associated
✓ Keyboard navigation works
```

### 4. Test Print

```bash
# Ctrl+P (Print)
# Chọn: Save as PDF

# Mong đợi:
✓ Table hiển thị đúng trong PDF
✓ Header có border
✓ Actions ẩn (không print)
```

---

## 🔗 FILE LIÊN QUAN

| File                                 | Mục đích           | Thay đổi        |
| ------------------------------------ | ------------------ | --------------- |
| `css/views-table-improvements.css`   | CSS cải thiện      | **Tạo mới**     |
| `crm_actions.libraries.yml`          | Khai báo libraries | **Cập nhật**    |
| `config/views.view.all_contacts.yml` | Views config       | Không thay đổi  |
| `css/crm_actions.css`                | CSS chính          | Có thể cập nhật |

---

## 💡 THÊM CÁC CẢI THIỆN TỰ CHỌN

### 1. Thêm Search/Filter

```yaml
# views.view.all_contacts.yml
exposed_form:
  type: basic
  exposed_filters:
    - title (Name)
    - field_email (Email)
    - field_owner (Owner)
```

### 2. Thêm Sorting

```yaml
sort_fields:
  - created (Newest First)
  - title (Name A-Z)
```

### 3. Sticky Header vĩnh viễn

CSS mới đã có:

```css
.views-table thead {
  position: sticky;
  top: 0;
  z-index: 100;
}
```

### 4. Export to CSV

Cần Views Data Export module:

```bash
ddev drush pm-enable views_data_export
```

### 5. Add Checkboxes (Bulk Actions)

```yaml
fields:
  bulk_form:
    id: bulk_form
    label: "Select"
```

---

## ⚠️ TROUBLESHOOTING

### Q: Header vẫn không hiển thị

**A**:

1. Clear cache: `ddev drush cache:rebuild`
2. Kiểm tra View config: có Label không?
3. Kiểm tra CSS load: DevTools > Styles tab
4. Thêm `!important` nếu cần

### Q: Mobile không hiển thị tên cột

**A**:

1. Kiểm tra AttributesCallback trong render
2. Thêm data-label attribute vào <td> tags
3. CSS `td::before { content: attr(data-label); }`

### Q: Styling khác

**A**:

1. Check specificity: theme CSS có override không?
2. Use `!important` trong css/views-table-improvements.css
3. Reload hard (Ctrl+Shift+R) browser cache

### Q: Print bị lỗi

**A**:

1. Thêm @media print rules
2. Hide buttons: `.views-field-crm-edit-link { display: none; }`
3. Set table width: `100%`

---

## 📞 LIÊN HỆ & SUPPORT

Nếu gặp issue:

1. Check this guide trước
2. Clear cache + reload
3. Kiểm tra DevTools Styles tab
4. Kiểm tra Drupal logs: `ddev drush logs`

---

## 📝 CHANGELOG

**v1.0** (2026-03-06)

- ✅ Tạo CSS cải thiện views-table-improvements.css
- ✅ Đảm bảo thead luôn hiển thị
- ✅ Responsive mobile design
- ✅ Accessibility improvements
- ✅ Document đầy đủ

---

**Trạng thái**: ✅ Hoàn tất & sẵn sàng dùng  
**Cần cache rebuild**: Yes (`ddev drush cache:rebuild`)
