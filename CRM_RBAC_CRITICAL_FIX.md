# 🚨 CRM RBAC - CRITICAL SETUP INSTRUCTIONS

## Your Error (Solved ✅)

**Problem:** `ServiceNotFoundException: You have requested a non-existent service "crm.access_service"`

**Root Cause:** Drupal's service container cache wasn't cleared after adding services to `crm.services.yml`

**Solution:** Run this command:

```bash
ddev exec drush cache:rebuild
```

---

## ⚡ Quick Setup Checklist

Follow these exact steps in order:

### Step 1: Verify Services Are Deployed ✅

Check that these files exist:

```bash
# Check service files exist
ls -la web/modules/custom/crm/src/Service/
# Should show:
#   - CRMAccessService.php
#   - CRMDashboardSecurityService.php
#   - DataIntegrityService.php
```

### Step 2: Clear Drupal Cache (CRITICAL) ✅

```bash
ddev exec drush cache:rebuild
```

**MUST SEE:** `[success] Cache rebuild complete.`

### Step 3: Verify Service is Loaded ✅

Try accessing a Views page:

```
https://your-crm-site/crm/all-contacts
```

**Should NOT see:** ServiceNotFoundException error

### Step 4: Test Access Control ✅

**As Admin (should see all contacts):**

1. Log in with admin account
2. Go to `/crm/all-contacts`
3. Should see all contacts in system

**As Sales Rep (should see own + team contacts):**

1. Log in with sales rep account
2. Go to `/crm/all-contacts`
3. Should see only own + team contacts
4. Try accessing another rep's contact directly
5. Should get "Access Denied" message

---

## 🔍 If It's Still Not Working

### Check 1: Service Definition

```bash
# View the service configuration
cat web/modules/custom/crm/crm.services.yml | head -20
```

**Should show:**

```yaml
services:
  crm.access_service:
    class: Drupal\crm\Service\CRMAccessService
    arguments:
      - "@database"
      - "@logger.factory"
      - "@entity_type.manager"
```

### Check 2: PHP Syntax

```bash
# Check for PHP syntax errors
ddev exec php -l web/modules/custom/crm/src/Service/CRMAccessService.php
ddev exec php -l web/modules/custom/crm/crm.module
```

**Should show:** `No syntax errors detected`

### Check 3: Module Info

```bash
# Verify module is enabled
ddev exec drush pm:list | grep -i "crm core"
```

**Should show:** `✓ enabled crm`

### Check 4: Clear Cache Again

Sometimes Drupal needs cache cleared twice:

```bash
ddev exec drush cache:rebuild
# Wait a few seconds then try again
sleep 2
ddev exec drush cache:rebuild
```

---

## 🎯 Common Issues & Solutions

### ❌ Error: "Module crm not found"

**Solution:** Ensure module is enabled

```bash
ddev exec drush pm:enable crm
```

### ❌ Error: "Service not found" (after cache rebuild)

**Solution 1:** Check namespace in crm.services.yml matches class

```bash
# The class line should be:
# class: Drupal\crm\Service\CRMAccessService

# Verify the PHP file namespace is:
# namespace Drupal\crm\Service;
```

**Solution 2:** Force flush all caches

```bash
ddev exec drush cache:rebuild
# If still issues, clear file-system cache too
ddev exec rm -rf web/sites/default/files/.drupal-production-cache
ddev exec drush cache:rebuild
```

### ❌ Error: "Hook not triggering" / Views showing all data

**Solution:** The hook might not be registered yet, clear cache again:

```bash
ddev exec drush cache:rebuild
```

### ❌ Error: "Class not found" for service

**Problem:** PHP namespace mismatch between YAML and class file  
**Solution:**

```bash
# Check the class file header
head -10 web/modules/custom/crm/src/Service/CRMAccessService.php
# Should show: namespace Drupal\crm\Service;

# Check the YAML definition
cat web/modules/custom/crm/crm.services.yml
# Should show: class: Drupal\crm\Service\CRMAccessService
```

---

## ✅ Verification Checklist

After following the steps above, verify:

- [ ] Cache rebuild completed successfully
- [ ] Services file has correct YAML syntax
- [ ] Service class files exist and have correct namespace
- [ ] Views pages load without ServiceNotFoundException
- [ ] Admin can see all records
- [ ] Sales Rep can see only own records
- [ ] Anonymous user gets access denied
- [ ] No PHP syntax errors in logs

---

## 📞 Need More Help?

| Issue                            | Reference                                                                        |
| -------------------------------- | -------------------------------------------------------------------------------- |
| Want full setup guide?           | See [CRM_RBAC_SETUP_AND_MIGRATION.md](CRM_RBAC_SETUP_AND_MIGRATION.md)           |
| Want development guide?          | See [CRM_RBAC_IMPLEMENTATION_GUIDE.md](CRM_RBAC_IMPLEMENTATION_GUIDE.md)         |
| Want API reference?              | See [CRM_RBAC_API_REFERENCE.md](CRM_RBAC_API_REFERENCE.md)                       |
| Want to understand architecture? | See [CRM_RBAC_SYSTEM_DOCUMENTATION.md](CRM_RBAC_SYSTEM_DOCUMENTATION.md)         |
| Want security audit checklist?   | See [CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md](CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md) |

---

## 🚀 Next Steps

After cache rebuild is successful:

1. **Access a Views page** to confirm no errors
2. **Log in as different roles** to test access control
3. **Review logs** at `/admin/reports/dblog` for any warnings
4. **Follow full setup guide** for complete deployment (see links above)

---

**Status:** The cache rebuild should have fixed your issue. Try accessing your Views page now!

**Last Updated:** March 10, 2026  
**Drupal Version:** 11.x  
**Module:** CRM Core
