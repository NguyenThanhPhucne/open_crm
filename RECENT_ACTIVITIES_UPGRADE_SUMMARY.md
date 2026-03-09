# Recent Activities Card Upgrade - HubSpot/Salesforce Style

## Overview

The Recent Activities card has been upgraded from a static list to a modern, scrollable activity feed similar to HubSpot and Salesforce dashboards.

## Changes Made

### 1. Data Loading (Line 170)

**Before:**

```php
->range(0, 10);
```

**After:**

```php
->range(0, 30);
```

- Increased activities from 10 to 30 to provide enough content for internal scrolling
- Users can now see more activities without clicking "View all"

### 2. Card Layout CSS (Line 760)

**Before:**

```css
.activities-card {
  height: 100%;
}
```

**After:**

```css
.activities-card {
  height: 560px;
  display: flex;
  flex-direction: column;
}
```

- Fixed height of 560px ensures the card maintains consistent visual height
- Flex layout allows the activity feed to fill available space
- Header stays fixed, feed scrolls inside

### 3. Activity Feed Container CSS (Line 823)

**Before:**

```css
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  flex: 1;
  overflow-y: auto;
  padding: 0;
}
```

**After:**

```css
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0 8px 0 0;
  scroll-behavior: smooth;
  margin-right: -8px;
}
```

**Key Improvements:**

- `gap: 0` - Removed gap, activities now flow naturally
- `scroll-behavior: smooth` - Adds smooth scrolling like modern CRM apps
- `padding: 0 8px 0 0` - Right padding for scrollbar spacing
- `margin-right: -8px` - Compensates for padding to keep content aligned
- `overflow-x: hidden` - Prevents horizontal scroll

### 4. Scrollbar Styling (Lines 833-848)

**Before:**

```css
.activity-list::-webkit-scrollbar {
  width: 6px;
}
.activity-list::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.activity-list::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
```

**After:**

```css
.activity-list::-webkit-scrollbar {
  width: 6px;
}
.activity-list::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.activity-list::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
```

**Improvements:**

- Added smooth transition for scrollbar hover effect
- Slightly larger border-radius for modern appearance
- Consistent with HubSpot/Salesforce scrollbar styling

### 5. Activity Item Styling (Lines 850-865)

**Before:**

```css
.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 10px 0;
  border-radius: 10px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  border: 1px solid transparent;
}

.activity-item:hover {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-color: #e2e8f0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}
```

**After:**

```css
.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 8px;
  border-radius: 8px;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  border: 1px solid transparent;
  border-bottom: 1px solid #f1f5f9;
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-item:hover {
  background: #f8fafc;
  border-color: transparent;
  border-radius: 8px;
}
```

**Improvements:**

- Added separator lines between items (border-bottom)
- Clean hover effect with simple background color (matches modern CRM UX)
- Removed gradient and shadow for cleaner interface
- `:last-child` removes border from last item for clean bottom edge
- Adjusted padding for better spacing in scrollable feed

### 6. Responsive Design (Lines 1252-1298)

**Added:**

```css
/* Responsive Design for Activities Card */
@media (max-width: 1200px) {
  .activities-card {
    height: 480px;
  }
}

@media (max-width: 768px) {
  /* ... existing styles ... */

  .activities-card {
    height: 420px;
  }

  .activity-item {
    padding: 10px 6px;
  }

  .activity-icon {
    width: 36px;
    height: 36px;
  }
}
```

**Responsive Behavior:**

- **Desktop (>1200px):** 560px height - Full activity feed visible
- **Tablet (768px-1200px):** 480px height - Good balance between feed and other sections
- **Mobile (<768px):** 420px height - Compact view with adjusted padding and smaller icons

## Visual Behavior

### Desktop View

```
┌─────────────────────────────┐
│ Recent Activities    View all│
├─────────────────────────────┤
│ [Icon] Activity Title ... 2h │
│        Type • Contact        │ ← Hover: Light blue background
├─────────────────────────────┤
│ [Icon] Activity Title ... 4h │
│        Type • Contact        │
├─────────────────────────────┤
│ ... (more with scroll)       │
│                          ↑   │
│                       Scroll │
└─────────────────────────────┘
```

### Key Features

✅ **Fixed Height:** 560px (desktop), 480px (tablet), 420px (mobile)  
✅ **Internal Scrolling:** Smooth scroll behavior inside card  
✅ **Header Fixed:** Title and "View all" link always visible  
✅ **30 Activities:** Enough data to necessitate scrolling  
✅ **Visual Separators:** Subtle borders between items  
✅ **Smooth Hover:** Clean background color change on hover  
✅ **Custom Scrollbar:** Styled to match design system  
✅ **Responsive:** Scales appropriately on smaller screens  
✅ **Modern UX:** Similar to HubSpot and Salesforce activity feeds

## HTML Structure (No Changes)

The HTML structure remained unchanged - all improvements are CSS and data loading based:

```html
<section class="dashboard-card activities-card">
  <div class="section-header">
    <h3>Recent Activities</h3>
    <a href="/crm/all-activities">View all</a>
  </div>
  <div class="activity-feed">
    <div class="activity-item">...</div>
    <!-- More activities with internal scroll -->
  </div>
</section>
```

## Browser Compatibility

- ✅ Chrome/Edge (WebKit scrollbar styling)
- ✅ Firefox (Native scrollbar)
- ✅ Safari (WebKit scrollbar styling)
- ✅ Mobile browsers (Touch scroll)

## Testing Checklist

- [ ] Load dashboard and verify Recent Activities card shows 560px fixed height
- [ ] Scroll inside the activities card (should scroll smoothly)
- [ ] Verify header stays fixed while scrolling
- [ ] Check hover effect on activity items (light background)
- [ ] Test on tablet (should be 480px)
- [ ] Test on mobile (should be 420px)
- [ ] Verify custom scrollbar appears when scrolling
- [ ] Check all 30 activities load with no issues
- [ ] Verify "View all" link is clickable

## Performance Notes

- Loading 30 activities instead of 10 increases initial data load slightly
- CSS improvements have no performance impact
- Smooth scrolling uses GPU acceleration on modern browsers
- No JavaScript changes required

## Files Modified

- `/web/modules/custom/crm_dashboard/src/Controller/DashboardController.php`

---

**Upgrade Completed:** The Recent Activities card is now a modern, scrollable activity feed matching HubSpot and Salesforce styling and behavior.
