/**
 * @file
 * ClickUp-like list toolbar enhancer for CRM list pages.
 */

(function (Drupal) {
  "use strict";

  function getConfig(pathname) {
    if (
      pathname.includes("/crm/all-contacts") ||
      pathname.includes("/crm/my-contacts")
    ) {
      return {
        entity: "contact",
        title: "Contacts Workspace",
        subtitle: "Organize leads, owners, and relationship touchpoints.",
        views: [
          {
            label: "All",
            href: "/crm/all-contacts",
            match: /\/crm\/all-contacts/,
          },
          {
            label: "Mine",
            href: "/crm/my-contacts",
            match: /\/crm\/my-contacts/,
          },
        ],
      };
    }

    if (
      pathname.includes("/crm/all-deals") ||
      pathname.includes("/crm/my-deals")
    ) {
      return {
        entity: "deal",
        title: "Deals Workspace",
        subtitle: "Track pipeline movement, value, and close confidence.",
        views: [
          { label: "All", href: "/crm/all-deals", match: /\/crm\/all-deals/ },
          { label: "Mine", href: "/crm/my-deals", match: /\/crm\/my-deals/ },
          {
            label: "Pipeline",
            href: pathname.includes("/crm/my-deals")
              ? "/crm/my-pipeline"
              : "/crm/all-pipeline",
            match: /\/crm\/(my|all)-pipeline/,
          },
        ],
      };
    }

    if (
      pathname.includes("/crm/all-organizations") ||
      pathname.includes("/crm/my-organizations")
    ) {
      return {
        entity: "organization",
        title: "Organizations Workspace",
        subtitle: "Manage account health and company-level relationships.",
        views: [
          {
            label: "All",
            href: "/crm/all-organizations",
            match: /\/crm\/all-organizations/,
          },
          {
            label: "Mine",
            href: "/crm/my-organizations",
            match: /\/crm\/my-organizations/,
          },
        ],
      };
    }

    if (
      pathname.includes("/crm/all-activities") ||
      pathname.includes("/crm/my-activities")
    ) {
      return {
        entity: "activity",
        title: "Activities Workspace",
        subtitle: "Stay on top of calls, meetings, and follow-ups.",
        views: [
          {
            label: "All",
            href: "/crm/all-activities",
            match: /\/crm\/all-activities/,
          },
          {
            label: "Mine",
            href: "/crm/my-activities",
            match: /\/crm\/my-activities/,
          },
        ],
      };
    }

    return null;
  }

  // Icon SVGs for keyboard hints
  var HINT_ICONS = {
    '/':   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>',
    'Esc': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>',
    'N':   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>',
  };

  function createHints(addUrl) {
    var hints = [
      { key: "/", text: "Focus search" },
      { key: "Esc", text: "Clear search" },
      { key: "N", text: "Create new" },
    ];

    var hintHtml = hints
      .map(function (item) {
        var icon = HINT_ICONS[item.key] || '';
        return (
          '<span class="crm-kbd-hint">' +
          (icon ? '<span class="crm-kbd-hint__icon">' + icon + '</span>' : '') +
          '<kbd>' + item.key + '</kbd>' +
          '<span class="crm-kbd-hint__text">' + item.text + '</span>' +
          '</span>'
        );
      })
      .join("");

    return (
      '<div class="crm-kbd-hints" data-add-url="' +
      (addUrl || "") +
      '">' +
      hintHtml +
      "</div>"
    );
  }

  Drupal.behaviors.crmListToolbar = {
    attach: function (context) {
      var pages = context.querySelectorAll
        ? context.querySelectorAll(
            ".contacts-page, .deals-page, .orgs-page, .acts-page",
          )
        : [];

      pages.forEach(function (pageEl) {
        if (pageEl.dataset.clickupToolbarReady === "1") {
          return;
        }

        var filterBar = pageEl.querySelector(".filter-bar");
        if (!filterBar) {
          return;
        }

        var config = getConfig(globalThis.location.pathname);
        if (!config) {
          return;
        }

        var titleEl = pageEl.querySelector(".page-title");
        var subtitleEl = pageEl.querySelector(".page-subtitle");
        var titleText = titleEl ? titleEl.textContent.trim() : config.title;
        var subtitleText = subtitleEl
          ? subtitleEl.textContent.trim()
          : config.subtitle;

        var addLink = pageEl.querySelector(".page-actions .btn-primary");
        var addUrl = addLink ? addLink.getAttribute("href") : "";

        var segments = config.views
          .map(function (view) {
            var isActive = view.match.test(globalThis.location.pathname);
            return (
              '<a class="crm-segment' +
              (isActive ? " is-active" : "") +
              '" href="' +
              view.href +
              '">' +
              view.label +
              "</a>"
            );
          })
          .join("");

        var toolbarHtml =
          '<div class="crm-list-toolbar" data-entity="' +
          config.entity +
          '">' +
          '<div class="crm-list-toolbar__right crm-list-toolbar__right--full">' +
          '<nav class="crm-segmented" aria-label="View controls">' +
          segments +
          "</nav>" +
          createHints(addUrl) +
          "</div>" +
          "</div>";

        filterBar.insertAdjacentHTML("beforebegin", toolbarHtml);
        pageEl.dataset.clickupToolbarReady = "1";
      });
    },
  };
})(Drupal);
