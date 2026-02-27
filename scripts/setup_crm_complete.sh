#!/bin/bash

echo "🚀 SETUP COMPLETE CRM SYSTEM - ALL-IN-ONE"
echo "=========================================="
echo ""
echo "Script này sẽ thiết lập CRM từ đầu đến cuối:"
echo "  1. Taxonomies (Deal Stage, Activity Type, Lead Source, Industry)"
echo "  2. Content Types (Contact, Deal, Activity, Organization)"
echo "  3. Owner Fields (field_owner, field_assigned_to)"
echo "  4. Roles & Permissions (Sales Rep, Sales Manager)"
echo "  5. Sample Users (manager, salesrep1, salesrep2, customer1)"
echo "  6. Sample Data (Organizations, Contacts, Deals, Activities)"
echo "  7. Views (Contacts, Deals, Activities, Organizations)"
echo "  8. Dashboard (Professional UI with Stats)"
echo "  9. Menu & Homepage"
echo " 10. Light Mode Theme"
echo ""
read -p "Tiếp tục? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 1
fi

echo ""
echo "=========================================="
echo "🔧 BƯỚC 1: CREATE TAXONOMIES"
echo "=========================================="
bash scripts/create_taxonomies.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 2: CREATE CONTENT TYPES"
echo "=========================================="
bash scripts/create_content_types.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 3: ADD OWNER FIELDS (FIXED VERSION)"
echo "=========================================="
bash scripts/fix_owner_fields_v2.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 4: CREATE ROLES & PERMISSIONS"
echo "=========================================="
bash scripts/create_roles_and_permissions.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 5: UPDATE PERMISSIONS (VIEW OWN CONTENT)"
echo "=========================================="
bash scripts/update_permissions_v2.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 6: CREATE SAMPLE USERS"
echo "=========================================="
bash scripts/create_sample_users.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 7: CREATE SAMPLE DATA (WITH OWNERS)"
echo "=========================================="
bash scripts/create_sample_data_v2.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 8: CREATE VIEWS"
echo "=========================================="
bash scripts/create_views.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 9: CONFIGURE FORM DISPLAYS"
echo "=========================================="
bash scripts/configure_form_displays.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 10: SETUP PROFESSIONAL UI"
echo "=========================================="
bash scripts/restore_professional_ui.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 11: IMPROVE CSS (Tailwind/Stripe/Notion)"
echo "=========================================="
bash scripts/improve_dashboard_css.sh
bash scripts/improve_table_css.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 12: FIX HOMEPAGE LOGIN"
echo "=========================================="
bash scripts/fix_homepage_login.sh

echo ""
echo "=========================================="
echo "🔧 BƯỚC 13: SET LIGHT MODE"
echo "=========================================="
ddev drush config:set gin.settings enable_darkmode 0 -y

echo ""
echo "=========================================="
echo "🔧 BƯỚC 14: FINAL CACHE CLEAR"
echo "=========================================="
ddev drush cr

echo ""
echo "=========================================="
echo "✅ HOÀN THÀNH!"
echo "=========================================="
echo ""
echo "🎉 HỆ THỐNG CRM ĐÃ SẴN SÀNG!"
echo ""
echo "📍 TRUY CẬP:"
echo "   URL: http://open-crm.ddev.site"
echo ""
echo "🔐 TÀI KHOẢN TEST:"
echo ""
echo "   👨‍💼 Admin (Full access)"
echo "      Username: admin"
echo "      Password: aa5BLB69Jt"
echo ""
echo "   👔 Manager (View all team data)"
echo "      Username: manager"
echo "      Password: manager123"
echo ""
echo "   👤 Sales Rep 1 (View own data only)"
echo "      Username: salesrep1"
echo "      Password: sales123"
echo "      Data: Acme Corp, Global Enterprises + contacts/deals"
echo ""
echo "   👤 Sales Rep 2 (View own data only)"
echo "      Username: salesrep2"
echo "      Password: sales123"
echo "      Data: Tech Solutions, Retail Plus + contacts/deals"
echo ""
echo "📊 FEATURES:"
echo "   ✅ Professional Dashboard (Tailwind style)"
echo "   ✅ Modern Tables (Notion/Linear style)"
echo "   ✅ Data Privacy (Sales reps see own data only)"
echo "   ✅ Owner Fields (Auto-assign to creator)"
echo "   ✅ Roles & Permissions (Rep vs Manager)"
echo "   ✅ Sample Data (4 orgs, 5 contacts, 5 deals, 5 activities)"
echo "   ✅ Light Mode (Professional look)"
echo "   ✅ Clean Navigation (No emojis)"
echo ""
echo "🎯 NEXT STEPS:"
echo "   1. Login as salesrep1 → See only own data"
echo "   2. Login as manager → See all team data"
echo "   3. Create new contact → Auto-assigned to you"
echo "   4. Test data privacy filters in views"
echo ""
echo "📖 DOCUMENTATION:"
echo "   - See scripts/FIX_ISSUES.md for technical details"
echo "   - See scripts/login_guide.sh for login instructions"
echo "   - See scripts/verify_complete_system.sh for system check"
echo ""
