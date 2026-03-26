# 🔍 Advanced Search & Filtering - Implementation Plan

**Date**: March 26, 2026  
**Estimated Time**: 12-14 hours  
**Priority**: HIGH  
**Status**: Ready for Development

---

## 📊 Current State Analysis

### ✅ Already Exists

- ✅ Search API configured for Contacts, Deals, Organizations
- ✅ Basic Controllers for listing (AllContactsController, etc.)
- ✅ Simple text search (LIKE pattern on name, email, phone)
- ✅ Basic filters: Stage, Owner, sorting
- ✅ Database structure with all necessary fields

### ❌ Missing Features

- ❌ AND/OR/NOT logic (only AND works)
- ❌ Range filters (amount, date, count)
- ❌ Advanced filter UI (visual builder)
- ❌ Saved searches / Filter templates
- ❌ Fuzzy search (typo tolerance)
- ❌ Filter suggestions
- ❌ Export filtered results
- ❌ Bulk operations on filtered results

---

## 🎯 Solution Architecture

### Phase 1: Basic Advanced Filters (6-7 hours)

**What**: Add range filters, multiple selections, AND/OR logic

### Phase 2: Saved Searches (3-4 hours)

**What**: Let users save and reuse filters

### Phase 3: UI Enhancements (3-4 hours)

**What**: Better UX, suggestions, export

---

## 📋 Detailed Implementation Plan

### **PART 1: Create Filter Module (2 hours)**

#### Step 1.1: Create Module Structure

```bash
web/modules/custom/crm_advanced_filters/
├── crm_advanced_filters.info.yml
├── crm_advanced_filters.module
├── crm_advanced_filters.routing.yml
├── crm_advanced_filters.services.yml
├── crm_advanced_filters.permissions.yml
├── src/
│   ├── Service/
│   │   ├── FilterService.php          ⭐ Core filter logic
│   │   ├── SavedFilterService.php     ⭐ Manage saved filters
│   │   └── SuggestionService.php      ⭐ Filter suggestions
│   ├── Form/
│   │   ├── AdvancedFilterForm.php     ⭐ Filter builder form
│   │   └── SaveFilterForm.php
│   ├── Controller/
│   │   ├── FilterController.php       ⭐ API endpoints
│   │   └── SavedFilterController.php
│   └── Plugin/
│       └── Filter/                    ⭐ Filter plugins
│           ├── TextFilter.php
│           ├── RangeFilter.php
│           ├── SelectFilter.php
│           └── DateFilter.php
├── templates/
│   ├── filter-builder.html.twig       ⭐ Main UI
│   ├── filter-results.html.twig
│   └── saved-filters.html.twig
├── js/
│   ├── filter-builder.js              ⭐ Interactive UI
│   ├── filter-api.js
│   └── suggestions.js
└── css/
    └── advanced-filters.css
```

---

### **PART 2: Build Filter Service (3 hours)**

#### Step 2.1: FilterService - Core Logic

