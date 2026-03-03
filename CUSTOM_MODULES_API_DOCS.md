# Phase 3: Custom Modules & APIs Documentation

## Overview

This document provides comprehensive documentation for all custom CRM modules, their APIs, routes, and integration points.

---

## Table of Contents

1. [CRM Dashboard](#crm-dashboard)
2. [CRM Activity Log](#crm-activity-log)
3. [CRM Quick Add](#crm-quick-add)
4. [CRM Kanban Pipeline](#crm-kanban-pipeline)
5. [CRM Import/Export](#crm-import-export)
6. [CRM Register](#crm-register)
7. [CRM Contact 360](#crm-contact-360)
8. [CRM Teams](#crm-teams)
9. [CRM Notifications](#crm-notifications)
10. [Queue System](#queue-system)
11. [Fixture System](#fixture-system)

---

## CRM Dashboard

**Module:** `crm_dashboard`  
**Path:** `web/modules/custom/crm_dashboard`

### Purpose

Provides main CRM dashboard with statistics, charts, and quick access to key features.

### Routes

#### `/admin/crm`

- **Controller:** `CrmDashboardController::dashboard`
- **Permission:** `access crm dashboard`
- **Purpose:** Admin dashboard view

#### `/crm/dashboard`

- **Controller:** `CrmDashboardController::userDashboard`
- **Permission:** `access content`
- **Purpose:** User-facing dashboard for sales reps

#### `/crm/dashboard-new`

- **Controller:** `CrmDashboardController::newDashboard`
- **Permission:** `access content`
- **Purpose:** Enhanced dashboard with charts

### Key Features

- **Statistics Widgets:** Show counts for Contacts, Deals, Organizations
- **Recent Activity:** Display latest CRM activities
- **Deal Pipeline:** Visual pipeline status
- **Sales Performance:** Charts and metrics

### Usage Example

```php
// Get dashboard statistics
$stats = \Drupal::service('crm_dashboard.statistics')->getStats();

// Returns:
// [
//   'contacts' => 16,
//   'deals' => 10,
//   'organizations' => 16,
//   'activities' => 21,
//   'total_deal_value' => 1191000,
// ]
```

---

## CRM Activity Log

**Module:** `crm_activity_log`  
**Path:** `web/modules/custom/crm_activity_log`

### Purpose

Manage activity logging for Contacts and Deals - calls, emails, meetings, tasks.

### Routes

#### `/node/{node}/activities`

- **Controller:** `ActivityLogController::activityTab`
- **Title:** "Lịch sử tương tác"
- **Permission:** `access content`
- **Purpose:** Display activity timeline for a Contact or Deal

#### `/crm/activity/log-call/{contact}`

- **Controller:** `ActivityLogController::logCallForm`
- **Permission:** `create activity content`
- **Purpose:** Quick form to log a phone call

#### `/crm/activity/log-call/{contact}/submit`

- **Controller:** `ActivityLogController::logCallSubmit`
- **Method:** POST
- **Purpose:** Process call logging form

#### `/crm/activity/schedule-meeting/{contact}`

- **Controller:** `ActivityLogController::scheduleMeetingForm`
- **Permission:** `create activity content`
- **Purpose:** Quick form to schedule a meeting

#### `/crm/activity/schedule-meeting/{contact}/submit`

- **Controller:** `ActivityLogController::scheduleMeetingSubmit`
- **Method:** POST
- **Purpose:** Process meeting scheduling form

### Activity Types

- **Call:** Phone conversations with contacts
- **Email:** Email communications
- **Meeting:** In-person or virtual meetings
- **Task:** Follow-up tasks and reminders

### Usage Example

```php
// Create an activity programmatically
use Drupal\node\Entity\Node;

$activity = Node::create([
  'type' => 'activity',
  'title' => 'Follow-up Call',
  'field_type' => ['target_id' => $call_term_id],
  'field_contact' => ['target_id' => $contact_nid],
  'field_deal' => ['target_id' => $deal_nid],
  'field_datetime' => date('Y-m-d\TH:i:s'),
  'field_description' => [
    'value' => 'Discussed pricing options',
    'format' => 'plain_text',
  ],
  'field_outcome' => [
    'value' => '[Outcome: Khách nghe máy - Quan tâm] Will review proposal',
    'format' => 'plain_text',
  ],
  'field_assigned_to' => ['target_id' => $user_id],
  'status' => 1,
]);
$activity->save();
```

---

## CRM Quick Add

**Module:** `crm_quickadd`  
**Path:** `web/modules/custom/crm_quickadd`

### Purpose

Provides AJAX-powered quick add forms for Contacts, Deals, and Organizations without page reload.

### Routes

#### `/crm/quickadd/contact`

- **Controller:** `QuickAddController::contactForm`
- **Permission:** `create contact content`
- **Purpose:** Show quick add contact modal

#### `/crm/quickadd/contact/submit`

- **Controller:** `QuickAddController::contactSubmit`
- **Method:** POST
- **Purpose:** Process contact creation

#### `/crm/quickadd/deal`

- **Controller:** `QuickAddController::dealForm`
- **Permission:** `create deal content`
- **Purpose:** Show quick add deal modal

#### `/crm/quickadd/deal/submit`

- **Controller:** `QuickAddController::dealSubmit`
- **Method:** POST
- **Purpose:** Process deal creation

#### `/crm/quickadd/organization`

- **Controller:** `QuickAddController::organizationForm`
- **Permission:** `create organization content`
- **Purpose:** Show quick add organization modal

#### `/crm/quickadd/organization/submit`

- **Controller:** `QuickAddController::organizationSubmit`
- **Method:** POST
- **Purpose:** Process organization creation

#### `/crm/quickadd/check-duplicate`

- **Controller:** `QuickAddController::checkDuplicate`
- **Method:** POST
- **Purpose:** Check for duplicate records before saving

### Features

- **AJAX Forms:** No page reload required
- **Duplicate Detection:** Checks email for contacts, name for organizations
- **Validation:** Client and server-side validation
- **Auto-Assignment:** Automatically assigns owner to current user

### Usage Example

```javascript
// Trigger quick add from JavaScript
jQuery.ajax({
  url: "/crm/quickadd/contact",
  method: "GET",
  success: function (response) {
    // Show modal with form
    showModal(response);
  },
});
```

---

## CRM Kanban Pipeline

**Module:** `crm_kanban`  
**Path:** `web/modules/custom/crm_kanban`

### Purpose

Provides drag-and-drop Kanban board for managing deal pipeline stages.

### Routes

#### `/crm/pipeline`

- **Controller:** `KanbanController::pipeline`
- **Title:** "Pipeline"
- **Permission:** `access content`
- **Purpose:** Display Kanban board view

#### `/crm/pipeline/update-stage`

- **Controller:** `KanbanController::updateStage`
- **Method:** POST
- **Purpose:** AJAX endpoint to update deal stage

### Pipeline Stages

Default stages (from `pipeline_stage` taxonomy):

1. **Prospect** - Initial contact
2. **Qualified** - Qualified lead
3. **Proposal** - Proposal sent
4. **Negotiation** - Contract negotiation
5. **Won** - Deal closed successfully
6. **Lost** - Deal lost

### Usage Example

```javascript
// Update deal stage via AJAX
jQuery.ajax({
  url: "/crm/pipeline/update-stage",
  method: "POST",
  data: {
    deal_id: 123,
    new_stage_id: 45,
  },
  success: function (response) {
    console.log("Stage updated");
  },
});
```

---

## CRM Import/Export

**Module:** `crm_import_export`  
**Path:** `web/modules/custom/crm_import_export`

### Purpose

Import and export Contacts, Deals, and Organizations via CSV files.

### Routes

#### `/admin/crm/import`

- **Controller:** `ImportExportController::importPage`
- **Permission:** `administer crm`
- **Purpose:** Import dashboard

#### `/admin/crm/import/contacts`

- **Controller:** `ImportExportController::importContacts`
- **Permission:** `administer crm`
- **Purpose:** Import contacts from CSV

#### `/admin/crm/import/deals`

- **Controller:** `ImportExportController::importDeals`
- **Permission:** `administer crm`
- **Purpose:** Import deals from CSV

#### `/admin/crm/export/contacts`

- **Controller:** `ImportExportController::exportContacts`
- **Permission:** `access content`
- **Purpose:** Export contacts to CSV

#### `/admin/crm/export/deals`

- **Controller:** `ImportExportController::exportDeals`
- **Permission:** `access content`
- **Purpose:** Export deals to CSV

### CSV Format - Contacts

```csv
Name,Email,Phone,Position,Company,Lead Source,Owner
John Doe,john@example.com,+1-555-0100,CEO,Acme Inc,Website,salesrep1
```

### CSV Format - Deals

```csv
Deal Title,Amount,Probability,Stage,Contact,Organization,Closing Date,Owner
Enterprise License,150000,75,Proposal,john@example.com,Acme Inc,2026-04-15,salesrep1
```

### Usage with Queue System

For large imports, use the queue system:

```bash
# Add import to queue
ddev drush scr scripts/csv_import_queue.php contacts sample_contacts.csv

# Process queue
ddev drush scr scripts/process_queue.php crm_csv_import
```

---

## CRM Register

**Module:** `crm_register`  
**Path:** `web/modules/custom/crm_register`

### Purpose

Custom registration page with CRM-specific styling and fields.

### Routes

#### `/register`

- **Controller:** `RegisterController::registerPage`
- **Permission:** Anonymous
- **Purpose:** Public registration page

### Features

- **Custom Styling:** Professional registration UI
- **Role Assignment:** Automatically assigns appropriate CRM roles
- **Welcome Email:** Sends welcome email to new users
- **Profile Fields:** Collects additional profile information

---

## CRM Contact 360

**Module:** `crm_contact360`  
**Path:** `web/modules/custom/crm_contact360`

### Purpose

Provides 360-degree view of contacts with all related information in one place.

### Features

- **Contact Overview:** Basic information and status
- **Related Deals:** All deals associated with contact
- **Activity Timeline:** Chronological activity history
- **Related Organizations:** Company relationships
- **Documents:** Associated files and documents
- **Notes:** Internal notes about contact

### Usage

Integrated into Contact node view as additional tabs and widgets.

---

## CRM Teams

**Module:** `crm_teams`  
**Path:** `web/modules/custom/crm_teams`

### Purpose

Manage sales teams, team assignments, and team performance tracking.

### Features

- **Team Creation:** Create and manage sales teams
- **Member Assignment:** Assign users to teams
- **Team Hierarchy:** Manager and member roles
- **Performance Metrics:** Team-level statistics
- **Territory Management:** Assign territories to teams

---

## CRM Notifications

**Module:** `crm_notifications`  
**Path:** `web/modules/custom/crm_notifications`

### Purpose

Real-time notifications for CRM events.

### Notification Types

- **Deal Stage Change:** When deal moves to new stage
- **New Lead Assignment:** When new lead is assigned
- **Activity Reminder:** Upcoming meetings and tasks
- **Deal Closing:** Deals approaching closing date
- **Mention:** When user is mentioned in notes

### Usage Example

```php
// Send notification
$notification_service = \Drupal::service('crm_notifications.manager');
$notification_service->notify($user_id, [
  'type' => 'deal_stage_change',
  'title' => 'Deal moved to Negotiation',
  'message' => 'Enterprise License deal is now in Negotiation stage',
  'link' => '/node/123',
]);
```

---

## Queue System

### Overview

The queue system handles bulk operations asynchronously to prevent timeouts and improve performance.

### Available Queues

#### `crm_csv_import`

- **Purpose:** Process CSV import in batches
- **Batch Size:** 25 rows
- **Usage:** `ddev drush scr scripts/csv_import_queue.php contacts file.csv`

#### `crm_bulk_delete`

- **Purpose:** Delete nodes in batches
- **Batch Size:** 50 nodes
- **Usage:** `ddev drush scr scripts/bulk_delete.php sample`

#### `crm_bulk_update`

- **Purpose:** Update multiple nodes in batches
- **Batch Size:** 50 nodes

#### `crm_email_notification`

- **Purpose:** Send bulk email notifications
- **Batch Size:** 10 emails

### Commands

```bash
# Check queue status
ddev drush scr scripts/process_queue.php status

# Process specific queue
ddev drush scr scripts/process_queue.php crm_csv_import

# Process all queues
ddev drush scr scripts/process_queue.php all
```

---

## Fixture System

### Overview

The fixture system separates sample/development data from production code using YAML files.

### Directory Structure

```
fixtures/
├── development/          # Development sample data
│   ├── organizations.yml
│   ├── contacts.yml
│   ├── deals.yml
│   └── activities.yml
└── production/           # Production seed data
    └── (empty - for production use)
```

### Fixture Format

#### Organizations

```yaml
organizations:
  - id: acme_corp
    title: "Acme Corporation"
    website: "https://acme.com"
    address: "123 Main St, New York, NY"
    industry: Technology
    annual_revenue: 5000000
    employees_count: 150
```

#### Contacts

```yaml
contacts:
  - id: john_smith
    title: "John Smith"
    email: "john.smith@acme.com"
    phone: "+1-555-0101"
    position: "CEO"
    organization: acme_corp
    lead_source: Website
```

#### Deals

```yaml
deals:
  - id: enterprise_license
    title: "Enterprise Software License"
    amount: 150000
    probability: 75
    stage: Proposal
    contact: john_smith
    organization: acme_corp
    closing_date: "2026-04-15"
```

#### Activities

```yaml
activities:
  - id: call_001
    title: "Initial Discovery Call"
    type: Call
    contact: john_smith
    deal: enterprise_license
    datetime: "2026-02-20T10:00:00"
    description: "Discussed business needs..."
    outcome: "[Outcome: Khách nghe máy - Quan tâm]"
    assigned_to: salesrep1
```

### Commands

```bash
# Load development fixtures
ddev drush scr scripts/load_fixtures.php development

# Load production fixtures
ddev drush scr scripts/load_fixtures.php production
```

### Benefits

- ✅ **Separation of Concerns:** Data separate from code
- ✅ **Version Control:** YAML fixtures in Git
- ✅ **Easy Updates:** Modify fixtures without touching scripts
- ✅ **Environment-Specific:** Different data for dev/prod
- ✅ **Reusable:** Load fixtures multiple times for testing

---

## API Integration Examples

### Creating a Contact via API

```php
use Drupal\node\Entity\Node;

$contact = Node::create([
  'type' => 'contact',
  'title' => 'Jane Doe',
  'field_email' => 'jane@example.com',
  'field_phone' => '+1-555-0200',
  'field_position' => 'VP Sales',
  'field_owner' => ['target_id' => $current_user->id()],
  'status' => 1,
]);
$contact->save();
```

### Creating a Deal with References

```php
$deal = Node::create([
  'type' => 'deal',
  'title' => 'Cloud Services Contract',
  'field_amount' => 200000,
  'field_probability' => 70,
  'field_stage' => ['target_id' => $qualified_term_id],
  'field_contact' => ['target_id' => $contact_nid],
  'field_organization' => ['target_id' => $org_nid],
  'field_closing_date' => '2026-06-30',
  'field_owner' => ['target_id' => $current_user->id()],
  'status' => 1,
]);
$deal->save();
```

### Logging an Activity

```php
$activity = Node::create([
  'type' => 'activity',
  'title' => 'Discovery Call',
  'field_type' => ['target_id' => $call_term_id],
  'field_contact' => ['target_id' => $contact_nid],
  'field_deal' => ['target_id' => $deal_nid],
  'field_datetime' => date('Y-m-d\TH:i:s'),
  'field_description' => [
    'value' => 'Discussed requirements and timeline',
    'format' => 'plain_text',
  ],
  'field_outcome' => [
    'value' => '[Outcome: Khách nghe máy - Quan tâm]',
    'format' => 'plain_text',
  ],
  'field_assigned_to' => ['target_id' => $current_user->id()],
  'status' => 1,
]);
$activity->save();
```

---

## Maintenance & Troubleshooting

### Clear All Queues

```bash
ddev drush ev "
\$queues = ['crm_csv_import', 'crm_bulk_delete', 'crm_bulk_update', 'crm_email_notification'];
foreach (\$queues as \$name) {
  \$queue = \Drupal::service('queue')->get(\$name);
  \$queue->deleteQueue();
  echo 'Cleared: ' . \$name . PHP_EOL;
}
"
```

### Rebuild Fixtures

```bash
# Delete all sample data
ddev drush sqlq "DELETE FROM node_field_data WHERE nid > 48"

# Reload fixtures
ddev drush scr scripts/load_fixtures.php development

# Re-index
ddev drush search-api:index
ddev drush cr
```

### Check Module Status

```bash
# Check if custom modules are enabled
ddev drush pm:list --type=Module --status=enabled | grep crm_
```

---

## Performance Optimization

### Caching Strategy

- **Database Indexes:** All entity reference fields indexed (Phase 1)
- **Search API:** Full-text search with database backend (Phase 1)
- **Views Caching:** Tag-based caching on all listing views (Phase 2)
- **Page Caching:** Dynamic page cache for authenticated users (Phase 2)

### Queue Processing

- Process queues during off-peak hours
- Use cron to automatically process queues
- Monitor queue length to prevent backlog

### Bulk Operations

- Always use queue system for operations > 50 items
- Batch size: 25-50 items per job
- Include error handling and rollback capability

---

## Security Considerations

### Permissions

- All custom routes require appropriate permissions
- Owner field enforcement in views
- Access checks on all entity operations

### Data Validation

- Email validation on contacts
- Numeric validation on deal amounts
- Date validation on closing dates
- Duplicate detection on critical fields

### Input Sanitization

- All user input passed through Drupal API
- CSRF protection on all forms
- XSS prevention through proper output escaping

---

## Testing

### Manual Testing Scripts

```bash
# Test views
ddev drush scr scripts/test_views.php

# Test search
ddev drush search-api:search crm_contacts_index "@example.com"

# Test customer portal
ddev drush scr scripts/test_customer_portal.php

# Comprehensive validation
ddev drush scr scripts/validate_phase1_phase2.php
```

### Automated Testing

Consider implementing:

- PHPUnit tests for custom services
- Functional tests for forms and workflows
- Integration tests for API endpoints
- Performance tests for bulk operations

---

## Future Enhancements

### Planned Features

- **REST API:** Full RESTful API for external integrations
- **Mobile App:** Companion mobile application
- **Advanced Reporting:** Custom report builder
- **AI Integration:** Predictive deal scoring
- **Email Integration:** Two-way email sync

### Module Roadmap

1. **crm_api** - RESTful API module
2. **crm_reports** - Advanced reporting
3. **crm_automation** - Workflow automation
4. **crm_forecasting** - Sales forecasting
5. **crm_mobile_sync** - Mobile data synchronization

---

## Support & Resources

### Documentation

- **Phase 1 & 2:** See [PHASE_1_2_FINAL_VALIDATION.md](PHASE_1_2_FINAL_VALIDATION.md)
- **Quick Reference:** See [PHASE_1_2_QUICK_REFERENCE.md](PHASE_1_2_QUICK_REFERENCE.md)
- **System Analysis:** See [SYSTEM_GAP_ANALYSIS.md](SYSTEM_GAP_ANALYSIS.md)

### Useful Commands

```bash
# List all custom routes
ddev drush route:debug | grep crm_

# Check field definitions
ddev drush field:info

# Export configuration
ddev drush config:export

# Generate module
ddev drush generate module-standard
```

---

**Documentation Version:** 1.0  
**Last Updated:** March 2, 2026  
**Maintainer:** CRM Development Team
