# Open CRM - Complete Technical Architecture Documentation (Part 2)

_Continuation from Part 1_

---

## 8. Import/Export Module

**Location:** `web/modules/custom/crm_import_export/`

**Purpose:** CSV import/export for contacts, deals, organizations

### 8.1 crm_import_export.info.yml

```yaml
name: "CRM Import/Export"
type: module
description: "Advanced CSV import/export for CRM data with field mapping functionality"
package: CRM
core_version_requirement: ^11
```

### 8.2 ImportController.php (Complete Analysis)

**Purpose:** Provides UI for importing CRM data from CSV files

#### Method: `importPage()`

**What it does:**

- Renders hub page with import options
- Shows available import types: Contacts, Deals, Organizations, Activities
- Displays required fields for each type
- Links to import forms

**UI Structure:**

```
┌────────────────────────────────────────────────────┐
│  Import Center                                      │
├────────────────────────────────────────────────────┤
│                                                     │
│  ┌──────────────────┐  ┌──────────────────┐       │
│  │ Import Contacts  │  │ Import Deals     │       │
│  │                  │  │                  │       │
│  │ Required:        │  │ Required:        │       │
│  │ • Name           │  │ • Deal Name      │       │
│  │ • Phone/Email    │  │ • Amount         │       │
│  │ • Owner          │  │ • Stage          │       │
│  │                  │  │ • Owner          │       │
│  │ [Import CSV ➜]   │  │ [Import CSV ➜]   │       │
│  └──────────────────┘  └──────────────────┘       │
│                                                     │
│  ┌──────────────────┐  ┌──────────────────┐       │
│  │ Import Org...    │  │ Import Activities│       │
│  │ ...              │  │ ...              │       │
│  └──────────────────┘  └──────────────────┘       │
└────────────────────────────────────────────────────┘
```

**Implementation details:**

```php
public function importPage() {
  $html = <<<HTML
<div class="import-container">
  <div class="import-header">
    <h1>Import Center</h1>
    <p>Import your CRM data from CSV files</p>
  </div>

  <div class="import-grid">
    <!-- Contact Import Card -->
    <div class="import-card">
      <div class="import-card-header">
        <div class="import-icon blue">
          <i data-lucide="users" width="28" height="28"></i>
        </div>
        <div class="import-card-title">
          <h2>Import Contacts</h2>
          <p>Upload CSV file with contact data</p>
        </div>
      </div>

      <div class="import-card-body">
        <div class="field-list">
          <h4>Required Fields:</h4>
          <div class="field-tags">
            <span class="field-tag">name</span>
            <span class="field-tag">phone</span>
            <span class="field-tag">email</span>
          </div>
        </div>

        <div class="field-list">
          <h4>Optional Fields:</h4>
          <ul>
            <li>organization</li>
            <li>position</li>
            <li>customer_type</li>
            <li>source</li>
            <li>notes</li>
          </ul>
        </div>
      </div>

      <div class="import-actions">
        <a href="/admin/crm/import/contacts" class="btn btn-primary">
          <i data-lucide="upload"></i>
          Import Contacts
        </a>
        <a href="/files/templates/contacts_template.csv" class="btn btn-secondary">
          <i data-lucide="download"></i>
          Template
        </a>
      </div>
    </div>

    <!-- Similar cards for Deals, Organizations, Activities -->
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
HTML;

  return [
    '#markup' => Markup::create($html),
    '#attached' => ['library' => ['crm_import_export/import_styles']],
  ];
}
```

#### Method: `importContactsCsv(Request $request)`

**Purpose:** Processes uploaded CSV file and creates contact nodes

**Step-by-step logic:**

```php
public function importContactsCsv(Request $request) {
  // STEP 1: Handle file upload
  $file = $request->files->get('csv_file');

  if (!$file) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'No file uploaded',
    ], 400);
  }

  // STEP 2: Validate file type
  $allowed = ['text/csv', 'application/csv', 'text/plain'];
  if (!in_array($file->getMimeType(), $allowed)) {
    return new JsonResponse([
      'success' => FALSE,
      'message' => 'Invalid file type. Please upload CSV.',
    ], 400);
  }

  // STEP 3: Parse CSV file
  $csv_data = [];
  if (($handle = fopen($file->getPathname(), 'r')) !== FALSE) {
    // First row = headers
    $headers = fgetcsv($handle);

    // Remaining rows = data
    while (($row = fgetcsv($handle)) !== FALSE) {
      $csv_data[] = array_combine($headers, $row);
    }
    fclose($handle);
  }

  // STEP 4: Validate required fields
  $required = ['name', 'phone', 'email'];
  foreach ($csv_data as $index => $row) {
    foreach ($required as $field) {
      if (empty($row[$field])) {
        \Drupal::messenger()->addWarning("Row {$index}: Missing required field '{$field}'");
      }
    }
  }

  // STEP 5: Batch import contacts
  $imported = 0;
  $errors = [];

  foreach ($csv_data as $index => $row) {
    try {
      // Check for duplicate phone
      $existing = \Drupal::entityQuery('node')
        ->condition('type', 'contact')
        ->condition('field_phone', $row['phone'])
        ->accessCheck(FALSE)
        ->count()
        ->execute();

      if ($existing > 0) {
        $errors[] = "Row {$index}: Phone {$row['phone']} already exists";
        continue;
      }

      // Handle organization lookup/creation
      $org_id = NULL;
      if (!empty($row['organization'])) {
        // Try to find existing organization
        $org_query = \Drupal::entityQuery('node')
          ->condition('type', 'organization')
          ->condition('title', $row['organization'])
          ->accessCheck(FALSE)
          ->range(0, 1);
        $org_ids = $org_query->execute();

        if (!empty($org_ids)) {
          $org_id = reset($org_ids);
        } else {
          // Create new organization
          $org = Node::create([
            'type' => 'organization',
            'title' => $row['organization'],
            'field_assigned_staff' => ['target_id' => \Drupal::currentUser()->id()],
            'uid' => \Drupal::currentUser()->id(),
            'status' => 1,
          ]);
          $org->save();
          $org_id = $org->id();
        }
      }

      // Handle taxonomy term lookup (customer_type, source)
      $customer_type_id = NULL;
      if (!empty($row['customer_type'])) {
        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties([
            'vid' => 'crm_customer_type',
            'name' => $row['customer_type'],
          ]);
        if (!empty($terms)) {
          $term = reset($terms);
          $customer_type_id = $term->id();
        }
      }

      // Create contact node
      $contact = Node::create([
        'type' => 'contact',
        'title' => $row['name'],
        'field_email' => $row['email'] ?? '',
        'field_phone' => $row['phone'] ?? '',
        'field_position' => $row['position'] ?? '',
        'field_organization' => $org_id ? ['target_id' => $org_id] : NULL,
        'field_customer_type' => $customer_type_id ? ['target_id' => $customer_type_id] : NULL,
        'field_notes' => $row['notes'] ?? '',
        'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $contact->save();

      $imported++;

    } catch (\Exception $e) {
      $errors[] = "Row {$index}: " . $e->getMessage();
      \Drupal::logger('crm_import')->error('Import error: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  // STEP 6: Return results
  return new JsonResponse([
    'success' => TRUE,
    'message' => "Successfully imported {$imported} contacts",
    'imported' => $imported,
    'errors' => $errors,
    'total' => count($csv_data),
  ]);
}
```

