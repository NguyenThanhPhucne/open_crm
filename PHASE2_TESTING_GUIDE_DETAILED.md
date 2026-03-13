# PHASE 2 Feature Testing Guide

## Browser Setup

- URL: http://127.0.0.1/crm/all-contacts
- Login with admin user
- Open browser console (F12) to see any JS errors

## Feature 1: Inline Edit (Click-to-Edit)

### Where to Find It

- **Location**: Any CRM list view (All Contacts, All Deals, etc.)
- **Trigger**: Hover over any field with pencil icon

### What to Look For

1. **Hover Effect**
   - Hover over contact name field
   - ✅ Should see pencil (edit) icon appear
   - ✅ Field should highlight

2. **Click to Edit**
   - Click on the field (or pencil icon)
   - ✅ Should become editable (text input appears)
   - ✅ Old value is highlighted (ready to replace)

3. **Edit Input**
   - Type a new value
   - ✅ Input should accept text
   - ✅ Should show placeholder or guidance

4. **Save (Enter Key)**
   - Press Enter to save
   - ✅ Field should save and exit edit mode
   - ✅ Checkmark icon appears briefly
   - ✅ Field updates to new value
   - ✅ No page reload expected

5. **Cancel (Escape Key)**
   - Press Escape to cancel
   - ✅ Edit mode closes
   - ✅ Original value restored
   - ✅ No change saved

6. **Error Handling**
   - Try entering invalid data (test field validation)
   - ✅ Should show error message
   - ✅ Should allow retry
   - ✅ Original value preserved on error

### Technical Checks (Browser Console)

```javascript
// Check if inline edit library is loaded
Drupal.behaviors.crm_inline_edit !== undefined; // Should be true
```

### Expected Performance

- Edit field → API call completes: < 500ms
- Visual feedback should be immediate

---

## Feature 2: Optimistic UI (Form Save Feedback)

### Where to Find It

- **Location**: Any node form (New Contact, Edit Contact, etc.)
- **Path**: /node/add/contact or /node/[id]/edit

### What to Look For

1. **Form Loaded**
   - Open a contact form
   - ✅ Form should load normally
   - ✅ No console errors

2. **Fill Form**
   - Fill in a few fields (name, email, phone)
   - ✅ Fields should be interactive
   - ✅ No lag in input

3. **Click Save**
   - Click the form "Save" button
   - ✅ Button should show loading state (spinner or disabled)
   - ✅ Toast notification should appear at top
   - ✅ Toast should say "Saving..." or similar

4. **Success Feedback**
   - Wait for save to complete
   - ✅ Toast should change to "Saved successfully"
   - ✅ Toast should be green (success color)
   - ✅ Toast should disappear after 3-5 seconds
   - ✅ Form should be clean (no validation errors if valid data)

5. **On Error**
   - Try saving with invalid email format
   - ✅ Toast should show error message (red)
   - ✅ Form should show field-level errors
   - ✅ Should allow editing and re-trying

6. **Redirect**
   - After successful save
   - ✅ Should navigate to entity view page (or stay on edit)
   - ✅ Should show "Entity updated" message
   - ✅ URL should reflect saved entity ID

### Technical Checks (Browser Console)

```javascript
// Check if optimistic UI library is loaded
Drupal.behaviors.crm_optimistic_ui !== undefined; // Should be true

// Try form save manually
document.querySelector("form").submit();
```

### Expected Experience

- Click save → immediate visual feedback
- No "flash" where page reloads
- Smooth toast notifications
- Form remains interactive during save

---

## Feature 3: Lazy Load (Infinite Scroll)

### Where to Find It

- **Location**: Any list view (All Contacts, All Deals, etc.)
- **Path**: /crm/all-contacts, /crm/all-deals, etc.

### What to Look For

1. **List Displayed**
   - List view should show contacts (10-20 items)
   - ✅ Contacts should have names, emails, etc.
   - ✅ List should be paginated or use infinite scroll

2. **Scroll to Bottom**
   - Scroll down to the bottom of the page
   - ✅ Should see loading indicator
   - ✅ New items should appear at bottom
   - ✅ No page reload expected

