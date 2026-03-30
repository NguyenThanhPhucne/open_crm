/**
 * @file
 * CRM Empty States — Lucide icon initialization for empty state views.
 */
(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmEmptyStates = {
    attach: function (context) {
      // Initialize Lucide icons inside empty state blocks if the lib is loaded.
      once("crm-empty-states", ".crm-empty-state", context).forEach(
        function () {
          if (window.lucide && typeof window.lucide.createIcons === "function") {
            window.lucide.createIcons();
          }
        }
      );
    },
  };
})(Drupal, once);