**Key features:**

1. **Duplicate detection:** Checks for existing phone numbers
2. **Organization lookup:** Finds existing org or creates new one
3. **Taxonomy matching:** Maps CSV values to term names
4. **Error handling:** Logs errors per row, continues processing
5. **Owner assignment:** All imported contacts owned by importer

**CSV format example:**

```csv
name,phone,email,organization,position,customer_type,source,notes
John Doe,0912345678,john@example.com,Acme Corp,CEO,Hot Lead,Website,"VIP customer"
Jane Smith,0987654321,jane@company.vn,Tech Inc,CTO,Warm Lead,Referral,"Follow up next week"
```

### 8.3 ExportController.php

**Purpose:** Generates CSV exports of CRM data

#### Method: `exportContacts()`

```php
public function exportContacts() {
  // STEP 1: Query contacts (filtered by ownership for non-admins)
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('status', 1)
    ->accessCheck(TRUE) // Apply access control automatically
    ->sort('created', 'DESC');

  $nids = $query->execute();
  $contacts = Node::loadMultiple($nids);

  // STEP 2: Build CSV data
  $csv_rows = [];

  // Header row
  $csv_rows[] = [
    'ID',
    'Name',
    'Phone',
    'Email',
    'Organization',
    'Position',
    'Customer Type',
    'Source',
    'Owner',
    'Created Date',
  ];

  // Data rows
  foreach ($contacts as $contact) {
    // Get organization name
    $org_name = '';
    if ($contact->hasField('field_organization') && !$contact->get('field_organization')->isEmpty()) {
      $org = $contact->get('field_organization')->entity;
      if ($org) {
        $org_name = $org->getTitle();
      }
    }

    // Get taxonomy term names
    $customer_type = '';
    if ($contact->hasField('field_customer_type') && !$contact->get('field_customer_type')->isEmpty()) {
      $term = $contact->get('field_customer_type')->entity;
      if ($term) {
        $customer_type = $term->getName();
      }
    }

    $source = '';
    if ($contact->hasField('field_source') && !$contact->get('field_source')->isEmpty()) {
      $term = $contact->get('field_source')->entity;
      if ($term) {
        $source = $term->getName();
      }
    }

    // Get owner name
    $owner_name = '';
    if ($contact->hasField('field_owner') && !$contact->get('field_owner')->isEmpty()) {
      $owner = $contact->get('field_owner')->entity;
      if ($owner) {
        $owner_name = $owner->getDisplayName();
      }
    }

    $csv_rows[] = [
      $contact->id(),
      $contact->getTitle(),
      $contact->get('field_phone')->value ?? '',
      $contact->get('field_email')->value ?? '',
      $org_name,
      $contact->get('field_position')->value ?? '',
      $customer_type,
      $source,
      $owner_name,
      date('Y-m-d H:i:s', $contact->getCreatedTime()),
    ];
  }

  // STEP 3: Generate CSV file
  $filename = 'contacts_export_' . date('Y-m-d_H-i-s') . '.csv';

  $response = new Response();
  $response->headers->set('Content-Type', 'text/csv');
  $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

  // Open output stream
  $output = fopen('php://temp', 'r+');

  // Write CSV rows
  foreach ($csv_rows as $row) {
    fputcsv($output, $row);
  }

  // Read content
  rewind($output);
  $csv_content = stream_get_contents($output);
  fclose($output);

  $response->setContent($csv_content);

  return $response;
}
```

**Response headers:**

```http
HTTP/1.1 200 OK
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="contacts_export_2026-03-06_14-30-25.csv"
Content-Length: 25648

ID,Name,Phone,Email,Organization,Position...
123,"John Doe","0912345678","john@example.com","Acme Corp","CEO"...
```

**Browser behavior:**

- Receives response
- Triggers "Save As" dialog
- User saves CSV file
- File can be opened in Excel/Google Sheets

### 8.4 Import Form (ImportContactsForm.php)

**Purpose:** Drupal Form API form for CSV upload with field mapping

**Form structure:**

```php
public function buildForm(array $form, FormStateInterface $form_state) {
  $form['#attributes']['enctype'] = 'multipart/form-data';

  $form['instructions'] = [
    '#markup' => '<div class="import-instructions">
      <h3>How to import contacts:</h3>
      <ol>
        <li>Download the CSV template</li>
        <li>Fill in your contact data</li>
        <li>Upload the CSV file below</li>
        <li>Map CSV columns to contact fields</li>
        <li>Click "Import"</li>
      </ol>
    </div>',
  ];

  $form['csv_file'] = [
    '#type' => 'file',
    '#title' => $this->t('CSV File'),
    '#description' => $this->t('Upload a CSV file with contact data (max 10MB)'),
    '#required' => TRUE,
    '#upload_validators' => [
      'file_validate_extensions' => ['csv txt'],
      'file_validate_size' => [10 * 1024 * 1024], // 10MB
    ],
  ];

  $form['field_mapping'] = [
    '#type' => 'fieldset',
    '#title' => $this->t('Field Mapping'),
    '#description' => $this->t('Map CSV columns to contact fields'),
  ];

  $contact_fields = [
    'title' => 'Name',
    'field_email' => 'Email',
    'field_phone' => 'Phone',
    'field_organization' => 'Organization',
    'field_position' => 'Position',
    'field_customer_type' => 'Customer Type',
    'field_source' => 'Source',
    'field_notes' => 'Notes',
  ];

  foreach ($contact_fields as $field_name => $field_label) {
    $form['field_mapping'][$field_name] = [
      '#type' => 'select',
      '#title' => $field_label,
      '#options' => [
        '' => '- Skip this field -',
        // Options will be populated with CSV column names after upload
      ],
      '#required' => in_array($field_name, ['title', 'field_phone']),
    ];
  }

  $form['duplicate_handling'] = [
    '#type' => 'radios',
    '#title' => $this->t('Duplicate Handling'),
    '#options' => [
      'skip' => $this->t('Skip duplicates (recommended)'),
      'update' => $this->t('Update existing contacts'),
      'create' => $this->t('Create new contacts (may create duplicates)'),
    ],
    '#default_value' => 'skip',
  ];

  $form['actions'] = [
    '#type' => 'actions',
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Import Contacts'),
    '#button_type' => 'primary',
  ];

  return $form;
}
```

