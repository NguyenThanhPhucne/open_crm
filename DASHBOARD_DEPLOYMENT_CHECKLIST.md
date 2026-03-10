# CRM Dashboard Refactoring - Deployment & Validation Checklist

## Pre-Deployment Checklist

### Code Review

- [ ] All PHP code follows Drupal coding standards
- [ ] CSS is organized and minified (optional)
- [ ] JavaScript is wrapped in proper scope
- [ ] No console errors or warnings
- [ ] All variables are properly declared
- [ ] Security checks: User filters, permission checks, input validation

### Testing Coverage

- [ ] Dashboard loads without errors
- [ ] Activities card displays 3-4 items (max height works)
- [ ] Metrics cards show trends (↑ with count)
- [ ] Responsive layout tested on:
  - [ ] Desktop (1920px+)
  - [ ] Laptop (1200px+)
  - [ ] Tablet (768px-1200px)
  - [ ] Mobile (320px-768px)
- [ ] Charts render correctly (Pipeline + Deal Value)
- [ ] Hover states smooth on all interactive elements
- [ ] Navigation links work
- [ ] Performance: Dashboard loads in <2 seconds

### Environment Validation

- [ ] Drupal 11 is installed and running
- [ ] Database is backed up
- [ ] All required permissions are configured
- [ ] crm_dashboard module is enabled
- [ ] Caches are cleared

---

## Deployment Steps

### Step 1: Backup Current State

```bash
# Backup database
mysqldump -u root -p drupal_db > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup code
tar -czf drupal_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/drupal
```

### Step 2: Deploy Files

```bash
# Copy modified files to production
cp web/modules/custom/crm_dashboard/src/Controller/DashboardController.php /production/path/
cp web/modules/custom/crm_dashboard/crm_dashboard.module /production/path/
cp web/modules/custom/crm_dashboard/crm_dashboard.routing.yml /production/path/

# Verify file permissions
chmod 644 /production/path/*.php
chmod 644 /production/path/*.module
chmod 644 /production/path/*.yml
```

### Step 3: Module Activation

```bash
# Via Drush
drush pm:uninstall crm_dashboard --yes
drush pm:install crm_dashboard --yes

# Via UI (if no SSH access)
# 1. Go to /admin/modules
# 2. search for "CRM Dashboard"
# 3. Uncheck and Save
# 4. Check and Save
```

### Step 4: Cache Clear

```bash
# Clear all caches
drush cc all

# Or via UI
# /admin/config/development/performance > Clear All Caches
```

### Step 5: Route Rebuild

```bash
# Rebuild routing
drush cr

# Or UI
# /admin/config/development/performance > Clear All Caches (rebuilds routes)
```

---

## Post-Deployment Validation

### User-Facing Tests

#### Test 1: Dashboard Loads Correctly

```
1. Navigate to /crm/dashboard
2. ✓ Dashboard appears within 2 seconds
3. ✓ No console errors (F12)
4. ✓ All 6 KPI cards visible
5. ✓ Charts render with data
6. ✓ Recent Activities shows items
```

#### Test 2: Activities Card Layout

```
1. Look at Recent Activities panel
2. ✓ Card height is balanced (not too tall)
3. ✓ Activity items scroll internally
4. ✓ Scrollbar appears only when needed
5. ✓ Bottom of dashboard not pushed down
6. ✓ All activities visible in list
```

#### Test 3: Visual Hierarchy - Activity Items

```
For each activity in the list:
1. ✓ Icon has color (not grayscale)
2. ✓ Title is bold and readable
3. ✓ Type badge is visible (uppercase, gray bg)
4. ✓ Contact name is blue and clickable
5. ✓ Timestamp is gray and right-aligned
6. ✓ Hover state shows subtle background
```

#### Test 4: Metrics Card Trends

```
For each KPI card:
1. ✓ Number is large and prominent
2. ✓ Label is small and gray
3. ✓ Description explains the metric
4. ✓ Trend shows ↑ or trend indicator
5. ✓ Trend number is correct (±X)
6. ✓ Trend color is appropriate (green/red)
```

#### Test 5: Charts and Data Visualization

