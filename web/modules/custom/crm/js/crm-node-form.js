/**
 * CRM Node Form — ClickUp-style UX enhancements.
 * - Organizes fields into 2-column grid rows per type
 * - Auto-focuses the title field
 * - Wraps related fields in labeled sections
 */
(function (Drupal, once) {
  "use strict";

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

  // Section labels per type (optional visual dividers)
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

  /**
   * Find a form item wrapper by its data-drupal-selector or field name slug.
   */
  function findFormItem(form, slug) {
    // Try data-drupal-selector on the wrapper div
    const bySelector = form.querySelector(`[data-drupal-selector*="${slug}"]`);
    if (bySelector) {
      // Walk up to the .form-item ancestor or the field wrapper div
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
    // Look for wrapper by class containing the slug (e.g. js-form-item-field-email-0-value)
    const normalized = slug.replace(/-/g, "-");
    const sel = `[data-drupal-selector="edit-${normalized}"], .field--name-${normalized}`;
    const el = region.querySelector(sel);
    if (!el) return null;
    // Get top-level child of region that contains this element
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

      // Create the grid wrapper
      const row = document.createElement("div");
      row.className = "crm-form-row";

      // Insert row before el1
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
      // Also check if el is already inside a crm-form-row (grid pair)
      if (!el) return;
      const target = el.classList.contains("crm-form-row") ? el : el;
      const divider = document.createElement("span");
      divider.className = "crm-form-section-label";
      divider.textContent = label;
      target.parentNode.insertBefore(divider, target);
    });
  }

  Drupal.behaviors.crmNodeForm = {
    attach(context) {
      once("crm-node-form", ".crm-node-form", context).forEach((form) => {
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

        // 4. Smooth scroll to first error on submit failure
        const errors = form.querySelectorAll(".form-item--error");
        if (errors.length > 0) {
          errors[0].scrollIntoView({ behavior: "smooth", block: "center" });
        }
      });
    },
  };
})(Drupal, once);
