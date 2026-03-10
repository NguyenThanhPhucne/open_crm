#!/bin/bash

###############################################################################
# FIX SEARCH API ACCESS CONTROL
###############################################################################
# Purpose: Enable content_access processor for Search API indexes
# Impact: Ensure sales reps can only search their own data
# Usage: bash scripts/production/fix_search_api_access.sh
###############################################################################

set -e  # Exit on error

echo "================================================"
echo "FIX SEARCH API ACCESS CONTROL"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
  echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
  echo -e "${RED}❌ $1${NC}"
}

print_warning() {
  echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
  echo "ℹ️  $1"
}

# Check if ddev is available, otherwise use drush directly
if command -v ddev &> /dev/null; then
  DRUSH="ddev drush"
  print_info "Using ddev drush"
elif command -v drush &> /dev/null; then
  DRUSH="drush"
  print_info "Using drush"
else
  print_error "Neither ddev nor drush found. Please install one of them."
  exit 1
fi

print_info "Checking existing Search API indexes..."
INDEXES=$($DRUSH search-api:list --format=json 2>/dev/null || echo "[]")

if [ "$INDEXES" = "[]" ]; then
  print_warning "No Search API indexes found. Have you created them yet?"
  exit 0
fi

print_success "Found Search API indexes"
echo ""

# List of CRM indexes to fix
declare -a CRM_INDEXES=(
  "crm_contacts_index"
  "crm_deals_index"
  "crm_organizations_index"
)

FIXED_COUNT=0
ERROR_COUNT=0

for INDEX_ID in "${CRM_INDEXES[@]}"; do
  echo "----------------------------------------"
  echo "Processing index: $INDEX_ID"
  echo "----------------------------------------"
  
  # Check if index exists
  if ! $DRUSH config:get "search_api.index.$INDEX_ID" &>/dev/null; then
    print_warning "Index not found: $INDEX_ID (skipping)"
    echo ""
    continue
  fi
  
  # Get current processor settings
  CURRENT_PROCESSORS=$($DRUSH config:get "search_api.index.$INDEX_ID" processor_settings 2>/dev/null || echo "")
  
  if [[ $CURRENT_PROCESSORS == *"content_access"* ]]; then
    print_info "content_access processor already configured for $INDEX_ID"
  else
    print_info "Enabling content_access processor..."
    
    # Enable content_access processor
    if $DRUSH config:set "search_api.index.$INDEX_ID" \
      processor_settings.content_access.weights.preprocess_query -30 -y &>/dev/null; then
      
      print_success "Enabled content_access for $INDEX_ID"
      ((FIXED_COUNT++))
      
      # Mark index for reindexing
      print_info "Marking index for rebuild..."
      $DRUSH search-api:rebuild-tracker "$INDEX_ID" &>/dev/null || true
      
    else
      print_error "Failed to enable content_access for $INDEX_ID"
      ((ERROR_COUNT++))
    fi
  fi
  
  echo ""
done

echo "================================================"
echo "REINDEXING"
echo "================================================"
echo ""

if [ $FIXED_COUNT -gt 0 ]; then
  print_info "Reindexing updated indexes..."
  
  for INDEX_ID in "${CRM_INDEXES[@]}"; do
    if $DRUSH config:get "search_api.index.$INDEX_ID" &>/dev/null; then
      print_info "Indexing: $INDEX_ID"
      
      # Index with limit to avoid timeout
      if $DRUSH search-api:index "$INDEX_ID" --limit=500 &>/dev/null; then
        print_success "Indexed $INDEX_ID (batch)"
      else
        print_warning "Indexing $INDEX_ID may need to continue in background"
      fi
    fi
  done
  
  echo ""
  print_info "To index remaining items, run:"
  echo "  $DRUSH search-api:index --batch"
fi

echo ""
echo "================================================"
echo "SUMMARY"
echo "================================================"
echo ""
print_success "Fixed: $FIXED_COUNT indexes"

if [ $ERROR_COUNT -gt 0 ]; then
  print_error "Errors: $ERROR_COUNT indexes"
fi

echo ""
echo "================================================"
echo "VERIFICATION"
echo "================================================"
echo ""
echo "To verify access control is working:"
echo ""
echo "1. Login as sales_rep (not admin)"
echo "2. Go to /search (or wherever search is)"
echo "3. Search for a contact owned by ANOTHER sales rep"
echo "4. ✅ Should NOT appear in results"
echo "5. Search for your OWN contact"
echo "6. ✅ Should appear in results"
echo ""

if [ $FIXED_COUNT -gt 0 ]; then
  print_success "Access control has been enabled!"
  print_info "Run '$DRUSH search-api:index' regularly to keep index updated"
else
  print_info "No changes were needed"
fi

echo ""
echo "================================================"
echo "OPTIONAL: Advanced Configuration"
echo "================================================"
echo ""
echo "For better performance, also enable these processors:"
echo ""
echo "1. HTML Filter (strip tags)"
echo "   $DRUSH config:set search_api.index.crm_contacts_index \\"
echo "     processor_settings.html_filter.weights.preprocess_index -10 -y"
echo ""
echo "2. Ignore Case (case-insensitive search)"
echo "   $DRUSH config:set search_api.index.crm_contacts_index \\"
echo "     processor_settings.ignorecase.weights.preprocess_index 0 -y"
echo ""
echo "3. Tokenizer (better word matching)"
echo "   $DRUSH config:set search_api.index.crm_contacts_index \\"
echo "     processor_settings.tokenizer.weights.preprocess_index 20 -y"
echo ""

echo "✨ Done!"
echo ""

exit $ERROR_COUNT
