# Open CRM - Complete Technical Architecture Documentation (Part 3)

_Continuation - Final Sections 11-17_

---

## 11. Views Configuration Deep Dive

### 11.1 Views Architecture Overview

All CRM listings use Drupal Views module. Views generates SQL queries from YAML configuration without writing code.

**Configuration location:** `config/views.view.*.yml`  
**Total CRM Views:** 13 configurations

#### View Types

1. **User-scoped views** (`my_*`): Filtered by current user
2. **Admin views** (`all_*`): Show all records, role-restricted
3. **Search API views**: Full-text search across CRM entities

---

### 11.2 Common Views Patterns

#### Pattern 1: Ownership Contextual Filter

**All user views use this pattern:**

```yaml
arguments:
  field_owner_target_id:
    id: field_owner_target_id
    table: node__field_owner
    field: field_owner_target_id
    plugin_id: numeric
    default_action: default
    default_argument_type: current_user # ← Auto-fills with logged-in user ID
    summary:
      sort_order: asc
      number_of_records: 0
      format: default_summary
```

**Generated SQL WHERE clause:**

```sql
WHERE owner.field_owner_target_id = :current_user_uid
```

**Result:** Automatically filters to show only user's own records

---

#### Pattern 2: Exposed Filters

**Render search form above view results:**

```yaml
filters:
  title:
    id: title
    table: node_field_data
    field: title
    plugin_id: string
    operator: contains
    value: ""
    exposed: true # ← Makes it a search box
    expose:
      identifier: title
      label: "Search by name"
      placeholder: "Enter contact name..."
      required: false
```

**Renders as HTML:**

```html
<form method="get" class="views-exposed-form">
  <div class="form-item">
    <label for="edit-title">Search by name</label>
    <input
      type="text"
      id="edit-title"
      name="title"
      placeholder="Enter contact name..."
    />
  </div>
  <button type="submit" class="button">Apply</button>
  <button type="reset" class="button">Reset</button>
</form>
```

**User flow:**

1. User types "John" → submits form
2. URL becomes `/crm/my-contacts?title=John`
3. Views adds: `AND nfd.title LIKE '%John%'`

---

#### Pattern 3: Custom CrmEditLink Field

**All table views include action buttons:**

```yaml
fields:
  crm_edit_link:
    id: crm_edit_link
    table: node_field_data
    field: crm_edit_link
    plugin_id: crm_edit_link # ← Custom plugin from crm_edit module
    label: Actions
    exclude: false
```

**Plugin renders:**

```html
<div class="crm-actions">
  <button onclick="CRMInlineEdit.openModal(123, 'contact')" class="edit-btn">
    <i data-lucide="edit"></i> Edit
  </button>
  <button onclick="CRMInlineEdit.confirmDelete(123)" class="delete-btn">
    <i data-lucide="trash-2"></i> Delete
  </button>
</div>
```

**Plugin code reference:** `crm_edit/src/Plugin/views/field/CrmEditLink.php`

---

### 11.3 View-by-View Analysis

#### View: my_contacts

**File:** `config/views.view.my_contacts.yml` (274 lines)

**Purpose:** Contact list for current user

**Configuration:**

```yaml
id: my_contacts
label: "My Contacts"
base_table: node_field_data
base_field: nid

display:
  default:
    display_options:
      title: "My Contacts"

      # Displayed fields
      fields:
        title: # Contact name
          id: title
          table: node_field_data
          field: title
          plugin_id: field
          label: Name
          type: string
          settings:
            link_to_entity: true # Makes name clickable

        field_organization: # Organization reference
          id: field_organization
          table: node__field_organization
          field: field_organization
          plugin_id: field
          label: Organization
          type: entity_reference_label
          settings:
            link: true # Links to org detail page

        field_phone:
          id: field_phone
          table: node__field_phone
          field: field_phone
          plugin_id: field
          label: Phone
          type: string

        field_email:
          id: field_email
          table: node__field_email
          field: field_email
          plugin_id: field
          label: Email
          type: basic_string

        field_source: # Lead source taxonomy
          id: field_source
          table: node__field_source
          field: field_source
          plugin_id: field
          label: Source
          type: entity_reference_label

        field_customer_type: # Customer type taxonomy
          id: field_customer_type
          table: node__field_customer_type
          field: field_customer_type
          plugin_id: field
          label: Type
          type: entity_reference_label

        crm_edit_link: # Action buttons
          id: crm_edit_link
          plugin_id: crm_edit_link
          label: Actions

      # Pagination
      pager:
        type: full
        options:
          items_per_page: 25
          offset: 0

      # Sorting
      sorts:
        changed:
          id: changed
          table: node_field_data
          field: changed
          plugin_id: date
          order: DESC # Most recently updated first

      # Ownership filter (automatic - uses current user ID)
      arguments:
        field_owner_target_id:
          id: field_owner_target_id
          table: node__field_owner
          field: field_owner_target_id
          plugin_id: numeric
          default_action: default
          default_argument_type: current_user

      # Search filters (exposed to user)
      filters:
        status:
          id: status
          value: "1" # Published only
          plugin_id: boolean

        type:
          id: type
          value:
            contact: contact
          plugin_id: bundle

        title:
          id: title
          operator: contains
          exposed: true
          expose:
            identifier: title
            label: "Search by name"

        field_email_value:
          id: field_email_value
          operator: contains
          exposed: true
          expose:
            identifier: email
            label: "Search by email"

        field_phone_value:
          id: field_phone_value
          operator: contains
          exposed: true
          expose:
            identifier: phone
            label: "Search by phone"

      # Display format
      style:
        type: table
        options:
          columns:
            title: title
            field_organization: field_organization
            field_phone: field_phone
            field_email: field_email
            field_source: field_source
            field_customer_type: field_customer_type
            crm_edit_link: crm_edit_link

  page_1:
    id: page_1
    display_plugin: page
    path: crm/my-contacts

    # Header area with "Add Contact" button
    header:
      area:
        id: area
        plugin_id: text
        content:
          value: |
            <div style="text-align: right; margin-bottom: 20px;">
              <a href="/node/add/contact" class="button button--primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M12 5v14M5 12h14"></path>
                </svg>
                Add Contact
              </a>
            </div>
          format: full_html
```

**Generated SQL Query:**