**Form submission:**

```php
public function submitForm(array &$form, FormStateInterface $form_state) {
  $file = $form_state->getValue('csv_file');
  $field_mapping = $form_state->getValue('field_mapping');
  $duplicate_handling = $form_state->getValue('duplicate_handling');

  // Process CSV with batch API for large files
  $batch = [
    'title' => $this->t('Importing contacts...'),
    'operations' => [
      [
        '\Drupal\crm_import_export\Batch\ImportBatch::processContacts',
        [$file, $field_mapping, $duplicate_handling],
      ],
    ],
    'finished' => '\Drupal\crm_import_export\Batch\ImportBatch::finished',
  ];

  batch_set($batch);
}
```

**Batch processing (for large datasets):**

```php
// ImportBatch.php
class ImportBatch {
  public static function processContacts($file, $field_mapping, $duplicate_handling, &$context) {
    if (!isset($context['sandbox']['progress'])) {
      // Initialize
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = self::countCsvRows($file);
      $context['results']['imported'] = 0;
      $context['results']['skipped'] = 0;
      $context['results']['updated'] = 0;
    }

    // Process 50 rows per batch iteration
    $limit = 50;
    $csv_data = self::readCsvChunk($file, $context['sandbox']['progress'], $limit);

    foreach ($csv_data as $row) {
      // Import logic here (similar to earlier example)
      $context['sandbox']['progress']++;
      $context['results']['imported']++;

      // Update progress bar
      $context['message'] = t('Processed @current of @max contacts', [
        '@current' => $context['sandbox']['progress'],
        '@max' => $context['sandbox']['max'],
      ]);
    }

    // Finished?
    if ($context['sandbox']['progress'] >= $context['sandbox']['max']) {
      $context['finished'] = 1;
    } else {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  public static function finished($success, $results, $operations) {
    if ($success) {
      $message = t('Imported @count contacts', ['@count' => $results['imported']]);
      \Drupal::messenger()->addStatus($message);
    } else {
      \Drupal::messenger()->addError(t('Import failed'));
    }
  }
}
```

**Batch processing benefits:**

- ✅ Handles large CSV files (1000+ rows)
- ✅ Shows progress bar to user
- ✅ Avoids PHP timeout errors
- ✅ Allows cancellation mid-process

### 8.5 Summary: Import/Export Module

**What it provides:**

- ✅ CSV import for all CRM types
- ✅ Field mapping UI
- ✅ Duplicate detection
- ✅ Batch processing for large imports
- ✅ CSV export with access control
- ✅ Template downloads

**Architecture:**

- Controllers for pages
- Form API for upload forms
- Batch API for processing
- Response objects for CSV downloads

**Improvement opportunities:**

- Add Excel (.xlsx) support
- Add import scheduling (cron)
- Add rollback functionality ("Undo last import")
- Add data validation rules (email format, phone format)
- Add preview before import

---

## 9. All Other Custom Modules

Let me document the remaining 8 modules:

### 9.1 crm_actions Module

**Location:** `web/modules/custom/crm_actions/`

**Purpose:** Provides global navigation bar on all CRM pages

#### crm_actions.module (Complete Analysis)

**Key hook: `hook_page_top()`**

```php
function crm_actions_page_top(array &$page_top) {
  $current_user = \Drupal::currentUser();

  // Only show for authenticated users
  if ($current_user->isAuthenticated()) {
    $current_path = \Drupal::request()->getRequestUri();

    // Show on CRM pages
    if (str_contains($current_path, '/crm/') ||
        str_contains($current_path, '/node/') ||
        str_contains($current_path, '/app/')) {

      $page_top['crm_global_nav'] = [
        '#markup' => Markup::create(_crm_actions_build_navbar()),
        '#attached' => [
          'library' => ['crm_actions/global_nav'],
        ],
      ];
    }
  }
}
```

**What this does:**

- Checks if user is logged in
- Checks if current URL matches CRM pages
- Injects navigation HTML at top of page
- Attaches CSS/JS library

**Navigation structure:**

```
┌─────────────────────────────────────────────────────────┐
│ [OpenCRM Logo] Dashboard | Contacts | Deals | Pipeline | │
│                 Activities | Organizations              │
└─────────────────────────────────────────────────────────┘
```

**Navigation items (dynamic based on role):**

| For Admin         | For Sales Rep    |
| ----------------- | ---------------- |
| All Contacts      | My Contacts      |
| All Deals         | My Deals         |
| All Pipeline      | My Pipeline      |
| All Activities    | My Activities    |
| All Organizations | My Organizations |

**Implementation:**

```php
function _crm_actions_build_navbar() {
  $current_user = \Drupal::currentUser();
  $is_admin = in_array('administrator', $current_user->getRoles()) || $current_user->id() == 1;

  $items = [
    [
      'url' => '/crm/dashboard',
      'label' => 'Dashboard',
      'icon' => 'layout-dashboard',
    ],
    [
      'url' => $is_admin ? '/crm/all-contacts' : '/crm/my-contacts',
      'label' => $is_admin ? 'All Contacts' : 'Contacts',
      'icon' => 'users',
    ],
    // ... more items
  ];

  $html = '<div class="crm-global-nav">';
  foreach ($items as $item) {
    $html .= '<a href="' . $item['url'] . '">';
    $html .= '<i data-lucide="' . $item['icon'] . '"></i>';
    $html .= '<span>' . $item['label'] . '</span>';
    $html .= '</a>';
  }
  $html .= '</div>';

  return $html;
}
```

**CSS highlights:**

```css
/* crm_actions.css */
.crm-global-nav {
  position: sticky;
  top: 0;
  z-index: 1000;
  background: white;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.crm-nav-item {
  padding: 12px 16px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  text-decoration: none;
  transition: all 0.2s;
}

.crm-nav-item:hover {
  color: #3b82f6;
  background: #eff6ff;
}

.crm-nav-item.active {
  color: #3b82f6;
  border-bottom: 2px solid #3b82f6;
  font-weight: 600;
}
```

**Active state detection:**

```php
$active = str_contains($current_path, '/crm/my-contacts') ? ' active' : '';
```

---

### 9.2 crm_quickadd Module

**Location:** `web/modules/custom/crm_quickadd/`

**Purpose:** Floating quick-add button + modal forms for fast data entry

#### QuickAddController.php

**Quick add workflow:**

```
1. User on any CRM page
2. Floating button visible in bottom-right corner
3. Click button → Modal opens with "Add Contact/Deal/Activity" options
4. Select "Add Contact"
5. Quick form displayed (fewer fields than full form)
6. Fill name, phone, email
7. Click "Save"
8. AJAX submission
9. Contact created
10. Success notification
11. Modal closes
12. Page stays on current view (no redirect)
```

**Key methods:**

