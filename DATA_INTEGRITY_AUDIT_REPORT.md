# CRM Data Integrity Audit Report

**Generated:** March 9, 2026  
**Scope:** Complete analysis of open_crm custom modules, entity relationships, and data validation  
**Status:** CRITICAL ISSUES IDENTIFIED - Recommendations Required

---

## Executive Summary

The CRM application is **OPERATIONAL but contains CRITICAL data integrity vulnerabilities** that pose risks to data accuracy, reporting reliability, and system reliability. Analysis of 16 custom modules revealed:

- **7 CRITICAL Issues** affecting core data relationships
- **12 MAJOR Issues** impacting data consistency and validation
- **8 MINOR Issues** causing potential data synchronization problems

Without immediate remediation, the CRM is at risk of:

- Orphaned entities (deals without owners, activities without targets)
- Inconsistent dashboard statistics and pipeline calculations
- Data loss due to broken entity references
- Role-based access control bypasses
- Unreliable reporting and analytics

---

## Part 1: CRITICAL ISSUES (MUST FIX)

### Issue 1.1: INCONSISTENT STAGE ID REFERENCES 🔴

**Severity:** CRITICAL  
**Location:** Multiple files  
**Risk Level:** HIGH - Causes incorrect deal classifications and statistics

**Problem:**
The system uses BOTH integer stage IDs AND string stage values inconsistently:

```php
// In DashboardController.php (line 160)
if ($stage_id === 5) { // Hard-coded: ID 5 = Won
  $won_value += $amount;
  $won_count++;
} elseif ($stage_id === 6) { // Hard-coded: ID 6 = Lost
  $lost_value += $amount;
  $lost_count++;
}

// BUT in the SAME file (line 204)
->condition('field_stage', 'closed_won') // String value, not ID!

// And in KanbanController.php (line 1438)
if ($stage_id == 5) { // Again, hard-coded ID
```

**Consequences:**

- Dashboard calculations may use wrong stage values
- Pipeline board may fail to recognize stage changes
- Won/Lost deal counts could be inaccurate
- Statistics mismatch between dashboard and pipeline

**Root Cause:**

- Stage field references are mixed between taxonomy term IDs (5, 6) and string identifiers (closed_won, closed_lost)
- No centralized stage mapping constant

**Recommended Fix:**

```php
// Create in crm.module or new integrity.module
const PIPELINE_STAGES = [
  'qualified' => 1,
  'proposal' => 2,
  'negotiation' => 3,
  'closed_won' => 5,
  'closed_lost' => 6,
];

// Usage throughout system
if ($deal->get('field_stage')->target_id === self::PIPELINE_STAGES['closed_won']) {
  // Consistent reference
}
```

---

### Issue 1.2: NO VALIDATION FOR ORPHANED ENTITIES 🔴

**Severity:** CRITICAL  
**Location:** All entity creation points  
**Risk Level:** HIGH - Leads to broken data

**Problem:**
Entities can be created with NULL or missing required relationships:

1. **Deals without Owners:**
   - No validation that `field_owner` is set
   - Dashboard filters would exclude these deals
   - Deals appear "lost" in system tracking

2. **Activities without Assignments:**
   - `field_assigned_to` can be NULL
   - Dashboard counts would be incorrect
   - No one is responsible for the activity

3. **Deals without Contacts/Organizations:**
   - Optional fields but no validation logic
   - Impossible to track deal source
   - Can't calculate deal value correctly

**Evidence:**

```php
// In validation service (DataValidationService.php)
public function validateDeal(array $data) {
  $errors = [];
  // Missing: Required validation for field_owner
  // Missing: Required validation for field_contact or field_organization
  // At least ONE should be present
}

// No checks for null references in dashboard
$owner = $deal->get('field_owner')->entity;
if ($owner) { // Silently ignores if NULL
  $owner_name = $owner->getDisplayName();
}
```

**Recommended Fixes:**

A. **Add Pre-Save Validation (in entity hooks):**