```php
// File: src/Service/FilterService.php

class FilterService {

  /**
   * Build query based on filter definition
   *
   * Input:
   * {
   *   "entity_type": "contact",
   *   "conditions": {
   *     "logic": "AND",
   *     "rules": [
   *       {
   *         "field": "title",
   *         "operator": "contains",
   *         "value": "customer"
   *       },
   *       {
   *         "field": "field_owner",
   *         "operator": "equals",
   *         "value": 123
   *       },
   *       {
   *         "field": "field_amount",
   *         "operator": "between",
   *         "min": 1000000,
   *         "max": 5000000
   *       }
   *     ]
   *   },
   *   "sorting": {
   *     "field": "changed",
   *     "direction": "DESC"
   *   },
   *   "pagination": {
   *     "page": 1,
   *     "limit": 20
   *   }
   * }
   */
  public function buildQuery($filter_definition) {
    // Validate filter
    // Build entity query
    // Apply access control
    // Apply conditions
    // Apply sorting
    // Apply pagination
    // Return results with total count
  }

  /**
   * Get available filters for entity type
   */
  public function getAvailableFilters($entity_type) {
    return [
      'contact' => [
        'text' => ['title', 'field_email', 'field_phone'],
        'select' => ['field_owner', 'field_organization', 'field_source'],
        'range' => [],
        'date' => ['created', 'changed']
      ],
      'deal' => [
        'text' => ['title'],
        'select' => ['field_stage', 'field_owner', 'field_contact', 'field_organization'],
        'range' => ['field_amount', 'field_probability'],
        'date' => ['field_closing_date', 'created', 'changed']
      ],
      'organization' => [
        'text' => ['title', 'field_email', 'field_phone', 'field_website'],
        'select' => ['field_industry', 'field_assigned_staff'],
        'range' => ['field_annual_revenue', 'field_employees_count'],
        'date' => ['created', 'changed']
      ]
    ];
  }

  /**
   * Get filter options (for select filters)
   */
  public function getFilterOptions($entity_type, $field_name) {
    // For field_stage: load all stage terms
    // For field_owner: load all users
    // For field_source: load all source terms
    // Return ["value" => "label"] array
  }
}
```

#### Step 2.2: SavedFilterService - Persistence

```php
// File: src/Service/SavedFilterService.php

class SavedFilterService {

  /**
   * Save filter for current user
   */
  public function saveFilter($name, $filter_definition, $entity_type) {
    // Create new SavedFilter entity
    // Store user ID, name, definition, entity_type
    // Return ID
  }

  /**
   * Get user's saved filters
   */
  public function getUserFilters($entity_type = null) {
    // Load all SavedFilter entities for current user
    // Filter by entity_type if provided
    // Return array of filters
  }

  /**
   * Delete saved filter
   */
  public function deleteFilter($filter_id) {
    // Delete SavedFilter entity
  }

  /**
   * Update saved filter
   */
  public function updateFilter($filter_id, $filter_definition) {
    // Update filter definition
  }
}
```

---

### **PART 3: Create API Endpoints (2 hours)**

#### Step 3.1: Filter API Endpoint

```php
// File: src/Controller/FilterApiController.php

class FilterApiController {

  /**
   * POST /api/crm/filters/query
   *
   * Request body:
   * {
   *   "entity_type": "contact",
   *   "conditions": {...},
   *   "sorting": {...},
   *   "pagination": {...}
   * }
   *
   * Response:
   * {
   *   "success": true,
   *   "total": 150,
   *   "page": 1,
   *   "results": [...]
   * }
   */
  public function queryFilters(Request $request) {
    $filter_def = json_decode($request->getContent(), true);
    $results = $this->filterService->buildQuery($filter_def);
    return new JsonResponse($results);
  }

  /**
   * GET /api/crm/filters/available/{entity_type}
   *
   * Get all available filters for entity type
   */
  public function getAvailableFilters($entity_type) {
    $filters = $this->filterService->getAvailableFilters($entity_type);
    return new JsonResponse($filters);
  }

  /**
   * GET /api/crm/filters/options/{entity_type}/{field}
   *
   * Get options for a select filter
   */
  public function getFilterOptions($entity_type, $field) {
    $options = $this->filterService->getFilterOptions($entity_type, $field);
    return new JsonResponse($options);
  }

  /**
   * GET /api/crm/filters/saved
   *
   * Get user's saved filters
   */
  public function getSavedFilters(Request $request) {
    $entity_type = $request->query->get('type');
    $filters = $this->savedFilterService->getUserFilters($entity_type);
    return new JsonResponse($filters);
  }

  /**
   * POST /api/crm/filters/save
   *
   * Save new filter
   */
  public function saveFilter(Request $request) {
    $data = json_decode($request->getContent(), true);
    $id = $this->savedFilterService->saveFilter(
      $data['name'],
      $data['definition'],
      $data['entity_type']
    );
    return new JsonResponse(['id' => $id]);
  }
}
```