```sql
SELECT
  nfd.nid AS nid,
  nfd.changed AS changed,
  nfd.title AS title,
  email.field_email_value AS email,
  phone.field_phone_value AS phone,
  org_ref.field_organization_target_id AS org_id,
  source_ref.field_source_target_id AS source_tid,
  type_ref.field_customer_type_target_id AS customer_type_tid
FROM node_field_data nfd
LEFT JOIN node__field_email email
  ON nfd.nid = email.entity_id AND email.deleted = 0
LEFT JOIN node__field_phone phone
  ON nfd.nid = phone.entity_id AND phone.deleted = 0
LEFT JOIN node__field_owner owner
  ON nfd.nid = owner.entity_id AND owner.deleted = 0
LEFT JOIN node__field_organization org_ref
  ON nfd.nid = org_ref.entity_id AND org_ref.deleted = 0
LEFT JOIN node__field_source source_ref
  ON nfd.nid = source_ref.entity_id AND source_ref.deleted = 0
LEFT JOIN node__field_customer_type type_ref
  ON nfd.nid = type_ref.entity_id AND type_ref.deleted = 0
WHERE
  nfd.type = 'contact'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5  -- Current user ID
  -- Optional filters (if search form submitted):
  AND (nfd.title LIKE '%search_term%')
  AND (email.field_email_value LIKE '%search_email%')
  AND (phone.field_phone_value LIKE '%search_phone%')
ORDER BY nfd.changed DESC
LIMIT 25 OFFSET 0;

-- Additional queries to load referenced entities:
-- For each contact, load organization entity
SELECT * FROM node_field_data WHERE nid IN (45, 67, 89...);
-- For each contact, load taxonomy terms
SELECT * FROM taxonomy_term_field_data WHERE tid IN (12, 23, 34...);
```

**Performance note:** Views uses entity loading which triggers additional queries. Typically 1 main query + 2-3 sub-queries per view render.

---

#### View: my_activities

**File:** `config/views.view.my_activities.yml` (300 lines)

**Purpose:** Activities assigned to current user

**Key differences from my_contacts:**

```yaml
# Contextual filter on different field
arguments:
  field_assigned_to_target_id: # ← Different ownership field
    default_argument_type: current_user

# Includes both contact and deal references
fields:
  field_contact: # Related contact
    type: entity_reference_label
    settings:
      link: true

  field_deal: # Related deal
    type: entity_reference_label
    settings:
      link: true

  field_type: # Activity type taxonomy (Call, Meeting, Email, etc.)
    type: entity_reference_label

  field_datetime: # Due date/time
    type: datetime_default
    settings:
      format_type: short # "03/06/2026 - 14:30"

# Sorted by due date
sorts:
  field_datetime_value:
    order: DESC # Newest/most urgent first
```

**Rendered table:**

| Activity       | Contact    | Deal      | Type    | Date          | Actions        |
| -------------- | ---------- | --------- | ------- | ------------- | -------------- |
| Follow-up call | John Doe   | Project X | Call    | Mar 06, 14:00 | Edit \| Delete |
| Send proposal  | Jane Smith | Deal Y    | Email   | Mar 05, 10:00 | Edit \| Delete |
| Product demo   | -          | Deal Z    | Meeting | Mar 04, 15:30 | Edit \| Delete |

**Generated SQL:**

```sql
SELECT
  nfd.nid,
  nfd.title,
  contact_ref.field_contact_target_id AS contact_id,
  deal_ref.field_deal_target_id AS deal_id,
  type_ref.field_type_target_id AS type_tid,
  datetime.field_datetime_value AS due_date
FROM node_field_data nfd
LEFT JOIN node__field_assigned_to assigned
  ON nfd.nid = assigned.entity_id AND assigned.deleted = 0
LEFT JOIN node__field_contact contact_ref
  ON nfd.nid = contact_ref.entity_id AND contact_ref.deleted = 0
LEFT JOIN node__field_deal deal_ref
  ON nfd.nid = deal_ref.entity_id AND deal_ref.deleted = 0
LEFT JOIN node__field_type type_ref
  ON nfd.nid = type_ref.entity_id AND type_ref.deleted = 0
LEFT JOIN node__field_datetime datetime
  ON nfd.nid = datetime.entity_id AND datetime.deleted = 0
WHERE
  nfd.type = 'activity'
  AND nfd.status = 1
  AND assigned.field_assigned_to_target_id = 5  -- Current user
ORDER BY datetime.field_datetime_value DESC
LIMIT 20;
```

---

#### View: my_deals

**File:** `config/views.view.my_deals.yml`

**Purpose:** Deals owned by current user

**Unique feature: Grouping by stage**

```yaml
style:
  type: table
  options:
    grouping:
      - field: field_stage
        rendered: true
        rendered_strip: false
```

**Result:** Deals are grouped under stage headers:

```
═════════════════════════════════════════
Lead (3 deals)
═════════════════════════════════════════
Deal A     $20,000    25%    Mar 15, 2026
Deal B     $15,000    10%    Mar 20, 2026
Deal C     $10,000    15%    Mar 25, 2026

═════════════════════════════════════════
Qualified (2 deals)
═════════════════════════════════════════
Deal D     $50,000    40%    Apr 01, 2026
Deal E     $30,000    35%    Apr 10, 2026

═════════════════════════════════════════
Proposal Sent (1 deal)
═════════════════════════════════════════
Deal F     $100,000   60%    Apr 15, 2026
```

**Fields with custom formatting:**

```yaml
field_amount:
  id: field_amount
  plugin_id: field
  type: number_decimal
  settings:
    thousand_separator: ","
    prefix_suffix: true
  # Custom rewrite to add currency
  alter:
    alter_text: true
    text: "{{ field_amount }} VND"

field_probability:
  id: field_probability
  plugin_id: field
  type: number_integer
  alter:
    alter_text: true
    text: "{{ field_probability }}%" # Adds percent sign
```

**Sort order:**

```yaml
sorts:
  field_closing_date_value:
    order: ASC # Earliest close date first (most urgent)
```

---

#### View: all_organizations

**File:** `config/views.view.all_organizations.yml`

**Purpose:** Admin view showing ALL organizations

**Key difference: Role-based access (no user filtering)**

```yaml
access:
  type: role
  options:
    role:
      administrator: administrator
      sales_manager: sales_manager
# Regular users cannot access this view
```

**No contextual filter:**

```yaml
# NO field_owner_target_id or field_assigned_staff_target_id argument
# Shows ALL organizations regardless of who created them
```