3. **Continue Scrolling**
   - Keep scrolling down
   - ✅ More items load automatically
   - ✅ Smooth append (no jumping around)
   - ✅ Loading indicator appears for each batch

4. **Load All Data**
   - Continue scrolling until no more items
   - ✅ Should reach end of list
   - ✅ Loading indicator should disappear
   - ✅ Message or UI should indicate "No more items"

5. **Error Handling**
   - Reload page and scroll (test network error)
   - ✅ Should show error message
   - ✅ Should allow retry
   - ✅ Should not lose already-loaded items

### Technical Checks (Browser Console)

```javascript
// Check if lazy load library is loaded
Drupal.behaviors.crm_lazy_load !== undefined; // Should be true

// Check scroll state
Drupal.behaviors.crm_lazy_load.lastLoadedPage; // Should increment
```

### Expected Performance

- Scroll to bottom → new items load: < 1000ms
- Items should appear smoothly
- No noticeable lag or freezing

---

## Performance Checks

### Before Tests

1. Open Chrome DevTools (F12)
2. Go to Network tab
3. Filter by XHR (XMLHttpRequest) to see API calls

### During Tests

1. **Inline Edit**: Should see `/api/v1/node/...` PATCH request
   - Expected: ~100-200ms response time
   - Expected: JSON response with success: true

2. **Optimistic UI**: Should see form POST request
   - Expected: ~200-500ms response time
   - Expected: Redirect response or saved confirmation

3. **Lazy Load**: Should see list/view request
   - Expected: ~300-600ms response time
   - Expected: HTML or JSON with more items

### Database Query Count

- Before optimization: ~150 queries per page load
- After optimization: ~30 queries per page load (80% improvement)

---

## Accessibility & Cross-Browser

### Keyboard Navigation

- [ ] Tab through fields works smoothly
- [ ] Inline edit accessible via keyboard
- [ ] Enter/Escape keys work as documented
- [ ] Focus indicators visible

### Mobile Responsiveness

- [ ] Features work on tablet (iPad size)
- [ ] Features work on mobile (iPhone size)
- [ ] Touch interactions work for inline edit
- [ ] Scroll performance smooth on mobile

### Browser Compatibility

- [ ] Chrome/Edge: All features working
- [ ] Firefox: All features working (if available)
- [ ] Safari: All features working (if available)

---

## Error Scenarios to Test

### 1. Inline Edit Errors

- [ ] Edit non-existent field
- [ ] Edit field without permission
- [ ] Edit field with invalid type
- [ ] Network timeout during save

### 2. Form Save Errors

- [ ] Save with required fields empty
- [ ] Save with invalid field formats
- [ ] Network error during save
- [ ] Timeout during save

### 3. Lazy Load Errors

- [ ] Network error while loading more
- [ ] No more items available
- [ ] Rapid scrolling (spam loading)

---

## Success Criteria

All PHASE 2 features must:

- ✅ Work without page reloads
- ✅ Provide instant visual feedback
- ✅ Handle errors gracefully with messages
- ✅ Maintain data integrity
- ✅ Perform within response time targets (see above)
- ✅ Not break existing functionality
- ✅ Work across browsers and devices

---

## Quick Demo Script

If you want to test quickly:

1. Go to http://127.0.0.1/crm/all-contacts
2. Hover over first contact name → click to edit → change name → press Enter
3. Click on a contact name to view details
4. Edit form → change email/phone → click Save → watch toast
5. Go back to list → scroll down → watch auto-load

Expected total time: < 2 minutes

---

## Troubleshooting

### Console Shows Errors

- Right-click → Inspect → Console tab
- Look for red errors about undefined behavior
- Note the error message and line number
- Report with browser version

### Features Not Working

- Ctrl+Shift+Delete (or Cmd+Shift+Delete) to clear browser cache
- Hard refresh (Ctrl+F5)
- Check that user is logged in as admin
- Verify JavaScript files exist in `/web/modules/custom/crm/js/`

### Slow Performance

- Check Network tab in DevTools
- Look for individual request times
- Can be due to database query count
- Check that indexes were created (run `ddev drush updb`)
