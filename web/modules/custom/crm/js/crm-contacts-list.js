/**
 * @file
 * Contacts List JavaScript - AJAX delete, sorting, and UI enhancements.
 *
 * Handles:
 * - Delete confirmation modal
 * - AJAX deletion with database consistency
 * - Immediate UI removal after successful deletion
 * - Error handling with notifications
 * - Call-to-action for recently updated/new contacts
 */

(function (Drupal, jQuery) {
  "use strict";

  let csrfToken = null;

  function getCsrfToken() {
    if (csrfToken) {
      return Promise.resolve(csrfToken);
    }

    return fetch("/session/token", { credentials: "same-origin" })
      .then((response) => response.text())
      .then((token) => {
        csrfToken = token.trim();
        return csrfToken;
      });
  }

  function refreshContactsWrapper() {
    const currentWrapper = document.querySelector(".crm-contacts-wrapper");
    if (!currentWrapper) {
      return Promise.resolve(false);
    }

    return fetch(window.location.href, { credentials: "same-origin" })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }
        return response.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newWrapper = doc.querySelector(".crm-contacts-wrapper");
        if (!newWrapper) {
          return false;
        }

        currentWrapper.replaceWith(newWrapper);
        Drupal.attachBehaviors(newWrapper);
        return true;
      })
      .catch((error) => {
        console.error("Contacts wrapper refresh error:", error);
        return false;
      });
  }

  /**
   * Initialize delete buttons and modal functionality.
   */
  Drupal.behaviors.contactsListDelete = {
    attach: function (context) {
      // Delete button click handler.
      jQuery(".crm-contact__delete-btn", context).each(function () {
        // Skip if already processed
        if (jQuery(this).data("delete-initialized")) {
          return;
        }
        jQuery(this).data("delete-initialized", true);

        jQuery(this).on("click", function (e) {
          e.preventDefault();
          const nid = jQuery(this).data("nid");
          const contactName =
            jQuery(this).data("name") ||
            jQuery(this).closest("tr").find(".crm-contact-link").text().trim();

          // Show confirmation modal.
          showDeleteConfirmation(nid, contactName);
        });
      });

      // Initialize relative time formatting.
      updateRelativeTimes();
    },
  };

  /**
   * Show delete confirmation modal.
   *
   * @param {number} nid - Node ID to delete.
   * @param {string} contactName - Contact name for display.
   */
  function showDeleteConfirmation(nid, contactName) {
    // Create modal HTML if not already present.
    if (!document.getElementById("DeleteConfirmationModal")) {
      const modalHTML = `
        <div id="DeleteConfirmationModal" class="crm-modal crm-modal--danger" role="dialog" aria-labelledby="DeleteConfirmationTitle">
          <div class="crm-modal__overlay"></div>
          <div class="crm-modal__content">
            <div class="crm-modal__header">
              <h2 id="DeleteConfirmationTitle" class="crm-modal__title">Delete Contact</h2>
              <button class="crm-modal__close-btn" aria-label="Close modal">&times;</button>
            </div>
            <div class="crm-modal__body">
              <p>Are you sure you want to delete <strong id="contact-name-display"></strong>?</p>
              <p class="text-secondary text-sm mt-2">This action cannot be undone. The contact will be permanently removed from your CRM.</p>
            </div>
            <div class="crm-modal__footer">
              <button class="crm-modal__cancel-btn btn btn-secondary" data-action="cancel">Cancel</button>
              <button class="crm-modal__confirm-btn btn btn-danger" data-action="confirm">Delete</button>
            </div>
          </div>
        </div>
      `;
      jQuery("body").append(modalHTML);
      attachModalHandlers();
    }

    // Update modal content and show.
    jQuery("#contact-name-display").text(contactName);
    const modal = jQuery("#DeleteConfirmationModal");
    modal.addClass("crm-modal--visible").data("nid", nid);
    modal.focus();
  }

  /**
   * Attach modal event handlers.
   */
  function attachModalHandlers() {
    const modal = jQuery("#DeleteConfirmationModal");

    // Close button.
    jQuery(".crm-modal__close-btn").on("click", function () {
      modal.removeClass("crm-modal--visible");
    });

    // Cancel button.
    jQuery(".crm-modal__cancel-btn").on("click", function () {
      modal.removeClass("crm-modal--visible");
    });

    // Confirm button.
    jQuery(".crm-modal__confirm-btn").on("click", function () {
      const nid = modal.data("nid");
      deleteContact(nid);
    });

    // Close on overlay click.
    jQuery(".crm-modal__overlay").on("click", function () {
      modal.removeClass("crm-modal--visible");
    });

    // Close on Escape key.
    jQuery(document).on("keydown.modal", function (e) {
      if (e.key === "Escape" && modal.hasClass("crm-modal--visible")) {
        modal.removeClass("crm-modal--visible");
      }
    });
  }

  /**
   * Delete a contact via AJAX.
   *
   * @param {number} nid - Node ID to delete.
   */
  function deleteContact(nid) {
    const modal = jQuery("#DeleteConfirmationModal");
    const contactRow = jQuery(`tr[data-nid="${nid}"]`);

    // Show loading state.
    const confirmBtn = modal.find(".crm-modal__confirm-btn");
    const originalText = confirmBtn.text();
    confirmBtn.prop("disabled", true).text("Deleting...");

    // Send AJAX request to delete the contact.
    getCsrfToken()
      .then(function (token) {
        return jQuery.ajax({
          url: `/api/contacts/${nid}/delete`,
          type: "POST",
          dataType: "json",
          headers: {
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-Token": token,
          },
        });
      })
      .done(function (response) {
        if (response.status === "success") {
          modal.removeClass("crm-modal--visible");

          window.CRM &&
            CRM.toast("Contact deleted — removed from your CRM.", "success");

          refreshContactsWrapper().then(function (refreshed) {
            if (!refreshed) {
              window.location.reload();
            }
          });
        } else {
          window.CRM &&
            CRM.toast(response.message || "Failed to delete contact.", "error");
        }
      })
      .fail(function (jqXHR) {
        let errorMessage = "Failed to delete contact. Please try again.";

        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
          errorMessage = jqXHR.responseJSON.message;
        }

        window.CRM && CRM.toast(errorMessage, "error");
      })
      .always(function () {
        // Restore button state.
        confirmBtn.prop("disabled", false).text(originalText);
      });
  }
  /**
   * Update relative times (e.g., "2 minutes ago").
   *
   * Formats timestamps to human-readable relative times.
   */
  function updateRelativeTimes() {
    jQuery(".crm-timestamp, .crm-contact__timestamp").each(function () {
      const timestamp = jQuery(this).data("timestamp");
      if (timestamp) {
        const relativeTime = getRelativeTime(timestamp);
        jQuery(this).text(relativeTime);
      }
    });
  }

  /**
   * Calculate relative time from timestamp.
   *
   * @param {number} timestamp - Unix timestamp in seconds.
   * @returns {string} Relative time string (e.g., "2 minutes ago").
   */
  function getRelativeTime(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const seconds = now - timestamp;

    if (seconds < 60) {
      return "just now";
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
      return `${minutes}m ago`;
    }

    const hours = Math.floor(seconds / 3600);
    if (hours < 24) {
      return `${hours}h ago`;
    }

    const days = Math.floor(seconds / 86400);
    if (days === 1) {
      return "Yesterday";
    }

    if (days < 7) {
      return `${days}d ago`;
    }

    const weeks = Math.floor(days / 7);
    if (weeks < 4) {
      return `${weeks}w ago`;
    }

    // Format as date for older entries.
    const date = new Date(timestamp * 1000);
    return date.toLocaleDateString("en-US", {
      month: "short",
      day: "numeric",
      year:
        date.getFullYear() !== new Date().getFullYear() ? "numeric" : undefined,
    });
  }
})(Drupal, jQuery);