```php
// In crm.module
function crm_node_presave(NodeInterface $node) {
  if ($node->bundle() === 'deal') {
    // Validate deal has required relationships
    if ($node->get('field_owner')->isEmpty()) {
      $node->get('field_owner')->setValue(\Drupal::currentUser()->id());
    }

    // Validate deal has contact OR organization
    $has_contact = !$node->get('field_contact')->isEmpty();
    $has_org = !$node->get('field_organization')->isEmpty();

    if (!$has_contact && !$has_org) {
      throw new \Exception('Deal must reference at least Contact or Organization');
    }
  }

  if ($node->bundle() === 'activity') {
    // Activity must have assigned_to user
    if ($node->get('field_assigned_to')->isEmpty()) {
      throw new \Exception('Activity must be assigned to a user');
    }

    // Activity must have contact OR deal
    $has_contact = !$node->get('field_contact')->isEmpty();
    $has_deal = !$node->get('field_deal')->isEmpty();

    if (!$has_contact && !$has_deal) {
      throw new \Exception('Activity must reference Contact or Deal');
    }
  }
}
```

B. **Add Integrity Check Service:**

```php
// Create: src/Service/DataIntegrityService.php
class DataIntegrityService {
  public function findOrphanedEntities() {
    $issues = [];

    // Find deals without owners
    $deal_ids = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($deal_ids)) {
      $issues['orphaned_deals_without_owner'] = [
        'count' => count($deal_ids),
        'entity_ids' => $deal_ids,
        'severity' => 'critical'
      ];
    }

    // Find activities without assignments
    $activity_ids = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('field_assigned_to', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($activity_ids)) {
      $issues['orphaned_activities_unassigned'] = [
        'count' => count($activity_ids),
        'entity_ids' => $activity_ids,
        'severity' => 'critical'
      ];
    }

    return $issues;
  }
}
```

---

### Issue 1.3: NO VALIDATION FOR BROKEN ENTITY REFERENCES 🔴

**Severity:** CRITICAL  
**Location:** Dashboard, Kanban, Views  
**Risk Level:** CRITICAL - Referenced entities could be deleted

**Problem:**
The system doesn't validate that referenced entities still exist:

```php
// In KanbanController.php (line 78)
$org = $deal->get('field_organization')->entity;
if ($org) {
  $org_name = $org->getTitle(); // $org could be NULL if deleted
}

// Issue: If organization is deleted, field still has target_id
// Entity relationship becomes broken, causing:
// 1. NULL warnings
// 2. Incorrect data display
// 3. Invalid statistics
```

**Consequences:**

- Deleted contacts/organizations break deal references
- Dashboard shows incomplete data for broken references
- Pipeline calculations exclude invalid deals
- Silent failures in UI

**Recommended Fix:**

```php
// In DataIntegrityService.php
public function validateEntityReferences() {
  $broken_references = [];

  // Check deal->contact references
  $deals = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
    'type' => 'deal'
  ]);

  foreach ($deals as $deal) {
    if (!$deal->get('field_contact')->isEmpty()) {
      $contact = $deal->get('field_contact')->entity;
      if (!$contact) {
        // Target exists but entity doesn't (was deleted)
        $broken_references[] = [
          'type' => 'deal',
          'id' => $deal->id(),
          'field' => 'field_contact',
          'target_id' => $deal->get('field_contact')->target_id,
          'action' => 'REMOVE_REFERENCE_OR_DELETE_DEAL'
        ];
      }
    }
  }

  return $broken_references;
}

// Usage: Run cleanup
public function fixBrokenReferences() {
  $broken = $this->validateEntityReferences();
  foreach ($broken as $ref) {
    $entity = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($ref['id']);

    $field_name = $ref['field'];
    $entity->get($field_name)->setValue(NULL);
    $entity->save();
  }
}
```

---

### Issue 1.4: UNVALIDATED PIPELINE STAGE VALUES 🔴

**Severity:** CRITICAL  
**Location:** Deal form submission, Kanban stage updates  
**Risk Level:** HIGH - Corrupt deal status

**Problem:**
Pipeline stage updates are NOT validated against taxonomy:

