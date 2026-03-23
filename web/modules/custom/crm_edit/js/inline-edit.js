/**
 * @file
 * Inline Edit JavaScript for CRM entities.
 */

// Global CRMInlineEdit object for modal functionality
window.CRMInlineEdit = {
  // Cached CSRF token (fetched once per page load)
  _csrfToken: null,

  getCsrfToken: async function () {
    if (!this._csrfToken) {
      const resp = await fetch("/session/token", {
        credentials: "same-origin",
      });
      this._csrfToken = (await resp.text()).trim();
    }
    return this._csrfToken;
  },

  getRowId: function (nid, type) {
    const rowPrefixMap = {
      contact: "contact-row-",
      deal: "deal-row-",
      organization: "org-row-",
      activity: "activity-row-",
    };

    return (rowPrefixMap[type] || type + "-row-") + nid;
  },

  refreshEntityRow: function (nid, type) {
    const rowId = this.getRowId(nid, type);
    const currentRow = document.getElementById(rowId);
    if (!currentRow) {
      return Promise.resolve(false);
    }

    return fetch(window.location.href, {
      credentials: "same-origin",
      cache: "no-store",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newRow = doc.getElementById(rowId);
        if (!newRow) {
          return false;
        }

        currentRow.replaceWith(newRow);
        newRow.style.transition = "background-color 0.5s ease";
        newRow.style.backgroundColor = "#dcfce7";
        setTimeout(() => {
          newRow.style.backgroundColor = "";
        }, 900);

        document.dispatchEvent(new CustomEvent("crm:results-swapped"));
        return true;
      })
      .catch((error) => {
        console.error("Row refresh error:", error);
        return false;
      });
  },

  refreshListSections: function () {
    const resultsWrap = document.getElementById("crm-results-wrap");
    if (!resultsWrap) {
      return Promise.resolve(false);
    }

    return fetch(window.location.href, {
      credentials: "same-origin",
      cache: "no-store",
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("HTTP " + response.status);
        }
        return response.text();
      })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, "text/html");
        const newResultsWrap = doc.getElementById("crm-results-wrap");
        const currentStatsBar = document.querySelector(".stats-bar");
        const newStatsBar = doc.querySelector(".stats-bar");
        const currentCount = document.querySelector(".filter-count");
        const newCount = doc.querySelector(".filter-count");

        if (!newResultsWrap) {
          return false;
        }

        resultsWrap.innerHTML = newResultsWrap.innerHTML;

        if (currentStatsBar && newStatsBar) {
          currentStatsBar.innerHTML = newStatsBar.innerHTML;
        }

        if (currentCount && newCount) {
          currentCount.textContent = newCount.textContent;
        }

        document.dispatchEvent(new CustomEvent("crm:results-swapped"));
        if (typeof lucide !== "undefined") {
          lucide.createIcons();
        }
        return true;
      })
      .catch((error) => {
        console.error("List refresh error:", error);
        return false;
      });
  },

  refreshCurrentView: function (nid, type, data) {
    return this.refreshEntityRow(nid, type).then((refreshedRow) => {
      if (refreshedRow) {
        return true;
      }

      return this.refreshListSections().then((refreshedList) => {
        if (refreshedList) {
          return true;
        }

        const rowEl = document.getElementById(this.getRowId(nid, type));
        if (rowEl) {
          rowEl.style.transition = "background-color 0.5s ease";
          rowEl.style.backgroundColor = "#dcfce7";
          const timeCell = rowEl.querySelector(".td-time");
          if (timeCell) timeCell.textContent = "just now";
          if (data.title) {
            const nameLink = rowEl.querySelector("[class$='-name-link']");
            if (nameLink) nameLink.textContent = data.title;
          }
          return true;
        }

        if (window.location.pathname.startsWith("/node/")) {
          window.location.reload();
          return true;
        }

        return false;
      });
    });
  },

  removeEntityRowOptimistically: function (nid, type) {
    const rowId = this.getRowId(nid, type);
    const row = document.getElementById(rowId);
    if (!row) {
      return false;
    }

    row.style.transition = "opacity 0.2s ease, transform 0.2s ease";
    row.style.opacity = "0";
    row.style.transform = "translateX(8px)";

    setTimeout(() => {
      row.remove();

      // Best-effort quick update for visible result counts.
      const countEl = document.querySelector(".filter-count");
      if (countEl) {
        const text = countEl.textContent || "";
        const m = text.match(/(\d+)/);
        if (m) {
          const oldNum = Number(m[1]);
          if (Number.isFinite(oldNum) && oldNum > 0) {
            countEl.textContent = text.replace(m[1], String(oldNum - 1));
          }
        }
      }
    }, 200);

    return true;
  },

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
      setTimeout(() => {
        overlay.remove();
        // If on a dedicated add page, redirect to the matching list instead of
        // leaving the user staring at the "Loading create form…" background.
        const path = window.location.pathname;
        const addMatch = path.match(/^\/crm\/add\/(\w+)/);
        if (addMatch) {
          const listMap = {
            contact: "/crm/all-contacts",
            deal: "/crm/all-deals",
            organization: "/crm/all-organizations",
            activity: "/crm/all-activities",
          };
          window.location.href = listMap[addMatch[1]] || "/crm/dashboard";
        }
      }, 300);
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

    // Keyboard shortcuts for the edit modal
    const editKeyHandler = (e) => {
      if (e.key === "Escape") {
        document.removeEventListener("keydown", editKeyHandler);
        this.closeModal();
      } else if (
        // Ctrl+Enter or Cmd+Enter anywhere → save
        // Plain Enter when NOT focused inside a textarea → save
        (e.key === "Enter" && (e.ctrlKey || e.metaKey)) ||
        (e.key === "Enter" && document.activeElement?.tagName !== "TEXTAREA")
      ) {
        const activeTag = document.activeElement?.tagName;
        // Don't intercept if a submit/button is focused (browser handles it)
        if (activeTag === "BUTTON" || activeTag === "A") return;
        e.preventDefault();
        document.removeEventListener("keydown", editKeyHandler);
        this.saveModal(form);
      }
    };
    document.addEventListener("keydown", editKeyHandler);
  },

  saveModal: function (form) {
    if (!form) {
      form = document.querySelector("#crm-modal-overlay form");
    }
    if (!form) return;

    const nid = form.dataset.nid;
    const type = form.dataset.type;

    // Detect if form has file inputs with files selected or removed files
    const fileInputs = form.querySelectorAll('input[type="file"]');
    const removedFidsInputs = form.querySelectorAll('.removed-fids-input');
    let hasFiles = false;
    let hasRemovedFiles = false;
    fileInputs.forEach(function (input) {
      if (input.files && input.files.length > 0) hasFiles = true;
    });
    removedFidsInputs.forEach(function (input) {
      if (input.value && input.value.trim() !== '') hasRemovedFiles = true;
    });

    // Show saving state
    const modal = document.querySelector(".crm-modal-container");
    if (modal) {
      modal.classList.add("is-saving");
    }

    this.getCsrfToken().then((csrfToken) => {
      let fetchOptions;

      if (hasFiles || hasRemovedFiles) {
        // Use FormData for multipart upload
        const formData = new FormData(form);
        formData.set('nid', nid);
        formData.set('type', type);
        fetchOptions = {
          method: "POST",
          headers: { "X-CSRF-Token": csrfToken },
          body: formData,
        };
        var saveUrl = "/crm/edit/ajax/save-with-files";
      } else {
        // Use JSON for regular fields (no files)
        const formData = new FormData(form);
        const jsonData = { nid: nid, type: type };
        for (let [key, value] of formData.entries()) {
          jsonData[key] = value;
        }
        fetchOptions = {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken,
          },
          body: JSON.stringify(jsonData),
        };
        var saveUrl = "/crm/edit/ajax/save";
      }

      fetch(saveUrl, fetchOptions)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            const overlay = document.getElementById("crm-modal-overlay");
            if (overlay) overlay.remove();

            this.refreshCurrentView(nid, type, data);

            if (window.CRM && window.CRM.toast) {
              window.CRM.toast("Changes saved successfully!", "success");
            } else {
              localStorage.setItem(
                "crmToast",
                JSON.stringify({
                  message: "Changes saved successfully!",
                  type: "success",
                }),
              );
            }
          } else {
            this.showMessage(data.message || "Error saving changes", "error");
            if (modal) {
              modal.classList.remove("is-saving");
            }
          }
        })
        .catch((error) => {
          console.error("Save error:", error);
          this.showMessage(
            "Failed to save changes. Please try again.",
            "error",
          );
          if (modal) {
            modal.classList.remove("is-saving");
          }
        });
    }); // end getCsrfToken
  },

  removeFileItem: function (fid, fieldName) {
    // Remove the file item from DOM
    var item = document.getElementById('file-item-' + fid);
    if (item) {
      item.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
      item.style.opacity = '0';
      item.style.transform = 'translateX(8px)';
      setTimeout(function () { item.remove(); }, 200);
    }
    // Add fid to removed_fids hidden input
    var form = document.querySelector('#crm-modal-overlay form') || document.getElementById('crm-edit-form');
    if (!form) return;
    var hiddenInput = form.querySelector('input[name="' + fieldName + '__removed_fids"]');
    if (hiddenInput) {
      var current = hiddenInput.value ? hiddenInput.value.split(',').filter(Boolean) : [];
      current.push(String(fid));
      hiddenInput.value = current.join(',');
    }
  },

  showMessage: function (message, type) {
    if (window.CRM && window.CRM.toast) {
      window.CRM.toast(message, type || "success");
    }
  },

  confirmDelete: function (nid, type, title) {
    // Streamlined UX: go straight to explicit typed confirmation.
    this.showDeleteFinalConfirm(nid, type, title);
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

    const proceedAction = () => {
      closeModal();
      setTimeout(() => this.showDeleteWarning(nid, type, title), 300);
    };

    proceedBtn.addEventListener("click", proceedAction);

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });

    const keyHandler = (e) => {
      if (e.key === "Escape") {
        document.removeEventListener("keydown", keyHandler);
        closeModal();
      } else if (e.key === "Enter") {
        document.removeEventListener("keydown", keyHandler);
        proceedAction();
      }
    };
    document.addEventListener("keydown", keyHandler);

    // Focus the proceed button so Enter intent is obvious
    setTimeout(() => proceedBtn.focus(), 50);
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

    const understandAction = () => {
      closeModal();
      setTimeout(() => this.showDeleteFinalConfirm(nid, type, title), 300);
    };

    understandBtn.addEventListener("click", understandAction);

    closeBtn.addEventListener("click", closeModal);

    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) {
        closeModal();
      }
    });

    const keyHandler = (e) => {
      if (e.key === "Escape") {
        document.removeEventListener("keydown", keyHandler);
        closeModal();
      } else if (e.key === "Enter") {
        document.removeEventListener("keydown", keyHandler);
        understandAction();
      }
    };
    document.addEventListener("keydown", keyHandler);

    // Focus the understand button so Enter intent is obvious
    setTimeout(() => understandBtn.focus(), 50);
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

            <div class="delete-confirm-grid">
              <div class="delete-risk-card">
                <h4>
                  <i data-lucide="alert-triangle"></i>
                  Permanent action
                </h4>
                <p>This ${typeLabel.toLowerCase()} and linked references will be removed immediately from active lists.</p>
                <ul>
                  <li><i data-lucide="x-circle"></i> Cannot be undone</li>
                  <li><i data-lucide="database-zap"></i> Database updates instantly</li>
                  <li><i data-lucide="refresh-cw"></i> Dashboard and lists refresh automatically</li>
                </ul>
              </div>

              <div class="final-confirmation">
                <label for="delete-confirm-input">
                  Type exact name to unlock delete:
                  <strong>${title}</strong>
                </label>
                <input 
                  type="text" 
                  id="delete-confirm-input" 
                  class="delete-confirm-input"
                  placeholder="Type exactly: ${title}"
                  autocomplete="off"
                />
                <p class="delete-match-status" aria-live="polite">Waiting for exact match</p>
              </div>
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
    const matchStatus = overlay.querySelector(".delete-match-status");
    const closeBtn = overlay.querySelector(".crm-modal-close");

    const closeModal = () => {
      overlay.classList.add("closing");
      setTimeout(() => overlay.remove(), 300);
    };

    // Check whether the typed/pasted value matches the title
    const checkMatch = () => {
      if (confirmInput.value.trim() === title) {
        deleteBtn.disabled = false;
        deleteBtn.classList.add("enabled");
        if (matchStatus) {
          matchStatus.textContent = "Ready to delete";
          matchStatus.classList.add("is-ready");
        }
      } else {
        deleteBtn.disabled = true;
        deleteBtn.classList.remove("enabled");
        if (matchStatus) {
          matchStatus.textContent = "Waiting for exact match";
          matchStatus.classList.remove("is-ready");
        }
      }
    };

    // "input" covers typing; "paste" fires before value updates so defer by one tick
    confirmInput.addEventListener("input", checkMatch);
    confirmInput.addEventListener("paste", () => setTimeout(checkMatch, 0));

    const deleteAction = () => {
      if (confirmInput.value.trim() === title) {
        this.performDelete(nid, type, title, confirmInput.value.trim());
      }
    };

    deleteBtn.addEventListener("click", deleteAction);

    // Keyboard shortcuts inside the input
    confirmInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !deleteBtn.disabled) {
        e.preventDefault();
        deleteAction();
      } else if (e.key === "Escape") {
        closeModal();
      }
    });

    closeBtn.addEventListener("click", closeModal);

    // Click-outside: use closest() so any transparent sub-element still works
    overlay.addEventListener("click", (e) => {
      if (!e.target.closest(".crm-modal-container")) {
        closeModal();
      }
    });

    // ESC from anywhere on the page (e.g. when input is not focused)
    const escHandler = (e) => {
      if (e.key === "Escape") {
        document.removeEventListener("keydown", escHandler);
        closeModal();
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

    this.getCsrfToken().then((csrfToken) => {
      fetch("/crm/edit/ajax/delete", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfToken,
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
            // Close delete modal immediately
            const overlay =
              document.getElementById("crm-delete-step3") ||
              document.getElementById("crm-delete-step2") ||
              document.getElementById("crm-delete-step1");
            if (overlay) overlay.remove();

            // Show toast immediately (no localStorage needed — we're on the same page)
            if (window.CRM && window.CRM.toast) {
              window.CRM.toast(
                data.message || "Deleted successfully!",
                "success",
              );
            } else {
              localStorage.setItem(
                "crmToast",
                JSON.stringify({
                  message: data.message || "Deleted successfully!",
                  type: "success",
                }),
              );
            }

            const removed = this.removeEntityRowOptimistically(nid, type);

            if (removed) {
              // Keep UX snappy: update immediately, then reconcile in background.
              setTimeout(() => {
                this.refreshListSections();
              }, 250);
            } else {
              this.refreshListSections().then((refreshed) => {
                if (!refreshed) {
                  setTimeout(() => location.reload(), 100);
                }
              });
            }
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
    }); // end getCsrfToken
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
    const cancelBtn = overlay.querySelector(".btn-cancel");
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

    // Keyboard shortcuts for the add modal
    const addKeyHandler = (e) => {
      if (e.key === "Escape") {
        document.removeEventListener("keydown", addKeyHandler);
        this.closeModal();
      } else if (
        (e.key === "Enter" && (e.ctrlKey || e.metaKey)) ||
        (e.key === "Enter" && document.activeElement?.tagName !== "TEXTAREA")
      ) {
        const activeTag = document.activeElement?.tagName;
        if (activeTag === "BUTTON" || activeTag === "A") return;
        e.preventDefault();
        document.removeEventListener("keydown", addKeyHandler);
        this.saveAddModal(form);
      }
    };
    document.addEventListener("keydown", addKeyHandler);
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

    // Send AJAX request (CSRF-protected)
    this.getCsrfToken().then((csrfToken) => {
      fetch("/crm/edit/ajax/create", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": csrfToken,
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
    }); // end getCsrfToken
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
