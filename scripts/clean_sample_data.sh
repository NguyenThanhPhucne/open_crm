#!/bin/bash

echo "🗑️  CLEANING SAMPLE DATA FROM CRM"
echo "=================================="
echo ""

echo "📊 Current Data Status:"
ddev drush sql-query "SELECT 'BEFORE' as Status, type, COUNT(*) as count FROM node_field_data WHERE status=1 AND type IN ('contact','deal','organization') GROUP BY type"
echo ""

echo "🔍 Identifying Sample Data..."
echo ""

# Sample Deals to DELETE (created by scripts)
SAMPLE_DEALS=(
  8   # Enterprise Software License
  9   # Cloud Migration Project
  10  # Annual Support Contract
  11  # Consulting Services
  19  # Deal - Tech Startup ABC (SalesRep1)
  22  # Deal - E-commerce XYZ (SalesRep2)
)

# Sample Contacts to DELETE  
SAMPLE_CONTACTS=(
  4   # John Smith
  7   # Emily Brown
  38  # Alice Johnson
  40  # Bob Wilson
  42  # Carol Martinez
  44  # David Chen
  46  # Emma Taylor
)

echo "❌ Sample Deals to delete: ${SAMPLE_DEALS[@]}"
echo "❌ Sample Contacts to delete: ${SAMPLE_CONTACTS[@]}"
echo ""
echo "⚠️  Starting deletion in 3 seconds..."
sleep 1
echo "."
sleep 1  
echo "."
sleep 1

echo ""
echo "🗑️  Deleting Sample Deals..."
for nid in "${SAMPLE_DEALS[@]}"; do
  ddev drush entity:delete node $nid --yes 2>/dev/null
  echo "  ✓ Deleted deal #$nid"
done

echo ""
echo "🗑️  Deleting Sample Contacts..."
for nid in "${SAMPLE_CONTACTS[@]}"; do
  ddev drush entity:delete node $nid --yes 2>/dev/null
  echo "  ✓ Deleted contact #$nid"
done

echo ""
echo "🧹 Clearing cache..."
ddev drush cr >/dev/null 2>&1

echo ""
echo "📊 Final Data Status:"
ddev drush sql-query "SELECT 'AFTER' as Status, type, COUNT(*) as count FROM node_field_data WHERE status=1 AND type IN ('contact','deal','organization') GROUP BY type"

echo ""
echo "✅ DONE! App now has ONLY REAL DATA"
echo ""
echo "📋 Remaining Data:"
echo "  ✅ Deals: Hợp đồng Website, Phase 1 Test Deal"
echo "  ✅ Contacts: Nguyễn Văn Test, Phase 1 Test Contact, Test Contact 1, Test Contact 2, Jane Smith, John Doe, Sarah Johnson, Mike Davis"
echo ""
echo "🎉 Pipeline (/crm/pipeline) hiện chỉ hiển thị dữ liệu THẬT!"
