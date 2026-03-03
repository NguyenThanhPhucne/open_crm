# 🛡️ TEAM PERMISSIONS - QUICK REFERENCE GUIDE

## 🎯 TỔNG QUAN

Module **crm_teams** cung cấp hệ thống phân quyền theo team cho Open CRM, cho phép:

- Sales Team A chỉ xem được khách hàng của Team A
- Sales Team B chỉ xem được khách hàng của Team B
- Manager xem được tất cả data

## 📋 TEAMS MẶC ĐỊNH

| Team ID | Team Name    | Description                      | Purpose            |
| ------- | ------------ | -------------------------------- | ------------------ |
| 42      | Sales Team A | Primary sales team               | Đội bán hàng chính |
| 43      | Sales Team B | Secondary sales team             | Đội bán hàng phụ   |
| 44      | Sales Team C | Third sales team                 | Đội bán hàng thứ 3 |
| 45      | Manager Team | Management team with full access | Đội quản lý        |

## 🔐 PERMISSIONS

### 1. Administer CRM Teams

- **Machine name**: `administer crm teams`
- **Gán cho**: Administrator
- **Chức năng**: Quản lý team assignments, truy cập /admin/crm/teams
- **Không cho phép**: Xem data của team khác (trừ khi có bypass permission)

### 2. Bypass CRM Team Access

- **Machine name**: `bypass crm team access`
- **Gán cho**: Administrator, Manager
- **Chức năng**: Bỏ qua team restrictions, xem tất cả data
- **Use case**: Manager cần xem cross-team reports

## 🎨 TEAMS MANAGEMENT UI

### URL

```
http://open-crm.ddev.site/admin/crm/teams
```

### Layout

```
┌─────────────────────────────────────────────────────┐
│  Team Management                                    │
├──────────────┬──────────────────────────────────────┤
│              │                                      │
│  Teams       │  Users                               │
│  Sidebar     │  Table                               │
│              │                                      │
│  - Team A    │  [Avatar] User   Email   Team  [▼]  │
│    👤 12     │  [Avatar] User   Email   Team  [▼]  │
│              │  [Avatar] User   Email   Team  [▼]  │
│  - Team B    │                                      │
│    👤 8      │                                      │
│              │                                      │
│  - Team C    │                                      │
│    👤 5      │                                      │
│              │                                      │
│  - Manager   │                                      │
│    👤 3      │                                      │
└──────────────┴──────────────────────────────────────┘
```

### Features

- ✅ Real-time AJAX team assignment
- ✅ Success notifications
- ✅ User counts per team
- ✅ Lucide SVG icons
- ✅ Responsive design
- ✅ Professional gradient UI

## 🔧 CÁCH ASSIGN TEAM

### Via UI (Recommended)

1. Login as admin
2. Go to `/admin/crm/teams`
3. Find user in table
4. Select team from dropdown
5. Click "Save" button
6. ✅ Success notification

### Via Drush

```bash
# Set team for user uid 3 to Sales Team A (tid 42)
ddev drush sqlq "INSERT INTO user__field_team (bundle, deleted, entity_id, revision_id, langcode, delta, field_team_target_id) VALUES ('user', 0, 3, 3, 'en', 0, 42) ON DUPLICATE KEY UPDATE field_team_target_id=42"

# Verify
ddev drush sqlq "SELECT u.name, t.name AS team FROM users_field_data u JOIN user__field_team ut ON u.uid=ut.entity_id JOIN taxonomy_term_field_data t ON ut.field_team_target_id=t.tid WHERE u.uid=3"
```

### Via PHP Script

```php
<?php
use Drupal\user\Entity\User;

$user = User::load(3); // Load user uid 3
$user->set('field_team', ['target_id' => 42]); // Sales Team A
$user->save();
echo "User assigned to Sales Team A\n";
?>
```

## 🧪 TESTING ACCESS CONTROL

### Scenario 1: Basic Team Isolation

```bash
# 1. Create 2 users
ddev drush user:create usera --password="test123" --mail="usera@example.com"
ddev drush user:create userb --password="test123" --mail="userb@example.com"

# 2. Assign teams (via UI or Drush)
# usera → Sales Team A (tid 42)
# userb → Sales Team B (tid 43)

# 3. Create test contacts
# Login as usera, create Contact "Contact A"
# Login as userb, create Contact "Contact B"

# 4. Test access
# Login as usera → Should only see "Contact A" ✅
# Login as userb → Should only see "Contact B" ✅
```

### Scenario 2: Manager Bypass

