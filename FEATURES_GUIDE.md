# OpenCRM - Hướng dẫn sử dụng các tính năng mới

## ✅ Đã hoàn thành 7/8 tính năng chính

### 1. 🏷️ Taxonomies (Phân loại)

- **CRM Source** (Nguồn khách): 6 loại (Website, Email, Phone, Referral, Social Media, Trade Show)
- **CRM Industry** (Ngành): 10 ngành (Technology, Healthcare, Finance, Retail, ...)
- **Customer Type** (Loại khách): 5 loại (Individual, SMB, Mid-Market, Enterprise, Government)

### 2. 📋 Trường bổ sung

- `field_source`: Theo dõi nguồn khách hàng
- `field_customer_type`: Phân loại khách hàng
- `field_industry`: Ngành nghề
- `field_probability`: Tỉ lệ chốt deal (0-100%)
- `field_contract`: Upload hợp đồng (PDF/DOC/XLS, tối đa 10MB)

### 3. ⚡ Quick Add Modal

**Truy cập**: Click nút "Quick Add" (màu xanh lá) trên thanh navigation

**Tính năng**:

- Tạo nhanh Contact với kiểm tra trùng lặp
- Tạo nhanh Deal với tự động tính xác suất
- Tạo nhanh Organization
- Tạo Organization trực tiếp từ form Contact
- Validation real-time và thông báo thành công

**Cách dùng**:

```
1. Click "Quick Add" hoặc vào /node/add/contact
2. Điền thông tin (email/phone sẽ check trùng tự động)
3. Submit → Thông báo xanh → Redirect về trang chi tiết
```

### 4. 📝 Activity Logging (Ghi nhận tương tác)

**Module**: `crm_activity_log`

**Truy cập**:

- Từ trang Contact: Tab "Lịch sử tương tác"
- Log Call: `/crm/activity/log-call/{contact_id}`
- Schedule Meeting: `/crm/activity/schedule-meeting/{contact_id}`

**Tính năng**:

- **Activity Timeline**: Xem lịch sử tương tác theo dạng timeline
- **Log Call**: Ghi nhận cuộc gọi với 7 kết quả:
  - ✅ Khách nghe máy - Quan tâm
  - ⚠️ Khách nghe máy - Chưa quan tâm
  - ❌ Khách nghe máy - Từ chối
  - 📵 Không nghe máy
  - 📞 Thuê bao / Máy bận
  - 🔄 Hẹn gọi lại sau
  - 📅 Đặt lịch meeting
- **Schedule Meeting**: Đặt lịch hẹn với date/time picker
- **Quick Actions**: Widget trên trang Contact với 3 nút nhanh
- **Auto-update**: Tự động cập nhật "Last Contacted Date"

**Cách dùng Log Call**:

```
1. Vào trang Contact (vd: /node/4)
2. Click "Log Call" từ Quick Actions hoặc header
3. Chọn Outcome từ dropdown
4. Nhập ghi chú (optional)
5. Chọn Deal liên quan (optional)
6. Submit → Activity xuất hiện trong Timeline
```

### 5. 💼 Deal Closing Logic

**Module**: `crm_kanban` (đã nâng cấp)

**Truy cập**: http://open-crm.ddev.site/crm/pipeline

**Tính năng**:

- **Kanban Board**: 6 cột (New, Qualified, Proposal, Negotiation, Won, Lost)
- **Drag & Drop**: Kéo deal giữa các cột
- **Won Stage Modal**: Khi kéo deal vào "Won" → Popup xuất hiện
- **Required Fields**:
  - Ngày chốt deal (date picker)
  - File hợp đồng (PDF/DOC/DOCX, max 10MB)
- **Validation**: Check file type và size
- **Auto-save**: Lưu file vào `field_contract` và `field_closing_date`
- **Notification**: Thông báo xanh "✅ Đã chốt deal thành công!"

**Cách dùng**:

```
1. Vào Pipeline: /crm/pipeline
2. Kéo 1 deal vào cột "Won"
3. Modal xuất hiện với form
4. Chọn ngày chốt (default = hôm nay)
5. Click vùng upload → Chọn file hợp đồng
6. Click "Xác nhận chốt deal"
7. Success → Reload tự động → File đã save
```

**Lưu ý**:

- File phải là PDF/DOC/DOCX/XLS/XLSX
- Kích thước tối đa 10MB
- Nếu Cancel modal → Deal tự động quay lại cột cũ

