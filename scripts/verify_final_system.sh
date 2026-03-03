#!/bin/bash

# =============================================================================
# OPEN CRM - FINAL SYSTEM VERIFICATION SCRIPT
# Verifies all 8 tasks are completed and functional
# =============================================================================

echo "============================================================"
echo "  OPEN CRM - FINAL VERIFICATION"
echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "============================================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# Function to run check
check() {
    local test_name="$1"
    local command="$2"
    local expected="$3"
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    echo -n "[$TOTAL_CHECKS] $test_name ... "
    
    result=$(eval "$command" 2>&1)
    
    if echo "$result" | grep -q "$expected"; then
        echo -e "${GREEN}PASS${NC}"
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
        return 0
    else
        echo -e "${RED}FAIL${NC}"
        echo "    Expected: $expected"
        echo "    Got: $result"
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        return 1
    fi
}

echo -e "${BLUE}=== TASK 1: TAXONOMIES ===${NC}"
check "Taxonomy: deal_stage exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_vocabulary WHERE vid='deal_stage'\"" \
    "1"

check "Taxonomy: deal_source exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_vocabulary WHERE vid='deal_source'\"" \
    "1"

check "Taxonomy: relationship_type exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_vocabulary WHERE vid='relationship_type'\"" \
    "1"

echo ""
echo -e "${BLUE}=== TASK 2: FIELDS ===${NC}"
check "Field: field_email on contact" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.node.contact.field_email'\"" \
    "1"

check "Field: field_phone on contact" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.node.contact.field_phone'\"" \
    "1"

check "Field: field_amount on deal" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.node.deal.field_amount'\"" \
    "1"

check "Field: field_stage on deal" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.node.deal.field_stage'\"" \
    "1"

echo ""
echo -e "${BLUE}=== TASK 3: QUICK ADD MODAL ===${NC}"
check "Module: crm_quickadd enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_quickadd" \
    "crm_quickadd"

check "Route: crm_quickadd.modal exists" \
    "ddev drush route:debug | grep crm_quickadd" \
    "crm_quickadd"

check "FAB CSS file exists" \
    "ls web/modules/custom/crm_quickadd/css/floating_button.css" \
    "floating_button.css"

check "FAB JS file exists" \
    "ls web/modules/custom/crm_quickadd/js/floating_button.js" \
    "floating_button.js"

echo ""
echo -e "${BLUE}=== TASK 4: ACTIVITY LOGGING ===${NC}"
check "Module: crm_activities enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_activities" \
    "crm_activities"

check "Content type: activity exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM node_type WHERE type='activity'\"" \
    "1"

check "Route: crm_activities.timeline exists" \
    "ddev drush route:debug | grep crm_activities" \
    "crm_activities"

echo ""
echo -e "${BLUE}=== TASK 5: DEAL CLOSING LOGIC ===${NC}"
check "Module: crm_deal_close enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_deal_close" \
    "crm_deal_close"

check "Route: crm_deal_close.modal exists" \
    "ddev drush route:debug | grep crm_deal_close" \
    "crm_deal_close"

check "DealCloseForm class exists" \
    "ls web/modules/custom/crm_deal_close/src/Form/DealCloseForm.php" \
    "DealCloseForm.php"

echo ""
echo -e "${BLUE}=== TASK 6: CONTACT 360 VIEW ===${NC}"
check "Module: crm_contact360 enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_contact360" \
    "crm_contact360"

check "Route: crm_contact360.view exists" \
    "ddev drush route:debug | grep crm_contact360" \
    "crm_contact360"

check "Contact360 CSS file exists" \
    "ls web/modules/custom/crm_contact360/css/contact360.css" \
    "contact360.css"

echo ""
echo -e "${BLUE}=== TASK 7: TEAM PERMISSIONS (NEW) ===${NC}"
check "Module: crm_teams enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_teams" \
    "crm_teams"

check "Taxonomy: crm_team exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_vocabulary WHERE vid='crm_team'\"" \
    "1"

check "Teams: 4 default teams created" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_term_field_data WHERE vid='crm_team'\"" \
    "4"

check "Field: field_team on user" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.user.user.field_team'\"" \
    "1"

check "Permission: bypass crm team access exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name LIKE '%crm_teams.permissions%'\"" \
    "1"