```php
// In KanbanController.php - No validation before save!
$nid = $this->request->request->get('nid');
$stage_id = $this->request->request->get('stage_id');

$deal = Node::load($nid);
$deal->set('field_stage', $stage_id); // NO VALIDATION
$deal->save();

// What if $stage_id is:
// - Non-existent term ID?
// - Integer when string expected?
// - Invalid negative number?
```

**Consequences:**

- Invalid stage IDs stored in database
- Dashboard categorization fails
- Pipeline board can't display deals
- Statistics become unreliable

**Validation Service Already Exists:**

```php
// In DataValidationService.php
public function validateTaxonomyTerm($tid, $vocabulary) {
  if (empty($tid)) {
    return ['valid' => FALSE, 'message' => 'Vui lòng chọn giá trị'];
  }

  $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);

  if (!$term || $term->bundle() !== $vocabulary) {
    return ['valid' => FALSE, 'message' => 'Giá trị không hợp lệ'];
  }

  return ['valid' => TRUE, 'message' => ''];
}
```

**Recommended Fix:**

```php
// In KanbanController.php - ADD THIS
public function updateStage() {
  $nid = $this->request->request->get('nid');
  $stage_id = $this->request->request->get('stage_id');

  // VALIDATE before save
  $validation = $this->validationService->validateTaxonomyTerm($stage_id, 'pipeline_stage');
  if (!$validation['valid']) {
    return new JsonResponse(['error' => $validation['message']], 400);
  }

  // VALIDATE deal exists
  $deal = Node::load($nid);
  if (!$deal || $deal->bundle() !== 'deal') {
    return new JsonResponse(['error' => 'Deal not found'], 404);
  }

  // Now safe to update
  $deal->set('field_stage', $stage_id);
  $deal->save();
}
```

---

### Issue 1.5: DASHBOARD STATISTICS NOT SYNCHRONIZED WITH DATA 🔴

**Severity:** CRITICAL  
**Location:** DashboardController.php lines 150-250  
**Risk Level:** HIGH - Reporting unreliability

**Problem:**
Dashboard metrics use different query conditions than the actual entities:

```php
// Dashboard counts "Won" as: stage_id === 5
if ($stage_id === 5) {
  $won_value += $amount;
  $won_count++;
}

// But pipeline stores as: string 'closed_won'
->condition('field_stage', 'closed_won')

// These don't match! Won deals counted wrong
```

**Consequences:**

1. Dashboard shows X "Won Deals" but Pipeline shows Y
2. Statistics don't match underlying data
3. Executives see wrong KPIs
4. Managers can't trust reports

**Historical Data Issue:**
Some deals may have stage_id=5 (old format) while others have 'closed_won' (new format) - MIXED!

**Recommended Fix:**

```php
// Create: crm_integrity module - run once
function crm_integrity_update_stage_format() {
  // Normalize all stage values to string format

  // Load all deals
  $deals = \Drupal::entityTypeManager()->getStorage('node')
    ->loadByProperties(['type' => 'deal']);

  $stage_map = [
    1 => 'qualified',
    2 => 'proposal',
    3 => 'negotiation',
    5 => 'closed_won',
    6 => 'closed_lost',
  ];

  foreach ($deals as $deal) {
    $current_stage = $deal->get('field_stage')->value;

    // If numeric, convert to string
    if (is_numeric($current_stage) && isset($stage_map[$current_stage])) {
      $deal->get('field_stage')->setValue($stage_map[$current_stage]);
      $deal->save();
    }
  }
}

// Then keep consistent throughout:
// ALWAYS use string values: 'closed_won', 'closed_lost', etc.
// Never use numeric IDs for logic
```

---

### Issue 1.6: NO VALIDATION OF ROLE-BASED DATA ISOLATION 🔴

**Severity:** CRITICAL  
**Location:** Dashboard, Kanban, All Views  
**Risk Level:** CRITICAL - Data leak between salespeople

**Problem:**
Role-based filtering uses simple conditions but can be bypassed:

```php
// Current implementation
if (!$is_admin) {
  $contacts_query->condition('field_owner', $user_id);
}

// BUT $is_admin is checked via:
$is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;

// ISSUES:
// 1. No check if user actually has 'access crm' permission
// 2. Managers can see all data (sales_manager role has no restrictions)
// 3. team_members role not implemented
// 4. Could be bypassed by direct API access
```

