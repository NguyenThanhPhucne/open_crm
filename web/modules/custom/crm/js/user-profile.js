(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.crmUserProfile = {
    attach: function (context, settings) {
      once("crm-user-profile", "body", context).forEach(function () {
        // Init Lucide icons
        if (typeof lucide !== "undefined" && lucide.createIcons) {
          setTimeout(() => lucide.createIcons(), 60);
        }

        // Animated count-up for stat values
        const statEls = document.querySelectorAll(".stat-value[data-count]");
        if (statEls.length && "IntersectionObserver" in window) {
          const observer = new IntersectionObserver(
            (entries) => {
              entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                observer.unobserve(entry.target);
                const el = entry.target;
                const target = parseInt(el.dataset.count, 10);
                if (isNaN(target) || target === 0) return;
                const duration = 700;
                let startTs = null;
                const step = (ts) => {
                  if (!startTs) startTs = ts;
                  const progress = Math.min((ts - startTs) / duration, 1);
                  // Ease-out cubic
                  const eased = 1 - Math.pow(1 - progress, 3);
                  el.textContent = Math.round(eased * target);
                  if (progress < 1) requestAnimationFrame(step);
                  else el.textContent = target;
                };
                requestAnimationFrame(step);
              });
            },
            { threshold: 0.4 },
          );
          statEls.forEach((el) => observer.observe(el));
        }
      });
    },
  };
})(Drupal, once);
