# Open CRM - Complete Technical Architecture Documentation

**Document Version:** 2.0  
**Platform:** Drupal 10/11  
**Date:** March 6, 2026  
**For:** Developer Onboarding & System Understanding  
**Audience:** Backend Developers, Frontend Developers, DevOps Engineers

---

## Document Purpose

This comprehensive technical documentation is designed to enable a new developer to:

✅ Completely understand the entire system architecture  
✅ Know exactly where every feature is implemented  
✅ Understand how data flows through the system  
✅ Know which files control which pages  
✅ Understand how CSS/JS affects the UI  
✅ Debug issues quickly by knowing file locations  
✅ Extend the system following existing patterns

---

## Table of Contents

### PART 1: SYSTEM FUNDAMENTALS

1. [System Architecture Overview](#1-system-architecture-overview)
2. [Complete Project Structure](#2-complete-project-structure)
3. [Data Model & Entity Architecture](#3-data-model--entity-architecture)

### PART 2: MODULE DEEP DIVE

4. [Core Module (crm) - Complete Analysis](#4-core-module-crm---complete-analysis)
5. [Dashboard Module - File by File](#5-dashboard-module---file-by-file)
6. [Edit Module - Inline Editing System](#6-edit-module---inline-editing-system)
7. [Kanban Module - Pipeline Board](#7-kanban-module---pipeline-board)
8. [Import/Export Module](#8-importexport-module)
9. [All Other Custom Modules](#9-all-other-custom-modules)

### PART 3: PAGE ARCHITECTURE

10. [Complete Page-by-Page Breakdown](#10-complete-page-by-page-breakdown)

### PART 4: VIEWS SYSTEM

11. [Views Configuration Deep Dive](#11-views-configuration-deep-dive)

### PART 5: UI SYSTEM

12. [CSS Architecture & UI System](#12-css-architecture--ui-system)
13. [JavaScript Features Complete Guide](#13-javascript-features-complete-guide)

### PART 6: DATA FLOW

14. [Complete Data Flow Scenarios](#14-complete-data-flow-scenarios)

### PART 7: SECURITY

15. [Permission System Deep Dive](#15-permission-system-deep-dive)

### PART 8: REFERENCE

16. [Feature Location Map](#16-feature-location-map)
17. [Improvement Suggestions](#17-improvement-suggestions)

---

# PART 1: SYSTEM FUNDAMENTALS

## 1. System Architecture Overview

### 1.1 Architectural Philosophy

Open CRM is built on **Drupal 10/11** using a **node-centric architecture**. Instead of creating custom entities, the system leverages Drupal's powerful node system with custom fields to represent CRM data.

**Core Design Decisions:**

```
┌─────────────────────────────────────────────────────────────┐
│                   ARCHITECTURAL LAYERS                       │
├─────────────────────────────────────────────────────────────┤
│  LAYER 1: Presentation                                       │
│  - Twig templates (minimal usage)                            │
│  - Controller-generated HTML (heavy usage)                   │
│  - Views-generated tables                                    │
│  - Custom CSS/JS for interactions                            │
├─────────────────────────────────────────────────────────────┤
│  LAYER 2: Business Logic                                     │
│  - 16 Custom Modules                                         │
│  - Controllers (Page rendering, AJAX handling)               │
│  - Hooks (Access control, form alteration)                   │
│  - Service classes (minimal - opportunities for improvement) │
├─────────────────────────────────────────────────────────────┤
│  LAYER 3: Data Access                                        │
│  - Drupal Entity Query API                                   │
│  - Views (declarative queries)                               │
│  - Direct database queries (rare, avoid)                     │
├─────────────────────────────────────────────────────────────┤
│  LAYER 4: Data Storage                                       │
│  - Drupal Nodes (Contact, Deal, Organization, Activity)      │
│  - Custom Fields (30+fields defined)                         │
│  - Taxonomy Terms (Pipeline stages, sources, etc.)           │
│  - User entities with custom fields                          │
├─────────────────────────────────────────────────────────────┤
│  LAYER 5: Infrastructure                                     │
│  - MariaDB 11.8 (Database)                                   │
│  - PHP 8.4 (Runtime)                                         │
│  - Nginx-FPM (Web server)                                    │
│  - DDEV (Development environment)                            │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Why Node-Based Architecture?

**Advantages:**

- ✅ Rapid development (use Drupal's existing CRUD system)
- ✅ Built-in revision system
- ✅ Automatic Views integration
- ✅ Built-in access control hooks
- ✅ Easy to add fields via UI

**Disadvantages:**

- ⚠️ Performance overhead vs custom entities
- ⚠️ Extra data in database (published status, revisions, etc.)
- ⚠️ JOIN overhead when querying fields
- ⚠️ Not ideal for > 100,000 records per type

### 1.3 CRM Entity Types

The system uses **4 core Drupal content types** to represent CRM entities:

| Content Type | Machine Name   | Purpose                                         | Owner Field            |
| ------------ | -------------- | ----------------------------------------------- | ---------------------- |
| Contact      | `contact`      | Individual people (leads, customers, prospects) | `field_owner`          |
| Deal         | `deal`         | Sales opportunities with pipeline stages        | `field_owner`          |
| Organization | `organization` | Companies/organizations                         | `field_assigned_staff` |
| Activity     | `activity`     | Tasks, calls, meetings, emails, notes           | `field_assigned_to`    |

**Why Different Owner Fields?**

```php
// Determined by business logic:
// Contact & Deal: field_owner (Owner owns the relationship)
// Organization: field_assigned_staff (Staff member assigned to account)
// Activity: field_assigned_to (Activity assigned to someone to complete)
```

This distinction is critical for access control (explained in section 15).

### 1.4 Module Ecosystem

**16 Custom Modules** extend Drupal's core functionality:

```
Modules by Function:

ACCESS CONTROL:
├── crm                   # Core access control & ownership tracking

UI/PRESENTATION:
├── crm_dashboard         # KPI cards, charts, metrics
├── crm_actions           # Global navigation bar
├── crm_navigation        # Navigation helpers, back buttons
├── crm_login             # Custom branded login page
├── crm_register          # Custom registration page

EDITING:
├── crm_edit              # Inline editing, modal forms, AJAX save/delete
├── crm_quickadd          # Floating quick-add button
├── crm_kanban            # Drag-and-drop pipeline board

DATA MANAGEMENT:
├── crm_import            # CSV import via Feeds module
├── crm_import_export     # Custom CSV import/export

FEATURES:
├── crm_activity_log      # Activity timeline widget
├── crm_contact360        # Enhanced contact detail view
├── crm_teams             # Team-based access control
├── crm_notifications     # Email notifications
├── crm_workflow          # Workflow automation
```

### 1.5 Request Flow

Understanding how a typical page request flows through the system:

```
USER REQUEST FLOW:

1. User navigates to /crm/my-contacts
   ↓
2. Drupal Routing System matches route
   - Checks routing.yml files
   - Finds: views.view.my_contacts (Views-generated route)
   ↓
3. Views executes query
   - Base query: SELECT FROM node_field_data WHERE type='contact'
   - Adds ownership filter via crm_query_node_access_alter()
   - Joins field tables (field_email, field_phone, etc.)
   ↓
4. Query Alteration (crm.module)
   - hook_query_node_access_alter() adds:
     WHERE field_owner = current_user_id
   ↓
5. Views loads results
   - Loads node objects
   - Extracts field values
   - Formats data (links, dates, etc.)
   ↓
6. Views renders table
   - Applies template (table template)
   - Adds custom fields (CrmEditLink plugin)
   - Attaches CSS/JS libraries
   ↓
7. Page assembled
   - Admin theme wrapper (Claro/Gin)
   - Global navigation injected (crm_actions via hook_page_top)
   - CSS aggregated and loaded
   - JS loaded and executed
   ↓
8. Response sent to browser
   - HTML rendered
   - User sees contact list
```

**For AJAX Requests (inline edit):**

```
AJAX REQUEST FLOW:

1. User clicks "Edit" button on contact row
   ↓
2. JavaScript intercepts click (inline-edit.js)
   - Prevents default link action
   - Calls: CRMInlineEdit.openModal(nid, 'contact')
   ↓
3. AJAX GET request: /crm/edit/modal/form?nid=123&type=contact
   ↓
4. Drupal routes to: ModalEditController::getEditForm()
   - Loads node 123
   - Checks access (owner check)
   - Builds edit form HTML
   - Returns JSON: {success: true, html: "..."}
   ↓
5. JavaScript receives response
   - Injects HTML into modal overlay
   - Initializes Lucide icons
   - Sets up form handlers
   ↓
6. User edits fields and clicks "Save"
   ↓
7. JavaScript serializes form
   - Prevents default submit
   - Collects form data
   - AJAX POST to: /crm/edit/ajax/save
   ↓
8. InlineEditController::ajaxSave()
   - Validates input
   - Loads node
   - Updates field values: $node->set('field_email', $email)
   - Saves: $node->save()
   - Returns JSON: {success: true, message: "Saved"}
   ↓
9. JavaScript receives success
   - Shows toast notification
   - Closes modal
   - Reloads page to show changes
```

---

## 2. Complete Project Structure

### 2.1 Root Directory

```
open_crm/
│
├── composer.json                      # PHP dependency management
├── composer.lock                      # Locked versions
│
├── web/                              # Document root (nginx points here)
│   ├── index.php                     # Entry point
│   ├── .htaccess                     # Apache config (not used in nginx setup)
│   │
│   ├── core/                         # Drupal core files (DO NOT MODIFY)
│   │   ├── lib/                      # Core PHP classes
│   │   ├── modules/                  # Core modules (node, user, views, etc.)
│   │   └── ...
│   │
│   ├── modules/                      # All modules
│   │   ├── contrib/                  # Downloaded contrib modules
│   │   │   ├── admin_toolbar/        # Admin UI improvements
│   │   │   ├── pathauto/             # Automatic URL aliases
│   │   │   ├── token/                # Token system for patterns
│   │   │   ├── views/                # Views module (if not in core)
│   │   │   └── ...
│   │   │
│   │   └── custom/                   # Custom CRM modules ⭐⭐⭐
│   │       ├── crm/
│   │       ├── crm_dashboard/
│   │       ├── crm_edit/
│   │       └── ... (16 total)
│   │
│   ├── themes/                       # Themes
│   │   ├── contrib/                  # Downloaded themes
│   │   │   ├── claro/                # Admin theme (modern)
│   │   │   └── gin/                  # Alternative admin theme
│   │   └── custom/                   # Custom themes (if any)
│   │
│   ├── sites/                        # Multi-site configuration
│   │   └── default/
│   │       ├── settings.php          # Database config, caching, etc.
│   │       ├── services.yml          # Service configuration
│   │       └── files/                # Uploaded files
│   │           ├── styles/           # Image style derivatives
│   │           ├── csv/              # Uploaded CSV files
│   │           └── ...
│   │
│   └── libraries/                    # External JS/CSS libraries
│
├── config/                           # Configuration management ⭐⭐⭐
│   ├── views.view.my_contacts.yml
│   ├── views.view.my_deals.yml
│   ├── search_api.index.*.yml
│   └── ...
│
├── vendor/                           # Composer packages
│   ├── autoload.php                  # Composer autoloader
│   ├── drupal/                       # Drupal dependencies
│   ├── symfony/                      # Symfony components
│   └── ...
│
├── scripts/                          # Utility scripts ⭐
│   ├── create_content_types.sh       # Setup script
│   ├── create_sample_data.sh         # Generate test data
│   ├── refactor_dashboard_banner.php # UI modification script
│   └── ...
│
├── fixtures/                         # Sample data
│   ├── development/                  # Dev environment data
│   └── production/                   # Production-ready samples
│
├── docs/                             # Documentation
│
├── backups/                          # Database backups
│
└── README.md
```

### 2.2 Custom Module Structure (Deep Dive)

Each custom module follows Drupal's standard structure. Let's examine `crm_edit` as the canonical example:

```
web/modules/custom/crm_edit/
│
├── crm_edit.info.yml                 # Module metadata ⭐
│   # Defines: name, description, dependencies, version requirements
│
├── crm_edit.routing.yml              # Route definitions ⭐⭐⭐
│   # Maps URLs to controllers
│   # Example: /crm/edit/contact/{node} → InlineEditController::editContact()
│
├── crm_edit.module                   # Hook implementations ⭐
│   # Implements: hook_help(), hook_theme(), hook_form_alter()
│   # Used for: Integrating with Drupal's systems
│
├── crm_edit.libraries.yml            # CSS/JS assets ⭐
│   # Defines: inline_edit library (CSS + JS)
│   # Loaded via: '#attached' => ['library' => ['crm_edit/inline_edit']]
│
├── crm_edit.permissions.yml          # Custom permissions (if any)
│   # Would define: edit crm content, delete crm content
│
├── src/                              # PHP classes (PSR-4 autoloading)
│   │
│   ├── Controller/                   # Page controllers ⭐⭐⭐
│   │   ├── InlineEditController.php  # Handles edit pages & AJAX
│   │   ├── ModalEditController.php   # Returns modal HTML
│   │   ├── AddController.php         # Handles add pages & quick-add
│   │   └── DeleteController.php      # Handles delete confirmation & AJAX
│   │
│   ├── Form/                         # Drupal forms (not heavily used)
│   │   └── ExampleForm.php
│   │
│   ├── Plugin/                       # Drupal plugins
│   │   └── views/
│   │       └── field/
│   │           └── CrmEditLink.php   # Custom Views field ⭐⭐⭐
│   │               # Adds "Edit | Delete" links to Views tables
│   │
│   └── Service/                      # Business logic services
│       └── (could add: EditService.php for reusable logic)
│
├── templates/                        # Twig templates (if needed)
│   └── crm-edit-modal.html.twig
│
├── css/                              # Stylesheets ⭐
│   └── inline-edit.css
│       # Loaded by: crm_edit.libraries.yml
│       # Styles: .crm-modal-overlay, .crm-modal-container, etc.
│
├── js/                               # JavaScript ⭐⭐⭐
│   └── inline-edit.js
│       # Loaded by: crm_edit.libraries.yml
│       # Defines: window.CRMInlineEdit object
│       # Functions: openModal(), closeModal(), saveModal()
│
└── README.md                         # Module documentation
```

**Key File Interactions:**

```
User Action Flow Through Module Files:

1. User visits /crm/my-contacts
   → Views loads | CrmEditLink.php generates "Edit" button HTML

2. User clicks "Edit" button
   → inline-edit.js intercepts click
   → Calls CRMInlineEdit.openModal(123, 'contact')

3. AJAX request to /crm/edit/modal/form?nid=123
   → crm_edit.routing.yml routes to →
   → ModalEditController::getEditForm()
   → Returns JSON with HTML

4. Modal displayed
   → inline-edit.css styles the modal
   → inline-edit.js handles interactions

5. User saves form
   → inline-edit.js calls saveModal()
   → AJAX POST to /crm/edit/ajax/save
   → InlineEditController::ajaxSave()
   → Node updated in database

6. Success response
   → inline-edit.js shows notification
   → Page reloaded
```

### 2.3 Configuration Directory

The `/config` directory contains **exported Views and Search API configurations**:

```
config/
├── views.view.my_contacts.yml        # Contact list view
├── views.view.my_deals.yml           # Deal list view
├── views.view.my_activities.yml      # Activity list view
├── views.view.my_organizations.yml   # Organization list view
├── views.view.all_contacts.yml       # Admin: all contacts
├── views.view.all_deals.yml          # Admin: all deals
├── views.view.all_activities.yml     # Admin: all activities
├── views.view.all_organizations.yml  # Admin: all organizations
├── views.view.contacts.yml           # Legacy view
│
├── search_api.index.crm_contacts_index.yml      # Search index for contacts
├── search_api.index.crm_deals_index.yml         # Search index for deals
└── search_api.index.crm_organizations_index.yml # Search index for orgs
```

**Important:** These are **static configuration files**. They are:

1. Exported via: `drush config:export` or `drush cex`
2. Imported via: `drush config:import` or `drush cim`
3. Not automatically loaded - require import
4. Version controlled (committed to git)

**When to use config management:**

- Moving Views from dev to production
- Sharing configuration across team members
- Backing up complex Views configurations

---

## 3. Data Model & Entity Architecture

### 3.1 Content Type: Contact

**Purpose:** Represents individual people (leads, prospects, customers)

**Fields Breakdown:**

| Field Label    | Machine Name           | Type                                      | Storage                      | Cardinality | Required | Description                   |
| -------------- | ---------------------- | ----------------------------------------- | ---------------------------- | ----------- | -------- | ----------------------------- |
| Name           | `title`                | Text                                      | node_field_data.title        | 1           | ✅ Yes   | Contact's full name           |
| Email          | `field_email`          | Email                                     | node\_\_field_email          | 1           | ❌ No    | Primary email address         |
| Phone          | `field_phone`          | Telephone                                 | node\_\_field_phone          | 1           | ❌ No    | Primary phone number          |
| Organization   | `field_organization`   | Entity Reference (node:organization)      | node\_\_field_organization   | 1           | ❌ No    | Company the contact works for |
| Position       | `field_position`       | Text (plain)                              | node\_\_field_position       | 1           | ❌ No    | Job title                     |
| Owner          | `field_owner`          | Entity Reference (user)                   | node\_\_field_owner          | 1           | ✅ Yes   | User who owns this contact ⭐ |
| Source         | `field_source`         | Entity Reference (taxonomy:lead_source)   | node\_\_field_source         | 1           | ❌ No    | How we found this contact     |
| Customer Type  | `field_customer_type`  | Entity Reference (taxonomy:customer_type) | node\_\_field_customer_type  | 1           | ❌ No    | Hot/Warm/Cold classification  |
| Avatar         | `field_avatar`         | Image                                     | node\_\_field_avatar         | 1           | ❌ No    | Profile picture               |
| LinkedIn       | `field_linkedin`       | Link                                      | node\_\_field_linkedin       | 1           | ❌ No    | LinkedIn profile URL          |
| Notes          | `field_notes`          | Text (long, formatted)                    | node\_\_field_notes          | 1           | ❌ No    | Internal notes about contact  |
| Last Contacted | `field_last_contacted` | Date                                      | node\_\_field_last_contacted | 1           | ❌ No    | Last interaction date         |

**Database Tables Involved:**

```sql
-- Primary node data
node_field_data:
  - nid (Node ID - primary key)
  - type = 'contact'
  - title (Name)
  - uid (Author - Drupal user who created it)
  - status (Published = 1, Unpublished = 0)
  - created (timestamp)
  - changed (timestamp)

-- Field storage (one table per field)
node__field_email:
  - entity_id (references node_field_data.nid)
  - field_email_value (email string)

node__field_phone:
  - entity_id
  - field_phone_value (phone string)

node__field_owner:
  - entity_id
  - field_owner_target_id (references users.uid) ⭐

node__field_organization:
  - entity_id
  - field_organization_target_id (references node_field_data.nid where type='organization')

... (etc for each field)
```

**SQL Query Example (how Views loads contacts):**

```sql
SELECT
  nfd.nid,
  nfd.title AS name,
  email.field_email_value AS email,
  phone.field_phone_value AS phone,
  owner.field_owner_target_id AS owner_id,
  org_node.title AS organization_name
FROM node_field_data nfd
LEFT JOIN node__field_email email
  ON nfd.nid = email.entity_id
LEFT JOIN node__field_phone phone
  ON nfd.nid = phone.entity_id
LEFT JOIN node__field_owner owner
  ON nfd.nid = owner.entity_id
LEFT JOIN node__field_organization org_ref
  ON nfd.nid = org_ref.entity_id
LEFT JOIN node_field_data org_node
  ON org_ref.field_organization_target_id = org_node.nid
WHERE nfd.type = 'contact'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5  -- Current user filter
ORDER BY nfd.changed DESC
LIMIT 25;
```

### 3.2 Content Type: Deal

**Purpose:** Represents sales opportunities in the pipeline

**Fields Breakdown:**

| Field Label         | Machine Name                | Type                                       | Cardinality | Required | Description                       |
| ------------------- | --------------------------- | ------------------------------------------ | ----------- | -------- | --------------------------------- |
| Deal Name           | `title`                     | Text                                       | 1           | ✅ Yes   | Name of the opportunity           |
| Amount              | `field_amount`              | Decimal (15,2)                             | 1           | ❌ No    | Deal value in VND                 |
| Stage               | `field_stage`               | Entity Reference (taxonomy:pipeline_stage) | 1           | ✅ Yes   | Current pipeline stage ⭐         |
| Probability         | `field_probability`         | Integer (0-100)                            | 1           | ❌ No    | Win probability percentage        |
| Closing Date        | `field_closing_date`        | Date                                       | 1           | ❌ No    | Expected close date               |
| Expected Close Date | `field_expected_close_date` | Date                                       | 1           | ❌ No    | Alternative close date field      |
| Contact             | `field_contact`             | Entity Reference (node:contact)            | 1           | ❌ No    | Primary contact for deal          |
| Organization        | `field_organization`        | Entity Reference (node:organization)       | 1           | ❌ No    | Company related to deal           |
| Owner               | `field_owner`               | Entity Reference (user)                    | 1           | ✅ Yes   | User who owns this deal ⭐        |
| Description         | `field_description`         | Text (long, formatted)                     | 1           | ❌ No    | Deal details                      |
| Lost Reason         | `field_lost_reason`         | Entity Reference (taxonomy:lost_reason)    | 1           | ❌ No    | Why deal was lost (if applicable) |

**Pipeline Stages (Taxonomy: pipeline_stage):**

```
Term ID | Term Name      | Weight | Description
--------|----------------|--------|---------------------------
1       | Lead           | 0      | Initial interest
2       | Qualified      | 1      | Qualified as real opportunity
3       | Proposal Sent  | 2      | Proposal/quote sent
4       | Negotiation    | 3      | In price/terms negotiation
5       | Closed Won     | 4      | Deal won! 🎉
6       | Closed Lost    | 5      | Deal lost 😞
```

**Kanban Board Logic:**

The `field_stage` determines which column a deal appears in on `/crm/pipeline`.

```php
// Kanban query for "Qualified" stage
$query = \Drupal::entityQuery('node')
  ->condition('type', 'deal')
  ->condition('field_stage', 2) // Qualified stage term ID
  ->condition('field_owner', $current_user_id) // Ownership filter
  ->sort('created', 'DESC')
  ->execute();
```

When user drags a deal from "Qualified" to "Proposal Sent":

```javascript
// Frontend: inline-edit.js or kanban-specific JS
function onDealDrop(dealId, newStageId) {
  fetch("/crm/pipeline/update-stage", {
    method: "POST",
    body: JSON.stringify({ deal_id: dealId, new_stage: newStageId }),
  });
}
```

```php
// Backend: KanbanController::updateStage()
$deal = Node::load($deal_id);
$deal->set('field_stage', $new_stage_id);
$deal->save();
return new JsonResponse(['success' => true]);
```

### 3.3 Content Type: Organization

**Purpose:** Represents companies/organizations

**Fields:**

| Field Label     | Machine Name            | Type                                 | Description         |
| --------------- | ----------------------- | ------------------------------------ | ------------------- |
| Company Name    | `title`                 | Text                                 | Organization name   |
| Industry        | `field_industry`        | Entity Reference (taxonomy:industry) | Business sector     |
| Employees Count | `field_employees_count` | Integer                              | Number of employees |
| Annual Revenue  | `field_annual_revenue`  | Decimal                              | Yearly revenue      |
| Address         | `field_address`         | Text (plain, long)                   | Company address     |
| Phone           | `field_phone`           | Telephone                            | Main phone          |
| Email           | `field_email`           | Email                                | Main email          |
| Assigned Staff  | `field_assigned_staff`  | Entity Reference (user)              | Account manager ⭐  |
| Logo            | `field_logo`            | Image                                | Company logo        |
| Description     | `field_description`     | Text (long, formatted)               | About the company   |

**Note:** Uses `field_assigned_staff` instead of `field_owner` for ownership tracking. This is a business logic decision to indicate "account assignment" rather than "ownership".

### 3.4 Content Type: Activity

**Purpose:** Represents tasks, calls, meetings, emails, notes

**Fields:**

| Field Label    | Machine Name        | Type                                         | Description                      |
| -------------- | ------------------- | -------------------------------------------- | -------------------------------- |
| Activity Title | `title`             | Text                                         | What is this activity            |
| Type           | `field_type`        | Entity Reference (taxonomy:activity_type)    | Call, Meeting, Email, Note, Task |
| Assigned To    | `field_assigned_to` | Entity Reference (user)                      | Who should do this ⭐            |
| Contact        | `field_contact_ref` | Entity Reference (node:contact)              | Related contact                  |
| Deal           | `field_deal`        | Entity Reference (node:deal)                 | Related deal                     |
| Date & Time    | `field_datetime`    | Date/time                                    | When this occurs                 |
| Outcome        | `field_outcome`     | Entity Reference (taxonomy:activity_outcome) | Completed, Cancelled, etc.       |
| Notes          | `field_notes`       | Text (long, formatted)                       | Activity details                 |

**Activity Types (Taxonomy: activity_type):**

- Call
- Meeting
- Email
- Task
- Note

**Usage on Contact Detail Page:**

The `crm_activity_log` module displays a timeline of activities related to a contact:

```php
// ActivityLogController.php
$activities = \Drupal::entityQuery('node')
  ->condition('type', 'activity')
  ->condition('field_contact_ref', $contact_nid)
  ->sort('created', 'DESC')
  ->range(0, 10)
  ->execute();
```

---

# PART 2: MODULE DEEP DIVE

## 4. Core Module (crm) - Complete Analysis

**Location:** `web/modules/custom/crm/`

### 4.1 crm.info.yml

```yaml
name: "CRM Core"
type: module
description: "Core CRM functionality: access control, data privacy, owner tracking"
package: CRM
core_version_requirement: ^10 || ^11
dependencies:
  - drupal:node
  - drupal:user
  - drupal:views
```

**What it does:**

- Declares module metadata
- Lists dependencies (node, user, views)
- Defines package grouping for admin UI

**When Drupal loads it:**

- On bootstrap (module discovery phase)
- Module must be enabled via: `drush en crm`

### 4.2 crm.module (Full Explanation)

**Purpose:** This file contains **hook implementations** - functions that Drupal calls at specific points in the request lifecycle.

#### Hook 1: `crm_node_access()`

**Signature:**

```php
function crm_node_access(NodeInterface $node, $op, AccountInterface $account)
```

**When called:** Every time Drupal checks if a user can access a node.

**Operations checked:** `view`, `update`, `delete`

**Logic Flow:**

```php
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $crm_types = ['contact', 'deal', 'activity', 'organization'];

  // Step 1: Only apply to CRM content types
  if (!in_array($node->bundle(), $crm_types)) {
    return AccessResult::neutral(); // Not our concern
  }

  // Step 2: Admins and managers bypass all checks
  if ($account->hasRole('administrator') ||
      $account->hasPermission('bypass crm team access') ||
      $account->hasRole('sales_manager')) {
    return AccessResult::neutral(); // Let Drupal's default system handle it
  }

  // Step 3: Sales reps - check ownership OR team membership
  if ($account->hasRole('sales_rep')) {
    // Determine which field contains the owner
    if ($node->bundle() === 'activity') {
      $owner_field = 'field_assigned_to';
    } elseif ($node->bundle() === 'organization') {
      $owner_field = 'field_assigned_staff';
    } else {
      $owner_field = 'field_owner'; // contact, deal
    }

    if (!$node->hasField($owner_field)) {
      return AccessResult::neutral();
    }

    $owner_id = $node->get($owner_field)->target_id;

    if (in_array($op, ['view', 'update', 'delete'])) {
      // Check 1: Is current user the owner?
      $is_owner = ($owner_id == $account->id());

      // Check 2: Are they on the same team?
      $same_team = _crm_check_same_team($account->id(), $owner_id);

      if ($is_owner || $same_team) {
        return AccessResult::allowed()
          ->cachePerUser()
          ->addCacheableDependency($node);
      }
      else {
        return AccessResult::forbidden('You can only access your own content or same team content')
          ->cachePerUser()
          ->addCacheableDependency($node);
      }
    }
  }

  return AccessResult::neutral();
}
```

**Real-world example:**

```
Scenario: Sales rep "John" (User ID 5) tries to view Contact "Jane Doe" (Node ID 123)

1. Drupal calls: crm_node_access($contact_node, 'view', $john_account)
2. Check: Is this a CRM type? Yes (contact) ✅
3. Check: Is John an admin? No ❌
4. Check: Is John a sales_rep? Yes ✅
5. Get owner of Contact 123: field_owner = User ID 7 (Sarah)
6. Check: Is John (5) the owner (7)? No ❌
7. Check: Are John and Sarah on same team?
   - Load John's field_team: Team ID 2
   - Load Sarah's field_team: Team ID 2
   - Same team? Yes ✅
8. Result: AccessResult::allowed() ✅ John can view the contact
```

#### Hook 2: `crm_query_node_access_alter()`

**Signature:**

```php
function crm_query_node_access_alter(\Drupal\Core\Database\Query\AlterableInterface $query)
```

**When called:** When Views or entity queries are executed and tagged with 'node_access'.

**Purpose:** Automatically filters queries to only return content the current user can access.

**Logic:**

```php
function crm_query_node_access_alter($query) {
  $account = \Drupal::currentUser();

  // Skip for admins and managers
  if ($account->hasRole('administrator') ||
      $account->hasRole('sales_manager') ||
      $account->hasPermission('bypass crm team access')) {
    return; // No filtering
  }

  // Only filter for sales reps
  if (!$account->hasRole('sales_rep')) {
    return;
  }

  // Find the node table in the query
  $tables = $query->getTables();
  foreach ($tables as $table) {
    if (isset($table['table']) && $table['table'] === 'node_field_data') {
      $alias = $table['alias'];

      // Join with ownership field tables
      $query->leftJoin('node__field_owner', 'crm_owner', "$alias.nid = crm_owner.entity_id");
      $query->leftJoin('node__field_assigned_to', 'crm_assigned', "$alias.nid = crm_assigned.entity_id");
      $query->leftJoin('node__field_assigned_staff', 'crm_staff', "$alias.nid = crm_staff.entity_id");

      // Build OR condition: user is owner OR assigned_to OR assigned_staff
      $or = $query->orConditionGroup()
        ->condition('crm_owner.field_owner_target_id', $account->id(), '=')
        ->condition('crm_assigned.field_assigned_to_target_id', $account->id(), '=')
        ->condition('crm_staff.field_assigned_staff_target_id', $account->id(), '=');

      // Optional: Add team-based filtering
      $user_team_id = _crm_get_user_team($account->id());
      if (!empty($user_team_id)) {
        // Join to owner's user to check their team
        $query->leftJoin('users_field_data', 'crm_node_owner', "$alias.uid = crm_node_owner.uid");
        $query->leftJoin('user__field_team', 'crm_owner_team', "crm_node_owner.uid = crm_owner_team.entity_id");

        // Also allow if owner is in same team
        $or->condition('crm_owner_team.field_team_target_id', $user_team_id, '=');
      }

      $query->condition($or);
      break;
    }
  }
}
```

**Real-world example:**

```
Scenario: Views executes query for "My Contacts" page

Original Query (before alteration):
SELECT * FROM node_field_data WHERE type = 'contact' AND status = 1

After crm_query_node_access_alter():
SELECT nfd.*
FROM node_field_data nfd
LEFT JOIN node__field_owner crm_owner ON nfd.nid = crm_owner.entity_id
LEFT JOIN node__field_assigned_to crm_assigned ON nfd.nid = crm_assigned.entity_id
LEFT JOIN node__field_assigned_staff crm_staff ON nfd.nid = crm_staff.entity_id
WHERE nfd.type = 'contact'
  AND nfd.status = 1
  AND (
    crm_owner.field_owner_target_id = 5  -- Current user
    OR crm_assigned.field_assigned_to_target_id = 5
    OR crm_staff.field_assigned_staff_target_id = 5
  )

Result: Sales rep only sees contacts they own or are assigned to
```

#### Hook 3: `crm_form_alter()`

**Purpose:** Pre-fills owner fields when creating new CRM content.

```php
function crm_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // When user clicks "Add Contact", this pre-fills field_owner

  $node = $form_state->getFormObject()->getEntity();

  if ($node->isNew() && in_array($node->bundle(), ['contact', 'deal'])) {
    $form['field_owner']['widget'][0]['target_id']['#default_value'] =
      \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
  }
}
```

#### Hook 4: `crm_node_presave()`

**Purpose:** Ensures owner field is set when saving, even if form doesn't set it.

```php
function crm_node_presave(NodeInterface $node) {
  if ($node->bundle() === 'contact' && $node->get('field_owner')->isEmpty()) {
    $node->set('field_owner', \Drupal::currentUser()->id());
  }
}
```

**Why both form_alter() and node_presave()?**

- `form_alter()` provides better UX (user sees pre-filled value)
- `node_presave()` is a safety net (in case node is created programmatically or through API)

### 4.3 crm.routing.yml

```yaml
crm.user_profile:
  path: "/user/{user}/profile"
  defaults:
    _controller: '\Drupal\crm\Controller\UserProfileController::view'
    _title: "User Profile"
  requirements:
    _permission: "access user profiles"
    user: \d+
  options:
    parameters:
      user:
        type: entity:user

crm.user_profile_canonical:
  path: "/user/{user}"
  defaults:
    _controller: '\Drupal\crm\Controller\UserProfileController::view'
    _title_callback: '\Drupal\crm\Controller\UserProfileController::getTitle'
  requirements:
    _custom_access: '\Drupal\crm\Controller\UserProfileController::access'
    user: \d+
  options:
    parameters:
      user:
        type: entity:user
```

**What it does:**

- Defines custom routes for user profile pages
- Routes `/user/5` and `/user/5/profile` to custom controller
- Applies access checks

**Why custom profile route?**

- Default Drupal `/user/5` shows basic info
- Custom controller adds CRM-specific data (contacts owned, deals won, etc.)

### 4.4 crm.libraries.yml

```yaml
user_profile_styles:
  version: 1.0
  css:
    theme:
      css/user-profile.css: {}
  js:
    https://unpkg.com/lucide@latest: { type: external, minified: true }
    js/user-profile.js: {}
  dependencies:
    - core/drupal
    - crm/crm_layout

crm_layout:
  version: 1.0
  css:
    theme:
      css/crm-layout.css: {}
  dependencies:
    - core/drupal
```

**What it does:**

- Defines CSS/JS assets that can be attached to pages
- `user_profile_styles` is loaded on user profile pages
- `crm_layout` is loaded globally on all CRM pages

**How to attach a library:**

```php
// In a render array:
return [
  '#markup' => '<div class="crm-page">Content</div>',
  '#attached' => [
    'library' => ['crm/crm_layout'],
  ],
];
```

**Dependencies:**

- `core/drupal` ensures Drupal core JS is loaded first
- `crm/crm_layout` dependency means "load crm_layout first, then user_profile_styles"

### 4.5 Helper Functions

#### `_crm_check_same_team()`

```php
/**
 * Check if two users are in the same team.
 *
 * @param int $uid1
 *   First user ID.
 * @param int $uid2
 *   Second user ID.
 *
 * @return bool
 *   TRUE if same team, FALSE otherwise.
 */
function _crm_check_same_team($uid1, $uid2) {
  $team1 = _crm_get_user_team($uid1);
  $team2 = _crm_get_user_team($uid2);

  if (empty($team1) || empty($team2)) {
    return FALSE; // If either has no team, no team-based access
  }

  return ($team1 === $team2);
}
```

**Usage:**

```php
if (_crm_check_same_team(5, 7)) {
  // User 5 and User 7 are on same team
}
```

#### `_crm_get_user_team()`

```php
/**
 * Get user's team ID.
 *
 * @param int $uid
 *   User ID.
 *
 * @return int|null
 *   Team taxonomy term ID or NULL.
 */
function _crm_get_user_team($uid) {
  $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);

  if ($user && $user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
    return $user->get('field_team')->target_id;
  }

  return NULL;
}
```

**Usage:**

```php
$team_id = _crm_get_user_team(5);
// Returns: 2 (Team ID) or NULL
```

### 4.6 Summary: Why CRM Core Module is Critical

**This module is the foundation of the entire CRM system.**

Without it:

- ❌ Sales reps would see ALL contacts (data leak)
- ❌ No ownership tracking
- ❌ No team-based access control
- ❌ Security vulnerabilities

With it:

- ✅ Automatic data filtering on all queries
- ✅ Ownership enforcement
- ✅ Team collaboration support
- ✅ Centralized access control logic

**Module dependency chain:**

```
All other CRM modules → Depend on → crm (core)
                                    ↓
                       Provides access control for all CRM data
```

---

## 5. Dashboard Module - File by File

**Location:** `web/modules/custom/crm_dashboard/`

**Purpose:** Displays KPI cards, charts, and metrics on `/crm/dashboard`

### 5.1 crm_dashboard.info.yml

```yaml
name: "CRM Dashboard"
type: module
description: "Professional dashboard with KPI cards and charts"
core_version_requirement: ^11
package: "CRM"
```

**No dependencies listed** - This means it only depends on Drupal core, not on other custom modules.

However, it **implicitly depends on `crm` module** because it queries CRM content types (contact, deal, organization, activity).

### 5.2 crm_dashboard.routing.yml

```yaml
crm_dashboard.admin:
  path: "/admin/crm"
  defaults:
    _controller: '\Drupal\crm_dashboard\Controller\DashboardController::view'
    _title: "CRM Dashboard"
  requirements:
    _permission: "access content"

crm_dashboard.dashboard:
  path: "/crm/dashboard"
  defaults:
    _controller: '\Drupal\crm_dashboard\Controller\DashboardController::view'
    _title: "CRM Dashboard"
  requirements:
    _permission: "access content"

crm_dashboard.test:
  path: "/crm/dashboard-new"
  defaults:
    _controller: '\Drupal\crm_dashboard\Controller\DashboardController::view'
    _title: "NEW Dashboard Test"
  requirements:
    _permission: "access content"
```

**3 routes, same controller:**

- `/admin/crm` - Admin-accessible dashboard
- `/crm/dashboard` - User dashboard (main route)
- `/crm/dashboard-new` - Test route (probably for development)

**Why same controller for all?**

- Controller logic automatically filters data based on user role
- No need for separate admin/user controllers

### 5.3 DashboardController.php (Complete Breakdown)

**Location:** `src/Controller/DashboardController.php`  
**Size:** 1440 lines (very long controller - refactoring opportunity!)

#### Method: `view()`

**Purpose:** Renders entire dashboard page

**Logic Flow:**

```php
public function view() {
  // STEP 1: Identify current user
  $current_user = \Drupal::currentUser();
  $user_id = $current_user->id();

  // STEP 2: Check if user is admin/manager
  $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;

  // STEP 3: Count Contacts (filtered by ownership for non-admins)
  $contacts_query = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->accessCheck(FALSE); // Bypass access checks (we'll filter manually)

  if (!$is_admin) {
    $contacts_query->condition('field_owner', $user_id); // Filter to user's contacts
  }

  $contacts_count = $contacts_query->count()->execute();

  // STEP 4: Count Organizations (filtered by assigned_staff for non-admins)
  $orgs_query = \Drupal::entityQuery('node')
    ->condition('type', 'organization')
    ->accessCheck(FALSE);

  if (!$is_admin) {
    $orgs_query->condition('field_assigned_staff', $user_id);
  }

  $orgs_count = $orgs_query->count()->execute();

  // STEP 5: Count Deals (filtered by owner for non-admins)
  $deals_query = \Drupal::entityQuery('node')
    ->condition('type', 'deal')
    ->accessCheck(FALSE);

  if (!$is_admin) {
    $deals_query->condition('field_owner', $user_id);
  }

  $deals_count = $deals_query->count()->execute();

  // STEP 6: Count Activities (filtered by assigned_to for non-admins)
  $activities_query = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->accessCheck(FALSE);

  if (!$is_admin) {
    $activities_query->condition('field_assigned_to', $user_id);
  }

  $activities_count = $activities_query->count()->execute();

  // STEP 7: Load pipeline stages from taxonomy
  $stage_terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'pipeline_stage']);

  $stages = [];
  $stage_colors = [];
  $deals_by_stage = [];

  // Color palette for chart
  $color_palette = [
    '#60a5fa', '#34d399', '#fbbf24', '#f472b6', '#10b981', '#ef4444',
    '#06b6d4', '#84cc16', '#f97316', '#a855f7', '#14b8a6', '#f43f5e',
  ];
  $color_index = 0;

  // STEP 8: For each pipeline stage, count deals
  foreach ($stage_terms as $term) {
    $stage_id = $term->id();
    $stage_name = $term->getName();
    $stages[$stage_id] = $stage_name;
    $stage_colors[$stage_id] = $color_palette[$color_index % count($color_palette)];
    $color_index++;

    $stage_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_stage', $stage_id)
      ->accessCheck(FALSE);

    if (!$is_admin) {
      $stage_query->condition('field_owner', $user_id);
    }

    $count = $stage_query->count()->execute();
    $deals_by_stage[$stage_id] = $count;
  }

  // STEP 9: Calculate revenue metrics
  $deal_ids_query = \Drupal::entityQuery('node')
    ->condition('type', 'deal')
    ->accessCheck(FALSE);

  if (!$is_admin) {
    $deal_ids_query->condition('field_owner', $user_id);
  }

  $deal_ids = $deal_ids_query->execute();
  $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids);

  $total_value = 0;
  $won_value = 0;
  $lost_value = 0;
  $won_count = 0;
  $lost_count = 0;

  foreach ($deals as $deal) {
    $amount = 0;
    if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
      $amount = floatval($deal->get('field_amount')->value);
      $total_value += $amount;
    }

    if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
      $stage = $deal->get('field_stage')->value;
      if ($stage === 'closed_won') {
        $won_value += $amount;
        $won_count++;
      } elseif ($stage === 'closed_lost') {
        $lost_value += $amount;
        $lost_count++;
      }
    }
  }

  // STEP 10: Format values for display
  $total_value_display = '$' . number_format($total_value / 1000000, 1) . 'M';
  $won_value_display = '$' . number_format($won_value / 1000000, 1) . 'M';

  // Calculate KPIs
  $total_closed = $won_count + $lost_count;
  $win_rate = $total_closed > 0 ? round(($won_count / $total_closed) * 100, 1) : 0;
  $avg_deal_size = $won_count > 0 ? round($won_value / $won_count, 0) : 0;
  $avg_deal_display = '$' . number_format($avg_deal_size / 1000, 0) . 'K';
  $conversion_rate = $deals_count > 0 ? round(($won_count / $deals_count) * 100, 1) : 0;

  // STEP 11: Get recent activities (last 10)
  $activity_ids_query = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->accessCheck(FALSE)
    ->sort('created', 'DESC')
    ->range(0, 10);

  if (!$is_admin) {
    $activity_ids_query->condition('field_assigned_to', $user_id);
  }

  $activity_ids = $activity_ids_query->execute();
  $recent_activities = [];

  if (!empty($activity_ids)) {
    $activities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($activity_ids);
    foreach ($activities as $activity) {
      // Get activity type from taxonomy
      $type_value = 'note';
      if ($activity->hasField('field_type') && !$activity->get('field_type')->isEmpty()) {
        $type_term = $activity->get('field_type')->entity;
        if ($type_term) {
          $type_value = strtolower($type_term->getName());
        }
      }

      // Map activity types to icons
      $type_icons = [
        'call' => 'phone',
        'meeting' => 'calendar',
        'email' => 'mail',
        'note' => 'file-text',
        'task' => 'check-square',
      ];

      // Map activity types to colors
      $type_colors = [
        'call' => '#3b82f6',
        'meeting' => '#8b5cf6',
        'email' => '#10b981',
        'note' => '#f59e0b',
        'task' => '#ec4899',
      ];

      // Get contact name
      $contact_name = '';
      if ($activity->hasField('field_contact') && !$activity->get('field_contact')->isEmpty()) {
        $contact = $activity->get('field_contact')->entity;
        if ($contact) {
          $contact_name = $contact->getTitle();
        }
      }

      $recent_activities[] = [
        'title' => $activity->getTitle(),
        'type' => ucfirst($type_value ?? 'note'),
        'icon' => $type_icons[$type_value] ?? 'activity',
        'color' => $type_colors[$type_value] ?? '#64748b',
        'contact' => $contact_name,
        'created' => \Drupal::service('date.formatter')->format($activity->getCreatedTime(), 'custom', 'd/m H:i'),
      ];
    }
  }

  // STEP 12: Build HTML output (long section with embedded styles and Chart.js)
  $html = $this->buildDashboardHtml(
    $contacts_count,
    $orgs_count,
    $deals_count,
    $activities_count,
    $stages,
    $stage_colors,
    $deals_by_stage,
    $total_value_display,
    $won_value_display,
    $win_rate,
    $avg_deal_display,
    $conversion_rate,
    $recent_activities
  );

  // STEP 13: Return render array
  return [
    '#markup' => Markup::create($html),
    '#attached' => [
      'library' => ['core/drupal'],
    ],
  ];
}
```

**Key Observations:**

1. **Manual role checking:** Uses `$is_admin` flag instead of permission checks
2. **Bypass access checks:** Uses `->accessCheck(FALSE)` because it manually filters by ownership
3. **Multiple queries:** Many separate queries (could be optimized)
4. **No service classes:** All logic in one method (refactoring opportunity)
5. **HTML generation:** Controller generates HTML directly (not using Twig templates)

#### Method: `buildDashboardHtml()`

**Purpose:** Generates HTML string with embedded CSS and Chart.js

**Structure:**

```php
private function buildDashboard Html(...many params...) {
  $html = <<<'HTML'
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  /* Embedded CSS for dashboard */
  .crm-dashboard { ... }
  .kpi-card { ... }
  .chart-container { ... }
</style>

<div class="crm-dashboard">
  <h1>My Dashboard</h1>

  <!-- KPI Cards -->
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon"><i data-lucide="users"></i></div>
      <div class="kpi-value">{$contacts_count}</div>
      <div class="kpi-label">Total Contacts</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon"><i data-lucide="building-2"></i></div>
      <div class="kpi-value">{$orgs_count}</div>
      <div class="kpi-label">Organizations</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon"><i data-lucide="briefcase"></i></div>
      <div class="kpi-value">{$deals_count}</div>
      <div class="kpi-label">Active Deals</div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon"><i data-lucide="activity"></i></div>
      <div class="kpi-value">{$activities_count}</div>
      <div class="kpi-label">Activities</div>
    </div>
  </div>

  <!-- Pipeline Chart -->
  <div class="chart-container">
    <h2>Pipeline Overview</h2>
    <canvas id="pipelineChart"></canvas>
  </div>

  <!-- Revenue Chart -->
  <div class="chart-container">
    <h2>Revenue Metrics</h2>
    <canvas id="revenueChart"></canvas>
  </div>

  <!-- Recent Activities -->
  <div class="activities-container">
    <h2>Recent Activities</h2>
    <div class="activity-list">
      {foreach activity}
        <div class="activity-item">
          <div class="activity-icon" style="background: {color}">
            <i data-lucide="{icon}"></i>
          </div>
          <div class="activity-content">
            <div class="activity-title">{title}</div>
            <div class="activity-meta">{type} | {contact} | {date}</div>
          </div>
        </div>
      {endforeach}
    </div>
  </div>
</div>

<script>
  // Initialize Lucide icons
  lucide.createIcons();

  // Pipeline Chart
  const pipelineCtx = document.getElementById('pipelineChart').getContext('2d');
  new Chart(pipelineCtx, {
    type: 'bar',
    data: {
      labels: {stage_labels},
      datasets: [{
        label: 'Deals by Stage',
        data: {deal_counts},
        backgroundColor: {stage_colors}
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false
    }
  });

  // Revenue Chart
  const revenueCtx = document.getElementById('revenueChart').getContext('2d');
  new Chart(revenueCtx, {
    type: 'doughnut',
    data: {
      labels: ['Won', 'Active', 'Lost'],
      datasets: [{
        data: [{won_value}, {active_value}, {lost_value}],
        backgroundColor: ['#10b981', '#3b82f6', '#ef4444']
      }]
    }
  });
</script>
HTML;

  return $html;
}
```

**Issues with this approach:**

1. **No template reusability:** HTML is hardcoded in controller
2. **Hard to theme:** Can't override in a theme
3. **Inline CSS/JS:** Violates CSP (Content Security Policy)
4. **Hard to test:** Can't unit test HTML generation
5. **Maintenance nightmare:** 1440 lines in one file!

**Better approach (recommendation):**

```php
// Controller should return structured data:
return [
  '#theme' => 'crm_dashboard',
  '#kpis' => [
    'contacts' => $contacts_count,
    'deals' => $deals_count,
    // etc
  ],
  '#chart_data' => [
    'pipeline' => $deals_by_stage,
    'revenue' => $revenue_data,
  ],
  '#activities' => $recent_activities,
  '#attached' => [
    'library' => ['crm_dashboard/dashboard'],
  ],
];

// Then: templates/crm-dashboard.html.twig
// And: crm_dashboard.libraries.yml with CSS/JS
```

### 5.4 Summary: Dashboard Module

**What it does well:**

- ✅ Comprehensive KPI display
- ✅ Nice chart visualizations
- ✅ Role-based data filtering
- ✅ Recent activities timeline

**What needs improvement:**

- ⚠️ 1440-line controller (refactor into services)
- ⚠️ Inline HTML/CSS/JS (use Twig templates + libraries)
- ⚠️ Multiple queries (could aggregate)
- ⚠️ No caching (performance issue with many users)

---

## 6. Edit Module - Inline Editing System

**Location:** `web/modules/custom/crm_edit/`

**Purpose:** Provides inline editing, modal forms, AJAX save/delete for all CRM content types

### 6.1 crm_edit.info.yml

```yaml
name: "CRM Edit"
description: "Inline editing functionality for CRM content with role-based permissions"
type: module
core_version_requirement: ^11
package: "CRM"
dependencies:
  - node
  - crm
```

**Note:** Depends on `crm` module (for access control logic)

### 6.2 crm_edit.routing.yml (Complete Analysis)

```yaml
# Edit routes (one per content type)
crm_edit.edit_contact:
  path: "/crm/edit/contact/{node}"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\InlineEditController::editContact'
    _title: "Edit Contact"
  requirements:
    _permission: "edit own contact content+edit any contact content"
    node: \d+
  options:
    parameters:
      node:
        type: entity:node

crm_edit.edit_deal:
  path: "/crm/edit/deal/{node}"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\InlineEditController::editDeal'
    _title: "Edit Deal"
  requirements:
    _permission: "edit own deal content+edit any deal content"
    node: \d+
  options:
    parameters:
      node:
        type: entity:node

# ... similar for edit_organization, edit_activity

# AJAX endpoints
crm_edit.ajax_save:
  path: "/crm/edit/ajax/save"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\InlineEditController::ajaxSave'
  requirements:
    _permission: "access content"
  methods: [POST]

crm_edit.ajax_validate:
  path: "/crm/edit/ajax/validate"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\InlineEditController::ajaxValidate'
  requirements:
    _permission: "access content"
  methods: [POST]

# Modal endpoints
crm_edit.modal_form:
  path: "/crm/edit/modal/form"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\ModalEditController::getEditForm'
  requirements:
    _permission: "access content"
  methods: [GET]

# Delete endpoints
crm_edit.ajax_delete:
  path: "/crm/edit/ajax/delete"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\DeleteController::ajaxDelete'
  requirements:
    _permission: "access content"
  methods: [POST]

# Add/Create endpoints
crm_edit.ajax_create_form:
  path: "/crm/edit/ajax/create/form"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\AddController::getCreateForm'
  requirements:
    _permission: "access content"
  methods: [GET]

crm_edit.ajax_create:
  path: "/crm/edit/ajax/create"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\AddController::ajaxCreate'
  requirements:
    _permission: "access content"
  methods: [POST]

# Add pages (full page, not modal)
crm_edit.add_contact:
  path: "/crm/add/contact"
  defaults:
    _controller: '\Drupal\crm_edit\Controller\AddController::addPage'
    type: "contact"
    _title: "Add Contact"
  requirements:
    _permission: "create contact content"

# ... similar for add_deal, add_organization, add_activity
```

**Route categories:**

1. **Edit routes** (`/crm/edit/{type}/{node}`) - Full-page edit forms
2. **AJAX save** (`/crm/edit/ajax/save`) - Handle form submissions
3. **AJAX validate** (`/crm/edit/ajax/validate`) - Real-time validation
4. **Modal form** (`/crm/edit/modal/form`) - Return modal HTML
5. **Delete** (`/crm/edit/ajax/delete`) - Delete confirmation
6. **Create** (`/crm/edit/ajax/create`) - Quick-add functionality
7. **Add pages** (`/crm/add/{type}`) - Full-page add forms

### 6.3 InlineEditController.php (Deep Dive)

**Purpose:** Handles all edit operations (forms, AJAX save, validation)

#### Method: `checkEditAccess()`

```php
protected function checkEditAccess(NodeInterface $node) {
  $account = $this->currentUser();

  // Admin has full access
  if ($account->hasRole('administrator')) {
    return AccessResult::allowed();
  }

  $bundle = $node->bundle();

  // Managers can edit any content
  if ($account->hasRole('sales_manager')) {
    if ($account->hasPermission("edit any {$bundle} content")) {
      return AccessResult::allowed();
    }
  }

  // Sales reps can only edit own content
  if ($account->hasRole('sales_rep')) {
    $owner_field = $this->getOwnerField($bundle);

    if ($node->hasField($owner_field)) {
      $owner_id = $node->get($owner_field)->target_id;

      if ($owner_id == $account->id()) {
        if ($account->hasPermission("edit own {$bundle} content")) {
          return AccessResult::allowed();
        }
      }
    }
  }

  return AccessResult::forbidden('You do not have permission to edit this content.');
}
```

**Explanation:**

- This method is called BEFORE showing edit form
- Checks user role and ownership
- Returns `AccessResult::allowed()` or `AccessResult::forbidden()`
- Used by all edit methods (editContact, editDeal, etc.)

#### Method: `editContact()`

```php
public function editContact(NodeInterface $node) {
  // Check access first
  $access = $this->checkEditAccess($node);
  if (!$access->isAllowed()) {
    return [
      '#markup' => '<div class="error-message">Access denied.</div>',
    ];
  }

  // Build edit form
  return $this->buildEditForm($node, 'contact');
}
```

**Flow:**

```
1. User navigates to /crm/edit/contact/123
2. Drupal routes to: InlineEditController::editContact($node)
3. Check access: Can user edit node 123?
4. If yes: Build edit form HTML
5. Return render array with form
```

#### Method: `buildEditForm()`

```php
protected function buildEditForm(NodeInterface $node, $type) {
  $nid = $node->id();
  $title = $node->getTitle();

  // Get field definitions
  $field_manager = \Drupal::service('entity_field.manager');
  $fields = $field_manager->getFieldDefinitions('node', $type);

  // Build editable fields list
  $editable_fields = $this->getEditableFields($node, $fields);

  // Generate HTML form
  $html = $this->generateEditFormHTML($node, $type, $editable_fields);

  return [
    '#markup' => Markup::create($html),
    '#attached' => [
      'library' => ['crm_edit/inline_edit'],
    ],
  ];
}
```

**What `getEditableFields()` does:**

- Returns list of fields that should be editable
- Excludes: title, created, changed, uid (system fields)
- Includes: field_email, field_phone, field_organization, etc.

```php
protected function getEditableFields($node, $fields) {
  $editable = [];

  foreach ($fields as $field_name => $field_definition) {
    // Skip base fields
    if (in_array($field_name, ['nid', 'uuid', 'vid', 'type', 'uid', 'title', 'created', 'changed'])) {
      continue;
    }

    // Skip fields user can't edit
    if (!$field_definition->getFieldStorageDefinition()->isBaseField()) {
      $editable[$field_name] = $field_definition;
    }
  }

  return $editable;
}
```

#### Method: `ajaxSave()` (Most Important!)

**Purpose:** Receives AJAX POST, validates, saves node, returns JSON

```php
public function ajaxSave(Request $request) {
  // Get POST data
  $data = json_decode($request->getContent(), TRUE);

  $nid = $data['nid'] ?? NULL;
  $type = $data['type'] ?? NULL;

  if (!$nid || !$type) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Missing node ID or type',
    ], 400);
  }

  // Load node
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

  if (!$node) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Node not found',
    ], 404);
  }

  // Check access
  $access = $this->checkEditAccess($node);
  if (!$access->isAllowed()) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Access denied',
    ], 403);
  }

  // Update fields
  $updated_fields = [];
  foreach ($data as $field_name => $field_value) {
    // Skip non-field keys
    if (in_array($field_name, ['nid', 'type'])) {
      continue;
    }

    // Check if field exists and is editable
    if ($node->hasField($field_name)) {
      // Special handling for different field types
      if (strpos($field_name, 'field_') === 0) {
        $field_definition = $node->get($field_name)->getFieldDefinition();
        $field_type = $field_definition->getType();

        // Entity reference fields
        if ($field_type === 'entity_reference') {
          $node->set($field_name, ['target_id' => $field_value]);
        }
        // Regular fields
        else {
          $node->set($field_name, $field_value);
        }

        $updated_fields[] = $field_name;
      }
    }
  }

  // Validate node
  $violations = $node->validate();
  if (count($violations) > 0) {
    $errors = [];
    foreach ($violations as $violation) {
      $errors[] = $violation->getMessage();
    }

    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Validation failed',
      'errors' => $errors,
    ], 422);
  }

  // Save node
  try {
    $node->save();

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Changes saved successfully',
      'updated_fields' => $updated_fields,
      'node_id' => $nid,
    ]);
  } catch (\Exception $e) {
    \Drupal::logger('crm_edit')->error('Error saving node @nid: @message', [
      '@nid' => $nid,
      '@message' => $e->getMessage(),
    ]);

    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Error saving changes: ' . $e->getMessage(),
    ], 500);
  }
}
```

**Step-by-step flow:**

```
1. JavaScript sends AJAX POST:
   {
     nid: 123,
     type: 'contact',
     field_email: 'john@example.com',
     field_phone: '0123456789'
   }

