# 🚀 Open CRM Deployment & Testing Guide

**Date**: March 26, 2026  
**Version**: 1.0 - Production Ready  
**Prepared For**: Deployment to https://phucnt-open-crm.training.weebpal.com/

---

## 📋 Pre-Deployment Checklist

### Code Review

- [x] All 7 bugs fixed and tested locally
- [x] No breaking changes to existing functionality
- [x] All changes backward compatible
- [x] Code follows Drupal standards
- [x] Error handling improved
- [x] Security vulnerabilities fixed

### Files Modified Summary

```
5 files changed:
1. web/modules/custom/crm_workflow/crm_workflow.module
2. web/modules/custom/crm_data_quality/crm_data_quality.module
3. web/modules/custom/crm_register/src/Form/CrmRegisterForm.php
4. web/modules/custom/crm/src/Service/CRMAccessService.php
5. web/modules/custom/crm_teams/src/Form/UserTeamForm.php

Documentation Added:
- BUG_FIXES_APPLIED.md (comprehensive bug report)
- IMPLEMENTATION_ROADMAP.md (feature roadmap)
- DEPLOYMENT_GUIDE.md (this file)
```

---

## 🔄 Deployment Steps

### Step 1: Backup Current State

```bash
# Create backup directory with timestamp
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup database
mysqldump -u drupal_user -p drupal_db > "$BACKUP_DIR/database.sql"

# Backup configuration
drush config:export --destination="$BACKUP_DIR/config"

# Backup custom files
cp -r web/modules/custom "$BACKUP_DIR/modules_backup"
```

### Step 2: Apply Code Changes

```bash
# Navigate to project root
cd /path/to/open_crm

# Pull latest changes (if using git)
git pull origin main

# OR manually copy fixed files to appropriate locations:
cp web/modules/custom/crm_workflow/crm_workflow.module \
   /var/www/html/open_crm/web/modules/custom/crm_workflow/

# ... (repeat for all 5 modified files)
```

### Step 3: Clear Caches

```bash
# Clear all Drupal caches
drush cache:rebuild  # or: drush cr

# Rebuild search indexes
drush search-api:index

# Clear browser caches
# Instruct users to hard-refresh: Ctrl+Shift+R (Chrome/Firefox) or Cmd+Shift+R (Mac)
```

### Step 4: Verify Deployment

```bash
# Check database status
drush status

# Verify modules are enabled
drush pm:list --status=enabled | grep crm

# Run automated tests
drush test-run CRMDataQualityTest
drush test-run CrmWorkflowTest
```

### Step 5: Monitor Application

```bash
# Watch error logs in real-time
tail -f log/drupal.log

# Check for warnings
grep -i warning /var/www/html/open_crm/log/drupal.log | tail -20

# Monitor performance
watch -n 5 'drush pm:list | grep crm'
```

---

## 🧪 Testing Procedures

### TEST 1: Registration Form Validation

```
Steps:
1. Go to https://phucnt-open-crm.training.weebpal.com/register
2. Try to register with invalid username:
   - Username: "ab" (too short)
   - Expected Error: "Username must be at least 3 characters and contain only letters, numbers, dots, and underscores."
3. Try to register with taken email
   - Expected Error: "This email address is already registered..."
4. Try to register with invalid email
   - Expected Error: "Please enter a valid email address..."
5. Try to register with password < 5 chars
   - Expected Error: "Password must be at least 5 characters long."
6. Register successfully with valid data
   - Expected: Auto-login and redirect to dashboard
```

**Pass Criteria**: All validations work as described ✅

---

### TEST 2: Deal Won Workflow

```
Steps:
1. Login as sales_rep user
2. Create a new deal:
   - Title: "Test Deal"
   - Amount: 50000
   - Stage: "Negotiation"
3. Save successfully
4. Edit deal and change stage to "Won"
5. Try to save WITHOUT uploading contract file
   - Expected Error/Warning: Shows validation message from _crm_workflow_validate_deal_won()
6. Upload contract file
7. Save again
   - Expected: Deal saved successfully
   - Expected: Email notification sent to manager
   - Check logs: drush logs should show email was sent
```

