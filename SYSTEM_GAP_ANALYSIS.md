# GAP ANALYSIS: Hiện Tại vs Yêu Cầu Master Plan

Date: 2026-02-27  
Dự án: Open CRM - Drupal 11

---

## 📊 TỔNG QUAN

### ✅ Đã Hoàn Thành (Foundation Ready)

| Component        | Status  | Notes                                                                   |
| ---------------- | ------- | ----------------------------------------------------------------------- |
| Content Types    | ✅ 100% | Contact, Organization, Deal, Activity                                   |
| Taxonomies       | ✅ 100% | Pipeline Stage, Lead Source, Activity Type                              |
| User Roles       | ✅ 100% | sales_manager, sales_rep (cần rename?), customer                        |
| Views (Basic)    | ✅ 100% | my_contacts, my_deals, my_activities, my_organizations, sales_dashboard |
| Owner Fields     | ✅ 100% | field_owner, field_assigned_to, field_assigned_staff                    |
| Field Naming Fix | ✅ 100% | Đã fix tất cả lỗi field names trong views                               |
| Lucide Icons     | ✅ 100% | Quick Access homepage với professional icons                            |

---

## ❌ GAP ANALYSIS: Thiếu So Với Yêu Cầu

### 1. ENTITY FIELDS - Thiếu Trường

#### Contact Entity

| Field Yêu Cầu    | Trạng Thái | Field Hiện Tại | Action                             |
| ---------------- | ---------- | -------------- | ---------------------------------- |
| `field_position` | ❌ Thiếu   | N/A            | **CẦN TẠO** - Chức vụ (Text Plain) |

#### Organization Entity

| Field Yêu Cầu  | Trạng Thái | Field Hiện Tại | Action                                           |
| -------------- | ---------- | -------------- | ------------------------------------------------ |
| `field_logo`   | ❌ Thiếu   | N/A            | **CẦN TẠO** - Logo công ty (Image)               |
| `field_status` | ❌ Thiếu   | N/A            | **CẦN TẠO** - Active/Inactive (List or Taxonomy) |

#### Deal Entity

| Field Yêu Cầu                | Trạng Thái | Field Hiện Tại       | Action                         |
| ---------------------------- | ---------- | -------------------- | ------------------------------ |
| `field_related_contact`      | ❌ Thiếu   | `field_contact`      | **CẦN RENAME** hoặc giữ nguyên |
| `field_related_organization` | ❌ Thiếu   | `field_organization` | **CẦN RENAME** hoặc giữ nguyên |

**💡 Gợi ý**: Giữ nguyên `field_contact` và `field_organization` cho đơn giản (không cần "related\_"). Logic vẫn đúng.

#### Activity Entity

| Field Yêu Cầu         | Trạng Thái | Field Hiện Tại   | Action         |
| --------------------- | ---------- | ---------------- | -------------- |
| `field_activity_type` | ⚠️ Sai tên | `field_type`     | **CẦN RENAME** |
| `field_deal_ref`      | ⚠️ Sai tên | `field_deal`     | **CẦN RENAME** |
| `field_due_date`      | ⚠️ Sai tên | `field_datetime` | **CẦN RENAME** |

---

### 2. URL STRUCTURE - Không Khớp

| Yêu Cầu (Master Plan) | Hiện Tại                | Gap          | Priority                              |
| --------------------- | ----------------------- | ------------ | ------------------------------------- |
| `/app/contacts`       | `/crm/my-contacts`      | ❌ Path khác | **HIGH**                              |
| `/app/pipeline`       | `/crm/my-pipeline`      | ❌ Path khác | **HIGH**                              |
| `/app/dashboard`      | `/crm/dashboard`        | ❌ Path khác | MEDIUM                                |
| `/app/contacts/add`   | Chưa có                 | ❌ Thiếu     | LOW (có thể dùng `/node/add/contact`) |
| `/app/activities`     | `/crm/my-activities`    | ❌ Path khác | MEDIUM                                |
| `/app/organizations`  | `/crm/my-organizations` | ❌ Path khác | LOW                                   |

**💡 Quyết định**:

- Option A: Giữ `/crm/*` (Drupal convention, rõ ràng là CRM module)
- Option B: Đổi thành `/app/*` (Theo Master Plan, giống SaaS hiện đại)

---

### 3. FEATURES - Chưa Triển Khai

#### Epic 1: Quản Lý Khách Hàng (STT 1-2)

