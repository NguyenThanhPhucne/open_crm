#!/bin/bash

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║        ACCESS CONTROL TEST - STEP BY STEP GUIDE                ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "✅ FIXED: my_activities view now filters by current user"
echo ""
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "PHASE 1: CREATE TEST DATA (as different users)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "🔑 Login Links:"
echo "───────────────────────────────────────────────────────────────"
echo ""

echo "SalesRep1:"
LINK1=$(ddev drush user:login --name=salesrep1 --no-browser 2>/dev/null)
echo "$LINK1"
echo ""

echo "SalesRep2:"
LINK2=$(ddev drush user:login --name=salesrep2 --no-browser 2>/dev/null)
echo "$LINK2"
echo ""

echo "Manager:"
LINK3=$(ddev drush user:login --name=manager --no-browser 2>/dev/null)
echo "$LINK3"
echo ""

echo ""
echo "📝 STEP 1: Create contacts as SalesRep1"
echo "───────────────────────────────────────────────────────────────"
echo "1. Click this login link: $LINK1"
echo "2. After login, go to: http://open-crm.ddev.site/node/add/contact"
echo "3. Create contact:"
echo "   Name: Test Contact SR1-A"
echo "   Phone: +84901111111"
echo "   Status: Active"
echo "4. Click Save"
echo "5. Create another contact:"
echo "   Name: Test Contact SR1-B"
echo "   Phone: +84901111112"
echo "   Status: Active"
echo "6. Click Save"
echo ""
echo "✅ Now you have 2 contacts owned by salesrep1"
echo ""
echo "Press ENTER when done..."
read

echo ""
echo "📝 STEP 2: Create contacts as SalesRep2"
echo "───────────────────────────────────────────────────────────────"
echo "1. Click this login link: $LINK2"
echo "2. After login, go to: http://open-crm.ddev.site/node/add/contact"
echo "3. Create contact:"
echo "   Name: Test Contact SR2-A"
echo "   Phone: +84902222221"
echo "   Status: Active"
echo "4. Click Save"
echo "5. Create another contact:"
echo "   Name: Test Contact SR2-B"
echo "   Phone: +84902222222"
echo "   Status: Active"
echo "6. Click Save"
echo ""
echo "✅ Now you have 2 contacts owned by salesrep2"
echo ""
echo "Press ENTER when done..."
read

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "PHASE 2: TEST ACCESS CONTROL (as SalesRep1)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "📊 TEST 1: View 'My Contacts' as SalesRep1"
echo "───────────────────────────────────────────────────────────────"
echo "1. Make sure you're logged in as salesrep1: $LINK1"
echo "2. Go to: http://open-crm.ddev.site/crm/my-contacts"
echo "3. You should see:"
echo "   ✅ Test Contact SR1-A"
echo "   ✅ Test Contact SR1-B"
echo "   ❌ Test Contact SR2-A (should NOT see)"
echo "   ❌ Test Contact SR2-B (should NOT see)"
echo ""
echo "Expected: ONLY 2 contacts (your own)"
echo ""
echo "✅ Did you see ONLY your 2 contacts? (y/n)"
read -r test1_result
if [ "$test1_result" = "y" ]; then
  echo "   ✅ PASSED - Filtering works correctly!"
else
  echo "   ❌ FAILED - Check view configuration"
fi
echo ""

echo "🔒 TEST 2: Try to edit SalesRep2's contact"
echo "───────────────────────────────────────────────────────────────"
echo "1. Still logged in as salesrep1"
echo "2. Find the node ID of 'Test Contact SR2-A':"
echo "   - Click on 'Test Contact SR2-A' in any list"
echo "   - Look at URL: /node/XXX (XXX is the node ID)"
echo "3. Try to edit it: http://open-crm.ddev.site/node/XXX/edit"
echo "   (Replace XXX with actual node ID)"
echo "4. Expected: '403 Access Denied' page"
echo ""
echo "✅ Did you get 'Access Denied'? (y/n)"
read -r test2_result
if [ "$test2_result" = "y" ]; then
  echo "   ✅ PASSED - Edit permission works correctly!"
else
  echo "   ❌ FAILED - User can edit others' contacts (security issue!)"
fi
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "PHASE 3: TEST MANAGER ACCESS"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "📊 TEST 3: View 'My Contacts' as Manager"
echo "───────────────────────────────────────────────────────────────"
echo "1. Login as manager: $LINK3"
echo "2. Go to: http://open-crm.ddev.site/crm/my-contacts"
echo "3. You should see:"
echo "   ✅ Test Contact SR1-A"
echo "   ✅ Test Contact SR1-B"
echo "   ✅ Test Contact SR2-A"
echo "   ✅ Test Contact SR2-B"
echo "   ✅ All other existing contacts"
echo ""
echo "Expected: ALL contacts (manager sees everything)"
echo ""
echo "✅ Did you see ALL 4+ contacts? (y/n)"
read -r test3_result
if [ "$test3_result" = "y" ]; then
  echo "   ✅ PASSED - Manager can see all data!"
