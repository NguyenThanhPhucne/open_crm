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

  /**
   * Initialize delete buttons and modal functionality.
   */
  Drupal.behaviors.contactsListDelete = {
    attach: function (context) {
      // Delete button click handler.
      jQuery(".crm-contact__delete-btn", context)
        .once("delete-initialized")
        .on("click", function (e) {
          e.preventDefault();
          const nid = jQuery(this).data("nid");
          const contactName = jQuery(this)
            .closest("tr")
            .find(".crm-contact__name")
            .text();

          // Show confirmation modal.
          showDeleteConfirmation(nid, contactName);
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
    jQuery.ajax({
      url: `/api/contacts/${nid}/delete`,
      type: "POST",
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      success: function (response) {
        if (response.status === "success") {
          // Remove row with animation.
          contactRow
            .addClass("crm-contact--deleting")
            .fadeOut(300, function () {
              jQuery(this).remove();

              // Close modal.
              modal.removeClass("crm-modal--visible");

              // Show success notification.
              showNotification(
                "success",
                "Contact deleted",
                "The contact has been removed from your CRM.",
              );

              // Check if table is now empty.
              if (jQuery(".crm-contacts-table tbody tr").length === 0) {
                showEmptyState();
              }
            });
        } else {
          showNotification(
            "error",
            "Error",
            response.message || "Failed to delete contact.",
          );
        }
      },
      error: function (jqXHR, textStatus, errorThrown) {
        let errorMessage = "Failed to delete contact. Please try again.";

        if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
          errorMessage = jqXHR.responseJSON.message;
        }

        showNotification("error", "Error", errorMessage);
      },
      complete: function () {
        // Restore button state.
        confirmBtn.prop("disabled", false).text(originalText);
      },
    });
  }

  /**
   * Show a notification message.
   *
   * @param {string} type - Type: 'success', 'error', 'warning', 'info'.
   * @param {string} title - Notification title.
   * @param {string} message - Notification message.
   */
  function showNotification(type, title, message) {
    const notificationHTML = `
      <div class="crm-notification crm-notification--${type}" role="alert">
        <div class="crm-notification__icon">${getIcon(type)}</div>
        <div class="crm-notification__content">
          <h3 class="crm-notification__title">${title}</h3>
          <p class="crm-notification__message">${message}</p>
        </div>
        <button class="crm-notification__close" aria-label="Close notification">&times;</button>
      </div>
    `;

    const notification = jQuery(notificationHTML);
    jQuery("body").append(notification);

    // Close button.
    notification.find(".crm-notification__close").on("click", function () {
      notification.fadeOut(200, function () {
        jQuery(this).remove();
      });
    });

    // Auto-close after 5 seconds.
    setTimeout(function () {
      notification.fadeOut(200, function () {
        jQuery(this).remove();
      });
    }, 5000);
  }

  /**
   * Show empty state when all contacts are deleted.
   */
  function showEmptyState() {
    jQuery(".crm-contacts-table").fadeOut(300, function () {
      const emptyStateHTML = `
        <div class="crm-empty-state">
          <div class="crm-empty-state__icon">📇</div>
          <h2 class="crm-empty-state__title">No contacts yet</h2>
          <p class="crm-empty-state__message">Get started by adding your first contact.</p>
          <a href="/crm/contacts/add" class="btn btn-primary crm-empty-state__cta">Add New Contact</a>
        </div>
      `;
      jQuery(this).after(emptyStateHTML);
    });
  }

  /**
   * Update relative times (e.g., "2 minutes ago").
   *
   * Formats timestamps to human-readable relative times.
   */
  function updateRelativeTimes() {
    jQuery(".crm-contact__timestamp").each(function () {
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

  /**
   * Get icon for notification type.
   *
   * @param {string} type - Notification type.
   * @returns {string} Icon HTML.
   */
  function getIcon(type) {
    const icons = {
      success: "✓",
      error: "✕",
      warning: "⚠",
      info: "ⓘ",
    };
    return icons[type] || "●";
  }
})(Drupal, jQuery);