```php
public function contactForm() {
  // Build quick-add form HTML
  return [
    '#theme' => 'quickadd_contact_form',
    '#attached' => ['library' => ['crm_quickadd/quickadd']],
  ];
}

public function contactSubmit(Request $request) {
  $data = json_decode($request->getContent(), TRUE);

  // Validate
  if (empty($data['name']) || empty($data['phone'])) {
    return new JsonResponse(['status' => 'error', 'message' => 'Missing required fields'], 400);
  }

  // Check duplicate
  $existing = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('field_phone', $data['phone'])
    ->accessCheck(FALSE)
    ->count()
    ->execute();

  if ($existing > 0) {
    return new JsonResponse(['status' => 'error', 'message' => 'Phone number already exists'], 409);
  }

  // Handle inline org creation
  $org_id = $data['organization'] ?? NULL;
  if ($org_id === '__new__' && !empty($data['organization_name'])) {
    $new_org = Node::create([
      'type' => 'organization',
      'title' => $data['organization_name'],
      'field_assigned_staff' => ['target_id' => \Drupal::currentUser()->id()],
    ]);
    $new_org->save();
    $org_id = $new_org->id();
  }

  // Create contact
  $contact = Node::create([
    'type' => 'contact',
    'title' => $data['name'],
    'field_email' => $data['email'] ?? '',
    'field_phone' => $data['phone'],
    'field_organization' => $org_id ? ['target_id' => $org_id] : NULL,
    'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
  ]);
  $contact->save();

  return new JsonResponse([
    'status' => 'success',
    'message' => 'Contact created successfully',
    'entity_id' => $contact->id(),
  ]);
}
```

**Floating button HTML:**

```html
<button id="crm-quickadd-btn" class="quickadd-floating-btn">
  <i data-lucide="plus"></i>
</button>

<div id="crm-quickadd-menu" class="quickadd-menu hidden">
  <button data-type="contact">
    <i data-lucide="user-plus"></i>
    <span>Add Contact</span>
  </button>
  <button data-type="deal">
    <i data-lucide="briefcase"></i>
    <span>Add Deal</span>
  </button>
  <button data-type="activity">
    <i data-lucide="calendar-plus"></i>
    <span>Add Activity</span>
  </button>
  <button data-type="organization">
    <i data-lucide="building-2"></i>
    <span>Add Organization</span>
  </button>
</div>
```

**CSS for floating button:**

```css
.quickadd-floating-btn {
  position: fixed;
  bottom: 24px;
  right: 24px;
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
  cursor: pointer;
  transition: all 0.3s ease;
  z-index: 999;
}

.quickadd-floating-btn:hover {
  transform: scale(1.1) rotate(90deg);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
}

.quickadd-menu {
  position: fixed;
  bottom: 90px;
  right: 24px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
  padding: 8px;
  z-index: 998;
}

.quickadd-menu button {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px 16px;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  border-radius: 8px;
  transition: background 0.2s;
}

.quickadd-menu button:hover {
  background: #f3f4f6;
}
```

**JavaScript interaction:**

```javascript
// quickadd.js
document
  .getElementById("crm-quickadd-btn")
  .addEventListener("click", function () {
    const menu = document.getElementById("crm-quickadd-menu");
    menu.classList.toggle("hidden");
  });

document.querySelectorAll(".quickadd-menu button").forEach((btn) => {
  btn.addEventListener(" click", function () {
    const type = this.dataset.type;
    openQuickAddModal(type);
  });
});

function openQuickAddModal(type) {
  // Fetch form HTML
  fetch("/crm/quickadd/form/" + type)
    .then((response) => response.text())
    .then((html) => {
      // Display modal
      // ... similar to inline-edit modal
    });
}
```

**Benefit:**

- ✅ Fast data entry without navigating away
- ✅ Reduced friction (fewer clicks)
- ✅ Always accessible (floating button)
- ✅ Good UX for power users

---

### 9.3 crm_activity_log Module

**Location:** `web/modules/custom/crm_activity_log/`

**Purpose:** Displays activity timeline on contact/deal detail pages

#### ActivityLogController.php

**Purpose:** Renders activity widget for entity detail pages

**Display location example:**

Contact detail page (`/node/123` where 123 is contact):

```
┌────────────────────────────────────────────┐
│ Contact: John Doe                           │
├────────────────────────────────────────────┤
│ Email: john@example.com                     │
│ Phone: 0912345678                           │
│ Organization: Acme Corp                     │
│                                             │
│ ┌─────────────────────────────────────────┐ │
│ │ Activity Timeline                        │ │
│ ├─────────────────────────────────────────┤ │
│ │ ○ Call - Sales Follow-up                │ │
│ │   2 hours ago                            │ │
│ │                                          │ │
│ │ ○ Meeting - Product Demo                │ │
│ │   Yesterday at 3:00 PM                   │ │
│ │                                          │ │
│ │ ○ Email - Quote Sent                    │ │
│ │   3 days ago                             │ │
│ │                                          │ │
│ │ [+ Add Activity]                         │ │
│ └─────────────────────────────────────────┘ │
└────────────────────────────────────────────┘
```

**Implementation:**

```php
public function getActivities($entity_type, $entity_id) {
  // Query activities related to this entity
  $query = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition('status', 1)
    ->accessCheck(TRUE)
    ->sort('created', 'DESC')
    ->range(0, 20); // Latest 20 activities

  // Add entity-specific filter
  if ($entity_type === 'contact') {
    $query->condition('field_contact_ref', $entity_id);
  } elseif ($entity_type === 'deal') {
    $query->condition('field_deal', $entity_id);
  } elseif ($entity_type === 'organization') {
    $query->condition('field_organization', $entity_id);
  }

  $nids = $query->execute();
  $activities = Node::loadMultiple($nids);

  // Build timeline HTML
  $html = '<div class="activity-timeline">';
  $html .= '<h3>Activity Timeline</h3>';

  foreach ($activities as $activity) {
    // Get activity type
    $type = 'note';
    if ($activity->hasField('field_type') && !$activity->get('field_type')->isEmpty()) {
      $term = $activity->get('field_type')->entity;
      if ($term) {
        $type = strtolower($term->getName());
      }
    }

    // Icon mapping
    $icons = [
      'call' => 'phone',
      'meeting' => 'calendar',
      'email' => 'mail',
      'note' => 'file-text',
      'task' => 'check-square',
    ];

    // Color mapping
    $colors = [
      'call' => '#3b82f6',
      'meeting' => '#8b5cf6',
      'email' => '#10b981',
      'note' => '#f59e0b',
      'task' => '#ec4899',
    ];

    $icon = $icons[$type] ?? 'activity';
    $color = $colors[$type] ?? '#64748b';

    // Time ago
    $created = $activity->getCreatedTime();
    $time_ago = \Drupal::service('date.formatter')->formatInterval(
      time() - $created,
      1
    ) . ' ago';

    $html .= '<div class="activity-item">';
    $html .= '<div class="activity-icon" style="background: ' . $color . '">';
    $html .= '<i data-lucide="' . $icon . '"></i>';
    $html .= '</div>';
    $html .= '<div class="activity-content">';
    $html .= '<div class="activity-title">' . htmlspecialchars($activity->getTitle()) . '</div>';
    $html .= '<div class="activity-meta">' . ucfirst($type) . ' • ' . $time_ago . '</div>';
    $html .= '</div>';
    $html .= '</div>';
  }

  $html .= '<button onclick="CRMQuickAdd.openModal(\'activity\', {contact_id: ' . $entity_id . '})">';
  $html .= '<i data-lucide="plus"></i> Add Activity';
  $html .= '</button>';
  $html .= '</div>';

  return [
    '#markup' => Markup::create($html),
    '#attached' => [
      'library' => ['crm_activity_log/activity_widget'],
    ],
  ];
}
```

