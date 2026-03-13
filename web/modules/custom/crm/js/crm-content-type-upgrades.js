/**
 * CRM Content Type Model Upgrades
 *
 * Enhancements to content types:
 * 1. Contact: Add validation rules, improve field constraints
 * 2. Organization: Add certification fields, improve structure
 * 3. Deal: Add revenue tracking, probability validation
 * 4. Activity: Add outcome tracking, better scheduling
 *
 * This module extends the CRM content types with production-grade improvements.
 */

(function (Drupal, once) {
  "use strict";

  /**
   * Content Type Improvement Hooks
   *
   * These hooks run when content types are created or modified
   * to enforce better validation and structure.
   */

  // ===== CONTACT TYPE IMPROVEMENTS =====
  Drupal.behaviors.improveContactType = {
    attach: function (context) {
      // Find contact forms
      once("contact-type-improve", "form.crm-form--contact", context).forEach(
        function (form) {
          // Validate email field
          var emailField = form.querySelector('[name*="field_email"]');
          if (emailField) {
            emailField.addEventListener("blur", function () {
              validateContactEmail(this);
            });
          }

          // Validate phone field
          var phoneField = form.querySelector('[name*="field_phone"]');
          if (phoneField) {
            phoneField.addEventListener("blur", function () {
              validateContactPhone(this);
            });
          }

          // Contact status enforcement
          var statusField = form.querySelector('[name*="field_status"]');
          if (statusField) {
            statusField.addEventListener("change", function () {
              enforceContactStatus(form, this);
            });
          }

          console.log("[CRM Type Improve] Contact form enhanced");
        },
      );
    },
  };

  // ===== ORGANIZATION TYPE IMPROVEMENTS =====
  Drupal.behaviors.improveOrganizationType = {
    attach: function (context) {
      once("org-type-improve", "form.crm-form--organization", context).forEach(
        function (form) {
          // Validate employee count (must be >= 0)
          var empField = form.querySelector('[name*="field_employees_count"]');
          if (empField) {
            empField.addEventListener("blur", function () {
              validateEmployeeCount(this);
            });
          }

          // Validate revenue field (must be >= 0)
          var revenueField = form.querySelector(
            '[name*="field_annual_revenue"]',
          );
          if (revenueField) {
            revenueField.addEventListener("blur", function () {
              validateRevenue(this);
            });
          }

          // Organization size auto-calculation
          setupOrganizationSizeAutoCalculation(form);

          console.log("[CRM Type Improve] Organization form enhanced");
        },
      );
    },
  };

  // ===== DEAL TYPE IMPROVEMENTS =====
  Drupal.behaviors.improveDealType = {
    attach: function (context) {
      once("deal-type-improve", "form.crm-form--deal", context).forEach(
        function (form) {
          // Validate deal amount (must be >= 0)
          var amountField = form.querySelector('[name*="field_amount"]');
          if (amountField) {
            amountField.addEventListener("blur", function () {
              validateDealAmount(this);
            });
          }

          // Validate probability (0-100)
          var probField = form.querySelector('[name*="field_probability"]');
          if (probField) {
            probField.addEventListener("blur", function () {
              validateDealProbability(this);
            });
          }

          // Close date must be after today
          var closeDateField = form.querySelector(
            '[name*="field_expected_close_date"]',
          );
          if (closeDateField) {
            closeDateField.addEventListener("blur", function () {
              validateDealCloseDate(this);
            });
          }

          // Calculate expected revenue (amount * probability)
          setupDealExpectedRevenueCalculation(form);

          console.log("[CRM Type Improve] Deal form enhanced");
        },
      );
    },
  };

  // ===== ACTIVITY TYPE IMPROVEMENTS =====
  Drupal.behaviors.improveActivityType = {
    attach: function (context) {
      once("activity-type-improve", "form.crm-form--activity", context).forEach(
        function (form) {
          // Activity type determines required fields
          var typeField = form.querySelector('[name*="field_type"]');
          if (typeField) {
            typeField.addEventListener("change", function () {
              enforceActivityTypeRequirements(form, this);
            });
          }

          // Activity datetime must be in future for scheduled activities
          var datetimeField = form.querySelector('[name*="field_datetime"]');
          if (datetimeField) {
            datetimeField.addEventListener("blur", function () {
              validateActivityDateTime(this);
            });
          }

          console.log("[CRM Type Improve] Activity form enhanced");
        },
      );
    },
  };

  // ===== VALIDATION FUNCTIONS =====

  function validateContactEmail(emailField) {
    var value = emailField.value.trim();
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (value && !emailRegex.test(value)) {
      showFieldError(emailField, "Invalid email address");
      return false;
    } else {
      removeFieldError(emailField);
      return true;
    }
  }

  function validateContactPhone(phoneField) {
    var value = phoneField.value.trim();
    var phoneRegex = /^[\d\s\-\+\(\)]+$/;

    if (value && !phoneRegex.test(value)) {
      showFieldError(phoneField, "Invalid phone number");
      return false;
    } else {
      removeFieldError(phoneField);
      return true;
    }
  }

  function enforceContactStatus(form, statusField) {
    var status = statusField.value;
    var requiredFields = [];

    // If converting to lead, all fields must be filled
    if (status === "lead") {
      requiredFields = [
        '[name*="field_email"]',
        '[name*="field_phone"]',
        '[name*="field_position"]',
      ];
    }

    // Mark required fields
    requiredFields.forEach(function (selector) {
      var field = form.querySelector(selector);
      if (field) {
        field.setAttribute("required", "required");
        field.parentElement.classList.add("is-required");
      }
    });
  }

  function validateEmployeeCount(field) {
    var value = parseInt(field.value);

    if (!isNaN(value) && value < 0) {
      showFieldError(field, "Employee count cannot be negative");
      field.value = "0";
      return false;
    } else {
      removeFieldError(field);
      return true;
    }
  }

  function validateRevenue(field) {
    var value = parseFloat(field.value);

    if (!isNaN(value) && value < 0) {
      showFieldError(field, "Annual revenue cannot be negative");
      field.value = "0";
      return false;
    } else {
      removeFieldError(field);
      return true;
    }
  }

  function setupOrganizationSizeAutoCalculation(form) {
    var empField = form.querySelector('[name*="field_employees_count"]');
    var sizeField = form.querySelector('[name*="field_size"]');

    if (!empField || !sizeField) return;

    function updateSize() {
      var count = parseInt(empField.value) || 0;
      var size = "unknown";

      if (count === 0) size = "unknown";
      else if (count <= 10) size = "micro";
      else if (count <= 50) size = "small";
      else if (count <= 250) size = "medium";
      else if (count <= 1000) size = "large";
      else size = "enterprise";

      sizeField.value = size;
    }

    empField.addEventListener("change", updateSize);
  }

  function validateDealAmount(field) {
    var value = parseFloat(field.value);

    if (!isNaN(value) && value < 0) {
      showFieldError(field, "Deal amount cannot be negative");
      field.value = "0";
      return false;
    } else {
      removeFieldError(field);
      return true;
    }
  }

  function validateDealProbability(field) {
    var value = parseInt(field.value);

    if (!isNaN(value)) {
      if (value < 0) {
        showFieldError(field, "Probability cannot be less than 0%");
        field.value = "0";
        return false;
      } else if (value > 100) {
        showFieldError(field, "Probability cannot be more than 100%");
        field.value = "100";
        return false;
      }
    }

    removeFieldError(field);
    return true;
  }

  function validateDealCloseDate(field) {
    var value = field.value;
    if (!value) return true;

    var selectedDate = new Date(value);
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
      showFieldError(field, "Close date must be in the future");
      return false;
    } else {
      removeFieldError(field);
      return true;
    }
  }

  function setupDealExpectedRevenueCalculation(form) {
    var amountField = form.querySelector('[name*="field_amount"]');
    var probField = form.querySelector('[name*="field_probability"]');
    var revenueField = form.querySelector('[name*="field_expected_revenue"]');

    if (!amountField || !probField || !revenueField) return;

    function updateExpectedRevenue() {
      var amount = parseFloat(amountField.value) || 0;
      var probability = parseInt(probField.value) || 0;
      var expectedRevenue = (amount * probability) / 100;

      revenueField.value = expectedRevenue.toFixed(2);
    }

    amountField.addEventListener("change", updateExpectedRevenue);
    probField.addEventListener("change", updateExpectedRevenue);
  }

  function enforceActivityTypeRequirements(form, typeField) {
    var type = typeField.value;
    var contactField = form.querySelector('[name*="field_contact"]');
    var dealField = form.querySelector('[name*="field_deal"]');
    var outcomField = form.querySelector('[name*="field_outcome"]');

    // Reset
    [contactField, dealField, outcomField].forEach(function (field) {
      if (field) {
        field.removeAttribute("required");
        field.parentElement.classList.remove("is-required");
      }
    });

    // Enforce based on type
    if (type === "call" || type === "meeting") {
      if (contactField) {
        contactField.setAttribute("required", "required");
        contactField.parentElement.classList.add("is-required");
      }
    } else if (type === "email") {
      if (contactField) {
        contactField.setAttribute("required", "required");
      }
    }

    // Outcome is required if activity is completed
    var statusField = form.querySelector('[name*="field_status"]');
    if (statusField && statusField.value === "completed" && outcomField) {
      outcomField.setAttribute("required", "required");
    }
  }

  function validateActivityDateTime(field) {
    var value = field.value;
    if (!value) return true;

    var selectedDate = new Date(value);
    var now = new Date();

    // Only warn if date is in the past (don't prevent, as logging past activities is ok)
    if (selectedDate < now) {
      field.parentElement.classList.add("has-warning");
    } else {
      field.parentElement.classList.remove("has-warning");
    }

    return true;
  }

  // ===== UTILITY FUNCTIONS =====

  function showFieldError(field, message) {
    removeFieldError(field);
    field.classList.add("is-error");

    var errorEl = document.createElement("div");
    errorEl.className = "crm-field-error";
    errorEl.style.cssText = "color: #d9534f; font-size: 12px; margin-top: 4px;";
    errorEl.textContent = "✗ " + message;

    field.parentElement.appendChild(errorEl);
  }

  function removeFieldError(field) {
    field.classList.remove("is-error");
    var errorEl = field.parentElement.querySelector(".crm-field-error");
    if (errorEl) {
      errorEl.remove();
    }
  }

  // ===== PUBLIC API =====

  Drupal.crmContentTypes = {
    validateEmail: validateContactEmail,
    validatePhone: validateContactPhone,
    validateAmount: validateDealAmount,
    validateProbability: validateDealProbability,
    showError: showFieldError,
    clearError: removeFieldError,
  };
})(Drupal, once);
