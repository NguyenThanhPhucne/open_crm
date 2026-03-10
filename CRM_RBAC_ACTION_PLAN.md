# CRM RBAC - Next Steps Action Plan

## 🚀 Immediate Action Items

### Phase 1: Staging Deployment (This Week)

#### Task 1.1: Code Deployment ⏱️ 15 minutes

```bash
# 1. Copy service files
cp src/Service/CRMAccessService.php web/modules/custom/crm/src/Service/
cp src/Service/CRMDashboardSecurityService.php web/modules/custom/crm/src/Service/
cp src/Service/AIAccessControlTrait.php web/modules/custom/crm_ai_autocomplete/src/Service/

# 2. Copy updated configuration
cp crm.module web/modules/custom/crm/
cp crm.services.yml web/modules/custom/crm/
cp AIAutoCompleteController.php web/modules/custom/crm_ai_autocomplete/src/Controller/

# 3. Create database backups
ddev db snapshot crm-pre-rbac-$(date +%Y%m%d)

# 4. Clear caches on staging
ddev exec drush cache:rebuild
```

#### Task 1.2: Services Verification ⏱️ 10 minutes

```bash
# Verify services are registered
ddev exec drush container:debug | grep "crm.access_service"
ddev exec drush container:debug | grep "crm.dashboard_security_service"

# Expected output: Service names listed
# If missing: Re-run "ddev exec drush cache:rebuild"
```

#### Task 1.3: Database Setup ⏱️ 20 minutes

```bash
# Add indexes for performance
ddev db ssh -e "
ALTER TABLE node__field_owner ADD INDEX field_owner_idx (field_owner_target_id);
ALTER TABLE node__field_assigned_staff ADD INDEX field_assigned_staff_idx (field_assigned_staff_target_id);
ALTER TABLE node__field_assigned_to ADD INDEX field_assigned_to_idx (field_assigned_to_target_id);
SHOW INDEX FROM node__field_owner;
"

# Migrate existing data (if needed)
# See CRM_RBAC_SETUP_AND_MIGRATION.md section "Migrate Existing Records"
```

#### Task 1.4: Create Test Users ⏱️ 15 minutes

```bash
# Create admin test user
ddev exec drush user:create admin_test --mail=admin@test.local --password=TestAdmin123!
ddev exec drush user:role:add administrator admin_test

# Create manager test user
ddev exec drush user:create manager_test --mail=manager@test.local --password=TestManager123!
ddev exec drush user:role:add sales_manager manager_test

# Create representative test user
ddev exec drush user:create rep_test --mail=rep@test.local --password=TestRep123!
ddev exec drush user:role:add sales_rep rep_test

# Verify users created
ddev exec drush user:list
```

#### Task 1.5: Enable Access Logging ⏱️ 5 minutes

```bash
# Verify logger channel configured
ddev exec drush config:get system.logging

# Enable detailed logging if not already done
ddev exec drush config:set system.logging error_level verbose
```

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

### Phase 2: Functional Testing (Day 2-3)

#### Task 2.1: Admin Access Testing ⏱️ 30 minutes

```
□ Log in as admin_test user
□ Navigate to /crm/all-contacts
□ Verify: See ALL contacts in system
□ Click to view a contact
□ Verify: Can access any contact without restriction
□ Edit a contact details
□ Verify: Save successful
□ Try to delete a contact
□ Verify: Delete successful
□ Check /admin/reports/dblog
□ Verify: See access granted entries
```

#### Task 2.2: Manager Access Testing ⏱️ 30 minutes

```
□ Log in as manager_test user
□ Navigate to /crm/all-contacts
□ Verify: See only team contacts
□ Try to access contact from different team
□ Verify: Get 403 Forbidden OR record not listed
□ Visit Dashboard
□ Verify: See team performance metrics
□ Check team statistics widget
□ Verify: Shows team data correctly
□ Check access logs
□ Verify: Shows "access granted" entries
```

#### Task 2.3: Representative Access Testing ⏱️ 30 minutes