**Pass Criteria**:

- Contract file validation works ✅
- Email notification sent ✅
- Deal saved successfully ✅

---

### TEST 3: Email Uniqueness (Contact & Organization)

```
Steps:
1. Create a Contact with email: "john@company.com"
2. Try to create another Contact with same email
   - Expected Error: "This email is already in use by another record."
3. Try to create Organization with same email
   - Expected Error: "This email is already in use by another record."
4. Edit first contact and change email to "john2@company.com"
   - Expected: Saves successfully (no conflict after change)
5. Try to create organization with "john2@company.com"
   - Expected Error: Email conflict
```

**Pass Criteria**: Email uniqueness enforced across both contact and organization ✅

---

### TEST 4: Organization Phone Requirement

```
Steps:
1. Go to add new organization form
2. Fill in:
   - Name: "Test Corp"
   - Email: "contact@testcorp.com"
3. Try to save WITHOUT phone
   - Expected Error: "Phone is required"
4. Add phone: "+84912345678"
5. Save successfully
   - Expected: Organization created
```

**Pass Criteria**: Phone is required for organizations ✅

---

### TEST 5: Access Control (Sales Rep Isolation)

```
Setup:
- User A: sales_rep, Team: Sales Team 1
- User B: sales_rep, Team: Sales Team 2

Steps:
1. Login as User A
2. Create contact "Contact A"
3. Logout
4. Login as User B
5. Go to /crm/my-contacts
   - Expected: "Contact A" NOT visible
6. Go to /crm/all-contacts
   - Expected: "Contact A" NOT visible
   - Expected: Access denied or empty list
7. Try to access directly: /node/[contact-a-id]
   - Expected: Access denied

Reverse test:
1. Login as admin
2. Go to /crm/all-contacts
   - Expected: ALL contacts visible (both User A and B's)
```

**Pass Criteria**: Data isolation works correctly ✅

---

### TEST 6: Error Message Security

```
Steps:
1. Try to register with database error simulation
2. Check displayed message
   - Expected: Generic message like "An error occurred. Please try again later."
   - NOT Expected: Raw database error or stack trace
3. Check /admin/reports/dblog
   - Expected: Detailed error logged server-side for debugging
   - Expected: Admin can see full error details
```

**Pass Criteria**: Errors hidden from users, logged server-side ✅

---

### TEST 7: Team Assignment Error Handling

```
Steps:
1. Go to /admin/crm/teams
2. Try to assign user to team via API call (use curl/postman)
3. Simulate error (e.g., invalid team ID)
4. Check response message
   - Expected: "An error occurred while assigning the team..."
   - NOT Expected: Database error details
5. Check logs
   - Expected: /admin/reports/dblog shows full error
```

**Pass Criteria**: Error messages are secure ✅

---

## 📊 Post-Deployment Testing

### Automated Test Suite

```bash
# Run full test suite
drush test-run

# Run specific module tests
drush test-run --module=crm
drush test-run --module=crm_data_quality
drush test-run --module=crm_workflow
drush test-run --module=crm_teams
```

### Performance Testing

```bash
# Measure page load times
ab -n 100 -c 10 https://phucnt-open-crm.training.weebpal.com/

# Check database query log
mysql -u drupal_user -p drupal_db -e "
  SET GLOBAL slow_query_log = 'ON';
  SET GLOBAL long_query_time = 1;
"

# Watch for slow queries
tail -f /var/log/mysql/slow-query.log
```

### User Acceptance Testing

