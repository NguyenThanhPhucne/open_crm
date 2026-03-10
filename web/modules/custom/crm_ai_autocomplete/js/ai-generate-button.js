(function () {
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

  // Function to show toast notification
  function showToast(message, type) {
    const iconMap = {
      success: "check-circle",
      error: "alert-circle",
      warning: "alert-triangle",
      info: "info",
    };

    const icon = iconMap[type] || "info";

    const toastHtml = `
      <div class="crm-toast crm-toast-${type}">
        <div class="crm-toast-content">
          <svg class="crm-toast-icon" data-lucide="${icon}" width="20" height="20"></svg>
          <span class="crm-toast-message">${message}</span>
        </div>
        <button class="crm-toast-close" type="button" aria-label="Close notification">
          <svg data-lucide="x" width="16" height="16"></svg>
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
      }, 1000); // 1s to match closing animation duration
    };

    closeBtn.addEventListener("click", removeToast);

    // Auto dismiss after 2 seconds
    const dismissTime = 2000;
    setTimeout(() => {
      if (toastEl.parentNode) {
        removeToast();
      }
    }, dismissTime);
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
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Save toast message to localStorage and redirect
          localStorage.setItem(
            "crmToast",
            JSON.stringify({
              message: "New contact created successfully!",
              type: "success",
            }),
          );
          setTimeout(() => {
            window.location.href = data.entity_url;
          }, 100);
        } else {
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
        // Restore button on error
        button.disabled = false;
        button.classList.remove("ai-generating");
        button.innerHTML = originalHTML;
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