**Exposed filters:**

```yaml
filters:
  title:
    operator: contains
    exposed: true
    expose:
      identifier: title
      label: "Search organization"
      placeholder: "Enter organization name..."

  field_industry_target_id:
    plugin_id: taxonomy_index_tid
    exposed: true
    expose:
      identifier: industry
      label: "Filter by industry"
      multiple: false
      as_select: true # Renders as <select> dropdown
    # Loads options from 'Industry' vocabulary
```

**Exposed sorts:**

```yaml
exposed_sorts:
  created_DESC:
    label: "Newest first"
    order: DESC
    field: created

  created_ASC:
    label: "Oldest first"
    order: ASC
    field: created

  title_ASC:
    label: "Name (A-Z)"
    order: ASC
    field: title

  title_DESC:
    label: "Name (Z-A)"
    order: DESC
    field: title
```

**Renders as:**

```html
<form class="views-exposed-form">
  <input type="text" name="title" placeholder="Enter organization name..." />

  <select name="industry">
    <option value="">- Any Industry -</option>
    <option value="1">Technology</option>
    <option value="2">Healthcare</option>
    <option value="3">Manufacturing</option>
    ...
  </select>

  <select name="sort_by">
    <option value="created_DESC">Newest first</option>
    <option value="created_ASC">Oldest first</option>
    <option value="title_ASC">Name (A-Z)</option>
    <option value="title_DESC">Name (Z-A)</option>
  </select>

  <button type="submit">Apply</button>
</form>
```

---

### 11.4 Views Performance Optimization

#### Technique 1: Views Caching

```yaml
cache:
  type: tag
  options: {}
```

**How it works:**

- Views caches rendered output
- Cache automatically invalidated when:
  - Related nodes are created/updated/deleted
  - Taxonomy terms change
  - Users change
- Cache keys include: user ID, URL parameters, language

**Example cache tags:**

```
node_list
node:123
node:124
user:5
taxonomy_term:45
```

**When node 123 is updated:**

- Drupal invalidates all cache entries with `node:123` tag
- Views automatically re-renders on next page load

---

#### Technique 2: Database Indexes

**Created via:** `scripts/create_database_indexes.sh`

```sql
-- Ownership field indexes (critical for user-scoped views)
CREATE INDEX idx_node_field_owner
  ON node__field_owner(field_owner_target_id);

CREATE INDEX idx_node_field_assigned_to
  ON node__field_assigned_to(field_assigned_to_target_id);

CREATE INDEX idx_node_field_assigned_staff
  ON node__field_assigned_staff(field_assigned_staff_target_id);

-- Type + status composite index (common WHERE clause)
CREATE INDEX idx_node_type_status
  ON node_field_data(type, status);

-- Pipeline stage index (for kanban and grouped views)
CREATE INDEX idx_node_field_stage
  ON node__field_stage(field_stage_target_id);

-- Date indexes (for sorting by created/changed/close date)
CREATE INDEX idx_node_created
  ON node_field_data(created);

CREATE INDEX idx_node_changed
  ON node_field_data(changed);

CREATE INDEX idx_node_field_closing_date
  ON node__field_closing_date(field_closing_date_value);
```

**Impact:**

- **Without indexes:** 500ms query time for 10K contacts
- **With indexes:** 50ms query time (10x faster)

---

#### Technique 3: Pagination

**All views use pagination:**

```yaml
pager:
  type: full # "1 2 3 ... » Last" pagination
  options:
    items_per_page: 25
    offset: 0
    expose:
      items_per_page: true
      items_per_page_options: "25, 50, 100"
```

**Why important:**

- Loading 10,000 contacts at once: **Memory overflow**
- Loading 25 contacts per page: **Fast and responsive**

---

#### Technique 4: Lazy Loading for Expensive Operations

**Activity timeline widget uses lazy builder:**

```php
// In crm_activity_log.module
function crm_activity_log_node_view_alter(array &$build, NodeInterface $node) {
  if ($node->bundle() === 'contact') {
    $build['activity_timeline'] = [
      '#lazy_builder' => [
        'Drupal\crm_activity_log\Controller\ActivityLogController::getActivities',
        [$node->bundle(), $node->id()],
      ],
      '#create_placeholder' => TRUE,  // Render as placeholder initially
      '#weight' => 100,
    ];
  }
}
```

**How it works:**

1. Contact detail page renders quickly (without activities)
2. Placeholder shows: `<drupal-render-placeholder callback="..."></drupal-render-placeholder>`
3. After main page loads, AJAX request fetches activity timeline
4. Timeline injected into page via JavaScript

**Benefit:**

- Page loads fast (500ms → 200ms)
- User sees content immediately
- Heavy queries don't block initial render

---

### 11.5 Complete Views Inventory

| View ID             | Path                                                | Purpose                   | Access          | Contextual Filter    |
| ------------------- | --------------------------------------------------- | ------------------------- | --------------- | -------------------- |
| `my_contacts`       | `/crm/my-contacts`                                  | User's contacts           | Authenticated   | field_owner          |
| `my_deals`          | `/crm/my-deals`                                     | User's deals              | Authenticated   | field_owner          |
| `my_activities`     | `/crm/my-activities`                                | User's activities         | Authenticated   | field_assigned_to    |
| `my_organizations`  | `/crm/my-organizations`                             | User's organizations      | Authenticated   | field_assigned_staff |
| `my_projects`       | `/crm/my-projects`                                  | User's projects           | Authenticated   | field_owner          |
| `all_contacts`      | `/crm/all-contacts`                                 | All contacts (admin)      | Admin + Manager | None                 |
| `all_deals`         | `/crm/all-deals`                                    | All deals (admin)         | Admin + Manager | None                 |
| `all_activities`    | `/crm/all-activities`                               | All activities (admin)    | Admin + Manager | None                 |
| `all_organizations` | `/crm/all-organizations`                            | All organizations (admin) | Admin + Manager | None                 |
| `contacts`          | `/crm/contacts`                                     | Legacy contact view       | Deprecated      | -                    |
| Search API views    | `/search/contacts`, `/search/deals`, `/search/orgs` | Full-text search          | Authenticated   | -                    |

---

## 12. CSS Architecture & UI System

### 12.1 Design System Foundation

#### CSS Variables (Design Tokens)

**Defined in:** `web/modules/custom/crm/css/crm-layout.css`

