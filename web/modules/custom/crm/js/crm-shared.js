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

    // ── Inject shared CSS once per page ──────────────────────────────────
    if (!document.getElementById("crm-irs-style")) {
      var styleEl = document.createElement("style");
      styleEl.id = "crm-irs-style";
      styleEl.textContent =
        ".crm-filter-clear{position:absolute;right:8px;top:50%;" +
        "transform:translateY(-50%);width:22px;height:22px;display:none;" +
        "align-items:center;justify-content:center;cursor:pointer;" +
        "color:#94a3b8;background:none;border:none;padding:0;" +
        "border-radius:50%;font-size:18px;line-height:1;z-index:3;" +
        "transition:color .15s,background .15s}" +
        ".crm-filter-clear.visible{display:flex}" +
        ".crm-filter-clear:hover{color:#ef4444;background:rgba(239,68,68,.08)}" +
        ".filter-bar.is-submitting{opacity:.65;pointer-events:none;transition:opacity .2s}" +
        "#crm-results-wrap{transition:opacity .15s}" +
        "#crm-results-wrap.crm-loading{opacity:.45;pointer-events:none}";
      document.head.appendChild(styleEl);
    }

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

      fetch(url, { credentials: "same-origin" })
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
    var bar = document.createElement("div");
    bar.className = "crm-shortcuts-hint";
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
})(window, document);
