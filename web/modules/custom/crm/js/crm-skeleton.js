/**
 * @file
 * CRM Skeleton Loader — JS controller.
 *
 * Provides CRMSkeleton.show() / CRMSkeleton.hide() to swap skeleton
 * placeholders in and out of any container, replacing the plain
 * opacity-fade loading class with premium shimmer screens.
 */
(function (window, document) {
  "use strict";

  /* ── HTML generators ───────────────────────────────────────────────── */

  /**
   * Build a skeleton table body with `count` rows.
   * @param {number} count
   * @returns {string}
   */
  function buildTableSkeleton(count) {
    var rows = "";
    for (var i = 0; i < count; i++) {
      rows +=
        '<tr class="crm-skeleton-row">' +
          '<td class="sk-col-check"><span class="crm-skeleton"></span></td>' +
          '<td class="sk-col-name">' +
            '<div class="sk-name-inner">' +
              '<span class="crm-skeleton sk-avatar"></span>' +
              '<span class="crm-skeleton sk-label"></span>' +
            "</div>" +
          "</td>" +
          '<td class="sk-col-text"><span class="crm-skeleton"></span></td>' +
          '<td class="sk-col-text"><span class="crm-skeleton"></span></td>' +
          '<td class="sk-col-badge"><span class="crm-skeleton"></span></td>' +
          '<td class="sk-col-actions"><span class="crm-skeleton"></span></td>' +
        "</tr>";
    }
    return (
      '<table class="crm-skeleton-table crm-entities-list">' +
        "<thead><tr>" +
          '<th style="width:40px"></th>' +
          "<th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th></th>" +
        "</tr></thead>" +
        "<tbody>" + rows + "</tbody>" +
      "</table>"
    );
  }

  /**
   * Build `count` skeleton stat cards (dashboard).
   * @param {number} count
   * @returns {string}
   */
  function buildCardsSkeleton(count) {
    var cards = "";
    for (var i = 0; i < count; i++) {
      cards +=
        '<div class="crm-skeleton-card">' +
          '<span class="crm-skeleton sk-card-icon"></span>' +
          '<span class="crm-skeleton sk-card-value"></span>' +
          '<span class="crm-skeleton sk-card-label"></span>' +
        "</div>";
    }
    return '<div class="crm-skeleton-cards">' + cards + "</div>";
  }

  /**
   * Build `count` skeleton kanban columns with 3 cards each.
   * @param {number} count
   * @returns {string}
   */
  function buildKanbanSkeleton(count) {
    function col() {
      var innerCards = "";
      for (var j = 0; j < 3; j++) {
        innerCards +=
          '<div class="crm-skeleton-kanban-card">' +
            '<span class="crm-skeleton sk-kanban-title"></span>' +
            '<span class="crm-skeleton sk-kanban-sub"></span>' +
            '<span class="crm-skeleton sk-kanban-badge"></span>' +
          "</div>";
      }
      return (
        '<div class="crm-skeleton-kanban-col">' +
          '<span class="crm-skeleton sk-col-header"></span>' +
          innerCards +
        "</div>"
      );
    }
    var cols = "";
    for (var i = 0; i < count; i++) {
      cols += col();
    }
    return '<div class="crm-skeleton-kanban">' + cols + "</div>";
  }

  /* ── Public API ────────────────────────────────────────────────────── */
  var CRMSkeleton = {

    /**
     * Inject skeleton HTML into `container`, hiding real content.
     *
     * @param {HTMLElement} container
     * @param {'table'|'cards'|'kanban'} type
     * @param {number} [count=6]  Number of rows/cards/columns to render.
     */
    show: function (container, type, count) {
      if (!container) return;
      count = count || 6;

      // Stash real children
      if (!container._crmSkeletonSaved) {
        container._crmSkeletonSaved = container.innerHTML;
      }

      var html;
      if (type === "cards") {
        html = buildCardsSkeleton(count);
      } else if (type === "kanban") {
        html = buildKanbanSkeleton(count);
      } else {
        html = buildTableSkeleton(count);
      }

      container.innerHTML = html;
      container.setAttribute("data-skeleton-active", "1");
    },

    /**
     * Remove skeleton and restore real content (or just clear the flag).
     *
     * @param {HTMLElement} container
     */
    hide: function (container) {
      if (!container) return;
      if (container._crmSkeletonSaved !== undefined) {
        container.innerHTML = container._crmSkeletonSaved;
        delete container._crmSkeletonSaved;
      }
      container.removeAttribute("data-skeleton-active");
    },

    /**
     * Convenience: show skeleton on the standard #crm-results-wrap element
     * before an AJAX fetch, then hide after content is swapped.
     *
     * Integrates with the fetchResults() flow in crm-shared.js.
     *
     * @param {'table'|'cards'|'kanban'} type
     * @param {number} [count=6]
     */
    showResultsWrap: function (type, count) {
      var wrap = document.getElementById("crm-results-wrap");
      if (wrap) {
        this.show(wrap, type || "table", count || 8);
        wrap.classList.add("crm-loading");
      }
    },

    hideResultsWrap: function () {
      var wrap = document.getElementById("crm-results-wrap");
      if (wrap) {
        this.hide(wrap);
        wrap.classList.remove("crm-loading");
      }
    },
  };

  window.CRMSkeleton = CRMSkeleton;

})(window, document);