```css
:root {
  /* Spacing Scale (8px base) */
  --crm-spacing-xs: 8px;
  --crm-spacing-sm: 12px;
  --crm-spacing-md: 16px;
  --crm-spacing-lg: 24px;
  --crm-spacing-xl: 32px;
  --crm-spacing-2xl: 48px;
  --crm-spacing-3xl: 64px;

  /* Border Radius */
  --crm-border-radius: 12px;
  --crm-border-radius-sm: 6px;
  --crm-border-radius-lg: 16px;

  /* Shadows */
  --crm-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
  --crm-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
  --crm-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
  --crm-shadow-xl: 0 20px 48px rgba(0, 0, 0, 0.15);

  /* Typography */
  --crm-font-sans:
    -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue",
    Arial, sans-serif;
  --crm-font-mono:
    "SF Mono", Monaco, "Cascadia Code", "Roboto Mono", Consolas, monospace;

  /* Transitions */
  --crm-transition: all 0.2s ease;
  --crm-transition-fast: all 0.15s ease;
  --crm-transition-slow: all 0.3s ease;
}
```

**Usage example:**

```css
.card {
  padding: var(--crm-spacing-lg); /* 24px */
  border-radius: var(--crm-border-radius); /* 12px */
  box-shadow: var(--crm-shadow-sm);
  transition: var(--crm-transition);
}

.card:hover {
  box-shadow: var(--crm-shadow-md);
  transform: translateY(-2px);
}
```

---

### 12.2 Color System

#### Primary Colors

```css
/* Blue (Primary action color) */
--blue-50: #eff6ff;
--blue-100: #dbeafe;
--blue-200: #bfdbfe;
--blue-300: #93c5fd;
--blue-400: #60a5fa;
--blue-500: #3b82f6; /* ← Main brand color */
--blue-600: #2563eb;
--blue-700: #1d4ed8;
--blue-800: #1e40af;
--blue-900: #1e3a8a;
```

**Usage:**

- Primary buttons: `--blue-500`
- Button hover: `--blue-600`
- Button active: `--blue-700`
- Light backgrounds: `--blue-50`

---

#### Semantic Colors

```css
/* Success (Green) */
--green-500: #10b981; /* Checkmarks, success messages, "Add" buttons */
--green-600: #059669; /* Hover state */
--green-50: #f0fdf4; /* Light background */

/* Danger (Red) */
--red-500: #ef4444; /* Delete buttons, errors */
--red-600: #dc2626; /* Hover state */
--red-50: #fef2f2; /* Light background */

/* Warning (Yellow) */
--yellow-500: #f59e0b; /* Warning messages, pending states */
--yellow-600: #d97706;
--yellow-50: #fffbeb;

/* Info (Purple) */
--purple-500: #8b5cf6; /* Meetings, special items */
--purple-600: #7c3aed;
--purple-50: #faf5ff;

/* Neutral (Gray) */
--gray-50: #f9fafb; /* Page backgrounds */
--gray-100: #f3f4f6; /* Card backgrounds */
--gray-200: #e5e7eb; /* Borders */
--gray-300: #d1d5db; /* Input borders */
--gray-400: #9ca3af; /* Placeholder text */
--gray-500: #64748b; /* Secondary text */
--gray-600: #475569; /* Body text */
--gray-700: #334155; /* Headings */
--gray-800: #1e293b; /* Dark headings */
--gray-900: #0f172a; /* Black */
```

---

#### Pipeline Stage Colors

**12-color palette for dynamic stage coloring:**

```javascript
// Used in KanbanController.php and DashboardController.php
const colors = [
  "#3b82f6", // Blue
  "#10b981", // Green
  "#f59e0b", // Yellow
  "#ef4444", // Red
  "#8b5cf6", // Purple
  "#ec4899", // Pink
  "#06b6d4", // Cyan
  "#84cc16", // Lime
  "#f97316", // Orange
  "#14b8a6", // Teal
  "#a855f7", // Violet
  "#22c55e", // Emerald
];

// Assign colors cyclically: stage 1 → color[0], stage 2 → color[1], ...
$color = $colors[$stage_index % 12];
```

**Result:** Each pipeline stage has unique color for visual differentiation

---

### 12.3 Layout System

#### Full-Width CRM Pages

**Problem:** Drupal themes default to narrow layouts (960px-1200px)  
**Solution:** Override with full-width CSS

**File:** `web/modules/custom/crm/css/crm-layout.css`

```css
/* Target CRM pages specifically */
body.path-crm .layout-container,
body.path-crm .region-content,
body.path-crm #main-content,
[data-crm-page] .layout-container {
  max-width: none !important; /* Remove width constraint */
  width: 100% !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
  margin: 0 !important;
}

/* Remove default Olivero theme grid constraints */
body.path-crm .grid-full {
  grid-template-columns: 1fr !important;
  max-width: none !important;
}

/* Remove default Claro admin theme container */
body.path-crm .block-system-main-block {
  max-width: none !important;
  width: 100% !important;
}

/* Ensure Views tables expand to full width */
body.path-crm .views-table {
  width: 100% !important;
  max-width: none !important;
}
```

**Result:** CRM pages use full browser width for data tables

---

#### Responsive Sidebar Layout

```css
.layout-main-wrapper {
  display: flex;
  width: 100%;
}

.layout-sidebar {
  flex: 0 0 auto;
  max-width: 280px;
  min-width: 280px;
}

.layout-main {
  flex: 1 1 auto;
  max-width: none;
}

/* Mobile: Stack vertically */
@media (max-width: 639px) {
  .layout-main-wrapper {
    flex-direction: column;
  }

  .layout-sidebar {
    max-width: 100%;
    width: 100%;
  }
}

/* Tablet: Reduced sidebar */
@media (min-width: 640px) and (max-width: 1023px) {
  .layout-sidebar {
    max-width: 200px;
    min-width: 200px;
  }
}

/* Desktop: Full sidebar */
@media (min-width: 1024px) {
  .layout-sidebar {
    max-width: 280px;
    min-width: 280px;
  }
}
```

---

### 12.4 Global Navigation Styles

**File:** `web/modules/custom/crm_actions/css/crm_actions.css` (1161 lines)

#### Navigation Bar

```css
.crm-global-nav {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  position: sticky; /* Stays at top when scrolling */
  top: 0;
  z-index: 1000;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.crm-nav-container {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  padding: 0 24px;
  height: 60px;
  gap: 32px;
}
```

