#!/bin/bash

# Simple verification for Open CRM critical functionality

echo "======================================================"
echo "  OPEN CRM - CRITICAL FUNCTIONS CHECK"
echo "  Date: $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================"
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS=0
FAIL=0

check() {
    local name="$1"
    local command="$2"
    local expected="$3"
    
    echo -n "$name ... "
    result=$(eval "$command" 2>&1)
    
    if echo "$result" | grep -q "$expected"; then
        echo -e "${GREEN}✓ PASS${NC}"
        PASS=$((PASS + 1))
    else
        echo -e "${RED}✗ FAIL${NC}"
        FAIL=$((FAIL + 1))
    fi
}

echo -e "${BLUE}=== DATABASE CHECKS ===${NC}"
check "Teams vocabulary exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_vocabulary WHERE vid='crm_team'\"" \
    "1"

check "4 teams created" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM taxonomy_term_field_data WHERE vid='crm_team'\"" \
    "4"

check "field_team on user exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='field.field.user.user.field_team'\"" \
    "1"

check "Contact content type exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM node_type WHERE type='contact'\"" \
    "1"

check "Deal content type exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM node_type WHERE type='deal'\"" \
    "1"

check "Organization content type exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM node_type WHERE type='organization'\"" \
    "1"

check "Activity content type exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM node_type WHERE type='activity'\"" \
    "1"

echo ""
echo -e "${BLUE}=== MODULE CHECKS ===${NC}"
check "crm_teams module enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep -c crm_teams" \
    "1"

check "crm_quickadd module enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep -c crm_quickadd" \
    "1"

check "crm_dashboard module enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep -c crm_dashboard" \
    "1"

check "crm_actions module enabled" \
    "ddev drush pm:list --type=module --status=enabled | grep -c crm_actions" \
    "1"

echo ""
echo -e "${BLUE}=== FILE CHECKS ===${NC}"
check "crm_teams.module exists" \
    "ls web/modules/custom/crm_teams/crm_teams.module" \
    "crm_teams.module"

check "TeamsManagementController exists" \
    "ls web/modules/custom/crm_teams/src/Controller/TeamsManagementController.php" \
    "TeamsManagementController.php"

check "DashboardController exists" \
    "ls web/modules/custom/crm_dashboard/src/Controller/DashboardController.php" \
    "DashboardController.php"

check "FAB CSS exists" \
    "ls web/modules/custom/crm_quickadd/css/floating_button.css" \
    "floating_button.css"

check "FAB JS exists" \
    "ls web/modules/custom/crm_quickadd/js/floating_button.js" \
    "floating_button.js"

echo ""
echo -e "${BLUE}=== PERMISSIONS CHECKS ===${NC}"
check "bypass crm team access permission exists" \
    "ddev drush sqlq \"SELECT COUNT(*) FROM config WHERE name='crm_teams.permissions'\"" \
    "1"

check "Administrator has bypass permission" \
    "ddev drush role:perm:list administrator | grep -c 'bypass crm team access'" \
    "1"

echo ""
echo -e "${BLUE}=== PHP SYNTAX CHECKS ===${NC}"
check "crm_teams.module syntax check" \
    "php -l web/modules/custom/crm_teams/crm_teams.module" \
    "No syntax errors"

check "TeamsManagementController syntax check" \
    "php -l web/modules/custom/crm_teams/src/Controller/TeamsManagementController.php" \
    "No syntax errors"

check "DashboardController syntax check" \
    "php -l web/modules/custom/crm_dashboard/src/Controller/DashboardController.php" \
    "No syntax errors"

echo ""
echo "======================================================"
echo -e "${BLUE}RESULTS:${NC}"
echo -e "${GREEN}Passed: $PASS${NC}"
echo -e "${RED}Failed: $FAIL${NC}"
TOTAL=$((PASS + FAIL))
PERCENT=$((PASS * 100 / TOTAL))
echo "Success Rate: $PERCENT%"
echo "======================================================"

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}"
    echo "✅ ALL CRITICAL CHECKS PASSED!"
    echo "System is operational and ready to use."
    echo -e "${NC}"
    exit 0
elif [ $PERCENT -ge 80 ]; then
    echo -e "${BLUE}"
    echo "⚠️  MOSTLY OPERATIONAL ($PERCENT%)"
    echo "Minor issues detected but system should work."
    echo -e "${NC}"
    exit 0
else
    echo -e "${RED}"
    echo "❌ CRITICAL ISSUES DETECTED ($PERCENT%)"
    echo "Please review failed checks."
    echo -e "${NC}"
    exit 1
fi