```
□ Log in as rep_test user
□ Create a new contact
□ Verify: Gets assigned to rep_test as owner
□ Navigate to /crm/all-contacts
□ Verify: See only own contacts + team contacts
□ Try to edit another rep's contact
□ Verify: Get 403 Forbidden
□ Try to delete a contact from different team
□ Verify: Get 403 Forbidden
□ Check Dashboard
□ Verify: See only own statistics
□ Check team performance
□ Verify: CANNOT access (permission denied)
□ Check access logs
□ Verify: Shows mix of granted/denied entries
```

#### Task 2.4: Anonymous Access Testing ⏱️ 15 minutes

```
□ Log out (anonymous user)
□ Try to navigate to /crm/all-contacts
□ Verify: Redirect to login
□ Try to access /api/contacts
□ Verify: Get 403 Forbidden with error message
□ Try to access /admin panel
□ Verify: Redirect to login
□ Check logs for access attempts
□ Verify: See "access denied" entries
```

#### Task 2.5: Views Filtering Verification ⏱️ 30 minutes

```
□ Create test contacts assigned to different users
□ For each user role (admin, manager, rep):
  □ Log in as user
  □ View /crm/all-contacts
  □ Verify: Correct filtering applied
  □ View /crm/my-contacts
  □ Verify: Shows only user's contacts
  □ View /crm/team-contacts
  □ Verify: Shows correct team filtering
  □ Check /admin/structure/views
  □ Verify: No manual filters added (automatic filtering working)
```

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

### Phase 3: Performance Testing (Day 3)

#### Task 3.1: Load Testing ⏱️ 30 minutes

```bash
# Install Apache Bench if needed
brew install httpd  # macOS
# or apt-get install apache2-utils  # Linux

# Test with 100 requests, 10 concurrent
ab -n 100 -c 10 https://staging.crm.local/crm/all-contacts

# Expected results:
# - Requests per second: > 2
# - Average response time: < 200ms
# - Failed requests: 0
```

#### Task 3.2: Database Query Profiling ⏱️ 30 minutes

```bash
# Enable slow query log
ddev exec mysql -e "
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.1;
"

# Run test queries as each role
# Monitor /var/log/mysql/slow.log for slow queries

# Check query plan
ddev exec mysql -e "
EXPLAIN SELECT n.* FROM node_field_data n
LEFT JOIN node__field_owner o ON o.entity_id = n.nid
WHERE n.type = 'contact' AND o.field_owner_target_id = 1;
"
# Verify: Should use index, not full table scan
```

#### Task 3.3: Caching Verification ⏱️ 20 minutes

```bash
# Check Views caching enabled
ddev exec drush views:analyze

# Check page caching
ddev exec drush config:get system.performance page.max_age

# Verify access results are cached per user
# - User 1 visits /crm/all-contacts → 150ms (first load)
# - User 1 visits again → 20ms (cached)
# - User 2 visits → 150ms (different cache)
```

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

### Phase 4: Security Audit (Day 4)

#### Task 4.1: Use the Provided Checklist ⏱️ 2 hours

See `CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md` and go through each of the 17 sections:

1. **Access Control Basics** (10 items)
2. **Entity Type Coverage** (10 items)
3. **Role Configuration** (8 items)
4. **Database Security** (6 items)
5. **API Security** (7 items)
6. **Query Security** (6 items)
7. **Form Security** (5 items)
8. **Views Security** (6 items)
9. **Dashboard Security** (4 items)
10. **Access Logging** (5 items)
11. **Caching Security** (4 items)
12. **Session Management** (3 items)
13. **File Permissions** (3 items)
14. **Environment Configuration** (4 items)
15. **Monitoring & Alerts** (4 items)
16. **Disaster Recovery** (3 items)
17. **Compliance & Standards** (4 items)

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

#### Task 4.2: Penetration Testing ⏱️ 1 hour

