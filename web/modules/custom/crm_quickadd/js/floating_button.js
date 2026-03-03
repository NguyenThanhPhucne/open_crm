(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmFloatingButton = {
    attach: function (context, settings) {
      const fabButton = once("crm-fab", "#crm-fab-button", context);

      if (fabButton.length > 0) {
        const button = fabButton[0];
        const menu = document.getElementById("crm-fab-menu");

        // Create overlay
        let overlay = document.querySelector(".crm-fab-overlay");
        if (!overlay) {
          overlay = document.createElement("div");
          overlay.className = "crm-fab-overlay";
          document.body.appendChild(overlay);
        }

        // Toggle menu on button click
        button.addEventListener("click", function (e) {
          e.stopPropagation();
          toggleMenu();
        });

        // Close menu when clicking overlay
        overlay.addEventListener("click", function () {
          closeMenu();
        });

        // Close menu on ESC key
        document.addEventListener("keydown", function (e) {
          if (e.key === "Escape") {
            closeMenu();
          }
        });

        function toggleMenu() {
          const isActive = button.classList.contains("active");

          if (isActive) {
            closeMenu();
          } else {
            openMenu();
          }
        }

        function openMenu() {
          button.classList.add("active");
          menu.classList.add("active");
          overlay.classList.add("active");

          // Initialize Lucide icons
          if (typeof lucide !== "undefined") {
            lucide.createIcons();
          }
        }

        function closeMenu() {
          button.classList.remove("active");
          menu.classList.remove("active");
          overlay.classList.remove("active");
        }
      }

      // Initialize Lucide icons
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }
    },
  };
})(Drupal, once);