---

### **PART 4: Create Filter UI (3 hours)**

#### Step 4.1: Advanced Filter Form

```php
// File: src/Form/AdvancedFilterForm.php

class AdvancedFilterForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type = $form_state->get('entity_type') ?? 'contact';

    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $entity_type,
    ];

    // Filter builder section
    $form['filters'] = [
      '#type' => 'container',
      '#id' => 'advanced-filters-container',
      '#attributes' => ['class' => ['filters-builder']],
    ];

    // Logic selector (AND/OR)
    $form['filters']['logic'] = [
      '#type' => 'select',
      '#title' => $this->t('Match'),
      '#options' => [
        'AND' => t('All conditions (AND)'),
        'OR' => t('Any condition (OR)'),
      ],
      '#default_value' => 'AND',
    ];

    // Dynamic condition fields
    $num_conditions = $form_state->get('num_conditions') ?? 1;
    for ($i = 0; $i < $num_conditions; $i++) {
      $form['filters']['condition_' . $i] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['filter-row']],
      ];

      $form['filters']['condition_' . $i]['field'] = [
        '#type' => 'select',
        '#title' => $this->t('Field'),
        '#options' => $this->filterService->getAvailableFilters($entity_type),
        '#ajax' => [
          'callback' => '::updateOperators',
          'event' => 'change',
        ],
      ];

      $form['filters']['condition_' . $i]['operator'] = [
        '#type' => 'select',
        '#title' => $this->t('Operator'),
        '#options' => [
          'equals' => t('Equals'),
          'not_equals' => t('Not equals'),
          'contains' => t('Contains'),
          'not_contains' => t('Not contains'),
          'starts_with' => t('Starts with'),
          'ends_with' => t('Ends with'),
          'greater_than' => t('Greater than'),
          'less_than' => t('Less than'),
          'between' => t('Between'),
          'in' => t('In list'),
          'is_empty' => t('Is empty'),
          'not_empty' => t('Not empty'),
        ],
      ];

      $form['filters']['condition_' . $i]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value'),
        '#ajax' => [
          'callback' => '::updateValueField',
          'event' => 'change',
        ],
      ];

      $form['filters']['condition_' . $i]['remove'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#ajax' => [
          'callback' => '::removeCondition',
          'event' => 'click',
        ],
      ];
    }

    // Add condition button
    $form['filters']['add_condition'] = [
      '#type' => 'button',
      '#value' => $this->t('+ Add Filter'),
      '#ajax' => [
        'callback' => '::addCondition',
        'event' => 'click',
      ],
    ];

    // Sorting
    $form['sorting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sorting'),
    ];

    $form['sorting']['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort by'),
      '#options' => $this->filterService->getAvailableFilters($entity_type),
    ];

    $form['sorting']['direction'] = [
      '#type' => 'select',
      '#title' => $this->t('Direction'),
      '#options' => [
        'ASC' => t('Ascending'),
        'DESC' => t('Descending'),
      ],
    ];

    // Save filter checkbox
    $form['save_filter'] = [
      '#type' => 'details',
      '#title' => $this->t('Save this filter'),
    ];

    $form['save_filter']['save_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter name'),
      '#placeholder' => t('e.g., My Hot Deals'),
    ];

    // Submit buttons
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply Filters'),
      '#button_type' => 'primary',
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build filter definition from form
    // Call FilterService::buildQuery
    // Redirect to results page or render inline
  }
}
```

#### Step 4.2: Filter Builder JavaScript

