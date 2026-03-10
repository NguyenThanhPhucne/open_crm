# CRM Access Control Security Audit Checklist

## Pre-Deployment Checklist

Use this checklist before deploying the access control system to ensure complete coverage.

## 1. Entity Access ✓/✗

- [ ] **hook_node_access()** is implemented and enabled
  - Location: `crm.module`
  - Uses: `CRMAccessService`
  - Covers: Create, read, update, delete operations

- [ ] **hook_query_node_access_alter()** is implemented
  - Location: `crm.module`
  - Filters: Views, entity queries, API responses
  - Tested with Views

- [ ] **All CRM entity bundles are covered:**
  - [ ] Contact
  - [ ] Deal
  - [ ] Organization
  - [ ] Activity
  - [ ] (Other custom entities: ****\_****)

- [ ] **Owner fields exist and are correct:**
  - [ ] Contact: `field_owner`
  - [ ] Deal: `field_owner`
  - [ ] Organization: `field_assigned_staff`
  - [ ] Activity: `field_assigned_to`

## 2. Role Configuration ✓/✗

- [ ] **Administrator role configured:**
  - [ ] Has all CRM permissions
  - [ ] Can view: All entities
  - [ ] Can edit: All entities
  - [ ] Can delete: All entities

- [ ] **Sales Manager role configured:**
  - [ ] Can view: All team entities
  - [ ] Can edit: All team entities
  - [ ] Can delete: All team entities
  - [ ] Cannot bypass team restrictions

- [ ] **Sales Representative role configured:**
  - [ ] Can view: Own + team entities only
  - [ ] Can edit: Own + team entities only
  - [ ] Can delete: Own + team entities only

- [ ] **Anonymous role configured:**
  - [ ] Cannot access any CRM entities
  - [ ] Cannot view entity pages
  - [ ] Cannot access API endpoints

## 3. Views Integration ✓/✗

- [ ] **All Views respect access filtering:**
  - [ ] `all_contacts` - Filtered by access
  - [ ] `all_deals` - Filtered by access
  - [ ] `all_organizations` - Filtered by access
  - [ ] `all_activities` - Filtered by access
  - [ ] Custom views: **\_\_\_\_**
  - [ ] Custom views: **\_\_\_\_**

- [ ] **Admin Views exemption:**
  - [ ] Admin users see unfiltered results
  - [ ] Manager users see unfiltered results
  - [ ] Regular users see filtered results

- [ ] **Tests performed:**
  - [ ] Admin views all contacts: PASS/FAIL
  - [ ] Rep views own contacts only: PASS/FAIL
  - [ ] Anon denied access: PASS/FAIL

## 4. Controllers & API Endpoints ✓/✗

- [ ] **All controllers check access:**
  - [ ] `AIAutoCompleteController` - Access check
  - [ ] `AIGenerateContactController` - Ownership set
  - [ ] `UserProfileController` - Access to user data
  - [ ] Custom controllers: **\_\_\_\_**

- [ ] **API endpoints protected:**
  - [ ] `/api/ai/autocomplete` - Permission check
  - [ ] `/crm/ai-generate-contact` - Permission check
  - [ ] Custom endpoints: **\_\_\_\_**

- [ ] **Error responses:**
  - [ ] Returns 403 Forbidden when denied
  - [ ] Returns 401 Unauthorized when not authenticated
  - [ ] Returns 404 Not Found for inaccessible entities

## 5. Forms & Input ✓/✗

- [ ] **Form default values:**
  - [ ] New contacts default owner to current user
  - [ ] New deals default owner to current user
  - [ ] New organizations default staff to current user
  - [ ] New activities default assignee to current user

- [ ] **Form field restrictions:**
  - [ ] Sales reps cannot change owner field
  - [ ] Sales reps cannot assign to other teams
  - [ ] Only owners/managers can change status

- [ ] **Form submission validation:**
  - [ ] Cannot submit with invalid owner
  - [ ] Cannot submit with non-existent user
  - [ ] Cannot submit for another user (unless manager)

