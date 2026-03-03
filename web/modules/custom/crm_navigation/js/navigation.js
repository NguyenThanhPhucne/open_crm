/**
 * @file
 * Initialize Lucide icons for CRM navigation.
 */

(function (Drupal) {
  "use strict";

  /**
   * Initialize Lucide icons when they are added to the page.
   */
  Drupal.behaviors.crmNavigationLucide = {
    attach: function (context, settings) {
      // Wait for lucide to be available
      if (typeof lucide !== "undefined") {
        // Initialize all lucide icons
        lucide.createIcons();
      } else {
        // Retry after a short delay if lucide is not yet loaded
        setTimeout(function () {
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }
        }, 100);
      }
    },
  };
})(Drupal);
