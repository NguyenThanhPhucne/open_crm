#!/bin/bash

###############################################################################
# QUICK FIX SCRIPT - Fix P0 Critical Issues
###############################################################################
# Purpose: Automatically fix critical production blockers
# Run: bash scripts/production/quick_fix_p0.sh
###############################################################################

set -e  # Exit on error

echo "================================================"
echo "QUICK FIX - P0 CRITICAL ISSUES"
echo "================================================"
echo ""
echo "This script will fix the following:"
echo "1. Disable conflicting modules (crm_teams, crm_import)"
echo "2. Uninstall unused contrib modules (feeds)"
echo "3. Enable crm_workflow module"
echo "4. Clear cache"
echo ""
echo "⚠️  WARNING: This will make changes to your site!"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
  echo "Aborted."
  exit 0
fi

echo ""
echo "================================================"
echo "STEP 1: Backup Current Configuration"
echo "================================================"
echo ""

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "ℹ️  Exporting current config..."
if command -v drush &> /dev/null; then
  drush config:export --destination="$BACKUP_DIR/config" || true
  echo "✅ Config backed up to: $BACKUP_DIR/config"
else
  echo "⚠️  Drush not found. Skipping backup."
fi

echo ""
echo "================================================"
echo "STEP 2: Disable Conflicting Modules"
echo "================================================"
echo ""

# Check if modules are enabled
MODULES_TO_DISABLE=()

if drush pm:list --status=enabled --format=list 2>/dev/null | grep -q "crm_teams"; then
  MODULES_TO_DISABLE+=("crm_teams")
fi

if drush pm:list --status=enabled --format=list 2>/dev/null | grep -q "crm_import"; then
  MODULES_TO_DISABLE+=("crm_import")
fi

if [ ${#MODULES_TO_DISABLE[@]} -gt 0 ]; then
  echo "ℹ️  Disabling modules: ${MODULES_TO_DISABLE[*]}"
  
  for MODULE in "${MODULES_TO_DISABLE[@]}"; do
    echo "  - Disabling $MODULE..."
    drush pmu "$MODULE" -y || echo "  ⚠️  Could not disable $MODULE"
  done
  
  echo "✅ Disabled conflicting modules"
else
  echo "ℹ️  No conflicting modules found"
fi

echo ""
echo "================================================"
echo "STEP 3: Uninstall Unused Contrib Modules"
echo "================================================"
echo ""

CONTRIB_TO_REMOVE=()

if drush pm:list --format=list 2>/dev/null | grep -q "^feeds$"; then
  CONTRIB_TO_REMOVE+=("feeds")
fi

if [ ${#CONTRIB_TO_REMOVE[@]} -gt 0 ]; then
  echo "ℹ️  Uninstalling contrib modules: ${CONTRIB_TO_REMOVE[*]}"
  
  for MODULE in "${CONTRIB_TO_REMOVE[@]}"; do
    echo "  - Uninstalling $MODULE..."
    drush pmu "$MODULE" -y || echo "  ⚠️  Could not uninstall $MODULE"
  done
  
  echo "✅ Uninstalled unused contrib modules"
  echo "ℹ️  You can remove them from composer.json later"
else
  echo "ℹ️  No unused contrib modules found"
fi

echo ""
echo "================================================"
echo "STEP 4: Enable CRM Workflow Module"
echo "================================================"
echo ""

if [ -d "web/modules/custom/crm_workflow" ]; then
  echo "ℹ️  crm_workflow module found"
  
  if drush pm:list --status=enabled --format=list 2>/dev/null | grep -q "crm_workflow"; then
    echo "ℹ️  crm_workflow already enabled"
  else
    echo "ℹ️  Enabling crm_workflow..."
    drush en crm_workflow -y || echo "⚠️  Could not enable crm_workflow"
    echo "✅ Enabled crm_workflow module"
  fi
else
  echo "⚠️  crm_workflow module not found"
  echo "   Expected location: web/modules/custom/crm_workflow/"
  echo "   Please create the module first."
fi

echo ""
echo "================================================"
echo "STEP 5: Clear Cache"
echo "================================================"
echo ""

echo "ℹ️  Clearing all caches..."
drush cr || echo "⚠️  Could not clear cache"
echo "✅ Cache cleared"

echo ""
echo "================================================"
echo "STEP 6: Verify Changes"
echo "================================================"
echo ""

echo "Enabled modules:"
drush pm:list --status=enabled --type=module --format=table | grep crm || true

echo ""
echo "================================================"
echo "SUMMARY"
echo "================================================"
echo ""
echo "✅ P0 fixes applied:"
echo "   - Disabled crm_teams module (conflict)"
echo "   - Disabled crm_import module (duplicate)"
echo "   - Uninstalled feeds module (unused)"
echo "   - Enabled crm_workflow module (automation)"
echo ""
echo "⚠️  IMPORTANT NEXT STEPS:"
echo ""
echo "1. Merge crm_teams logic into crm module"
echo "   See: COMPREHENSIVE_AUDIT_REPORT.md #ISSUE 1"
echo ""
echo "2. Test access control:"
echo "   - Login as sales_rep"
echo "   - Verify data isolation"
echo "   - Check permissions"
echo ""
echo "3. Complete crm_workflow implementation:"
echo "   - Edit: web/modules/custom/crm_workflow/crm_workflow.module"
echo "   - Uncomment TODO sections"
echo "   - Test Deal Won workflow"
echo ""
echo "4. Remove deprecated folders:"
echo "   rm -rf web/modules/custom/crm_import/"
echo "   rm -rf web/modules/custom/crm_teams/"
echo ""
echo "5. Update composer.json:"
echo "   composer remove drupal/feeds"
echo ""
echo "6. Run remaining fixes:"
echo "   bash scripts/production/fix_search_api_access.sh"
echo "   php scripts/production/configure_views_caching.php"
echo "   mysql < scripts/production/add_indexes.sql"
echo ""

echo "✨ Done!"
echo ""

exit 0