```javascript
// File: js/filter-builder.js

(function (Drupal) {
  "use strict";

  Drupal.behaviors.filterBuilder = {
    attach: function (context) {
      const container = document.getElementById("advanced-filters-container");
      if (!container) return;

      // Load available filters
      const entityType = document.querySelector('[name="entity_type"]').value;
      fetch(`/api/crm/filters/available/${entityType}`)
        .then((res) => res.json())
        .then((filters) => {
          // Populate filter dropdowns
          // Setup event listeners
          // Handle dynamic field updates
        });

      // Handle field change
      document.querySelectorAll('[name*="[field]"]').forEach((field) => {
        field.addEventListener("change", (e) => {
          const row = e.target.closest(".filter-row");
          const fieldName = e.target.value;

          // Load options for this field
          fetch(`/api/crm/filters/options/${entityType}/${fieldName}`)
            .then((res) => res.json())
            .then((options) => {
              // Update value field (may switch from text to select)
              updateValueField(row, options);
            });
        });
      });

      // Handle AND/OR logic toggle
      document
        .querySelector('[name="logic"]')
        .addEventListener("change", (e) => {
          updateFilterUI(e.target.value);
        });
    },
  };
})(Drupal);
```

#### Step 4.3: Results Template

```twig
{# File: templates/filter-results.html.twig #}

<div class="crm-filter-results">

  <div class="filter-summary">
    <h2>{{ total }} Results</h2>
    <p>{{ filter_description }}</p>

    {% if saved_filters %}
    <div class="saved-filters">
      <h3>Saved Filters</h3>
      {%  for filter in saved_filters %}
      <button class="saved-filter-button" data-filter-id="{{ filter.id }}">
        {{ filter.name }}
      </button>
      {% endfor %}
    </div>
    {% endif %}
  </div>

  <div class="filter-results">
    {% if results is not empty %}
      {% for item in results %}
        <div class="result-item">
          {{ item.title }}
          {# Show relevant fields based on entity type #}
          {% if item.email %}
            <span class="email">{{ item.email }}</span>
          {% endif %}
        </div>
      {% endfor %}

      {# Pagination #}
      {{ pagination }}
    {% else %}
      <p class="no-results">No results found</p>
    {% endif %}
  </div>

</div>
```

---

### **PART 5: Database Setup (1 hour)**

#### Step 5.1: Create SavedFilter Entity Type

```yaml
# File: config/install/core.entity_type.saved_filter.yml

id: saved_filter
label: Saved Filter
module: crm_advanced_filters
group: default
weight_field: weight
label_count_plural: "@count saved filters"

handlers:
  storage: 'Drupal\Core\Entity\Sql\SqlContentEntityStorage'

fields:
  id:
    type: uuid
    label: ID
    read-only: true

  name:
    type: string
    label: Filter Name
    required: true
    max_length: 255

  entity_type:
    type: string
    label: Entity Type
    required: true
    max_length: 32

  filter_definition:
    type: text_long
    label: Filter Definition (JSON)
    required: true

  uid:
    type: entity_reference
    label: Owner
    target_type: user
    required: true

  created:
    type: created
    label: Created

  changed:
    type: changed
    label: Changed
```

---

## 🎨 Filter UI Mockup