**Visual structure:**

```
┌────────────────────────────────────────────────────┐
│ [OpenCRM Logo] Dashboard Contacts Deals Pipeline │
│                Activities Organizations      [+]   │
└────────────────────────────────────────────────────┘
```

---

#### Navigation Items

```css
.crm-nav-item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 8px;
  color: #64748b; /* Gray by default */
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.2s ease;
}

/* Hover state */
.crm-nav-item:hover {
  background: #f1f5f9; /* Light gray */
  color: #1e293b; /* Dark gray */
}

/* Active state (current page) */
.crm-nav-item.active {
  background: white;
  color: #3b82f6; /* Blue */
  border: 2px solid #3b82f6; /* Blue border */
}

.crm-nav-item.active:hover {
  background: #eff6ff; /* Very light blue */
  color: #2563eb; /* Darker blue */
  border-color: #2563eb;
}
```

**Visual states:**

| State   | Background           | Text Color          | Border   |
| ------- | -------------------- | ------------------- | -------- |
| Default | None                 | Gray (#64748b)      | None     |
| Hover   | Light gray (#f1f5f9) | Dark gray (#1e293b) | None     |
| Active  | White                | Blue (#3b82f6)      | 2px blue |

---

#### Quick Add Button

```css
.crm-quick-add-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: white;
  color: #10b981 !important; /* Green */
  border: 1.5px solid #10b981;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.crm-quick-add-btn:hover {
  background: #f0fdf4; /* Very light green */
  border-color: #10b981;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.crm-quick-add-btn:active {
  background: #dcfce7; /* Darker light green */
  color: #059669 !important;
  border-color: #059669;
}

.crm-quick-add-btn:focus {
  outline: 2px solid #10b981;
  outline-offset: 2px;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1); /* Green glow */
}
```

**Dropdown menu animation:**

```css
@keyframes slideDownFade {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.crm-quick-add-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
  padding: 8px;
  min-width: 220px;
  display: none;
  z-index: 1001;
  border: 1px solid #e2e8f0;
}

.crm-quick-add-menu.active {
  display: block;
  animation: slideDownFade 0.2s ease;
}
```

---

### 12.5 Button System

#### Primary Button (Blue Outline)

```css
.button--primary {
  background: white !important;
  color: #3b82f6 !important;
  border: 2px solid #3b82f6 !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  font-weight: 600 !important;
  padding: 10px 20px !important;
  border-radius: 8px !important;
  transition: all 0.2s ease !important;
  cursor: pointer !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
}

.button--primary:hover {
  background: #eff6ff !important; /* Very light blue */
  border-color: #2563eb !important; /* Darker blue */
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
}

.button--primary:active {
  background: #dbeafe !important; /* Light blue */
  transform: translateY(0); /* Remove lift */
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

.button--primary:disabled {
  background: #f3f4f6 !important;
  color: #9ca3af !important;
  border-color: #e5e7eb !important;
  cursor: not-allowed !important;
  transform: none !important;
}
```

**Visual behavior:**

1. **Default:** White background, blue text, blue border
2. **Hover:** Lifts 1px up, adds blue background tint
3. **Click:** Returns to ground, darker blue background
4. **Disabled:** Gray color, no interactions

---

#### Secondary Button (Gray Outline)

```css
.button--secondary {
  background: white !important;
  color: #64748b !important;
  border: 1.5px solid #e2e8f0 !important;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  font-weight: 500 !important;
  padding: 10px 20px !important;
  border-radius: 8px !important;
}

.button--secondary:hover {
  background: #f8fafc !important;
  border-color: #cbd5e1 !important;
}
```

---

#### Danger Button (Red for Delete)

```css
.button--danger {
  background: #fef2f2 !important; /* Very light red */
  color: #dc2626 !important; /* Red text */
  border: 1.5px solid #fecaca !important; /* Light red border */
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
  font-weight: 600 !important;
  padding: 10px 20px !important;
  border-radius: 8px !important;
}

.button--danger:hover {
  background: #fee2e2 !important; /* Darker light red */
  border-color: #fca5a5 !important;
}

.button--danger:active {
  background: #fecaca !important;
  border-color: #f87171 !important;
}
```

---

### 12.6 Modal Styles

#### Inline Edit Modal

**File:** `web/modules/custom/crm_edit/css/inline-edit.css`

```css
/* Overlay (full-screen backdrop) */
.crm-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6); /* 60% black */
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Modal content box */
.crm-modal-content {
  background: white;
  border-radius: 16px;
  box-shadow:
    0 24px 48px rgba(0, 0, 0, 0.15),
    0 12px 24px rgba(0, 0, 0, 0.1);
  max-width: 700px;
  width: 95%;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1); /* Custom easing */
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
```

**Animation sequence:**

1. Overlay fades in (0.2s)
2. Modal slides up from 30px below (0.3s)
3. Modal scales from 95% to 100%
4. Total animation: 0.3s

---

#### Quick Add Modal

**File:** `web/modules/custom/crm_quickadd/css/quickadd.css`

```css
.quickadd-form-container {
  background: white;
  border-radius: 12px;
  box-shadow:
    0 20px 25px -5px rgba(0, 0, 0, 0.1),
    0 10px 10px -5px rgba(0, 0, 0, 0.04);
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideUp 0.3s ease-out;
}

/* Form grid layout */
.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr); /* 2 equal columns */
  gap: 16px;
  margin-bottom: 16px;
}

.form-group.full-width {
  grid-column: 1 / -1; /* Span all columns */
}
```

**Rendered layout:**

```
┌───────────────────────┬───────────────────────┐
│ Name (required)       │ Phone (required)      │
├───────────────────────┴───────────────────────┤
│ Email (full-width)                             │
├────────────────────────────────────────────────┤
│ Organization (dropdown)                        │
├────────────────────────────────────────────────┤
│ Notes (textarea)                               │
└────────────────────────────────────────────────┘
```

---

#### Input Field Styles

```css
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db; /* Gray border */
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  transition: all 0.2s;
}

/* Focus state (when user clicks in field) */
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #3b82f6; /* Blue border */
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); /* Blue glow ring */
}

/* Error state */
.form-group input.error {
  border-color: #ef4444; /* Red border */
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); /* Red glow */
}

/* Disabled state */
.form-group input:disabled {
  background: #f9fafb;
  color: #9ca3af;
  cursor: not-allowed;
}

/* Placeholder text */
.form-group input::placeholder {
  color: #9ca3af; /* Light gray */
  opacity: 1;
}
```

---

### 12.7 Floating Action Button

**File:** `web/modules/custom/crm_quickadd/css/floating_button.css`

```css
.quickadd-floating-btn {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  background: linear-gradient(
    135deg,
    #3b82f6 0%,
    #2563eb 100%
  ); /* Blue gradient */
  color: white;
  border: none;
  border-radius: 50%; /* Perfect circle */
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  cursor: pointer;
  transition: all 0.3s ease;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Hover: Grow + rotate */
.quickadd-floating-btn:hover {
  transform: scale(1.1) rotate(90deg); /* Grow 10%, rotate + icon 90° */
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5); /* Larger shadow */
}

/* Click: Shrink briefly */
.quickadd-floating-btn:active {
  transform: scale(0.95) rotate(90deg);
}

/* Icon inside button */
.quickadd-floating-btn svg {
  width: 24px;
  height: 24px;
  color: white;
  transition: transform 0.3s ease;
}
```

**Visual effect:**

- Default: Blue circle with + icon in bottom-right corner
- Hover: Button grows + rotates (+ becomes ×)
- Click: Brief shrink animation
- Always visible, floats above page content

---

### 12.8 Complete CSS File Reference

| CSS File                                   | Library Name                       | Loaded On               | Purpose                           |
| ------------------------------------------ | ---------------------------------- | ----------------------- | --------------------------------- |
| `crm/css/crm-layout.css`                   | `crm/crm_layout`                   | All CRM pages           | Full-width layout, spacing tokens |
| `crm_actions/css/crm_actions.css`          | `crm_actions/global_nav`           | All CRM pages           | Global navigation bar             |
| `crm_edit/css/inline-edit.css`             | `crm_edit/inline_edit`             | Pages with edit buttons | Edit modal styling                |
| `crm_quickadd/css/quickadd.css`            | `crm_quickadd/quickadd`            | All CRM pages           | Quick-add modal                   |
| `crm_quickadd/css/floating_button.css`     | `crm_quickadd/floating_button`     | All CRM pages           | FAB button                        |
| `crm_activity_log/css/activity-widget.css` | `crm_activity_log/activity_widget` | Entity detail pages     | Activity timeline                 |
| `crm_login/css/login-form.css`             | `crm_login/login_form`             | `/login`                | Custom login page                 |
| `crm_register/css/register-form.css`       | `crm_register/register_form`       | `/user/register`        | Registration page                 |
| `crm/css/user-profile.css`                 | `crm/user_profile_styles`          | `/user/{uid}`           | User profile styling              |
| `crm_navigation/css/navigation.css`        | `crm_navigation/navigation`        | All CRM pages           | Back buttons, breadcrumbs         |

---

## 13. JavaScript Features Complete Guide

### 13.1 JavaScript Architecture

**Pattern:** Vanilla JavaScript with global namespace objects

**No framework:** No React, Vue, Angular, or jQuery

**External libraries:**

1. **Chart.js** (4.4.1) - Dashboard visualizations
2. **Sortable.js** (1.15.0) - Kanban drag-and-drop
3. **Lucide** (latest) - Icon library (external CDN)

**Global namespaces:**

- `window.CRMInlineEdit` - Edit modal system
- `window.CRMQuickAdd` - Quick-add modal system
- `window.Chart` - Chart.js library

---

### 13.2 Inline Edit JavaScript

**File:** `web/modules/custom/crm_edit/js/inline-edit.js` (739 lines)

#### Global Object Structure

```javascript
window.CRMInlineEdit = {
  // Properties
  currentModal: null,

  // Methods
  openModal: function (nid, type) {
    /* ... */
  },
  closeModal: function () {
    /* ... */
  },
  setupModalHandlers: function () {
    /* ... */
  },
  saveModal: function () {
    /* ... */
  },
  confirmDelete: function (nid) {
    /* ... */
  },
  showMessage: function (text, type) {
    /* ... */
  },
  handleFieldChange: function (field) {
    /* ... */
  },
};
```

---

#### Function: `openModal(nid, type)`

**Signature:**

```javascript
CRMInlineEdit.openModal(nid, type);
```

**Parameters:**

- `nid` (number): Node ID to edit
- `type` (string): Content type (`'contact'`, `'deal'`, `'organization'`, `'activity'`)

**Returns:** `void`

**Complete implementation:**

```javascript
openModal: function(nid, type) {
  // STEP 1: Prevent multiple modals
  if (CRMInlineEdit.currentModal) {
    CRMInlineEdit.closeModal();
  }

  // STEP 2: Create overlay with loading spinner
  const overlay = document.createElement('div');
  overlay.id = 'crm-modal-overlay';
  overlay.className = 'crm-modal-overlay';
  overlay.innerHTML = `
    <div class="crm-modal-content">
      <div class="crm-modal-loading">
        <div class="spinner"></div>
        <p>Loading form...</p>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);
  document.body.style.overflow = 'hidden';  // Prevent page scrolling
  CRMInlineEdit.currentModal = overlay;

  // STEP 3: Fetch form HTML via AJAX GET
  fetch(`/crm/edit/${type}/${nid}`, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    return response.text();
  })
  .then(html => {
    // STEP 4: Replace loading spinner with form
    const modalContent = overlay.querySelector('.crm-modal-content');
    modalContent.innerHTML = html;

    // STEP 5: Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
      lucide.createIcons({
        // Target icons only within modal
        parent: modalContent
      });
    }

    // STEP 6: Setup event handlers
    CRMInlineEdit.setupModalHandlers();

    // STEP 7: Focus first input (better UX)
    setTimeout(() => {
      const firstInput = modalContent.querySelector('input[type="text"]:not([readonly]), input[type="email"]:not([readonly])');
      if (firstInput) {
        firstInput.focus();
        firstInput.select();  // Select existing text for easy overwriting
      }
    }, 100);
  })
  .catch(error => {
    console.error('[CRMInlineEdit] Error loading form:', error);
    CRMInlineEdit.showMessage('Error loading form. Please try again.', 'error');
    CRMInlineEdit.closeModal();
  });
}
```

**AJAX request example:**

```http
GET /crm/edit/contact/123 HTTP/1.1
Host: example.com
X-Requested-With: XMLHttpRequest
Cookie: SESS=abc123...
```

**Response example:**

```html
<form id="crm-edit-form" data-nid="123" data-type="contact" method="post">
  <div class="crm-modal-header">
    <h2><i data-lucide="user"></i> Edit Contact</h2>
    <button type="button" class="crm-modal-close" aria-label="Close">
      <i data-lucide="x"></i>
    </button>
  </div>

  <div class="crm-modal-body">
    <div class="form-group">
      <label for="edit-title">Name <span class="required">*</span></label>
      <input
        type="text"
        id="edit-title"
        name="title"
        value="John Doe"
        required
      />
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="edit-email">Email</label>
        <input
          type="email"
          id="edit-email"
          name="field_email"
          value="john@example.com"
        />
      </div>

      <div class="form-group">
        <label for="edit-phone">Phone <span class="required">*</span></label>
        <input
          type="text"
          id="edit-phone"
          name="field_phone"
          value="0912345678"
          required
        />
      </div>
    </div>

    <!-- More fields... -->
  </div>

  <div class="crm-modal-actions">
    <button type="button" class="btn-delete">
      <i data-lucide="trash-2"></i> Delete
    </button>
    <div>
      <button type="button" class="btn-cancel">Cancel</button>
      <button type="button" class="btn-save">
        <i data-lucide="save"></i> Save Changes
      </button>
    </div>
  </div>