### 6. 🧭 Global Navigation Bar

**Module**: `crm_actions` (đã nâng cấp)

**Vị trí**: Sticky top trên mọi trang CRM

**Menu gồm**:

1. **OpenCRM** (Brand logo)
2. **Dashboard** → /crm/dashboard
3. **Contacts** → /crm/my-contacts
4. **Pipeline** → /crm/pipeline
5. **Activities** → /crm/my-activities
6. **Organizations** → /app/organizations
7. **Quick Add** (button xanh lá)

**Tính năng**:

- Active page highlight (nền xanh gradient)
- Hover effects mượt mà
- Responsive cho mobile
- Lucide icons đẹp mắt

### 7. 👤 Contact 360 View

**Module**: `crm_contact360`

**Truy cập**: Mở bất kỳ contact nào (vd: /node/4)

**Giao diện mới gồm**:

**A. Header Card (Gradient tím-hồng)**:

- Avatar circle với chữ cái đầu
- Tên + Chức vụ + Công ty
- Tags: Customer Type + Source
- Quick Actions (4 nút bên phải):
  - 📞 Call (tel: link)
  - ✉️ Email (mailto: link)
  - 📞 Log Call
  - 📅 Schedule Meeting

**B. Stats Cards (3 thẻ)**:

- 📈 Active Deals: Số deal đang chạy
- 💰 Pipeline Value: Tổng giá trị deal ($XXM)
- ⚡ Activities: Số hoạt động đã ghi

**C. Related Deals Widget**:

- List 5 deal gần nhất
- Hiển thị: Tên + Amount + Stage badge
- Click vào deal → Chuyển đến trang deal
- Empty state với button "Create First Deal"
- Link "View all deals" ở cuối

**D. Recent Activities Timeline**:

- List 5 activity gần nhất
- Icon color theo type: Call=blue, Meeting=purple, Email=green
- Hiển thị: Title + Description + Time
- Empty state nếu chưa có activity

**Màu sắc Stage**:

- New: Xanh dương
- Qualified: Tím
- Proposal: Cam
- Negotiation: Hồng
- Won: Xanh lá
- Lost: Đỏ

---

## 🔗 Quick Links Reference

### Main Pages

| Page          | URL                  | Mô tả                |
| ------------- | -------------------- | -------------------- |
| Dashboard     | `/crm/dashboard`     | Tổng quan hệ thống   |
| Contacts      | `/crm/my-contacts`   | Danh sách khách hàng |
| Pipeline      | `/crm/pipeline`      | Kanban board deals   |
| Activities    | `/crm/my-activities` | Hoạt động của tôi    |
| Organizations | `/app/organizations` | Danh sách công ty    |

### Quick Add

| Entity       | URL                        |
| ------------ | -------------------------- |
| Contact      | `/node/add/contact`        |
| Deal         | `/node/add/deal`           |
| Organization | `/node/add/organization`   |
| Activity     | Dùng Log Call/Meeting form |

### Activity Forms

| Form             | URL Pattern                                   |
| ---------------- | --------------------------------------------- |
| Log Call         | `/crm/activity/log-call/{contact_id}`         |
| Schedule Meeting | `/crm/activity/schedule-meeting/{contact_id}` |
| Activities Tab   | `/node/{contact_id}/activities`               |

---

## 🎯 Testing Checklist

### Test 1: Navigation Bar

- [ ] Mở /crm/my-contacts
- [ ] Thấy navigation bar ở top
- [ ] Click "Pipeline" → Chuyển sang /crm/pipeline
- [ ] Page "Pipeline" được highlight xanh
- [ ] Click "Quick Add" → Mở form tạo contact

### Test 2: Contact 360 View

- [ ] Mở /node/4
- [ ] Thấy header gradient với avatar
- [ ] Thấy 3 stats cards
- [ ] Click "Log Call" → Modal xuất hiện
- [ ] Scroll xuống thấy Related Deals + Activities

### Test 3: Activity Logging

- [ ] Từ Contact page, click "Log Call"
- [ ] Chọn outcome "✅ Khách nghe máy - Quan tâm"
- [ ] Nhập notes "Khách quan tâm package Enterprise"
- [ ] Submit → Thấy success message
- [ ] Quay lại Contact → Thấy activity trong timeline

