# Open CRM - Drupal 11 CRM System

Open CRM is a Drupal 11 based CRM with dashboard analytics, pipeline management, inline editing, AI-assisted content generation, and production hardening for data consistency.

## Features

### Core CRM

- Dashboard KPIs and charts for contacts, organizations, deals, and deal outcomes
- Contact, deal, organization, and activity management
- Kanban pipeline with drag-and-drop stage update
- Quick Add modal workflows for fast entity creation
- CSV import/export capabilities

### Data Integrity and Sync Hardening

- CSRF validation added on critical write APIs
- Frontend token handling aligned for same-origin requests
- Improved cache invalidation after create/update/delete
- Delete flow hardened to avoid false-success/stale-UI states
- Added reference cleanup to reduce orphan relationships on delete

### UX and Error Handling

- Professional custom 403 and 404 pages
- Improved dashboard refresh safety and null handling
- Better sync behavior after inline edits and deletion actions

## Requirements

- PHP 8.4+
- Drupal 11.3.3
- MariaDB 11.8+ or MySQL 8.0+
- Composer 2.x
- DDEV 1.23+ (recommended)

## Installation

### 1. Clone

```bash
git clone <repository-url> open_crm
cd open_crm
```

### 2. Install dependencies

```bash
composer install
```

### 3. Start local environment

```bash
ddev start
ddev drush site:install --existing-config -y
ddev drush cr
```

### 4. Access site

```bash
ddev launch
```

## Project Structure

```text
open_crm/
├── composer.json
├── config/
├── fixtures/
├── scripts/
│   ├── backup_database.sh
│   ├── restore_database.sh
│   └── production/
├── web/
│   ├── core/
│   ├── modules/custom/
│   ├── profiles/
│   ├── sites/
│   └── themes/
└── README.md
```

## Custom Modules

| Module | Purpose |
| --- | --- |
| crm | Core CRM controllers, listings, and access handling |
| crm_actions | Action buttons in CRM views |
| crm_activity_log | Contact activity logging widget |
| crm_ai_autocomplete | AI autocomplete and auto-create endpoints |
| crm_contact360 | Enhanced Contact 360 profile view |
| crm_dashboard | KPI dashboard and refresh endpoint |
| crm_data_quality | Data quality and production-readiness checks |
| crm_edit | Inline edit, batch update, create/delete APIs |
| crm_import | CSV import for CRM entities |
| crm_import_export | Admin import/export tools |
| crm_kanban | Deal pipeline board and stage updates |
| crm_login | Custom login and error pages |
| crm_navigation | CRM navigation helpers |
| crm_notifications | Event-based email notifications |
| crm_quickadd | Quick Add AJAX forms |
| crm_register | Custom user registration |
| crm_teams | Team-based access controls |
| crm_workflow | CRM workflow automation rules |

## Key Routes

| Route | Purpose |
| --- | --- |
| /crm/dashboard | Main dashboard |
| /crm/dashboard/refresh | Dashboard refresh data endpoint |
| /crm/my-contacts | My contacts list |
| /crm/my-deals | My deals list |
| /crm/my-organizations | My organizations list |
| /crm/my-activities | My activities list |
| /crm/all-contacts | All contacts list |
| /crm/all-deals | All deals list |
| /crm/all-organizations | All organizations list |
| /crm/all-activities | All activities list |
| /crm/my-pipeline | My deal pipeline |
| /crm/all-pipeline | Team/global pipeline |
| /crm/import | CSV import UI |
| /crm/edit/ajax/delete | Delete API |
| /api/v1/{entity_type}/{entity_id}/{field_name} | Inline update API |
| /api/v1/batch-update | Batch update API |
| /api/crm/ai/autocomplete | AI autocomplete API |
| /api/crm/ai/auto-create | AI auto-create API |
| /login | Custom login page |
| /access-denied | Custom 403 page |
| /page-not-found | Custom 404 page |

## Operations

### Backup and restore

```bash
bash scripts/backup_database.sh
bash scripts/restore_database.sh backups/<backup-file.sql>
```

### Production helper scripts

`scripts/production/` contains SQL and utility scripts for index tuning, view caching, and operational checks.

### Clear cache

```bash
ddev drush cr
```

## Post-Completion Review (2026-03-16)

Latest completed hardening includes:

- Dashboard production crash fixes for schema and stage edge-cases
- Removal of fragile field assumptions in key controllers
- AI endpoint access and CSRF hardening
- Improved delete consistency and cache invalidation
- Frontend data-sync and token handling improvements

Recent baseline commits:

- `2643be4` Harden CRM data sync, CSRF protection, and delete integrity
- `dc450be` Allow authenticated users on CRM AI auto-create endpoint
- `6013ca7` Remove hard dependency on field_deleted_at in CRM controllers

## Quick Validation Checklist

```bash
# 1) Rebuild caches
ddev drush cr

# 2) Confirm no php syntax issues on key hardened files
php -l web/modules/custom/crm_edit/src/Controller/DeleteController.php
php -l web/modules/custom/crm_ai_autocomplete/src/Controller/AIAutoCompleteController.php

# 3) Confirm routes are available
ddev drush router:debug | grep -E "crm/dashboard|crm/edit/ajax/delete|api/crm/ai/auto-create"
```

## Contributing

1. Create a branch: `git checkout -b feature/my-feature`
2. Commit changes: `git commit -m "Add feature"`
3. Push branch: `git push origin feature/my-feature`
4. Open a pull request

## License

See [LICENSE.txt](LICENSE.txt) for license details.

---

Version: 1.1.0  
Last Updated: March 16, 2026  
Maintainer: Thanh Phuc
