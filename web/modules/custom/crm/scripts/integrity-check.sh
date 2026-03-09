#!/bin/bash

# CRM Integrity Verification Script
# This script runs all integrity checks and generates a report

echo "======================================"
echo "CRM Integrity Verification Report"
echo "Generated: $(date)"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Drupal is accessible
echo "[1/6] Checking Drupal installation..."
if ddev drush status > /dev/null 2>&1; then
  echo -e "${GREEN}✓${NC} Drupal is accessible"
else
  echo -e "${RED}✗${NC} Cannot access Drupal"
  exit 1
fi

# Clear cache
echo "[2/6] Clearing cache..."
ddev drush cache:rebuild > /dev/null 2>&1
echo -e "${GREEN}✓${NC} Cache cleared"

# Run orphan detection
echo "[3/6] Checking for orphaned entities..."
ORPHANS=$(ddev drush crm:find-orphans 2>&1)
if echo "$ORPHANS" | grep -q "orphaned_deals_without_owner\|orphaned_activities"; then
  echo -e "${YELLOW}⚠${NC} Orphaned entities found"
  echo "$ORPHANS" | grep -E "orphaned_|Found" | head -5
else
  echo -e "${GREEN}✓${NC} No orphaned entities"
fi

# Check for broken references
echo "[4/6] Checking for broken references..."
BROKEN=$(ddev drush crm:find-broken-refs 2>&1)
if echo "$BROKEN" | grep -q "broken\|found"; then
  echo -e "${YELLOW}⚠${NC} Broken references found"
  echo "$BROKEN" | grep -E "broken\|Found" | head -5
else
  echo -e "${GREEN}✓${NC} No broken references"
fi

# Validate stage formats
echo "[5/6] Validating stage formats..."
STAGES=$(ddev drush crm:validate-stages 2>&1)
if echo "$STAGES" | grep -q "found\|invalid"; then
  echo -e "${YELLOW}⚠${NC} Invalid stage values found"
  echo "$STAGES" | head -5
else
  echo -e "${GREEN}✓${NC} All stage values valid"
fi

# Verify dashboard statistics
echo "[6/6] Verifying dashboard statistics..."
STATS=$(ddev drush crm:verify-stats 2>&1)
if echo "$STATS" | grep -q "verified"; then
  echo -e "${GREEN}✓${NC} Statistics verified"
  echo "$STATS" | grep -E "Won|Lost|Total" | head -3
else
  echo -e "${RED}✗${NC} Could not verify statistics"
fi

echo ""
echo "======================================"
echo "Integrity Check Complete"
echo "======================================"