```
1. ✓ Pipeline Stage Distribution chart shows all stages
2. ✓ Deal Value Doughnut chart shows Won/Lost/Active correctly
3. ✓ Tooltips appear on hover
4. ✓ Legend displays with data breakdown
5. ✓ Charts resize responsively
6. ✓ No console errors from Chart.js
```

#### Test 6: Responsive Design

```
Mobile (375px):
- [ ] Stats grid shows 2 columns
- [ ] Charts stack vertically
- [ ] Text remains readable
- [ ] No horizontal scrolling

Tablet (768px):
- [ ] Main content is single column
- [ ] Activities sidebar moves down
- [ ] All elements visible without scroll

Desktop (1920px):
- [ ] 2-column layout visible (charts + sidebar)
- [ ] Proper spacing and alignment
- [ ] Full width utilized efficiently
```

#### Test 7: Interactive States

```
1. ✓ Hover over KPI card → lifts up, top bar appears
2. ✓ Hover over Activity item → background changes
3. ✓ Hover over Deal item → subtle shadow
4. ✓ Hover over "View all" link → background pill
5. ✓ Click KPI card → navigates to correct page
6. ✓ Click "View all" → navigates to list view
```

#### Test 8: Real-Time Functionality

```
1. ✓ "↻ Live" indicator visible in Activities header
2. ✓ Open browser console
3. ✓ Check: window.dashboardSync exists
4. ✓ Check: Refresh happens every 30 seconds
5. ✓ Edit a deal's stage → observe dashboard updates
6. ✓ Create new activity → appears in list
```

#### Test 9: Performance Metrics

```
Dashboard Load:
- [ ] Initial paint: <1 second
- [ ] Full load: <2 seconds
- [ ] No memory leaks (15min monitoring)
- [ ] Smooth scrolling (60fps)
- [ ] CPU usage <10% while idle

Charts:
- [ ] Animation smooth (no stuttering)
- [ ] Hover interaction instant
- [ ] Zoom/pan works if enabled
```

#### Test 10: Accessibility

```
1. ✓ Tab navigation works through elements
2. ✓ Color contrast passes WCAG AA
3. ✓ Icons have aria-labels or title text
4. ✓ Try keyboard-only navigation
5. ✓ Screen reader compatible (test with NVDA)
```

---

## Real-Time Event Validation

### Test 1: Deal Stage Change Event

```bash
# Step 1: Open dashboard in one browser window
# Step 2: Open deals list in another window

# Step 3: Change a deal's stage
# Expected: Dashboard pipeline chart updates within 30 seconds

# Step 4: Check browser console
> window.dashboardSync.lastRefreshTime
```

### Test 2: Activity Creation Event

```bash
# Step 1: Open dashboard
# Step 2: Create new activity in another tab

# Step 3: Check dashboard "Recent Activities"
# Expected: New activity appears in list

# Step 4: Verify via console
> document.addEventListener('crm:activity-created', () => console.log('Event triggered'));
```

### Test 3: AJAX Endpoint

```bash
# Test the refresh endpoint
curl http://your-dashboard/crm/dashboard/refresh

# Expected response:
# {
#   "success": true,
#   "timestamp": 1234567890,
#   "message": "Dashboard data refreshed",
#   "counts": {"deals": 12, "activities": 5}
# }
```

---

## Browser Compatibility Testing

Test on following browsers:

### Desktop

- [ ] Chrome (latest) - Full support
- [ ] Firefox (latest) - Full support
- [ ] Safari (14+) - Full support
- [ ] Edge (latest) - Full support

### Mobile

- [ ] iOS Safari (14+) - Full support
- [ ] Chrome Mobile - Full support
- [ ] Samsung Internet - Full support

### Test Cases Per Browser

```
For each browser:
1. ✓ Dashboard loads without errors
2. ✓ Charts render correctly
3. ✓ Animations smooth (CSS transitions)
4. ✓ Scrolling smooth (especially activities)
5. ✓ Responsive design works
6. ✓ Form inputs functional (if any)
```

---

## Security Validation

### User Access Control

