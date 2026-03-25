/**
 * @file
 * Inline Edit JavaScript for CRM entities.
 */

// Global CRMInlineEdit object for modal functionality
globalThis.CRMInlineEdit = {
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

    return fetch(globalThis.location.href, {
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

    const url = new URL(globalThis.location.href);
    url.searchParams.set("_ts", Date.now());

    return fetch(url.toString(), {
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

  refreshCurrentView: async function (nid, type, data) {
    const refreshedRow = await this.refreshEntityRow(nid, type);
    if (refreshedRow) {
      return true;
    }

    const refreshedList = await this.refreshListSections();
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

    if (globalThis.location.pathname.startsWith("/node/")) {
      globalThis.location.reload();
      return true;
    }

    return false;
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
        const m = /(\d+)/.exec(text);
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
      '<div class="crm-modal-overlay" id="crm-modal-overlay">' +
      '<div class="crm-modal-loading" role="status" aria-live="polite" aria-label="Loading form">' +
      '<div class="crm-modal-loading__skeleton">' +
      '<div class="crm-skeleton crm-skeleton--title"></div>' +
      '<div class="crm-skeleton crm-skeleton--line"></div>' +
      '<div class="crm-skeleton crm-skeleton--line crm-skeleton--short"></div>' +
      '<div class="crm-skeleton crm-skeleton--line"></div>' +
      '<div class="crm-skeleton crm-skeleton--line crm-skeleton--short"></div>' +
      "</div>" +
      "<p>Loading...</p></div></div>";
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
    if (this._modalTrapCleanup) {
      this._modalTrapCleanup();
      this._modalTrapCleanup = null;
    }
    if (this._modalShortcutHandler) {
      document.removeEventListener("keydown", this._modalShortcutHandler);
      this._modalShortcutHandler = null;
    }

    const overlay = document.getElementById("crm-modal-overlay");
    if (overlay) {
      overlay.classList.add("closing");
      setTimeout(() => {
        overlay.remove();
        if (
          this._lastFocusedElement &&
          typeof this._lastFocusedElement.focus === "function"
        ) {
          this._lastFocusedElement.focus();
        }
        this._lastFocusedElement = null;

        // If on a dedicated add page, redirect to the matching list instead of
        // leaving the user staring at the "Loading create form…" background.
        const path = globalThis.location.pathname;
        const addMatch = /^\/crm\/add\/(\w+)/.exec(path);
        if (addMatch) {
          const listMap = {
            contact: "/crm/all-contacts",
            deal: "/crm/all-deals",
            organization: "/crm/all-organizations",
            activity: "/crm/all-activities",
          };
          globalThis.location.href = listMap[1] || "/crm/dashboard";
        }
      }, 300);
    }
  },

  setupModalHandlers: function () {
    const overlay = document.getElementById("crm-modal-overlay");
    if (!overlay) return;

    this.applyModalAccessibility(overlay);

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
        this.saveModal(form);
      }
    };
    if (this._modalShortcutHandler) {
      document.removeEventListener("keydown", this._modalShortcutHandler);
    }
    this._modalShortcutHandler = editKeyHandler;
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
    const removedFidsInputs = form.querySelectorAll(".removed-fids-input");
    let hasFiles = false;
    let hasRemovedFiles = false;
    fileInputs.forEach(function (input) {
      if (input.files && input.files.length > 0) hasFiles = true;
    });
    removedFidsInputs.forEach(function (input) {
      if (input.value && input.value.trim() !== "") hasRemovedFiles = true;
    });

    // Show saving state
    const modal = document.querySelector(".crm-modal-container");
    if (modal) {
      modal.classList.add("is-saving");
    }

    // OPTIMISTIC UI (Zero-Latency feel)
    // 1. Immediately close the modal and show loading state on the row
    const overlay = document.getElementById("crm-modal-overlay");
    if (overlay) overlay.style.display = "none"; // Hide instead of remove in case we need to rollback

    const rowNode = document.querySelector(
      `tr[data-entity-id="${nid}"], .crm-kanban-card[data-entity-id="${nid}"], .crm-card[data-entity-id="${nid}"]`,
    );
    if (rowNode) {
      rowNode.style.opacity = "0.5";
      rowNode.style.pointerEvents = "none";
      rowNode.style.filter = "grayscale(100%)";
    }

    globalThis.CRM?.toast?.("Saving changes...", "info", 2000);

    this.getCsrfToken().then((csrfToken) => {
      let fetchOptions;
      let saveUrl;

      if (hasFiles || hasRemovedFiles) {
        // Use FormData for multipart upload
        const formData = new FormData(form);
        formData.set("nid", nid);
        formData.set("type", type);
        fetchOptions = {
          method: "POST",
          headers: { "X-CSRF-Token": csrfToken },
          body: formData,
        };
        saveUrl = "/crm/edit/ajax/save-with-files";
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
        saveUrl = "/crm/edit/ajax/save";
      }

      fetch(saveUrl, fetchOptions)
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Actual delete of the overlay
            if (overlay) overlay.remove();

            this.refreshCurrentView(nid, type, data);

            globalThis.CRM?.toast?.("Changes saved successfully!", "success");
          } else {
            // Rollback: show modal again, remove row skeleton
            if (overlay) overlay.style.display = "";
            if (rowNode) {
              rowNode.style.opacity = "";
              rowNode.style.pointerEvents = "";
              rowNode.style.filter = "";
            }
            this.showMessage(data.message || "Error saving changes", "error");
          }
        })
        .catch((error) => {
          console.error("Save error:", error);
          if (overlay) overlay.style.display = "";
          if (rowNode) {
            rowNode.style.opacity = "";
            rowNode.style.pointerEvents = "";
            rowNode.style.filter = "";
          }
          this.showMessage(
            "Failed to save changes. Please try again.",
            "error",
          );
        });
    }); // end getCsrfToken
  },

  removeFileItem: function (fid, fieldName) {
    // Remove the file item from DOM
    const item = document.getElementById("file-item-" + fid);
    if (item) {
      item.style.transition = "opacity 0.2s ease, transform 0.2s ease";
      item.style.opacity = "0";
      item.style.transform = "translateX(8px)";
      setTimeout(function () {
        item.remove();
      }, 200);
    }
    // Add fid to removed_fids hidden input
    const form =
      document.querySelector("#crm-modal-overlay form") ||
      document.getElementById("crm-edit-form");
    if (!form) return;
    const hiddenInput = form.querySelector(
      'input[name="' + fieldName + '__removed_fids"]',
    );
    if (hiddenInput) {
      const current = hiddenInput.value
        ? hiddenInput.value.split(",").filter(Boolean)
        : [];
      current.push(String(fid));
      hiddenInput.value = current.join(",");
    }
  },

  showMessage: function (message, type) {
    globalThis.CRM?.toast?.(message, type || "success");
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
    // Close delete modal immediately for Optimistic UI feel
    const overlay =
      document.getElementById("crm-delete-step3") ||
      document.getElementById("crm-delete-step2") ||
      document.getElementById("crm-delete-step1");
    if (overlay) overlay.remove();

    // Show optimistic toast
    globalThis.CRM?.toast?.(`Deleting ${title}...`, "info", 2000);

    // 1. Optimistic UI: Find and clone the row for rollback, then hide/remove it immediately.
    let rowParent = null;
    let nextSibling = null;
    let rowClone = null;
    let rowNode = null;

    // Support both table row and kanban card
    rowNode = document.querySelector(
      `tr[data-entity-id="${nid}"], .crm-kanban-card[data-entity-id="${nid}"], .crm-card[data-entity-id="${nid}"]`,
    );

    if (rowNode) {
      rowParent = rowNode.parentNode;
      nextSibling = rowNode.nextSibling;
      rowClone = rowNode.cloneNode(true);
      // Remove immediately from UI
      rowNode.remove();
    }

    // 2. Fetch API in background
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
            // Success: update toast
            if (globalThis.CRM?.toast) {
              globalThis.CRM.toast(
                data.message || "Deleted successfully!",
                "success",
              );
            }
            // Background reconcile
            setTimeout(() => {
              this.refreshListSections();
            }, 300);
          } else {
            // Server returned error (e.g. ConstraintViolation) -> Rollback!
            this.showMessage(
              data.message || "Error deleting constraints.",
              "error",
            );
            if (rowClone && rowParent) {
              if (nextSibling) {
                nextSibling.before(rowClone);
              } else {
                rowParent.appendChild(rowClone);
              }
              // Flash red to indicate rollback
              rowClone.style.backgroundColor = "#fee2e2";
              setTimeout(() => {
                rowClone.style.backgroundColor = "";
              }, 1000);
            }
            this.refreshListSections();
          }
        })
        .catch((error) => {
          console.error("Delete error:", error);
          this.showMessage("Failed to delete. Please try again.", "error");
          // Rollback on network error!
          if (rowClone && rowParent) {
            if (nextSibling) {
              nextSibling.before(rowClone);
            } else {
              rowParent.appendChild(rowClone);
            }
            rowClone.style.backgroundColor = "#fee2e2";
            setTimeout(() => {
              rowClone.style.backgroundColor = "";
            }, 1000);
          }
        });
    });
  },

  openAddModal: function (type) {
    // Show loading overlay
    const loadingHtml =
      '<div class="crm-modal-overlay" id="crm-modal-overlay">' +
      '<div class="crm-modal-loading" role="status" aria-live="polite" aria-label="Loading create form">' +
      '<div class="crm-modal-loading__skeleton">' +
      '<div class="crm-skeleton crm-skeleton--title"></div>' +
      '<div class="crm-skeleton crm-skeleton--line"></div>' +
      '<div class="crm-skeleton crm-skeleton--line crm-skeleton--short"></div>' +
      '<div class="crm-skeleton crm-skeleton--line"></div>' +
      '<div class="crm-skeleton crm-skeleton--line crm-skeleton--short"></div>' +
      "</div>" +
      "<p>Loading create form...</p></div></div>";
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

    this.applyModalAccessibility(overlay);

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
        this.closeModal();
      } else if (
        (e.key === "Enter" && (e.ctrlKey || e.metaKey)) ||
        (e.key === "Enter" && document.activeElement?.tagName !== "TEXTAREA")
      ) {
        const activeTag = document.activeElement?.tagName;
        if (activeTag === "BUTTON" || activeTag === "A") return;
        e.preventDefault();
        this.saveAddModal(form);
      }
    };
    if (this._modalShortcutHandler) {
      document.removeEventListener("keydown", this._modalShortcutHandler);
    }
    this._modalShortcutHandler = addKeyHandler;
    document.addEventListener("keydown", addKeyHandler);
  },

  applyModalAccessibility: function (overlay) {
    const container = overlay.querySelector(".crm-modal-container");
    if (!container) {
      return;
    }

    this._lastFocusedElement = document.activeElement;
    container.setAttribute("role", "dialog");
    container.setAttribute("aria-modal", "true");
    if (!container.hasAttribute("tabindex")) {
      container.setAttribute("tabindex", "-1");
    }

    if (this._modalTrapCleanup) {
      this._modalTrapCleanup();
    }
    this._modalTrapCleanup = this.trapFocus(container, () => this.closeModal());

    const focusable = this.getFocusableElements(container);
    if (focusable.length) {
      focusable[0].focus();
    } else {
      container.focus();
    }
  },

  getFocusableElements: function (container) {
    const selector =
      'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
    return Array.from(container.querySelectorAll(selector)).filter(
      (el) =>
        el.offsetParent !== null && el.getAttribute("aria-hidden") !== "true",
    );
  },

  trapFocus: function (container, onEscape) {
    const keyHandler = (e) => {
      if (e.key === "Escape") {
        e.preventDefault();
        onEscape();
        return;
      }

      if (e.key !== "Tab") {
        return;
      }

      const focusable = this.getFocusableElements(container);
      if (!focusable.length) {
        e.preventDefault();
        container.focus();
        return;
      }

      const first = focusable[0];
      const last = focusable.at(-1);
      if (e.shiftKey && document.activeElement === first) {
        e.preventDefault();
        last.focus();
      } else if (!e.shiftKey && document.activeElement === last) {
        e.preventDefault();
        first.focus();
      }
    };

    document.addEventListener("keydown", keyHandler, true);
    return () => document.removeEventListener("keydown", keyHandler, true);
  },

  renderCreateErrors: function (form, errors) {
    Object.keys(errors).forEach((fieldName) => {
      const fieldWrapper = form
        .querySelector(`[name="${fieldName}"]`)
        ?.closest(".form-field");
      if (fieldWrapper) {
        const errorDiv = document.createElement("div");
        errorDiv.className = "field-error";
        errorDiv.textContent = errors[fieldName];
        fieldWrapper.appendChild(errorDiv);
      }
    });
  },

  showCreateFailure: function (statusDiv, message) {
    if (!statusDiv) {
      return;
    }

    statusDiv.className = "save-status error";
    statusDiv.innerHTML =
      '<i data-lucide="alert-circle"></i> ' +
      (message || "Failed to create. Please try again.");
    if (typeof lucide !== "undefined") {
      lucide.createIcons();
    }
  },

  handleCreateResult: function (result, options) {
    const { overlay, skeletonNode, statusDiv, form } = options;

    if (result.success) {
      if (overlay) overlay.remove();
      globalThis.CRM?.toast?.(
        result.message || "Created successfully!",
        "success",
      );
      setTimeout(() => {
        this.refreshListSections();
      }, 300);
      return;
    }

    if (skeletonNode) skeletonNode.remove();
    if (overlay) overlay.style.display = "";
    this.showCreateFailure(
      statusDiv,
      result.message || "Error creating content",
    );
    if (result.errors) {
      this.renderCreateErrors(form, result.errors);
    }
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

    // 1. Optimistic UI: Close Modal Immediately & Inject Skeleton
    const overlay = document.getElementById("crm-modal-overlay");
    if (overlay) overlay.style.display = "none"; // Hide instead of completely remove for rollback

    // Inject a skeleton "Creating..." row at the top of the list or kanban
    let skeletonNode = null;
    const tableBody = document.querySelector(
      "#crm-results-wrap table.crm-table tbody",
    );
    const kanbanBoard = document.querySelector(
      "#crm-results-wrap .crm-kanban-board > div",
    );

    globalThis.CRM?.toast?.("Creating...", "info", 2000);

    if (tableBody) {
      skeletonNode = document.createElement("tr");
      skeletonNode.innerHTML =
        '<td colspan="8"><div class="crm-create-skeleton crm-create-skeleton--row"></div></td>';
      tableBody.prepend(skeletonNode);
    } else if (kanbanBoard) {
      skeletonNode = document.createElement("div");
      skeletonNode.className = "crm-kanban-card";
      skeletonNode.innerHTML =
        '<div class="crm-create-skeleton crm-create-skeleton--card"></div>';
      kanbanBoard.prepend(skeletonNode);
    }

    // 2. Send AJAX request (CSRF-protected)
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
          this.handleCreateResult(result, {
            overlay,
            skeletonNode,
            statusDiv,
            form,
          });
        })
        .catch((error) => {
          console.error("Create error:", error);
          if (skeletonNode) skeletonNode.remove();
          if (overlay) overlay.style.display = ""; // Show modal again
          this.showCreateFailure(statusDiv);
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
        globalThis.CRMInlineEdit?.openModal?.(nid, bundle);
      });

      $(context).on("click", ".crm-delete-action", function () {
        const nid = $(this).data("nid");
        const bundle = $(this).data("bundle");
        const title = $(this).data("title");
        globalThis.CRMInlineEdit?.confirmDelete?.(nid, bundle, title);
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

        const $form = $(this);

        globalThis.addEventListener("beforeunload", function (e) {
          if ($form.hasClass("has-changes") && !$form.hasClass("is-saving")) {
            e.preventDefault();
          }
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
