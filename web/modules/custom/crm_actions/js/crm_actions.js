(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmActions = {
    attach: function (context, settings) {
      // Initialize Lucide icons
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }

      // Quick Add Dropdown Toggle
      const toggleButtons = once(
        "quick-add-toggle",
        "#crm-quick-add-toggle",
        context,
      );

      if (toggleButtons.length > 0) {
        const button = toggleButtons[0];
        const menu = document.getElementById("crm-quick-add-menu");

        button.addEventListener("click", function (e) {
          e.stopPropagation();
          menu.classList.toggle("active");

          // Reinitialize Lucide icons
          if (typeof lucide !== "undefined") {
            setTimeout(() => lucide.createIcons(), 50);
          }
        });

        // Close dropdown when clicking outside
        document.addEventListener("click", function (e) {
          if (menu && !menu.contains(e.target) && e.target !== button) {
            menu.classList.remove("active");
          }
        });

        // Close on ESC key
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape" && menu) {
            menu.classList.remove("active");
          }
        });
      }

      // Override Drupal Core AJAX throbber with CRM Skeleton
      if (Drupal.theme) {
        Drupal.theme.ajaxProgressThrobber = function (message) {
          return '<div class="ajax-progress ajax-progress-throbber"><div class="crm-skeleton-row" style="width: 100%; border: none;"><div class="col-main"><div class="crm-skeleton crm-skeleton-animate crm-skeleton--title"></div><div class="crm-skeleton crm-skeleton-animate crm-skeleton--text crm-skeleton--text-medium"></div></div></div></div>';
        };
        Drupal.theme.ajaxProgressIndicatorFullscreen = function () {
          return '<div class="ajax-progress ajax-progress-fullscreen"><div class="crm-skeleton-row" style="width: 100%; background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"><div class="col-main"><div class="crm-skeleton crm-skeleton-animate crm-skeleton--title"></div><div class="crm-skeleton crm-skeleton-animate crm-skeleton--text crm-skeleton--text-short"></div></div></div></div>';
        };
      }
    },
  };
})(Drupal, once);