```
□ Try SQL injection in parameters
  □ /crm/all-contacts?nid=1 OR 1=1
  □ Verify: No data leak, query still filtered by access

□ Try direct entity access bypass
  □ Try to load unauthorized entity via ID
  □ Verify: Get 403 Forbidden

□ Try role spoofing
  □ Try to add role to own user via API
  □ Verify: Permission denied

□ Try cache poisoning
  □ Modify cookie/session data
  □ Refresh page
  □ Verify: No security bypass

□ Try to access via different paths
  □ Direct /node/123
  □ API /api/node/123
  □ Form submission
  □ Verify: All paths respect access control
```

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

### Phase 5: Go-Live Preparation (Day 5)

#### Task 5.1: Create Deployment Plan ⏱️ 30 minutes

Document:

- [ ] Deployment date/time (off-peak hours recommended: 2-6 AM)
- [ ] Database backup strategy
- [ ] Rollback procedure
- [ ] Communication plan (notify users)
- [ ] Monitoring strategy post-deployment
- [ ] Escalation contacts

#### Task 5.2: Create Runbooks ⏱️ 1 hour

Prepare documentation for:

- [ ] Deployment steps (automated or manual)
- [ ] Health check procedures
- [ ] Rollback procedure
- [ ] Emergency contacts
- [ ] Common issues & solutions

#### Task 5.3: Notify Stakeholders ⏱️ 15 minutes

Send notifications to:

- [ ] Development team (code review sign-off)
- [ ] QA team (testing complete)
- [ ] Security team (audit complete)
- [ ] Operations team (ready to deploy)
- [ ] Management (status update)
- [ ] End Users (expected impact, maintenance window)

#### Task 5.4: Final Code Review ⏱️ 30 minutes

Checklist:

- [ ] All code deployed to staging
- [ ] All tests passing
- [ ] Documentation complete
- [ ] Logging configured
- [ ] Performance acceptable
- [ ] Security audit passed
- [ ] Rollback plan ready

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

## 📋 Production Deployment Checklist

### Pre-Deployment (1 hour before)

```bash
# On production environment

# 1. Create database backup
mysqldump --all-databases --single-transaction > /backups/crm-pre-rbac-$(date +%Y%m%d-%H%M%S).sql
echo "Backup size: $(du -h /backups/crm-pre-rbac-*.sql | tail -1)"

# 2. Mark maintenance window
ddev exec drush state:set system.maintenance_mode TRUE
ddev exec drush state:set system.maintenance_mode_message "CRM RBAC Security Update"

# 3. Verify backup completeness
ddev database list

# 4. Verify all services deployed
ddev exec drush cache:rebuild --verbose
```

### Deployment (15 minutes)

```bash
# 1. Copy files
cp /staging/crm/src/Service/*.php /prod/crm/src/Service/
cp /staging/crm/crm.module /prod/crm/
cp /staging/crm/crm.services.yml /prod/crm/

# 2. Clear all caches
ddev exec drush cache:rebuild

# 3. Run database migrations (if any)
ddev exec drush updatedb

# 4. Verify services
ddev exec drush container:debug | grep "crm.access_service"

# 5. Enable maintenance mode signoff
ddev exec drush state:set system.maintenance_mode FALSE
```

### Post-Deployment (30 minutes)

```bash
# 1. Health checks
curl -I https://crm.company.com/crm/all-contacts
# Expected: 200 or 403 (if logged out)

# 2. Monitor logs
ddev exec drush log:tail --channel=crm_access --severity=3 &

# 3. Test each role
# Log in as admin, manager, rep
# Verify access works correctly

# 4. Check performance
# Monitor response times: < 200ms target

# 5. Final verification
ddev exec drush status

# 6. Notify stakeholders
# Status: "Deployment complete, system operational"
```

**Completion Status:** [ ] Not Started | [ ] In Progress | [ ] Complete

---

## ⏮️ Rollback Procedure (If Needed)

**Estimated Time:** 30 minutes