**How it's integrated:**

```php
// In crm_activity_log.module
function crm_activity_log_node_view_alter(array &$build, NodeInterface $node, EntityViewDisplayInterface $display) {
  // Add activity widget to contact/deal pages
  if (in_array($node->bundle(), ['contact', 'deal', 'organization'])) {
    $build['activity_timeline'] = [
      '#lazy_builder' => [
        '\Drupal\crm_activity_log\Controller\ActivityLogController::getActivities',
        [$node->bundle(), $node->id()],
      ],
      '#create_placeholder' => TRUE,
      '#weight' => 100, // Display at bottom
    ];
  }
}
```

**CSS:**

```css
.activity-timeline {
  background: white;
  border-radius: 12px;
  padding: 24px;
  margin-top: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.activity-item {
  display: flex;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid #e5e7eb;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: white;
}

.activity-content {
  flex: 1;
}

.activity-title {
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 4px;
}

.activity-meta {
  font-size: 14px;
  color: #64748b;
}
```

---

### 9.4 crm_contact360 Module

**Location:** `web/modules/custom/crm_contact360/`

**Purpose:** Enhanced contact detail page with 360-degree view

**Features:**

- Contact information card
- Related deals
- Related activities
- Communication history
- Files/attachments
- Notes timeline

**Layout:**

```
┌─────────────────────────────────────────────────────────┐
│ Contact 360° View: John Doe                              │
├───────────────┬─────────────────────────────────────────┤
│               │                                           │
│ Contact Card  │  Quick Stats                             │
│               │  ┌──────┬──────┬──────┬──────┐          │
│ [Avatar]      │  │ 5    │ 3    │ 12   │ $50K  │          │
│ John Doe      │  │Deals │Won   │Calls │Value │          │
│ CEO           │  └──────┴──────┴──────┴──────┘          │
│ Acme Corp     │                                           │
│               │  Recent Deals                             │
│ 📧 john@      │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━       │
│ 📞 0912...    │  Deal Name           Amount    Stage      │
│               │  New Project         $20K      Proposal   │
│ Owner:        │  Expansion           $30K      Negotiation│
│ Sarah Sales   │                                           │
│               │  Activity Timeline                        │
│ [Edit]        │  ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━       │
│ [Delete]      │  ○ Follow-up call scheduled               │
│               │  ○ Demo completed successfully            │
└───────────────┴─────────────────────────────────────────┘
```

**Implementation uses:**

- Custom controller rendering multi-section page
- Multiple entity queries (deals, activities, files)
- Aggregation queries for statistics
- Template rendering

---

### 9.5 crm_teams Module

**Location:** `web/modules/custom/crm_teams/`

**Purpose:** Team-based access control and team management

#### Features:

1. **Team entity (taxonomy)**
   - Taxonomy vocabulary: `crm_team`
   - Terms: Sales Team A, Sales Team B, Support Team, etc.

2. **User field: `field_team`**
   - Type: Entity Reference (taxonomy term)
   - Target: `crm_team` vocabulary
   - Allows: Single selection

3. **Team-based filtering**
   - Integrated with `crm.module` access control
   - Function: `_crm_check_same_team()`
   - Logic: If users share same team, can view each other's content

4. **Team management page (`/admin/crm/teams`)**
   - View all teams
   - View team members
   - Assign users to teams
   - Create/edit/delete teams

#### TeamsManagementController.php

```php
public function teamsPage() {
  // Load all teams
  $teams = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => 'crm_team']);

  $html = '<div class="teams-container">';

  foreach ($teams as $team) {
    // Get team members
    $member_query = \Drupal::entityQuery('user')
      ->condition('status', 1)
      ->condition('field_team', $team->id())
      ->accessCheck(FALSE);
    $uids = $member_query->execute();
    $members = \Drupal\user\Entity\User::loadMultiple($uids);

    $html .= '<div class="team-card">';
    $html .= '<h3>' . $team->getName() . '</h3>';
    $html .= '<p>Members: ' . count($members) . '</p>';

    $html .= '<div class="team-members">';
    foreach ($members as $member) {
      $html .= '<div class="member-badge">';
      $html .= '<i data-lucide="user"></i>';
      $html .= '<span>' . $member->getDisplayName() . '</span>';
      $html .= '</div>';
    }
    $html .= '</div>';

    $html .= '</div>';
  }

  $html .= '</div>';

  return ['#markup' => Markup::create($html)];
}
```

---

### 9.6 crm_notifications Module

**Location:** `web/modules/custom/crm_notifications/`

**Purpose:** Email notifications for CRM events

#### Notification triggers:

1. **Contact assigned to me**
   - When: Admin assigns contact to a user
   - To: Assigned user
   - Subject: "New contact assigned: John Doe"

2. **Deal stage changed**
   - When: Deal moves to new stage
   - To: Deal owner
   - Subject: "Deal 'Project X' moved to Proposal Sent"

3. **Activity due soon**
   - When: Activity due date is today or tomorrow
   - To: Assigned user
   - Subject: "Reminder: Follow-up call due today"

4. **Deal won**
   - When: Deal moved to "Closed Won" stage
   - To: Deal owner + Manager
   - Subject: "🎉 Deal won: Project X - $50,000"

#### Implementation:

