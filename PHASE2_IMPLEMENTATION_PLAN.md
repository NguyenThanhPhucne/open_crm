# 📋 PHASE 2: SYNC LAG FIXES & UI/UX IMPROVEMENTS - PLAN CHI TIẾT

## 🎯 Mục Tiêu PHASE 2

**Primary Goal:** Loại bỏ độ trễ giữa việc lưu dữ liệu và hiển thị cập nhật (sync lag)

**Secondary Goals:**

- Cải thiện trải nghiệm người dùng (UI/UX)
- Tối ưu hóa hiệu suất database
- Triển khai UI tối ưu (optimistic updates)
- Giảm thời gian loading

---

## 📊 BASELINE ANALYSIS

### Current Infrastructure

```
📦 crm_edit module: 180KB
📦 10 Views configured
📦 5 JavaScript files
📦 79 node field tables
📦 Database: MariaDB with full-text search
```

### Identified Performance Issues

```
❌ Full page refresh on update
❌ No optimistic UI updates
❌ No caching layer for queries
❌ Full wrapper replacement after delete
❌ Loading delays on modal operations
❌ No incremental updates (always full refresh)
```

---

## 🔧 PHASE 2 IMPLEMENTATION COMPONENTS

### Component 1: OPTIMISTIC UI UPDATES

**File:** `web/modules/custom/crm/js/crm-optimistic-ui.js` (NEW)

**Mục Đích:** Hiển thị thay đổi ngay lập tức, sync trong background

```javascript
Features:
✅ Instant form submission feedback
✅ Auto-disable form while saving
✅ Show toast "Saving..." immediately
✅ Update form fields optimistically
✅ Rollback on error
✅ Show actual toast when confirmed
```

**Workflows:**

```
User edits contact name:
1. [INSTANT] Form shows new value
2. [INSTANT] Toast "Saving..." appears
3. [BACKGROUND] AJAX POST to save
4. [SUCCESS] Toast "Contact updated"
5. [IF ERROR] Revert to previous value + Error toast
```

---

### Component 2: SMART QUERY CACHING

**File:** `web/modules/custom/crm_edit/src/Service/QueryCacheService.php` (NEW)

**Mục Đích:** Cache queries với smart invalidation

```php
Features:
✅ Cache contact list queries (5 min TTL)
✅ Cache field options (7 days - mostly static)
✅ Smart invalidation on save
✅ Per-user caching (privacy)
✅ Redis backend support
```

**Cache Keys:**

```
crm:contact:list:{view_id}:{filters_hash}
crm:options:{field_name}:{bundle}
crm:contact:{nid}  ← Invalidate on save
```

---

### Component 3: LAZY LOADING FOR LISTS

**File:** `web/modules/custom/crm/js/crm-lazy-load.js` (NEW)

**Mục Đích:** Tải dữ liệu khi cần thay vì tất cả cùng lúc

```javascript
Features:
✅ Load first 20 rows initially
✅ Load more on scroll
✅ Skeleton loaders while fetching
✅ Virtual scrolling for 1000+ rows
✅ State persistence
```

**Performance Impact:**

```
BEFORE: Load 5000 rows = 2-3 seconds
AFTER:  Load 20 rows = 200ms, load more on scroll
```

---

### Component 4: DATABASE QUERY OPTIMIZATION

**File:** `web/modules/custom/crm_edit/src/Service/OptimizedQueryService.php` (NEW)

**Mục Đích:** Giảm số lượng & complexity của queries

```php
Features:
✅ Single query instead of N+1
✅ Only load needed fields
✅ Batch loading (fields)
✅ Efficient sorting/filtering
✅ Index optimization
```

**Example Optimization:**

```php
BEFORE (N+1 problem):
SELECT * FROM nodes WHERE type='contact'  // 100 queries
  for each: SELECT * FROM field_email WHERE nid=$n
  for each: SELECT * FROM field_phone WHERE nid=$n

AFTER (Efficient):
SELECT n.nid, fe.field_email_value, fp.field_phone_value
FROM node n
LEFT JOIN node__field_email fe ON n.nid = fe.entity_id
LEFT JOIN node__field_phone fp ON n.nid = fp.entity_id
WHERE n.type='contact'
```

---

### Component 5: INLINE EDITING (No Modal)

**File:** `web/modules/custom/crm/js/crm-inline-edit.js` (NEW)

**Mục Đích:** Edit fields directly in lists, không cần modal

```javascript
Features:
✅ Click field name → inline edit
✅ Enter to save, Escape to cancel
✅ Auto-save after 1s idle
✅ Show validation errors inline
✅ Multi-cell editing
✅ Undo/redo support
```

**Example Workflow:**

```
BEFORE:
1. Click Edit → Modal opens
2. Fill form → Save
3. Modal closes → List refreshes (2-3s)
4. See updated value

AFTER:
1. Click field → Becomes editable
2. Type → Auto-saves (200ms)
3. See updated immediately ✓
```

---

### Component 6: WebSocket REAL-TIME SYNC (Optional)

**File:** `web/modules/custom/crm/js/crm-websocket.js` (FUTURE)

**Mục Đích:** Real-time sync khi người khác edit

```javascript
Features:
✅ WebSocket connection to sync server
✅ Other users' edits appear live
✅ Conflict detection & resolution
✅ Offline queue & retry
```

---

## 🗄️ DATABASE OPTIMIZATIONS

### Index Optimization

**New Indexes to Create:**

```sql
-- Speed up contact list queries
ALTER TABLE node ADD INDEX idx_type_created (type, created);
ALTER TABLE node ADD INDEX idx_type_status (type, field_status);
ALTER TABLE node__field_deleted_at ADD INDEX idx_value (field_deleted_at_value);

-- Speed up email lookups
ALTER TABLE node__field_email ADD INDEX idx_value (field_email_value);
ALTER TABLE node__field_phone ADD INDEX idx_value (field_phone_value);

-- Speed up organization queries
ALTER TABLE node__field_organization ADD INDEX idx_target (field_organization_target_id);
```

