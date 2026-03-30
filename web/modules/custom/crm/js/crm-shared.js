/**
 * CRM Shared UI — ClickUp-inspired interactions.
 * Provides: Toast notifications, Keyboard shortcuts, Realtime search debounce.
 */
(function (window, document) {
  "use strict";

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TOAST NOTIFICATION SYSTEM
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  var _toastContainer = null;

  function getToastContainer() {
    if (!_toastContainer) {
      _toastContainer = document.createElement("div");
      _toastContainer.id = "crm-toast-container";
      document.body.appendChild(_toastContainer);
    }
    return _toastContainer;
  }

  /**
   * Show a toast notification.
   * @param {string} message
   * @param {'success'|'error'|'info'|'warn'} type
   * @param {number} duration  ms (default 4000)
   */
  function toast(message, type, duration) {
    type = type || "success";
    duration = duration || 4000;

    var icons = {
      success:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
      warn: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };

    var el = document.createElement("div");
    el.className = "crm-toast crm-toast--" + type;
    el.innerHTML =
      '<span class="crm-toast__icon">' +
      (icons[type] || icons.info) +
      "</span>" +
      '<span class="crm-toast__msg">' +
      message +
      "</span>" +
      '<button class="crm-toast__close" aria-label="Dismiss">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
      "</button>";

    var container = getToastContainer();
    container.appendChild(el);

    // Animate in
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.classList.add("crm-toast--show");
      });
    });

    function dismiss() {
      el.classList.remove("crm-toast--show");
      el.classList.add("crm-toast--hide");
      setTimeout(function () {
        if (el.parentNode) {
          el.parentNode.removeChild(el);
        }
      }, 300);
    }

    el.querySelector(".crm-toast__close").addEventListener("click", dismiss);
    var timer = setTimeout(dismiss, duration);
    el.addEventListener("mouseenter", function () {
      clearTimeout(timer);
    });
    el.addEventListener("mouseleave", function () {
      timer = setTimeout(dismiss, 1500);
    });
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     PENDING TOAST (persisted across page reload)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  function setPendingToast(message, type) {
    try {
      localStorage.setItem(
        "crm_pending_toast",
        JSON.stringify({ message: message, type: type || "success" }),
      );
    } catch (e) {}
  }

  function flushPendingToast() {
    try {
      var raw = localStorage.getItem("crm_pending_toast");
      if (raw) {
        localStorage.removeItem("crm_pending_toast");
        var d = JSON.parse(raw);
        setTimeout(function () {
          toast(d.message, d.type);
        }, 350);
      }
    } catch (e) {}
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     KEYBOARD SHORTCUTS
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  /**
   * @param {Object} opts
   *   addUrl:     URL to navigate to for "N" (new record) shortcut
   *   searchId:   ID of search input element for "/" shortcut
   */
  function initKeyboardShortcuts(opts) {
    opts = opts || {};

    document.addEventListener("keydown", function (e) {
      // Ignore if focused inside an input, textarea, select, contenteditable
      var tag = document.activeElement && document.activeElement.tagName;
      var isEditable =
        tag === "INPUT" ||
        tag === "TEXTAREA" ||
        tag === "SELECT" ||
        (document.activeElement && document.activeElement.isContentEditable);

      // Escape: blur search if focused
      if (e.key === "Escape" && !e.ctrlKey && !e.metaKey) {
        if (
          document.activeElement &&
          document.activeElement.id === opts.searchId
        ) {
          document.activeElement.blur();
        }
        return;
      }

      if (isEditable) return;

      // N: go to add new
      if (
        (e.key === "n" || e.key === "N") &&
        !e.ctrlKey &&
        !e.metaKey &&
        !e.altKey
      ) {
        if (opts.addUrl) {
          e.preventDefault();
          window.location.href = opts.addUrl;
        }
      }

      // /: focus search input
      if (e.key === "/" && !e.ctrlKey && !e.metaKey) {
        var searchEl = opts.searchId
          ? document.getElementById(opts.searchId)
          : null;
        if (searchEl) {
          e.preventDefault();
          searchEl.focus();
          searchEl.select();
        }
      }
    });
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     REALTIME DEBOUNCED SEARCH
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  /**
   * Wire up a search/filter form to update results without a full page reload.
   * Uses fetch() + DOMParser to swap only #crm-results-wrap and update the
   * filter-count label. Falls back to a normal form submit when #crm-results-wrap
   * is absent (e.g. pages that don't opt-in to AJAX filtering).
   *
   * Also injects × clear buttons into every .filter-input-wrap in the form.
   *
   * @param {string} inputId   ID of the primary search <input>
   * @param {number} delay     Debounce delay in ms (default 450)
   */
  function initRealtimeSearch(inputId, delay) {
    var input = document.getElementById(inputId);
    if (!input) return;
    delay = delay || 450;
    var form = input.closest("form") || input.form;
    var resultsWrap = document.getElementById("crm-results-wrap");


    // ── Serialize form fields to a clean URL ──────────────────────────────
    function getFormUrl() {
      var action = form
        ? form.getAttribute("action") || window.location.pathname
        : window.location.pathname;
      var params = new URLSearchParams();
      if (form) {
        form
          .querySelectorAll("input[name], select[name]")
          .forEach(function (el) {
            if (el.type === "submit") return;
            var v = el.value;
            // strip empties and "all" sentinel values so URLs stay clean
            if (!v || v === "0") return;
            params.set(el.name, v);
          });
      }
      return action + (params.toString() ? "?" + params.toString() : "");
    }

    // ── AJAX fetch + DOM swap ─────────────────────────────────────────────
    function fetchResults(url) {
      if (resultsWrap) resultsWrap.classList.add("crm-loading");
      if (form) form.classList.add("is-submitting");

      var fetchUrl = new URL(url, window.location.origin);
      fetchUrl.searchParams.set("_ts", Date.now());

      fetch(fetchUrl.toString(), { credentials: "same-origin", cache: "no-store" })
        .then(function (r) {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.text();
        })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, "text/html");

          // Swap the results container (chips + table + pagination)
          var newWrap = doc.getElementById("crm-results-wrap");
          if (newWrap && resultsWrap) {
            resultsWrap.innerHTML = newWrap.innerHTML;
            resultsWrap.classList.remove("crm-loading");
          }

          // Update filter-count label inside the filter bar
          var newCount = doc.querySelector(".filter-count");
          var oldCount = document.querySelector(".filter-count");
          if (newCount && oldCount) oldCount.textContent = newCount.textContent;

          // Show/hide the "Clear all filters" link in the filter bar
          var hasFilt = false;
          if (form) {
            form
              .querySelectorAll(".filter-input, .filter-select")
              .forEach(function (el) {
                if (el.value && el.value !== "0") hasFilt = true;
              });
          }
          var clearLink = form ? form.querySelector(".btn-filter-clear") : null;
          if (clearLink) clearLink.style.display = hasFilt ? "" : "none";

          // Update the address bar and re-init icons
          history.pushState(null, "", url);
          if (window.lucide) lucide.createIcons();
          if (form) form.classList.remove("is-submitting");

          // Signal other components (bulk select etc.) that content changed
          document.dispatchEvent(new CustomEvent("crm:results-swapped"));
        })
        .catch(function () {
          if (form) form.classList.remove("is-submitting");
          if (resultsWrap) resultsWrap.classList.remove("crm-loading");
          // Fallback: normal navigation keeps everything working
          window.location.href = url;
        });
    }

    // ── doSubmit: AJAX when opt-in, else normal form submit ───────────────
    function doSubmit() {
      if (!resultsWrap || !form) {
        if (form) form.submit();
        return;
      }
      fetchResults(getFormUrl());
    }

    // ── Intercept form submit event (Apply button / submit) ───────────────
    if (form) {
      form.addEventListener("submit", function (e) {
        if (!resultsWrap) return;
        e.preventDefault();
        doSubmit();
      });
    }

    // ── Intercept "Clear all filters" link ────────────────────────────────
    if (form) {
      var clearFilterLink = form.querySelector(".btn-filter-clear");
      if (clearFilterLink) {
        clearFilterLink.addEventListener("click", function (e) {
          if (!resultsWrap) return;
          e.preventDefault();
          form.querySelectorAll(".filter-input").forEach(function (el) {
            el.value = "";
          });
          form.querySelectorAll(".filter-select").forEach(function (el) {
            el.value = el.options[0] ? el.options[0].value : "";
          });
          form.querySelectorAll(".crm-filter-clear").forEach(function (btn) {
            btn.classList.remove("visible");
          });
          clearFilterLink.style.display = "none";
          fetchResults(form.getAttribute("action") || window.location.pathname);
        });
      }
    }

    // ── Helper: inject × clear button into a .filter-input-wrap ──────────
    function attachClearBtn(inp) {
      var wrap = inp.closest(".filter-input-wrap");
      if (!wrap || wrap.querySelector(".crm-filter-clear")) return;
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "crm-filter-clear";
      btn.setAttribute("tabindex", "-1");
      btn.setAttribute("aria-label", "Clear");
      btn.textContent = "\u00d7"; // ×
      inp.style.setProperty("padding-right", "32px", "important");
      wrap.appendChild(btn);
      if (inp.value) btn.classList.add("visible");
      inp.addEventListener("input", function () {
        btn.classList.toggle("visible", !!inp.value);
      });
      btn.addEventListener("click", function () {
        inp.value = "";
        btn.classList.remove("visible");
        inp.focus();
        doSubmit();
      });
    }

    // ── Attach clear buttons to all filter text inputs in the form ────────
    var filterInputs = form
      ? form.querySelectorAll(".filter-input-wrap .filter-input")
      : [input];
    filterInputs.forEach(function (inp) {
      attachClearBtn(inp);
    });

    // ── Debounced auto-submit on typing ───────────────────────────────────
    filterInputs.forEach(function (inp) {
      var timer = null;
      var lastVal = inp.value;
      inp.addEventListener("input", function () {
        clearTimeout(timer);
        var val = inp.value;
        if (val === lastVal) return;
        timer = setTimeout(function () {
          lastVal = val;
          doSubmit();
        }, delay);
      });
      inp.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault(); // prevent double-fire via form submit event
          clearTimeout(timer);
          doSubmit();
        }
      });
    });

    // ── Dropdown selects: submit immediately on change ────────────────────
    if (form) {
      form.querySelectorAll(".filter-select").forEach(function (sel) {
        sel.addEventListener("change", function () {
          doSubmit();
        });
      });
    }

    // ── Event delegation: pagination, sort-column links, page-size ────────
    if (resultsWrap) {
      document.addEventListener("click", function (e) {
        var a = e.target.closest("a.page-link");
        if (a && resultsWrap.contains(a)) {
          e.preventDefault();
          fetchResults(a.href);
          return;
        }
        var sortA = e.target.closest("thead a");
        if (sortA && resultsWrap.contains(sortA)) {
          e.preventDefault();
          fetchResults(sortA.href);
        }
      });
      document.addEventListener("change", function (e) {
        if (
          e.target &&
          e.target.id === "pg-sz-sel" &&
          resultsWrap.contains(e.target)
        ) {
          var u = new URL(window.location.href);
          u.searchParams.set("per_page", e.target.value);
          u.searchParams.set("page", "0");
          fetchResults(u.toString());
        }
      });
    }
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     SHORTCUT HINT BAR
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  /**
   * Render a small keyboard shortcut hint bar at the bottom-left.
   * @param {Array<{key:string, label:string}>} shortcuts
   */
  function renderShortcutHints(shortcuts) {
    if (!shortcuts || !shortcuts.length) return;
    // Guard: only inject once per page. Drupal behaviors may call attach() multiple times.
    if (document.getElementById("crm-shortcuts-hint")) return;
    var bar = document.createElement("div");
    bar.className = "crm-shortcuts-hint";
    bar.id = "crm-shortcuts-hint";
    bar.innerHTML = shortcuts
      .map(function (s) {
        return (
          '<span class="crm-sh-item"><kbd>' +
          s.key +
          "</kbd> " +
          s.label +
          "</span>"
        );
      })
      .join("");
    document.body.appendChild(bar);
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     BULK ACTIONS (LIST PAGES)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  /**
   * Enable multi-select actions on list pages.
   * @param {{entityType: string}} opts
   */
  function initBulkActions(opts) {
    opts = opts || {};
    var entityType = opts.entityType;
    if (!entityType) return;

    var bulkBar = document.getElementById("bulk-bar");
    if (!bulkBar) return;

    var bkCount = document.getElementById("bk-ct");
    var clearBtn = document.getElementById("bulk-clear-btn");
    var editBtn = document.getElementById("bulk-edit-btn");
    var deleteBtn = document.getElementById("bulk-delete-btn");

    function getRowChecks() {
      return Array.prototype.slice.call(document.querySelectorAll(".row-chk"));
    }

    function canRowDelete(checkbox) {
      if (!checkbox) return false;
      var row = checkbox.closest("tr");
      if (!row) return false;
      return !!row.querySelector(".btn-delete");
    }

    function getSelectedChecks() {
      return getRowChecks().filter(function (c) {
        return c.checked;
      });
    }

    function getDeleteSelectedChecks() {
      return getSelectedChecks().filter(function (c) {
        return canRowDelete(c);
      });
    }

    function getNameLink(row) {
      if (!row) return null;
      return row.querySelector(
        ".contact-name-link, .deal-name-link, .org-name-link, .act-name-link",
      );
    }

    function setBusy(isBusy) {
      [clearBtn, editBtn, deleteBtn].forEach(function (btn) {
        if (!btn) return;
        btn.disabled = !!isBusy;
        btn.style.opacity = isBusy ? "0.6" : "";
        btn.style.pointerEvents = isBusy ? "none" : "";
      });
    }

    function refreshBulk() {
      var selected = getSelectedChecks();
      var count = selected.length;

      if (bkCount) {
        bkCount.textContent = count + " selected";
      }

      if (count > 0) {
        bulkBar.classList.add("show");
      } else {
        bulkBar.classList.remove("show");
      }

      var chkAll = document.getElementById("chk-all");
      if (chkAll) {
        var all = getRowChecks();
        chkAll.checked =
          all.length > 0 &&
          all.every(function (c) {
            return c.checked;
          });
        chkAll.indeterminate =
          all.some(function (c) {
            return c.checked;
          }) && !chkAll.checked;
      }

      getRowChecks().forEach(function (c) {
        var row = c.closest("tr");
        if (!row) return;
        row.classList.toggle("row-selected", c.checked);
      });
    }

    function clearSelection() {
      getRowChecks().forEach(function (c) {
        c.checked = false;
      });
      var chkAll = document.getElementById("chk-all");
      if (chkAll) {
        chkAll.checked = false;
        chkAll.indeterminate = false;
      }
      refreshBulk();
    }

    async function getCsrfToken() {
      if (
        window.CRMInlineEdit &&
        typeof window.CRMInlineEdit.getCsrfToken === "function"
      ) {
        return window.CRMInlineEdit.getCsrfToken();
      }
      var resp = await fetch("/session/token", { credentials: "same-origin" });
      return (await resp.text()).trim();
    }

    async function runBulkEdit() {
      var selected = getSelectedChecks();
      if (!selected.length) {
        toast("Please select at least one record.", "info");
        return;
      }

      var nextTitle = window.prompt(
        "Enter new title for " + selected.length + " selected record(s):",
      );
      if (nextTitle === null) return;

      nextTitle = (nextTitle || "").trim();
      if (!nextTitle) {
        toast("Title cannot be empty.", "warn");
        return;
      }

      setBusy(true);
      try {
        var csrfToken = await getCsrfToken();
        var updates = selected.map(function (c) {
          return {
            entity_type: "node",
            entity_id: Number(c.value),
            field: "title",
            value: nextTitle,
          };
        });

        var resp = await fetch("/api/v1/batch-update", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken,
          },
          credentials: "same-origin",
          body: JSON.stringify({ updates: updates }),
        });

        var data = await resp.json();
        if (!resp.ok || !data) {
          throw new Error("Batch update failed");
        }

        var updated = Number(data.updated || 0);
        var failed = Number(data.failed || 0);

        if (updated > 0) {
          selected.forEach(function (c) {
            var row = c.closest("tr");
            var nameLink = getNameLink(row);
            if (nameLink) {
              nameLink.textContent = nextTitle;
            }
          });
        }

        if (updated > 0 && failed === 0) {
          toast("Updated " + updated + " record(s).", "success");
        } else if (updated > 0 && failed > 0) {
          toast("Updated " + updated + ", failed " + failed + ".", "warn");
        } else {
          toast("No records were updated.", "error");
        }

        clearSelection();
        if (
          window.CRMInlineEdit &&
          typeof window.CRMInlineEdit.refreshListSections === "function"
        ) {
          window.CRMInlineEdit.refreshListSections();
        }
      } catch (error) {
        console.error("Bulk edit error:", error);
        toast("Bulk edit failed. Please try again.", "error");
      } finally {
        setBusy(false);
      }
    }

    async function runBulkDelete() {
      var selected = getSelectedChecks();
      if (!selected.length) {
        toast("Please select at least one record.", "info");
        return;
      }

      var deletableSelected = getDeleteSelectedChecks();
      if (!deletableSelected.length) {
        toast(
          "Selected records cannot be deleted with your current permissions.",
          "warn",
        );
        return;
      }

      var skippedByPermission = selected.length - deletableSelected.length;

      var ok = await confirmBulkDelete(deletableSelected.length);
      if (!ok) return;

      setBusy(true);
      try {
        var csrfToken = await getCsrfToken();

        // Snapshot each row's HTML and its next sibling BEFORE any mutation
        // so we can roll back rows that fail to delete on the server.
        var rowSnapshots = deletableSelected.map(function (c) {
          var row = c.closest("tr");
          return {
            html: row ? row.outerHTML : null,
            parent: row ? row.parentNode : null,
            nextSibling: row ? row.nextSibling : null,
          };
        });

        var tasks = deletableSelected.map(function (c) {
          var row = c.closest("tr");
          var link = getNameLink(row);
          var title = link ? (link.textContent || "").trim() : "";
          return fetch("/crm/edit/ajax/delete", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": csrfToken,
            },
            credentials: "same-origin",
            body: JSON.stringify({
              nid: Number(c.value),
              type: entityType,
              confirmation: title,
            }),
          })
            .then(function (resp) {
              return resp.json().then(function (json) {
                return { ok: resp.ok && json && json.success, json: json };
              });
            })
            .catch(function () {
              return { ok: false, json: null };
            });
        });

        var results = await Promise.all(tasks);
        var successCount = 0;
        var failedCount = 0;

        results.forEach(function (r, idx) {
          if (r.ok) {
            successCount += 1;
            if (
              window.CRMInlineEdit &&
              typeof window.CRMInlineEdit.removeEntityRowOptimistically ===
                "function"
            ) {
              window.CRMInlineEdit.removeEntityRowOptimistically(
                Number(deletableSelected[idx].value),
                entityType,
              );
            }
          } else {
            failedCount += 1;
            // ROLLBACK: restore the row to its original position in the table.
            var snap = rowSnapshots[idx];
            if (snap && snap.html && snap.parent) {
              var tmp = document.createElement("tbody");
              tmp.innerHTML = snap.html;
              var restoredRow = tmp.firstChild;
              if (restoredRow) {
                if (snap.nextSibling) {
                  snap.parent.insertBefore(restoredRow, snap.nextSibling);
                } else {
                  snap.parent.appendChild(restoredRow);
                }
                // Flash row red briefly to signal the failure
                restoredRow.style.transition = "background 0.3s";
                restoredRow.style.background = "#fee2e2";
                setTimeout(function () {
                  restoredRow.style.background = "";
                }, 1200);
              }
            }
          }
        });

        var deniedCount = results.filter(function (r) {
          return (
            r && r.json && /access denied/i.test(String(r.json.message || ""))
          );
        }).length;

        if (successCount > 0 && failedCount === 0) {
          toast("Deleted " + successCount + " record(s).", "success");
        } else if (successCount > 0 && failedCount > 0) {
          toast(
            "Deleted " + successCount + ", failed " + failedCount + ".",
            "warn",
          );
        } else {
          toast("Bulk delete failed.", "error");
        }

        if (deniedCount > 0 || skippedByPermission > 0) {
          var skippedTotal = deniedCount + skippedByPermission;
          toast(
            skippedTotal +
              " record(s) were skipped because your account does not have delete permission.",
            "warn",
            5200,
          );
        }

        clearSelection();
        if (
          window.CRMInlineEdit &&
          typeof window.CRMInlineEdit.refreshListSections === "function"
        ) {
          setTimeout(function () {
            window.CRMInlineEdit.refreshListSections();
          }, 250);
        }
      } catch (error) {
        console.error("Bulk delete error:", error);
        toast("Bulk delete failed. Please try again.", "error");
      } finally {
        setBusy(false);
      }
    }

    function confirmBulkDelete(count) {

      return new Promise(function (resolve) {
        var overlay = document.createElement("div");
        overlay.className = "crm-bulk-confirm";
        overlay.innerHTML =
          '<div class="crm-bulk-confirm__card" role="dialog" aria-modal="true" aria-label="Confirm bulk delete">' +
          '<h3 class="crm-bulk-confirm__ttl">Delete ' +
          count +
          " selected record(s)?</h3>" +
          '<p class="crm-bulk-confirm__txt">This action cannot be undone. Related references will be cleaned where supported.</p>' +
          '<span class="crm-bulk-confirm__warn">Permanent deletion</span>' +
          '<div class="crm-bulk-confirm__row">' +
          '<label class="crm-bulk-confirm__lbl" for="crm-bulk-confirm-input">Type DELETE to confirm</label>' +
          '<input id="crm-bulk-confirm-input" class="crm-bulk-confirm__input" type="text" autocomplete="off" spellcheck="false" placeholder="DELETE" />' +
          '<div class="crm-bulk-confirm__hint">Only uppercase DELETE will enable the delete button.</div>' +
          "</div>" +
          '<div class="crm-bulk-confirm__actions">' +
          '<button type="button" class="crm-bulk-confirm__btn" id="crm-bulk-confirm-cancel">Cancel</button>' +
          '<button type="button" class="crm-bulk-confirm__btn crm-bulk-confirm__btn--danger" id="crm-bulk-confirm-ok" disabled>Delete selected</button>' +
          "</div>" +
          "</div>";

        document.body.appendChild(overlay);

        var input = overlay.querySelector("#crm-bulk-confirm-input");
        var cancelBtn = overlay.querySelector("#crm-bulk-confirm-cancel");
        var okBtn = overlay.querySelector("#crm-bulk-confirm-ok");

        function cleanup(result) {
          document.removeEventListener("keydown", keyHandler);
          overlay.remove();
          resolve(result);
        }

        function keyHandler(e) {
          if (e.key === "Escape") {
            cleanup(false);
          }
          if (e.key === "Enter" && !okBtn.disabled) {
            cleanup(true);
          }
        }

        document.addEventListener("keydown", keyHandler);

        input.addEventListener("input", function () {
          okBtn.disabled = input.value.trim() !== "DELETE";
        });

        cancelBtn.addEventListener("click", function () {
          cleanup(false);
        });

        okBtn.addEventListener("click", function () {
          if (!okBtn.disabled) {
            cleanup(true);
          }
        });

        overlay.addEventListener("click", function (e) {
          if (e.target === overlay) {
            cleanup(false);
          }
        });

        setTimeout(function () {
          input.focus();
        }, 0);
      });
    }

    document.addEventListener("change", function (e) {
      if (e.target && e.target.classList.contains("chk-all")) {
        getRowChecks().forEach(function (c) {
          c.checked = e.target.checked;
        });
        refreshBulk();
      } else if (e.target && e.target.classList.contains("row-chk")) {
        refreshBulk();
      }
    });

    document.addEventListener("crm:results-swapped", function () {
      clearSelection();
    });

    if (clearBtn) {
      clearBtn.addEventListener("click", clearSelection);
    }
    if (editBtn) {
      editBtn.addEventListener("click", runBulkEdit);
    }
    if (deleteBtn) {
      deleteBtn.addEventListener("click", runBulkDelete);
    }

    refreshBulk();
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     AUTO-INIT ON DOM READY
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  document.addEventListener("DOMContentLoaded", function () {
    flushPendingToast();

    // Legacy: flush old format used by CRMInlineEdit
    try {
      var legacyRaw = localStorage.getItem("crmToast");
      if (legacyRaw) {
        localStorage.removeItem("crmToast");
        var ld = JSON.parse(legacyRaw);
        setTimeout(function () {
          toast(
            ld.message,
            ld.type === "danger" ? "error" : ld.type || "success",
          );
        }, 350);
      }
    } catch (e) {}
  });

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     PUBLIC API
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  window.CRM = window.CRM || {};
  window.CRM.toast = toast;
  window.CRM.setPendingToast = setPendingToast;
  window.CRM.initKeyboardShortcuts = initKeyboardShortcuts;
  window.CRM.initRealtimeSearch = initRealtimeSearch;
  window.CRM.renderShortcutHints = renderShortcutHints;
  window.CRM.initBulkActions = initBulkActions;
})(window, document);