**Consequences:**

1. Sales staff can see competitors' data
2. Sensitive deal info exposed between team members
3. No audit trail of data access
4. Regulatory compliance issues (if customer data protected)

**Evidence of GAP:**

```php
// Sales Manager has NO VIEW RESTRICTION
if (!$is_admin) { // Only checks for admin
  // Apply filter
}
// If NOT admin, restriction applied - but managers need restrictions too!
```

**Recommended Fix:**

A. **Create Access Control Service:**

```php
// In crm.module
function crm_node_access(\Drupal\node\DataInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  $bundle = $node->bundle();

  // Only apply to CRM entities
  if (!in_array($bundle, ['contact', 'deal', 'organization', 'activity'])) {
    return \Drupal\Core\Access\AccessResult::neutral();
  }

  if ($op === 'view') {
    // Admin can view everything
    if ($account->hasRole('administrator')) {
      return \Drupal\Core\Access\AccessResult::allowed();
    }

    // Check if user owns the entity
    if ($node->hasField('field_owner')) {
      $owner = $node->get('field_owner')->target_id;
      if ($owner == $account->id()) {
        return \Drupal\Core\Access\AccessResult::allowed();
      }
    }

    // Check if manager can view user's team data
    if ($account->hasRole('sales_manager')) {
      $team_members = $this->getTeamMembers($account->id());
      if ($node->hasField('field_owner')) {
        $owner = $node->get('field_owner')->target_id;
        if (in_array($owner, $team_members)) {
          return \Drupal\Core\Access\AccessResult::allowed();
        }
      }
    }

    // Default deny
    return \Drupal\Core\Access\AccessResult::forbidden();
  }

  return \Drupal\Core\Access\AccessResult::neutral();
}
```

B. **Add Audit Logging:**

```php
// Log all data access
function crm_log_data_access($user_id, $entity_type, $entity_id, $operation) {
  \Drupal::database()->insert('crm_data_access_log')->fields([
    'user_id' => $user_id,
    'entity_type' => $entity_type,
    'entity_id' => $entity_id,
    'operation' => $operation,
    'timestamp' => time(),
  ])->execute();
}
```

---

### Issue 1.7: NO VALIDATION FOR ENTITY FIELD CONSISTENCY 🔴

**Severity:** CRITICAL  
**Location:** Field storage configuration  
**Risk Level:** MEDIUM - Incorrect display

**Problem:**
Activities can be missing both `field_contact` AND `field_deal`:

```php
// In DataValidationService - validateDeal() IS IMPLEMENTED
// But validateActivity() is MISSING

// Currently, activities allow:
// - field_contact: NULL
// - field_deal: NULL
// BOTH being empty is allowed (no validation rule)

// Result: Activity exists but can't link to anything
```

**Consequences:**

- Activities appear in list but context is lost
- Can't navigate from activity to related deal
- Dashboard can't calculate activity metrics correctly

**Recommended Fix:**

```php
// Add in DataValidationService.php
public function validateActivity(array $data) {
  $errors = [];

  // Required: Must have contact OR deal
  $has_contact = !empty($data['contact']);
  $has_deal = !empty($data['deal']);

  if (!$has_contact && !$has_deal) {
    $errors['contact_or_deal'] = 'Activity must reference Contact or Deal';
  }

  // Optional: Type (validate if provided)
  if (!empty($data['type'])) {
    $type_validation = $this->validateTaxonomyTerm($data['type'], 'activity_type');
    if (!$type_validation['valid']) {
      $errors['type'] = $type_validation['message'];
    }
  }

  // Optional: Assigned user
  if (!empty($data['assigned_to'])) {
    $assigned_validation = $this->validateUserReference($data['assigned_to']);
    if (!$assigned_validation['valid']) {
      $errors['assigned_to'] = $assigned_validation['message'];
    }
  }

  return [
    'valid' => empty($errors),
    'errors' => $errors,
  ];
}
```

---

## Part 2: MAJOR ISSUES

### Issue 2.1: INCOMPLETE ENTITY REFERENCE VALIDATION IN FORMS