else
  echo "   ❌ FAILED - Manager should see all contacts"
fi
echo ""

echo "✏️ TEST 4: Manager can edit any contact"
echo "───────────────────────────────────────────────────────────────"
echo "1. Still logged in as manager"
echo "2. Find 'Test Contact SR1-A' (owned by salesrep1)"
echo "3. Click on it, then click 'Edit' tab"
echo "4. Change phone to: +84999999999"
echo "5. Click Save"
echo "6. Expected: Save successful (no access denied)"
echo ""
echo "✅ Were you able to edit and save? (y/n)"
read -r test4_result
if [ "$test4_result" = "y" ]; then
  echo "   ✅ PASSED - Manager can edit any content!"
else
  echo "   ❌ FAILED - Manager should be able to edit all contacts"
fi
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "PHASE 4: TEST MY ACTIVITIES VIEW (FIXED)"
echo "═══════════════════════════════════════════════════════════════"
echo ""

echo "📅 TEST 5: My Activities filtering"
echo "───────────────────────────────────────────────────────────────"
echo "1. Login as salesrep1: $LINK1"
echo "2. Create an activity:"
echo "   - Go to: http://open-crm.ddev.site/node/add/activity"
echo "   - Title: Test Activity SR1"
echo "   - Type: Call"
echo "   - Assigned To: salesrep1 (yourself)"
echo "   - Date: Today"
echo "   - Save"
echo ""
echo "3. Login as salesrep2: $LINK2"
echo "4. Create an activity:"
echo "   - Go to: http://open-crm.ddev.site/node/add/activity"
echo "   - Title: Test Activity SR2"
echo "   - Type: Meeting"
echo "   - Assigned To: salesrep2 (yourself)"
echo "   - Date: Today"
echo "   - Save"
echo ""
echo "5. Login as salesrep1 again: $LINK1"
echo "6. Go to: http://open-crm.ddev.site/crm/my-activities"
echo "7. You should see:"
echo "   ✅ Test Activity SR1 (your activity)"
echo "   ❌ Test Activity SR2 (should NOT see)"
echo ""
echo "Press ENTER when ready to test..."
read
echo ""
echo "✅ Did you see ONLY your activity? (y/n)"
read -r test5_result
if [ "$test5_result" = "y" ]; then
  echo "   ✅ PASSED - My Activities filter works!"
else
  echo "   ❌ FAILED - Activities view not filtering correctly"
fi
echo ""

echo "═══════════════════════════════════════════════════════════════"
echo "TEST SUMMARY"
echo "═══════════════════════════════════════════════════════════════"
echo ""

passed=0
total=5

[ "$test1_result" = "y" ] && ((passed++))
[ "$test2_result" = "y" ] && ((passed++))
[ "$test3_result" = "y" ] && ((passed++))
[ "$test4_result" = "y" ] && ((passed++))
[ "$test5_result" = "y" ] && ((passed++))

echo "Results: $passed / $total tests passed"
echo ""

if [ "$test1_result" = "y" ]; then
  echo "✅ TEST 1: SalesRep sees only own contacts"
else
  echo "❌ TEST 1: Contact filtering failed"
fi

if [ "$test2_result" = "y" ]; then
  echo "✅ TEST 2: SalesRep cannot edit others' contacts"
else
  echo "❌ TEST 2: Edit permission failed"
fi

if [ "$test3_result" = "y" ]; then
  echo "✅ TEST 3: Manager sees all contacts"
else
  echo "❌ TEST 3: Manager view failed"
fi

if [ "$test4_result" = "y" ]; then
  echo "✅ TEST 4: Manager can edit any contact"
else
  echo "❌ TEST 4: Manager edit permission failed"
fi

if [ "$test5_result" = "y" ]; then
  echo "✅ TEST 5: My Activities filters correctly"
else
  echo "❌ TEST 5: Activities filter failed"
fi

echo ""

if [ $passed -eq $total ]; then
  echo "╔════════════════════════════════════════════════════════════════╗"
  echo "║                 🎉 ALL TESTS PASSED! 🎉                        ║"
  echo "║          ACCESS CONTROL IS WORKING CORRECTLY                  ║"
  echo "╚════════════════════════════════════════════════════════════════╝"
else
  echo "╔════════════════════════════════════════════════════════════════╗"
  echo "║               ⚠️  SOME TESTS FAILED                            ║"
  echo "║         Please review failed tests above                       ║"
  echo "╚════════════════════════════════════════════════════════════════╝"
fi

echo ""
echo "📊 SECURITY CONCLUSION:"
echo "───────────────────────────────────────────────────────────────"
echo ""
echo "IF ALL TESTS PASSED:"
echo "  ✅ Permissions are configured correctly"
echo "  ✅ Views filter data by user properly"
echo "  ✅ Users can only edit/delete their own content"
echo "  ✅ Managers can access all data"
echo "  ✅ System is secure for production use"
echo ""
echo "Your CRM has proper access control! 🔒"
echo ""
