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
    },
  };
})(Drupal, once);