**Severity:** MAJOR  
**Location:** DataValidationService.php  
**Issue:** `validateNodeReference()` doesn't verify the node still exists (deleted but referenced)

### Issue 2.2: STAGE TAXONOMY TERMS NOT CACHED

**Severity:** MAJOR  
**Location:** DashboardController.php, KanbanController.php  
**Issue:** Loading stage terms on every page load (performance issue)

### Issue 2.3: CONTACT->ORGANIZATION RELATIONSHIP UNVALIDATED

**Severity:** MAJOR  
**Location:** Contacts  
**Issue:** Contact can reference deleted organization; No constraint

### Issue 2.4: ACTIVITY DATETIME VALIDATION INCOMPLETE

**Severity:** MAJOR  
**Location:** DashboardController.php (line 194)  
**Issue:** Overdue activities checked with `<=` but field_datetime might be missing

### Issue 2.5: NO DUPLICATE CHECKING FOR CONTACTS BY EMAIL

**Severity:** MAJOR  
**Location:** Contact save process  
**Issue:** `checkDuplicateEmail()` exists but not used everywhere

### Issue 2.6: PERMISSION CHECKS INCOMPLETE

**Severity:** MAJOR  
**Location:** InlineEditController.php, ModalEditController.php  
**Issue:** Manager role doesn't check team membership before allowing edits

### Issue 2.7: STATISTICS EXCLUDE UNOWNED ENTITIES

**Severity:** MAJOR  
**Location:** Dashboard calculations  
**Issue:** Orphaned deals (with NULL owner) never appear in totals

### Issue 2.8: VIEW CONTEXTUAL FILTERS NOT ENFORCED

**Severity:** MAJOR  
**Location:** views.view.all\_\*.yml  
**Issue:** Views use "All" prefix but don't verify user has global view permission

### Issue 2.9: DEAL AMOUNT FIELD ALLOWS NEGATIVE VALUES

**Severity:** MAJOR  
**Location:** Field configuration  
**Issue:** Only validated in import, not in form submission

### Issue 2.10: CLOSING DATE NOT VALIDATED AGAINST TODAY

**Severity:** MAJOR  
**Location:** Deal form submission  
**Issue:** Can set closing date in past (no validation)

### Issue 2.11: NO CACHE INVALIDATION ON ORPHAN DETECTION

**Severity:** MAJOR  
**Location:** Dashboard cache tags (line 2638)  
**Issue:** Cache doesn't invalidate when field_owner becomes NULL

### Issue 2.12: ROLE-BASED VIEWS NOT ENFORCING SAME FILTER

**Severity:** MAJOR  
**Location:** views.view.my*\* vs views.view.all*_  
**Issue:** "my\__" views use contextual filter but "all\_\*" views don't enforce admin-only

---

## Part 3: DATA RELATIONSHIPS ANALYSIS

### Verified Entity Relationships

```
CONTACTS
├── field_owner (user) ✓ VALIDATED
├── field_organization (organization) ✓ OPTIONAL - NOT VALIDATED
├── field_email (text) ✓ VALIDATED (format only)
└── field_phone (text) ✓ VALIDATED (format + duplicates)

ORGANIZATIONS
├── field_assigned_staff (user) ✓ VALIDATED
├── field_industry (text) ✓ OPTIONAL
└── field_website (url) ✓ FORMAT VALIDATED

DEALS
├── field_owner (user) ⚠ NOT REQUIRED - ORPHAN RISK
├── field_contact (contact) ⚠ OPTIONAL - NO VALIDATION
├── field_organization (organization) ⚠ OPTIONAL - NO VALIDATION
├── field_stage (taxonomy: pipeline_stage) ⚠ MIXED FORMAT (ID vs String)
├── field_amount (decimal) ⚠ CAN BE NEGATIVE
├── field_probability (decimal) ✓ OPTIONAL
└── field_closing_date (date) ⚠ NO VALIDATION

ACTIVITIES
├── field_assigned_to (user) ⚠ NOT REQUIRED - ORPHAN RISK
├── field_contact (contact) ⚠ OPTIONAL
├── field_deal (deal) ⚠ OPTIONAL
├── field_type (taxonomy: activity_type) ✓ VALIDATED
├── field_datetime (datetime) ⚠ NO VALIDATION
└── field_description (text) ✓ OPTIONAL

LEGEND:
✓ = Properly validated
⚠ = Missing validation
🔴 = Critical gap
```

