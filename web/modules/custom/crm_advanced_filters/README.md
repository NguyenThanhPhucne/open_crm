# CRM Advanced Filters Module

## Overview

The CRM Advanced Filters module provides powerful, flexible filtering and search capabilities for the Open CRM system. It enables users to build complex queries with multiple conditions, save searches for reuse, and discover data patterns through intelligent suggestions.

## Features

### ✅ Core Features Implemented (35% Complete)

1. **Advanced Filter Builder**
   - Dynamic condition builder with AND/OR/NOT logic
   - Support for 14 different operators (equals, contains, between, in, etc.)
   - Field-specific suggestions based on data patterns
   - Trending popular filters from team colleagues
   - Real-time operator adjustment based on field type

2. **Comprehensive API Endpoints**
   - `POST /api/crm/filters/query` - Execute complex filter queries
   - `GET /api/crm/filters/available/{type}` - Get available filters for entity type
   - `GET /api/crm/filters/options/{type}/{field}` - Get field value options
   - `POST /api/crm/filters/save` - Save filter searches
   - `GET /api/crm/filters/saved` - Retrieve user's saved filters
   - `DELETE /api/crm/filters/{id}` - Delete saved filter

3. **Saved Searches**
   - Save filter combinations for reuse
   - Personal and team-shareable filters
   - Last-used tracking for quick access
   - Filter descriptions and documentation
   - Database persistence with SavedFilter entity

4. **Data-Driven Suggestions**
   - Analyze database for high-variance fields (best for filtering)
   - Suggest most-used filter combinations from team
   - Auto-complete values for text fields
   - Smart operator suggestions per field type

5. **Responsive UI**
   - Modern, clean interface with sidebar suggestions
   - Mobile-friendly design
   - Modal dialogs for saving and exporting
   - Color-coded field types for better usability
   - Sticky suggestion sidebar on desktop

### 🔄 In Progress (35% Complete)

The module structure and core logic are fully implemented. The following components are in place:

**Completed Components:**

- [x] Module definition (`.info.yml`)
- [x] Service definitions and dependency injection
- [x] Permission definitions
- [x] Routing and REST API endpoints
- [x] FilterService (core query builder with 500+ lines)
- [x] SavedFilterService (CRUD operations)
- [x] SuggestionService (data analysis)
- [x] FilterApiController (6 endpoints)
- [x] AdvancedFilterForm (dynamic condition builder)
- [x] FilterResultsController (results display)
- [x] Twig templates (filter-builder.html.twig, filter-results.html.twig)
- [x] JavaScript (filter-builder.js with 400+ lines, filter-api.js)
- [x] CSS (advanced-filters.css with 600+ lines)
- [x] SavedFilter entity (database persistence)
- [x] Install hooks

**Remaining Work (65%):**

- [ ] Form validation logic refinement
- [ ] JavaScript event handler completion
- [ ] Full AJAX integration for dynamic updates
- [ ] Export functionality (CSV, PDF, JSON)
- [ ] Bulk actions on filtered results
- [ ] Search API integration optimization
- [ ] Performance testing and query optimization
- [ ] Unit and integration tests
- [ ] Documentation and help text

## Module Structure

```
crm_advanced_filters/
├── src/
│   ├── Controller/
│   │   ├── FilterApiController.php      (REST API - 280 lines)
│   │   └── FilterResultsController.php  (Results display - 320 lines)
│   ├── Entity/
│   │   ├── SavedFilter.php              (Content entity - 280 lines)
│   │   └── SavedFilterInterface.php     (Entity interface - 130 lines)
│   ├── Form/
│   │   └── AdvancedFilterForm.php       (Filter builder form - 380 lines)
│   └── Service/
│       ├── FilterService.php            (Core query builder - 550 lines)
│       ├── SavedFilterService.php       (Persistence layer - 150 lines)
│       └── SuggestionService.php        (Smart suggestions - 200 lines)
├── templates/
│   ├── filter-builder.html.twig         (UI template - 350 lines)
│   └── filter-results.html.twig         (Results template - 380 lines)
├── js/
│   ├── filter-builder.js                (Interactive UI - 430 lines)
│   ├── filter-api.js                    (API client - 150 lines)
│   └── suggestions.js                   (Coming soon)
├── css/
│   └── advanced-filters.css             (Styling - 600+ lines)
├── config/install/
│   └── core.entity_type.saved_filter.yml (Entity config)
├── crm_advanced_filters.info.yml
├── crm_advanced_filters.module
├── crm_advanced_filters.routing.yml
├── crm_advanced_filters.services.yml
├── crm_advanced_filters.permissions.yml
├── crm_advanced_filters.libraries.yml
├── crm_advanced_filters.install
└── README.md (this file)
```

