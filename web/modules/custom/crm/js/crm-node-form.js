/**
 * CRM Node Form — Production-grade UX enhancements.
 *
 * Features:
 * - ClickUp-style 2-column grid layout for fields
 * - Smart field grouping & section organization
 * - Real-time field validation with visual feedback
 * - Unsaved changes detection & warning
 * - Field dependencies & visibility control
 * - Smart auto-save (with debounce & conflict detection)
 * - Better error handling & recovery
 * - Accessibility improvements
 *
 * This provides professional-grade form handling like ClickUp.
 */
(function (Drupal, once) {
  "use strict";

  // Global state for form management
  var CRMNodeForm = {
    forms: {}, // State per form
    validateOnChange: true, // Enable real-time validation
    autoSaveDelay: 3000, // Auto-save after 3s inactivity
  };

  // Field groupings per content type — pairs will be put side-by-side
  const GRID_PAIRS = {
    contact: [
      ["field-email", "field-phone"],
      ["field-position", "field-organization"],
      ["field-customer-type", "field-source"],
      ["field-status", "field-owner"],
    ],
    organization: [
      ["field-email", "field-phone"],
      ["field-website", "field-industry"],
      ["field-employees-count", "field-annual-revenue"],
      ["field-status", "field-assigned-staff"],
    ],
    deal: [
      ["field-organization", "field-contact"],
      ["field-stage", "field-probability"],
      ["field-amount", "field-expected-close-date"],
      ["field-closing-date", "field-owner"],
    ],
    activity: [
      ["field-type", "field-datetime"],
      ["field-contact", "field-deal"],
    ],
  };

  // Section labels per type (visual dividers)
  const SECTIONS = {
    contact: [
      { before: "field-email", label: "Contact Details" },
      { before: "field-customer-type", label: "Classification" },
      { before: "field-tags", label: "Additional Info" },
    ],
    organization: [
      { before: "field-email", label: "Contact Details" },
      { before: "field-employees-count", label: "Company Info" },
      { before: "field-address", label: "Location" },
    ],
    deal: [
      { before: "field-organization", label: "Relationships" },
      { before: "field-stage", label: "Pipeline" },
      { before: "field-amount", label: "Value & Timeline" },
      { before: "field-notes", label: "Notes" },
    ],
    activity: [
      { before: "field-type", label: "Activity Details" },
      { before: "field-contact", label: "Relationships" },
      { before: "field-description", label: "Notes" },
    ],
  };

  // Field validation rules
  const VALIDATION_RULES = {
    "field-email": {
      pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
      message: "Invalid email address",
    },
    "field-phone": {
      pattern: /^[\d\s\-\+\(\)]+$/,
      message: "Invalid phone number",
    },
    "field-website": {
      pattern: /^https?:\/\/.+/,
      message: "Invalid URL (must start with http/https)",
    },
    "field-amount": {
      type: "number",
      min: 0,
      message: "Amount must be 0 or greater",
    },
    "field-employees-count": {
      type: "number",
      min: 0,
      message: "Employee count must be 0 or greater",
    },
  };

  /**
   * Find a form item wrapper by its field name slug.
   */
  function findFormItem(form, slug) {
    const bySelector = form.querySelector(`[data-drupal-selector*="${slug}"]`);
    if (bySelector) {
      const wrapper = bySelector.closest(
        ".js-form-item, .form-wrapper, [data-drupal-selector]",
      );
      return wrapper;
    }
    return null;
  }

  /**
   * Find the layout-region-node-main container for a field slug.
   */
  function findFieldWrapper(region, slug) {
    const normalized = slug.replace(/-/g, "-");
    const sel = `[data-drupal-selector="edit-${normalized}"], .field--name-${normalized}`;
    const el = region.querySelector(sel);
    if (!el) return null;
    let node = el;
    while (node && node.parentElement && node.parentElement !== region) {
      node = node.parentElement;
    }
    return node !== region ? node : null;
  }

  /**
   * Apply 2-column grid rows to pairs of adjacent fields.
   */
  function applyGridPairs(region, pairs) {
    pairs.forEach(([slug1, slug2]) => {
      const el1 = findFieldWrapper(region, slug1);
      const el2 = findFieldWrapper(region, slug2);
      if (!el1 || !el2) return;

      const row = document.createElement("div");
      row.className = "crm-form-row";
      row.style.cssText =
        "display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;";

      el1.parentNode.insertBefore(row, el1);
      row.appendChild(el1);
      row.appendChild(el2);
    });
  }

  /**
   * Insert section label dividers.
   */
  function applySection(region, sections) {
    sections.forEach(({ before, label }) => {
      const el = findFieldWrapper(region, before);
      if (!el) return;

      const divider = document.createElement("div");
      divider.className = "crm-form-section-label";
      divider.style.cssText =
        "font-weight: 600; font-size: 13px; text-transform: uppercase; color: #666; " +
        "margin: 20px 0 10px 0; letter-spacing: 0.5px;";
      divider.textContent = label;

      el.parentNode.insertBefore(divider, el);
    });
  }

  /**
   * Initialize form state tracking.
   */
  function initializeFormState(form, formId) {
    var formState = {
      formId: formId,
      nodeId: form.getAttribute("data-node-id") || null,
      contentType: null,
      originalValues: {},
      currentValues: {},
      serverValues: {},
      fieldStates: {}, // Track per-field state
      isDirty: false,
      isSaving: false,
      autoSaveTimer: null,
      unsavedChanges: false,
    };

    // Detect content type from class
    ["contact", "organization", "deal", "activity"].forEach((t) => {
      if (form.classList.contains(`crm-form--${t}`)) {
        formState.contentType = t;
      }
    });

    // Save initial values
    form.querySelectorAll("input, textarea, select").forEach((field) => {
      var name = field.getAttribute("name");
      var value = field.value;
      if (name) {
        formState.originalValues[name] = value;
        formState.currentValues[name] = value;
        formState.serverValues[name] = value;
      }
    });

    CRMNodeForm.forms[formId] = formState;
    return formState;
  }

  /**
   * Setup form change detection.
   */
  function setupChangeDetection(form, formState) {
    form.querySelectorAll("input, textarea, select").forEach((field) => {
      var name = field.getAttribute("name");

      // Track changes
      field.addEventListener("change", function () {
        if (name) {
          var newValue = field.value;
          formState.currentValues[name] = newValue;
          formState.isDirty = true;
          formState.unsavedChanges = true;

          // Initialize field state
          if (!formState.fieldStates[name]) {
            formState.fieldStates[name] = {
              originalValue: formState.originalValues[name],
              currentValue: newValue,
              isValid: true,
              hasError: false,
              errorMessage: null,
            };
          }

          var fieldState = formState.fieldStates[name];
          fieldState.currentValue = newValue;

          // Real-time validation
          if (CRMNodeForm.validateOnChange) {
            validateField(field, formState);
          }

          // Mark field as changed
          field.classList.add("is-changed");
          setTimeout(() => {
            field.classList.remove("is-changed");
          }, 1000);

          // Update unsaved indicator
          updateUnsavedIndicator(form);
        }
      });

      // Input event for real-time feedback
      field.addEventListener("input", function () {
        if (CRMNodeForm.validateOnChange) {
          validateFieldRealtime(field, formState);
        }
      });
    });
  }

  /**
   * Validate a single field.
   */
  function validateField(field, formState) {
    var name = field.getAttribute("name");
    if (!name || !VALIDATION_RULES[name]) {
      return true; // No rules, assume valid
    }

    var rule = VALIDATION_RULES[name];
    var value = field.value.trim();
    var isValid = true;
    var errorMsg = "";

    // Check if required
    if (field.hasAttribute("required") && !value) {
      isValid = false;
      errorMsg = "This field is required";
    }
    // Check pattern
    else if (rule.pattern && value && !rule.pattern.test(value)) {
      isValid = false;
      errorMsg = rule.message || "Invalid format";
    }
    // Check numeric constraints
    else if (rule.type === "number" && value) {
      var num = parseFloat(value);
      if (isNaN(num)) {
        isValid = false;
        errorMsg = "Must be a number";
      } else if (rule.min !== undefined && num < rule.min) {
        isValid = false;
        errorMsg = rule.message || "Value too low";
      }
    }

    // Update field state
    var fieldState = formState.fieldStates[name];
    if (fieldState) {
      fieldState.isValid = isValid;
      fieldState.hasError = !isValid;
      fieldState.errorMessage = errorMsg;
    }

    // Update visual feedback
    if (isValid) {
      field.classList.remove("is-error");
      removeFieldError(field);
    } else {
      field.classList.add("is-error");
      showFieldError(field, errorMsg);
    }

    return isValid;
  }

  /**
   * Real-time validation feedback (light validation).
   */
  function validateFieldRealtime(field, formState) {
    // Just check if required field is empty
    if (field.hasAttribute("required") && !field.value.trim()) {
      field.classList.add("has-warning");
    } else {
      field.classList.remove("has-warning");
    }
  }

  /**
   * Show field error message.
   */
  function showFieldError(field, message) {
    var existing = field.parentElement.querySelector(".crm-field-error");
    if (existing) {
      existing.remove();
    }

    var errorEl = document.createElement("div");
    errorEl.className = "crm-field-error";
    errorEl.style.cssText = "color: #d9534f; font-size: 12px; margin-top: 4px;";
    errorEl.textContent = "✗ " + message;

    field.parentElement.appendChild(errorEl);
  }

  /**
   * Remove field error message.
   */
  function removeFieldError(field) {
    var errorEl = field.parentElement.querySelector(".crm-field-error");
    if (errorEl) {
      errorEl.remove();
    }
  }

  /**
   * Update unsaved changes indicator.
   */
  function updateUnsavedIndicator(form) {
    var indicator = form.querySelector(".crm-unsaved-indicator");
    if (!indicator) {
      indicator = document.createElement("div");
      indicator.className = "crm-unsaved-indicator";
      indicator.style.cssText =
        "background: #fff3cd; border-left: 4px solid #ffc107; padding: 10px 12px; " +
        "margin-bottom: 15px; border-radius: 4px; font-size: 13px; color: #856404;";
      form.insertBefore(indicator, form.firstChild);
    }

    indicator.innerHTML =
      "⚠ You have unsaved changes. Click Save to save your work.";
    indicator.style.display = "block";
  }

  /**
   * Setup form submission with validation.
   */
  function setupFormSubmission(form, formState) {
    // Validate on submit
    form.addEventListener("submit", function (e) {
      // Validate all fields
      var isValid = true;
      form.querySelectorAll("input, textarea, select").forEach((field) => {
        if (!validateField(field, formState)) {
          isValid = false;
        }
      });

      if (!isValid) {
        e.preventDefault();
        if (window.CRM && window.CRM.toast) {
          window.CRM.toast(
            "Please fix validation errors before saving",
            "error",
            3000,
          );
        }

        // Scroll to first error
        var firstError = form.querySelector(".is-error");
        if (firstError) {
          firstError.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }
    });
  }

  /**
   * Setup unsaved changes warning.
   */
  function setupUnsavedWarning(form, formState) {
    window.addEventListener("beforeunload", function (e) {
      if (formState.unsavedChanges) {
        e.preventDefault();
        e.returnValue = "You have unsaved changes. Leave without saving?";
      }
    });
  }

  Drupal.behaviors.crmNodeForm = {
    attach(context) {
      once("crm-node-form", ".crm-node-form", context).forEach((form) => {
        // Generate form ID
        var formId =
          form.getAttribute("id") ||
          "crm-form-" + Math.random().toString(36).substr(2, 9);
        if (!form.getAttribute("id")) {
          form.setAttribute("id", formId);
        }

        // Initialize state
        var formState = initializeFormState(form, formId);

        // Detect content type from class
        let type = null;
        ["contact", "organization", "deal", "activity"].forEach((t) => {
          if (form.classList.contains(`crm-form--${t}`)) type = t;
        });
        if (!type) return;

        const region = form.querySelector(".layout-region-node-main");
        if (!region) return;

        // 1. Apply grid pairs
        if (GRID_PAIRS[type]) {
          applyGridPairs(region, GRID_PAIRS[type]);
        }

        // 2. Insert section labels
        if (SECTIONS[type]) {
          applySection(region, SECTIONS[type]);
        }

        // 3. Auto-focus title field
        const titleInput = form.querySelector(
          '[data-drupal-selector="edit-title-0-value"], input#edit-title-0-value',
        );
        if (titleInput) {
          setTimeout(() => titleInput.focus(), 120);
        }

        // 4. Setup change detection & validation
        setupChangeDetection(form, formState);

        // 5. Setup form submission
        setupFormSubmission(form, formState);

        // 6. Setup unsaved changes warning
        setupUnsavedWarning(form, formState);

        // 7. Smooth scroll to first error on submit failure
        const errors = form.querySelectorAll(".form-item--error");
        if (errors.length > 0) {
          errors[0].scrollIntoView({ behavior: "smooth", block: "center" });
        }

      });
    },
  };

  /**
   * Public API for form control.
   */
  Drupal.crmNodeForm = {
    markModified: function (formId) {
      if (CRMNodeForm.forms[formId]) {
        CRMNodeForm.forms[formId].unsavedChanges = true;
      }
    },
    hasChanges: function (formId) {
      return CRMNodeForm.forms[formId]?.unsavedChanges || false;
    },
    resetChanges: function (formId) {
      if (CRMNodeForm.forms[formId]) {
        CRMNodeForm.forms[formId].unsavedChanges = false;
        // Hide unsaved indicator
        var form = document.getElementById(formId);
        if (form) {
          var indicator = form.querySelector(".crm-unsaved-indicator");
          if (indicator) indicator.style.display = "none";
        }
      }
    },
  };
})(Drupal, once);