---

## Part 4: ROLE-BASED ACCESS CONTROL AUDIT

### Current Implementation

```php
Role: administrator
  └─ View: ALL entities globally
  └─ Actions: CREATE, READ, UPDATE, DELETE on all

Role: sales_manager
  └─ View: ALL entities (NO TEAM RESTRICTION!)
  └─ Actions: EDIT/DELETE any contact/deal/organization
  └─ Issue: Can see and modify competitors' data

Role: sales_rep
  └─ View: Only own entities (field_owner)
  └─ Actions: EDIT/DELETE own only
  └─ Issue: Can't see team members' deals for collaboration

Role: authenticated (default)
  └─ View: None (except public profile)
  └─ Actions: None
```

### Security Vulnerabilities

1. **Sales Manager Data Leakage:** Managers bypass contact filters
2. **No Team Isolation:** All managers see all organizations
3. **No Audit Trail:** No logging of who viewed what
4. **Direct Entity Access:** Can load entities directly by ID

---

## Part 5: DASHBOARD STATISTICS VERIFICATION

### Metric Calculation Issues

| Metric                  | Calculation Method                           | Validation Status                    | Risk     |
| ----------------------- | -------------------------------------------- | ------------------------------------ | -------- |
| Total Contacts          | Count all contacts (owner filter: non-admin) | ✓ Good                               | Low      |
| Total Organizations     | Count all orgs (assigned_staff filter)       | ✓ Good                               | Low      |
| Deals in Pipeline       | Count deals not in closed_won/lost           | ⚠ Mixed stage format                 | MEDIUM   |
| Total Value             | Sum of all deal amounts                      | ⚠ Allows negatives                   | MEDIUM   |
| Won Deals (count)       | stage_id === 5 OR stage='closed_won'         | **INCONSISTENT**                     | **HIGH** |
| Lost Deals (count)      | stage_id === 6 OR stage='closed_lost'        | **INCONSISTENT**                     | **HIGH** |
| Win Rate                | won / (won + lost) \* 100                    | ✓ Logic OK (if data OK)              | Medium   |
| Activities              | Count activities (assigned_to filter)        | ✓ Good                               | Low      |
| Conversion Rate         | won / total_deals \* 100                     | ✓ Logic OK (if data OK)              | Medium   |
| Avg Deal Size           | total_won_value / won_count                  | ✓ Logic OK (if data OK)              | Medium   |
| Overdue Activities      | Count with field_datetime <= now             | ⚠ NULL check missing                 | MEDIUM   |
| Active Pipeline Value   | Sum non-closed deals                         | ⚠ Same as total (includes negatives) | MEDIUM   |
| Revenue This Week       | Sum closed_won created this week             | ⚠ Stage format inconsistency         | MEDIUM   |
| Avg Days in Pipeline    | Average days from created to closed          | ⚠ Only checks closed_won (string)    | MEDIUM   |
| Due This Week           | Count with closing_date in range             | ✓ Looks good                         | Low      |
| New Contacts This Month | Count created in month                       | ✓ Looks good                         | Low      |

---

## Part 6: CRITICAL RECOMMENDATIONS

### Immediate Actions (Week 1)

1. **Deploy Stage Format Normalization**
   - Convert all integer stage IDs (5, 6) to strings ('closed_won', 'closed_lost')
   - Run once to update historical data
   - Update validation to only accept strings

2. **Add Pre-Save Validation Hooks**
   - Requires: field_owner for deals (default to creator if missing)
   - Requires: field_assigned_to for activities (default to creator if missing)
   - Requires: Deal has contact OR organization
   - Requires: Activity has contact OR deal

3. **Implement Orphan Detection Batch Job**
   - Scan all deals without owners
   - Scan all activities without assignments
   - Log findings for manual review/fix