</form>
```

---

#### Function: `setupModalHandlers()`

**Purpose:** Attaches all event listeners to modal

```javascript
setupModalHandlers: function() {
  const overlay = document.getElementById('crm-modal-overlay');
  if (!overlay) return;

  const form = overlay.querySelector('#crm-edit-form');
  if (!form) return;

  const closeBtn = overlay.querySelector('.crm-modal-close');
  const cancelBtn = overlay.querySelector('.btn-cancel');
  const saveBtn = overlay.querySelector('.btn-save');
  const deleteBtn = overlay.querySelector('.btn-delete');

  // HANDLER 1: Click outside modal to close
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      // Clicked on backdrop (not modal content)
      if (CRMInlineEdit.hasUnsavedChanges()) {
        if (confirm('You have unsaved changes. Are you sure you want to close?')) {
          CRMInlineEdit.closeModal();
        }
      } else {
        CRMInlineEdit.closeModal();
      }
    }
  });

  // HANDLER 2: Close button (X in top-right)
  if (closeBtn) {
    closeBtn.addEventListener('click', function() {
      CRMInlineEdit.closeModal();
    });
  }

  // HANDLER 3: Cancel button
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function() {
      CRMInlineEdit.closeModal();
    });
  }

  // HANDLER 4: Save button
  if (saveBtn) {
    saveBtn.addEventListener('click', function(e) {
      e.preventDefault();
      CRMInlineEdit.saveModal();
    });
  }

  // HANDLER 5: Delete button
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function(e) {
      e.preventDefault();
      const nid = form.dataset.nid;
      CRMInlineEdit.confirmDelete(nid);
    });
  }

  // HANDLER 6: ESC key to close
  const escapeHandler = function(e) {
    if (e.key === 'Escape' || e.key === 'Esc') {
      CRMInlineEdit.closeModal();
      // Remove this listener after use
      document.removeEventListener('keydown', escapeHandler);
    }
  };
  document.addEventListener('keydown', escapeHandler);

  // HANDLER 7: Enter in single-line text fields submits form
  const textInputs = form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]');
  textInputs.forEach(input => {
    input.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        CRMInlineEdit.saveModal();
      }
    });
  });

  // HANDLER 8: Track changes for unsaved warning
  const formFields = form.querySelectorAll('input, select, textarea');
  formFields.forEach(field => {
    field.addEventListener('change', function() {
      form.dataset.hasChanges = 'true';
    });
  });
}
```

**Event listener summary:**

| Event    | Element          | Action                      |
| -------- | ---------------- | --------------------------- |
| Click    | Backdrop         | Close if no unsaved changes |
| Click    | Close button (X) | Close modal                 |
| Click    | Cancel button    | Close modal                 |
| Click    | Save button      | Submit via AJAX             |
| Click    | Delete button    | Confirm and delete          |
| Keydown  | Document         | ESC to close                |
| Keypress | Text inputs      | Enter to submit             |
| Change   | Form fields      | Mark as "has changes"       |

---

#### Function: `saveModal()`

**Purpose:** Submits form data via AJAX POST

```javascript
saveModal: function() {
  const form = document.getElementById('crm-edit-form');
  if (!form) return;

  const saveBtn = form.querySelector('.btn-save');
  const nid = form.dataset.nid;
  const type = form.dataset.type;

  // STEP 1: Validate required fields
  const requiredFields = form.querySelectorAll('[required]');
  let validationFailed = false;

  requiredFields.forEach(field => {
    if (!field.value || field.value.trim() === '') {
      field.classList.add('error');
      validationFailed = true;

      // Show error message
      let errorMsg = field.parentElement.querySelector('.error-message');
      if (!errorMsg) {
        errorMsg = document.createElement('div');
        errorMsg.className = 'error-message';
        errorMsg.textContent = 'This field is required';
        field.parentElement.appendChild(errorMsg);
      }
    } else {
      field.classList.remove('error');
      const errorMsg = field.parentElement.querySelector('.error-message');
      if (errorMsg) {
        errorMsg.remove();
      }
    }
  });

  if (validationFailed) {
    CRMInlineEdit.showMessage('Please fill in all required fields', 'error');
    return;
  }

  // STEP 2: Disable save button (prevent double-submit)
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<div class="spinner-small"></div> Saving...';

  // STEP 3: Collect form data
  const formData = new FormData(form);
  formData.append('nid', nid);
  formData.append('type', type);

  // STEP 4: Send AJAX POST
  fetch('/crm/edit/ajax/save', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    },
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // STEP 5: Show success message
      CRMInlineEdit.showMessage(data.message || 'Saved successfully', 'success');

      // STEP 6: Close modal
      CRMInlineEdit.closeModal();

      // STEP 7: Reload page to show updated data
      // (Alternative: Update data in DOM without reload)
      setTimeout(() => {
        window.location.reload();
      }, 500);
    } else {
      // Show error message from backend
      CRMInlineEdit.showMessage(data.message || 'Save failed', 'error');

      // Re-enable save button
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i data-lucide="save"></i> Save Changes';
      lucide.createIcons({ parent: saveBtn });
    }
  })
  .catch(error => {
    console.error('[CRMInlineEdit] Save error:', error);
    CRMInlineEdit.showMessage('Network error. Please check your connection.', 'error');

    // Re-enable save button
    saveBtn.disabled = false;
    saveBtn.innerHTML = '<i data-lucide="save"></i> Save Changes';
    lucide.createIcons({ parent: saveBtn });
  });
}
```

**AJAX request:**

```http
POST /crm/edit/ajax/save HTTP/1.1
Content-Type: multipart/form-data; boundary=----Boundary123

