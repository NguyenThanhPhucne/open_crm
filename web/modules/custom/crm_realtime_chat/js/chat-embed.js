(function (Drupal, once, drupalSettings) {
  Drupal.behaviors.crmRealtimeChatEmbed = {
    attach: function attach() {
      once("crm-realtime-chat", "[data-crm-realtime-chat]").forEach(
        function (container) {
          const settings = drupalSettings.crmRealtimeChat || {};
          if (!settings.frontendUrl) {
            return;
          }

          const statusEl    = container.querySelector("[data-crm-chat-status]");
          const statusText  = statusEl ? statusEl.querySelector(".crm-chat-status-text") : null;
          const frameEl     = container.querySelector("[data-crm-chat-frame]");
          const frameWrap   = container.querySelector("#crm-chat-frame-wrap");
          const skeletonEl  = container.querySelector("#crm-chat-skeleton");
          const fallbackEl  = container.querySelector("[data-crm-chat-fallback]");

          if (!document.getElementById("crm-chat-embed-styles")) {
            const style = document.createElement("style");
            style.id = "crm-chat-embed-styles";
            style.textContent = `
              .typing-dots::after { content: ''; animation: typing 1.5s steps(4, end) infinite; }
              @keyframes typing { 0%, 20% { content: ''; } 40% { content: '.'; } 60% { content: '..'; } 80%, 100% { content: '...'; } }
              .is-connecting { background-color: #f59e0b !important; }
            `;
            document.head.appendChild(style);
          }

          // Helper: update the status bar
          function setStatus(state, text) {
            if (statusEl) {
              const dot = statusEl.querySelector('.crm-chat-status-dot');
              if (dot) {
                dot.classList.remove("is-ok", "is-error", "is-connecting");
                dot.classList.add("is-" + state);
              } else {
                statusEl.classList.remove("is-ok", "is-error", "is-connecting");
                statusEl.classList.add("is-" + state);
              }
            }
            if (statusText) { statusText.innerHTML = text; }
          }
          
          setStatus("connecting", "Connecting<span class='typing-dots'></span>");

          // Timeout: if iframe hasn't loaded in 10s, show error + retry
          const timeoutId = globalThis.setTimeout(function () {
            setStatus("error", "Chat is taking a while to load…");
            if (fallbackEl) {
              fallbackEl.hidden = false;
              // Add a retry button if not already present
              if (!fallbackEl.querySelector(".crm-chat-retry-btn[data-retry]")) {
                var retryBtn = document.createElement("button");
                retryBtn.className = "crm-chat-retry-btn";
                retryBtn.setAttribute("data-retry", "1");
                retryBtn.textContent = "↻ Retry";
                retryBtn.addEventListener("click", function () {
                  if (frameEl) {
                    setStatus("", "Reconnecting…");
                    fallbackEl.hidden = true;
                    frameEl.src = frameEl.src; // reload iframe
                  }
                });
                fallbackEl.appendChild(retryBtn);
              }
            }
            if (skeletonEl) { skeletonEl.style.display = "none"; }
          }, 10000);

          if (frameEl) {
            frameEl.addEventListener("load", function () {
              globalThis.clearTimeout(timeoutId);

              // Transition: hide skeleton, reveal frame
              if (skeletonEl) { skeletonEl.style.display = "none"; }
              if (frameWrap)  { frameWrap.style.display = ""; }
              // Small delay so the fade-in animation is visible
              globalThis.requestAnimationFrame(function () {
                frameEl.classList.add("is-loaded");
              });

              setStatus("ok", "Connected");
            });

            // Start loading the iframe AFTER the listener is attached
            // to guarantee we catch the load event, even from localhost.
            if (frameEl.dataset.src) {
              frameEl.src = frameEl.dataset.src;
            }
          }

          // Listen for postMessage from chat iframe (e.g. unread count)
          globalThis.addEventListener("message", function (evt) {
            // Only trust messages from the same origin as the chat URL
            try {
              var chatOrigin = new URL(settings.frontendUrl).origin;
              if (evt.origin !== chatOrigin) return;
            } catch (e) { return; }

            var data = evt.data || {};

            // Update unread badge on the launcher
            if (typeof data.unreadCount === "number") {
              var badge = document.querySelector(".crm-chat-launcher-badge");
              if (badge) {
                badge.textContent = data.unreadCount > 0 ? String(data.unreadCount) : "";
                badge.style.display = data.unreadCount > 0 ? "flex" : "none";
              }
            }
          });

          // Notify other components that the chat is mounted
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