### Query Optimization

**View Updates:**

```yaml
# config/views.view.all_contacts.yml

✅ Only load needed fields (don't load all 30+ fields)
✅ Add pager (50 per page, not 200)
✅ Pre-load relationships (organization, owner)
✅ Cache results (5 minutes)
✅ Use fast filtering (indexed fields only)
```

---

## 📝 IMPLEMENTATION TASKS

### Phase 2A: Core Optimizations (Week 1)

```
Task 2A1: Create QueryCacheService
  └─ Implement caching layer for common queries
  └─ Smart invalidation on entity save
  └─ Test with contact list views

Task 2A2: Database Index Optimization
  └─ Create install hook to add indexes
  └─ Update Views to use indexed fields
  └─ Verify performance improvement

Task 2A3: Optimistic UI Updates (JavaScript)
  └─ Create crm-optimistic-ui.js
  └─ Implement optimistic form handling
  └─ Add toast notifications
  └─ Test with contact edit
```

### Phase 2B: Advanced Features (Week 2)

```
Task 2B1: Inline Editing
  └─ Create crm-inline-edit.js
  └─ Implement click-to-edit behavior
  └─ Add auto-save functionality
  └─ Test with list views

Task 2B2: Lazy Loading
  └─ Create crm-lazy-load.js
  └─ Implement infinite scroll
  └─ Add skeleton loaders
  └─ Test with large datasets

Task 2B3: View Configuration
  └─ Update all views for optimization
  └─ Add caching to views
  └─ Reduce fields loaded
  └─ Performance benchmarking
```

---

## 📈 EXPECTED PERFORMANCE IMPROVEMENTS

### Baseline Metrics (PHASE 1)

```
Contact List Load: ~1500ms
Edit Modal Open: ~800ms
Save to Display: ~2000ms (full refresh)
```

### Target Metrics (PHASE 2)

```
Contact List Load: ~300ms (80% faster)
Edit Modal Open: ~200ms (75% faster)
Save to Display: ~200ms (90% faster, optimistic)
Inline Edit Save: ~100ms (instant + background)
```

---

## 🔧 FOLDER STRUCTURE

```
web/modules/custom/crm_edit/src/Service/
├── QueryCacheService.php              ← Cache management
└── OptimizedQueryService.php          ← Query optimization

web/modules/custom/crm/js/
├── crm-optimistic-ui.js               ← Instant feedback
├── crm-inline-edit.js                 ← Click-to-edit
├── crm-lazy-load.js                   ← Lazy loading
└── crm-websocket.js                   ← Real-time (future)

config/
├── crm_edit.install                   ← Database indexes
└── views.view.*.yml                   ← Updated with cache
```

---

## 📋 SPECIFIC IMPROVEMENTS PER MODULE

### For Contact List View

```
BEFORE:
1. Load all 200 contacts
2. Load all fields (30+ per contact)
3. Render HTML
4. Attach JS behaviors
= 2-3 seconds

AFTER:
1. Load 20 contacts (with needed fields only)
2. Render HTML
3. Attach JS behaviors
4. On scroll → load more 20 (async)
= 300-400ms initially
```

### For Contact Edit

```
BEFORE:
1. Open modal (full form load)
2. Save via AJAX
3. Full page refresh
4. See updated value
= 2-3 seconds total

AFTER:
1. Open modal instantly (already in DOM)
2. User types → optimistic update (instant)
3. Save via AJAX (background)
4. See updated immediately
= 100ms perceived
```

---

## 🎬 ROADMAP

```
PHASE 1 ✅ COMPLETE
  └─ Database + Data Sync
  └─ Soft-delete, Email/Phone validation
  └─ Auto-filtering

PHASE 2 🚀 STARTING NOW
  └─ Week 1: Core optimizations
     └─ Caching, Indexes, Optimistic UI
  └─ Week 2: Advanced features
     └─ Inline editing, Lazy loading

PHASE 3 (Future)
  └─ Dashboard & reporting
  └─ Bulk operations
  └─ Real-time sync (WebSocket)
```

---

## ✅ SUCCESS CRITERIA

```
✅ Sync lag reduced from 2-3s to <200ms
✅ Contact list loads in <400ms
✅ Inline editing works in lists (no modal)
✅ Auto-save on background without blocking UI
✅ 50% reduction in database queries
✅ All tests pass
✅ No visual glitches or regressions
✅ Works offline temporarily (queue updates)
✅ Performance benchmarks documented
```

---

## 🛠️ FIRST TASK: Query Caching Service

**Priority:** HIGH - This gives immediate performance boost

**Implementation Steps:**

1. Create `QueryCacheService.php` with cache management
2. Update contact list views to use cache
3. Add smart invalidation on entity save
4. Test with 1000+ contacts
5. Benchmark performance

**Expected Results:**

- Contact list: 2000ms → 300ms (85% faster)
- Repeated queries: cached (0ms after first query)
- Auto-invalidate on edit (users see latest data)

---

## 📞 Next Steps

> > > Sẵn sàng triển khai PHASE 2?

1. ✅ Bạn muốn tôi bắt đầu với Component 1 (Optimistic UI)?
2. ✅ Hay bắt đầu với Database Optimization?
3. ✅ Hay muốn triển khai toàn bộ PHASE 2?

Tôi sẽ thực hiện từng task một, kiểm tra kỹ lưỡng, và cập nhật bạn từng bước.

---

**Status:** PHASE 2 Plan Created ✅  
**Ready to Start:** YES 🚀