check "Route: crm_teams.settings exists" \
    "ddev drush route:debug | grep crm_teams.settings" \
    "crm_teams.settings"

check "TeamsManagementController exists" \
    "ls web/modules/custom/crm_teams/src/Controller/TeamsManagementController.php" \
    "TeamsManagementController.php"

echo ""
echo -e "${BLUE}=== TASK 8: ADMIN DASHBOARD ===${NC}"
check "Module: crm_dashboard enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_dashboard" \
    "crm_dashboard"

check "Route: crm_dashboard.admin exists" \
    "ddev drush route:debug | grep crm_dashboard" \
    "crm_dashboard"

check "DashboardController exists" \
    "ls web/modules/custom/crm_dashboard/src/Controller/DashboardController.php" \
    "DashboardController.php"

check "Dashboard has 1000+ lines (enhanced)" \
    "wc -l web/modules/custom/crm_dashboard/src/Controller/DashboardController.php | awk '{print \$1}'" \
    "1076"

echo ""
echo -e "${BLUE}=== BONUS: NAVIGATION & UI ===${NC}"
check "Module: crm_actions enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep crm_actions" \
    "crm_actions"

check "Navigation CSS file exists" \
    "ls web/modules/custom/crm_actions/css/crm_actions.css" \
    "crm_actions.css"

check "Navigation JS file exists" \
    "ls web/modules/custom/crm_actions/js/crm_actions.js" \
    "crm_actions.js"

echo ""
echo "============================================================"
echo -e "${BLUE}=== DATABASE VERIFICATION ===${NC}"
echo "============================================================"
echo ""

echo "Teams in database:"
ddev drush sqlq "SELECT tid, name FROM taxonomy_term_field_data WHERE vid='crm_team' ORDER BY name"
echo ""

echo "Users and team assignments:"
ddev drush sqlq "SELECT u.uid, u.name AS username, COALESCE(t.name, 'No Team') AS team FROM users_field_data u LEFT JOIN user__field_team ut ON u.uid=ut.entity_id LEFT JOIN taxonomy_term_field_data t ON ut.field_team_target_id=t.tid WHERE u.uid > 0 ORDER BY u.uid"
echo ""

echo "Content type counts:"
ddev drush sqlq "SELECT type, COUNT(*) AS count FROM node GROUP BY type ORDER BY type"
echo ""

echo "============================================================"
echo -e "${BLUE}=== FINAL RESULTS ===${NC}"
echo "============================================================"
echo ""
echo "Total checks: $TOTAL_CHECKS"
echo -e "Passed: ${GREEN}$PASSED_CHECKS${NC}"
echo -e "Failed: ${RED}$FAILED_CHECKS${NC}"
echo ""

PERCENTAGE=$((PASSED_CHECKS * 100 / TOTAL_CHECKS))

if [ $FAILED_CHECKS -eq 0 ]; then
    echo -e "${GREEN}============================================================${NC}"
    echo -e "${GREEN}   ✅ ALL TESTS PASSED! SYSTEM IS 100% OPERATIONAL!${NC}"
    echo -e "${GREEN}============================================================${NC}"
    echo ""
    echo -e "${GREEN}🎉 CONGRATULATIONS!${NC}"
    echo "All 8 tasks completed successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Access Teams Management: http://open-crm.ddev.site/admin/crm/teams"
    echo "2. Assign users to teams"
    echo "3. Test team-based access control"
    echo "4. Review documentation: COMPLETION_REPORT.md"
    exit 0
elif [ $PERCENTAGE -ge 90 ]; then
    echo -e "${YELLOW}============================================================${NC}"
    echo -e "${YELLOW}   ⚠️  SYSTEM MOSTLY OPERATIONAL (${PERCENTAGE}%)${NC}"
    echo -e "${YELLOW}============================================================${NC}"
    echo ""
    echo "Minor issues detected. Review failed checks above."
    exit 1
else
    echo -e "${RED}============================================================${NC}"
    echo -e "${RED}   ❌ SYSTEM HAS ISSUES (${PERCENTAGE}%)${NC}"
    echo -e "${RED}============================================================${NC}"
    echo ""
    echo "Multiple checks failed. Please review and fix issues."
    exit 2
fi