## 6. AI AutoComplete Service ✓/✗

- [ ] **Air suggestions filtered by access:**
  - [ ] Only suggests entities user can view
  - [ ] Contact suggestions respect access
  - [ ] Organization suggestions respect access
  - [ ] Deal suggestions respect access

- [ ] **AI-generated data respects ownership:**
  - [ ] New contacts owned by current user
  - [ ] Suggestions respect user's organization
  - [ ] Cross-organization references validated

## 7. Dashboard & Reporting ✓/✗

- [ ] **Dashboard widgets filtered:**
  - [ ] Recent Contacts: User's only
  - [ ] Recent Deals: User's only
  - [ ] Recent Activities: User's only
  - [ ] Pipeline chart: User's data
  - [ ] Team stats: Manager only
  - [ ] Custom widgets: **\_\_\_\_**

- [ ] **Reports respect access:**
  - [ ] Contact reports: User's only
  - [ ] Sales reports: User's only
  - [ ] Pipeline reports: User's only
  - [ ] Admin reports: All data visible

## 8. Data Export ✓/✗

- [ ] **Exports filtered by access:**
  - [ ] CSV exports: Only accessible records
  - [ ] PDF reports: Only accessible records
  - [ ] Excel files: Only accessible records
  - [ ] API exports: Only accessible data

- [ ] **No bulk download bypass:**
  - [ ] Cannot download all data
  - [ ] Cannot export unfiltered data
  - [ ] Export respects row-level access

## 9. Team-Based Access ✓/✗

- [ ] **Team field exists on users:**
  - [ ] `field_team` field created
  - [ ] All users assigned to team (or none)
  - [ ] Teams taxonomy configured

- [ ] **Team-based filtering works:**
  - [ ] Same-team users see each other's records
  - [ ] Different-team users don't see records
  - [ ] Team can be empty (individual contributors)

- [ ] **Tests:**
  - [ ] Same team sees records: PASS/FAIL
  - [ ] Different team denied: PASS/FAIL
  - [ ] Team member access: PASS/FAIL

## 10. Logging & Audit Trail ✓/✗

- [ ] **Access logging enabled:**
  - [ ] `crm_access` logger configured
  - [ ] Log channel exists
  - [ ] Logs are persistent

- [ ] **Critical actions logged:**
  - [ ] Entity view: Logged
  - [ ] Entity edit: Logged
  - [ ] Entity delete: Logged
  - [ ] Access denied: Logged
  - [ ] Permission bypass: Logged

- [ ] **Audit trail accessible:**
  - [ ] Logs viewable at `/admin/reports/dblog`
  - [ ] Logs searchable by entity type
  - [ ] Logs searchable by user

## 11. Performance & Optimization ✓/✗

- [ ] **Database indexes:**
  - [ ] `field_owner_target_id` indexed
  - [ ] `field_assigned_to_target_id` indexed
  - [ ] `field_assigned_staff_target_id` indexed
  - [ ] Query performance acceptable

- [ ] **Caching configured:**
  - [ ] Views caching enabled (output)
  - [ ] Query caching enabled
  - [ ] Page caching for authenticated users
  - [ ] Cache tags properly set

- [ ] **Performance tests:**
  - [ ] Small dataset (100 records): Fast
  - [ ] Medium dataset (10K records): Acceptable
  - [ ] Large dataset (100K records): Optimized

## 12. Security Hardening ✓/✗

- [ ] **Direct access prevention:**
  - [ ] Cannot access entity by ID directly
  - [ ] Cannot bypass Views to get data
  - [ ] Cannot use developer tools to access
  - [ ] Cannot use API to bypass access

- [ ] **Permission hierarchy:**
  - [ ] Admin > Manager > Rep > Anon
  - [ ] No sideways permission grants
  - [ ] Bypass permission restricted

- [ ] **Input validation:**
  - [ ] Entity IDs validated
  - [ ] User IDs validated
  - [ ] Filter values validated
  - [ ] No SQL injection possible