```php
// crm_notifications.module
function crm_notifications_node_update(NodeInterface $node) {
  // Check if deal stage changed
  if ($node->bundle() === 'deal' && $node->hasField('field_stage')) {
    $original = $node->original ?? NULL;

    if ($original) {
      $old_stage = $original->get('field_stage')->target_id;
      $new_stage = $node->get('field_stage')->target_id;

      if ($old_stage != $new_stage) {
        // Stage changed - send notification
        $stage_term = $node->get('field_stage')->entity;
        $stage_name = $stage_term ? $stage_term->getName() : 'Unknown';

        // Get owner
        $owner_id = $node->get('field_owner')->target_id;
        $owner = \Drupal\user\Entity\User::load($owner_id);

        if ($owner) {
          $mailManager = \Drupal::service('plugin.manager.mail');
          $mailManager->mail(
            'crm_notifications',
            'deal_stage_changed',
            $owner->getEmail(),
            $owner->getPreferredLangcode(),
            [
              'deal_title' => $node->getTitle(),
              'new_stage' => $stage_name,
            ]
          );
        }
      }
    }
  }
}

/**
 * Implements hook_mail().
 */
function crm_notifications_mail($key, &$message, $params) {
  switch ($key) {
    case 'deal_stage_changed':
      $message['subject'] = 'Deal "' . $params['deal_title'] . '" moved to ' . $params['new_stage'];
      $message['body'][] = 'Your deal "' . $params['deal_title'] . '" has been moved to stage: ' . $params['new_stage'];
      $message['body'][] = 'View deal: ' . \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $params['deal_id'];
      break;
  }
}
```

---

### 9.7 crm_workflow Module

**Location:** `web/modules/custom/crm_workflows/`

**Purpose:** Automated workflow rules

#### Example workflows:

1. **Auto-assign lead source**
   - Trigger: New contact created with email domain @acme.com
   - Action: Set source to "Website"

2. **Auto-create welcome activity**
   - Trigger: New contact created
   - Action: Create activity "Send welcome email"

3. **Auto-update deal probability**
   - Trigger: Deal stage changed to "Negotiation"
   - Action: Set probability to 75%

4. **Auto-notify manager**
   - Trigger: Deal amount > $100,000
   - Action: Send email to sales manager

#### Implementation:

```php
// crm_workflow.module
function crm_workflow_node_insert(NodeInterface $node) {
  // Workflow: Auto-create welcome activity for new contacts
  if ($node->bundle() === 'contact') {
    $welcome_activity = Node::create([
      'type' => 'activity',
      'title' => 'Send welcome email to ' . $node->getTitle(),
      'field_type' => ['target_id' => _get_term_id_by_name('activity_type', 'Email')],
      'field_contact_ref' => ['target_id' => $node->id()],
      'field_assigned_to' => $node->get('field_owner')->target_id,
      'field_datetime' => date('Y-m-d\TH:i:s', strtotime('+1 day')),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);
    $welcome_activity->save();
  }
}

function crm_workflow_node_update(NodeInterface $node) {
  // Workflow: Auto-update probability based on stage
  if ($node->bundle() === 'deal' && $node->hasField('field_stage')) {
    $stage = $node->get('field_stage')->entity;

    if ($stage) {
      $stage_name = strtolower($stage->getName());

      $probability_map = [
        'lead' => 10,
        'qualified' => 25,
        'proposal sent' => 50,
        'negotiation' => 75,
        'closed won' => 100,
        'closed lost' => 0,
      ];

      if (isset($probability_map[$stage_name])) {
        $node->set('field_probability', $probability_map[$stage_name]);
        $node->save(); // Save again with updated probability
      }
    }
  }
}
```

---

### 9.8 crm_navigation Module

**Location:** `web/modules/custom/crm_navigation/`

**Purpose:** Navigation helpers (back buttons, breadcrumbs, shortcuts)

#### Features:

1. **Back button on entity pages**

   ```html
   <a href="javascript:history.back()" class="crm-back-btn">
     <i data-lucide="arrow-left"></i>
     Back
   </a>
   ```

2. **Breadcrumb enhancement**

   ```
   Home > CRM > Contacts > John Doe
   ```

3. **Keyboard shortcuts**
   - `Ctrl+N`: New contact
   - `Ctrl+D`: Dashboard
   - `Ctrl+K`: Search
   - `/`: Focus search box

#### Implementation:

```php
// crm_navigation.module
function crm_navigation_page_attachments(array &$attachments) {
  $attachments['#attached']['library'][] = 'crm_navigation/navigation';
}
```

```javascript
// navigation.js
document.addEventListener("keydown", function (e) {
  // Ctrl+N: New contact
  if (e.ctrlKey && e.key === "n") {
    e.preventDefault();
    window.location.href = "/node/add/contact";
  }

  // Ctrl+D: Dashboard
  if (e.ctrlKey && e.key === "d") {
    e.preventDefault();
    window.location.href = "/crm/dashboard";
  }

  // Ctrl+K: Search
  if (e.ctrlKey && e.key === "k") {
    e.preventDefault();
    document.querySelector('.views-exposed-form input[type="text"]')?.focus();
  }

  // /: Focus search
  if (e.key === "/" && !e.ctrlKey && !e.metaKey) {
    const searchInput = document.querySelector(
      '.views-exposed-form input[type="text"]',
    );
    if (searchInput && document.activeElement !== searchInput) {
      e.preventDefault();
      searchInput.focus();
    }
  }
});
```

---

### 9.9 crm_login & crm_register Modules

**Purpose:** Custom branded login and registration pages

#### crm_login

Provides custom login page at `/login` with:

- Modern UI design
- Discord-style gradient background
- Lucide icons
- Remember me checkbox
- Forgot password link

#### crm_register

Provides custom registration page at `/user/register` with:

- Multi-step registration
- Field validation
- Terms acceptance
- Email verification

---

## 10. Complete Page-by-Page Breakdown

### 10.1 Homepage (`/`)

**Route:** Default Drupal front page  
**Controller:** `\Drupal\node\Controller\NodeController::view`  
**Template:** `page.html.twig`

**For Anonymous Users:**

- Shows login banner with call-to-action
- "Get Started" button → `/login`

**For Authenticated Users:**

- Redirects to `/crm/dashboard` (via hook_user_login)

**CSS Loaded:**

- `core/drupal` (Drupal core CSS)
- `claro/global-styling` (if using Claro theme)

**JavaScript Loaded:**

- `core/drupal` (Drupal core JS)

---

### 10.2 Login Page (`/login`)

**Route:** `crm_login.login`  
**Path:** `/login`  
**Controller:** `\Drupal\crm_login\Controller\LoginController::loginPage`  
**Template:** Inline HTML (no Twig)

**CSS Loaded:**

- `crm_login/login_form` library
  - `css/login-form.css`

**JavaScript Loaded:**

- `https://unpkg.com/lucide@latest` (icons)
- `js/login-form.js` (form validation)

**What Happens:**

1. User navigates to `/login`
2. LoginController renders custom HTML form
3. Form styled with gradient background
4. User enters username + password
5. Form submits to Drupal's `/user/login` endpoint
6. On success: Redirected to `/crm/dashboard`
7. On failure: Error message displayed

**Database Queries:**

- None (until form submission)
- On submit: `SELECT * FROM users_field_data WHERE name = ?`

---

### 10.3 Dashboard (`/crm/dashboard`)