------Boundary123
Content-Disposition: form-data; name="nid"

123
------Boundary123
Content-Disposition: form-data; name="type"

contact
------Boundary123
Content-Disposition: form-data; name="title"

John Doe Updated
------Boundary123
Content-Disposition: form-data; name="field_email"

john.updated@example.com
------Boundary123
Content-Disposition: form-data; name="field_phone"

0987654321
------Boundary123--
```

**Response (success):**

```json
{
  "success": true,
  "message": "Contact updated successfully",
  "nid": 123
}
```

**Response (error):**

```json
{
  "success": false,
  "message": "Phone number already exists",
  "errors": {
    "field_phone": "This phone number is already in use"
  }
}
```

---

#### Function: `confirmDelete(nid)`

**Purpose:** Delete entity with confirmation dialog

```javascript
confirmDelete: function(nid) {
  // STEP 1: Show native browser confirmation
  const confirmed = confirm(
    'Are you sure you want to delete this item?\n\n' +
    'This action cannot be undone.'
  );

  if (!confirmed) {
    return;  // User clicked "Cancel"
  }

  // STEP 2: Show loading state
  CRMInlineEdit.showMessage('Deleting...', 'info');

  // STEP 3: Send delete request
  fetch('/crm/edit/ajax/delete', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({ nid: nid }),
    credentials: 'same-origin'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // STEP 4: Show success message
      CRMInlineEdit.showMessage('Deleted successfully', 'success');

      // STEP 5: Close modal
      CRMInlineEdit.closeModal();

      // STEP 6: Reload page (deleted item will be removed from list)
      setTimeout(() => {
        // If on detail page, redirect to list
        if (window.location.pathname.includes('/node/')) {
          window.location.href = '/crm/my-contacts';  // Or appropriate list page
        } else {
          window.location.reload();
        }
      }, 800);
    } else {
      CRMInlineEdit.showMessage(data.message || 'Delete failed', 'error');
    }
  })
  .catch(error => {
    console.error('[CRMInlineEdit] Delete error:', error);
    CRMInlineEdit.showMessage('Network error', 'error');
  });
}
```

**Delete request:**

```http
POST /crm/edit/ajax/delete HTTP/1.1
Content-Type: application/json