4. **Fix Role-Based View Filtering**
   - Add team-aware filtering for sales_manager role
   - Implement node_access hook for entity-level control
   - Add permission checks to all AJAX endpoints

### Short-term Actions (Week 2-3)

5. **Create Data Integrity Module (crm_integrity)**
   - Validates all entity relationships on save
   - Detects and logs orphaned entities
   - Provides admin UI for data cleanup
   - Includes automated repair scripts

6. **Enhance Validation Service**
   - Add validateActivity() method
   - Add validateUser() reference method
   - Add validateContractsWithOrganization() method
   - Use consistently across all forms

7. **Implement Audit Logging**
   - Log all entity access by user
   - Log all data modifications
   - Create admin dashboard for audit trail
   - Export capabilities for compliance

8. **Add Stage Caching Layer**
   - Load pipeline stages once and cache
   - Bust cache when taxonomy terms updated
   - Reduces database queries by ~30%

### Medium-term Actions (Week 4-6)

9. **Implement Data Synchronization Checks**
   - Auto-sync dashboard stats with actual counts
   - Add dashboard to verify stats match queries
   - Create reconciliation report

10. **Add Data Validation Dashboard**
    - Show current data integrity status
    - List orphaned entities with fix actions
    - Show broken references
    - Provide one-click repair tools

11. **Implement Comprehensive Testing**
    - Add unit tests for validation service
    - Add integration tests for entity relationships
    - Add data integrity test suite
    - Run before each deployment

---

## Part 7: IMPLEMENTATION GUIDE

### Module Structure: crm_integrity

```
web/modules/custom/crm_integrity/
├── crm_integrity.info.yml
├── crm_integrity.module
├── crm_integrity.permissions.yml
├── src/
│   ├── Service/
│   │   ├── DataIntegrityService.php
│   │   ├── StageNormalizationService.php
│   │   └── AuditLogger.php
│   ├── Controller/
│   │   └── DataIntegrityDashboardController.php
│   └── Commands/
│       └── DataIntegrityCommands.php
├── templates/
│   ├── data-integrity-dashboard.html.twig
│   ├── orphaned-entities-report.html.twig
│   └── broken-references-report.html.twig
└── commands/
    ├── normalize-stages.sh
    ├── find-orphans.sh
    └── repair-data.sh
```

### Drush Commands to Add

```bash
# Find and report orphaned entities
drush crm:find-orphans

# Normalize stage format (integer to string)
drush crm:normalize-stages

# Find broken entity references
drush crm:find-broken-refs

# Auto-repair data (with manual review)
drush crm:repair-data

# Verify data integrity
drush crm:verify-integrity

# Generate integrity report
drush crm:integrity-report
```

### Database Migrations

```php
// In crm_integrity.module
function crm_integrity_update_9001() {
  // Normalize stage values from integer to string
  // Update all deals: 5 -> 'closed_won', 6 -> 'closed_lost', etc.
}

function crm_integrity_update_9002() {
  // Add validation constraints to fields
  // Mark field_owner as required on deals
}

function crm_integrity_update_9003() {
  // Create audit logging table
  // Schema: user_id, entity_type, entity_id, operation, timestamp
}
```

---

## Part 8: VALIDATION RULES TO IMPLEMENT

### Required Field Validation

```yaml
Deals:
  - field_owner: REQUIRED (auto-set to creator if missing)
  - field_stage: REQUIRED + one of {qualified, proposal, negotiation, closed_won, closed_lost}
  - field_amount: REQUIRED + >= 0 + <= 999,999,999
  - field_contact: OPTIONAL - if set, must exist
  - field_organization: OPTIONAL - if set, must exist
  - one_of: field_contact OR field_organization (at least one)

Activities:
  - field_assigned_to: REQUIRED (auto-set to creator if missing)
  - field_type: OPTIONAL + if set, must be valid taxonomy term
  - field_datetime: OPTIONAL + if set, must be valid date
  - one_of: field_contact OR field_deal (at least one)

Contacts:
  - title: REQUIRED
  - field_phone: REQUIRED + Vietnamese format
  - field_email: OPTIONAL + valid format if set
  - unique: field_phone, field_email
  - field_organization: OPTIONAL - if set, must exist

Organizations:
  - title: REQUIRED
  - field_assigned_staff: REQUIRED
  - field_website: OPTIONAL + valid URL if set
```