```
┌─────────────────────────────────────────────────────┐
│ Advanced Filters for Contacts                       │
├─────────────────────────────────────────────────────┤
│                                                     │
│ Match: [AND ▼] (All / Any)                        │
│                                                     │
│ Filter 1:                                           │
│ ┌──────────────────────────────────────────────┐  │
│ │ Field: [Title         ▼]                     │ ✕│
│ │ Operator: [Contains   ▼]                     │  │
│ │ Value: [_____________]                      │  │
│ └──────────────────────────────────────────────┘  │
│                                                     │
│ Filter 2:                                           │
│ ┌──────────────────────────────────────────────┐  │
│ │ Field: [Stage        ▼]                      │ ✕│
│ │ Operator: [Equals    ▼]                      │  │
│ │ Value: [New ▼] [In Progress ▼] [Contacted ▼]  │
│ └──────────────────────────────────────────────┘  │
│                                                     │
│ Filter 3:                                           │
│ ┌──────────────────────────────────────────────┐  │
│ │ Field: [Created Date ▼]                      │ ✕│
│ │ Operator: [Between   ▼]                      │  │
│ │ From: [01/03/2026] To: [26/03/2026]          │  │
│ └──────────────────────────────────────────────┘  │
│                                                     │
│ [+ Add Filter]                                     │
│                                                     │
│ SORTING                                            │
│ Sort by: [Changed ▼]  Direction: [Descending ▼]  │
│                                                     │
│ SAVE THIS FILTER                                   │
│ ☐ Filter name: [My Hot Deals        ]            │
│                                                     │
│ [Apply Filters] [Reset]                           │
│                                                     │
└─────────────────────────────────────────────────────┘

RESULTS PAGE:
┌─────────────────────────────────────────────────────┐
│ 45 Results matching "My Hot Deals"                  │
│ Stage = (New OR In Progress) AND Created > 01/03    │
│                                                     │
│ Saved Filters: [My Hot Deals] [My VIP] [At Risk]  │
│                                                     │
│ Contact A | email | phone | €100k Deal             │
│ Contact B | email | phone | €50k Deal              │
│ Contact C | email | phone | New Organization      │
│ ... (40 more)                                       │
│                                                     │
│ < 1 | 2 | 3 | 4 > (Pagination)                     │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## 📈 Implementation Timeline

| Task                     | Hours  | Difficulty  |
| ------------------------ | ------ | ----------- |
| Module Setup & Files     | 1      | ⭐ Easy     |
| FilterService Core Logic | 2      | ⭐⭐ Medium |
| SavedFilterService       | 1      | ⭐ Easy     |
| API Endpoints            | 1      | ⭐⭐ Medium |
| Form & Frontend          | 2      | ⭐⭐ Medium |
| JavaScript & AJAX        | 2      | ⭐⭐⭐ Hard |
| Database Setup & Entity  | 1      | ⭐ Easy     |
| Templates & CSS          | 1      | ⭐ Easy     |
| Testing & Bug Fixes      | 2      | ⭐⭐ Medium |
| **TOTAL**                | **13** | -           |

---

## 🚀 Quick Start Commands

```bash
# 1. Create module directory
mkdir -p web/modules/custom/crm_advanced_filters/{src,templates,js,css,config/install}

# 2. Create basic files
touch web/modules/custom/crm_advanced_filters/crm_advanced_filters.info.yml
touch web/modules/custom/crm_advanced_filters/crm_advanced_filters.module
touch web/modules/custom/crm_advanced_filters/crm_advanced_filters.routing.yml
touch web/modules/custom/crm_advanced_filters/crm_advanced_filters.services.yml

# 3. Enable module
drush en crm_advanced_filters

# 4. Test API
curl -X POST http://localhost/api/crm/filters/query \
  -H "Content-Type: application/json" \
  -d '{
    "entity_type": "contact",
    "conditions": {"logic": "AND", "rules": [...]},
    "pagination": {"page": 1, "limit": 20}
  }'
```

---

## ✅ Definition of Done

- [ ] Module created and enabled
- [ ] FilterService filters contacts by text, select, range
- [ ] SavedFilterService CRUD operations working
- [ ] API endpoints (/api/crm/filters/\*) functional
- [ ] Filter form with dynamic conditions
- [ ] AND/OR logic working
- [ ] Results page showing filtered data
- [ ] Saved filters persisted and retrievable
- [ ] Tests written for core logic (60% coverage min)
- [ ] UI/UX matches mockup
- [ ] Performance: Filter 1000+ records in < 500ms
- [ ] Access control enforced (users see only their data)

---

## 📝 Success Metrics

**Before**: Users spend ~2 min searching for 1 specific deal
**After**: Users find deal in < 10 seconds using advanced filters

**Before**: No way to save filter searches
**After**: Users can save 5+ filter combinations and reuse

**Before**: Can't filter by date range or amount range
**After**: Full range filtering for all numeric fields

---

**Status**: Ready to start development  
**Next Step**: Review this plan, then start building Phase 1 (FilterService)