**Route:** `crm_dashboard.dashboard`  
**Path:** `/crm/dashboard`  
**Controller:** `\Drupal\crm_dashboard\Controller\DashboardController::view`  
**Template:** Inline HTML (no Twig)

**CSS Loaded:**

- `crm_actions/global_nav` (navigation bar)
- Inline `<style>` tags (in generated HTML)

**JavaScript Loaded:**

- `https://cdn.jsdelivr.net/npm/chart.js@4.4.1` (charts)
- `https://unpkg.com/lucide@latest` (icons)
- Inline `<script>` tags (Chart initialization)

**Database Queries Executed:**

```sql
-- 1. Count contacts
SELECT COUNT(*) FROM node_field_data nfd
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
WHERE nfd.type = 'contact'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5 -- Current user

-- 2. Count organizations
SELECT COUNT(*) FROM node_field_data nfd
LEFT JOIN node__field_assigned_staff staff ON nfd.nid = staff.entity_id
WHERE nfd.type = 'organization'
  AND nfd.status = 1
  AND staff.field_assigned_staff_target_id = 5

-- 3. Count deals
SELECT COUNT(*) FROM node_field_data nfd
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
WHERE nfd.type = 'deal'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5

-- 4. Count activities
SELECT COUNT(*) FROM node_field_data nfd
LEFT JOIN node__field_assigned_to assigned ON nfd.nid = assigned.entity_id
WHERE nfd.type = 'activity'
  AND nfd.status = 1
  AND assigned.field_assigned_to_target_id = 5

-- 5. Load pipeline stages
SELECT * FROM taxonomy_term_field_data WHERE vid = 'pipeline_stage'

-- 6-N. For each stage, count deals
SELECT COUNT(*) FROM node_field_data nfd
LEFT JOIN node__field_stage stage ON nfd.nid = stage.entity_id
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
WHERE nfd.type = 'deal'
  AND stage.field_stage_target_id = 1 -- Stage ID
  AND owner.field_owner_target_id = 5

-- M. Load all deals for revenue calculations
SELECT nfd.nid FROM node_field_data nfd
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
WHERE nfd.type = 'deal'
  AND owner.field_owner_target_id = 5

-- M+1. Load recent activities (last 10)
SELECT nfd.nid FROM node_field_data nfd
LEFT JOIN node__field_assigned_to assigned ON nfd.nid = assigned.entity_id
WHERE nfd.type = 'activity'
  AND assigned.field_assigned_to_target_id = 5
ORDER BY nfd.created DESC
LIMIT 10
```

**Total Queries:** ~15-20 (depending on number of pipeline stages)

**Render Time:** ~200-500ms (for typical dataset)

**Page Elements:**

1. **Global Navigation Bar** (from crm_actions)
2. **KPI Cards** (4 cards: Contacts, Orgs, Deals, Activities)
3. **Pipeline Chart** (Bar chart showing deals by stage)
4. **Revenue Chart** (Doughnut chart: Won vs Active vs Lost)
5. **Recent Activities Timeline** (Last 10 activities)

---

### 10.4 My Contacts (`/crm/my-contacts`)

**Route:** Generated by Views (`views.view.my_contacts.page_1`)  
**Path:** `/crm/my-contacts`  
**Views ID:** `my_contacts`  
**Display:** `page_1`

**CSS Loaded:**

- `crm_actions/global_nav`
- `crm_edit/inline_edit`
- `views/views` (Views default styling)
- `gisystems/tables` (Table styling from admin theme)

**JavaScript Loaded:**

- `crm_edit/inline_edit`
- `https://unpkg.com/lucide@latest`

**Database Query:**

```sql
SELECT
  nfd.nid,
  nfd.title AS contact_name,
  email.field_email_value AS email,
  phone.field_phone_value AS phone,
  org.title AS organization_name,
  source_term.name AS source,
  type_term.name AS customer_type
FROM node_field_data nfd
LEFT JOIN node__field_email email
  ON nfd.nid = email.entity_id AND email.deleted = 0
LEFT JOIN node__field_phone phone
  ON nfd.nid = phone.entity_id AND phone.deleted = 0
LEFT JOIN node__field_owner owner
  ON nfd.nid = owner.entity_id AND owner.deleted = 0
LEFT JOIN node__field_organization org_ref
  ON nfd.nid = org_ref.entity_id AND org_ref.deleted = 0
LEFT JOIN node_field_data org
  ON org_ref.field_organization_target_id = org.nid
LEFT JOIN node__field_source source_ref
  ON nfd.nid = source_ref.entity_id AND source_ref.deleted = 0
LEFT JOIN taxonomy_term_field_data source_term
  ON source_ref.field_source_target_id = source_term.tid
LEFT JOIN node__field_customer_type type_ref
  ON nfd.nid = type_ref.entity_id AND type_ref.deleted = 0
LEFT JOIN taxonomy_term_field_data type_term
  ON type_ref.field_customer_type_target_id = type_term.tid
WHERE nfd.type = 'contact'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5 -- Current user
ORDER BY nfd.changed DESC
LIMIT 25 OFFSET 0;
```

**Page Elements:**

1. **Global Navigation** (crm_actions)
2. **Page Title:** "My Contacts"
3. **Header Area:** "Add Contact" button
4. **Exposed Filters:** Search by name, email, phone
5. **View Results:** Table with columns:
   - Name (linked to /node/NID)
   - Organization (linked to org detail)
   - Phone
   - Email
   - Source (taxonomy)
   - Customer Type (taxonomy)
   - Actions (Edit | Delete buttons via CrmEditLink plugin)
6. **Pager:** 1 2 3 ... Next › (if > 25 contacts)

**User Interactions:**

- Click "Add Contact" → Navigate to `/node/add/contact`
- Click contact name → Navigate to `/node/123` (contact detail)
- Click "Edit" → Modal opens (AJAX fetch)
- Click "Delete" → Confirmation, then AJAX delete
- Type in search box → Filter results (exposed filter submission)

---

### 10.5 My Deals (`/crm/my-deals`)

**Route:** `views.view.my_deals.page_1`  
**Path:** `/crm/my-deals`  
**Views ID:** `my_deals`

**Similar to My Contacts, but:**

**Database Query:**