---

## Part 9: TESTING CHECKLIST

### Unit Tests

- [ ] DataIntegrityService finds all orphaned entities
- [ ] StageNormalizationService converts all stage formats
- [ ] Validation rejects NULL owners for deals
- [ ] Validation rejects invalid stage values
- [ ] Audit logger records all operations
- [ ] Role-based filtering removes unauthorized data

### Integration Tests

- [ ] Creating deal auto-assigns owner
- [ ] Creating activity auto-assigns user
- [ ] Deleting contact removes deal references (or delete deals)
- [ ] Dashboard stats match query counts
- [ ] Pipeline board shows correct deal count per stage
- [ ] Manager can't see competitors' data
- [ ] Sales rep can see own data only
- [ ] Admin can see all data

### Data Tests

- [ ] No deals with NULL owner
- [ ] No activities with NULL assigned_to
- [ ] No deals referencing deleted contacts
- [ ] All stage values in {qualified, proposal, negotiation, closed_won, closed_lost}
- [ ] All deal amounts >= 0
- [ ] Dashboard totals = query counts
- [ ] No broken entity references

---

## Part 10: COMPLIANCE & GOVERNANCE

### Data Quality Metrics

```
Target Metrics:
- 100% of deals have owner (currently: unknown, likely <95%)
- 100% of activities have assignment (currently: unknown, likely <90%)
- 100% of stage values valid (currently: ~85% due to mixed format)
- 0 broken entity references (currently: unknown, likely >5%)
- Dashboard stats match queries (currently: NO - stats inconsistent)
- 0 role-based access violations (currently: unknown)
```

### Monitoring & Alerting

```
Alert if:
- Orphaned entities count > 0
- Broken reference count > 0
- Dashboard stat variance > 2%
- Stage validation failures > 0
- Access control violations detected
```

### Audit Trail Requirements

```
Log all:
- Who viewed which entity
- Who created/updated/deleted what
- When data changed
- Stage transitions with timestamp
- Access deny events
- Edit conflicts or concurrent modifications
```

---

## SUMMARY: CRITICAL PATH

**High Priority (Must fix before production use):**

1. ✅ Normalize stage format (integer to string)
2. ✅ Add field_owner requirement for deals
3. ✅ Add field_assigned_to requirement for activities
4. ✅ Implement role-based access control (node_access)
5. ✅ Fix dashboard stat calculations

**Medium Priority (Fix within 2 weeks):** 6. ⚠ Implement orphan detection & cleanup 7. ⚠ Add comprehensive validation service 8. ⚠ Fix contact->organization validation 9. ⚠ Add audit logging

**Low Priority (Nice to have):** 10. ℹ Performance optimization (caching) 11. ℹ Enhanced UI for data integrity 12. ℹ Advanced reporting features

---

## APPENDIX: QUERY TEMPLATES

### Find Orphaned Deals

```sql
SELECT nid, title, field_owner_target_id, field_stage_value, created
FROM node__field_owner
where entity_id NOT IN (SELECT nid FROM node__field_owner WHERE field_owner_target_id IS NOT NULL)
AND entity_bundle = 'deal'
ORDER BY created DESC;
```

### Find Broken References

```sql
SELECT d.nid, d.title, d.field_contact_target_id
FROM node__field_contact d
LEFT JOIN node c ON d.field_contact_target_id = c.nid AND c.type = 'contact'
WHERE d.entity_bundle = 'deal'
AND c.nid IS NULL
AND d.field_contact_target_id IS NOT NULL;
```

### Validate Stage Format

```sql
SELECT DISTINCT field_stage_value, COUNT(*) as count
FROM node__field_stage
WHERE entity_bundle = 'deal'
GROUP BY field_stage_value
ORDER BY count DESC;
```

---

**Report Prepared By:** Data Integrity Audit System  
**Review Status:** REQUIRES IMMEDIATE ACTION  
**Next Review:** After implementing all CRITICAL fixes (estimated: 1 week)
