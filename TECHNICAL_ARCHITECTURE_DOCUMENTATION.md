# Open CRM - Technical Architecture Documentation

**Version:** 1.0  
**Platform:** Drupal 10/11  
**Date:** March 6, 2026  
**Author:** System Architecture Analysis

---

## Table of Contents

1. [System Architecture](#1-system-architecture)
2. [Folder Structure](#2-folder-structure)
3. [Key Files Explained](#3-key-files-explained)
4. [Page Architecture](#4-page-architecture)
5. [UI and CSS Mapping](#5-ui-and-css-mapping)
6. [Feature Implementation](#6-feature-implementation)
7. [Data Flow](#7-data-flow)
8. [Permissions Model](#8-permissions-model)
9. [Improvement Suggestions](#9-improvement-suggestions)

---

## 1. System Architecture

### 1.1 High-Level Overview

Open CRM is built on **Drupal 10/11** using a **modular architecture** with custom modules. The system follows a **node-based data model** where all CRM entities (Contacts, Deals, Activities, Organizations) are implemented as Drupal content types with custom fields.

**Key Architectural Decisions:**

- **No custom entities**: Uses Drupal's built-in node system
- **Field-based extensibility**: All data stored in custom fields
- **Views-driven display**: Most listings use Drupal Views
- **Controller-based custom pages**: Dashboard and special features use custom controllers
- **Role-based access control**: Uses Drupal permissions + custom access hooks
- **Team-based data filtering**: Ownership fields (`field_owner`, `field_assigned_to`) control visibility

### 1.2 Core Components

```
┌─────────────────────────────────────────────────────┐
│                   User Interface                     │
│  Login/Register → Dashboard → Lists → Detail Views   │
└─────────────────┬───────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────┐
│              Custom Modules Layer                    │
│  crm, crm_dashboard, crm_edit, crm_kanban, etc.     │
└─────────────────┬───────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────┐
│              Drupal Core Layer                       │
│  Node system, Views, User, Taxonomy, Fields          │
└─────────────────┬───────────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────────┐
│                 Database (MariaDB)                   │
│  node_field_data + field tables + config tables      │
└──────────────────────────────────────────────────────┘
```

### 1.3 Content Type Architecture

**Core Content Types:**

| Content Type | Machine Name   | Purpose                         | Owner Field            |
| ------------ | -------------- | ------------------------------- | ---------------------- |
| Contact      | `contact`      | Customer/lead information       | `field_owner`          |
| Deal         | `deal`         | Sales opportunities             | `field_owner`          |
| Organization | `organization` | Companies                       | `field_assigned_staff` |
| Activity     | `activity`     | Tasks, meetings, calls          | `field_assigned_to`    |
| Page         | `page`         | Static content (homepage, etc.) | N/A                    |

### 1.4 Module Ecosystem

**16 Custom Modules:**

```
web/modules/custom/
├── crm/                    # Core module: access control, ownership
├── crm_actions/            # Global navigation bar
├── crm_activity_log/       # Activity timeline widget
├── crm_contact360/         # Contact detail view
├── crm_dashboard/          # Dashboard with KPIs and charts
├── crm_edit/               # Inline editing (modals, AJAX)
├── crm_import/             # CSV import (Feeds integration)
├── crm_import_export/      # Import/export functionality
├── crm_kanban/             # Pipeline kanban board
├── crm_login/              # Custom login page
├── crm_navigation/         # Navigation enhancements
├── crm_notifications/      # Notification system
├── crm_quickadd/           # Floating quick-add button
├── crm_register/           # Custom registration page
├── crm_teams/              # Team management
├── crm_workflow/           # Workflow automation
```

---

## 2. Folder Structure

### 2.1 Project Root

```
open_crm/
├── composer.json           # PHP dependencies
├── web/                    # Drupal web root
│   ├── core/              # Drupal core (do not modify)
│   ├── modules/
│   │   ├── contrib/       # Contributed modules (Views, Search API, etc.)
│   │   └── custom/        # Custom CRM modules ⭐
│   ├── themes/            # Themes (likely using Claro/Gin admin theme)
│   ├── sites/
│   │   └── default/
│   │       ├── files/     # Uploaded files
│   │       └── settings.php
│   └── ...
├── config/                 # Configuration management ⭐
│   ├── views.view.*.yml   # Views configurations
│   └── search_api.index.*.yml
├── scripts/                # Utility scripts ⭐
├── fixtures/               # Sample data
├── vendor/                 # Composer dependencies
└── docs/                   # Documentation
```

### 2.2 Custom Module Structure (Example: `crm_edit`)

```
crm_edit/
├── crm_edit.info.yml       # Module metadata
├── crm_edit.routing.yml    # Route definitions ⭐
├── crm_edit.module         # Hooks and helper functions
├── crm_edit.libraries.yml  # CSS/JS library definitions
├── src/
│   ├── Controller/         # Page controllers ⭐
│   │   ├── InlineEditController.php
│   │   ├── ModalEditController.php
│   │   ├── AddController.php
│   │   └── DeleteController.php
│   ├── Form/              # Drupal forms
│   └── Plugin/
│       └── views/
│           └── field/     # Custom Views field plugin
│               └── CrmEditLink.php ⭐
├── css/
│   └── inline-edit.css    # Module-specific styles
├── js/
│   └── inline-edit.js     # Frontend logic
└── templates/             # Twig templates
```

### 2.3 Configuration Folder

**Purpose:** Stores exported Views and Search API configurations  
**Key Files:**

- `views.view.my_contacts.yml` - Contact list view
- `views.view.my_deals.yml` - Deal list view
- `views.view.all_organizations.yml` - Organization list (admin view)
- `search_api.index.crm_contacts_index.yml` - Search indexing for contacts

**Note:** These are **configuration exports**, not dynamically loaded. They must be imported via `drush cim` or UI.

---

## 3. Key Files Explained

### 3.1 Core Module: `crm`

**Purpose:** Provides unified access control and ownership tracking for all CRM content.

#### [crm.module](web/modules/custom/crm/crm.module)

**Key Functions:**

```php
crm_node_access(NodeInterface $node, $op, AccountInterface $account)
```

- **What it does:** Controls who can view/edit/delete CRM nodes
- **Logic:**
  - Administrators: Full access
  - Sales Managers: View all team content
  - Sales Reps: Only own content OR same team content
- **Ownership determination:**
  - Contacts/Deals: `field_owner`
  - Activities: `field_assigned_to`
  - Organizations: `field_assigned_staff`

```php
crm_query_node_access_alter($query)
```

- **What it does:** Automatically filters Views and entity queries
- **When triggered:** Any query tagged with `node_access`
- **Example:** Sales rep viewing `/crm/my-contacts` only sees their contacts

**Dependencies:** None (core module)

#### [crm.routing.yml](web/modules/custom/crm/crm.routing.yml)

```yaml
crm.user_profile:
  path: "/user/{user}/profile"
  defaults:
    _controller: '\Drupal\crm\Controller\UserProfileController::view'
```

**Routes defined:**

- `/user/{user}/profile` - User profile page
- `/user/{user}` - Canonical user page

#### [crm.libraries.yml](web/modules/custom/crm/crm.libraries.yml)

**Libraries:**

- `user_profile_styles` - User profile page CSS/JS
- `crm_layout` - Global CRM layout styles

**Loaded on:** User profile pages

---

### 3.2 Dashboard Module: `crm_dashboard`

**Purpose:** Displays KPI cards and analytics charts on the dashboard.

#### [DashboardController.php](web/modules/custom/crm_dashboard/src/Controller/DashboardController.php)

**Method:** `view()`

**What it does:**

1. Gets current user ID
2. Checks if user is admin/manager
3. Queries database for counts:
   - Contacts
   - Organizations
   - Deals
   - Activities
4. Loads pipeline stages from taxonomy
5. Counts deals per stage
6. Calculates total deal value
7. Renders dashboard with KPI cards + pipeline chart

**Data filtering:**

```php
if (!$is_admin) {
  $contacts_query->condition('field_owner', $user_id);
}
```

Non-admins only see their own data.

**Renders:** HTML markup with embedded Chart.js visualizations

**Routes:**

- `/admin/crm` - Admin dashboard
- `/crm/dashboard` - User dashboard
- `/crm/dashboard-new` - Test dashboard

---

### 3.3 Edit Module: `crm_edit`

**Purpose:** Provides inline editing, modal editing, AJAX save/delete, and quick-add functionality.

#### Key Controllers:

**[InlineEditController.php](web/modules/custom/crm_edit/src/Controller/InlineEditController.php)**

Methods:

- `editContact()`, `editDeal()`, `editOrganization()`, `editActivity()` - Load edit forms
- `ajaxSave()` - Handle AJAX form submission
- `ajaxValidate()` - Real-time validation

**Data flow:**

```
User clicks "Edit" → Modal opens → User edits fields → AJAX POST to /crm/edit/ajax/save → Node updated → JSON response → UI updates
```

**[AddController.php](web/modules/custom/crm_edit/src/Controller/AddController.php)**

Methods:

- `addPage()` - Render add form page
- `getCreateForm()` - Return form HTML for modal
- `ajaxCreate()` - Handle AJAX creation

**[DeleteController.php](web/modules/custom/crm_edit/src/Controller/DeleteController.php)**

Method:

- `ajaxDelete()` - Soft/hard delete nodes via AJAX

#### Views Plugin:

**[CrmEditLink.php](web/modules/custom/crm_edit/src/Plugin/views/field/CrmEditLink.php)**

- **Type:** Custom Views field plugin
- **Used in:** All CRM list views (my_contacts, my_deals, etc.)
- **What it does:** Adds "Edit | Delete" action links to each row
- **Example output:**
  ```html
  <a href="/crm/edit/contact/123" class="edit-link">Edit</a> |
  <a href="#" data-node-id="123" class="delete-link">Delete</a>
  ```

---

### 3.4 Kanban Module: `crm_kanban`

**Purpose:** Visualizes deals in a drag-and-drop pipeline board.

#### [KanbanController.php](web/modules/custom/crm_kanban/src/Controller/KanbanController.php)

**Method:** `view()`

**What it does:**

1. Gets current user ID and role
2. Loads pipeline stages from `pipeline_stage` taxonomy
3. For each stage:
   - Queries deals in that stage
   - Filters by ownership (`field_owner`) for non-admins
   - Loads deal nodes
4. Renders Kanban board HTML with columns
5. Attaches drag-and-drop JS library

**Method:** `updateStage()`

**What it does:**

- Receives AJAX POST with `deal_id` and `new_stage`
- Updates `field_stage` on the deal node
- Returns JSON success/error

**Routes:**

- `/crm/pipeline` - User's own pipeline
- `/crm/all-pipeline` - All deals pipeline (admin view)

---

### 3.5 Import/Export Module: `crm_import_export`

**Purpose:** CSV import and export for contacts and deals.

#### [ImportController.php](web/modules/custom/crm_import_export/src/Controller/ImportController.php)

- **Method:** `importPage()`
- **Route:** `/crm/import`, `/admin/crm/import`
- **Display:** Landing page with links to import forms

#### [ImportContactsForm.php](web/modules/custom/crm_import_export/src/Form/ImportContactsForm.php)

**Form fields:**

- File upload (CSV)
- Field mapping options

**Submit handler:**

1. Reads CSV file
2. Maps columns to Drupal fields
3. Creates contact nodes in batch
4. Sets `field_owner` to current user
5. Displays success message

#### [ExportController.php](web/modules/custom/crm_import_export/src/Controller/ExportController.php)

**Methods:**

- `exportContacts()` - Exports all contacts to CSV
- `exportDeals()` - Exports all deals to CSV

**Logic:**

1. Queries nodes of specified type
2. Filters by ownership (non-admins only see own data)
3. Loads field values
4. Generates CSV
5. Sends file download response

---

### 3.6 Global Actions Module: `crm_actions`

**Purpose:** Displays global navigation bar on all CRM pages.

#### [crm_actions.module](web/modules/custom/crm_actions/crm_actions.module)

**Hook:** `hook_page_top()`

**What it does:**

1. Checks if user is authenticated
2. Checks if current page is a CRM page (`/crm/*`, `/node/*`, `/app/*`)
3. Builds navigation bar HTML
4. Injects it at the top of the page
5. Attaches CSS/JS library

**Navigation items (non-admin):**

- Dashboard
- Contacts
- Deals
- Pipeline
- Activities
- Organizations
- Teams
- Profile

**Navigation items (admin/manager):**

- Same, but links to "All" views instead of "My" views

**Function:** `_crm_actions_build_navbar()`

**Output:**

```html
<nav class="crm-global-nav">
  <div class="nav-items">
    <a href="/crm/dashboard" class="nav-item active">
      <i data-lucide="layout-dashboard"></i>
      <span>Dashboard</span>
    </a>
    ...
  </div>
</nav>
```

---

### 3.7 Login Module: `crm_login`

**Purpose:** Custom branded login page with custom form and routing.

#### [CrmLoginForm.php](web/modules/custom/crm_login/src/Form/CrmLoginForm.php)

**What it does:**

- Extends Drupal's UserLoginForm
- Custom HTML structure (card layout, image column)
- Custom validation
- Redirects to `/crm/dashboard` on success

#### [RedirectController.php](web/modules/custom/crm_login/src/Controller/RedirectController.php)

**Purpose:** Redirects `/user/login` to `/login`

#### [login-form.css](web/modules/custom/crm_login/css/login-form.css)

**Styles:**

- `.auth-card` - Login card container
- `.auth-form-column` - Form column
- `.auth-image-column` - Right-side image
- `.btn-auth-submit` - Submit button (white background, blue border)

**Design:** Minimal, flat, modern SaaS-style

---

### 3.8 Activity Log Module: `crm_activity_log`

**Purpose:** Displays timeline widget showing recent activities related to a contact/deal.

#### [ActivityLogController.php](web/modules/custom/crm_activity_log/src/Controller/ActivityLogController.php)

**Method:** `view($node)`

**What it does:**

1. Receives a node ID (contact or deal)
2. Queries activity nodes linked to that node via `field_contact` or `field_deal`
3. Loads activity nodes
4. Renders timeline widget

**Used on:** Contact detail pages, Deal detail pages

**Rendered via:** Block or embedded in node view

---

## 4. Page Architecture

### 4.1 Homepage (`/`)

**Node:** Node 142 (Quick Access - CRM)  
**Template:** Default node template  
**Content:** HTML in body field

**Sections:**

1. **Dashboard Header** (`<div class="dashboard-header">`)
   - Logo: "Open CRM"
   - Description text
2. **Login Banner** (`.crm-login-banner`) - Only for anonymous users
   - Login button (white background, blue border)
   - Register button (hidden via `display: none`)
3. **Quick Access Cards** (`.quick-access-cards`)
   - Contacts card → `/crm/my-contacts`
   - Deals card → `/crm/my-deals`
   - Organizations card → `/crm/my-organizations`
   - Pipeline card → `/crm/pipeline`
   - Activities card → `/crm/my-activities`
   - Teams card → `/admin/structure/taxonomy/manage/team/overview`

**CSS:** Embedded inline styles in node body

**Visibility logic:**

- Anonymous users: See login banner + cards (links disabled)
- Authenticated users: See cards (links enabled), no login banner

---

### 4.2 Dashboard (`/crm/dashboard`)

**Route:** `crm_dashboard.dashboard`  
**Controller:** `DashboardController::view()`  
**Template:** Rendered in controller (no Twig template)

**Sections:**

1. **KPI Cards**
   - Total Contacts
   - Total Organizations
   - Total Deals
   - Total Activities

   **Data source:** Entity queries with ownership filters

2. **Pipeline Chart**
   - Horizontal bar chart (Chart.js)
   - Shows deal count per pipeline stage
   - Dynamic colors

   **Data source:** Taxonomy terms (`pipeline_stage`) + deal queries

3. **Revenue Chart**
   - Bar chart showing total deal value
   - Win rate percentage

   **Data source:** `field_amount` aggregation

**CSS:**

- Inline styles in controller output
- Global styles from `crm/css/crm-layout.css`

**JS:**

- Chart.js (CDN)
- Embedded chart initialization scripts

**Access control:**

- Non-admins: See only their own data
- Admins/managers: See all data

---

### 4.3 Contact List (`/crm/my-contacts`)

**Route:** Defined in Views (`views.view.my_contacts`)  
**View:** `my_contacts`  
**Display:** Page display  
**Path:** `/crm/my-contacts`

**View configuration:**

- **Base table:** `node_field_data`
- **Filters:**
  - Content type = `contact`
  - Status = Published
  - Owner = Current user (via `field_owner`) ⭐

**Fields displayed:**

1. Title (Name) - Links to node
2. Organization - Entity reference label
3. Phone
4. Email
5. Source - Taxonomy term
6. Customer Type - Taxonomy term
7. Actions - Custom field plugin (`crm_edit_link`)

**Pager:** 25 items per page

**Exposed filters:**

- Search by name
- Filter by source
- Filter by customer type

**CSS:**

- Global table styles from admin theme
- Custom styles from `crm_actions/css/crm_actions.css`

**Template:** Default Views table template

**Access:** `access content` permission + ownership filter

---

### 4.4 Deal List (`/crm/my-deals`)

**Route:** Views-generated (`views.view.my_deals`)  
**View:** `my_deals`  
**Path:** `/crm/my-deals`

**Fields:**

1. Deal Name
2. Owner
3. Amount (formatted with thousand separator + " VND")
4. Stage (pipeline_stage taxonomy)
5. Probability (with "%" suffix)
6. Close Date
7. Contact (entity reference)
8. Organization (entity reference)
9. Actions (edit/delete links)

**Filters:**

- Owner = Current user
- Status = Published

**Exposed filters:**

- Search by deal name
- Filter by stage
- Filter by close date range

---

### 4.5 Pipeline Kanban (`/crm/pipeline`)

**Route:** `crm_kanban.pipeline`  
**Controller:** `KanbanController::view()`  
**Template:** Rendered in controller

**Sections:**

```
┌──────────────────────────────────────────────────────────────┐
│  [Lead] → [Qualified] → [Proposal] → [Negotiation] → [Won]   │
│    3        2             5             1             8      │
│  ┌────┐    ┌────┐      ┌────┐       ┌────┐        ┌────┐   │
│  │Deal│    │Deal│      │Deal│       │Deal│        │Deal│   │
│  │    │    │    │      │    │       │    │        │    │   │
│  └────┘    └────┘      └────┘       └────┘        └────┘   │
│  └────┘    ...         ...          ...           ...      │
└──────────────────────────────────────────────────────────────┘
```

**Data flow:**

1. Load stages from taxonomy
2. For each stage, query deals with `field_stage = stage_id`
3. Display as draggable cards
4. On drag end:
   - AJAX POST to `/crm/pipeline/update-stage`
   - Update `field_stage`
   - Return JSON response

**JS Library:**

- Sortable.js or custom drag-and-drop
- Attached via `crm_kanban/kanban_board` library

**CSS:**

- `.kanban-board` - Main container
- `.kanban-column` - Stage columns
- `.kanban-card` - Deal cards

---

### 4.6 Contact Detail (`/node/{contact-id}`)

**Route:** Default Drupal node view  
**Template:** `node--contact.html.twig` (if exists) or default

**Sections:**

1. **Header**
   - Contact name
   - Avatar image
   - Edit button (if user has permission)

2. **Contact Information**
   - Email
   - Phone
   - Organization (linked)
   - Position
   - LinkedIn profile
   - Source
   - Customer type

3. **Activity Timeline** (from `crm_activity_log` module)
   - Recent activities related to this contact
   - Rendered via block or hook

4. **Related Deals** (via Views block)
   - Deals linked to this contact

**CSS:**

- `.contact-detail` - Main container
- Styles from `crm/css/user-profile.css`

**Access control:**

- Check ownership via `crm_node_access()`

---

### 4.7 Add Contact (`/crm/add/contact`)

**Route:** `crm_edit.add_contact`  
**Controller:** `AddController::addPage()`  
**Form:** Default node add form (contact bundle)

**What it does:**

1. Loads default Drupal node add form
2. Pre-fills `field_owner` with current user
3. Displays in modal (if via AJAX) or full page
4. On submit:
   - Creates contact node
   - Sets ownership fields
   - Redirects to contact list

**Alternative:** Quick-add modal via `crm_quickadd` module

---

### 4.8 Import Page (`/crm/import`)

**Route:** `crm_import_export.import_page_user`  
**Controller:** `ImportController::importPage()`  
**Template:** Custom HTML in controller

**Content:**

- Link to `/admin/crm/import/contacts` (Import Contacts form)
- Link to `/admin/crm/import/deals` (Import Deals form)
- Instructions
- Download sample CSV templates

---

## 5. UI and CSS Mapping

### 5.1 Global CSS Architecture

**Loading order:**

1. **Drupal Core CSS** (system.css, etc.)
2. **Admin Theme CSS** (Claro/Gin)
3. **Module-specific CSS** (loaded via libraries)
4. **Inline CSS** (in node body, controller output)

### 5.2 CSS File Mapping

| File                                       | Scope                   | Loaded On             | Components                                        |
| ------------------------------------------ | ----------------------- | --------------------- | ------------------------------------------------- |
| `crm/css/crm-layout.css`                   | Global                  | All CRM pages         | `.crm-container`, `.crm-header`, layout utilities |
| `crm/css/user-profile.css`                 | User profile            | `/user/{uid}/profile` | `.user-profile-card`, `.profile-header`           |
| `crm_actions/css/crm_actions.css`          | Navigation              | All CRM pages         | `.crm-global-nav`, `.nav-item`, `.nav-icon`       |
| `crm_edit/css/inline-edit.css`             | Editing                 | Pages with edit links | `.edit-modal`, `.edit-form`, `.modal-overlay`     |
| `crm_login/css/login-form.css`             | Login                   | `/login`, `/register` | `.auth-card`, `.btn-auth-submit`                  |
| `crm_quickadd/css/quickadd.css`            | Quick-add button        | All CRM pages         | `.floating-add-button`, `.quickadd-menu`          |
| `crm_activity_log/css/activity-widget.css` | Activity timeline       | Contact/deal detail   | `.activity-timeline`, `.timeline-item`            |
| `crm_navigation/css/navigation.css`        | Navigation enhancements | CRM pages             | Navigation utilities                              |

### 5.3 Design System

**Colors:**

- Primary blue: `#3b82f6`
- Hover blue: `#2563eb`
- Light blue: `#eff6ff` (backgrounds)
- Darker blue: `#dbeafe` (active states)
- Success green: `#10b981`
- Warning yellow: `#fbbf24`
- Danger red: `#ef4444`

**Typography:**

- Base font: System font stack
- Headings: Sans-serif, bold
- Body: 14-16px

**Spacing:**

- Base unit: 8px
- Card padding: 16-24px
- Section margin: 24-32px

**Border radius:** 8px (consistent across all components)

**Shadows:**

```css
box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); /* Light */
box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Medium */
```

### 5.4 Reusable Components

#### Card Component

**HTML:**

```html
<div class="crm-card">
  <div class="card-header">
    <h3>Card Title</h3>
  </div>
  <div class="card-body">Content here</div>
</div>
```

**Used in:**

- Dashboard KPI cards
- Quick access cards
- Activity timeline cards

#### Button Component

**Primary button (blue filled):**

```css
.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
}
```

**Secondary button (white with blue border):**

```css
.btn-secondary {
  background: white;
  color: #3b82f6;
  border: 1px solid #3b82f6;
}
```

**Used on:** Login button, form submit buttons, action buttons

#### Table Component

**Style:** Default Drupal table + custom enhancements

**Features:**

- Striped rows (`.views-table`)
- Hover highlight
- Responsive (scrollable on mobile)
- Sortable headers (if enabled in Views)

---

## 6. Feature Implementation

### 6.1 Contact Management

**Content Type:** `contact`

**Key Fields:**

| Field Name     | Machine Name           | Type                        | Description                |
| -------------- | ---------------------- | --------------------------- | -------------------------- |
| Name           | `title`                | String                      | Contact name               |
| Email          | `field_email`          | Email                       | Contact email              |
| Phone          | `field_phone`          | String                      | Phone number               |
| Organization   | `field_organization`   | Entity Reference            | Links to organization node |
| Position       | `field_position`       | String                      | Job title                  |
| Owner          | `field_owner`          | Entity Reference (User)     | Ownership tracking ⭐      |
| Source         | `field_source`         | Entity Reference (Taxonomy) | Lead source                |
| Customer Type  | `field_customer_type`  | Entity Reference (Taxonomy) | Customer category          |
| Avatar         | `field_avatar`         | Image                       | Profile picture            |
| LinkedIn       | `field_linkedin`       | Link                        | LinkedIn profile URL       |
| Notes          | `field_notes`          | Text (long)                 | Internal notes             |
| Last Contacted | `field_last_contacted` | Date                        | Last contact date          |

**Views:**

- `my_contacts` - User's own contacts
- `all_contacts` - All contacts (admin view)

**Features:**

- Create: `/crm/add/contact`
- Edit: Modal via `/crm/edit/contact/{id}`
- Delete: AJAX via `/crm/edit/ajax/delete`
- Import: `/crm/import/contacts`
- Export: `/admin/crm/export/contacts`

**Access control:**

- View: Owner or same team
- Edit: Owner or admin
- Delete: Owner or admin

---

### 6.2 Deal Management

**Content Type:** `deal`

**Key Fields:**

| Field Name   | Machine Name         | Type                        | Description          |
| ------------ | -------------------- | --------------------------- | -------------------- |
| Deal Name    | `title`              | String                      | Deal title           |
| Amount       | `field_amount`       | Decimal                     | Deal value (in VND)  |
| Stage        | `field_stage`        | Entity Reference (Taxonomy) | Pipeline stage ⭐    |
| Probability  | `field_probability`  | Integer                     | Win probability (%)  |
| Closing Date | `field_closing_date` | Date                        | Expected close date  |
| Contact      | `field_contact`      | Entity Reference            | Primary contact      |
| Organization | `field_organization` | Entity Reference            | Related organization |
| Owner        | `field_owner`        | Entity Reference (User)     | Deal owner ⭐        |
| Description  | `field_description`  | Text (long)                 | Deal description     |
| Lost Reason  | `field_lost_reason`  | Entity Reference (Taxonomy) | Reason if lost       |

**Pipeline Stages (Taxonomy: `pipeline_stage`):**

- Lead
- Qualified
- Proposal Sent
- Negotiation
- Closed Won
- Closed Lost

**Views:**

- `my_deals` - User's own deals
- `all_deals` - All deals (admin view)

**Kanban:**

- `/crm/pipeline` - Drag-and-drop deal board

**Features:**

- Create: `/crm/add/deal`
- Edit: Modal via `/crm/edit/deal/{id}`
- Delete: AJAX
- Stage update: Drag-and-drop in Kanban
- Import/Export

**Metrics calculated:**

- Total deal value: `SUM(field_amount)`
- Win rate: `COUNT(stage=Won) / COUNT(all deals)`
- Average deal size: `AVG(field_amount)`

---

### 6.3 Organization Management

**Content Type:** `organization`

**Key Fields:**

| Field Name      | Machine Name            | Type                        | Description         |
| --------------- | ----------------------- | --------------------------- | ------------------- |
| Company Name    | `title`                 | String                      | Organization name   |
| Industry        | `field_industry`        | Entity Reference (Taxonomy) | Industry category   |
| Employees Count | `field_employees_count` | Integer                     | Number of employees |
| Annual Revenue  | `field_annual_revenue`  | Decimal                     | Yearly revenue      |
| Address         | `field_address`         | Text                        | Company address     |
| Phone           | `field_phone`           | String                      | Company phone       |
| Email           | `field_email`           | Email                       | Company email       |
| Assigned Staff  | `field_assigned_staff`  | Entity Reference (User)     | Account manager ⭐  |
| Logo            | `field_logo`            | Image                       | Company logo        |
| Description     | `field_description`     | Text (long)                 | Company description |

**Views:**

- `my_organizations` - User's assigned organizations
- `all_organizations` - All organizations (admin view)

**Related entities:**

- Contacts linked to this organization
- Deals linked to this organization

---

### 6.4 Activity Management

**Content Type:** `activity`

**Key Fields:**

| Field Name     | Machine Name                               | Type                        | Description                         |
| -------------- | ------------------------------------------ | --------------------------- | ----------------------------------- |
| Activity Title | `title`                                    | String                      | Activity name                       |
| Type           | Activity type (call, meeting, email, etc.) |
| Assigned To    | `field_assigned_to`                        | Entity Reference (User)     | Owner ⭐                            |
| Contact        | `field_contact_ref`                        | Entity Reference            | Related contact                     |
| Deal           | `field_deal`                               | Entity Reference            | Related deal                        |
| Date & Time    | `field_datetime`                           | Date/time                   | When activity occurs                |
| Outcome        | `field_outcome`                            | Entity Reference (Taxonomy) | Result (completed, cancelled, etc.) |
| Notes          | `field_notes`                              | Text (long)                 | Activity notes                      |

**Views:**

- `my_activities` - User's assigned activities
- `all_activities` - All activities (admin view)

**Timeline widget:**

- Displays activities on contact/deal detail pages
- Sorted by date (newest first)

---

### 6.5 Search/Filter Functionality

**Search API Integration:**

**Indexes:**

1. `crm_contacts_index` - Indexes contact fields for fast search
2. `crm_deals_index` - Indexes deal fields
3. `crm_organizations_index` - Indexes organization fields

**Search backend:** Database search (default) or Solr (if configured)

**Indexed fields:**

- Title
- Email
- Phone
- Organization name
- Owner name
- Custom fields

**Views integration:**

- Exposed filters use Search API for autocomplete
- Filters available:
  - Text search (name, email, phone)
  - Taxonomy filters (source, stage, customer type)
  - Date range (closing date, created date)
  - Owner filter (for admins)

**Example:** Search contacts by name in `my_contacts` view

1. User types in exposed filter "Enter name..."
2. Views builds query with condition `title LIKE '%query%'`
3. Query filtered by `field_owner = current_user` (via `crm_query_node_access_alter`)
4. Results displayed in table

---

### 6.6 CSV Import Feature

**Module:** `crm_import_export`

**Import flow:**

```
1. User visits /crm/import
2. Clicks "Import Contacts"
3. Uploads CSV file
4. System validates CSV format
5. User maps CSV columns to Drupal fields
   - CSV "Name" → field_title
   - CSV "Email" → field_email
   - CSV "Company" → field_organization
6. Click "Import"
7. Batch process:
   - Read CSV row by row
   - Create contact node for each row
   - Set field_owner = current user
   - Handle errors (duplicate email, invalid format)
8. Display summary:
   - "50 contacts imported successfully"
   - "2 contacts failed" (with error details)
```

**Forms:**

- `ImportContactsForm` - Contact import
- `ImportDealsForm` - Deal import

**Validation:**

- Required fields check
- Email format validation
- Duplicate detection (optional)
- Taxonomy term matching (creates terms if not exist)

**Alternative:** `crm_import` module uses Feeds module for more advanced import

---

### 6.7 Team-Based Access (Optional Feature)

**Module:** `crm_teams`

**Concept:**

- Users have a `field_team` (entity reference to taxonomy term)
- Users can only see content owned by teammates
- Sales managers can see all team content

**Implementation:**

**Helper function in `crm.module`:**

```php
function _crm_check_same_team($user1_id, $user2_id) {
  $user1 = User::load($user1_id);
  $user2 = User::load($user2_id);

  if (!$user1->hasField('field_team') || !$user2->hasField('field_team')) {
    return FALSE;
  }

  $team1 = $user1->get('field_team')->target_id;
  $team2 = $user2->get('field_team')->target_id;

  return $team1 && $team2 && ($team1 == $team2);
}
```

**Access logic:**

- User A (Team 1) tries to view contact owned by User B (Team 1) → ✅ Allowed
- User A (Team 1) tries to view contact owned by User C (Team 2) → ❌ Forbidden

**Team management:** `/admin/structure/taxonomy/manage/team`

---

## 7. Data Flow

### 7.1 Creating a Contact

**Step-by-step flow:**

```
1. User clicks "Add Contact" button on /crm/my-contacts

2. Frontend (JavaScript):
   - Intercepts click event
   - Sends AJAX GET request to /crm/edit/ajax/create/form?type=contact

3. Backend (AddController::getCreateForm()):
   - Loads default node add form for 'contact' bundle
   - Returns form HTML

4. Frontend:
   - Displays form in modal overlay

5. User fills form:
   - Name: "John Doe"
   - Email: "john@example.com"
   - Phone: "0123456789"
   - Organization: (autocomplete field)

6. User clicks "Save"

7. Frontend:
   - Serializes form data
   - Sends AJAX POST to /crm/edit/ajax/create
   - Body: { type: "contact", title: "John Doe", field_email: "john@example.com", ... }

8. Backend (AddController::ajaxCreate()):
   - Validates input
   - Creates node:
     $node = Node::create([
       'type' => 'contact',
       'title' => 'John Doe',
       'field_email' => 'john@example.com',
       'field_owner' => $current_user_id, // ⭐ Auto-set owner
     ]);
     $node->save();
   - Returns JSON: { success: true, node_id: 123, message: "Contact created" }

9. Frontend:
   - Receives JSON response
   - Closes modal
   - Shows success message (toast notification)
   - Reloads contact list (or inserts new row via AJAX)
```

**Database changes:**

- New row in `node_field_data` (node ID, title, type, owner, status, created)
- New rows in field tables:
  - `node__field_email`
  - `node__field_phone`
  - `node__field_owner`
  - etc.

---

### 7.2 Viewing Deals

**Step-by-step flow:**

```
1. User navigates to /crm/my-deals

2. Drupal routing:
   - Matches route defined in Views (views.view.my_deals page display)
   - Executes Views query

3. Views query builder:
   - Base query: SELECT * FROM node_field_data WHERE type = 'deal'
   - Add filters:
     - status = 1 (published)
   - Join field tables:
     - LEFT JOIN node__field_owner ON node_field_data.nid = node__field_owner.entity_id
     - LEFT JOIN node__field_amount ON ...
     - LEFT JOIN node__field_stage ON ...
     - etc.

4. Query alteration (crm_query_node_access_alter()):
   - Checks current user role
   - If sales_rep:
     - Adds condition: node__field_owner.field_owner_target_id = $current_user_id
   - If administrator/sales_manager:
     - No additional filter (sees all deals)

5. Query execution:
   - Returns list of node IDs matching criteria

6. Views rendering:
   - Loads full node objects
   - Extracts field values
   - Formats fields (amount → "1,000,000 VND", date → "Mar 15, 2026")
   - Builds table HTML

7. Views adds custom field:
   - CrmEditLink plugin generates "Edit | Delete" links

8. Output:
   - Renders views-view-table.html.twig template
   - Outputs HTML table

9. Browser receives response:
   - Displays deal list
   - User sees only their own deals (unless admin)
```

**SQL query (simplified):**

```sql
SELECT
  nfd.nid,
  nfd.title,
  owner.field_owner_target_id,
  amount.field_amount_value,
  stage.field_stage_target_id,
  closing.field_closing_date_value
FROM node_field_data nfd
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
LEFT JOIN node__field_amount amount ON nfd.nid = amount.entity_id
LEFT JOIN node__field_stage stage ON nfd.nid = stage.entity_id
LEFT JOIN node__field_closing_date closing ON nfd.nid = closing.entity_id
WHERE nfd.type = 'deal'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5  -- Current user ID
ORDER BY closing.field_closing_date_value ASC
LIMIT 25 OFFSET 0;
```

---

### 7.3 Filtering Contacts

**Step-by-step flow:**

```
1. User on /crm/my-contacts page

2. Exposed filter form displayed above table:
   - [Search by name: _______]
   - [Source: All / Website / Referral / Cold Call]
   - [Customer Type: All / Hot / Warm / Cold]
   - [Search] [Reset]

3. User enters "John" in search box and selects Source = "Website"

4. User clicks "Search"

5. Frontend:
   - Form submits to /crm/my-contacts?name=John&source=1

6. Views processes exposed filters:
   - Adds WHERE condition: title LIKE '%John%'
   - Adds WHERE condition: field_source_target_id = 1 (Website term ID)

7. Query execution:
   - Rebuilt query with additional filters
   - Ownership filter still applies (field_owner = current user)

8. Views renders updated results:
   - Only contacts matching "John" AND Source = "Website" AND Owner = Current User

9. Output:
   - Updated table displayed
   - Filter form retains values ("John" still in input, "Website" still selected)
```

**URL structure:**

- No filters: `/crm/my-contacts`
- With filters: `/crm/my-contacts?title=John&source=1&customer_type=2`

**Reset button:**

- Redirects to `/crm/my-contacts` (no query params)

---

### 7.4 Editing Organizations

**Inline edit flow:**

```
1. User on /crm/my-organizations page

2. User hovers over "Acme Corp" row
   - "Edit" link appears

3. User clicks "Edit"

4. Frontend:
   - Prevents default link behavior
   - Sends AJAX GET to /crm/edit/modal/form?node_id=45&type=organization

5. Backend (ModalEditController::getEditForm()):
   - Loads node 45
   - Checks access (user must be owner or admin)
   - Loads node edit form
   - Returns form HTML

6. Frontend:
   - Displays form in modal overlay
   - Pre-fills current values:
     - Company Name: "Acme Corp"
     - Industry: "Technology"
     - Employees: "150"
     - etc.

7. User changes "Employees" from 150 to 200

8. User clicks "Save"

9. Frontend:
   - Serializes form
   - Sends AJAX POST to /crm/edit/ajax/save
   - Body: { node_id: 45, field_employees_count: 200, field_industry: 3, ... }

10. Backend (InlineEditController::ajaxSave()):
    - Validates CSRF token
    - Validates user has permission
    - Loads node 45
    - Updates fields:
      $node->set('field_employees_count', 200);
      $node->save();
    - Returns JSON: { success: true, message: "Organization updated" }

11. Frontend:
    - Receives response
    - Closes modal
    - Updates table row in-place (200 employees shown without full page reload)
    - Shows success message
```

**Advantage:** No full page reload, instant updates

---

## 8. Permissions Model

### 8.1 User Roles

**Defined roles:**

| Role                 | Machine Name                                  | Description                           |
| -------------------- | --------------------------------------------- | ------------------------------------- |
| Administrator        | `administrator`                               | Full access to everything             |
| Sales Manager        | `sales_manager`                               | View all team data, manage users      |
| Sales Representative | `sales_rep`                                   | View/edit own data + same team data   |
| Customer/Client      | (Optional) External users with limited access |
| Anonymous            | `anonymous`                                   | Public visitors (see login page only) |
| Authenticated User   | `authenticated`                               | Base authenticated user               |

### 8.2 Permission Strategy

**Drupal core permissions:**

1. **Node permissions** (per content type):
   - `create contact content`
   - `edit own contact content`
   - `edit any contact content`
   - `delete own contact content`
   - `delete any contact content`
   - (Same pattern for: deal, organization, activity)

2. **CRM-specific permissions:**
   - `access content` - Required for all CRM pages
   - `bypass crm team access` - See all team data (for managers)
   - `administer crm import export` - Access import/export features
   - `access user profiles` - View other users' profiles

**Permission assignment:**

| Permission         | Administrator | Sales Manager | Sales Rep |
| ------------------ | ------------- | ------------- | --------- |
| Create contact     | ✅            | ✅            | ✅        |
| Edit own contact   | ✅            | ✅            | ✅        |
| Edit ANY contact   | ✅            | ✅            | ❌        |
| Delete ANY contact | ✅            | ❌            | ❌        |
| Bypass team access | ✅            | ✅            | ❌        |
| Import/Export      | ✅            | ✅            | ❌        |
| Administer users   | ✅            | ✅            | ❌        |

### 8.3 Access Control Implementation

**Three layers of access control:**

#### Layer 1: Drupal Core Permissions

```php
// In routing.yml
requirements:
  _permission: "create contact content"
```

Checks if user has the specified permission before allowing route access.

#### Layer 2: View Access Plugins

```yaml
# In views.view.all_deals.yml
access:
  type: role
  options:
    role:
      administrator: administrator
      sales_manager: sales_manager
```

Restricts entire view to specific roles. Used for "All" views (admins/managers only).

#### Layer 3: Custom Access Hook (Most Important!)

```php
// In crm.module
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  // Ownership-based access control
  $owner_id = $node->get('field_owner')->target_id;

  if ($owner_id == $account->id()) {
    return AccessResult::allowed(); // User is owner
  }

  if (_crm_check_same_team($account->id(), $owner_id)) {
    return AccessResult::allowed(); // Same team
  }

  return AccessResult::forbidden(); // Not owner, not same team
}
```

**When it triggers:**

- User tries to view a node: `$op = 'view'`
- User tries to edit a node: `$op = 'update'`
- User tries to delete a node: `$op = 'delete'`

**Decision logic:**

1. Is user administrator? → Allow
2. Is user sales manager? → Allow
3. Is user the owner? → Allow
4. Is user on the same team? → Allow
5. Otherwise → Deny

### 8.4 Data Isolation

**Query filter (automatic):**

```php
function crm_query_node_access_alter($query) {
  // Automatically filters all node queries

  if (user is sales_rep) {
    $query->condition('field_owner.target_id', $current_user_id);
    // OR
    $query->condition('field_owner.target_id', 'IN', $teammate_ids);
  }
}
```

**Result:** Sales reps NEVER see other users' data in:

- Views lists
- Search results
- Entity queries
- Autocomplete results

**Exceptions:**

- Administrators see everything (bypass hook)
- Sales managers see all team data (bypass hook)

### 8.5 Security Considerations

**Potential vulnerabilities:**

1. **Direct node access:**
   - Risk: User guesses URL `/node/123` and tries to access
   - Protection: `crm_node_access()` hook checks ownership

2. **API endpoints:**
   - Risk: User sends AJAX request to `/crm/edit/ajax/save` with arbitrary node_id
   - Protection: Controller checks ownership before saving:
     ```php
     $node = Node::load($node_id);
     if ($node->get('field_owner')->target_id != $current_user_id) {
       return new JsonResponse(['error' => 'Access denied'], 403);
     }
     ```

3. **Views bypass:**
   - Risk: User modifies exposed filter URL to see other users' data
   - Protection: `crm_query_node_access_alter()` enforces ownership filter

4. **CSRF attacks:**
   - Risk: Malicious site sends fake form submission
   - Protection: Drupal's CSRF token validation on all forms

5. **SQL injection:**
   - Risk: User input in exposed filter
   - Protection: Drupal's query builder automatically escapes input

**Recommendations:**

- ✅ Ownership checks implemented
- ✅ Query filters applied globally
- ✅ CSRF protection enabled
- ⚠️ Consider adding audit logging for sensitive operations
- ⚠️ Add rate limiting on login/import endpoints

---

## 9. Improvement Suggestions

### 9.1 Architectural Issues

#### Issue 1: No Custom Entities

**Current:** All CRM data stored as nodes (content types)

**Problem:**

- Nodes are designed for content, not business data
- Extra overhead (revisions, translations, etc.)
- Limited query performance on large datasets
- Field storage in separate tables (JOIN overhead)

**Recommendation:**

Create custom entities for Contacts, Deals, Organizations:

```php
/**
 * @ContentEntityType(
 *   id = "crm_contact",
 *   label = @Translation("Contact"),
 *   base_table = "crm_contact",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name"
 *   }
 * )
 */
class Contact extends ContentEntityBase { ... }
```

**Benefits:**

- Single table storage (faster queries)
- No unnecessary node overhead
- Cleaner data model
- Better performance at scale (10,000+ records)

**Effort:** High (requires migration)

#### Issue 2: Inline CSS in Controller Output

**Current:** Dashboard and other pages have inline styles in PHP

**Problem:**

- Violates separation of concerns
- Hard to maintain
- Content Security Policy (CSP) issues
- No caching benefits

**Recommendation:**

Extract CSS to separate files, use Twig templates:

```php
// Instead of:
return ['#markup' => '<div style="...">...</div>'];

// Use:
return [
  '#theme' => 'crm_dashboard',
  '#kpis' => $kpis,
  '#attached' => ['library' => ['crm_dashboard/dashboard']],
];
```

Create Twig template:

```twig
{# templates/crm-dashboard.html.twig #}
<div class="crm-dashboard">
  {% for kpi in kpis %}
    <div class="kpi-card">{{ kpi.value }}</div>
  {% endfor %}
</div>
```

**Benefits:**

- Cleaner code
- Reusable templates
- Easier theming
- Better caching

**Effort:** Medium

#### Issue 3: Mixed Access Control Logic

**Current:** Ownership checks scattered across:

- `crm_node_access()` hook
- View filters
- Controller checks
- Query alter hooks

**Problem:**

- Inconsistent implementation
- Easy to forget checks in new features
- Hard to audit security

**Recommendation:**

Centralize access control in a service:

```php
class CrmAccessControlService {
  public function canView(NodeInterface $node, AccountInterface $account) {
    // Single source of truth for access logic
  }

  public function canEdit(NodeInterface $node, AccountInterface $account) { ... }

  public function filterQueryByOwnership(Query $query) { ... }
}
```

Use in all controllers/hooks:

```php
$access_service = \Drupal::service('crm.access_control');
if (!$access_service->canEdit($node, $current_user)) {
  throw new AccessDeniedException();
}
```

**Benefits:**

- Single source of truth
- Easier testing
- Consistent behavior
- Easier security audits

**Effort:** Medium

---

### 9.2 Performance Optimizations

#### Issue 4: N+1 Query Problem in Views

**Current:** Views loads nodes one by one for entity reference fields (organization, owner)

**Problem:**

- For 50 contacts, might execute 100+ queries
- Slow page load times

**Recommendation:**

Enable Views caching:

```yaml
# In view configuration
cache:
  type: time
  options:
    results_lifespan: 300 # 5 minutes
    output_lifespan: 300
```

Or use entity preloading in custom code:

```php
$node_ids = [1, 2, 3, ...];
$nodes = Node::loadMultiple($node_ids);  // Single query
```

**Benefits:**

- Faster page loads
- Reduced database load

**Effort:** Low

#### Issue 5: No Search Index for Large Datasets

**Current:** Search API configured but might not be active

**Recommendation:**

Ensure Search API is indexing content:

```bash
drush search-api:index crm_contacts_index
drush search-api:index crm_deals_index
```

Configure Solr backend for production (instead of database):

- Install Solr
- Configure Search API Solr module
- Reindex content

**Benefits:**

- Fast full-text search
- Autocomplete performance
- Scalability to millions of records

**Effort:** Medium (Solr setup)

---

### 9.3 Code Quality

#### Issue 6: Long Controller Methods

**Current:** `DashboardController::view()` is 1440 lines

**Problem:**

- Hard to read and maintain
- Difficult to test
- Violates Single Responsibility Principle

**Recommendation:**

Refactor into services:

```php
class DashboardController extends ControllerBase {
  public function view() {
    $kpis = $this->kpiService->getKpis();
    $chart_data = $this->chartService->getPipelineData();

    return [
      '#theme' => 'crm_dashboard',
      '#kpis' => $kpis,
      '#chart_data' => $chart_data,
    ];
  }
}

class CrmKpiService {
  public function getKpis() {
    return [
      'contacts' => $this->getContactCount(),
      'deals' => $this->getDealCount(),
      ...
    ];
  }

  private function getContactCount() { ... }
}
```

**Benefits:**

- Testable services
- Reusable logic
- Cleaner controllers

**Effort:** Low-Medium

#### Issue 7: No Automated Tests

**Current:** No PHPUnit or Functional tests

**Recommendation:**

Add tests for critical features:

```php
// tests/src/Functional/ContactAccessTest.php
class ContactAccessTest extends BrowserTestBase {
  public function testSalesRepCannotViewOtherContacts() {
    $rep1 = $this->createUser([], 'rep1', FALSE, ['roles' => ['sales_rep']]);
    $rep2 = $this->createUser([], 'rep2', FALSE, ['roles' => ['sales_rep']]);

    $contact = Node::create([
      'type' => 'contact',
      'title' => 'Test Contact',
      'field_owner' => $rep1->id(),
    ]);
    $contact->save();

    $this->drupalLogin($rep2);
    $this->drupalGet('/node/' . $contact->id());
    $this->assertSession()->statusCodeEquals(403);  // Access denied
  }
}
```

**Benefits:**

- Prevent regressions
- Confidence in deployments
- Documentation of expected behavior

**Effort:** High (initial setup), Low (maintenance)

---

### 9.4 Feature Enhancements

#### Issue 8: No Real-time Notifications

**Current:** `crm_notifications` module exists but implementation unclear

**Recommendation:**

Implement real-time notifications using Mercure or Pusher:

- User A assigns deal to User B
- User B sees notification instantly (no page refresh)

**Effort:** High

#### Issue 9: Limited Workflow Automation

**Current:** `crm_workflow` module exists but seems minimal

**Recommendation:**

Add workflow rules:

- When deal stage changes to "Won" → Create activity "Send welcome email"
- When contact hasn't been contacted in 30 days → Create reminder activity
- When deal is idle for 14 days → Notify manager

Use Rules module or custom event subscribers.

**Effort:** Medium

#### Issue 10: No Mobile App

**Current:** Web-only interface

**Recommendation:**

Build mobile app with Drupal as headless backend:

- JSON:API or GraphQL module
- React Native or Flutter app
- Offline support for field sales

**Effort:** Very High (separate project)

---

### 9.5 Security Hardening

#### Issue 11: Team Access is Optional

**Current:** Team-based access only works if `field_team` exists on users

**Recommendation:**

Make team assignment mandatory for sales reps:

```php
// In user form validation
if (!$user->get('field_team')->isEmpty() && $user->hasRole('sales_rep')) {
  $form_state->setError($form['field_team'], 'Sales reps must be assigned to a team');
}
```

**Benefits:**

- Enforced data isolation
- Clear team structure

**Effort:** Low

#### Issue 12: No Audit Trail

**Current:** No logging of who changed what

**Recommendation:**

Install and configure:

- **Drupal Core**: Already logs some events (config changes, login attempts)
- **Activity Log module**: Logs node create/update/delete
- **Custom logging**: Log sensitive operations

```php
\Drupal::logger('crm')->info('User @user deleted contact @contact', [
  '@user' => $current_user->getDisplayName(),
  '@contact' => $node->label(),
]);
```

**Benefits:**

- Compliance (GDPR, audit requirements)
- Security incident investigation
- User activity tracking

**Effort:** Low-Medium

---

## Summary

### Architecture Overview

Open CRM is a **well-structured Drupal 10/11 application** with a clear modular architecture. The system uses:

- **Node-based data model** (4 content types: Contact, Deal, Organization, Activity)
- **16 custom modules** providing specialized functionality
- **Views-driven listings** with role-based filters
- **Custom controllers** for complex pages (dashboard, kanban, import)
- **Ownership-based access control** with automatic data filtering
- **AJAX-powered editing** for better UX

### Strengths

✅ **Modular design** - Features cleanly separated into modules  
✅ **Security-conscious** - Multiple layers of access control  
✅ **User-friendly** - Inline editing, drag-and-drop kanban, quick-add buttons  
✅ **Role-based UX** - Admins see different views than sales reps  
✅ **Extensible** - Easy to add new fields, views, and features

### Weaknesses

⚠️ **Scalability concerns** - Node system may not scale to 100,000+ records  
⚠️ **Code quality** - Some long controller methods, inline CSS  
⚠️ **Lack of tests** - No automated test coverage  
⚠️ **Mixed access control** - Logic scattered across multiple places

### Maintainability Score: 7/10

The codebase is generally well-organized, but would benefit from refactoring controllers, extracting services, and adding tests.

### Scalability Score: 6/10

Current design works well for small-to-medium datasets (up to ~10,000 records per type). For larger scale, consider custom entities and database optimization.

### Recommendation

**For immediate production use:** System is ready with minor CSS/caching improvements.

**For long-term sustainability:** Invest in test coverage, refactor controllers, and monitor performance as data grows.

---

## Quick Reference

### Key URLs

| Page         | URL                 | Access        |
| ------------ | ------------------- | ------------- |
| Homepage     | `/`                 | Everyone      |
| Login        | `/login`            | Anonymous     |
| Dashboard    | `/crm/dashboard`    | Authenticated |
| My Contacts  | `/crm/my-contacts`  | Sales Rep     |
| All Contacts | `/crm/all-contacts` | Admin/Manager |
| Pipeline     | `/crm/pipeline`     | Sales Rep     |
| Import       | `/crm/import`       | Authenticated |
| Add Contact  | `/crm/add/contact`  | Authenticated |

### Key Modules

| Module              | Purpose        | Critical? |
| ------------------- | -------------- | --------- |
| `crm`               | Access control | ⭐ Yes    |
| `crm_dashboard`     | Dashboard      | ⭐ Yes    |
| `crm_edit`          | Inline editing | ⭐ Yes    |
| `crm_actions`       | Navigation     | ⭐ Yes    |
| `crm_kanban`        | Pipeline board | Medium    |
| `crm_import_export` | Import/export  | Medium    |
| `crm_login`         | Custom login   | Low       |

### Key Files

| File                                                   | Purpose              |
| ------------------------------------------------------ | -------------------- |
| `crm/crm.module`                                       | Access control hooks |
| `crm_dashboard/src/Controller/DashboardController.php` | Dashboard logic      |
| `crm_edit/src/Controller/InlineEditController.php`     | AJAX editing         |
| `config/views.view.my_contacts.yml`                    | Contact list view    |
| `crm_actions/crm_actions.module`                       | Global navigation    |

---

**End of Documentation**

For questions or clarifications, consult the inline code comments or Drupal documentation at https://www.drupal.org/docs.