```
Assign to: Team leads and early adopters

Test Scenarios:
1. Sales Rep Workflow
   - Create contact
   - Add activity
   - Create deal
   - Move through pipeline
   - Mark as won (test new validation)

2. Manager Workflow
   - View team dashboards
   - View all team deals
   - Receive deal won notifications

3. Data Quality
   - Try to duplicate email
   - Try to create contact without phone
   - Merge duplicate records (when available)

4. Search & Filter
   - Search for contact
   - Filter by deal stage
   - Find all activities

Feedback Collection:
- Ease of use
- Performance issues (page load, search)
- Missing features
- Data accuracy
```

---

## 🛑 Rollback Plan

If critical issues occur, rollback is simple:

### Immediate Rollback (< 5 minutes)

```bash
# Restore from backup
drush config:import --source="$BACKUP_DIR/config"

# OR restore from git
git checkout HEAD~1 web/modules/custom/

# Clear caches
drush cr
```

### Database Rollback

```bash
# If database changes were made (they weren't in this update)
mysql -u drupal_user -p drupal_db < "$BACKUP_DIR/database.sql"
```

### Full Site Restoration

```bash
# Use your hosting provider's backup/restore feature
# Or restore from backup directory created in Step 1
```

---

## 📈 Monitoring After Deployment

### Daily Checks (First Week)

```
✓ Check error logs: /admin/reports/dblog
✓ Verify email notifications are working
✓ Check database performance
✓ Monitor user feedback
✓ Test key workflows (deal creation, registration)
```

### Weekly Checks

```
✓ Review performance metrics
✓ Check search index status
✓ Verify access control (audit sample data access)
✓ Review user adoption metrics
✓ Check backup integrity
```

### Monthly Checks

```
✓ Security audit
✓ Database optimization
✓ Update dependencies
✓ Review system logs
✓ Capacity planning
```

---

## 🔍 Known Limitations & Workarounds

### Limitation 1: Deal Won Email Notifications

**Issue**: Emails may not send if mail system not configured  
**Workaround**: Configure SMTP in settings.php or use Drupal Mail System module

### Limitation 2: Phone Validation

**Issue**: Phone validator service must be enabled  
**Workaround**: Check crm_data_quality service configuration

### Limitation 3: Search Index Updates

**Issue**: Search may have stale data for a few minutes  
**Workaround**: Run `drush search-api:index` to force refresh

---

## 📞 Support & Escalation

### Level 1: User Support

- Verify steps are correct
- Check user permissions
- Clear browser cache
- Try different browser

### Level 2: Technical Support

- Check application logs
- Review database logs
- Check system resources
- Test in different environment

### Level 3: Developer

- Code review of related changes
- Database analysis
- Performance profiling
- Feature implementation

---

## ✅ Sign-Off Checklist

Before considering deployment complete:

- [ ] All 7 bugs fixed and verified
- [ ] Tests 1-7 passed successfully
- [ ] Performance baseline established
- [ ] User acceptance testing complete
- [ ] Documentation updated
- [ ] Training materials prepared
- [ ] Support team briefed
- [ ] Monitoring dashboards set up
- [ ] Backup verified and tested
- [ ] Rollback procedure documented and tested

---

## 📝 Post-Deployment Notes

### Documentation

- Updated README.md with new features
- Updated admin documentation
- Created user guides for new features

### Training

- Prepared user training materials
- Scheduled training sessions
- Created video tutorials

### Support

- Set up help desk tickets
- Created FAQ document
- Assigned support staff

---

**Deployment Status**: READY FOR PRODUCTION ✅  
**Prepared By**: Development Team  
**Date**: 2026-03-26  
**Approval Required**: Project Manager, QA Lead, Business Owner

---

## 🎯 Success Metrics (Post-Deployment)

Track these metrics for 30 days:

- System uptime: Target 99.5%+
- Average response time: Target < 500ms
- Error rate: Target < 0.1%
- User adoption: Track % of users using new features
- User satisfaction: Target 4.5/5.0 stars
- Support tickets: Track volume and resolution time

---

**Document Complete**  
Ready for team review and deployment