| Feature                                    | Status   | Technical Solution                                  |
| ------------------------------------------ | -------- | --------------------------------------------------- |
| Import Excel/CSV                           | ❌ Thiếu | Module: **Feeds** hoặc **Migrate Tools**            |
| Quick Add Modal                            | ❌ Thiếu | Module: **Gin LB** + Custom Modal Block             |
| Card View với Avatar                       | ❌ Thiếu | Views + Custom Twig Template                        |
| Click-to-Call                              | ❌ Thiếu | Custom JS (tel: link)                               |
| Inline Entity Form (tạo Org trong Contact) | ❌ Thiếu | Module: **Inline Entity Form**                      |
| Validate trùng Email/SĐT                   | ❌ Thiếu | Module: **Unique Field**, **Clientside Validation** |

#### Epic 2: Pipeline (STT 3-4)

| Feature                     | Status   | Technical Solution                                     |
| --------------------------- | -------- | ------------------------------------------------------ |
| Kanban Drag-Drop            | ❌ Thiếu | Module: **Content Planner** hoặc **Views Kanban** + JS |
| Auto-save khi drop          | ❌ Thiếu | AJAX Hook + Custom JS                                  |
| Tính tổng Amount theo cột   | ❌ Thiếu | Views Aggregation hoặc Custom JS                       |
| Popup "Won" với Form        | ❌ Thiếu | Module: **ECA** (Event-Condition-Action)               |
| Trigger Email khi chốt deal | ❌ Thiếu | ECA + **Rules** hoặc Custom Hook                       |

#### Epic 3: Activity (STT 5)

| Feature                           | Status                      | Technical Solution             |
| --------------------------------- | --------------------------- | ------------------------------ |
| Log Call nhanh tại trang Contact  | ❌ Thiếu                    | Gin Sidebar Block + Quick Form |
| Auto-update "Last Contacted Date" | ❌ Thiếu                    | ECA hoặc hook_entity_insert    |
| Upload file đính kèm              | ⚠️ Có field nhưng chưa test | Field: File (multiple)         |

#### Epic 4: Dashboard (STT 6)

| Feature               | Status   | Technical Solution                            |
| --------------------- | -------- | --------------------------------------------- |
| Charts (Bar/Pie)      | ❌ Thiếu | Module: **Charts** + **Highcharts** library   |
| KPI Cards             | ❌ Thiếu | Views Block + Custom CSS (giống Quick Access) |
| "Lịch hẹn hôm nay"    | ❌ Thiếu | Views Filter: Date = Today                    |
| "Deals nóng cần chốt" | ❌ Thiếu | Views Sort: Closing Date ASC                  |

#### Epic 5: Phân Quyền (STT 7)

| Feature                       | Status                      | Technical Solution     |
| ----------------------------- | --------------------------- | ---------------------- |
| Team/Group Management         | ❌ Thiếu                    | Module: **Group**      |
| Sales Team A không xem Team B | ❌ Thiếu                    | Group + Access Control |
| Manager xem tất cả            | ⚠️ Có logic nhưng chưa test | Permission: "view any" |

---

### 4. MODULES - Thiếu Các Module Cần Thiết

| Module                                    | Chức Năng                           | Status       | Priority |
| ----------------------------------------- | ----------------------------------- | ------------ | -------- |
| **Inline Entity Form**                    | Tạo Organization trong Contact form | ❌ Chưa cài  | **HIGH** |
| **Content Planner** hoặc **Views Kanban** | Kanban Board                        | ❌ Chưa cài  | **HIGH** |
| **ECA** (Event-Condition-Action)          | Logic phức tạp không cần code       | ❌ Chưa cài  | **HIGH** |
| **Charts**                                | Biểu đồ Dashboard                   | ❌ Chưa cài  | MEDIUM   |
| **Group**                                 | Quản lý Team/Phân quyền nhóm        | ❌ Chưa cài  | MEDIUM   |
| **Feeds** hoặc **Migrate Tools**          | Import CSV                          | ❌ Chưa cài  | MEDIUM   |
| **Unique Field**                          | Validate trùng Email/SĐT            | ❌ Chưa cài  | LOW      |
| **Clientside Validation**                 | Validate form không reload          | ❌ Chưa cài  | LOW      |
| **Address**                               | Địa chỉ chuẩn hóa                   | ⚠️ Cần check | LOW      |
| **Date Range** / **Smart Date**           | Quản lý lịch hẹn                    | ⚠️ Cần check | LOW      |

---

## 🎯 ĐỀ XUẤT ROADMAP

### Phase 1: Fix Foundation (1-2 ngày)