```bash
# 1. Create manager user
ddev drush user:create manager --password="test123" --mail="manager@example.com"

# 2. Assign bypass permission
ddev drush role:create crm_manager "CRM Manager"
ddev drush role:perm:add crm_manager 'bypass crm team access'
ddev drush user:role:add crm_manager manager

# 3. Assign to Manager Team (optional, for organization)
# Via UI: /admin/crm/teams, assign manager to Manager Team

# 4. Test access
# Login as manager → Should see ALL contacts ✅
```

### Scenario 3: Views Integration

```bash
# Navigate to views with team filtering:
# - /crm/my-contacts
# - /crm/my-deals
# - /crm/my-organizations

# Expected: Only see entities owned by same team members ✅
```

## 🔍 ACCESS CONTROL LOGIC

### hook_node_access()

```php
// Fires when user tries to view node
// Check:
// 1. Is node a CRM entity? (contact, deal, org, activity)
// 2. User has bypass permission? → ALLOW
// 3. User uid = 0 (anonymous)? → DENY
// 4. User is admin (uid 1)? → ALLOW
// 5. Get user's team
// 6. Get node owner's team
// 7. Teams match? → ALLOW : DENY
```

### hook_query_TAG_alter()

```php
// Fires on entity queries with tag 'node_access'
// Add:
// 1. JOIN user__field_team on node owner
// 2. WHERE field_team_target_id = current_user_team
// 3. OR current_user has bypass permission
```

### hook_views_query_alter()

```php
// Fires on CRM views (crm_contacts, crm_deals, etc.)
// Add:
// 1. JOIN user__field_team
// 2. WHERE condition for team filtering
// 3. Unless user has bypass permission
```

## 📊 VERIFICATION QUERIES

### Check Teams

```sql
SELECT tid, name, description__value
FROM taxonomy_term_field_data
WHERE vid='crm_team'
ORDER BY name;
```

Expected:

```
42  Manager Team      Management team with full access
43  Sales Team A      Primary sales team
44  Sales Team B      Secondary sales team
45  Sales Team C      Third sales team
```

### Check User Assignments

```sql
SELECT
  u.uid,
  u.name AS username,
  t.name AS team_name,
  GROUP_CONCAT(r.roles_target_id) AS roles
FROM users_field_data u
LEFT JOIN user__field_team ut ON u.uid = ut.entity_id
LEFT JOIN taxonomy_term_field_data t ON ut.field_team_target_id = t.tid
LEFT JOIN user__roles r ON u.uid = r.entity_id
WHERE u.uid > 0
GROUP BY u.uid
ORDER BY u.uid;
```

### Check Field Configuration

```bash
ddev drush config:get field.field.user.user.field_team
```

Expected:

```yaml
field_name: field_team
entity_type: user
bundle: user
field_type: entity_reference
settings:
  handler: "default:taxonomy_term"
  handler_settings:
    target_bundles:
      crm_team: crm_team
```

## 🚨 TROUBLESHOOTING

### Issue: Teams not showing in UI

**Symptom**: /admin/crm/teams shows empty teams list

**Solution**:

```bash
# Recreate teams
ddev drush scr scripts/create_teams.php

# Verify
ddev drush sqlq "SELECT name FROM taxonomy_term_field_data WHERE vid='crm_team'"
```

### Issue: Field field_team not found

**Symptom**: Error "The entity does not have a field_team field"

**Solution**:

```bash
# Recreate field
ddev drush scr scripts/create_team_field.php

# Clear cache
ddev drush cr

# Verify
ddev drush config:get field.field.user.user.field_team
```

### Issue: Access control not working

**Symptom**: Users see data from other teams

**Checklist**:

1. ✅ Module enabled? `ddev drush pm:list | grep crm_teams`
2. ✅ Cache cleared? `ddev drush cr`
3. ✅ Teams assigned? Check /admin/crm/teams
4. ✅ No bypass permission? Check /admin/people/permissions
5. ✅ Hooks registered? Check crm_teams.module hook implementations

**Debug**:

```php
// Add to crm_teams.module for debugging
function crm_teams_node_access(\Drupal\node\NodeInterface $node, $op, \Drupal\Core\Session\AccountInterface $account) {
  \Drupal::logger('crm_teams')->debug('Node access check: node @nid, op @op, user @uid', [
    '@nid' => $node->id(),
    '@op' => $op,
    '@uid' => $account->id(),
  ]);
  // ... rest of hook code
}
```

Then check logs:

```bash
ddev drush watchdog:show --type=crm_teams
```

### Issue: Manager can't see all data

**Symptom**: Manager user still sees filtered data

**Solution**:

```bash
# Add bypass permission to manager role
ddev drush role:perm:add crm_manager 'bypass crm team access'

# Or add to manager user directly (not recommended, use roles)
# Verify permissions
ddev drush role:perm:list crm_manager | grep bypass
```

