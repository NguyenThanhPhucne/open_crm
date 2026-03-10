(function () {
  // Debug logs storage
  window.crmDebugLogs = [];

  // Intercept console.log to also store in window
  const originalLog = console.log;
  console.log = function () {
    window.crmDebugLogs.push(Array.from(arguments).join(" "));
    originalLog.apply(console, arguments);
  };

  // Intercept console.error too
  const originalError = console.error;
  console.error = function () {
    window.crmDebugLogs.push("ERROR: " + Array.from(arguments).join(" "));
    originalError.apply(console, arguments);
  };

  // Function to show debug panel
  function showDebugPanel() {
    const panel = document.createElement("div");
    panel.id = "crm-debug-panel";
    panel.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: #1e1e1e;
      color: #d4d4d4;
      padding: 15px;
      border-radius: 8px;
      max-width: 400px;
      max-height: 300px;
      overflow-y: auto;
      z-index: 10000;
      font-family: monospace;
      font-size: 12px;
      border: 1px solid #444;
      box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;

    const closeBtn = document.createElement("button");
    closeBtn.textContent = "✕";
    closeBtn.style.cssText = `
      position: absolute;
      top: 5px;
      right: 5px;
      background: none;
      border: none;
      color: #d4d4d4;
      cursor: pointer;
      font-size: 18px;
    `;
    closeBtn.onclick = () => panel.remove();

    const logs = window.crmDebugLogs.map((log) => `<div>${log}</div>`).join("");
    panel.innerHTML = `
      <div style="margin-bottom: 10px; font-weight: bold; color: #4ec9b0;">🐛 CRM Debug Logs</div>
      ${logs}
    `;
    panel.appendChild(closeBtn);
    document.body.appendChild(panel);
  }

  // Check if there are logs from previous page
  window.addEventListener("DOMContentLoaded", function () {
    if (window.crmDebugLogs.length > 0) {
      setTimeout(showDebugPanel, 100);
    }
  });

  // Check if there's a pending toast from a previous page reload
  window.addEventListener("DOMContentLoaded", function () {
    const pendingToast = localStorage.getItem("crmToast");
    if (pendingToast) {
      try {
        const toastData = JSON.parse(pendingToast);
        localStorage.removeItem("crmToast");
        // Show the toast after page is fully loaded
        setTimeout(() => {
          showToast(toastData.message, toastData.type);
        }, 300);
      } catch (e) {
        console.error("Error parsing toast data:", e);
      }
    }
  });

  // Function to show loading modal (center screen)
  function showLoadingModal() {
    const loadingHtml = `
      <div class="crm-loading-overlay">
        <div class="crm-loading-modal">
          <div class="crm-loading-ring"></div>
          <div class="crm-loading-text with-dots">Generating new contact</div>
        </div>
      </div>
    `;
    document.body.insertAdjacentHTML("beforeend", loadingHtml);
    return document.querySelector(".crm-loading-overlay:last-child");
  }

  // Function to hide loading modal
  function hideLoadingModal(loadingOverlay) {
    if (loadingOverlay && loadingOverlay.parentNode) {
      loadingOverlay.remove();
    }
  }

  // Function to show toast notification
  function showToast(message, type) {
    const iconMap = {
      success: "check-circle",
      error: "alert-circle",
      warning: "alert-triangle",
      info: "info",
    };

    const icon = iconMap[type] || "info";

    // Determine if this is a "new contact created" modal or regular toast
    const isNewContactModal = message.includes(
      "New contact created successfully",
    );

    if (isNewContactModal) {
      // Show as center screen modal
      const toastHtml = `
        <div class="crm-toast-overlay">
          <div class="crm-toast crm-toast-${type} crm-toast-modal">
            <svg class="crm-toast-icon" data-lucide="${icon}"></svg>
            <div class="crm-toast-content">
              <p class="crm-toast-message">${message}</p>
            </div>
          </div>
        </div>
      `;
      document.body.insertAdjacentHTML("beforeend", toastHtml);

      const overlayEl = document.querySelector(".crm-toast-overlay:last-child");
      const toastEl = overlayEl.querySelector(".crm-toast");

      // Initialize Lucide icons in toast
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }

      // Trigger animation
      setTimeout(() => {
        toastEl.classList.add("show");
      }, 10);

      // Auto dismiss after 3 seconds
      const dismissTime = 3000;
      setTimeout(() => {
        if (toastEl && toastEl.parentNode) {
          toastEl.classList.add("closing");
          setTimeout(() => {
            if (overlayEl.parentNode) {
              overlayEl.remove();
            }
          }, 300);
        }
      }, dismissTime);

      // Allow clicking overlay to close
      overlayEl.addEventListener("click", (e) => {
        if (e.target === overlayEl) {
          toastEl.classList.add("closing");
          setTimeout(() => {
            if (overlayEl.parentNode) {
              overlayEl.remove();
            }
          }, 300);
        }
      });
    } else {
      // Show as top-right toast (for edit, delete, updates)
      const toastHtml = `
        <div class="crm-toast crm-toast-${type}">
          <svg class="crm-toast-icon" data-lucide="${icon}"></svg>
          <div class="crm-toast-content">
            <p class="crm-toast-message">${message}</p>
          </div>
          <button class="crm-toast-close" type="button" aria-label="Close toast">
            <svg data-lucide="x"></svg>
          </button>
        </div>
      `;
      document.body.insertAdjacentHTML("beforeend", toastHtml);

      const toastEl = document.querySelector(".crm-toast:last-child");

      // Initialize Lucide icons in toast
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }

      // Trigger animation
      setTimeout(() => {
        toastEl.classList.add("show");
      }, 10);

      // Close button handler
      const closeBtn = toastEl.querySelector(".crm-toast-close");
      const removeToast = () => {
        toastEl.classList.add("closing");
        setTimeout(() => {
          if (toastEl.parentNode) {
            toastEl.remove();
          }
        }, 300);
      };

      closeBtn.addEventListener("click", removeToast);

      // Auto dismiss after 3 seconds
      setTimeout(() => {
        if (toastEl.parentNode) {
          removeToast();
        }
      }, 3000);
    }
  }

  function initAIGenerateButton() {
    // Find the button by ID first (for direct button rendering)
    let button = document.getElementById("crm-ai-generate-btn");

    // If not found, try to find it as a local action link
    if (!button) {
      // Look for a link that contains "Generate data" in the local actions area
      const localActionsBlock = document.getElementById(
        "block-gin-local-actions",
      );
      if (localActionsBlock) {
        const links = localActionsBlock.querySelectorAll("a");
        for (let link of links) {
          if (link.textContent.includes("Generate data")) {
            button = link;
            button.id = "crm-ai-generate-btn";
            break;
          }
        }
      }
    }

    if (!button) return;

    button.addEventListener("click", function (e) {
      e.preventDefault();
      crmAIGenerateSimple(button);
    });
  }

  function crmAIGenerateSimple(button) {
    const originalText = button.textContent;
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.classList.add("ai-generating");

    // Create professional loading state HTML
    const loadingHTML = `
      <span class="ai-loading-spinner">
        <svg class="ai-spinner-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 2.2"/>
        </svg>
        <span class="ai-loading-text">Generating...</span>
      </span>
    `;
    button.innerHTML = loadingHTML;

    // Show loading modal (center screen)
    const loadingOverlay = showLoadingModal();

    // Get CSRF token from meta tag or from Drupal settings
    let csrfToken = "";
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) {
      csrfToken = metaToken.content;
    } else if (window.drupalSettings && window.drupalSettings.csrf_token) {
      csrfToken = window.drupalSettings.csrf_token;
    }

    fetch("/api/crm/ai/auto-create", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": csrfToken,
      },
      body: JSON.stringify({
        entityType: "contact",
        bundle: "contact",
      }),
    })
      .then((response) => {
        console.log("API Response Status:", response.status);
        if (!response.ok) {
          throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        // Try to parse as JSON, if it fails, log the response text
        return response.text().then((text) => {
          console.log("API Response Text:", text.substring(0, 500));
          try {
            const data = JSON.parse(text);
            console.log("API Response Data:", data);
            return data;
          } catch (e) {
            console.error(
              "Response was not valid JSON. Raw response:",
              text.substring(0, 500),
            );
            throw new Error(
              "Server returned invalid response: " + text.substring(0, 100),
            );
          }
        });
      })
      .then((data) => {
        console.log("Processing response, success:", data.success);
        if (data.success) {
          // Save toast message to localStorage and redirect
          const provider = data.provider || "unknown";
          console.log("Contact created with provider:", provider);
          console.log("Redirecting to:", data.entity_url);
          console.log(
            "*** ALLOW 5 SECONDS TO READ THIS OUTPUT BEFORE REDIRECT ***",
          );

          // Hide loading modal before redirect
          hideLoadingModal(loadingOverlay);

          localStorage.setItem(
            "crmToast",
            JSON.stringify({
              message: `New contact created successfully! (Provider: ${provider})`,
              type: "success",
            }),
          );
          setTimeout(() => {
            window.location.href = data.entity_url;
          }, 1500); // 1.5 second delay for debug panel visibility
        } else {
          // Hide loading modal on error
          hideLoadingModal(loadingOverlay);
          // Restore button on error
          button.disabled = false;
          button.classList.remove("ai-generating");
          button.innerHTML = originalHTML;
          showToast(
            "Error: " + (data.message || "Failed to generate contact"),
            "error",
          );
        }
      })
      .catch((error) => {
        // Hide loading modal on error
        hideLoadingModal(loadingOverlay);
        // Restore button on error
        button.disabled = false;
        button.classList.remove("ai-generating");
        button.innerHTML = originalHTML;
        console.error("AI generation error:", error);
        showToast("Error: " + error.message, "error");
      });
  }

  // Initialize when DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAIGenerateButton);
  } else {
    initAIGenerateButton();
  }
})();
