# 🚨 CRITICAL FIXES CHECKLIST

> **TL;DR:** Dự án chưa sẵn sàng production. Cần fix 3 lỗi CRITICAL và 4 lỗi HIGH PRIORITY trước khi deploy.

---

## ❌ P0 - CRITICAL (Block Production)

### 1. Access Control Conflict 🔴

**File:** [crm.module](web/modules/custom/crm/crm.module) + [crm_teams.module](web/modules/custom/crm_teams/crm_teams.module)

**Problem:** Hai module implement cùng hooks → conflict nghiêm trọng

**Fix:**

```bash
# Disable crm_teams
ddev drush pmu crm_teams -y

# Merge logic vào crm module
# TODO: Update crm.module với unified access control
# TODO: Test all permission scenarios
```

**Status:** ⏳ TODO  
**ETA:** 4 hours

---

### 2. Duplicate Import Modules 🔴

**Files:** `crm_import/` vs `crm_import_export/`

**Fix:**

```bash
# Keep crm_import_export (has DataValidationService)
ddev drush pmu crm_import -y
ddev drush pmu feeds -y
rm -rf web/modules/custom/crm_import/
```

**Status:** ⏳ TODO  
**ETA:** 1 hour

---

### 3. Clean Up Scripts Folder 🟡

**Path:** `scripts/` (80+ files)

**Fix:**

```bash
# Reorganize structure
mkdir -p scripts/{production,setup,maintenance,development,deprecated}

# Move files to appropriate folders
# TODO: See COMPREHENSIVE_AUDIT_REPORT.md for structure
```

**Status:** ⏳ TODO  
**ETA:** 2 hours

---

## ⚠️ P1 - HIGH PRIORITY (Before Launch)

### 4. Missing ECA Automation 🟡

**Problem:** No automation workflows (Deal Won validation, Email notification)

**Fix Option A - ECA Module:**

- Go to `/admin/config/workflow/eca/add`
- Create "Deal Won Validation" model
- Create "Deal Won Email" model

**Fix Option B - Custom Hook (Faster):**

```bash
# Create new module
ddev drush generate module

# Module name: crm_workflow
# Implement: hook_node_presave() + hook_node_update()
# See COMPREHENSIVE_AUDIT_REPORT.md for code
```

**Status:** ⏳ TODO  
**ETA:** 6 hours

---

### 5. Enable Views Caching 🟡

**Fix:**

```bash
ddev drush config:set views.view.my_contacts display.default.cache.type time -y
ddev drush config:set views.view.my_deals display.default.cache.type time -y
ddev drush config:set views.view.my_activities display.default.cache.type time -y

# Or run script
ddev exec php scripts/maintenance/configure_views_caching.php
```

**Status:** ⏳ TODO  
**ETA:** 3 hours

---

### 6. Fix Search API Access Control 🟡

**Fix:**

```bash
ddev drush config:set search_api.index.crm_contacts_index processor_settings.content_access.weights.preprocess_query -30 -y
ddev drush search-api:rebuild-tracker crm_contacts_index
ddev drush search-api:index crm_contacts_index
```

**Status:** ⏳ TODO  
**ETA:** 2 hours

---

### 7. Add Database Indexes 🟡

**Fix:**

```sql
-- Run in database
CREATE INDEX idx_field_owner ON node__field_owner (field_owner_target_id);
CREATE INDEX idx_field_assigned_to ON node__field_assigned_to (field_assigned_to_target_id);
CREATE INDEX idx_field_assigned_staff ON node__field_assigned_staff (field_assigned_staff_target_id);
CREATE INDEX idx_field_team ON user__field_team (field_team_target_id);
```

```bash
# Via ddev
ddev mysql < scripts/production/add_indexes.sql
```

**Status:** ⏳ TODO  
**ETA:** 30 min

---

## ✅ Testing Checklist

After fixes, verify:

- [ ] Sales Rep A cannot see Sales Rep B's contacts
- [ ] Sales Manager can see all team data
- [ ] Deal stage "Won" triggers validation + email
- [ ] Views load in < 300ms with 10k records
- [ ] Search respects access control
- [ ] Import validates duplicates correctly
- [ ] No PHP errors in logs

---

## 📊 Progress Tracker

| Phase         | Tasks | Completed | Progress      |
| ------------- | ----- | --------- | ------------- |
| P0 - Critical | 3     | 0/3       | ░░░░░░░░░░ 0% |
| P1 - High     | 4     | 0/4       | ░░░░░░░░░░ 0% |
| Testing       | 7     | 0/7       | ░░░░░░░░░░ 0% |

**Total Progress:** 0/14 (0%)

---

## 🎯 Quick Start

```bash
# 1. Backup database
ddev export-db --file=backup_$(date +%Y%m%d).sql.gz

# 2. Fix P0 issues
ddev drush pmu crm_teams crm_import feeds -y
# TODO: Merge crm_teams logic vào crm module
# TODO: Reorganize scripts/

# 3. Fix P1 issues
ddev exec php scripts/maintenance/configure_views_caching.php
ddev drush config:set search_api.index.crm_contacts_index processor_settings.content_access.weights.preprocess_query -30 -y
ddev mysql < scripts/production/add_indexes.sql
# TODO: Implement crm_workflow module

# 4. Test
ddev drush uli
# Manual testing...

# 5. Deploy
git add -A
git commit -m "fix: critical production readiness fixes"
git push origin main
```

---

## 📞 Questions?

See full analysis: [COMPREHENSIVE_AUDIT_REPORT.md](COMPREHENSIVE_AUDIT_REPORT.md)

**Issue tracking:** Create Jira tickets from this checklist  
**Timeline:** 6 working days for all fixes

---

**Last updated:** 4 March 2026
