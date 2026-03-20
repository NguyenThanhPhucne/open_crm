/**
 * @file
 * Inline Editing for CRM Lists
 *
 * Allows clicking on list cells to edit them directly:
 * - Click to edit
 * - Enter to save, Escape to cancel
 * - Shows validation feedback
 * - AJAX saves without page reload
 *
 * Reduces modal friction - quick edits are now instant.
 */

(function (Drupal, jQuery) {
  "use strict";

  var activeEdit = null; // Track currently editing field

  /**
   * Initialize inline editing for list views.
   */
  Drupal.behaviors.crmInlineEdit = {
    attach: function (context) {
      // Find all list view rows with editable fields
      jQuery("table tbody tr[data-entity-id]", context)
        .once("crm-inline-edit")
        .each(function () {
          initializeRowForEditing(this);
        });
    },
  };

  /**
   * Initialize a table row for inline editing.
   */
  function initializeRowForEditing(row) {
    var $row = jQuery(row);
    var entityId = $row.attr("data-entity-id");
    var entityType = $row.attr("data-entity-type") || "node";

    // Mark editable cells
    $row.find("td[data-field-name]").each(function () {
      var $cell = jQuery(this);
      var fieldName = $cell.attr("data-field-name");

      if (isFieldEditable(fieldName, entityType)) {
        $cell.addClass("is-inline-editable").css("cursor", "pointer");

        // Add click handler
        $cell.on("click", function (e) {
          if (
            activeEdit &&
            activeEdit !== this &&
            !jQuery(activeEdit).closest("td").has(e.target).length
          ) {
            cancelEdit(activeEdit);
          }

          if (!jQuery(this).hasClass("is-editing")) {
            startEdit(this, entityId, entityType, fieldName);
          }
        });

        // Add hover effect
        $cell.on("mouseenter", function () {
          if (!jQuery(this).hasClass("is-editing")) {
            jQuery(this).addClass("is-editable-hover");
          }
        });

        $cell.on("mouseleave", function () {
          jQuery(this).removeClass("is-editable-hover");
        });
      }
    });

    console.log("[CRM Inline Edit] Initialized row: " + entityId);
  }

  /**
   * Check if a field is editable.
   */
  function isFieldEditable(fieldName, entityType) {
    // List of editable fields per entity type
    var editableFields = {
      node: [
        "title",
        "field_email",
        "field_phone",
        "field_organization",
        "field_status",
        "field_stage",
        "field_amount",
        "field_team",
      ],
    };

    var allowed = editableFields[entityType] || [];
    return allowed.indexOf(fieldName) !== -1;
  }

  /**
   * Start inline editing for a cell.
   */
  function startEdit(cell, entityId, entityType, fieldName) {
    var $cell = jQuery(cell);
    var currentValue = $cell.text().trim();
    var fieldType = $cell.attr("data-field-type") || "text";

    activeEdit = cell;
    $cell.addClass("is-editing");

    // Create edit input based on field type
    var $input = createEditInput(fieldType, fieldName, currentValue);

    // Replace cell content
    $cell.html("").append($input);

    // Focus input
    $input.focus();

    // Select all text
    if ($input.is("input[type='text']")) {
      $input.select();
    }

    // Bind keyboard events
    $input.on("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        saveEdit(cell, entityId, entityType, fieldName, $input.val());
      } else if (e.key === "Escape") {
        e.preventDefault();
        cancelEdit(cell, currentValue);
      }
    });

    // Save on blur (click outside)
    $input.on("blur", function () {
      setTimeout(function () {
        if ($cell.hasClass("is-editing")) {
          saveEdit(cell, entityId, entityType, fieldName, $input.val());
        }
      }, 200);
    });

    console.log("[CRM Inline Edit] Started editing: " + fieldName);
  }

  /**
   * Create appropriate input for field type.
   */
  function createEditInput(fieldType, fieldName, value) {
    var $input;

    if (fieldType === "select" || fieldType === "list") {
      $input = jQuery("<select/>");
      // Show loading indicator while options are fetching
      $input.append(jQuery("<option/>").val("").text("Loading…").prop("disabled", true).prop("selected", true));
      $input.prop("disabled", true).css("opacity", "0.6");

      // Load available options asynchronously
      getFieldOptions(fieldName).then(function(options) {
        var currentValue = value;
        $input.empty(); // remove loading option
        jQuery.each(options, function (key, label) {
          $input.append(jQuery("<option/>").val(key).text(label));
        });
        $input.prop("disabled", false).css("opacity", "1");
        // Select the correct option now that they are rendered
        if(currentValue) {
            var match = $input.find("option").filter(function() {
                return jQuery(this).text() === currentValue;
            });
            if(match.length) {
                $input.val(match.val());
            } else {
                $input.val(currentValue);
            }
        }
      });
    } else if (fieldType === "textarea" || fieldType === "text_long") {
      $input = jQuery("<textarea/>").prop("rows", 3).val(value).css({
        width: "100%",
        padding: "0.5rem",
        border: "2px solid #2196f3",
        borderRadius: "4px",
      });
    } else {
      $input = jQuery("<input/>").attr("type", "text").val(value).css({
        width: "100%",
        padding: "0.5rem",
        border: "2px solid #2196f3",
        borderRadius: "4px",
      });
    }

    return $input;
  }

  /**
   * Get available options for a field. Returns a Promise.
   */
  var cachedOptions = {};
  function getFieldOptions(fieldName) {
    if (cachedOptions[fieldName]) {
        return Promise.resolve(cachedOptions[fieldName]);
    }

    if (fieldName === 'field_stage') {
        return fetch('/api/v1/crm/stages', {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error("Failed to fetch stages");
            return response.json();
        })
        .then(function(data) {
            cachedOptions[fieldName] = data;
            return data;
        })
        .catch(function(err) {
            console.error("Error fetching stages:", err);
            // Fallback options
            return {
                prospecting: "Prospecting",
                qualification: "Qualification",
                proposal: "Proposal",
                negotiation: "Negotiation",
                closed_won: "Closed Won",
                closed_lost: "Closed Lost",
            };
        });
    }

    // Static fallback options for other fields
    var fieldOptions = {
      field_status: {
        active: "Active",
        inactive: "Inactive",
        archived: "Archived",
      },
      field_team: {
        sales: "Sales Team",
        support: "Support Team",
        management: "Management",
      },
    };

    return Promise.resolve(fieldOptions[fieldName] || {});
  }

  /**
   * Save edited value via AJAX.
   */
  function saveEdit(cell, entityId, entityType, fieldName, newValue) {
    var $cell = jQuery(cell);
    var $row = $cell.closest('tr');
    var originalValue = $cell.attr('data-original-value') || $cell.text();

    // Show saving state immediately (optimistic)
    $cell.html('<span class="crm-inline-edit__saving">Saving...</span>');

    // Fetch the CSRF token (async, cached)
    getCsrfToken().then(function (csrfToken) {
      return fetch('/api/v1/' + entityType + '/' + entityId + '/' + fieldName, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'X-Csrf-Token': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ value: newValue }),
      });
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('HTTP ' + response.status);
        }
        return response.json();
      })
      .then(function (data) {
        // Success
        var displayValue = data.display_value || newValue;

        // Show success checkmark
        $cell.html(
          '<span class="crm-inline-edit__success">✓</span> <span>' +
            displayValue +
            '</span>',
        );

        // Fade to normal after 1s
        setTimeout(function () {
          $cell.text(displayValue).removeClass('is-editing');
          activeEdit = null;
        }, 1000);

        console.log('[CRM Inline Edit] Saved ' + fieldName + ' = ' + newValue);

        // Trigger row updated event
        $row.trigger('crm.row.updated', [entityId, fieldName, newValue]);
      })
      .catch(function (error) {
        // Error
        console.error('[CRM Inline Edit] Save failed', error);

        // Show error state
        $cell.html('<span class="crm-inline-edit__error">✗ Error saving</span>');

        setTimeout(function () {
          $cell.text(originalValue).removeClass('is-editing');
          activeEdit = null;
        }, 2000);

        // Show error toast
        if (window.CRM && window.CRM.toast) {
          window.CRM.toast('Error saving changes', 'error', 4000);
        }
      });
  }

  /**
   * Cancel editing and restore original value.
   */
  function cancelEdit(cell, originalValue) {
    var $cell = jQuery(cell);

    if (originalValue === undefined) {
      originalValue = $cell.attr("data-original-value");
    }

    $cell.text(originalValue).removeClass("is-editing");
    activeEdit = null;

    console.log("[CRM Inline Edit] Cancelled edit");
  }

  /**
   * Get CSRF token (async, cached — matches crm-shared.js pattern).
   * Tries meta tag first, then falls back to /session/token endpoint.
   */
  var _cachedCsrfToken = null;
  function getCsrfToken() {
    if (_cachedCsrfToken) {
      return Promise.resolve(_cachedCsrfToken);
    }

    // Try meta tag first (fastest path)
    var metaToken = jQuery('meta[name="csrf-token"]').attr('content');
    if (metaToken) {
      _cachedCsrfToken = metaToken;
      return Promise.resolve(metaToken);
    }

    // Fetch from Drupal session/token endpoint (reliable fallback)
    return fetch('/session/token', { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (token) {
        _cachedCsrfToken = token.trim();
        return _cachedCsrfToken;
      })
      .catch(function () {
        return '';
      });
  }
})(Drupal, jQuery);
