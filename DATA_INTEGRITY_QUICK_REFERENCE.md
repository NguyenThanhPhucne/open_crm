# CRM Data Integrity Audit - Executive Summary & Quick Reference

**Date:** March 9, 2026  
**Severity:** 🔴 CRITICAL ISSUES FOUND  
**Action Required:** Immediate

---

## TL;DR - What's Broken?

| Issue                          | Impact                                | Fix Time | Priority  |
| ------------------------------ | ------------------------------------- | -------- | --------- |
| **Stage Format Inconsistency** | Dashboard shows wrong Won/Lost counts | 2 hours  | 🔴 NOW    |
| **Orphaned Entities**          | Deals disappear from dashboards       | 3 hours  | 🔴 NOW    |
| **No Reference Validation**    | Deleted contacts break deals          | 4 hours  | 🔴 NOW    |
| **Broken Access Control**      | Salespeople see competitors' data     | 6 hours  | 🔴 NOW    |
| **No Validation Hooks**        | Invalid data saved to database        | 5 hours  | 🔴 WEEK 1 |

---

## Critical Path to Fix (First 48 Hours)

### Day 1: Morning

1. **Backup Database** (30 min)

   ```bash
   mysqldump open_crm > /backup/open_crm_$(date +%Y%m%d_%H%M%S).sql
   ```

2. **Normalize Stage Format** (90 min)
   - Run migration script in implementation guide
   - Verify all stages converted from integer to string
   - Confirm: 0 numeric stage IDs remain

3. **Verify Statistics** (30 min)
   - Check dashboard Won count = Query count
   - Check Lost count = Query count
   - Regenerate dashboard to confirm

### Day 1: Afternoon

4. **Find Orphaned Entities** (60 min)
   - Run orphan detection script
   - Export list to CSV for review
   - Document count and IDs

5. **Create Access Control** (120 min)
   - Add node_access hook
   - Test role-based filtering
   - Verify data isolation between salespeople

### Day 2: Morning

6. **Add Validation Hooks** (90 min)
   - Add presave validation
   - Test with form submissions
   - Verify auto-assign owner logic

7. **Test & Verify** (90 min)
   - Run full data integrity checks
   - Verify dashboard stats
   - Confirm access control

---

## Key Findings Summary

### 🔴 CRITICAL ISSUES (Fix immediately)

1. **Stage ID vs String Mismatch**
   - Some code uses ID (5, 6), some uses string ('closed_won', 'closed_lost')
   - Causes dashboard counts to be unreliable
   - Some deals counted as "won" incorrectly

2. **Orphaned Deal Entities**
   - Deals can exist with NULL owner
   - Dashboard filters exclude these
   - Deals appear "lost" but don't show in reports

3. **No Validation of References**
   - Deleted contacts leave broken references
   - Dashboard silently fails on NULL entities
   - Statistics become inaccurate

4. **Sales Manager Can See All Data**
   - No team-based filtering implemented
   - Managers see competitors' contacts/deals
   - Security vulnerability

5. **Activities Without Assignments**
   - Can be created with NULL assigned_to
   - No one is responsible
   - Disappear from activity reports

---

## Impact Matrix

```
┌─────────────────────────────────────────────────────────┐
│ WHAT BREAKS IF WE DON'T FIX                             │
├─────────────────────────────────────────────────────────┤
│                                                         │
│ Dashboard         Won/Lost counts wrong                 │
│ Pipeline          Deals disappear at random              │
│ Activities        Unassigned activities become orphans   │
│ Statistics        Don't match actual data                │
│ Security          Data visible to wrong users            │
│ Reporting         Can't trust any numbers                │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Module Dependencies for Fixes

### Must Have (Currently Missing)

- ❌ Data integrity validation on save
- ❌ Pre-save hooks for required fields
- ❌ Entity reference validation
- ❌ Node access control implementation
- ❌ Orphan detection utilities

### Already Have (We Can Use)

- ✅ DataValidationService (import validation only)
- ✅ Role-based filtering in dashboard (admin only)
- ✅ Entity type manager
- ✅ Query API for counts

---

## Files to Create/Modify

### New Files to Create

```
web/modules/custom/crm/src/Service/DataIntegrityService.php
web/modules/custom/crm/src/Commands/DataIntegrityCommands.php
web/modules/custom/crm/crm.module (new hooks)
```

### Files to Modify

```
web/modules/custom/crm_dashboard/src/Controller/DashboardController.php (Line 160)
web/modules/custom/crm_kanban/src/Controller/KanbanController.php (Line 1438)
web/modules/custom/crm_edit/src/Controller/InlineEditController.php (add validation)
```

---

## Testing Commands

```bash
# After implementation, run these to verify fixes:

