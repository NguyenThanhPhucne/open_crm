(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.crmRealtimeChatLauncher = {
    attach: function attach() {
      once("crm-chat-launcher", "body").forEach(function (body) {
        const settings = drupalSettings.crmRealtimeChatLauncher || {};
        const chatUrl = settings.chatUrl || "/crm/realtime-chat";
        const currentPath = settings.currentPath || "";

        if (currentPath === chatUrl) {
          return;
        }

        const launcher = document.createElement("a");
        launcher.href = chatUrl;
        launcher.className = "crm-chat-launcher";
        launcher.setAttribute("aria-label", "Open realtime chat");
        launcher.setAttribute("title", "Open Realtime Chat (Ctrl+Shift+C)");
        launcher.innerHTML =
          '<span class="crm-chat-launcher-icon" aria-hidden="true"><svg class="crm-chat-launcher-icon-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg></span><span>Realtime Chat</span><span class="crm-chat-launcher-badge" style="display:none;position:absolute;top:-6px;right:-6px;min-width:18px;height:18px;padding:0 4px;border-radius:50px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;align-items:center;justify-content:center;border:2px solid #fff;animation:crmBadgePulse 2s ease-in-out infinite;pointer-events:none"></span>';

        body.appendChild(launcher);

        globalThis.addEventListener("keydown", function(e) {
          if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'c') {
            e.preventDefault();
            launcher.click();
          }
        });

        const applySafePosition = function () {
          const fabContainer = document.querySelector(".crm-fab-container");
          if (!fabContainer) {
            launcher.style.setProperty("--crm-chat-launcher-right", "22px");
            launcher.style.setProperty("--crm-chat-launcher-bottom", "22px");
            return;
          }

          const rect = fabContainer.getBoundingClientRect();
          const viewportHeight = globalThis.innerHeight;
          const viewportWidth = globalThis.innerWidth;
          const baseSpacing = viewportWidth <= 720 ? 10 : 12;

          const safeBottom = Math.max(
            viewportHeight - rect.top + baseSpacing,
            22,
          );
          const safeRight = Math.max(
            viewportWidth - rect.right,
            viewportWidth <= 720 ? 14 : 22,
          );

          launcher.style.setProperty(
            "--crm-chat-launcher-right",
            safeRight + "px",
          );
          launcher.style.setProperty(
            "--crm-chat-launcher-bottom",
            safeBottom + "px",
          );
        };

        applySafePosition();
        globalThis.addEventListener("resize", applySafePosition);
      });
    },
  };
})(Drupal, once, drupalSettings);
