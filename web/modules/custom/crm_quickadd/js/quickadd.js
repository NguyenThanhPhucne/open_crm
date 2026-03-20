(function ($, Drupal, drupalSettings) {
  "use strict";

  /* ═══════════════════════════════════════════════════════════════════════
     CSRF token cache
     ═══════════════════════════════════════════════════════════════════════ */
  let _csrfToken = null;
  function getCsrfToken() {
    return _csrfToken
      ? Promise.resolve(_csrfToken)
      : fetch("/session/token", { credentials: "same-origin" })
          .then((r) => r.text())
          .then((t) => {
            _csrfToken = t.trim();
            return _csrfToken;
          });
  }

  /* ═══════════════════════════════════════════════════════════════════════
     CRMQuickAdd — in-page modal controller
     Works exactly like CRMInlineEdit: opens a modal overlay ON the current
     page so the background the user sees is always the page they came from.
     After a successful save the user is sent to the new entity's own page.
     ═══════════════════════════════════════════════════════════════════════ */
  window.CRMQuickAdd = {
    openModal: function (type) {
      // Prevent double-open
      if (document.getElementById("crm-qa-overlay")) return;

      // Close the FAB menu if it's open
      var fabBtn = document.getElementById("crm-fab-button");
      var fabMenu = document.getElementById("crm-fab-menu");
      var fabOver = document.querySelector(".crm-fab-overlay");
      if (fabBtn) fabBtn.classList.remove("active");
      if (fabMenu) fabMenu.classList.remove("active");
      if (fabOver) fabOver.classList.remove("active");

      // Build the loading overlay
      var overlay = document.createElement("div");
      overlay.id = "crm-qa-overlay";
      overlay.className = "crm-qa-overlay";
      overlay.innerHTML =
        '<div class="crm-qa-loader">' +
        '<div class="crm-qa-spinner"></div>' +
        "</div>";
      document.body.appendChild(overlay);
      document.body.style.overflow = "hidden";

      // Animate in
      requestAnimationFrame(function () {
        overlay.classList.add("crm-qa-visible");
      });

      // Fetch the quickadd form page and extract just the form container
      fetch("/crm/quickadd/" + type, { credentials: "same-origin" })
        .then(function (r) {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.text();
        })
        .then(function (html) {
          var doc = new DOMParser().parseFromString(html, "text/html");
          var container = doc.querySelector(".crm-modal-container");
          if (!container) {
            CRMQuickAdd.closeModal();
            return;
          }

          // Replace spinner with actual form
          overlay.innerHTML = "";
          overlay.appendChild(container);

          // Override every close / cancel control
          overlay
            .querySelectorAll(
              '[onclick*="history.back"], .quickadd-close, .crm-modal-close, .btn-secondary, .btn-cancel',
            )
            .forEach(function (btn) {
              btn.removeAttribute("onclick");
              btn.onclick = null;
              btn.addEventListener("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                CRMQuickAdd.closeModal();
              });
            });

          // Click outside the card → close
          overlay.addEventListener("click", function (e) {
            if (e.target === overlay) CRMQuickAdd.closeModal();
          });

          // ESC → close
          overlay._esc = function (e) {
            if (e.key === "Escape") CRMQuickAdd.closeModal();
          };
          document.addEventListener("keydown", overlay._esc);

          // Wire up Drupal behaviors (form submit, validation, etc.)
          if (typeof Drupal !== "undefined") {
            Drupal.attachBehaviors(overlay);
          }
          if (typeof lucide !== "undefined") lucide.createIcons();
        })
        .catch(function () {
          CRMQuickAdd.closeModal();
        });
    },

    closeModal: function () {
      var overlay = document.getElementById("crm-qa-overlay");
      if (!overlay) return;
      if (overlay._esc) {
        document.removeEventListener("keydown", overlay._esc);
      }
      overlay.classList.remove("crm-qa-visible");
      setTimeout(function () {
        overlay.remove();
        document.body.style.overflow = "";
      }, 220);
    },
  };

  /* ═══════════════════════════════════════════════════════════════════════
     Global link interceptor — catches quickadd links anywhere on the page
     (nav buttons, FAB items, page headers, etc.)
     ═══════════════════════════════════════════════════════════════════════ */
  document.addEventListener(
    "click",
    function (e) {
      var a = e.target.closest("a[href]");
      if (!a) return;
      var href = a.getAttribute("href") || "";
      var m = href.match(/^\/crm\/quickadd\/(contact|deal|organization)$/);
      if (m) {
        e.preventDefault();
        CRMQuickAdd.openModal(m[1]);
      }
    },
    true, // capture phase so it fires before any other handlers
  );

  /* ═══════════════════════════════════════════════════════════════════════
     Drupal behaviors — form submit, validation, inline org, etc.
     ═══════════════════════════════════════════════════════════════════════ */
  Drupal.behaviors.crmQuickAdd = {
    attach: function (context, settings) {
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }

      // Backdrop click on STANDALONE pages (when not in modal overlay)
      $(".quickadd-modal-wrapper", context).each(function () {
        if (!$(this).data("backdrop-close-attached")) {
          $(this).data("backdrop-close-attached", true);
          $(this).on("click", function (e) {
            if ($(e.target).is(".quickadd-modal-wrapper")) {
              window.history.back();
            }
          });
        }
      });

      // ESC on STANDALONE pages
      if (!$(document).data("quickadd-esc-attached")) {
        $(document).data("quickadd-esc-attached", true);
        $(document).on("keydown.quickadd", function (e) {
          if (
            e.key === "Escape" &&
            $(".quickadd-modal-wrapper").length &&
            !document.getElementById("crm-qa-overlay")
          ) {
            window.history.back();
          }
        });
      }

      // Contact Form Handler
      $("#quickadd-contact-form", context).each(function () {
        if (!$(this).data("quickadd-contact-attached")) {
          $(this).data("quickadd-contact-attached", true);
          $(this).on("submit", function (e) {
            e.preventDefault();
            submitForm("contact", $(this));
          });
        }
      });

      // Deal Form Handler
      $("#quickadd-deal-form", context).each(function () {
        if (!$(this).data("quickadd-deal-attached")) {
          $(this).data("quickadd-deal-attached", true);
          $(this).on("submit", function (e) {
            e.preventDefault();
            submitForm("deal", $(this));
          });
        }
      });

      // Organization Form Handler
      $("#quickadd-organization-form", context).each(function () {
        if (!$(this).data("quickadd-organization-attached")) {
          $(this).data("quickadd-organization-attached", true);
          $(this).on("submit", function (e) {
            e.preventDefault();
            submitForm("organization", $(this));
          });
        }
      });

      // Show/hide inline organization fields
      $("#contact-organization", context).each(function () {
        if (!$(this).data("org-toggle-attached")) {
          $(this).data("org-toggle-attached", true);
          $(this).on("change", function () {
            if ($(this).val() === "__new__") {
              $("#inline-org-fields").slideDown(200);
              $("#inline-org-name").prop("required", true);
            } else {
              $("#inline-org-fields").slideUp(200);
              $("#inline-org-name").prop("required", false);
            }
          });
        }
      });

      // Real-time validation for phone
      $("#contact-phone", context).each(function () {
        if (!$(this).data("phone-validate-attached")) {
          $(this).data("phone-validate-attached", true);
          $(this).on("blur", function () {
            const phone = $(this).val();
            if (phone.length > 0) {
              checkDuplicate("field_phone", phone, "#phone-validation");
            }
          });
        }
      });

      // Real-time validation for email
      $("#contact-email", context).each(function () {
        if (!$(this).data("email-validate-attached")) {
          $(this).data("email-validate-attached", true);
          $(this).on("blur", function () {
            const email = $(this).val();
            if (email.length > 0) {
              checkDuplicate("field_email", email, "#email-validation");
            }
          });
        }
      });

      /* ── Submit handler ─────────────────────────────────────────────── */
      function submitForm(type, $form) {
        const $container = $form.closest(".crm-modal-container");
        const $button = $container.find('[type="submit"]');
        const $message = $container.find("#quickadd-message");

        $button.addClass("loading").prop("disabled", true);
        $message.hide();

        const formData = {};
        $form.find("input, select, textarea").each(function () {
          const name = $(this).attr("name");
          const value = $(this).val();
          if (name && value) formData[name] = value;
        });

        getCsrfToken().then((csrfToken) => {
          fetch("/crm/quickadd/" + type + "/submit", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": csrfToken,
            },
            body: JSON.stringify(formData),
          })
            .then((r) => r.json())
            .then((data) => {
              if (data.status === "success") {
                showMessage($message, data.message, "success");

                setTimeout(function () {
                  // Always go to the newly created entity's page
                  var target = data.entity_id
                    ? "/node/" + data.entity_id
                    : data.redirect || null;

                  if (document.getElementById("crm-qa-overlay")) {
                    // Modal mode: close overlay first, then navigate
                    CRMQuickAdd.closeModal();
                    if (target) {
                      setTimeout(function () {
                        window.location.href = target;
                      }, 250);
                    }
                  } else if (target) {
                    window.location.href = target;
                  } else {
                    window.history.back();
                  }
                }, 800);
              } else {
                showMessage(
                  $message,
                  data.message || "An error occurred.",
                  "error",
                );
                $button.removeClass("loading").prop("disabled", false);
              }
            })
            .catch((error) => {
              console.error("Error:", error);
              showMessage(
                $message,
                "An error occurred. Please try again.",
                "error",
              );
              $button.removeClass("loading").prop("disabled", false);
            });
        });
      }

      /* ── Duplicate phone/email check ────────────────────────────────── */
      function checkDuplicate(field, value, selector) {
        getCsrfToken()
          .then((csrfToken) =>
            fetch("/crm/quickadd/check-duplicate", {
              method: "POST",
              headers: {
                "Content-Type": "application/json",
                "X-CSRF-Token": csrfToken,
              },
              body: JSON.stringify({ field: field, value: value }),
            }),
          )
          .then((r) => r.json())
          .then((data) => {
            if (data.exists) {
              $(selector).text(data.message).css("color", "#ef4444");
            } else {
              $(selector).text("").css("color", "");
            }
          })
          .catch((e) => console.error("Validation error:", e));
      }

      /* ── Status message ─────────────────────────────────────────────── */
      function showMessage($element, message, type) {
        $element
          .removeClass("success error")
          .addClass(type)
          .html(
            '<i data-lucide="' +
              (type === "success" ? "check-circle" : "alert-circle") +
              '"></i>' +
              message,
          )
          .slideDown(200);
        if (typeof lucide !== "undefined") lucide.createIcons();
      }

      if (!document.querySelector('script[src*="lucide"]')) {
        const script = document.createElement("script");
        script.src = "https://unpkg.com/lucide@latest";
        script.onload = function () {
          lucide.createIcons();
        };
        document.head.appendChild(script);
      }
    },
  };
})(jQuery, Drupal, drupalSettings);
