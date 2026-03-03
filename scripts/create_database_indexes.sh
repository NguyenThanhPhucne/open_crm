#!/bin/bash

echo "🔧 Creating database indexes for CRM entities..."
echo ""

# Index for field_owner (all content types use this)
echo "1. Creating index on field_owner..."
ddev drush sqlq "ALTER TABLE node__field_owner ADD INDEX IF NOT EXISTS idx_field_owner_target_id (field_owner_target_id)" 2>/dev/null && echo "  ✅ node__field_owner indexed" || echo "  ⚠️  Index may already exist"

# Index for field_organization (contact, deal)
echo "2. Creating index on field_organization..."
ddev drush sqlq "ALTER TABLE node__field_organization ADD INDEX IF NOT EXISTS idx_field_organization_target_id (field_organization_target_id)" 2>/dev/null && echo "  ✅ node__field_organization indexed" || echo "  ⚠️  Index may already exist"

# Index for field_stage (deal pipeline)
echo "3. Creating index on field_stage..."
ddev drush sqlq "ALTER TABLE node__field_stage ADD INDEX IF NOT EXISTS idx_field_stage_target_id (field_stage_target_id)" 2>/dev/null && echo "  ✅ node__field_stage indexed" || echo "  ⚠️  Index may already exist"

# Index for field_contact (activity)
echo "4. Creating index on field_contact..."
ddev drush sqlq "ALTER TABLE node__field_contact ADD INDEX IF NOT EXISTS idx_field_contact_target_id (field_contact_target_id)" 2>/dev/null && echo "  ✅ node__field_contact indexed" || echo "  ⚠️  Index may already exist"

# Index for field_contact_ref (activity alternate)
echo "5. Creating index on field_contact_ref..."
ddev drush sqlq "ALTER TABLE node__field_contact_ref ADD INDEX IF NOT EXISTS idx_field_contact_ref_target_id (field_contact_ref_target_id)" 2>/dev/null && echo "  ✅ node__field_contact_ref indexed" || echo "  ⚠️  Index may already exist"

# Index for field_deal (activity)
echo "6. Creating index on field_deal..."
ddev drush sqlq "ALTER TABLE node__field_deal ADD INDEX IF NOT EXISTS idx_field_deal_target_id (field_deal_target_id)" 2>/dev/null && echo "  ✅ node__field_deal indexed" || echo "  ⚠️  Index may already exist"

# Index for field_type (activity type)
echo "7. Creating index on field_type..."
ddev drush sqlq "ALTER TABLE node__field_type ADD INDEX IF NOT EXISTS idx_field_type_target_id (field_type_target_id)" 2>/dev/null && echo "  ✅ node__field_type indexed" || echo "  ⚠️  Index may already exist"

# Index for field_assigned_to (activity)
echo "8. Creating index on field_assigned_to..."
ddev drush sqlq "ALTER TABLE node__field_assigned_to ADD INDEX IF NOT EXISTS idx_field_assigned_to_target_id (field_assigned_to_target_id)" 2>/dev/null && echo "  ✅ node__field_assigned_to indexed" || echo "  ⚠️  Index may already exist"

# Index for field_assigned_staff (organization)
echo "9. Creating index on field_assigned_staff..."
ddev drush sqlq "ALTER TABLE node__field_assigned_staff ADD INDEX IF NOT EXISTS idx_field_assigned_staff_target_id (field_assigned_staff_target_id)" 2>/dev/null && echo "  ✅ node__field_assigned_staff indexed" || echo "  ⚠️  Index may already exist"

echo ""
echo "🎉 Database indexing complete!"
echo ""
echo "📊 Verifying indexes created..."
ddev drush sqlq "SELECT TABLE_NAME, INDEX_NAME, COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = 'db' AND INDEX_NAME LIKE 'idx_%' ORDER BY TABLE_NAME, INDEX_NAME" | head -20
echo ""
echo "✅ Done! Query performance should be significantly improved."