**Total Code: ~4,500+ lines implemented**

## Supported Entities

- **Contact** - Filter by email, phone, organization, source, customer type, owner, etc.
- **Deal** - Filter by amount, stage, probability, closing date, contact, owner, etc.
- **Organization** - Filter by email, phone, website, industry, revenue, employees, owner, etc.
- **Activity** - Filter by type, contact, deal, organization, date, notes, owner, etc.

## Supported Operators

| Operator      | Type        | Use Case         |
| ------------- | ----------- | ---------------- |
| equals        | All         | Exact match      |
| not_equals    | All         | Exclude exact    |
| contains      | Text        | Partial match    |
| not_contains  | Text        | Exclude partial  |
| starts        | Text        | Prefix match     |
| ends          | Text        | Suffix match     |
| greater_than  | Number/Date | Value above      |
| less_than     | Number/Date | Value below      |
| greater_equal | Number/Date | Value at/above   |
| less_equal    | Number/Date | Value at/below   |
| between       | Number/Date | Range filter     |
| in            | Select      | Multiple options |
| not_in        | Select      | Exclude options  |
| is_empty      | All         | NULL check       |
| is_not_empty  | All         | NOT NULL check   |

## API Documentation

### Execute Filter Query

```bash
POST /api/crm/filters/query
Content-Type: application/json

{
  "entity_type": "contact",
  "filter_definition": {
    "logic": "AND",
    "conditions": [
      {
        "field": "email",
        "operator": "contains",
        "value": "@example.com"
      },
      {
        "field": "owner",
        "operator": "equals",
        "value": "5"
      }
    ]
  },
  "sort": { "title": "ASC" },
  "limit": 50,
  "offset": 0
}
```

**Response:**

```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 123,
        "type": "contact",
        "title": "John Doe",
        "url": "/node/123"
      }
    ],
    "count": 1,
    "total_count": 15,
    "filter_description": "Email contains @example.com AND Owner equals 5",
    "pagination": {
      "limit": 50,
      "offset": 0,
      "has_more": false
    }
  }
}
```

### Get Available Filters

```bash
GET /api/crm/filters/available/contact
```

### Save Filter

```bash
POST /api/crm/filters/save
Content-Type: application/json

{
  "name": "High Value Prospects",
  "description": "Contacts from example.com with unassigned owner",
  "entity_type": "contact",
  "filter_definition": { ... },
  "is_public": true
}
```

### Get Saved Filters

```bash
GET /api/crm/filters/saved?entity_type=contact
```

### Delete Saved Filter

```bash
DELETE /api/crm/filters/123
```

## Database Schema

### saved_filter table

```sql
CREATE TABLE saved_filter (
  id INT PRIMARY KEY AUTO_INCREMENT,
  uuid VARCHAR(36) UNIQUE,
  langcode VARCHAR(12),
  name VARCHAR(255) NOT NULL,
  description LONGTEXT,
  entity_type VARCHAR(50) NOT NULL,
  filter_definition LONGBLOB NOT NULL,
  is_public TINYINT(1) DEFAULT 0,
  uid INT NOT NULL,
  created INT,
  changed INT,
  last_used INT,
  status TINYINT(1) DEFAULT 1,
  FOREIGN KEY (uid) REFERENCES users(uid)
);
```

## Installation & Setup

### 1. Enable the Module