{"nid": 123}
```

**Response:**

```json
{
  "success": true,
  "message": "Contact deleted successfully"
}
```

---

#### Function: `showMessage(text, type)`

**Purpose:** Display toast notification

```javascript
showMessage: function(text, type) {
  // Remove any existing toast
  const existing = document.querySelector('.crm-toast-message');
  if (existing) {
    existing.remove();
  }

  // Create toast element
  const toast = document.createElement('div');
  toast.className = `crm-toast-message crm-toast-${type}`;

  // Icon based on type
  const icons = {
    success: 'check-circle',
    error: 'x-circle',
    warning: 'alert-triangle',
    info: 'info'
  };
  const icon = icons[type] || 'info';

  toast.innerHTML = `
    <i data-lucide="${icon}"></i>
    <span>${text}</span>
  `;

  document.body.appendChild(toast);

  // Initialize icon
  if (typeof lucide !== 'undefined') {
    lucide.createIcons({ parent: toast });
  }

  // Animate in
  setTimeout(() => {
    toast.classList.add('show');
  }, 10);

  // Auto-hide after 3 seconds
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => {
      toast.remove();
    }, 300);  // Wait for fade-out animation
  }, 3000);
}
```

**Toast CSS:**

```css
.crm-toast-message {
  position: fixed;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  font-size: 14px;
  font-weight: 500;
  transform: translateX(400px); /* Start off-screen */
  opacity: 0;
  transition: all 0.3s ease;
  z-index: 10001;
}

.crm-toast-message.show {
  transform: translateX(0); /* Slide in */
  opacity: 1;
}

.crm-toast-success {
  background: #10b981;
  color: white;
}

.crm-toast-error {
  background: #ef4444;
  color: white;
}

.crm-toast-warning {
  background: #f59e0b;
  color: white;
}

.crm-toast-info {
  background: #3b82f6;
  color: white;
}
```

---

### 13.3 Quick Add JavaScript

**File:** `web/modules/custom/crm_quickadd/js/quickadd.js`

**Purpose:** Floating button + quick-add modal system

#### Floating Button Toggle

```javascript
document.addEventListener("DOMContentLoaded", function () {
  const fabBtn = document.getElementById("crm-quickadd-fab");
  const menu = document.getElementById("crm-quickadd-menu");

  if (!fabBtn || !menu) return;

  // Toggle menu on FAB click
  fabBtn.addEventListener("click", function (e) {
    e.stopPropagation();

    const isActive = menu.classList.contains("active");

    if (isActive) {
      menu.classList.remove("active");
      fabBtn.classList.remove("active");
    } else {
      menu.classList.add("active");
      fabBtn.classList.add("active");
    }
  });

  // Close menu when clicking outside
  document.addEventListener("click", function (e) {
    if (!fabBtn.contains(e.target) && !menu.contains(e.target)) {
      menu.classList.remove("active");
      fabBtn.classList.remove("active");
    }
  });

  // Handle menu item clicks
  const menuItems = menu.querySelectorAll("[data-type]");
  menuItems.forEach((item) => {
    item.addEventListener("click", function () {
      const type = this.dataset.type;

      // Close menu
      menu.classList.remove("active");
      fabBtn.classList.remove("active");

      // Open quick-add modal
      CRMQuickAdd.openModal(type);
    });
  });
});
```

---

#### Quick Add Modal System

```javascript
window.CRMQuickAdd = {
  currentModal: null,

  openModal: function(type) {
    // Prevent multiple modals
    if (C
```