```sql
SELECT
  nfd.nid,
  nfd.title AS deal_name,
  amount.field_amount_value AS deal_amount,
  stage_term.name AS pipeline_stage,
  probability.field_probability_value AS win_probability,
  close_date.field_closing_date_value AS expected_close,
  contact_node.title AS contact_name,
  org_node.title AS organization_name
FROM node_field_data nfd
LEFT JOIN node__field_owner owner ON nfd.nid = owner.entity_id
LEFT JOIN node__field_amount amount ON nfd.nid = amount.entity_id
LEFT JOIN node__field_stage stage_ref ON nfd.nid = stage_ref.entity_id
LEFT JOIN taxonomy_term_field_data stage_term ON stage_ref.field_stage_target_id = stage_term.tid
LEFT JOIN node__field_probability probability ON nfd.nid = probability.entity_id
LEFT JOIN node__field_closing_date close_date ON nfd.nid = close_date.entity_id
LEFT JOIN node__field_contact contact_ref ON nfd.nid = contact_ref.entity_id
LEFT JOIN node_field_data contact_node ON contact_ref.field_contact_target_id = contact_node.nid
LEFT JOIN node__field_organization org_ref ON nfd.nid = org_ref.entity_id
LEFT JOIN node_field_data org_node ON org_ref.field_organization_target_id = org_node.nid
WHERE nfd.type = 'deal'
  AND nfd.status = 1
  AND owner.field_owner_target_id = 5
ORDER BY close_date.field_close_date_value ASC
LIMIT 25;
```

**Table Columns:**

- Deal Name
- Amount (formatted with thousand separator)
- Stage (color-coded badge)
- Probability (%)
- Close Date
- Contact (linked)
- Organization (linked)
- Actions (Edit | Delete)

**Special Feature: Grouping by Stage**

Views configuration includes:

```yaml
style:
  options:
    grouping:
      - field: field_stage
        rendered: true
```

**Result:** Deals are grouped:

```
━━━ Qualified ━━━
Deal A    $20K
Deal B    $15K

━━━ Proposal Sent ━━━
Deal C    $50K
Deal D    $30K

━━━ Negotiation ━━━
Deal E    $100K
```

---

### 10.6 Pipeline (`/crm/pipeline`)

**Route:** `crm_kanban.pipeline`  
**Path:** `/crm/pipeline`  
**Controller:** `\Drupal\crm_kanban\Controller\KanbanController::view`

**CSS Loaded:**

- Inline `<style>` (in generated HTML)
- `crm_actions/global_nav`

**JavaScript Loaded:**

- `https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js`
- `https://unpkg.com/lucide@latest`
- Inline `<script>` (Sortable initialization)

**Database Queries:**

1. Load pipeline stages
2. For each stage, load deals in that stage (6-8 queries)
3. For each deal, load organization entity reference (if not eager-loaded)

**Page Layout:**

```
Horizontal scrolling board:
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Lead         │ Qualified    │ Proposal     │ Negotiation  │
│ 3 deals      │ 5 deals      │ 2 deals      │ 4 deals      │
│ $45K total   │ $120K total  │ $80K total   │ $200K total  │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │
│ │ Deal A   │ │ │ Deal D   │ │ │ Deal G   │ │ │ Deal J   │ │
│ │ $15K     │ │ │ $25K     │ │ │ $50K     │ │ │ $100K    │ │
│ │ Acme Corp│ │ │ Tech Inc │ │ │ BigCo    │ │ │ MegaCorp │ │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘ │
│ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │ ┌──────────┐ │
│ │ Deal B   │ │ │ Deal E   │ │ │ Deal H   │ │ │ Deal K   │ │
│ │ $20K     │ │ │ $30K     │ │ │ $30K     │ │ │ $50K     │ │
│ └──────────┘ │ └──────────┘ │ └──────────┘ │ └──────────┘ │
│ ...          │ ...          │              │ ...          │
└──────────────┴──────────────┴──────────────┴──────────────┘
```

**Drag-and-Drop Flow:**

1. User clicks and holds deal card
2. Sortable.js activates drag mode
3. User drags card over new column
4. Card animates to new position
5. Sortable.js fires `onEnd` event
6. JavaScript extracts:
   - `dealId` from `data-deal-id` attribute
   - `newStageId` from target column's `data-stage-id`
7. AJAX POST to `/crm/pipeline/update-stage`
8. Backend loads deal, updates `field_stage`, saves
9. Response: `{success: true}`
10. JavaScript reloads page to update totals

---

### 10.7 My Activities (`/crm/my-activities`)

**Route:** `views.view.my_activities.page_1`  
**Path:** `/crm/my-activities`

**Table Columns:**

- Activity Title
- Type (with icon)
- Related To (contact or deal)
- Due Date
- Outcome (completed, pending, cancelled)
- Actions

**Filter/Sort:**

- Filter by type (Call, Meeting, Email, Task, Note)
- Sort by due date
- Show only pending / Show all

---

### 10.8 My Organizations (`/crm/my-organizations`)

**Route:** `views.view.my_organizations.page_1`  
**Path:** `/crm/my-organizations`

**Table Columns:**

- Organization Name
- Industry
- Website (clickable link)
- Email
- Phone
- Owner
- # of Contacts (calculated field)
- # of Deals (calculated field)
- Actions

---

### 10.9 Import Page (`/admin/crm/import`)

**Route:** `crm_import_export.import_page`  
**Path:** `/admin/crm/import`  
**Controller:** `\Drupal\crm_import_export\Controller\ImportController::importPage`

**Access:** `administrator` and `sales_manager` roles

**Page shows 4 import cards:**

1. Import Contacts
2. Import Deals
3. Import Organizations
4. Import Activities

Each card links to specific import form:

- `/admin/crm/import/contacts`
- `/admin/crm/import/deals`
- etc.

---

### 10.10 Contact Detail (`/node/123` where 123 is contact)

**Route:** `entity.node.canonical`  
**Path:** `/node/{node}`  
**Controller:** Drupal core `NodeController::view`  
**Template:** `node--contact--full.html.twig` (if exists, else default)  
**View Mode:** `full`

**CSS Loaded:**

- `crm_actions/global_nav`
- `crm_activity_log/activity_widget`
- `crm_contact360/contact_view` (if module enabled)

**JavaScript Loaded:**

- `crm_edit/inline_edit` (for quick-edit button)

**Page Layout:**

```
┌─────────────────────────────────────────────────────┐
│ [← Back] Contact: John Doe                          │
├─────────────────────────────────────────────────────┤
│                                                      │
│ Email: john@example.com                              │
│ Phone: 0912345678                                    │
│ Organization: Acme Corp [View →]                     │
│ Position: CEO                                        │
│ Customer Type: Hot Lead                              │
│ Source: Website                                      │
│ Owner: Sarah Sales                                   │
│                                                      │
│ Notes:                                               │
│ This is a VIP customer interested in our premium     │
│ package. Follow up weekly.                           │
│                                                      │
│ [Edit Contact] [Delete Contact]                      │
│                                                      │
├─────────────────────────────────────────────────────┤
│ Related Deals (3)                                    │
├─────────────────────────────────────────────────────┤
│ • New Project - $20K - Proposal Sent                 │
│ • Expansion - $30K - Negotiation                     │
│ • Follow-up - $5K - Qualified                        │
│                                                      │
├─────────────────────────────────────────────────────┤
│ Activity Timeline (from crm_activity_log module)      │
├───────────────────────────────────────────────────
```
