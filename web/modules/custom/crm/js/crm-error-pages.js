/**
 * @file
 * CRM Error Pages — minimal JS for interactive elements.
 */
(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmErrorPages = {
    attach: function (context) {
      // Auto-redirect countdown (optional, only if countdown element present)
      once("crm-error-pages", ".crm-error-page[data-redirect]", context).forEach(
        function (el) {
          var redirectUrl = el.getAttribute("data-redirect");
          var delay = parseInt(el.getAttribute("data-redirect-delay") || "8", 10);
          var counter = el.querySelector(".crm-error-redirect-counter");
          if (!redirectUrl || !counter) return;

          var remaining = delay;
          counter.textContent = remaining;

          var timer = setInterval(function () {
            remaining -= 1;
            counter.textContent = remaining;
            if (remaining <= 0) {
              clearInterval(timer);
              window.location.href = redirectUrl;
            }
          }, 1000);
        }
      );
    },
  };
})(Drupal, once);
