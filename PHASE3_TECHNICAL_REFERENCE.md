# PHASE 3: TECHNICAL REFERENCE - FILE SPECIFICATIONS

## 📋 Complete File Inventory

### Enhanced Files (3)

#### 1. crm-optimistic-ui.js

**Location**: `/web/modules/custom/crm/js/crm-optimistic-ui.js`

**Before PHASE 3**:

- Lines: ~250
- Features: Basic form submission, fetch wrapper
- Error Handling: Minimal
- State Tracking: None
- Retry: None

**After PHASE 3**:

- Lines: ~350+
- Features: State management, retry logic, debounce, validation
- Error Handling: Comprehensive with recovery
- State Tracking: Per-form tracking object
- Retry: Exponential backoff (1s → 2s → 5s)

**Key Additions**:

```javascript
var CRMOptimisticUI = {
  forms: {}, // Per-form state
  csrfToken: null,
  maxRetries: 3,
  retryDelays: [1000, 2000, 5000],
  saveTimeout: 5000,
};
```

**New Capabilities**:

- `CRMOptimisticUI.saveForm(formId)` - Save with retry
- `CRMOptimisticUI.hasChanges(formId)` - Check for unsaved
- `CRMOptimisticUI.resetForm(formId)` - Reset to server state
- Conflict detection on concurrent edits
- Better error recovery with Revert button

---

#### 2. crm-lazy-load.js

**Location**: `/web/modules/custom/crm/js/crm-lazy-load.js`

**Before PHASE 3**:

- Lines: ~240
- Features: Basic scroll listener
- Loading: Simple fetch on scroll
- Retry: None
- Performance: No debounce, no caching

**After PHASE 3**:

- Lines: ~500+
- Features: Intersection Observer, state management, retry
- Loading: Modern, efficient, with fallback
- Retry: Exponential backoff with manual override
- Performance: Debounced (300ms), cached pages, deduped requests

**Key Additions**:

```javascript
var CRMLazyLoad = {
  lists: {}, // Per-list state
  useIntersectionObserver: true,
  maxRetries: 3,
  retryDelays: [1000, 2000, 5000],
  loadTimeout: 8000,
};
```

**New Capabilities**:

- `CRMLazyLoad.loadNextPage(listId)` - Manual load next page
- `CRMLazyLoad.resetList(listId)` - Reset pagination
- `CRMLazyLoad.getCurrentPage(listId)` - Get current page number
- `CRMLazyLoad.getLoadedItemCount(listId)` - Count loaded items
- Intersection Observer for efficient detection
- Request deduplication prevents concurrent loads
- Smart page caching with timestamp tracking

---

#### 3. crm-node-form.js

**Location**: `/web/modules/custom/crm/js/crm-node-form.js`

**Before PHASE 3**:

- Lines: ~170
- Features: Form layout only
- Validation: None
- Change Detection: None
- Accessibility: Basic

**After PHASE 3**:

- Lines: ~450+
- Features: Layout, validation, state tracking
- Validation: Real-time field rules (email, phone, URL, numeric)
- Change Detection: Full tracking + unsaved warning
- Accessibility: Enhanced focus management, ARIA roles

**Key Additions**:

```javascript
var CRMNodeForm = {
  forms: {},
  validateOnChange: true,
  autoSaveDelay: 3000,
};

const VALIDATION_RULES = {
  "field-email": {
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    message: "Invalid email",
  },
  "field-phone": { pattern: /^[\d\s\-\+\(\)]+$/, message: "Invalid phone" },
  "field-amount": { type: "number", min: 0, message: "Must be >= 0" },
};
```

**New Capabilities**:

- `CRMNodeForm.markModified(formId, fieldName)` - Mark field as changed
- `CRMNodeForm.hasChanges(formId)` - Check for unsaved changes
- `CRMNodeForm.resetChanges(formId)` - Reset to server values
- Real-time validation with error messages
- Visual feedback for errors (red border, error text)
- Unsaved indicator in form title
- Auto-scroll to first validation error
- Browser beforeunload warning on unsaved changes

---

### New Files (2)

#### 4. crm-ui-professional.css

**Location**: `/web/modules/custom/crm/css/crm-ui-professional.css`
**Lines**: ~600+
**Status**: NEW - Professional styling system

**Color Variables**:

```css
:root {
  --crm-primary: #0066cc;
  --crm-primary-light: #e6f0ff;
  --crm-primary-dark: #003399;
  --crm-success: #27ae60;
  --crm-danger: #d9534f;
  --crm-warning: #f39c12;
}
```

**Components Styled**:

1. **Tables**: Professional headers, hover effects, inline edit indicators
2. **Forms**: Clean inputs, focus states, validation colors
3. **Buttons**: 3 variants (primary/secondary/danger), loading animation
4. **Alerts**: Color-coded success/error/warning/info
5. **Toasts**: Slide animation, auto-dismiss, fixed positioning
6. **Status Badges**: Color-coded status indicators
7. **Responsive**: Mobile (480px), tablet (768px), desktop
8. **Dark Mode**: CSS media query support
9. **Accessibility**: WCAG standards, focus states, skip links

**Key Features**:

- CSS custom properties for easy customization
- Smooth transitions and animations
- Grid layout for forms
- Professional spacing and typography
- Touch-friendly button sizes
- Clear visual hierarchy

---

#### 5. crm-content-type-upgrades.js

**Location**: `/web/modules/custom/crm/js/crm-content-type-upgrades.js`
**Lines**: ~400+
**Status**: NEW - Content type validation system

**Content Types Enhanced**:

**Contact**:

```javascript
{
  type: "contact",
  fields: ["field-email", "field-phone", "field-status"],
  validations: {
    "field-email": { pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/ },
    "field-phone": { pattern: /^[\d\s\-\+\(\)]+$/ },
    "field-status": { enum: ["lead", "customer", "prospect"] }
  }
}
```

**Organization**:

```javascript
{
  type: "organization",
  validators: {
    "field-employee-count": { min: 0, message: "Cannot be negative" },
    "field-annual-revenue": { min: 0, message: "Cannot be negative" }
  },
  autoCalculate: {
    "field-size": function(empCount) {
      return empCount <= 10 ? "micro"
           : empCount <= 50 ? "small"
           : empCount <= 250 ? "medium"
           : empCount <= 1000 ? "large"
           : "enterprise";
    }
  }
}
```

**Deal**:

```javascript
{
  type: "deal",
  validators: {
    "field-amount": { min: 0 },
    "field-probability": { min: 0, max: 100 },
    "field-close-date": { mustBeFuture: true }
  },
  autoCalculate: {
    "field-expected-revenue": function(amount, probability) {
      return (amount * probability) / 100;
    }
  }
}
```

**Activity**:

```javascript
{
  type: "activity",
  conditionalRequired: {
    "field-contact": { when: { "field-type": ["call", "meeting", "email"] } },
    "field-outcome": { when: { "field-status": "completed" } }
  },
  validators: {
    "field-datetime": { mustBeFuture: true, warn: true }
  }
}
```

**Public API**:

```javascript
Drupal.crmContentTypes = {
  getValidator(contentType),
  validateField(contentType, fieldName, value),
  autoCalculate(contentType, fieldName, values),
  getConditionalRequired(contentType, status)
}
```

---

## 🔧 Integration Points

### How Files Work Together

```
User Interacts with CRM
    ↓
crm-node-form.js validates input
    ↓ (if valid)
crm-content-type-upgrades.js applies business rules
    ↓
crm-optimistic-ui.js handles save with retry
    ↓ (retry logic, conflict detection)
Server saves data
    ↓
Form resets, list reloads via crm-lazy-load.js
    ↓
crm-ui-professional.css displays results nicely
```

---

## 📊 Performance Metrics

### Before PHASE 3:

- Form save failures: Unhandled (data lost)
- List loading: Scroll listener, no caching
- Validation: On submit only
- Error recovery: Manual page refresh
- UI/UX: Generic styling

### After PHASE 3:

- Form save failures: Auto-retry 3 times with exponential backoff
- List loading: Intersection Observer with page caching
- Validation: Real-time with visual feedback
- Error recovery: User-friendly Revert button
- UI/UX: Professional ClickUp-level design

---

## 🛡️ Security & Data Protection

### CSRF Protection:

```javascript
// Token cached in CRMOptimisticUI.csrfToken
// Validated on each request
// Prevents cross-site attacks
```

### Request Deduplication:

```javascript
// Track in-flight requests by request ID
// Prevent duplicate submissions
// Server-side validation with request IDs
```

### Soft Delete Support:

```javascript
// Original values always tracked
// Can always revert to previous state
// No permanent data loss
```

---

## 📱 Responsive Breakpoints

### Mobile (< 480px):

- Single-column forms
- Full-width buttons
- Stacked navigation
- Larger touch targets

### Tablet (480px - 768px):

- 2-column forms
- Optimized spacing
- Horizontal nav
- Medium touch targets

### Desktop (> 768px):

- Multi-column layouts
- Full feature set
- Horizontal nav expanded
- Compact mouse targets

---

## ♿ Accessibility Standards

### WCAG Level AA Compliance:

- ✅ Color contrast ratios >= 4.5:1
- ✅ Keyboard navigation support
- ✅ Focus visible indicators
- ✅ ARIA label support
- ✅ Skip link support
- ✅ Form field associations
- ✅ Error message associations
- ✅ Semantic HTML

---

## 🎨 Design System Variables

### Colors:

- Primary: #0066cc
- Success: #27ae60
- Danger: #d9534f
- Warning: #f39c12
- Neutral: #f5f5f5

### Spacing:

- Base unit: 8px (8, 16, 24, 32, 40 px)
- Form field height: 36px
- Button height: 40px
- Input padding: 8px 12px

### Typography:

- Base font: -apple-system, BlinkMacSystemFont, Segoe UI
- Base size: 14px
- Form label: 12px bold
- Headings: 18px-32px

---

## 📚 Dependencies

### Required Libraries:

- Drupal Core (11.3.5)
- jQuery (Drupal bundled)
- Drupal.behaviors (Drupal core)

### New Dependencies:

- None (all vanilla JavaScript + CSS)

### Browser Support:

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Intersection Observer polyfill needed for IE11 (not included)
- ES6 features used (let/const/arrow functions)

---

## 🧪 Testing Checklist

- ✅ Form save with network failure (retry logic)
- ✅ Form validation with invalid data (error feedback)
- ✅ List pagination with scroll (lazy load)
- ✅ Unsaved changes detection (browser warning)
- ✅ Conflict detection (concurrent edits)
- ✅ Responsive design (mobile/tablet/desktop)
- ✅ Dark mode (CSS variables)
- ✅ Accessibility (keyboard + screen reader)
- ✅ Database connection (connectivity verified)
- ✅ System health (Drupal bootstrap successful)

---

## 📞 Support & Customization

### To Customize Colors:

Edit `:root` variables in `crm-ui-professional.css`

### To Change Validation Rules:

Modify `VALIDATION_RULES` in `crm-node-form.js`

### To Adjust Retry Logic:

Change `maxRetries` and `retryDelays` in individual JS files

### To Disable Dark Mode:

Remove `@media (prefers-color-scheme: dark)` from CSS

---

_PHASE 3 Technical Reference - Complete Specifications_