### Test 4: Deal Closing

- [ ] Mở /crm/pipeline
- [ ] Tạo 1 deal test hoặc dùng deal có sẵn
- [ ] Kéo deal vào cột "Won"
- [ ] Modal xuất hiện
- [ ] Chọn ngày chốt
- [ ] Upload file PDF test
- [ ] Submit → Success → Deal đã ở Won
- [ ] Mở deal detail → Check file contract đã save

### Test 5: Quick Add Modal

- [ ] Click "Quick Add" trên nav bar
- [ ] Điền tên "Test User" + email "test@example.com"
- [ ] Điền phone "0901234567"
- [ ] Submit → Success notification
- [ ] Redirect về trang contact mới
- [ ] Thử tạo lại với cùng email → Báo lỗi trùng

---

## ⏳ Tính năng sẽ làm tiếp

### 8. Team Permissions (Group Module)

**Yêu cầu từ Excel**:

- Sales Team A không xem được data của Team B
- Manager xem được tất cả
- Owner-based filtering

**Implementation**:

- Install Group module
- Tạo groups (Sales Team A, B, C...)
- Config group permissions
- Add group filter vào views
- Role assignment UI

### 9. Admin Dashboard Enhancement

**Yêu cầu từ Excel**:

- System-wide KPIs
- Revenue charts (Chart.js)
- Conversion rate metrics
- Top performers leaderboard
- Export reports

**Implementation**:

- Enhance /crm/dashboard
- Add Chart.js library
- Create KPI widgets
- Add filtering by date range
- Export to CSV/Excel

---

## 🐛 Known Issues & Limitations

### Activity Logging

- ~~Field names mismatch đã fix (field_contact, field_type, field_description)~~
- Email notification chưa implement (TODO trong code)

### Deal Closing

- File upload chỉ support PDF/DOC/DOCX/XLS/XLSX
- Email to manager chưa có (cần Rules module)

### Contact 360

- Avatar chỉ show chữ cái đầu (chưa có upload avatar)
- Related entities limit = 5 items

---

## 📚 Technical Documentation

### Modules Created/Modified

1. `crm_activity_log` - Activity logging system
2. `crm_kanban` - Pipeline with deal closing
3. `crm_actions` - Global navigation bar
4. `crm_contact360` - Contact 360 view
5. `crm_quickadd` - Quick add modals (existing, enhanced)

### Files Structure

```
web/modules/custom/
├── crm_activity_log/
│   ├── src/Controller/ActivityLogController.php
│   ├── templates/
│   │   ├── activity-log-tab.html.twig
│   │   ├── log-call-form.html.twig
│   │   ├── schedule-meeting-form.html.twig
│   │   └── activity-quick-actions.html.twig
│   ├── css/activity-widget.css
│   └── js/activity-widget.js
├── crm_kanban/
│   └── src/Controller/KanbanController.php (922 lines with modal)
├── crm_actions/
│   └── crm_actions.module (Global nav injector)
└── crm_contact360/
    ├── crm_contact360.module
    └── templates/contact-360-view.html.twig
```

### Database Schema

**New Fields**:

- `field_source` (taxonomy_term reference)
- `field_customer_type` (taxonomy_term reference)
- `field_industry` (taxonomy_term reference)
- `field_probability` (integer 0-100)
- `field_contract` (file, private://contracts/)

**Activity Content Type**:

- `field_contact` (entity_reference → Contact)
- `field_deal` (entity_reference → Deal)
- `field_type` (list: Call, Meeting, Email, Note)
- `field_description` (text_long, stores outcome + notes)
- `field_datetime` (datetime, for meetings)

---

## 🚀 Performance Notes

- All custom routes cache-enabled
- Entity queries use accessCheck(TRUE) for security
- Lucide icons loaded via CDN (consider local copy for production)
- Images/Avatars not implemented (use Gravatar or upload module)
- File uploads go to `private://contracts` for security

---

## 📝 Changelog

### Version 1.0 (2026-02-27)

- ✅ Created 3 taxonomies with terms
- ✅ Added 5 new fields to content types
- ✅ Built Quick Add modal system
- ✅ Implemented Activity Logging
- ✅ Added Deal Closing Logic with modal
- ✅ Created Global Navigation Bar
- ✅ Built Contact 360 View

---

**Tất cả tính năng đều hoạt động và sẵn sàng test!** 🎉
