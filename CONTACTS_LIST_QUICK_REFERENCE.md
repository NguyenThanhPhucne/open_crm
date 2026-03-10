# Modern Contacts List - Quick Reference Card

## ⚡ 60-Second Setup

```bash
# 1. Rebuild cache (REQUIRED)
ddev exec drush cache:rebuild

# 2. Open in browser
open http://open-crm.ddev.site/crm/all-contacts

# Done! ✅
```

---

## 🎯 What to Expect

| Feature         | What You'll See                                  |
| --------------- | ------------------------------------------------ |
| **Sorting**     | Newest/most-updated contacts FIRST               |
| **Timestamps**  | "2 minutes ago", "Yesterday", "3 hours ago"      |
| **Badges**      | Green "✨ New" if created <10 min ago            |
| **Delete**      | Click Delete → Modal → Click Delete again → Gone |
| **Permissions** | Admin: delete all; Rep: delete own only          |

---

## 🧪 Quick Tests

### ✅ Test 1: Page Loads

```
Go to /crm/all-contacts
Should see: Contact table with names, emails, timestamps
```

### ✅ Test 2: Delete Works

```
1. Hover over contact → Delete button appears
2. Click Delete → Confirmation modal
3. Click Delete → Row fades out, notification shows
4. Refresh page → Contact gone from database
```

### ✅ Test 3: Timestamps

```
See "2m ago", "1h ago", "Yesterday" etc.
These update as time passes (refresh to see new times)
```

### ✅ Test 4: Badges

```
Green "New" badge = Created in last 10 minutes
Blue "Updated" badge = Last changed in last 10 minutes
No badge = Older than 10 minutes
```

### ✅ Test 5: Mobile

```
Resize browser to mobile width
Table should stack nicely, buttons should be clickable
```

---

## 🐛 If Something Returns Wrong

### Delete button not working?

```bash
ddev exec drush cache:rebuild
# Then hard refresh: Ctrl+Shift+R (or Cmd+Shift+R on Mac)
```

### Still doesn't work?

```bash
# Check console for errors (F12)
# If you see permission errors, make sure you're logged in as admin
```

### Timestamps wrong?

```bash
# Hard refresh page
# Timestamps are calculated on page load, not real-time
```

### Contacts still visible after deletion?

```bash
# Refresh page - if contact is STILL there, something failed
# Check error notification at bottom of screen
```

---

## 📁 What Files Changed

**New Files:**

- `src/Controller/ContactsListController.php` - Backend logic
- `js/crm-contacts-list.js` - Delete modal, animations
- `css/crm-contacts-list.css` - Styling & badges
- `templates/views-view-table--all-contacts.html.twig` - Table display

**Modified Files:**

- `crm.routing.yml` - Added route
- `crm.libraries.yml` - Added CSS/JS library
- `crm.module` - Added library attachment hook

---

## 🔑 Key Timings

⏱️ **10 minutes** = Badge eligibility window (New/Updated badges disappear after 10 min)  
⏱️ **500ms** = Page load target (most page loads)  
⏱️ **300ms** = Delete animation (row fade-out)  
⏱️ **3 seconds** = Notification display duration

---

## 🎨 Customization (Advanced)

**Change badge color (green → blue)?**

- Edit: `css/crm-contacts-list.css`
- Find: `.crm-badge-new { background-color: #10b981; }`
- Change to desired color

**Change sort order (newest → oldest)?**

- Edit: `src/Controller/ContactsListController.php`
- Find: `->sort('changed', 'DESC')`
- Change to: `->sort('changed', 'ASC')`

**Change badge time window (10 min → 30 min)?**

- Edit: `src/Controller/ContactsListController.php`
- Find: `const BADGE_WINDOW = 600;` (600 seconds = 10 minutes)
- Change to: `const BADGE_WINDOW = 1800;` (30 minutes)

---

## 📊 Architecture Overview

```
Browser Request: GET /crm/all-contacts
    ↓
Route: crm.routing.yml → ContactsListController::view()
    ↓
Controller queries database:
  - SELECT contacts WHERE type='contact'
  - SORT BY changed DESC, created DESC (newest first)
  - Apply RBAC filter (show only user's contacts)
    ↓
Controller formats data for template:
  - Add timestamps (changed_relative_time, created_relative_time)
  - Calculate badges (isNew, isUpdated)
  - Add user permissions (canDelete, canEdit)
    ↓
Twig template renders HTML table with badges
    ↓
Browser loads CSS & JavaScript:
  - CSS: Styling, badges, hover effects, animations
  - JS: Delete button handlers, modal, AJAX
    ↓
User interaction: Click Delete
    ↓
JavaScript: Show modal, get confirmation
    ↓
POST /crm/all-contacts/{nid}/delete
    ↓
Controller::delete() validates permission & deletes
    ↓
JavaScript: Fade out row, show notification
    ↓
Browser: Item removed from DOM
```

---

## 📋 Deployment Checklist

- [ ] All 4 new files exist in correct locations
- [ ] `ddev exec drush cache:rebuild` completed
- [ ] Page loads at `/crm/all-contacts` without 404
- [ ] Contacts display in correct order (newest first)
- [ ] Timestamps show (e.g., "2m ago")
- [ ] Badges show for recent items
- [ ] Delete button works (modal appears)
- [ ] Delete confirmation actually deletes (test refresh)
- [ ] Permission checks work (test as non-admin user)
- [ ] Mobile responsive (test at 375px width)

---

## 🚀 Go Live

```bash
# 1. Final cache rebuild
ddev exec drush cache:rebuild

# 2. Check for errors
ddev exec drush log:tail --severity=3

# 3. Quick smoke test
open http://open-crm.ddev.site/crm/all-contacts

# 4. Done! ✅
```

---

## 📞 Need More Help?

See full docs: [CRM_MODERN_CONTACTS_LIST_IMPLEMENTATION.md](CRM_MODERN_CONTACTS_LIST_IMPLEMENTATION.md)  
Or full deployment guide: [CONTACTS_LIST_DEPLOYMENT_GUIDE.md](CONTACTS_LIST_DEPLOYMENT_GUIDE.md)

---

**Version:** 1.0 | **Status:** Ready to Deploy ✅