### Issue: AJAX assignment not working

**Symptom**: Click Save button, no response

**Checklist**:

1. ✅ JavaScript console errors? Open browser DevTools
2. ✅ CSRF token valid? Check network tab
3. ✅ Endpoint responding? Test `/admin/crm/teams/assign` directly

**Solution**:

```bash
# Clear cache
ddev drush cr

# Check route
ddev drush route:debug crm_teams.assign_team

# Check logs
ddev drush watchdog:show --type=php --count=50
```

## 📚 CODE REFERENCE

### Key Files

```
/web/modules/custom/crm_teams/
├── crm_teams.module                 [Access control hooks]
├── crm_teams.routing.yml            [Route definitions]
├── crm_teams.permissions.yml        [Permission definitions]
├── crm_teams.links.menu.yml         [Menu links]
└── src/
    ├── Controller/
    │   └── TeamsManagementController.php  [UI + AJAX]
    └── Form/
        └── UserTeamForm.php         [Drupal form]
```

### Hook Implementations

```php
// Location: crm_teams.module

crm_teams_node_access($node, $op, $account)
  → Check team membership for node operations

crm_teams_query_node_access_alter($query)
  → Filter entity queries by team

crm_teams_views_query_alter($view, $query)
  → Apply team filter to CRM views

crm_teams_install()
  → Create vocabulary + teams + field on install
```

### Controller Methods

```php
// Location: TeamsManagementController.php

manageTeams()
  → Main UI page
  → Query teams + users
  → Render professional interface

assignTeam(Request $request)
  → AJAX endpoint
  → Update user team
  → Return JSON response

buildTeamsUI($teams_data, $users_data)
  → Build HTML/CSS/JS
  → 679 lines of professional UI
```

## 🎯 BEST PRACTICES

### 1. Always Use Roles for Permissions

❌ Bad: Assign `bypass crm team access` to individual users
✅ Good: Create `crm_manager` role with bypass permission, assign role to users

### 2. Clear Cache After Changes

```bash
ddev drush cr
```

### 3. Test in Private/Incognito Window

- Avoids cached permissions
- Ensures clean testing

### 4. Use Descriptive Team Names

✅ Good: "Sales Team A", "Marketing Team", "Support Team"
❌ Bad: "Team 1", "Group A", "Users"

### 5. Document Team Assignments

- Keep spreadsheet of user → team mappings
- Update when employees change roles
- Audit quarterly

### 6. Monitor Access Logs

```bash
# Check recent access denials
ddev drush watchdog:show --type=access_denied --count=50
```

### 7. Backup Before Bulk Changes

```bash
# Export current team assignments
ddev drush sqlq "SELECT u.name, t.name FROM users_field_data u JOIN user__field_team ut ON u.uid=ut.entity_id JOIN taxonomy_term_field_data t ON ut.field_team_target_id=t.tid" > team_backup.csv
```

## 📞 SUPPORT

### Quick Commands Cheat Sheet

```bash
# List all teams
ddev drush sqlq "SELECT name FROM taxonomy_term_field_data WHERE vid='crm_team'"

# List users and teams
ddev drush sqlq "SELECT u.name, COALESCE(t.name, 'No Team') AS team FROM users_field_data u LEFT JOIN user__field_team ut ON u.uid=ut.entity_id LEFT JOIN taxonomy_term_field_data t ON ut.field_team_target_id=t.tid WHERE u.uid>0"

# Count users per team
ddev drush sqlq "SELECT t.name, COUNT(ut.entity_id) AS user_count FROM taxonomy_term_field_data t LEFT JOIN user__field_team ut ON t.tid=ut.field_team_target_id WHERE t.vid='crm_team' GROUP BY t.tid"

# Clear all team assignments (DANGER!)
ddev drush sqlq "DELETE FROM user__field_team"

# Assign all users to Team A (bulk assign)
ddev drush sqlq "INSERT INTO user__field_team (bundle, deleted, entity_id, revision_id, langcode, delta, field_team_target_id) SELECT 'user', 0, uid, uid, 'en', 0, 42 FROM users WHERE uid > 1 ON DUPLICATE KEY UPDATE field_team_target_id=42"
```

### Common Drush Commands

```bash
# Module status
ddev drush pm:info crm_teams

# Reinstall module
ddev drush pm:uninstall crm_teams -y && ddev drush en crm_teams -y

# Export configuration
ddev drush config:export

# Check permissions
ddev drush role:perm:list administrator | grep team
```

---

**Version**: 1.0
**Last Updated**: 2024 (Teams Release)
**Module**: crm_teams
**Drupal**: 11.3.3
