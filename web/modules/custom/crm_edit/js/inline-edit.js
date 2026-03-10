/**
 * @file
 * Inline Edit JavaScript for CRM entities.
 */

// Check if there's a pending toast from a previous page reload
window.addEventListener("DOMContentLoaded", function () {
  const pendingToast = localStorage.getItem("crmToast");
  if (pendingToast) {
    try {
      const toastData = JSON.parse(pendingToast);
      localStorage.removeItem("crmToast");
      // Show the toast after page is fully loaded
      setTimeout(() => {
        window.CRMInlineEdit.showMessage(toastData.message, toastData.type);
      }, 300);
    } catch (e) {
      console.error("Error parsing toast data:", e);
    }
  }
});

// Global CRMInlineEdit object for modal functionality
window.CRMInlineEdit = {
  openModal: function (nid, type) {
    // Show loading overlay
    const loadingHtml =
      '<div class="crm-modal-overlay" id="crm-modal-overlay"><div class="crm-modal-loading"><div class="spinner"></div><p>Loading...</p></div></div>';
    document.body.insertAdjacentHTML("beforeend", loadingHtml);

    // Fetch modal form via AJAX
    fetch("/crm/edit/modal/form?nid=" + nid + "&type=" + type)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Replace loading with actual modal
          const overlay = document.getElementById("crm-modal-overlay");
          overlay.innerHTML = data.html;

          // Initialize Lucide icons in modal
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }

          // Setup modal close handlers
          this.setupModalHandlers();
        } else {
          alert("Error loading form: " + (data.message || "Unknown error"));
          this.closeModal();
        }
      })
      .catch((error) => {
        console.error("Modal load error:", error);
        alert("Failed to load edit form. Please try again.");
        this.closeModal();
      });
  },

  closeModal: function () {
    const overlay = document.getElementById("crm-modal-overlay");
    if (overlay) {
      overlay.classList.add("closing");
      setTimeout(() => overlay.remove(), 300);
    }
  },

  setupModalHandlers: function () {
    const overlay = document.getElementById("crm-modal-overlay");
    if (!overlay) return;

    // Close on overlay click
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        this.closeModal();
      }
    });

    // Close button
    const closeBtn = overlay.querySelector(".crm-modal-close");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => this.closeModal());
    }

    // Cancel button
    const cancelBtn = overlay.querySelector(".btn-cancel");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => this.closeModal());
    }

    // Form submission
    const form = overlay.querySelector(".crm-modal-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.saveModal(form);
      });
    }

    // ESC key to close
    const escHandler = (e) => {
      if (e.key === "Escape") {
        this.closeModal();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  },

  saveModal: function (form) {
    if (!form) {
      form = document.querySelector("#crm-modal-overlay form");
    }
    if (!form) return;

    // Collect form data
    const formData = new FormData(form);
    const nid = form.dataset.nid;
    const type = form.dataset.type;

    // Convert FormData to JSON object
    const jsonData = {
      nid: nid,
      type: type,
    };

    // Add all form fields to JSON
    for (let [key, value] of formData.entries()) {
      jsonData[key] = value;
    }

    // Show saving state
    const modal = document.querySelector(".crm-modal-container");
    if (modal) {
      modal.classList.add("is-saving");
    }

    // Submit via AJAX with JSON
    fetch("/crm/edit/ajax/save", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(jsonData),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Save toast message to localStorage and reload
          localStorage.setItem(
            "crmToast",
            JSON.stringify({
              message: "Changes saved successfully!",
              type: "success",
            }),
          );
          this.closeModal();

          // Reload page immediately to show updated data
          setTimeout(() => location.reload(), 100);
        } else {
          // Show error message (don't reload if there's an error)
          this.showMessage(data.message || "Error saving changes", "error");
          if (modal) {
            modal.classList.remove("is-saving");
          }
        }
      })
      .catch((error) => {
        console.error("Save error:", error);
        this.showMessage("Failed to save changes. Please try again.", "error");
        if (modal) {
          modal.classList.remove("is-saving");
        }
      });
  },

  showMessage: function (message, type) {
    // Determine icon based on message type
    const iconMap = {
      success: "check-circle",
      error: "alert-circle",
      warning: "alert-triangle",
      info: "info",
    };

    const icon = iconMap[type] || "info";

    const messageHtml = `
      <div class="crm-toast crm-toast-${type}">
        <div class="crm-toast-content">
          <svg class="crm-toast-icon" data-lucide="${icon}" width="20" height="20"></svg>
          <span class="crm-toast-message">${message}</span>
        </div>
        <button class="crm-toast-close" type="button" aria-label="Close notification">
          <svg data-lucide="x" width="16" height="16"></svg>
        </button>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", messageHtml);

    const toastEl = document.querySelector(".crm-toast:last-child");

    // Initialize Lucide icons in toast
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

    // Trigger animation
    setTimeout(() => {
      toastEl.classList.add("show");
    }, 10);

    // Close button handler
    const closeBtn = toastEl.querySelector(".crm-toast-close");
    const removeToast = () => {
      toastEl.classList.add("closing");
      setTimeout(() => {
        if (toastEl.parentNode) {
          toastEl.remove();
        }
      }, 1000); // 1s to match closing animation duration
    };

    closeBtn.addEventListener("click", removeToast);

    // Auto dismiss after 2 seconds
    const dismissTime = 2000;
    setTimeout(() => {
      if (toastEl.parentNode) {
        removeToast();
      }
    }, dismissTime);
  },

  confirmDelete: function (nid, type, title) {
    const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);

    // Step 1: Initial confirmation
    const step1Html = `
      <div class="crm-modal-overlay crm-delete-overlay" id="crm-delete-step1">
        <div class="crm-modal-container crm-delete-modal delete-step1">
          <div class="crm-modal-header">
            <h2>
              <i data-lucide="trash-2"></i>
              Delete ${typeLabel}
            </h2>
            <button class="crm-modal-close" type="button">
              <i data-lucide="x"></i>
            </button>
          </div>
          
          <div class="crm-modal-body">
            <div class="delete-entity-info">
              <h3>${title} <span class="entity-type-badge">${typeLabel}</span></h3>
            </div>
          </div>
          
          <div class="crm-modal-footer delete-footer-single">
            <button type="button" class="btn-proceed-delete">
              <i data-lucide="alert-circle"></i>
              <span>I want to delete this ${type}</span>
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", step1Html);

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

    this.setupDeleteStep1Handlers(nid, type, title);
  },

  setupDeleteStep1Handlers: function (nid, type, title) {
    const overlay = document.getElementById("crm-delete-step1");
    if (!overlay) return;

    const proceedBtn = overlay.querySelector(".btn-proceed-delete");
    const closeBtn = overlay.querySelector(".crm-modal-close");

    const closeModal = () => {
      overlay.classList.add("closing");
      setTimeout(() => overlay.remove(), 300);
    };

    proceedBtn.addEventListener("click", () => {
      closeModal();
      setTimeout(() => this.showDeleteWarning(nid, type, title), 300);
    });

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });

    const escHandler = (e) => {
      if (e.key === "Escape") {
        closeModal();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  },

  showDeleteWarning: function (nid, type, title) {
    const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);

    // Step 2: Warning modal
    const step2Html = `
      <div class="crm-modal-overlay crm-delete-overlay" id="crm-delete-step2">
        <div class="crm-modal-container crm-delete-modal delete-step2">
          <div class="crm-modal-header crm-delete-header">
            <h2>
              <i data-lucide="alert-triangle"></i>
              Delete ${typeLabel}
            </h2>
            <button class="crm-modal-close" type="button">
              <i data-lucide="x"></i>
            </button>
          </div>
          
          <div class="crm-modal-body">
            <div class="delete-entity-info">
              <h3>${title} <span class="entity-type-badge">${typeLabel}</span></h3>
            </div>
            
            <div class="critical-warning">
              <div class="warning-header">
                <i data-lucide="alert-octagon"></i>
                <h3>Unexpected bad things will happen if you don't read this!</h3>
              </div>
              <div class="warning-content">
                <p>This will permanently delete <strong>${title}</strong> including:</p>
                <ul>
                  <li><i data-lucide="x-circle"></i> All field data and content</li>
                  <li><i data-lucide="x-circle"></i> Associated references and relationships</li>
                  <li><i data-lucide="x-circle"></i> Activity history and logs</li>
                  <li><i data-lucide="x-circle"></i> Any attached files or documents</li>
                </ul>
                <p class="final-warning">This action <strong>cannot be undone</strong>. All data will be removed.</p>
              </div>
            </div>
          </div>
          
          <div class="crm-modal-footer delete-footer-single">
            <button type="button" class="btn-understand-delete">
              <i data-lucide="check-circle"></i>
              <span>I have read and understand these effects</span>
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", step2Html);

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

    this.setupDeleteStep2Handlers(nid, type, title);
  },

  setupDeleteStep2Handlers: function (nid, type, title) {
    const overlay = document.getElementById("crm-delete-step2");
    if (!overlay) return;

    const understandBtn = overlay.querySelector(".btn-understand-delete");
    const closeBtn = overlay.querySelector(".crm-modal-close");

    const closeModal = () => {
      overlay.classList.add("closing");
      setTimeout(() => overlay.remove(), 300);
    };

    understandBtn.addEventListener("click", () => {
      closeModal();
      setTimeout(() => this.showDeleteFinalConfirm(nid, type, title), 300);
    });

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });

    const escHandler = (e) => {
      if (e.key === "Escape") {
        closeModal();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  },

  showDeleteFinalConfirm: function (nid, type, title) {
    const typeLabel = type.charAt(0).toUpperCase() + type.slice(1);

    // Step 3: Final confirmation with text input
    const step3Html = `
      <div class="crm-modal-overlay crm-delete-overlay" id="crm-delete-step3">
        <div class="crm-modal-container crm-delete-modal delete-step3">
          <div class="crm-modal-header crm-delete-header">
            <h2>
              <i data-lucide="shield-alert"></i>
              Delete ${typeLabel}
            </h2>
            <button class="crm-modal-close" type="button">
              <i data-lucide="x"></i>
            </button>
          </div>
          
          <div class="crm-modal-body">
            <div class="delete-entity-info">
              <h3>${title} <span class="entity-type-badge">${typeLabel}</span></h3>
            </div>
            
            <div class="final-confirmation">
              <label for="delete-confirm-input">
                To confirm deletion, type "<strong>${title}</strong>" below:
              </label>
              <input 
                type="text" 
                id="delete-confirm-input" 
                class="delete-confirm-input"
                placeholder="Type the ${type} name to confirm"
                autocomplete="off"
              />
            </div>
          </div>
          
          <div class="crm-modal-footer delete-footer-single">
            <button type="button" class="btn-delete-final" disabled>
              Delete ${typeLabel}
            </button>
          </div>
        </div>
      </div>
    `;

    document.body.insertAdjacentHTML("beforeend", step3Html);

    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

    this.setupDeleteStep3Handlers(nid, type, title);
  },

  setupDeleteStep3Handlers: function (nid, type, title) {
    const overlay = document.getElementById("crm-delete-step3");
    if (!overlay) return;

    const confirmInput = overlay.querySelector("#delete-confirm-input");
    const deleteBtn = overlay.querySelector(".btn-delete-final");
    const closeBtn = overlay.querySelector(".crm-modal-close");

    // Enable delete button when name matches
    confirmInput.addEventListener("input", (e) => {
      if (e.target.value.trim() === title) {
        deleteBtn.disabled = false;
        deleteBtn.classList.add("enabled");
      } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove("enabled");
      }
    });

    // Handle delete action
    deleteBtn.addEventListener("click", () => {
      if (confirmInput.value.trim() === title) {
        this.performDelete(nid, type, title, confirmInput.value.trim());
      }
    });

    const closeModal = () => {
      overlay.classList.add("closing");
      setTimeout(() => overlay.remove(), 300);
    };

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });

    const escHandler = (e) => {
      if (e.key === "Escape") {
        closeModal();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);

    // Focus on input
    setTimeout(() => confirmInput.focus(), 100);
  },

  performDelete: function (nid, type, title, confirmation) {
    const modal = document.querySelector(".crm-delete-modal");
    if (modal) {
      modal.classList.add("is-deleting");
    }

    fetch("/crm/edit/ajax/delete", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        nid: nid,
        type: type,
        confirmation: confirmation || title,
      }),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Save toast message to localStorage and reload
          localStorage.setItem(
            "crmToast",
            JSON.stringify({
              message: data.message || "Deleted successfully!",
              type: "success",
            }),
          );

          // Close delete modal
          const overlay =
            document.getElementById("crm-delete-step3") ||
            document.getElementById("crm-delete-step2") ||
            document.getElementById("crm-delete-step1");
          if (overlay) {
            overlay.remove();
          }

          // Reload page immediately to show updated data
          setTimeout(() => location.reload(), 100);
        } else {
          this.showMessage(data.message || "Error deleting", "error");
          if (modal) {
            modal.classList.remove("is-deleting");
          }
        }
      })
      .catch((error) => {
        console.error("Delete error:", error);
        this.showMessage("Failed to delete. Please try again.", "error");
        if (modal) {
          modal.classList.remove("is-deleting");
        }
      });
  },

  openAddModal: function (type) {
    // Show loading overlay
    const loadingHtml =
      '<div class="crm-modal-overlay" id="crm-modal-overlay"><div class="crm-modal-loading"><div class="spinner"></div><p>Loading create form...</p></div></div>';
    document.body.insertAdjacentHTML("beforeend", loadingHtml);

    // Fetch create form via AJAX
    fetch("/crm/edit/ajax/create/form?type=" + type)
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Replace loading with actual modal
          const overlay = document.getElementById("crm-modal-overlay");
          overlay.innerHTML = data.html;

          // Initialize Lucide icons in modal
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }

          // Setup modal close handlers
          this.setupAddModalHandlers();
        } else {
          alert(
            "Error loading create form: " + (data.message || "Unknown error"),
          );
          this.closeModal();
        }
      })
      .catch((error) => {
        console.error("Modal load error:", error);
        alert("Failed to load create form. Please try again.");
        this.closeModal();
      });
  },

  setupAddModalHandlers: function () {
    const overlay = document.getElementById("crm-modal-overlay");
    if (!overlay) return;

    // Close on overlay click
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        this.closeModal();
      }
    });

    // Close buttons
    const closeBtns = overlay.querySelectorAll(".crm-modal-close");
    closeBtns.forEach((btn) => {
      btn.addEventListener("click", () => this.closeModal());
    });

    // Cancel button
    const cancelBtn = overlay.querySelector(".btn-secondary");
    if (cancelBtn) {
      cancelBtn.addEventListener("click", () => this.closeModal());
    }

    // Form submission
    const form = overlay.querySelector(".crm-modal-form");
    if (form) {
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        this.saveAddModal(form);
      });
    }

    // ESC key to close
    const escHandler = (e) => {
      if (e.key === "Escape") {
        this.closeModal();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);
  },

  saveAddModal: function (form) {
    if (!form) {
      form = document.querySelector("#crm-modal-overlay .add-form");
    }
    if (!form) return;

    // Clear previous error messages
    form.querySelectorAll(".field-error").forEach((el) => el.remove());
    const statusDiv = form.querySelector(".save-status");
    if (statusDiv) {
      statusDiv.className = "save-status";
      statusDiv.textContent = "";
    }

    // Collect form data
    const formData = new FormData(form);
    const type = form.dataset.type;
    const data = { type: type };

    formData.forEach((value, key) => {
      data[key] = value;
    });

    // Show saving state
    const saveBtn = form.querySelector(".save-btn");
    const originalBtnHtml = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i data-lucide="loader"></i> <span>Creating...</span>';
    saveBtn.disabled = true;
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }

    // Send AJAX request
    fetch("/crm/edit/ajax/create", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(data),
    })
      .then((response) => response.json())
      .then((result) => {
        if (result.success) {
          if (statusDiv) {
            statusDiv.className = "save-status success";
            statusDiv.innerHTML =
              '<i data-lucide="check-circle"></i> ' +
              (result.message || "Created successfully!");
            if (typeof lucide !== "undefined") {
              lucide.createIcons();
            }
          }

          // Save toast message to localStorage and redirect
          localStorage.setItem(
            "crmToast",
            JSON.stringify({
              message: result.message || "Created successfully!",
              type: "success",
            }),
          );

          // Close modal and redirect immediately
          setTimeout(() => {
            this.closeModal();
            if (result.nid) {
              // Redirect to new entity or reload page
              window.location.href = "/node/" + result.nid;
            } else {
              location.reload();
            }
          }, 100);
        } else {
          // Show error
          if (statusDiv) {
            statusDiv.className = "save-status error";
            statusDiv.innerHTML =
              '<i data-lucide="alert-circle"></i> ' +
              (result.message || "Error creating content");
            if (typeof lucide !== "undefined") {
              lucide.createIcons();
            }
          }

          // Restore button
          saveBtn.innerHTML = originalBtnHtml;
          saveBtn.disabled = false;
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }

          // Show field errors if any
          if (result.errors) {
            Object.keys(result.errors).forEach((fieldName) => {
              const fieldWrapper = form
                .querySelector(`[name="${fieldName}"]`)
                ?.closest(".form-field");
              if (fieldWrapper) {
                const errorDiv = document.createElement("div");
                errorDiv.className = "field-error";
                errorDiv.textContent = result.errors[fieldName];
                fieldWrapper.appendChild(errorDiv);
              }
            });
          }
        }
      })
      .catch((error) => {
        console.error("Create error:", error);
        if (statusDiv) {
          statusDiv.className = "save-status error";
          statusDiv.innerHTML =
            '<i data-lucide="alert-circle"></i> Failed to create. Please try again.';
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }
        }
        // Restore button
        saveBtn.innerHTML = originalBtnHtml;
        saveBtn.disabled = false;
      });
  },
};

(function ($, Drupal, drupalSettings) {
  "use strict";

  Drupal.behaviors.crmInlineEdit = {
    attach: function (context, settings) {
      // Initialize Lucide icons for edit buttons
      if (typeof lucide !== "undefined") {
        // Delay initialization to ensure DOM is ready
        setTimeout(function () {
          lucide.createIcons();
        }, 100);
      }

      // Event delegation for CRM action buttons
      $(context).on("click", ".crm-edit-action", function () {
        const nid = $(this).data("nid");
        const bundle = $(this).data("bundle");
        if (window.CRMInlineEdit && window.CRMInlineEdit.openModal) {
          window.CRMInlineEdit.openModal(nid, bundle);
        }
      });

      $(context).on("click", ".crm-delete-action", function () {
        const nid = $(this).data("nid");
        const bundle = $(this).data("bundle");
        const title = $(this).data("title");
        if (window.CRMInlineEdit && window.CRMInlineEdit.confirmDelete) {
          window.CRMInlineEdit.confirmDelete(nid, bundle, title);
        }
      });

      // Auto-save functionality (optional)
      $(
        ".crm-edit-form input, .crm-edit-form select, .crm-edit-form textarea",
        context,
      ).each(function () {
        // Skip if already processed
        if ($(this).data("crm-autosave")) {
          return;
        }
        $(this).data("crm-autosave", true);

        $(this).on("change", function () {
          // Mark form as dirty
          $(this).closest("form").addClass("has-changes");
        });
      });

      // Warn before leaving with unsaved changes
      $(".crm-edit-form", context).each(function () {
        // Skip if already processed
        if ($(this).data("crm-leave-warning")) {
          return;
        }
        $(this).data("crm-leave-warning", true);

        const form = this;

        window.addEventListener("beforeunload", function (e) {
          if (
            $(form).hasClass("has-changes") &&
            !$(form).hasClass("is-saving")
          ) {
            e.preventDefault();
            e.returnValue = "";
            return "You have unsaved changes. Are you sure you want to leave?";
          }
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