2. ajaxSave() receives request

3. Parse JSON body

4. Load node 123

5. Check if user can edit (checkEditAccess)

6. For each field in POST data:
   - Check if field exists on node
   - Set field value: $node->set('field_email', 'john@example.com')

7. Validate node: $node->validate()

8. If validation passes:
   - Save: $node->save()
   - Return success JSON

9. If validation fails:
   - Return error JSON with validation messages

10. JavaScript receives response:
    - If success: Close modal, show notification, reload page
    - If error: Display error messages
```

### 6.4 ModalEditController.php

**Purpose:** Returns modal HTML for AJAX requests

#### Method: `getEditForm()`

```php
public function getEditForm(Request $request) {
  $nid = $request->query->get('nid');
  $type = $request->query->get('type');

  // Load node
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

  if (!$node) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Node not found',
    ], 404);
  }

  // Check access
  $access = $this->checkEditAccess($node);
  if (!$access->isAllowed()) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Access denied',
    ], 403);
  }

  // Build form HTML
  $html = $this->buildModalHTML($node, $type);

  return new JsonResponse([
    'success' => TRUE,
    'html' => $html,
  ]);
}
```

**Explanation:**

- Receives GET request: `/crm/edit/modal/form?nid=123&type=contact`
- Loads node
- Checks access
- Builds HTML form
- Returns JSON: `{ success: true, html: "<div>...</div>" }`

**Frontend then:**

- Receives JSON
- Extracts `html` property
- Injects into modal overlay
- Displays modal to user

### 6.5 DeleteController.php

**Purpose:** Handles delete confirmation and execution

#### Method: `ajaxDelete()`

```php
public function ajaxDelete(Request $request) {
  $data = json_decode($request->getContent(), TRUE);
  $nid = $data['nid'] ?? NULL;

  if (!$nid) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Missing node ID',
    ], 400);
  }

  // Load node
  $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

  if (!$node) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Node not found',
    ], 404);
  }

  // Check access (user must have delete permission)
  if (!$node->access('delete')) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Access denied',
    ], 403);
  }

  // Delete node
  try {
    $title = $node->getTitle();
    $node->delete();

    return new JsonResponse([
      'success' => TRUE,
      'message' => "'{$title}' has been deleted",
    ]);
  } catch (\Exception $e) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Error deleteing node',
    ], 500);
  }
}
```

**Flow:**

```
1. User clicks "Delete" button in Views table
2. JavaScript shows confirmation dialog
3. User confirms
4. JavaScript sends AJAX POST: { nid: 123 }
5. DeleteController::ajaxDelete() executes
6. Check access
7. Delete node: $node->delete()
8. Return success JSON
9. JavaScript receives response
10. Shows "Deleted successfully" notification
11. Re loads page (row disappears from table)
```

### 6.6 AddController.php

**Purpose:** Handles "Add Contact"/"Add Deal" functionality

#### Method: `addPage()`

```php
public function addPage($type) {
  // Render full-page add form
  return [
    '#markup' => '<div>Add form for ' . $type . '</div>',
  ];
}
```

**Note:** This seems to be a stub. Full implementation might render Drupal's node add form.

Better implementation:

```php
public function addPage($type) {
  $entity_manager = \Drupal::entityTypeManager();
  $node = $entity_manager->getStorage('node')->create(['type' => $type]);

  $form = \Drupal::service('entity.form_builder')->getForm($node);

  return $form;
}
```

### 6.7 CrmEditLink.php (Views Plugin)

**Location:** `src/Plugin/views/field/CrmEditLink.php`

**Purpose:** Custom Views field that adds "Edit | Delete" links to Views tables

**Annotation:**

```php
/**
 * Field handler to show CRM edit link.
 *
 * @ViewsField("crm_edit_link")
 */