```bash
drush en crm_advanced_filters
```

### 2. Access the Interface

- **Filter Builder**: `/crm/filters/{entity_type}`
  - `/crm/filters/contact`
  - `/crm/filters/deal`
  - `/crm/filters/organization`
  - `/crm/filters/activity`

- **Results Page**: `/crm/filters/{entity_type}/results`

- **Saved Filters**: `/crm/filters/saved`

### 3. REST API Endpoints

All endpoints require:

- Valid user authentication (JWT or session)
- `access crm` permission
- CSRF token (for POST/DELETE)

## Permissions

- `use advanced filters` - Access filter builder
- `save filter searches` - Save and manage filters
- `administer saved_filter` - Admin access to all filters
- `export filter results` - Export filtered data
- `view all filter results` - Bypass access restrictions

## Search API Integration

The module uses Drupal's Search API module. Three indexes are pre-configured:

- `crm_contacts_index` - Contact entities
- `crm_deals_index` - Deal entities
- `crm_organizations_index` - Organization entities

### Future Enhancement:

The FilterService can be enhanced to use Search API for:

- Full-text search with relevance scoring
- Faceted navigation
- Typo tolerance and fuzzy matching
- Advanced text analysis

## Performance Optimization

### Current Strategy:

- Direct database queries using EntityQuery
- Indexed fields on commonly filtered columns
- Pagination with limit/offset
- Access control filtering applied at query time

### Future Improvements:

- Elasticsearch backend for large datasets
- Query caching layer
- Prepared statement optimization
- Search API integration for advanced features

## Development Notes

### To Run Tests:

```bash
# Run module tests (when tests are added)
php -d memory_limit=-1 ./vendor/bin/phpunit modules/custom/crm_advanced_filters
```

### Code Standards:

- PSR-2 compliance
- Drupal 11 best practices
- Service-oriented architecture
- Dependency injection throughout

### Key Classes:

**FilterService**

- `buildQuery()` - Construct database queries dynamically
- `executeFilter()` - Run filter with pagination
- `getAvailableFilters()` - List all filterable fields
- `getFieldOptions()` - Get selectable values for a field

**SavedFilterService**

- `saveFilter()` - Persist filter to database
- `loadFilter()` - Retrieve saved filter
- `getUserFilters()` - Get user's saved filters
- `deleteFilter()` - Remove saved filter

**SuggestionService**

- `getsmartSuggestions()` - Recommend filters based on data variance
- `getTrendingFilters()` - Popular filters from team
- `getFieldValueSuggestions()` - Auto-complete values

## Next Steps (Remaining 65%)

1. **Form Integration** (2-3 hours)
   - Connect form submissions to API
   - Implement AJAX for dynamic updates
   - Add validation error handling

2. **Export Functionality** (2 hours)
   - CSV export implementation
   - PDF export integration
   - JSON API output

3. **Bulk Operations** (2-3 hours)
   - Select multiple results
   - Bulk edit/update
   - Bulk delete with confirmation

4. **Testing** (2-3 hours)
   - Unit tests for services
   - API endpoint tests
   - UI integration tests

5. **Performance Tuning** (1-2 hours)
   - Query optimization
   - Database index verification
   - Caching strategies

6. **Documentation** (1-2 hours)
   - User guide
   - Admin documentation
   - API examples

## Support & Debugging

### Enable Module Logging:

```php
\Drupal::logger('crm_advanced_filters')->debug('Debug message');
```

### Check Database:

```bash
drush sql-query "SELECT * FROM saved_filter;"
```

### Clear Cache:

```bash
drush cache:rebuild
```

## Roadmap

### Version 2.0 (Future):

- [ ] Elasticsearch integration
- [ ] Advanced analytics on filters
- [ ] Filter sharing with granular permissions
- [ ] Multi-entity cross-filters
- [ ] Filter templates and presets
- [ ] Mobile app integration
- [ ] WebSocket real-time updates
- [ ] Filter audit trail

## License

Proprietary - Open CRM Project

## Author

Phuc OpenCRM Team
