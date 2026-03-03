(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmUserProfile = {
    attach: function (context, settings) {
      // Initialize Lucide icons when page loads
      once("crm-user-profile-icons", "body", context).forEach(function () {
        if (typeof lucide !== "undefined" && lucide.createIcons) {
          // Small delay to ensure all DOM elements are ready
          setTimeout(function () {
            lucide.createIcons();
          }, 100);
        }
      });
    },
  };
})(Drupal, once);