```
As Admin User:
- [ ] See all dashboard data
- [ ] No data filtering visible

As Regular User:
- [ ] See only their own data (contacts, deals, activities)
- [ ] Manager sees team data (if applicable)

As New User (no data):
- [ ] Dashboard loads without errors
- [ ] Shows "No data" states appropriately
```

### Data Integrity

```
1. ✓ No sensitive data visible in source
2. ✓ No API keys exposed
3. ✓ CSRF tokens present in forms
4. ✓ User filtering applied to all queries
5. ✓ Permissions enforced on all routes
6. ✓ No console warnings about security
```

### Performance Under Load

```
With multiple concurrent users:
1. ✓ Dashboard still loads <2s
2. ✓ No database connection errors
3. ✓ Charts render correctly
4. ✓ Memory usage stable
5. ✓ No timeout errors
```

---

## Rollback Plan

If issues are encountered:

### Quick Rollback (< 5 minutes)

```bash
# Restore from backup
mysql -u root -p drupal_db < backup_YYYYMMDD_HHMMSS.sql

# Restore code
rm -rf web/modules/custom/crm_dashboard
tar -xzf drupal_backup_YYYYMMDD_HHMMSS.tar.gz

# Clear cache
drush cc all
```

### Gradual Rollback

```bash
# Disable module without losing data
drush pm:uninstall crm_dashboard --no-cache-clear

# Revert to previous version
git checkout HEAD~1 -- web/modules/custom/crm_dashboard/

# Clear cache and test
drush cc all
```

---

## Sign-Off Checklist

### Project Manager Sign-Off

- [ ] All tests passed
- [ ] Dashboard looks professional (SaaS level)
- [ ] No breaking changes to existing functionality
- [ ] Real-time sync working as expected
- [ ] Performance acceptable
- [ ] Ready for production

### Technical Lead Sign-Off

- [ ] Code review completed
- [ ] Security validation passed
- [ ] Performance metrics acceptable
- [ ] Fallback mechanisms in place
- [ ] Documentation complete

### QA Sign-Off

- [ ] All test cases executed
- [ ] No regressions found
- [ ] Edge cases handled
- [ ] Browser compatibility confirmed
- [ ] Accessibility standards met

---

## Post-Launch Monitoring

### First 24 Hours

```
Monitor these metrics:
1. Error logs for any exceptions
2. Database performance (slow query log)
3. Server CPU/memory usage
4. User feedback (support tickets)
5. Dashboard page load times
6. Real-time sync events
```

### First Week

```
1. Check error logs daily
2. Monitor performance trends
3. Gather user feedback
4. Track any issues reported
5. Prepare hotfix if needed
```

### Ongoing

```
Monthly:
- Review performance metrics
- Check error trends
- Gather user feedback
- Plan feature improvements

Quarterly:
- Full audit of dashboard health
- Update documentation
- Plan next iteration
```

---

## Documentation Handoff

Provide these docs to team:

1. ✅ DASHBOARD_REFACTORING_SUMMARY.md - Complete changes
2. ✅ DASHBOARD_QUICK_REFERENCE.md - Quick lookup
3. ✅ DASHBOARD_DEVELOPER_GUIDE.md - Extension guide
4. ✅ PRODUCTION_READINESS.md - Deployment notes

---

## Contact & Support

For post-launch issues:

1. Check documentation first
2. Review error logs: `/var/log/drupal/`
3. Test with fresh browser (clear cache)
4. Check DashboardSync in browser console
5. Verify module is enabled: `drush pm:list | grep crm_dashboard`

---

## Success Criteria ✅

Dashboard is production-ready when:

1. ✅ All validation tests pass
2. ✅ No console errors or warnings
3. ✅ All animations smooth (60fps)
4. ✅ Page load <2 seconds
5. ✅ Real-time sync working
6. ✅ Mobile responsive
7. ✅ Accessibility standards met
8. ✅ Security validated
9. ✅ User feedback positive
10. ✅ Ready for team adoption

---

**Deployment Date:** ******\_\_\_\_******
**Deployed By:** ******\_\_\_\_******
**Signed Off By:** ******\_\_\_\_******
**Go-Live Date:** ******\_\_\_\_******