## 13. Third-party Integration ✓/✗

- [ ] **Custom modules check access:**
  - [ ] Custom controllers use service
  - [ ] Custom queries filtered
  - [ ] Custom forms validate ownership

- [ ] **RESTful API secured:**
  - [ ] REST endpoints check permission
  - [ ] REST endpoints check entity access
  - [ ] REST responses filtered

- [ ] **Mobile app integration:**
  - [ ] Mobile API endpoints filtered
  - [ ] Mobile auth respected
  - [ ] Mobile data encrypted

## 14. Testing & QA ✓/✗

### Unit Tests

- [ ] CRMAccessService tests pass
- [ ] Helper functions tested
- [ ] Owner field detection tested

### Functional Tests

- [ ] Admin access test passes
- [ ] Manager access test passes
- [ ] Rep access test passes
- [ ] Anonymous denial test passes
- [ ] Team access test passes

### Integration Tests

- [ ] Views filtering test passes
- [ ] API filtering test passes
- [ ] Dashboard filtering test passes
- [ ] Form defaults test passes

### Security Tests

- [ ] Direct access test: PASS (denied)
- [ ] Permission escalation test: PASS (denied)
- [ ] Data leakage test: PASS (no leak)
- [ ] Bypass test: PASS (cannot bypass)

## 15. Documentation ✓/✗

- [ ] **System documentation complete:**
  - [ ] `CRM_RBAC_SYSTEM_DOCUMENTATION.md`
  - [ ] `CRM_RBAC_IMPLEMENTATION_GUIDE.md`
  - [ ] API documentation
  - [ ] Security guidelines

- [ ] **Code documented:**
  - [ ] Service class documented
  - [ ] Hooks documented
  - [ ] Public methods have docblocks
  - [ ] Complex logic commented

- [ ] **User documentation:**
  - [ ] Admin guide for managing roles
  - [ ] User guide for understanding access
  - [ ] FAQ for common issues
  - [ ] Troubleshooting guide

## 16. Deployment Preparation ✓/✗

- [ ] **Pre-deployment:**
  - [ ] All tests pass
  - [ ] No open issues
  - [ ] Documentation complete
  - [ ] Code reviewed

- [ ] **Backup:**
  - [ ] Database backed up
  - [ ] Files backed up
  - [ ] Rollback plan documented

- [ ] **Communication:**
  - [ ] Users notified of changes
  - [ ] Admins trained on new system
  - [ ] Support team briefed

- [ ] **Deployment:**
  - [ ] Deploy to staging first
  - [ ] Test in staging environment
  - [ ] Deploy to production
  - [ ] Monitor for errors
  - [ ] Verify functionality

## 17. Post-Deployment Verification ✓/✗

- [ ] **System verification:**
  - [ ] All Views display correctly
  - [ ] API endpoints respond correctly
  - [ ] Forms work correctly
  - [ ] No PHP errors in logs
  - [ ] No database errors in logs

- [ ] **Access verification:**
  - [ ] Admins see all data
  - [ ] Managers see team data
  - [ ] Reps see own data
  - [ ] Anonymous denied

- [ ] **Performance verification:**
  - [ ] Page load times acceptable
  - [ ] API response time acceptable
  - [ ] Database queries optimized
  - [ ] No timeouts or errors

- [ ] **Monitoring:**
  - [ ] Monitor access logs
  - [ ] Monitor performance metrics
  - [ ] Monitor error logs
  - [ ] Monitor user reports

## Sign-Off

- [ ] **Development Lead:** ********\_******** Date: **\_\_\_**
- [ ] **QA Lead:** ********\_******** Date: **\_\_\_**
- [ ] **Security Review:** ********\_******** Date: **\_\_\_**
- [ ] **Product Owner:** ********\_******** Date: **\_\_\_**

## Notes

_Use this space for any additional notes, issues found, or action items:_

---

---

---