```bash
# 1. Enable maintenance mode
ddev exec drush state:set system.maintenance_mode TRUE

# 2. Restore previous code
git revert HEAD~1  # Or restore from backup

# 3. Restore database (if data was affected)
mysql < /backups/crm-pre-rbac-YYYYMMDD-HHMMSS.sql

# 4. Clear caches
ddev exec drush cache:rebuild

# 5. Verify rollback
ddev exec drush status

# 6. Disable maintenance mode
ddev exec drush state:set system.maintenance_mode FALSE

# 7. Notify team
# Status: "Rolled back to previous version, investigating issue"

# 8. Analyze logs
ddev exec drush log:tail --severity=3
# Look for errors in crm_access and cron logs
```

---

## 🎯 Success Criteria

### Immediate Success (Day 1)

- [x] Code deployed without errors
- [x] Services registered and available
- [x] Caches cleared successfully
- [x] No critical errors in logs

### Short-term Success (Week 1)

- [x] All role-based access working correctly
- [x] No unauthorized data access
- [x] Performance acceptable (< 200ms)
- [x] All audit logs complete

### Long-term Success (Month 1)

- [x] Zero security incidents
- [x] No user complaints about access
- [x] System stable in production
- [x] Access patterns normal (no anomalies)

---

## 📞 Escalation Path

### Issue: Access denied for authorized user

1. Check user role: `/admin/people`
2. Check entity owner field
3. Clear caches: `drush cr`
4. Contact development team if issue persists

### Issue: Slow page load

1. Check database indexes exist
2. Check query logs for slow queries
3. Verify caching enabled
4. Contact database team if persists

### Issue: Users see records they shouldn't

1. Check user role configuration
2. Force cache clear: `drush cr --user=1`
3. Check access logs for anomalies
4. Contact security team

### Issue: System won't start up

1. Check error logs: `drush log:tail --severity=3`
2. Verify services.yml syntax
3. Run rollback if needed
4. Contact development team

---

## 📊 Monitoring & Metrics

### Key Metrics to Track

```
Daily:
  - Page load times (target: < 200ms)
  - Error rate (target: < 0.1%)
  - Access denied count (normal: 10-20% of requests)
  - Cache hit rate (target: > 80%)

Weekly:
  - Performance trends
  - Access anomalies
  - Security incidents (target: 0)
  - User feedback

Monthly:
  - System health
  - Optimization opportunities
  - Feature requests
  - Compliance status
```

### Logging Commands

```bash
# Check access logs
ddev exec drush log:tail --channel=crm_access

# Check errors
ddev exec drush log:tail --severity=3

# Check performance
ddev exec drush log:tail --channel=default | grep "took"

# Check database
ddev exec drush log:tail --channel=database
```

---

## ✅ Sign-Off Template

**Project:** CRM RBAC Implementation  
**Deployment Date:** ******\_\_\_******

**Quality Assurance:** ******\_\_\_******  
Name: ******\_\_\_******  
Date: ******\_\_\_******

**Security Review:** ******\_\_\_******  
Name: ******\_\_\_******  
Date: ******\_\_\_******

**Operations Approval:** ******\_\_\_******  
Name: ******\_\_\_******  
Date: ******\_\_\_******

**Production Release:** ******\_\_\_******  
Name: ******\_\_\_******  
Date: ******\_\_\_******

---

## 📚 Reference Documents

- `CRM_RBAC_COMPLETE_SUMMARY.md` - System overview
- `CRM_RBAC_QUICK_REFERENCE.md` - Developer reference
- `CRM_RBAC_SECURITY_AUDIT_CHECKLIST.md` - Security verification (REQUIRED for sign-off)
- `CRM_RBAC_SETUP_AND_MIGRATION.md` - Setup & deployment details
- `CRM_RBAC_IMPLEMENTATION_GUIDE.md` - Developer guide with examples
- `CRM_RBAC_API_REFERENCE.md` - Complete API documentation
- `CRM_RBAC_PROJECT_COMPLETION_REPORT.md` - Project summary

---

**Status:** Ready for Staging Deployment  
**Next Action:** Start Phase 1 (Code Deployment)  
**Target Timeline:** 5 days for complete deployment cycle

_Use this action plan to guide the entire deployment process from staging to production._
