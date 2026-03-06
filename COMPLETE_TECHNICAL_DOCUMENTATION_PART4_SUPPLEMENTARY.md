# Open CRM - Complete Technical Documentation (Part 4: Supplementary)

_Advanced sections and missing module documentation_

**Document Version:** 3.0  
**Platform:** Drupal 10/11  
**Date:** March 6, 2026  
**For:** Senior Developers, Architects, System Maintenance

---

## Table of Contents

1. [Missing Modules Complete Documentation](#1-missing-modules-complete-documentation)
2. [Teams Module - Team-Based Access Control](#2-teams-module---team-based-access-control)
3. [Activity Log Module - Timeline & Tracking](#3-activity-log-module---timeline--tracking)
4. [Contact 360 Module - Enhanced Detail View](#4-contact-360-module---enhanced-detail-view)
5. [Additional Modules Overview](#5-additional-modules-overview)
6. [Complete Data Flow Scenarios](#6-complete-data-flow-scenarios)
7. [Permission System Deep Dive](#7-permission-system-deep-dive)
8. [Feature Location Map](#8-feature-location-map)
9. [API Endpoints Reference](#9-api-endpoints-reference)
10. [Performance Optimization Guide](#10-performance-optimization-guide)
11. [Architecture Improvements](#11-architecture-improvements)
12. [Security Analysis & Best Practices](#12-security-analysis--best-practices)

---

# 1. Missing Modules Complete Documentation

## 1.1 Module Overview

The CRM system contains **16 custom modules**. Previously documented modules:

- ✅ crm (Core)
- ✅ crm_dashboard
- ✅ crm_edit
- ✅ crm_kanban
- ✅ crm_import_export

This section documents the remaining **11 modules**.

---

# 2. Teams Module - Team-Based Access Control

## 2.1 Module Overview

**Module:** `crm_teams`  
**Path:** `web/modules/custom/crm_teams/`  
**Purpose:** Implement team-based data access control to restrict CRM records visibility

### Problem Solved

Single database, but data should be siloed by team:

- Sales Team A should NOT see Sales Team B's contacts
- Resources are grouped into teams
- Access control enforced via `crm_teams_node_access()` hook

### Team Assignment Model

```php
// User ← Team Assignment → Team
// Contact ← Owner (User) ← Team
// Deal ← Owner (User) ← Team
```

**Data structure:**

```
user_field_data
├── uid (user ID)
├── name (username)
└── [no direct team field]

user__field_team (parallel storage)
├── entity_id (uid)
└── field_team_target_id (team taxonomy ID)

node__field_owner (ownership field)
├── entity_id (nid of contact/deal)
└── field_owner_target_id (uid)

taxonomy_term_field_data (teams)
├── tid (team ID)
├── name (team name: "Team A", "Team B")
└── vid=teams (vocabulary)
```

---

## 2.2 Key Components

### crm_teams.module

```php
<?php

/**
 * @file
 * CRM Teams module - Team-based access control.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Implements hook_node_access().
 *
 * CRITICAL FUNCTION: Enforces team-based access control
 *
 * Execution flow:
 * 1. User tries to access contact/deal/organization/activity
 * 2. Drupal calls node_access hooks (this one)
 * 3. We retrieve user's team + owner's team
 * 4. Compare teams:
 *    - Same team → ALLOWED
 *    - Different team → FORBIDDEN
 *    - No teams → NEUTRAL (fall through to other access control)
 */
function crm_teams_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $bundle = $node->bundle();

  // STEP 1: Only apply to CRM content types
  if (!in_array($bundle, ['contact', 'deal', 'organization', 'activity'])) {
    return AccessResult::neutral();  // Let other modules decide
  }

  // STEP 2: Admin users bypass team restrictions
  if ($account->hasPermission('bypass crm team access')) {
    return AccessResult::neutral();  // Let other access control apply
  }

  // STEP 3: Get user's team assignment
  $user_team_id = _crm_teams_get_user_team($account->id());

  // STEP 4: Get owner's team (through owner user)
  $owner_team_id = _crm_teams_get_entity_owner_team($node);

  // STEP 5: If no team assignment, allow access (backward compatibility)
  if (empty($user_team_id) || empty($owner_team_id)) {
    return AccessResult::neutral();
  }

  // STEP 6: Compare teams for view operation
  if ($op === 'view') {
    if ($user_team_id === $owner_team_id) {
      // Same team - allow access
      return AccessResult::allowed()
        ->cachePerUser()  // Cache is different per user
        ->addCacheableDependency($node);  // Invalidate when node changes
    } else {
      // Different team - deny access
      return AccessResult::forbidden('Access denied: different team')
        ->cachePerUser()
        ->addCacheableDependency($node);
    }
  }

  // STEP 7: Other operations (edit, delete): return neutral
  // (Other modules like crm.module handle these)
  return AccessResult::neutral();
}

/**
 * Helper: Get user's team ID
 *
 * @param int $uid
 *   User ID
 *
 * @return int|null
 *   Team ID or NULL if not assigned to a team
 */
function _crm_teams_get_user_team($uid) {
  $user = \Drupal\user\Entity\User::load($uid);
  if (!$user || !$user->hasField('field_team')) {
    return NULL;
  }

  $team_ref = $user->get('field_team');
  if ($team_ref->isEmpty()) {
    return NULL;
  }

  // Returns the taxonomy term ID of the team
  return $team_ref->target_id;
}

/**
 * Helper: Get owner's team (through owner's user record)
 *
 * @param Drupal\node\NodeInterface $node
 *   The node to check
 *
 * @return int|null
 *   Team ID or NULL
 */
function _crm_teams_get_entity_owner_team(NodeInterface $node) {
  // Get owner user
  $owner = $node->getOwner();
  if (!$owner || !$owner->hasField('field_team')) {
    return NULL;
  }

  $team_ref = $owner->get('field_team');
  if ($team_ref->isEmpty()) {
    return NULL;
  }

  return $team_ref->target_id;
}
```

**Examples:**

```
SCENARIO 1: User in Team A tries to view Contact owned by Team A
────────────────────────────────────────────────────────────────
User: john (uid=5) → field_team = Team A (tid=12)
Contact: "ACME Corp" (nid=123) → field_owner = john (uid=5)
                                field_owner.team = Team A (tid=12)

Execution:
1. john tries to view node 123
2. crm_teams_node_access() called
3. $user_team_id = 12 (from john's field_team)
4. $owner_team_id = 12 (from john's field_team)
5. 12 === 12 → ALLOWED ✓

john CAN view the contact.


SCENARIO 2: User in Team B tries to view Contact owned by Team A
────────────────────────────────────────────────────────────────
User: jane (uid=6) → field_team = Team B (tid=13)
Contact: "ACME Corp" (nid=123) → field_owner = john (uid=5)
                                field_owner.team = Team A (tid=12)

Execution:
1. jane tries to view node 123
2. crm_teams_node_access() called
3. $user_team_id = 13 (from jane's field_team)
4. $owner_team_id = 12 (from john's field_team)
5. 13 !== 12 → FORBIDDEN ✗

jane CANNOT view the contact.
```

---

### crm_teams.routing.yml

```yaml
crm_teams.management:
  path: "/admin/crm/teams"
  defaults:
    _controller: '\Drupal\crm_teams\Controller\TeamsManagementController::manage'
    _title: "Manage Teams"
  requirements:
    _permission: "administer crm teams"

crm_teams.assign_users:
  path: "/admin/crm/teams/{team}/assign"
  defaults:
    _controller: '\Drupal\crm_teams\Controller\TeamsManagementController::assignUsers'
    _title: "Assign Users"
  requirements:
    _permission: "administer crm teams"
```

---

### crm_teams.permissions.yml

```yaml
administer crm teams:
  title: "Administer CRM Teams"
  description: "Create, edit, delete teams and assign users to teams"
  restrict access: true

bypass crm team access:
  title: "Bypass CRM Team Access Control"
  description: "Ignore team restrictions and access all records"
  restrict access: true

view all team records:
  title: "View All Team Records"
  description: "View records from all teams"
  restrict access: false
```

---

## 2.3 TeamsManagementController.php

**Purpose:** Admin interface for team management

**Methods:**

```php
/**
 * Management dashboard
 */
public function manage() {
  // List all teams
  $teams = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'teams']);

  // Build team cards with user count
  $teams_data = [];
  foreach ($teams as $team) {
    $user_count = \Drupal::database()
      ->query('SELECT COUNT(*) FROM {user__field_team} WHERE field_team_target_id = :tid', [':tid' => $team->id()])
      ->fetchField();

    $teams_data[] = [
      'name' => $team->getName(),
      'tid' => $team->id(),
      'user_count' => $user_count,
    ];
  }

  return [
    '#theme' => 'teams_management',
    '#teams' => $teams_data,
    '#attached' => ['library' => ['crm_teams/teams_admin']],
  ];
}

/**
 * Assign users to team form
 */
public function assignUsers($team) {
  // Build form to assign users to this team
  // Form elements rendered on POST to update field_team on user entities
}
```

---

## 2.4 Permissions Assignment

### Role Mapping

```yaml
# In role configuration (admin UI)

ROLE: "Sales Manager"
Permissions:
  - "view own crm nodes"
  - "edit own crm nodes"
  - "create contact content"
  - "view all team records"  ← Can see team's records
  ✗ "bypass crm team access"  ← Cannot see other teams

ROLE: "Administrator"
Permissions:
  - [ALL PERMISSIONS]
  - "bypass crm team access" ✓  ← Can see everything
  - "administer crm teams" ✓  ← Manage teams

ROLE: "Sales Rep"
Permissions:
  - "view own crm nodes"
  - "create contact content"
  ✗ Cannot see other team's records
```

---

## 2.5 Team Assignment Flow

### Scenario: Add user to Team A

```
ADMIN INTERFACE
  ↓
1. Admin visits /admin/crm/teams/12/assign
2. Form lists all users
3. Admin selects: John, Jane, Bob
4. Admin submits form
  ↓
BACKEND PROCESSING
  ↓
5. Form submission handler:
   - For John: Update field_team = Team A (tid=12)
   - For Jane: Update field_team = Team A (tid=12)
   - For Bob: Update field_team = Team A (tid=12)
  ↓
RESULT
  ↓
6. All three users:
   - field_team now points to Team A
   - Can now access Team A's records
   - Cannot access other teams' records
```

---

## 2.6 Query Alteration

**File:** Not shown in code, but implemented in views configuration

**Problem:** Views must also respect team access control

**Solution:** Use Views contextual filter + owner field

```yaml
# In views.view.my_contacts.yml
arguments:
  field_owner_target_id:
    # This ensures view only shows records owned by current user
    # which implicitly respects team access (if users are in same team)
    default_argument_type: current_user
```

**Alternative for admin view:**

```yaml
# In views.view.all_contacts.yml
access:
  type: role
  options:
    role:
      administrator: administrator
      sales_manager: sales_manager
      # Only admin and manager can see all_contacts
      # Regular users see my_contacts (filtered by owner)

filter:
  # Could add here: field_owner.field_team = current_user.field_team
  # To show all contacts in user's team, not just user's contacts
```

---

## 2.7 Summary: Teams Module Architecture

| Component                 | Purpose                                  | Critical? |
| ------------------------- | ---------------------------------------- | --------- |
| hook_node_access()        | Block access to different team's records | YES       |
| \_get_user_team()         | Retrieve user's team ID                  | YES       |
| \_get_entity_owner_team() | Retrieve owner's team ID                 | YES       |
| TeamsController           | Admin UI for team management             | NO        |
| Views integration         | Respect teams in view URLs               | MODERATE  |

---

# 3. Activity Log Module - Timeline & Tracking

## 3.1 Module Overview

**Module:** `crm_activity_log`  
**Path:** `web/modules/custom/crm_activity_log/`  
**Purpose:** Log and display activities (calls, emails, meetings, tasks) on contacts, deals, organizations

### Activity Types

```
CALL
  - Phone conversation
  - Duration
  - Outcome (e.g., "Interested", "Not interested", "Call back later")

EMAIL
  - Email sent/received
  - Subject
  - Date sent/received

MEETING
  - In-person or virtual meeting
  - Type (video call, in-person, phone)
  - Attendees
  - Notes

TASK
  - Follow-up action
  - Due date
  - Status (Open, Completed)
```

---

## 3.2 Content Type: Activity

**Schema:**

```yaml
Content Type: activity

Fields:
  title
    Type: String
    Example: "Follow-up call with John"

  field_type
    Type: Term Reference
    Vocabulary: activity_types (Call, Email, Meeting, Task)
    Example: tid=45 (Call)

  field_contact
    Type: Node Reference
    Target: contact nodes
    Example: References contact "John Doe"

  field_deal
    Type: Node Reference
    Target: deal nodes
    Optional

  field_organization
    Type: Node Reference
    Target: organization nodes
    Optional

  field_datetime
    Type: Date/Time
    Example: 2026-03-07 14:30:00

  field_description
    Type: Text (long)
    Example: "Discussed proposal, client seemed interested"

  field_outcome
    Type: Text
    Example: "[Outcome: Interested] Will send proposal next week"

  field_assigned_to
    Type: User Reference
    Example: References user uid=5 (Sales Rep)

  field_notes
    Type: Text (long)
    Example: "Follow up on pricing"

  created
    Type: Timestamp
    Auto-set when activity is created

  uid (owner)
    Type: User Reference
    Auto-set to current user
```

---

## 3.3 ActivityLogController.php

### Method: activityTab()

**Purpose:** Render activity timeline on contact/deal/organization page

**Usage:** Called via route `/node/{node}/activities`

**Implementation:**

```php
public function activityTab(NodeInterface $node) {
  $allowed = ['contact', 'deal', 'organization'];

  if (!in_array($node->bundle(), $allowed)) {
    throw new NotFoundHttpException();
  }

  // STEP 1: Determine reference field based on content type
  $field_map = [
    'contact' => 'field_contact',
    'deal' => 'field_deal',
    'organization' => 'field_organization',
  ];
  $ref_field = $field_map[$node->bundle()];

  // STEP 2: Query activities related to this node
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition($ref_field, $node->id())
    ->accessCheck(TRUE)  // Respect access control
    ->sort('created', 'DESC')  // Newest first
    ->range(0, 50);  // Limit to 50 for performance

  $activity_ids = $query->execute();
  $activities = Node::loadMultiple($activity_ids);

  // STEP 3: Format activities for rendering
  $items = [];
  foreach ($activities as $activity) {
    // Extract activity type
    $type = $activity->get('field_type')->value ?? '';

    // Get description
    $description = $activity->get('field_description')->value ?? '';

    // Parse outcome from description
    $outcome = '';
    if (preg_match('/\[Outcome: (.+?)\]/', $description, $m)) {
      $outcome = $m[1];
      // Remove outcome tag from description
      $description = trim(str_replace($m[0], '', $description));
    }

    $items[] = [
      'id' => $activity->id(),
      'title' => $activity->getTitle(),
      'type' => $type,
      'description' => $description,
      'outcome' => $outcome,
      'created' => date('d/m/Y H:i', $activity->getCreatedTime()),
      'author' => $activity->getOwner()->getDisplayName(),
    ];
  }

  // STEP 4: Render with theme template
  return [
    '#theme' => 'activity_log_tab',
    '#entity_type' => $node->bundle(),
    '#entity_id' => $node->id(),
    '#entity_name' => $node->getTitle(),
    '#activities' => $items,
    '#attached' => [
      'library' => ['crm_activity_log/activity_widget'],
      'css' => ['module' => ['crm_activity_log/css/activity-widget.css']],
    ],
  ];
}
```

**Rendered as:**

```html
<div class="activity-timeline">
  <h3>Lịch sử tương tác (5 activities)</h3>

  <div class="timeline-item">
    <div class="timeline-marker" style="background: #3b82f6;"></div>
    <div class="timeline-content">
      <p class="timeline-type">📞 Call</p>
      <p class="timeline-title">Follow-up call with John</p>
      <p class="timeline-description">
        Discussed proposal, client seemed interested
      </p>
      <p class="timeline-outcome">
        ✓ Interested - Will send proposal next week
      </p>
      <p class="timeline-meta">John Doe • 07/03/2026 14:30</p>
    </div>
  </div>

  <!-- More timeline items... -->
</div>
```

---

### Method: logCallForm()

**Purpose:** Quick form to log a phone call

**Route:** `/crm/activity/log-call/{contact_id}`

**Usage:**

```php
public function logCallForm($contact) {
  $contact_node = Node::load($contact);
  if (!$contact_node) {
    throw new NotFoundHttpException();
  }

  // Render form for logging call
  return [
    '#theme' => 'log_call_form',
    '#contact_id' => $contact,
    '#contact_name' => $contact_node->getTitle(),
    '#attached' => ['library' => ['crm_activity_log/activity_forms']],
  ];
}
```

**Form fields:**

```html
<form method="post" action="/crm/activity/log-call/{contact}/submit">
  <input type="hidden" name="contact_id" value="123" />

  <div class="form-group">
    <label>Contact: John Doe</label>
    <p>Read-only, shown for confirmation</p>
  </div>

  <div class="form-group">
    <label for="title">Call Notes <span class="required">*</span></label>
    <input
      type="text"
      id="title"
      name="title"
      placeholder="Brief description of call"
      required
    />
  </div>

  <div class="form-row">
    <div class="form-group">
      <label for="outcome">Outcome</label>
      <select id="outcome" name="field_outcome">
        <option value="">- Select -</option>
        <option value="Interested">Interested</option>
        <option value="Not interested">Not interested</option>
        <option value="Call back later">Call back later</option>
        <option value="Left voicemail">Left voicemail</option>
      </select>
    </div>

    <div class="form-group">
      <label for="duration">Duration (minutes)</label>
      <input
        type="number"
        id="duration"
        name="field_duration"
        min="0"
        value="15"
      />
    </div>
  </div>

  <div class="form-group">
    <label for="notes">Notes</label>
    <textarea id="notes" name="field_description" rows="4"></textarea>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Log Call</button>
    <button type="reset" class="btn btn-secondary">Clear</button>
  </div>
</form>
```

---

### Method: logCallSubmit()

**Purpose:** Process call logging form submission

**Route:** `/crm/activity/log-call/{contact}/submit` (POST)

**Implementation:**

```php
public function logCallSubmit(Request $request, $contact_id) {
  // STEP 1: Validate contact exists
  $contact = Node::load($contact_id);
  if (!$contact || $contact->bundle() !== 'contact') {
    return new JsonResponse(['error' => 'Contact not found'], 404);
  }

  // STEP 2: Get form data from POST
  $title = $request->request->get('title');
  $outcome = $request->request->get('field_outcome');
  $duration = $request->request->get('field_duration', 0);
  $notes = $request->request->get('field_description');

  // STEP 3: Validate required fields
  if (empty($title)) {
    return new JsonResponse(['error' => 'Title is required'], 400);
  }

  // STEP 4: Create activity node
  $activity = Node::create([
    'type' => 'activity',
    'title' => $title,
    'field_type' => 'call',  // Hardcoded for this method
    'field_contact' => ['target_id' => $contact_id],
    'field_datetime' => date('c'),  // Current timestamp
    'field_description' => $notes,
    'field_outcome' => $outcome,
    'field_duration' => $duration,
    'uid' => $this->currentUser()->id(),  // Current user as owner
    'status' => 1,
  ]);

  // STEP 5: Save activity
  $activity->save();

  // STEP 6: Return success response
  return new JsonResponse([
    'success' => true,
    'message' => 'Call logged successfully',
    'activity_id' => $activity->id(),
  ]);
}
```

---

## 3.4 ActivityApiController.php

**Purpose:** AJAX endpoints for activities

### Endpoint: GET /crm/activity/api/list/{entity_type}/{entity_id}

**Purpose:** Fetch activities for timeline widget (used by AJAX)

```php
public function getActivities($entity_type, $entity_id) {
  // MAP entity_type to field name
  $field_map = [
    'contact' => 'field_contact',
    'deal' => 'field_deal',
    'organization' => 'field_organization',
  ];

  if (!isset($field_map[$entity_type])) {
    return new JsonResponse(['error' => 'Invalid entity type'], 400);
  }

  // QUERY activities
  $activities = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition($field_map[$entity_type], $entity_id)
    ->sort('created', 'DESC')
    ->range(0, 50)
    ->execute();

  // FORMAT response
  $data = [];
  foreach (Node::loadMultiple($activities) as $act) {
    $data[] = [
      'id' => $act->id(),
      'title' => $act->getTitle(),
      'type' => $act->get('field_type')->value,
      'created' => $act->getCreatedTime(),
      'author' => $act->getOwner()->getDisplayName(),
    ];
  }

  return new JsonResponse($data);
}
```

---

## 3.5 Permissions

```yaml
create activity content:
  title: "Create activities"
  description: "Create call, email, meeting, task records"

edit any activity content:
  title: "Edit any activity"
  description: "Edit activities created by anyone"

edit own activity content:
  title: "Edit own activities"
  description: "Edit only your own activities"

delete any activity content:
  title: "Delete any activity"

delete own activity content:
  title: "Delete own activities"
```

---

# 4. Contact 360 Module - Enhanced Detail View

## 4.1 Module Overview

**Module:** `crm_contact360`  
**Path:** `web/modules/custom/crm_contact360/`  
**Purpose:** Enhanced 360-degree view of a contact with all related information in professional card layout

### What it provides

When user visits `/node/{contact_id}`:

```
┌──────────────────────────────────────────────────────────────┐
│  CONTACT DETAIL PAGE                                         │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌─────────────────────┐  ┌──────────────────────────────┐  │
│  │  CONTACT INFO       │  │  COMMUNICATION HISTORY       │  │
│  │                     │  │  (5 Activities)              │  │
│  │ Name: John Doe      │  │  - Call 07/03 14:30         │  │
│  │ Email: john@...     │  │  - Email 05/03 10:15        │  │
│  │ Phone: 0912345678   │  │  - Meeting 03/03 09:00      │  │
│  │ Position: Manager   │  │                              │  │
│  │ Organization: ACME  │  │  [Load More Activities ▼]    │  │
│  │                     │  │                              │  │
│  │ [EDIT] [DELETE]     │  │                              │  │
│  └─────────────────────┘  └──────────────────────────────┘  │
│                                                               │
│  ┌─────────────────────┐  ┌──────────────────────────────┐  │
│  │  RELATED DEALS      │  │  NOTES                       │  │
│  │  (3 Deals)          │  │  Internal conversation log   │  │
│  │  - Deal 1: 500K VND │  │                              │  │
│  │  - Deal 2: 300K VND │  │  [Add Note]                  │  │
│  │  - Deal 3: 100K VND │  │                              │  │
│  │  Total: 900K VND    │  │  Last note: 2026-03-07      │  │
│  │                     │  │                              │  │
│  │  [View All Deals]   │  │                              │  │
│  └─────────────────────┘  └──────────────────────────────┘  │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 4.2 Implementation

The module enhances the default node view with:

1. **Custom field display** - Override default Drupal field display
2. **Related data cards** - Show deals, activities, notes
3. **Action buttons** - Edit, Delete, Quick Add
4. **Professional styling** - Cards, spacing, typography

### Modification Flow

```php
// In crm_contact360.module

function crm_contact360_node_view_alter(&$build, NodeInterface $node) {
  if ($node->bundle() !== 'contact') {
    return;
  }

  // STEP 1: Enhance basic contact info card
  // Modify field display properties
  // Add custom themes

  // STEP 2: Inject activity timeline
  $build['activities'] = [
    '#lazy_builder' => [...],  // Lazy load activities
    '#weight' => 10,
  ];

  // STEP 3: Inject related deals card
  $build['related_deals'] = [
    '#type' => 'markup',
    '#markup' => _crm_contact360_build_deals_card($node),
    '#weight' => 20,
  ];

  // STEP 4: Attach CSS for cards styling
  $build['#attached']['library'][] = 'crm_contact360/contact_360_styles';
}
```

---

## 4.3 Custom Theme Templates

**File:** `crm_contact360/templates/contact-360-view.html.twig`

```twig
{# render contact info card with professional styling #}
<div class="contact-360-container">

  {# LEFT COLUMN #}
  <div class="col-left">
    {# CONTACT INFO CARD #}
    <div class="card contact-info-card">
      <div class="card-header">
        <h2>Contact Information</h2>
        <span class="badge" style="background: {{ color }}">{{ status }}</span>
      </div>

      <div class="card-body">
        <div class="info-item">
          <span class="label">Name</span>
          <span class="value">{{ node.title.value }}</span>
        </div>

        <div class="info-item">
          <span class="label">Email</span>
          <a href="mailto:{{ node.field_email.value }}">{{ node.field_email.value }}</a>
        </div>

        <div class="info-item">
          <span class="label">Phone</span>
          <a href="tel:{{ node.field_phone.value }}">{{ node.field_phone.value }}</a>
        </div>

        <div class="info-item">
          <span class="label">Position</span>
          <span class="value">{{ node.field_position.value }}</span>
        </div>

        <div class="info-item">
          <span class="label">Organization</span>
          <a href="/node/{{ node.field_organization.target_id }}">
            {{ node.field_organization.entity.title.value }}
          </a>
        </div>
      </div>

      <div class="card-footer">
        <button class="btn btn-primary" onclick="CRMInlineEdit.openModal({{ node.nid }}, 'contact')">
          Edit
        </button>
        <button class="btn btn-danger" onclick="CRMInlineEdit.confirmDelete({{ node.nid }})">
          Delete
        </button>
      </div>
    </div>
  </div>

  {# RIGHT COLUMN #}
  <div class="col-right">
    {# ACTIVITY TIMELINE #}
    {{ activity_timeline }}

    {# NOTES SECTION #}
    <div class="card notes-card">
      <div class="card-header">
        <h3>Internal Notes</h3>
      </div>
      <div class="card-body">
        {{ internal_notes }}
      </div>
    </div>
  </div>

</div>

{# RELATED DEALS SECTION #}
<div class="contact-360-deals">
  {{ related_deals_card }}
</div>
```

---

## 4.4 CSS Module

**File:** `crm_contact360/css/contact-360-styles.css`

```css
.contact-360-container {
  display: grid;
  grid-template-columns: 1fr 1fr; /* 2-column layout */
  gap: 24px;
  margin-bottom: 32px;
}

.contact-info-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.card-header h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: #1e293b;
}

.card-body {
  padding: 24px;
}

.info-item {
  display: grid;
  grid-template-columns: 120px 1fr;
  margin-bottom: 16px;
  gap: 12px;
}

.info-item .label {
  font-weight: 600;
  color: #64748b;
  font-size: 13px;
}

.info-item .value {
  color: #1e293b;
}

.info-item a {
  color: #3b82f6;
  text-decoration: none;
}

.info-item a:hover {
  text-decoration: underline;
}

.card-footer {
  padding: 16px 24px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 12px;
}

@media (max-width: 1024px) {
  .contact-360-container {
    grid-template-columns: 1fr; /* Stack on tablet */
  }
}
```

---

# 5. Additional Modules Overview

## 5.1 Quick-Add Module

**Module:** `crm_quickadd`  
**Purpose:** Floating action button (FAB) + modal for quickly adding contacts without leaving current page

### Features

- Floating green button in bottom-right corner
- Click opens modal with quick-add form
- Fills minimal required fields: Name, Phone, Email, Organization (optional)
- Creates contact node and saves immediately
- Shows toast notification: "Contact added successfully"

### Routes

```yaml
crm_quickadd.add_contact:
  path: "/crm/quick-add/contact"
  defaults:
    _controller: '\Drupal\crm_quickadd\Controller\QuickAddController::addContact'

crm_quickadd.add_deal:
  path: "/crm/quick-add/deal"
  defaults:
    _controller: '\Drupal\crm_quickadd\Controller\QuickAddController::addDeal'
```

---

## 5.2 Registration Module

**Module:** `crm_register`  
**Purpose:** Custom user registration form for CRM

### Features

- Custom registration page at `/user/register`
- Form fields: Email, Password, Confirm Password, Full Name, Phone (optional)
- Team assignment after registration (admin approval required)
- Email confirmation

---

## 5.3 Login Module

**Module:** `crm_login`  
**Purpose:** Custom login page styling and functionality

### Features

- Branded login page
- Form fields: Email, Password, [Remember Me], [Forgot Password?]
- Redirects after login to `/crm/dashboard` (configurable)
- Custom CSS for modern appearance

---

## 5.4 Navigation Module

**Module:** `crm_navigation`  
**Purpose:** Global navigation, breadcrumbs, back buttons

### Features

- Top navigation bar with module links
- Breadcrumb trails on detail pages
- Back buttons on modal closes
- Active state highlighting

---

## 5.5 Notifications Module

**Module:** `crm_notifications`  
**Purpose:** Email notifications and in-app alerts

### Notifications

1. **Activity mentioned** - When user is @mentioned in activity notes
2. **Deal stage changed** - When deal moves to new stage
3. **Assignment** - When contact assigned to user
4. **Task reminder** - When task is due

### Implementation

**Service:** `EmailService`

```php
class EmailService {
  public function sendNotification($user_id, $type, $data) {
    $user = User::load($user_id);

    // Build email
    $mailManager = \Drupal::service('plugin.manager.mail');
    $params = [
      'subject' => $this->getSubject($type),
      'body' => $this->getBody($type, $data),
    ];

    $mailManager->mail('crm_notifications', $type,
      $user->getEmail(), 'en', $params);
  }
}
```

---

## 5.6 Workflow Module

**Module:** `crm_workflow`  
**Purpose:** Automation and business rules

### Features

- Deal stage progression rules
- Auto-assignment based on rules
- Activity creation on specific events
- Custom workflows (future)

---

## 5.7 Actions Module

**Module:** `crm_actions`  
**Purpose:** Local Drupal actions (admin menu buttons)

### Actions

```
Views action buttons:
- "Edit" → Opens edit modal
- "Delete" → Opens delete confirmation
- "Add Activity" → Quick add activity form

View above link:
- "Add Contact" → Creates new contact
- "Add Deal" → Creates new deal
- "Export as CSV" → Downloads view as CSV
```

---

# 6. Complete Data Flow Scenarios

## Scenario 1: CREATE CONTACT

### Step-by-Step User Flow

```
1. USER CLICKS "Add Contact" BUTTON
   Location: /crm/my-contacts page

2. "ADD CONTACT" PAGE LOADS
   Route: /crm/edit/add/contact
   Controller: AddController::addPage()

3. FORM DISPLAYS
   Fields: Name*, Phone*, Email, Organization, Position, Source, Notes
   Buttons: [Cancel] [Save]

4. USER FILLS FORM
   Name: "John Doe"
   Phone: "0912345678"
   Email: "john@example.com"
   Organization: "ACME Corp" (autocomplete)

5. USER CLICKS SAVE
   Trigger: Form submission
   Method: POST to /crm/edit/ajax/save

6. BACKEND PROCESSING
   File: AddController::ajaxSave()
```

### Backend Processing (Detailed)

```php
// In AddController::ajaxSave()

POST /crm/edit/ajax/save HTTP/1.1
Content-Type: application/x-www-form-urlencoded

type=contact&title=John+Doe&field_phone=0912345678&field_email=john@example.com&field_organization=123&nid=-1&field_owner=5

────────────────────────────────────────

// STEP 1: Validate user permission
if (!currentUser->hasPermission('create contact content')) {
  return new JsonResponse(['error' => 'Access denied'], 403);
}

// STEP 2: Create node
$contact = Node::create([
  'type' => 'contact',
  'title' => 'John Doe',
  'field_phone' => '0912345678',
  'field_email' => 'john@example.com',
  'field_organization' => ['target_id' => 123],  // ACME Corp
  'field_owner' => ['target_id' => 5],  // Current user's ID
  'uid' => 5,  // Node owner = current user
  'status' => 1,
]);

// STEP 3: Trigger hook
hook_node_presave($contact);

// STEP 4: Save to database
$contact->save();  // INSERT INTO node, node_field_data, node__field_phone, etc.
// Returns contact nid=250

// STEP 5: Trigger hook
hook_node_insert($contact);

// STEP 6: Return JSON response
return new JsonResponse([
  'success' => true,
  'message' => 'Contact created successfully',
  'nid' => 250,
  'redirect' => '/crm/my-contacts'
]);
```

### Database Impact

```sql
-- NEW RECORDS CREATED

INSERT INTO node (uuid, type, created, changed, uid)
VALUES ('xxxx-xxxx', 'contact', 1709788200, 1709788200, 5);
-- Result: nid=250

INSERT INTO node_field_data (nid, vid, type, title, uid, status, created, changed, default_langcode)
VALUES (250, 250, 'contact', 'John Doe', 5, 1, 1709788200, 1709788200, 1);

INSERT INTO node__field_phone (entity_id, delta, field_phone_value)
VALUES (250, 0, '0912345678');

INSERT INTO node__field_email (entity_id, delta, field_email_value)
VALUES (250, 0, 'john@example.com');

INSERT INTO node__field_organization (entity_id, delta, field_organization_target_id)
VALUES (250, 0, 123);

INSERT INTO node__field_owner (entity_id, delta, field_owner_target_id)
VALUES (250, 0, 5);
```

### Frontend Response

```javascript
// Client receives:
{
  "success": true,
  "message": "Contact created successfully",
  "nid": 250,
  "redirect": "/crm/my-contacts"
}

// JavaScript action:
1. Show toast: "Contact created successfully" (green, 3 seconds)
2. Close modal
3. Redirect to /crm/my-contacts
4. Reload page → View now shows the new contact
```

### HTML Table Row

```html
<!-- On /crm/my-contacts view, new row appears: -->
<tr data-nid="250">
  <td>John Doe</td>
  <td>0912345678</td>
  <td>john@example.com</td>
  <td>ACME Corp</td>
  <td>07/03/2026</td>
  <td>
    <button onclick="CRMInlineEdit.openModal(250, 'contact')">Edit</button>
    <button onclick="CRMInlineEdit.confirmDelete(250)">Delete</button>
  </td>
</tr>
```

---

## Scenario 2: EDIT CONTACT

### User Flow

```
1. USER CLICKS EDIT BUTTON on contact row
   Trigger: onclick="CRMInlineEdit.openModal(250, 'contact')"

2. AJAX REQUEST SENDS
   GET /crm/edit/contact/250

3. BACKEND LOADS FORM
   Controller: InlineEditController::editContact()

4. FORM HTML RETURNED
   Contains all contact fields with current values pre-filled

5. MODAL DISPLAYS
   User sees form in overlay

6. USER EDITS FIELDS
   Change: Phone "0912345678" → "0987654321"

7. USER CLICKS SAVE
   Trigger: Form submission

8. AJAX POST /crm/edit/ajax/save
```

### Backend - Load Form

```php
// GET /crm/edit/contact/250

public function editContact($nid) {
  // STEP 1: Load contact node
  $node = Node::load(250);
  if (!$node || $node->bundle() !== 'contact') {
    return new JsonResponse(['error' => 'Not found'], 404);
  }

  // STEP 2: Check access
  if (!$node->access('view')) {
    return new JsonResponse(['error' => 'Access denied'], 403);
  }

  // STEP 3: Build form HTML with pre-filled values
  $html = "<form id='crm-edit-form' data-nid='250' data-type='contact'>";
  $html .= "<input type='text' name='title' value='{$node->getTitle()}'>";
  $html .= "<input type='text' name='field_phone' value='{$node->get('field_phone')->value}'>";
  // ... more fields ...
  $html .= "</form>";

  // STEP 4: Send form HTML as response
  return ['#markup' => $html];
}
```

### Backend - Save Changes

```php
// POST /crm/edit/ajax/save

// STEP 1: Load node
$node = Node::load(250);

// STEP 2: Check edit permission
if (!$node->access('update')) {
  return new JsonResponse(['error' => 'Access denied'], 403);
}

// STEP 3: Update fields from POST data
$node->setTitle('John Doe');  // unchanged
$node->set('field_phone', '0987654321');  // UPDATED
$node->set('field_email', 'john@example.com');  // unchanged

// STEP 4: Save
$node->save();
// UPDATE node_field_data SET ... WHERE nid=250
// UPDATE node__field_phone SET field_phone_value='0987654321' WHERE entity_id=250

// STEP 5: Return success
return new JsonResponse([
  'success' => true,
  'message' => 'Contact updated successfully'
]);
```

### Database Impact

```sql
UPDATE node_field_data
SET changed=1709788500
WHERE nid=250;

UPDATE node__field_phone
SET field_phone_value='0987654321'
WHERE entity_id=250;
```

### Frontend Response

```javascript
{
  "success": true,
  "message": "Contact updated successfully"
}

// JavaScript:
1. Show toast: "Contact updated successfully"
2. Close modal
3. Reload page to show updated phone number
```

---

## Scenario 3: DELETE CONTACT

### User Flow

```
1. USER CLICKS DELETE BUTTON
   Trigger: onclick="CRMInlineEdit.confirmDelete(250)"

2. CONFIRMATION DIALOG SHOWS
   "Are you sure you want to delete this contact?
    This action cannot be undone."
   [Cancel] [Delete]

3. USER CLICKS DELETE

4. AJAX POST /crm/edit/ajax/delete
   Payload: {nid: 250, type: 'contact'}
```

### Backend Processing

```php
// POST /crm/edit/ajax/delete

// STEP 1: Load node
$node = Node::load(250);
if (!$node) {
  return new JsonResponse(['error' => 'Not found'], 404);
}

// STEP 2: Check delete permission
if (!$node->access('delete')) {
  return new JsonResponse(['error' => 'Access denied'], 403);
}

// STEP 3: Delete
$node->delete();
// Deletes FROM node, node_field_data, node__field_phone, node__field_email, etc.
// Cascades to related records (activities referencing this contact may be orphaned)

// STEP 4: Return success
return new JsonResponse([
  'success' => true,
  'message' => 'Contact deleted successfully'
]);
```

### Database Impact

```sql
DELETE FROM node WHERE nid=250;
DELETE FROM node_field_data WHERE nid=250;
DELETE FROM node__field_phone WHERE entity_id=250;
DELETE FROM node__field_email WHERE entity_id=250;
DELETE FROM node__field_organization WHERE entity_id=250;
DELETE FROM node__field_owner WHERE entity_id=250;

-- Note: Activities referencing this contact still exist but orphaned
-- Queries on field_contact=250 return no results (orphaned)
```

### Frontend Response

```javascript
{
  "success": true,
  "message": "Contact deleted successfully"
}

// JavaScript:
1. Show toast: "Contact deleted successfully"
2. Close modal
3. Reload page
4. Contact row disappears from table
```

---

## Scenario 4: MOVE DEAL IN PIPELINE

### User Flow (Kanban Board)

```
1. USER VIEWS /crm/pipeline
   KanbanController renders kanban board

2. BOARD SHOWS COLUMNS
   Column 1: "Lead" (deals at this stage)
   Column 2: "Prospect" (deals at this stage)
   Column 3: "Qualified" (deals at this stage)
   Column 4: "Negotiation"
   Column 5: "Won"
   Column 6: "Lost"

3. USER DRAGS DEAL CARD
   From "Lead" column to "Prospect" column
   Visual: Card animates to new position

4. SORTABLE.JS DRAG EVENT
   Trigger: 'change' event
   Data: {oldIndex: 0, newIndex: 4, from: 'col-lead', to: 'col-prospect'}

5. JAVASCRIPT SENDS AJAX
   POST /crm/kanban/update-stage
   Payload: {deal_id: 156, stage: 'prospect', position: 4}
```

### JavaScript Drag Handler

```javascript
// In crm_kanban/js/kanban.js

// STEP 1: Initialize Sortable
const columns = document.querySelectorAll("[data-stage]");

columns.forEach((col) => {
  new Sortable(col, {
    group: "deals", // All columns are drag-enabled
    animation: 150,
    onEnd: function (evt) {
      // STEP 2: Capture drag end event
      const dealEl = evt.item;
      const dealId = dealEl.dataset.nid;
      const oldStageCol = evt.from.dataset.stage;
      const newStageCol = evt.to.dataset.stage;

      // STEP 3: If stage unchanged, ignore
      if (oldStageCol === newStageCol) {
        return;
      }

      // STEP 4: Send AJAX to update stage
      fetch("/crm/kanban/update-stage", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": drupalSettings.csrfToken,
        },
        body: JSON.stringify({
          deal_id: dealId,
          new_stage: newStageCol,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            console.log("Stage updated");
            // Update color if stage changed
            dealEl.style.borderLeftColor = data.color;
          } else {
            // Revert on error
            evt.from.appendChild(dealEl);
            console.error("Failedto update stage");
          }
        });
    },
  });
});
```

### Backend Processing

```php
// POST /crm/kanban/update-stage

public function updateStage(Request $request) {
  $data = json_decode($request->getContent(), true);
  $deal_id = $data['deal_id'];
  $new_stage = $data['new_stage'];  // e.g., 'prospect'

  // STEP 1: Load deal
  $deal = Node::load($deal_id);
  if (!$deal || $deal->bundle() !== 'deal') {
    return new JsonResponse(['error' => 'Not found'], 404);
  }

  // STEP 2: Check permission
  if (!$deal->access('update')) {
    return new JsonResponse(['error' => 'Access denied'], 403);
  }

  // STEP 3: Validate stage exists
  $stage_term = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', 'deal_stages')
    ->condition('name', $new_stage)
    ->execute();
  if (empty($stage_term)) {
    return new JsonResponse(['error' => 'Invalid stage'], 400);
  }

  $stage_tid = reset($stage_term);

  // STEP 4: Update field_stage
  $deal->set('field_stage', ['target_id' => $stage_tid]);

  // STEP 5: Save
  $deal->save();
  // UPDATE node__field_stage SET field_stage_target_id=25 WHERE entity_id=156

  // STEP 6: Get color for this stage
  $stage_term = Term::load($stage_tid);
  $color = $this->getStageColor($stage_tid);

  // STEP 7: Return success with color
  return new JsonResponse([
    'success' => true,
    'message' => 'Deal moved to ' . $new_stage,
    'color' => $color
  ]);
}
```

### Database Impact

```sql
UPDATE node__field_stage
SET field_stage_target_id=25
WHERE entity_id=156;
-- field_stage now points to prospect stage (tid=25)

-- If stage changed from lead to prospect:
-- - Deal visibility doesn't change (same owner)
-- - Deal position in kanban updates
-- - Timestamp changed=1709788500 updates
```

### Visual Result

```javascript
// Deal card automatically:
1. Moves to new column with smooth animation
2. Gets new color (based on stage)
3. Re-ordered within column
4. "Negotiation" stage field updated on detail page
```

---

## Scenario 5: IMPORT CSV CONTACTS

### User Flow

```
1. USER VISITS /crm/import
   ImportController renders import hub
   Shows 4 options: Import Contacts, Deals, Organizations, Activities

2. USER CLICKS "Import Contacts"

3. IMPORT FORM DISPLAYS
   URL: /crm/import/contacts
   Fields:
   [ Upload CSV file... ] [Browse]
   [ ] Match duplicates by phone number
   [Required fields: Name, Phone, Email]

4. USER SELECTS CSV FILE
   File: contacts.csv
   Contents:
   name,phone,email,organization,position
   John Doe,0912345678,john@example.com,ACME Corp,Manager
   Jane Smith,0987654321,jane@example.com,ACME Corp,Developer

5. USER CLICKS IMPORT
   Form submits with file

6. PROGRESS PAGE SHOWS
   "Processing 2 rows..."
   [#####.....] 50%
```

### Backend - File Processing

```php
// POST /crm/import/contacts

public function importContactsCsv(Request $request) {
  // STEP 1: Get uploaded file
  $file = $request->files->get('csv_file');
  if (!$file) {
    return new JsonResponse(['error' => 'No file'], 400);
  }

  // STEP 2: Validate file type
  if (!in_array($file->getMimeType(), ['text/csv', 'application/csv'])) {
    return new JsonResponse(['error' => 'Invalid file type'], 400);
  }

  // STEP 3: Parse CSV
  $rows = [];
  if (($handle = fopen($file->getPathname(), 'r')) !== FALSE) {
    $headers = fgetcsv($handle);  // First row
    while (($row = fgetcsv($handle)) !== FALSE) {
      $rows[] = array_combine($headers, $row);
    }
    fclose($handle);
  }

  // STEP 4: Validate required fields
  $required = ['name', 'phone', 'email'];
  $errors = [];
  foreach ($rows as $i => $row) {
    foreach ($required as $field) {
      if (empty($row[$field])) {
        $errors[] = "Row {$i}: Missing {$field}";
      }
    }
  }

  if (!empty($errors)) {
    return new JsonResponse([
      'success' => false,
      'message' => 'Validation failed',
      'errors' => $errors
    ], 400);
  }

  // STEP 5: Batch import using queue
  $queue = \Drupal::queue('crm_import_contacts');
  foreach ($rows as $row) {
    $queue->createItem($row);
  }

  // STEP 6: Return response
  return new JsonResponse([
    'success' => true,
    'message' => 'Queued ' . count($rows) . ' contacts for import',
    'count' => count($rows)
  ]);
}

// Queue worker process (runs via drush queue:run)
function _crm_import_contacts_process($data) {
  $name = $data['name'];
  $phone = $data['phone'];
  $email = $data['email'];

  // Check for duplicate
  $existing = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('field_phone', $phone)
    ->execute();

  if (!empty($existing)) {
    \Drupal::logger('crm_import')->warning("Contact {$phone} already exists");
    return;
  }

  // Create contact
  $contact = Node::create([
    'type' => 'contact',
    'title' => $name,
    'field_phone' => $phone,
    'field_email' => $email,
    'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
    'status' => 1,
  ]);
  $contact->save();

  \Drupal::logger('crm_import')->info("Contact {$name} imported (nid={$contact->id()})");
}
```

### Database Impact

```sql
-- For each row in CSV:

INSERT INTO node (uuid, type, created, changed, uid)
VALUES ('xxxx-xxxx', 'contact', 1709788200, 1709788200, 5);
-- Result: nid=350

INSERT INTO node__field_phone (entity_id, delta, field_phone_value)
VALUES (350, 0, '0912345678');

INSERT INTO node__field_email (entity_id, delta, field_email_value)
VALUES (350, 0, 'john@example.com');

-- Results:
-- Imported: 2 contacts
-- Errors: 0
-- Time: 2 seconds
```

### Result

```javascript
{
  "success": true,
  "message": "Queued 2 contacts for import",
  "count": 2
}

// User sees:
"✓ Import successful
 2 contacts have been queued for import.
 Check back in a few moments to see them in the contacts list."

// After queue processes:
// New contacts appear in /crm/my-contacts
```

---

# 7. Permission System Deep Dive

## 7.1 Permission Architecture

### Drupal Core Permissions

```yaml
# Basic node operations
view own unpublished content
edit own page content
delete own page content
create contact content
edit any contact content
delete any contact content
```

### CRM-Specific Permissions

**File:** `web/modules/custom/crm/crm.permissions.yml`

```yaml
access crm dashboard:
  title: "Access CRM Dashboard"
  description: "Can visit /crm/dashboard"

access crm kanban:
  title: "Access Kanban Board"
  description: "Can visit /crm/pipeline"

view own crm nodes:
  title: "View own CRM records"
  description: "View only records created by current user"

edit own crm nodes:
  title: "Edit own CRM records"
  description: "Edit only records created by current user"

delete own crm nodes:
  title: "Delete own CRM records"
  description: "Delete only records created by current user"

bypass crm access control:
  title: "Bypass CRM Access Control"
  description: "Bypass ownership-based access restrictions"
  restrict access: true

administer crm:
  title: "Administer CRM"
  description: "Full CRM administration access"
  restrict access: true
```

---

## 7.2 Role Definitions

### Role 1: Administrator

```yaml
ROLE: administrator (auto-created by Drupal)

Permissions:
  - administer crm
  - administer nodes
  - bypass crm access control
  - bypass crm team access
  - create contact content
  - create deal content
  - create organization content
  - create activity content
  - edit any contact content
  - edit any deal content
  - edit any organization content
  - edit any activity content
  - delete any contact content
  - delete any deal content
  - delete any organization content
  - delete any activity content
  - access crm dashboard
  - access crm kanban
  - view entire site

CAN: ✓ See all contacts, deals, organizations
  ✓ Edit anyone's records
  ✓ Delete records
  ✓ Manage users and teams
  ✓ Import CSV data
  ✓ Configure CRM settings
```

### Role 2: Sales Manager

```yaml
ROLE: sales_manager (custom role)

Permissions:
  - create contact content
  - create deal content
  - create organization content
  - create activity content
  - edit own contact content
  - edit own deal content
  - edit own organization content
  - edit own activity content
  - delete own contact content
  - delete own deal content
  - delete own organization content
  - delete own activity content
  - view own crm nodes
  - edit own crm nodes
  - delete own crm nodes
  - access crm dashboard
  - access crm kanban
  - view all team records  ← Key permission for manager
  - view site in maintenance mode

CAN: ✓ Create new records
  ✓ Edit own records
  ✓ View own team's records (through Views filtering)
  ✓ See dashboard and kanban
  ✗ Edit others' records
  ✗ See other teams' records
  ✗ Import data
  ✗ Configure settings

CANNOT: ✗ bypass crm team access
  ✗ administer crm
  ✗ administer nodes
```

### Role 3: Sales Rep

```yaml
ROLE: sales_rep (custom role)

Permissions:
  - create contact content
  - create deal content
  - create activity content
  - edit own contact content
  - edit own deal content
  - edit own activity content
  - delete own contact content
  - delete own deal content
  - delete own activity content
  - view own crm nodes
  - edit own crm nodes
  - delete own crm nodes
  - access crm dashboard

CAN: ✓ Create new records (owner = self)
  ✓ Edit own records only
  ✓ View own records in /crm/my-contacts
  ✓ See dashboard
  ✗ View /crm/pipeline (kanban denied)
  ✗ View all_contacts (admin view denied)
  ✗ See other team's records

CANNOT: ✗ Edit others' records
  ✗ See manager dashboard
  ✗ Import CSV
  ✗ Configure anything
```

### Role 4: Customer

```yaml
ROLE: customer (custom role)

Permissions:
  - view own contact page
  - access user profiles

CAN:
  ✓ View own user profile
  ✗ Access CRM
  ✗ View contacts/deals/organizations

(This role is for direct customers, not internal CRM users)
```

---

## 7.3 Access Control Flow

### Request to View Contact

```
REQUEST: User views /node/250 (contact)

┌─────────────────────────────────────
│ STEP 1: Drupal checks access hooks
│ ├─ hook_node_access() in crm.module
│ ├─ hook_node_access() in crm_teams.module
│ └─ hook_node_access() in other modules
├─────────────────────────────────────
│ STEP 2: CRM Core Module Check
│
│ if (node type is contact/deal/organization/activity) {
│   if (user owns node) {
│     ALLOWED  ✓
│   } else if (user.hasPermission('bypass crm access control')) {
│     ALLOWED ✓ (admin)
│   } else {
│     FORBIDDEN ✗
│   }
│ }
├─────────────────────────────────────
│ STEP 3: Teams Module Check
│ (only if teams assigned)
│
│ if (user.team === owner.team) {
│   ALLOWED ✓
│ } else {
│   FORBIDDEN ✗
│ }
├─────────────────────────────────────
│ RESULT: Access granted or denied
│
│ If denied → 403 Forbidden page
│ If allowed → Node content rendered
└─────────────────────────────────────
```

### Code in crm.module

```php
function crm_node_access(NodeInterface $node, $op, AccountInterface $account) {
  $bundle = $node->bundle();

  if (!in_array($bundle, ['contact', 'deal', 'organization', 'activity'])) {
    return AccessResult::neutral();
  }

  // CASE 1: Viewing own record
  if ($op === 'view' && $node->getOwnerId() === $account->id()) {
    return AccessResult::allowed()
      ->cachePerUser()
      ->addCacheableDependency($node);
  }

  // CASE 2: Admin user
  if ($account->hasPermission('bypass crm access control')) {
    return AccessResult::neutral();  // Allow other checks
  }

  // CASE 3: Different owner
  if ($op === 'view') {
    return AccessResult::forbidden('Not your record')
      ->cachePerUser()
      ->addCacheableDependency($node);
  }

  // Default: let other modules decide
  return AccessResult::neutral();
}
```

---

## 7.4 Views Access Control

### my_contacts View

```yaml
# /crm/my-contacts
# Only shows logged-in user's own contacts

arguments:
  field_owner_target_id:
    id: field_owner_target_id
    default_argument_type: current_user
    # ↑ This filters to only contacts owned by current user

access:
  type: perm
  options:
    perm: "access content"
    # ↑ Any logged-in user can access (authenticated role)
```

**Result SQL:**

```sql
SELECT * FROM node_field_data
WHERE
  type = 'contact'
  AND field_owner = 5  ← Current user's ID
  AND status = 1;
```

---

### all_contacts View

```yaml
# /crm/all-contacts
# Admin-only view showing ALL contacts

access:
  type: role
  options:
    role:
      administrator: true
      sales_manager: true
    # ↑ Only these roles can access

# No contextual filter
# Shows all contacts regardless of owner
```

**Access check:**

```php
if (user.role === 'administrator' || user.role === 'sales_manager') {
  // Load view
} else {
  // Show 403 Forbidden
}
```

---

## 7.5 Form-Level Permissions

### Node Add Form

```php
// When user visits /node/add/contact

// Drupal checks:
if (!user.hasPermission('create contact content')) {
  throw AccessDeniedException("You do not have permission to create contacts");
}

// Renders form if check passes
```

**Field visibility in form:**

```php
function crm_form_node_contact_form_alter(&$form, &$form_state) {
  $user = \Drupal::currentUser();

  // FIELD: Organization assignment
  $form['field_organization']['#access'] =
    $user->hasPermission('assign contacts');

  // FIELD: Owner assignment
  $form['field_owner']['#access'] = FALSE;
  // Don't let users choose owner, auto-set to current user

  // FIELD: Team assignment
  $form['field_team']['#access'] =
    $user->hasPermission('administer crm teams');
}
```

---

## 7.6 Permission Denied Messages

```
IF permission denied on view:
  Drupal shows: 403 Forbidden
  "Access denied. You do not have permission to access this page."

IF permission denied in form:
  Form element hidden (not rendered)

IF permission denied on API:
  JSON response: {"error": "Access denied"}
  HTTP status: 403
```

---

# 8. Feature Location Map

## Complete Feature to File Mapping

| Feature                 | Module            | Primary File                           | Controller/Method       | Route                                      | Type      |
| ----------------------- | ----------------- | -------------------------------------- | ----------------------- | ------------------------------------------ | --------- |
| **DASHBOARD**           |                   |                                        |                         |                                            |           |
| Main Dashboard          | crm_dashboard     | DashboardController.php                | view()                  | `/crm/dashboard`                           | Page      |
| Dashboard Stats         | crm_dashboard     | DashboardController.php                | buildDashboardHtml()    | Internal                                   | Logic     |
| Chart.js Integration    | crm_dashboard     | js/charts.js                           | -                       | Internal                                   | JS        |
| **CONTACTS**            |                   |                                        |                         |                                            |           |
| Contact List            | Views             | config/views.view.my_contacts.yml      | -                       | `/crm/my-contacts`                         | View      |
| Admin Contact List      | Views             | config/views.view.all_contacts.yml     | -                       | `/crm/all-contacts`                        | View      |
| Contact Detail          | crm_contact360    | contact-360-view.html.twig             | -                       | `/node/{contact_id}`                       | Page      |
| Add Contact             | crm_edit          | AddController.php                      | addPage()               | `/crm/edit/add/contact`                    | Form      |
| Edit Contact (Modal)    | crm_edit          | InlineEditController.php               | editContact()           | `/crm/edit/contact/{nid}`                  | AJAX      |
| Delete Contact          | crm_edit          | DeleteController.php                   | ajaxDelete()            | `/crm/edit/ajax/delete`                    | AJAX      |
| Quick Add Contact       | crm_quickadd      | QuickAddController.php                 | addContact()            | `/crm/quick-add/contact`                   | Modal     |
| Contact Search          | Search API        | -                                      | -                       | `/search/contacts`                         | Search    |
| **DEALS**               |                   |                                        |                         |                                            |           |
| Deal List               | Views             | config/views.view.my_deals.yml         | -                       | `/crm/my-deals`                            | View      |
| Deal Pipeline           | crm_kanban        | KanbanController.php                   | view()                  | `/crm/pipeline`                            | Page      |
| Edit Deal (Modal)       | crm_edit          | InlineEditController.php               | editDeal()              | `/crm/edit/deal/{nid}`                     | AJAX      |
| Move Deal Stage         | crm_kanban        | KanbanController.php                   | updateStage()           | `/crm/kanban/update-stage`                 | AJAX      |
| Add Deal                | crm_quickadd      | QuickAddController.php                 | addDeal()               | `/crm/quick-add/deal`                      | Modal     |
| **ORGANIZATIONS**       |                   |                                        |                         |                                            |           |
| Org List                | Views             | config/views.view.my_organizations.yml | -                       | `/crm/my-organizations`                    | View      |
| Org Detail              | crm_contact360    | contact-360-view.html.twig             | -                       | `/node/{org_id}`                           | Page      |
| Edit Organization       | crm_edit          | InlineEditController.php               | editOrganization()      | `/crm/edit/organization/{nid}`             | AJAX      |
| **ACTIVITIES**          |                   |                                        |                         |                                            |           |
| Activity Timeline       | crm_activity_log  | ActivityLogController.php              | activityTab()           | `/node/{nid}/activities`                   | Tab       |
| Activity List           | Views             | config/views.view.my_activities.yml    | -                       | `/crm/my-activities`                       | View      |
| Log Call                | crm_activity_log  | ActivityLogController.php              | logCallForm()           | `/crm/activity/log-call/{contact}`         | Form      |
| Schedule Meeting        | crm_activity_log  | ActivityLogController.php              | scheduleMeetingForm()   | `/crm/activity/schedule-meeting/{contact}` | Form      |
| Log Email               | crm_activity_log  | ActivityLogController.php              | logEmailForm()          | `/crm/activity/log-email/{contact}`        | Form      |
| **IMPORT/EXPORT**       |                   |                                        |                         |                                            |           |
| Import Hub              | crm_import_export | ImportController.php                   | importPage()            | `/crm/import`                              | Page      |
| Import Contacts         | crm_import_export | ImportContactsForm.php                 | buildForm()             | `/crm/import/contacts`                     | Form      |
| Import Deals            | crm_import_export | ImportDealsForm.php                    | buildForm()             | `/crm/import/deals`                        | Form      |
| Export as CSV           | crm_import_export | ExportController.php                   | exportView()            | `/crm/export/{view_name}`                  | Download  |
| **TEAMS**               |                   |                                        |                         |                                            |           |
| Team Management         | crm_teams         | TeamsManagementController.php          | manage()                | `/admin/crm/teams`                         | Page      |
| Assign Users            | crm_teams         | TeamsManagementController.php          | assignUsers()           | `/admin/crm/teams/{team}/assign`           | Form      |
| Team Access Control     | crm_teams         | crm_teams.module                       | crm_teams_node_access() | Internal                                   | Hook      |
| **USER MANAGEMENT**     |                   |                                        |                         |                                            |           |
| User Profile            | crm               | UserProfileController.php              | view()                  | `/user/{uid}`                              | Page      |
| Custom Login            | crm_login         | CrmLoginForm.php                       | buildForm()             | `/login`                                   | Form      |
| Custom Register         | crm_register      | CrmRegisterForm.php                    | buildForm()             | `/user/register`                           | Form      |
| **MODALS & COMPONENTS** |                   |                                        |                         |                                            |           |
| Inline Edit Modal       | crm_edit          | inline-edit.js                         | openModal()             | AJAX                                       | JS        |
| Quick Add Modal         | crm_quickadd      | quickadd.js                            | openModal()             | Modal                                      | JS        |
| Delete Confirmation     | crm_edit          | inline-edit.js                         | confirmDelete()         | Dialog                                     | JS        |
| Activity Add Modal      | crm_activity_log  | activity-widget.js                     | openActivityForm()      | Modal                                      | JS        |
| **NAVIGATION**          |                   |                                        |                         |                                            |           |
| Global Nav Bar          | crm_navigation    | global-nav.js                          | buildNav()              | All pages                                  | Component |
| Breadcrumbs             | crm_navigation    | breadcrumbs.twig                       | -                       | All pages                                  | Component |
| Back Buttons            | crm_navigation    | navigation.css                         | -                       | Detail pages                               | Component |
| **NOTIFICATIONS**       |                   |                                        |                         |                                            |           |
| Email Notifications     | crm_notifications | EmailService.php                       | sendNotification()      | Email queue                                | Service   |
| Toast Messages          | crm_edit          | toast.js                               | showMessage()           | Page                                       | JS        |
| Activity Alerts         | crm_activity_log  | ActivityApiController.php              | getAlerts()             | AJAX                                       | API       |

---

# 9. API Endpoints Reference

## AJAX Endpoints

### Edit/Delete Endpoints

```
POST /crm/edit/ajax/save
  Purpose: Save edited contact/deal/organization
  Middleware: CSRF token required
  Request: FormData (multipart)
  Response: {success: bool, message: string, nid: int}

POST /crm/edit/ajax/delete
  Purpose: Delete entity
  Request: {nid: int, type: string}
  Response: {success: bool, message: string}

GET /crm/edit/{type}/{nid}
  Purpose: Load edit form for entity
  Response: HTML form string
```

### Kanban Endpoints

```
POST /crm/kanban/update-stage
  Purpose: Move deal to new pipeline stage
  Request: {deal_id: int, new_stage: string}
  Response: {success: bool, color: string}

GET /crm/kanban/api/stages
  Purpose: Get all pipeline stages
  Response: [{id: int, name: string, color: string}, ...]
```

### Activity Endpoints

```
GET /crm/activity/api/list/{entity_type}/{entity_id}
  Purpose: Fetch activity timeline (for lazy loading)
  Response: {activities: [{id, title, type, created, author}, ...]}

POST /crm/activity/log-call/{contact_id}/submit
  Purpose: Log phone call
  Request: {title, outcome, duration, description}
  Response: {success: bool, activity_id: int}
```

### Import/Export Endpoints

```
POST /crm/import/contacts
  Purpose: Upload and process CSV
  Request: MultipartFormData (file upload)
  Response: {success: bool, count: int, message: string}

GET /crm/export/contacts
  Purpose: Export contacts as CSV
  Request: Query filters (optional)
  Response: CSV file download
```

---

# 10. Performance Optimization Guide

## 10.1 Database Indexes

**Critical indexes created via:**

```bash
ddev exec bash scripts/create_database_indexes.sh
```

**Indexes:**

```sql
-- Ownership (for filtering by user)
CREATE INDEX idx_node_field_owner
  ON node__field_owner(field_owner_target_id);
CREATE INDEX idx_node_field_assigned_to
  ON node__field_assigned_to(field_assigned_to_target_id);

-- Type + status (common for Views)
CREATE INDEX idx_node_type_status
  ON node_field_data(type, status);

-- Pipeline stage (for kanban grouping)
CREATE INDEX idx_node_field_stage
  ON node__field_stage(field_stage_target_id);

-- Dates (for sorting)
CREATE INDEX idx_node_created
  ON node_field_data(created DESC);
CREATE INDEX idx_node_field_closing_date
  ON node__field_closing_date(field_closing_date_value);
```

**Impact:** 10x query speedup with proper indexes

---

## 10.2 Caching Strategy

### Views Caching

All CRM views use Drupal's built-in cache tags:

```yaml
cache:
  type: tag
  options: {}
```

**Auto-invalidation:** Cache flushed when:

- Related node updated/deleted
- User changes
- Taxonomy term changes

### Render Caching

```php
return [
  '#markup' => $html,
  '#cache' => [
    'max-age' => 3600,  // Cache for 1 hour
    'contexts' => ['user'],  // Different cache per user
    'tags' => ['node:123', 'node:124', 'user:5'],
  ],
];
```

---

## 10.3 Lazy Loading

Activity timelines and related data use lazy builders:

```php
return [
  '#lazy_builder' => [
    'ActivityLogController::getActivities',
    [$node_id]
  ],
  '#create_placeholder' => TRUE,
];
```

**Result:** Page loads in 200ms without activities, then AJAX fetches activities

---

## 10.4 Query Optimization

```php
// BAD: Inefficient query
$contacts = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->execute();
// Loads ALL 10,000 contacts into memory!

// GOOD: Paginated query
$contacts = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->range(0, 25)  // Only 25 per page
  ->execute();

// GOOD: Only load fields we need
$query = \Drupal::database()
  ->select('node_field_data', 'nfd')
  ->fields('nfd', ['nid', 'title', 'created'])
  ->condition('nfd.type', 'contact')
  ->range(0, 25);
```

---

# 11. Architecture Improvements

## 11.1 Current Architecture Issues

### Issue 1: No Service Layer

**Current:** Business logic in Controllers

```php
// Bad: Logic mixed with request handling
public function ajaxSave(Request $request) {
  // Validation
  // Processing
  // Database operations
  // Response formatting
  // ALL in one method!
}
```

**Recommended:** Extract to Service classes

```php
// src/Service/ContactService.php
class ContactService {
  public function saveContact(array $data): Contact {
    // Validate
    // Process
    // Persist
    // Return
  }
}

// In controller:
public function ajaxSave(Request $request) {
  $data = $request->request->all();
  $contact = $this->contactService->saveContact($data);
  return new JsonResponse(['success' => true]);
}
```

**Benefit:** Reusable business logic, easier testing

---

### Issue 2: Authorization Logic in Hooks

**Current:** Access control scattered across hooks

```php
// crm.module
function crm_node_access() { ... }

// crm_teams.module
function crm_teams_node_access() { ... }

// Views filters
// Form alters
// API middleware
```

**Recommended:** Centralized access service

```php
// src/Service/AccessControlService.php
class AccessControlService {
  public function canViewNode(NodeInterface $node, UserInterface $user): bool {
    // Single source of truth
    // All logic in one place
  }

  public function canEditNode(NodeInterface $node, UserInterface $user): bool {
    // ...
  }
}

// Used everywhere:
if (!$this->accessControl->canViewNode($node, $user)) {
  throw new AccessDeniedException();
}
```

---

### Issue 3: Tight Coupling to Drupal

**Current:** Drupal EntityQuery used throughout

```php
// Hard to test without Drupal database
$contacts = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->execute();
```

**Recommended:** Data access abstraction (Repository pattern)

```php
// src/Repository/ContactRepository.php
interface ContactRepository {
  public function findById(int $id): ?Contact;
  public function findByPhone(string $phone): ?Contact;
  public function findAll(): array;
}

// MyDrupalContactRepository implements ContactRepository
class MyDrupalContactRepository implements ContactRepository {
  public function findAll(): array {
    // Uses EntityQuery
  }
}

// In services, use interface:
class ImportService {
  public function __construct(ContactRepository $repo) {
    $this->repo = $repo;
  }

  public function importCsv(array $rows) {
    // No knowledge of Drupal!
    // Can be tested with mock repository
  }
}
```

---

## 11.2 Recommended Architecture

```
┌─────────────────────────────────────────────┐
│  PRESENTATION LAYER                         │
│  - Controllers (HTTP routing)                │
│  - Forms (user input)                        │
│  - Twig templates (HTML rendering)          │
├─────────────────────────────────────────────┤
│  APPLICATION LAYER                          │
│  - Services (business logic)                 │
│  - Validators                                │
│  - Transformers (DTO conversion)            │
├─────────────────────────────────────────────┤
│  DATA LAYER                                  │
│  - Repositories (abstract data access)       │
│  - Entities (domain models)                  │
├─────────────────────────────────────────────┤
│  INFRASTRUCTURE LAYER                       │
│  - Drupal EntityQuery implementations        │
│  - Database drivers                          │
│  - External API clients                      │
└─────────────────────────────────────────────┘
```

---

## 11.3 Code Organization Proposal

```
web/modules/custom/crm/
├── src/
│   ├── Entity/
│   │   ├── Contact.php          # Domain entity
│   │   ├── Deal.php
│   │   └── Activity.php
│   ├── Repository/
│   │   ├── ContactRepository.php  # Interface
│   │   ├── DealRepository.php
│   │   └── ActivityRepository.php
│   ├── Service/
│   │   ├── ContactService.php     # Business logic
│   │   ├── DealService.php
│   │   ├── AccessControlService.php
│   │   └── NotificationService.php
│   ├── Validator/
│   │   ├── ContactValidator.php
│   │   └── DealValidator.php
│   ├── Controller/
│   │   ├── ContactController.php  # HTTP handling
│   │   ├── DealController.php
│   │   └── ApiController.php
│   ├── Form/
│   │   ├── ContactForm.php
│   │   └── DealForm.php
│   └── EventSubscriber/
│       ├── ContactEventSubscriber.php
│       └── DealEventSubscriber.php
├── tests/
│   ├── Unit/
│   │   └── Service/
│   │       └── ContactServiceTest.php
│   └── Functional/
│       └── ContactControllerTest.php
```

---

# 12. Security Analysis & Best Practices

## 12.1 Authentication

### Current Implementation

```php
// Drupal's default user authentication
// POST /user/login → Creates session → PHPSESSID cookie

// CRM custom login:
// POST /login → Validates email/password → Drupal session
```

**Good:** Uses Drupal's session management (secure by default)

**Missing:**

- Multi-factor authentication (MFA)
- IP whitelisting for admin accounts
- Login attempt rate limiting

---

## 12.2 Authorization

### Access Control Checks

```php
// ✓ Good: Checking node access before view
if (!$nodeUserData->access('view')) {
  throw new AccessDeniedException();
}

// ✓ Good: Permission checks in routes
requirements:
  _permission: 'create contact content'

// ✓ Good: Ownership-based filtering
->condition('field_owner', $user->id())
```

### Potential Issues

```php
// ✗ Problem: No check on AJAX endpoint
public function ajaxSave(Request $request) {
  // SHOULD validate $request->request.get('nid') ownership!
  $node = Node::load($request->request->get('nid'));
  // User could edit anyone's contact!
}

// FIX:
if (!$node->access('update')) {
  return new JsonResponse(['error' => 'Forbidden'], 403);
}
```

---

## 12.3 CSRF Protection

### Current Status

```php
// ✓ Drupal provides CSRF tokens automatically
// In forms: {{ form._token }}
// In AJAX: header 'X-CSRF-Token': drupalSettings.csrfToken

// ✓ Tokens validated on POST/PUT/DELETE
// ✗ Tokens NOT validated on GET (should never mutate on GET)
```

**Best Practice Check:**

```php
// ✓ Good: Mutation only on POST
public function save(Request $request) {
  if ($request->getMethod() !== 'POST') {
    return new BadRequestHttpException();
  }
  // Process...
}

// ✗ Bad: Mutation on GET
public function delete(Request $request, $id) {
  Node::load($id)->delete();  // Should be POST!
}
```

---

## 12.4 SQL Injection Prevention

### Current Code

```php
// ✓ Good: Entity API (parameterized queries)
\Drupal::entityQuery('node')
  ->condition('field_phone', $phone)  // Parameterized
  ->execute();

// ✓ Good: Database API with placeholders
\Drupal::database()
  ->query('SELECT * FROM node WHERE nid = :nid', [':nid' => $nid])
  ->fetchAll();

// ✗ Bad: String concatenation (if present)
$sql = "SELECT * FROM node WHERE nid = " . $nid;
// SQL injection possible!
```

**No SQL injection issues found** ✓

---

## 12.5 XSS Prevention

### Twig Templates

```twig
{# ✓ Good: Auto-escaped by default #}
<p>{{ node.title }}</p>
{# Output: <p>John Doe</p> (HTML-escaped if needed) #}

{# ✓ Good: Explicit escaping #}
<p>{{ node.title|escape }}

{# ✗ Bad: Raw markup (never do this!) #}
{# Don't use this: #}
{{ node.description }}  {# If it contains <script> tags! #}

{# ✓ Good: Use processed text format  #}
{{ node.field_description.value|render }}
```

**Check in code:**

```php
// In forms:
$form['#markup'] = '<p>' . $user_input . '</p>';  // ✗ XSS!

// Fix:
$form['#markup'] = '<p>' . Html::escape($user_input) . '</p>';  // ✓
```

---

## 12.6 Data Validation

### Form Validation

```php
// ✓ Good: Server-side validation (client-side can be bypassed)
public function submitForm(array &$form, FormStateInterface $form_state) {
  $email = $form_state->getValue('email');
  $message = '';

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $form_state->setErrorByName('email', 'Invalid email');
    return;  // Form not submitted
  }

  // Continue processing...
}
```

### JSON API Validation

```php
public function ajaxSave(Request $request) {
  $data = json_decode($request->getContent(), true);

  // ✓ Validate all inputs
  if (!isset($data['title']) || empty($data['title'])) {
    return new JsonResponse(['error' => 'Title required'], 400);
  }

  if (strlen($data['title']) > 255) {
    return new JsonResponse(['error' => 'Title too long'], 400);
  }

  // ✓ Sanitize before saving
  $title = Html::escape($data['title']);
  // ...
}
```

---

## 12.7 File Upload Security

### Current Implementation (Not visible in code)

**If CSV import is implemented, check:**

```php
// ✓ Good: Validate file type
$allowed_mimes = ['text/csv', 'application/csv'];
if (!in_array($file->getMimeType(), $allowed_mimes)) {
  return new JsonResponse(['error' => 'Invalid file'], 400);
}

// ✓ Good: Limit file size
if ($file->getSize() > 5 * 1024 * 1024) {  // 5MB
  return new JsonResponse(['error' => 'File too large'], 400);
}

// ✓ Good: Parse safely (not eval)
$rows = [];
if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
  while (($row = fgetcsv($handle)) !== FALSE) {
    $rows[] = $row;  // CSV is just data, not code
  }
  fclose($handle);
}

// ✗ Bad (never do):
eval($file_contents);  // Never eval user files!
```

---

## 12.8 Environment Security

### Secrets Management

```php
// ✓ Good: Use Drupal settings.php
$config['crm']['api_key'] = getenv('CRM_API_KEY');

// ✗ Bad: Hard-coded secrets
$api_key = 'secret-key-12345';  // In code!
```

### Database Credentials

```php
// ✓ Good: In settings.php (not in repo)
$databases['default']['default'] = [
  'driver' => 'mysql',
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASSWORD'),
  'host' => getenv('DB_HOST'),
];
```

---

## 12.9 Security Checklist

| Check                           | Status     | Action                         |
| ------------------------------- | ---------- | ------------------------------ |
| CSRF tokens on forms            | ✓ OK       | None                           |
| CSRF tokens on AJAX             | ✓ OK       | None                           |
| Access checks before mutations  | ⚠️ PARTIAL | Audit all controllers          |
| SQL injection prevention        | ✓ OK       | None                           |
| XSS prevention in Twig          | ✓ OK       | Regular audits                 |
| File upload validation          | 🔍 REVIEW  | Implement if CSV upload exists |
| Rate limiting                   | ✗ MISSING  | Add for login/import endpoints |
| Input validation on API         | ⚠️ PARTIAL | Strengthen validation          |
| Secrets in environment vars     | 🔍 REVIEW  | Check settings.php             |
| HTTPS enforcement               | 🔍 REVIEW  | Configure in .htaccess         |
| SQL injection in custom queries | ✓ OK       | None                           |
| Sensitive data in logs          | 🔍 REVIEW  | Check logger usage             |
| API authentication              | ⚠️ PARTIAL | Document & review              |

---

## 12.10 Recommended Security Improvements

### 1. Add Rate Limiting

```php
// Limit login attempts
function crm_login_form_alter(&$form, FormStateInterface $form_state) {
  $form['#validate'][] = '_crm_login_rate_limit';
}

function _crm_login_rate_limit(array &$form, FormStateInterface $form_state) {
  $ip = \Drupal::request()->getClientIp();
  $cache = \Drupal::cache()->get("login_attempts:$ip");

  if ($cache && $cache->data > 5) {  // Max 5 attempts
    $form_state->setGeneral Error('Too many login attempts. Try again in 15 minutes.');
  }
}
```

### 2. Audit Access Control

```php
// Add permission checks to all AJAX endpoints
public function ajaxSave(Request $request) {
  $nid = $request->request->get('nid');
  $node = Node::load($nid);

  // MUST CHECK:
  if (!$node->access('update', $this->currentUser())) {
    return new JsonResponse(['error' => 'Forbidden'], 403);
  }

  // Safe to proceed
}
```

### 3. Implement Content Security Policy (CSP)

```php
// In settings.php or module
header('Content-Security-Policy: default-src self; script-src self cdn.example.com;');
```

### 4. Log Security Events

```php
Drupal::logger('crm_security')->warning('Unauthorized access attempt', [
  'user' => $user->id(),
  'entity' => $node->id(),
  'action' => 'view',
]);
```

---

## Summary

This comprehensive documentation should serve as a complete reference for developers working with the Open CRM system. The documentation covers:

✅ All 16 custom modules  
✅ Complete data flow scenarios  
✅ Permission and access control  
✅ Performance optimization  
✅ Security best practices  
✅ Architecture recommendations

Keep this documentation updated as the system evolves.

---

**End of Part 4 - Supplementary Documentation**

Last Updated: March 6, 2026  
Version: 3.0