# 1. Check stage format
drush sql:query "SELECT DISTINCT field_stage_value FROM node__field_stage WHERE entity_bundle = 'deal';"
# Expected: qualified, proposal, negotiation, closed_won, closed_lost (all strings, no numbers)

# 2. Find orphaned deals
drush crm:find-orphans
# Expected: "No orphaned entities found!"

# 3. Check broken references
drush crm:find-broken-refs
# Expected: "No broken references found!"

# 4. Verify dashboard data
drush cache:rebuild && drush crm:verify-integrity
# Expected: All checks pass

# 5. Test access control
# Login as sales_rep, try to view competitor's contact
# Expected: Access Denied
```

---

## Metrics to Track

### Before Fix

- Orphaned deals: ? (unknown - need to audit)
- Broken references: ? (unknown - need to audit)
- Stats variance: >10% (dashboard doesn't match reality)
- Data visibility violations: HIGH RISK

### After Fix (Target)

- Orphaned deals: 0
- Broken references: 0
- Stats variance: <1%
- Data visibility violations: NONE
- Validation pass rate: 100%

---

## FAQ

**Q: How long will this take?**  
A: Phase 1 (Stage normalization): 2-3 hours  
 Phase 2 (Orphan detection): 1-2 hours  
 Phase 3 (Access control): 4-6 hours  
 Phase 4 (Validation): 3-4 hours  
 **Total: 10-15 hours (~2 days)**

**Q: Do we need to take the site down?**  
A: Yes, recommend 2-3 hour maintenance window for:

- Database backup & stage migration
- Code deployment
- Cache rebuild
- Final verification

**Q: How do we handle existing orphaned data?**  
A: Three options:

1.  Auto-fix (assign to system admin or creator)
2.  Manual review (admin reviews and fixes each)
3.  Delete (remove orphaned entities)

**Q: Will this break existing integrations?**  
A: Only if external systems depend on numeric stage IDs.
**Check:** Do any API consumers use stage_id (5,6)?
**If yes:** Add shim to convert format on API output

**Q: Can we roll back if something breaks?**  
A: Yes, restore from SQL backup. Stage normalization is reversible:

```sql
UPDATE node__field_stage SET field_stage_value = 5 WHERE field_stage_value = 'closed_won';
```

---

## Escalation Path

| Level                     | Time  | Status         |
| ------------------------- | ----- | -------------- |
| 🟢 DEV implements fixes   | Day 1 | START HERE     |
| 🟡 QA tests fixes         | Day 2 | After dev done |
| 🟠 Business review impact | Day 2 | After QA pass  |
| 🔴 Production deployment  | Day 3 | After approval |

---

## Success Criteria

After implementation:

✅ All deals have string stage values (no integers)  
✅ Zero orphaned entities found  
✅ Zero broken entity references  
✅ Dashboard stats match query counts  
✅ Sales rep can't see competitor data  
✅ Activities must have contact OR deal  
✅ Deals must have contact OR organization  
✅ All required field validations working

---

## Contact & Questions

- **Technical Details:** See DATA_INTEGRITY_AUDIT_REPORT.md
- **Implementation Steps:** See IMPLEMENTATION_GUIDE_CRITICAL_FIXES.md
- **Code Examples:** See both docs above

---

**Next Action:** Implement Phase 1 (Stage Normalization) within 24 hours  
**Review Date:** March 11, 2026 (after implementation complete)