class CrmEditLink extends FieldPluginBase {
```

**Key Methods:**

#### `query()`

```php
public function query() {
  // No query changes needed
  // This field doesn't add JOINs or WHERE clauses
}
```

#### `render()`

```php
public function render(ResultRow $values) {
  $entity = $values->_entity;

  if (!$entity || $entity->getEntityTypeId() !== 'node') {
    return '';
  }

  $bundle = $entity->bundle();
  $crm_types = ['contact', 'deal', 'organization', 'activity'];

  if (!in_array($bundle, $crm_types)) {
    return '';
  }

  $account = \Drug::currentUser();
  $nid = $entity->id();
  $title = htmlspecialchars($entity->getTitle() ?? '', ENT_QUOTES);

  // Check if user can edit
  $can_edit = $this->checkPermission($account, $entity, $bundle, 'edit');

  // Check if user can delete
  $can_delete = $this->checkPermission($account, $entity, $bundle, 'delete');

  if (!$can_edit && !$can_delete) {
    return ''; // User can't do anything with this node
  }

  $buttons = '<div class="crm-action-buttons">';

  if ($can_edit) {
    $buttons .= '
      <button
        class="crm-action-btn crm-edit-btn"
        onclick="CRMInlineEdit.openModal(' . $nid . ', \'' . $bundle . '\')"
        title="Quick Edit"
        type="button">
        <i data-lucide="edit-2"></i>
        <span>Edit</span>
      </button>';
  }

  if ($can_delete) {
    $buttons .= '
      <button
        class="crm-action-btn crm-delete-btn"
        onclick="CRMInlineEdit.confirmDelete(' . $nid . ', \'' . $bundle . '\', \'' . $title . '\')"
        title="Delete"
        type="button">
        <i data-lucide="trash-2"></i>
        <span>Delete</span>
      </button>';
  }

  $buttons .= '</div>';

  return [
    '#markup' => Markup::create($buttons),
    '#attached' => [
      'library' => [
        'crm_edit/lucide',
        'crm_edit/inline_edit',
      ],
    ],
  ];
}
```

**How to use in Views:**

1. Edit view: `/admin/structure/views/view/my_contacts/edit`
2. Add field: "CRM Edit Link"
3. Field identifier: `crm_edit_link`
4. Save view

**Result:** Every row in the contact table will have "Edit | Delete" buttons

### 6.8 inline-edit.js (Complete Walkthrough)

**Location:** `js/inline-edit.js`

**Purpose:** Frontend logic for modal interactions

**Structure:**

```javascript
window.CRMInlineEdit = {
  openModal: function (nid, type) { },
  closeModal: function () { },
  setupModalHandlers: function () { },
  saveModal: function (form) { },
  confirm Delete: function (nid, type, title) { },
  showMessage: function (message, type) { }
};
```

#### Function: `openModal(nid, type)`

```javascript
openModal: function (nid, type) {
  // Step 1: Show loading overlay
  const loadingHtml = `
    <div class="crm-modal-overlay" id="crm-modal-overlay">
      <div class="crm-modal-loading">
        <div class="spinner"></div>
        <p>Loading...</p>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML("beforeend", loadingHtml);

  // Step 2: Fetch modal form via AJAX
  fetch("/crm/edit/modal/form?nid=" + nid + "&type=" + type)
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Step 3: Replace loading with actual modal
        const overlay = document.getElementById("crm-modal-overlay");
        overlay.innerHTML = data.html;

        // Step 4: Initialize Lucide icons
        if (typeof lucide !== "undefined") {
          lucide.createIcons();
        }

        // Step 5: Setup event handlers
        this.setupModalHandlers();
      } else {
        alert("Error loading form: " + (data.message || "Unknown error"));
        this.closeModal();
      }
    })
    .catch((error) => {
      console.error("Modal load error:", error);
      alert("Failed to load edit form. Please try again.");
      this.closeModal();
    });
},
```

**Explanation:**

```
1. User clicks "Edit" button
2. openModal(123, 'contact') is called
3. Loading spinner displayed
4. AJAX GET request: /crm/edit/modal/form?nid=123&type=contact
5. Backend returns: { success: true, html: "<div>...</div>" }
6. JavaScript receives response
7. Replaces loading spinner with actual form HTML
8. Initializes Lucide icons (renders <i data-lucide="edit-2"> as SVG)
9. Sets up event handlers (close button, save button, ESC key)
```

#### Function: `closeModal()`

```javascript
closeModal: function () {
  const overlay = document.getElementById("crm-modal-overlay");
  if (overlay) {
    // Add closing animation
    overlay.classList.add("closing");

    // Remove after animation completes
    setTimeout(() => overlay.remove(), 300);
  }
},
```

**CSS for animation:**

```css
.crm-modal-overlay {
  opacity: 1;
  transition: opacity 0.3s ease;
}

.crm-modal-overlay.closing {
  opacity: 0;
}
```

#### Function: `setupModalHandlers()`

```javascript
setupModalHandlers: function () {
  const overlay = document.getElementById("crm-modal-overlay");
  if (!overlay) return;

  // Close on overlay click (click outside modal)
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) {
      this.closeModal();
    }
  });

  // Close button
  const closeBtn = overlay.querySelector(".crm-modal-close");
  if (closeBtn) {
    closeBtn.addEventListener("click", () => this.closeModal());
  }

  // Cancel button
  const cancelBtn = overlay.querySelector(".btn-cancel");
  if (cancelBtn) {
    cancelBtn.addEventListener("click", () => this.closeModal());
  }

  // Form submission
  const form = overlay.querySelector(".crm-modal-form");
  if (form) {
    form.addEventListener("submit", (e) => {
      e.preventDefault(); // Prevent default form submission
      this.saveModal(form);
    });
  }

  // ESC key to close
  const escHandler = (e) => {
    if (e.key === "Escape") {
      this.closeModal();
      document.removeEventListener("keydown", escHandler);
    }
  };
  document.addEventListener("keydown", escHandler);
},
```

**Event handlers registered:**

1. **Click outside modal** → Close
2. **Close button (X icon)** → Close
3. **Cancel button** → Close
4. **Form submit** → Prevent default, call saveModal()
5. **ESC key** → Close

#### Function: `saveModal(form)`

```javascript
saveModal: function (form) {
  if (!form) {
    form = document.querySelector("#crm-modal-overlay form");
  }
  if (!form) return;

  // Collect form data
  const formData = new FormData(form);
  const nid = form.dataset.nid;
  const type = form.dataset.type;

  // Convert FormData to JSON object
  const jsonData = {
    nid: nid,
    type: type,
  };

  for (let [key, value] of formData.entries()) {
    jsonData[key] = value;
  }

  // Show saving state
  const modal = document.querySelector(".crm-modal-container");
  if (modal) {
    modal.classList.add("is-saving");
  }

  // Submit via AJAX
  fetch("/crm/edit/ajax/save", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(jsonData),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Success!
        this.showMessage("Changes saved successfully!", "success");
        this.closeModal();

        // Reload page to show updated data
        setTimeout(() => location.reload(), 500);
      } else {
        // Error
        this.showMessage(data.message || "Error saving changes", "error");
        if (modal) {
          modal.classList.remove("is-saving");
        }
      }
    })
    .catch((error) => {
      console.error("Save error:", error);
      this.showMessage("Failed to save changes. Please try again.", "error");
      if (modal) {
        modal.classList.remove("is-saving");
      }
    });
},
```

**Step-by-step:**

```
1. User clicks "Save" button
2. Form submit event triggered
3. preventDefault() stops normal form submission
4. saveModal() is called
5. Collect form data: { nid: 123, type: 'contact', field_email: '...', field_phone: '...' }
6. Convert to JSON
7. Add "is-saving" class (shows spinner on button)
8. AJAX POST to /crm/edit/ajax/save
9. Backend processes: InlineEditController::ajaxSave()
10. Response: { success: true, message: '...' }
11. If success:
    - Show green notification "Saved!"
    - Close modal
    - Reload page (to update table with new values)
12. If error:
    - Show red notification with error message
    - Keep modal open
    - Remove "is-saving" class
```

#### Function: `confirmDelete(nid, type, title)`

```javascript
confirmDelete: function (nid, type, title) {
  // Show browser confirmation dialog
  if (!confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
    return; // User cancelled
  }

  // Send delete request
  fetch("/crm/edit/ajax/delete", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ nid: nid }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        this.showMessage(data.message || "Deleted successfully", "success");
        // Reload page to remove deleted row
        setTimeout(() => location.reload(), 500);
      } else {
        this.showMessage(data.message || "Error deleting", "error");
      }
    })
    .catch((error) => {
      console.error("Delete error:", error);
      this.showMessage("Failed to delete. Please try again.", "error");
    });
},
```

**UX Flow:**

```
1. User clicks "Delete" button on contact row
2. Browser shows confirmation: "Are you sure you want to delete 'John Doe'?"
3. User clicks "OK"
4. AJAX POST to /crm/edit/ajax/delete with { nid: 123 }
5. Backend: DeleteController::ajaxDelete()
6. Node deleted from database
7. Response: { success: true, message: "'John Doe' has been deleted" }
8. JavaScript shows green notification
9. Page reloads
10. Contact no longer appears in table
```

#### Function: `showMessage(message, type)`

```javascript
showMessage: function (message, type) {
  // Create toast notification
  const toast = document.createElement("div");
  toast.className = `crm-toast crm-toast-${type}`;
  toast.textContent = message;

  document.body.appendChild(toast);

  // Show notification
  setTimeout(() => toast.classList.add("show"), 100);

  // Auto-dismiss after 3 seconds
  setTimeout(() => {
    toast.classList.remove("show");
    setTimeout(() => toast.remove(), 300);
  }, 3000);
},
```

**CSS for toast:**

```css
.crm-toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 12px 24px;
  border-radius: 8px;
  color: white;
  font-weight: 500;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.3s ease;
  z-index: 10000;
}

.crm-toast.show {
  opacity: 1;
  transform: translateY(0);
}

.crm-toast-success {
  background: #10b981;
}

.crm-toast-error {
  background: #ef4444;
}
```

### 6.9 inline-edit.css

**Location:** `css/inline-edit.css`

**Key styles:**

```css
/* Modal Overlay */
.crm-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

/* Modal Container */
.crm-modal-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

/* Modal Header */
.crm-modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
}

/* Modal Body */
.crm-modal-body {
  padding: 24px;
}

/* Form Fields */
.crm-field-group {
  margin-bottom: 20px;
}

.crm-field-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #374151;
}

.crm-field-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.crm-field-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Modal Footer (Buttons) */
.crm-modal-footer {
  display: flex;
  gap: 12px;
  padding: 20px 24px;
  border-top: 1px solid #e5e7eb;
  justify-content: flex-end;
}

/* Buttons */
.btn-save {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-save:hover {
  background: #2563eb;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

/* Saving State */
.crm-modal-container.is-saving .btn-save {
  opacity: 0.6;
  cursor: not-allowed;
  position: relative;
}

.crm-modal-container.is-saving .btn-save::after {
  content: "";
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to {
    transform: translateY(-50%) rotate(360deg);
  }
}

/* Action Buttons (in Views table) */
.crm-action-buttons {
  display: flex;
  gap: 8px;
}

.crm-action-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
}

.crm-action-btn:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.crm-edit-btn:hover {
  background: #eff6ff;
  border-color: #3b82f6;
  color: #3b82f6;
}

.crm-delete-btn:hover {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}
```

### 6.10 Summary: Edit Module

**What it provides:**

- ✅ Inline editing for all CRM types
- ✅ Modal forms (no page reload)
- ✅ AJAX save/delete
- ✅ Real-time validation (planned)
- ✅ Access control integration
- ✅ Custom Views field for edit links

**Architecture quality:**

- ✅ Clean controller separation (Inline, Modal, Delete, Add)
- ✅ Reusable JavaScript object (`CRMInlineEdit`)
- ✅ Good error handling
- ⚠️ Controllers generate HTML directly (should use Twig)
- ⚠️ No service classes (all logic in controllers)
- ⚠️ Real-time validation not implemented (`ajaxValidate()` is stub)

**How to extend:**

1. Add new CRM type (e.g., "Project"):
   - Add route: `crm_edit.edit_project`
   - Add method: `InlineEditController::editProject()`
   - Update `CrmEditLink.php` to include 'project' in `$crm_types`
   - JavaScript will automatically work

2. Add custom validation:
   - Implement `ajaxValidate()` in `InlineEditController`
   - Call from JavaScript before `saveModal()`

3. Add bulk edit:
   - New controller: `BulkEditController`
   - Accept array of node IDs
   - Update multiple nodes in loop

---

## 7. Kanban Module - Pipeline Board

**Location:** `web/modules/custom/crm_kanban/`

**Purpose:** Drag-and-drop deal pipeline visualization

### 7.1 crm_kanban.info.yml

```yaml
name: "CRM Kanban Pipeline"
type: module
description: "Kanban board for managing deals with drag-and-drop functionality"
package: "CRM"
core_version_requirement: ^11
```

### 7.2 crm_kanban.routing.yml

```yaml
crm_kanban.pipeline:
  path: "/crm/pipeline"
  defaults:
    _controller: '\Drupal\crm_kanban\Controller\KanbanController::view'
    _title: "Sales Pipeline"
  requirements:
    _permission: "access content"

crm_kanban.all_pipeline:
  path: "/crm/all-pipeline"
  defaults:
    _controller: '\Drupal\crm_kanban\Controller\KanbanController::view'
    _title: "All Pipeline"
  requirements:
    _permission: "access content"

crm_kanban.update_stage:
  path: "/crm/pipeline/update-stage"
  defaults:
    _controller: '\Drupal\crm_kanban\Controller\KanbanController::updateStage'
  requirements:
    _permission: "access content"
  options:
    no_cache: TRUE
```

**3 routes:**

1. `/crm/pipeline` - User's own deals
2. `/crm/all-pipeline` - All deals (admin view)
3. `/crm/pipeline/update-stage` - AJAX endpoint for drag-and-drop

### 7.3 KanbanController.php (Complete Walkthrough)

**Size:** 1409 lines (another very long controller!)

#### Method: `view()`

**Purpose:** Renders Kanban board HTML

**Step-by-step logic:**

```php
public function view() {
  // STEP 1: Get current user
  $current_user = \Drupal::currentUser();
  $user_id = $current_user->id();

  // STEP 2: Check if admin
  $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;

  // STEP 3: Load pipeline stages from taxonomy
  $stage_terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'pipeline_stage']);

  $stages = [];
  $color_palette = [
    '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#10b981', '#ef4444',
    // ... more colors
  ];
  $color_index = 0;

  foreach ($stage_terms as $term) {
    $stage_id = $term->id();
    $stage_name = $term->getName();
    $stages[$stage_id] = [
      'name' => $stage_name,
      'color' => $color_palette[$color_index % count($color_palette)],
    ];
    $color_index++;
  }

  // STEP 4: Get deals grouped by stage
  $deals_by_stage = [];
  $totals_by_stage = [];

  foreach ($stages as $stage_id => $stage_info) {
    // Query deals in this stage
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_stage', $stage_id)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');

    // Filter by owner for non-admins
    if (!$is_admin) {
      $query->condition('field_owner', $user_id);
    }

    $nids = $query->execute();
    $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);

    $deals_by_stage[$stage_id] = [];
    $totals_by_stage[$stage_id] = 0;

    // STEP 5: For each deal, extract data
    foreach ($deals as $deal) {
      $value = $deal->get('field_amount')->value ?? 0;
      $totals_by_stage[$stage_id] += $value;

      // Get organization name
      $org_name = '';
      if ($deal->hasField('field_organization') && !$deal->get('field_organization')->isEmpty()) {
        $org = $deal->get('field_organization')->entity;
        if ($org) {
          $org_name = $org->getTitle();
        }
      }

      // Get owner name
      $owner_name = '';
      if ($deal->hasField('field_owner') && !$deal->get('field_owner')->isEmpty()) {
        $owner = $deal->get('field_owner')->entity;
        if ($owner) {
          $owner_name = $owner->getDisplayName();
        }
      }

      $deals_by_stage[$stage_id][] = [
        'nid' => $deal->id(),
        'title' => $deal->getTitle(),
        'value' => $value,
        'organization' => $org_name,
        'owner' => $owner_name,
      ];
    }
  }

  // STEP 6: Build Kanban HTML
  $html = $this->buildKanbanHtml($stages, $deals_by_stage, $totals_by_stage);

  return [
    '#markup' => Markup::create($html),
    '#attached' => [
      'library' => ['core/drupal'],
    ],
  ];
}
```

**Database queries executed:**

```sql
-- Query 1: Load pipeline stages
SELECT * FROM taxonomy_term_field_data WHERE vid = 'pipeline_stage'

-- Query 2-N: For each stage, get deals
SELECT nid FROM node_field_data nfd
LEFT JOIN node__field_stage stage ON nfd.nid = stage.entity_id
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
WHERE nfd.type = 'deal'
  AND stage.field_stage_target_id = 1  -- Stage ID
  AND owner.field_owner_target_id = 5  -- Current user (if not admin)
ORDER BY nfd.created DESC

-- Repeated for each stage...
```

**Performance consideration:**

- If 6 stages exist, this executes 7 queries (1 for stages + 6 for deals)
- For large datasets, could be optimized with a single query + grouping

#### Method: `buildKanbanHtml()`

**Purpose:** Generates HTML for Kanban board

**Structure:**

```html
<!DOCTYPE html>
<html>
  <head>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
      /* Embedded CSS for Kanban board */
      .kanban-container { ... }
      .kanban-board { ... }
      .kanban-column { ... }
      .kanban-card { ... }
    </style>
  </head>
  <body>
    <div class="kanban-container">
      <div class="kanban-header">
        <h1>Sales Pipeline</h1>
        <div class="kanban-actions">
          <button onclick="location.href='/crm/add/deal'">
            <i data-lucide="plus"></i>
            Add Deal
          </button>
        </div>
      </div>

      <div class="kanban-board">
        <!-- Column for each stage -->
        <?php foreach ($stages as $stage_id => $stage_info): ?>
        <div class="kanban-column" data-stage-id="<?= $stage_id ?>">
          <div
            class="kanban-column-header"
            style="border-color: <?= $stage_info['color'] ?>"
          >
            <h3><?= $stage_info['name'] ?></h3>
            <span class="deal-count"
              ><?= count($deals_by_stage[$stage_id]) ?></span
            >
          </div>

          <div class="kanban-column-total">
            Total: $<?= number_format($totals_by_stage[$stage_id] / 1000, 0) ?>K
          </div>

          <div class="kanban-cards" data-stage-id="<?= $stage_id ?>">
            <!-- Deal cards -->
            <?php foreach ($deals_by_stage[$stage_id] as $deal): ?>
            <div class="kanban-card" data-deal-id="<?= $deal['nid'] ?>">
              <div class="card-header">
                <h4><?= htmlspecialchars($deal['title']) ?></h4>
                <div class="card-menu">
                  <button onclick="openDealMenu(<?= $deal['nid'] ?>)">
                    <i data-lucide="more-vertical"></i>
                  </button>
                </div>
              </div>

              <div class="card-body">
                <div class="card-value">
                  $<?= number_format($deal['value'] / 1000, 1) ?>K
                </div>

                <?php if ($deal['organization']): ?>
                <div class="card-org">
                  <i data-lucide="building-2"></i>
                  <?= htmlspecialchars($deal['organization']) ?>
                </div>
                <?php endif; ?>

                <div class="card-owner">
                  <i data-lucide="user"></i>
                  <?= htmlspecialchars($deal['owner']) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <script>
      // Initialize Lucide icons
      lucide.createIcons();

      // Initialize Sortable.js for drag-and-drop
      document.querySelectorAll(".kanban-cards").forEach(function (column) {
        Sortable.create(column, {
          group: "kanban",
          animation: 150,
          ghostClass: "sortable-ghost",
          dragClass: "sortable-drag",
          onEnd: function (evt) {
            const dealId = evt.item.dataset.dealId;
            const newStageId = evt.to.dataset.stageId;

            // Send AJAX request to update stage
            fetch("/crm/pipeline/update-stage", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
              },
              body: JSON.stringify({
                deal_id: dealId,
                new_stage: newStageId,
              }),
            })
              .then((response) => response.json())
              .then((data) => {
                if (data.success) {
                  // Success - stage updated
                  console.log("Deal moved successfully");

                  // Reload page to update totals
                  location.reload();
                } else {
                  // Error - revert drag
                  alert("Error moving deal: " + data.message);
                  location.reload();
                }
              })
              .catch((error) => {
                console.error("Error:", error);
                alert("Failed to update deal stage");
                location.reload();
              });
          },
        });
      });
    </script>
  </body>
</html>
```

**Key components:**

1. **Kanban Container** - Main wrapper
2. **Kanban Header** - Title + "Add Deal" button
3. **Kanban Board** - Horizontal scrolling container
4. **Kanban Columns** - One per pipeline stage
5. **Kanban Cards** - Individual deals (draggable)
6. **Sortable.js** - Enables drag-and-drop
7. **AJAX on drop** - Updates deal stage

#### Method: `updateStage()`

**Purpose:** Receives AJAX POST when deal is dragged to new column

```php
public function updateStage(Request $request) {
  // Get POST data
  $data = json_decode($request->getContent(), TRUE);

  $deal_id = $data['deal_id'] ?? NULL;
  $new_stage = $data['new_stage'] ?? NULL;

  if (!$deal_id || !$new_stage) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Missing deal ID or stage',
    ], 400);
  }

  // Load deal node
  $deal = \Drupal::entityTypeManager()->getStorage('node')->load($deal_id);

  if (!$deal) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Deal not found',
    ], 404);
  }

  // Check if user can edit this deal
  if (!$deal->access('update')) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Access denied',
    ], 403);
  }

  // Update stage field
  try {
    $deal->set('field_stage', $new_stage);
    $deal->save();

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Deal stage updated',
      'deal_id' => $deal_id,
      'new_stage' => $new_stage,
    ]);
  } catch (\Exception $e) {
    \Drupal::logger('crm_kanban')->error('Error updating deal stage: @message', [
      '@message' => $e->getMessage(),
    ]);

    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Error updating stage',
    ], 500);
  }
}
```

**Flow:**

```
1. User drags deal card from "Qualified" to "Proposal Sent"
2. Sortable.js fires `onEnd` event
3. JavaScript extracts:
   - dealId from data-deal-id attribute
   - newStageId from data-stage-id attribute of target column
4. AJAX POST to /crm/pipeline/update-stage
5. Backend: KanbanController::updateStage()
6. Load deal node
7. Check access (user must own deal or be admin)
8. Update: $deal->set('field_stage', $new_stage_id)
9. Save: $deal->save()
10. Return JSON: {success: true}
11. JavaScript receives response
12. Reload page (to update column totals)
```

**Why reload page after update?**

- Column totals need recalculation
- Could be improved: Update totals client-side without reload
- Trade-off: Simplicity vs UX

### 7.4 CSS Highlights

```css
/* Kanban Board Layout */
.kanban-board {
  display: flex;
  gap: 20px;
  overflow-x: auto;
  padding-bottom: 20px;
}

/* Each Column */
.kanban-column {
  flex: 0 0 320px;
  background: #f9fafb;
  border-radius: 8px;
  padding: 16px;
}

/* Column Header */
.kanban-column-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 12px;
  border-bottom: 3px solid;
  margin-bottom: 16px;
}

/* Deal Cards Container */
.kanban-cards {
  min-height: 100px;
  max-height: calc(100vh - 300px);
  overflow-y: auto;
}

/* Individual Deal Card */
.kanban-card {
  background: white;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  cursor: move;
  transition: all 0.2s;
}

.kanban-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

/* Sortable.js States */
.sortable-ghost {
  opacity: 0.4;
  background: #e5e7eb;
}

.sortable-drag {
  cursor: grabbing;
  transform: rotate(5deg);
}
```

### 7.5 Summary: Kanban Module

**What it does well:**

- ✅ Visual pipeline representation
- ✅ Drag-and-drop UX (Sortable.js)
- ✅ Column totals
- ✅ Deal cards with key info
- ✅ Access control integration

**What needs improvement:**

- ⚠️ 1409-line controller (separate HTML generation)
- ⚠️ Page reload after drag (could update client-side)
- ⚠️ No real-time updates (if another user moves a deal, not reflected)
- ⚠️ No search/filter on Kanban
- ⚠️ Embedded CSS/JS (use external files)

**Feature ideas:**

- Add filters: Show only my deals, or deals > $100K
- Add search bar
- Use WebSockets for real-time updates
- Add "Split view": Kanban + List toggle
- Add deal creation directly in column (inline add card)

---

_Due to length, continuing in next response with sections 8-17..._

Would you like me to continue with the remaining sections? I'll continue with:

- Import/Export Module
- All Other Modules
- Complete Page Breakdown
- Views Deep Dive
- CSS System
- JavaScript Complete Guide
- Data Flow Scenarios
- Permission System
- Feature Map
- Improvement Suggestions