**Mục tiêu**: Căn chỉnh hệ thống hiện tại cho khớp 100% với Master Plan

- [ ] **1.1 Rename Activity Fields**
  - `field_type` → `field_activity_type`
  - `field_deal` → `field_deal_ref`
  - `field_datetime` → `field_due_date`
- [ ] **1.2 Add Missing Fields**
  - Contact: `field_position` (Text)
  - Organization: `field_logo` (Image), `field_status` (List: Active/Inactive)

- [ ] **1.3 Fix URL Structure**
  - Quyết định: Giữ `/crm/*` hay đổi `/app/*`
  - Update Views paths
  - Update Menu links

- [ ] **1.4 Verify Role Names**
  - Quyết định: Giữ `sales_rep` hay đổi `sales_representative`

### Phase 2: Core Features (3-5 ngày)

**Mục tiêu**: Triển khai 4 features chính

- [ ] **2.1 Kanban Pipeline**
  - Cài Content Planner / Views Kanban
  - Setup drag-drop
  - Tính tổng Amount

- [ ] **2.2 Quick Add Contact**
  - Cài Inline Entity Form
  - Tạo Modal form
  - Validate trùng

- [ ] **2.3 Import CSV**
  - Cài Feeds
  - Config import mapping
  - Template CSV mẫu

- [ ] **2.4 Log Call Nhanh**
  - Sidebar block trong Contact detail
  - Auto-update timestamp

### Phase 3: Advanced Features (5-7 ngày)

**Mục tiêu**: ECA Logic + Dashboard + Group Permission

- [ ] **3.1 Closing Logic (ECA)**
  - Setup ECA module
  - Popup form khi Won
  - Trigger email

- [ ] **3.2 Dashboard with Charts**
  - Cài Charts + Highcharts
  - KPI cards
  - Bar chart doanh số

- [ ] **3.3 Team Permission**
  - Cài Group module
  - Setup teams
  - Test access control

### Phase 4: Polish & Testing (2-3 ngày)

**Mục tiêu**: Hoàn thiện UX/UI và kiểm tra tổng thể

- [ ] **4.1 Upload Files**
  - Test file attachment trong Activity
  - Limit file types

- [ ] **4.2 Customer Portal**
  - Setup Customer role views
  - `/my/projects`, `/my/requests`

- [ ] **4.3 E2E Testing**
  - Test toàn bộ workflow
  - Fix bugs

---

## ❓ CÂU HỎI CẦN QUYẾT ĐỊNH

### Q1: URL Structure

Bạn muốn giữ `/crm/*` (hiện tại) hay đổi thành `/app/*` (theo Master Plan)?

**Ý kiến**: Nên giữ `/crm/*` vì:

- Rõ ràng đây là module CRM
- Tách biệt với phần `/app/*` dành cho Customer portal
- Ít công sửa

### Q2: Field Names

Có rename các field Activity không?

- `field_type` → `field_activity_type`
- `field_deal` → `field_deal_ref`
- `field_datetime` → `field_due_date`

**Ý kiến**: **KHÔNG** nên rename vì:

- Đã có data, rename sẽ mất data
- Logic vẫn đúng
- Tốn công migrate

Trừ khi bạn muốn chuẩn 100% theo doc (nhưng phải migrate data).

### Q3: Role Name

Giữ `sales_rep` hay đổi `sales_representative`?

**Ý kiến**: Giữ `sales_rep` - ngắn gọn, dễ gõ.

### Q4: Priority Features

Bạn muốn làm feature nào trước?

1. Kanban Pipeline (Ấn tượng nhất)
2. Import CSV (Tiện nhất)
3. Dashboard Charts (Đẹp nhất)
4. Team Permission (Quan trọng nhất)

---

## 📌 KẾT LUẬN

**Hiện tại đã có**: Foundation hoàn chỉnh (Content types, Fields, Views, Roles)

**Còn thiếu**: Advanced features (Kanban, Import, Charts, ECA, Group)

**Công việc còn lại**: ~15-20 ngày nếu làm full-time

**Gợi ý**: Ưu tiên Phase 1 (Fix Foundation) trước, sau đó làm từng feature theo nhu cầu thực tế.

---

**Câu hỏi cho bạn**: Bạn muốn tôi bắt đầu từ đâu? 😊

1. Fix field names + add missing fields (Phase 1)?
2. Triển khai Kanban Pipeline ngay (Phase 2)?
3. Làm Dashboard với Charts (Phase 2)?
4. Hay bạn có ưu tiên khác?
