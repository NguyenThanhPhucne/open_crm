/**
 * CRM Shared UI — ClickUp-inspired interactions.
 * Provides: Toast notifications, Keyboard shortcuts, Realtime search debounce.
 */
(function (window, document) {
  'use strict';

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     TOAST NOTIFICATION SYSTEM
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  var _toastContainer = null;

  function getToastContainer() {
    if (!_toastContainer) {
      _toastContainer = document.createElement('div');
      _toastContainer.id = 'crm-toast-container';
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
    type = type || 'success';
    duration = duration || 4000;

    var icons = {
      success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>',
      error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
      warn:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
      info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
    };

    var el = document.createElement('div');
    el.className = 'crm-toast crm-toast--' + type;
    el.innerHTML = '<span class="crm-toast__icon">' + (icons[type] || icons.info) + '</span>'
      + '<span class="crm-toast__msg">' + message + '</span>'
      + '<button class="crm-toast__close" aria-label="Dismiss">'
      + '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
      + '</button>';

    var container = getToastContainer();
    container.appendChild(el);

    // Animate in
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.classList.add('crm-toast--show');
      });
    });

    function dismiss() {
      el.classList.remove('crm-toast--show');
      el.classList.add('crm-toast--hide');
      setTimeout(function () {
        if (el.parentNode) { el.parentNode.removeChild(el); }
      }, 300);
    }

    el.querySelector('.crm-toast__close').addEventListener('click', dismiss);
    var timer = setTimeout(dismiss, duration);
    el.addEventListener('mouseenter', function () { clearTimeout(timer); });
    el.addEventListener('mouseleave', function () { timer = setTimeout(dismiss, 1500); });
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     PENDING TOAST (persisted across page reload)
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  function setPendingToast(message, type) {
    try { localStorage.setItem('crm_pending_toast', JSON.stringify({ message: message, type: type || 'success' })); } catch (e) {}
  }

  function flushPendingToast() {
    try {
      var raw = localStorage.getItem('crm_pending_toast');
      if (raw) {
        localStorage.removeItem('crm_pending_toast');
        var d = JSON.parse(raw);
        setTimeout(function () { toast(d.message, d.type); }, 350);
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

    document.addEventListener('keydown', function (e) {
      // Ignore if focused inside an input, textarea, select, contenteditable
      var tag = document.activeElement && document.activeElement.tagName;
      var isEditable = tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT'
        || (document.activeElement && document.activeElement.isContentEditable);

      // Escape: blur search if focused
      if (e.key === 'Escape' && !e.ctrlKey && !e.metaKey) {
        if (document.activeElement && document.activeElement.id === opts.searchId) {
          document.activeElement.blur();
        }
        return;
      }

      if (isEditable) return;

      // N: go to add new
      if ((e.key === 'n' || e.key === 'N') && !e.ctrlKey && !e.metaKey && !e.altKey) {
        if (opts.addUrl) {
          e.preventDefault();
          window.location.href = opts.addUrl;
        }
      }

      // /: focus search input
      if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
        var searchEl = opts.searchId ? document.getElementById(opts.searchId) : null;
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
   * Wire up a search input to auto-submit its parent form after typing stops.
   * @param {string} inputId   ID of the search <input>
   * @param {number} delay     Debounce delay in ms (default 450)
   */
  function initRealtimeSearch(inputId, delay) {
    var input = document.getElementById(inputId);
    if (!input) return;
    delay = delay || 450;
    var timer = null;
    var lastVal = input.value;

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var val = input.value;
      if (val === lastVal) return;
      timer = setTimeout(function () {
        lastVal = val;
        var form = input.closest('form') || input.form;
        if (form) { form.submit(); }
      }, delay);
    });

    // Submit immediately on Enter
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        clearTimeout(timer);
        var form = input.closest('form') || input.form;
        if (form) { form.submit(); }
      }
    });
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
    var bar = document.createElement('div');
    bar.className = 'crm-shortcuts-hint';
    bar.innerHTML = shortcuts.map(function (s) {
      return '<span class="crm-sh-item"><kbd>' + s.key + '</kbd> ' + s.label + '</span>';
    }).join('');
    document.body.appendChild(bar);
  }

  /* ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
     AUTO-INIT ON DOM READY
   ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ */
  document.addEventListener('DOMContentLoaded', function () {
    flushPendingToast();

    // Legacy: flush old format used by CRMInlineEdit
    try {
      var legacyRaw = localStorage.getItem('crmToast');
      if (legacyRaw) {
        localStorage.removeItem('crmToast');
        var ld = JSON.parse(legacyRaw);
        setTimeout(function () { toast(ld.message, ld.type === 'danger' ? 'error' : (ld.type || 'success')); }, 350);
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
