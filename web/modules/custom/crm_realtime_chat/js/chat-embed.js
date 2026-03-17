(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.crmRealtimeChatEmbed = {
    attach: function attach() {
      once("crm-realtime-chat", "[data-crm-realtime-chat]").forEach(
        function (container) {
          const settings = drupalSettings.crmRealtimeChat || {};
          if (!settings.frontendUrl) {
            return;
          }

          const statusEl = container.querySelector("[data-crm-chat-status]");
          const frameEl = container.querySelector("[data-crm-chat-frame]");
          const fallbackEl = container.querySelector(
            "[data-crm-chat-fallback]",
          );

          const timeoutId = globalThis.setTimeout(function () {
            if (statusEl) {
              statusEl.classList.remove("is-ok");
              statusEl.classList.add("is-error");
              statusEl.textContent =
                "Chat is taking longer than expected. Please wait or open full chat.";
            }
            if (fallbackEl) {
              fallbackEl.hidden = false;
            }
          }, 10000);

          if (frameEl) {
            frameEl.addEventListener("load", function () {
              globalThis.clearTimeout(timeoutId);
              if (statusEl) {
                statusEl.classList.remove("is-error");
                statusEl.classList.add("is-ok");
                statusEl.textContent = "Connected. Realtime chat is ready.";
              }
            });
          }

          // Reserved hook for future message passing between CRM and chat iframe.
          globalThis.dispatchEvent(
            new CustomEvent("crm-realtime-chat-mounted", {
              detail: settings,
            }),
          );
        },
      );
    },
  };
})(Drupal, once, drupalSettings);
