(function ($, Drupal, drupalSettings) {
  "use strict";

  // Cache CSRF token for the lifetime of this page
  let _csrfToken = null;
  function getCsrfToken() {
    return _csrfToken
      ? Promise.resolve(_csrfToken)
      : fetch("/session/token")
          .then((r) => r.text())
          .then((t) => {
            _csrfToken = t.trim();
            return _csrfToken;
          });
  }

  Drupal.behaviors.crmQuickAdd = {
    attach: function (context, settings) {
      // Initialize Lucide icons
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
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

      /**
       * Submit form via AJAX
       */
      function submitForm(type, $form) {
        const $button = $form.find('[type="submit"]');
        const $message = $form.find("#quickadd-message");

        // Disable button and show loading
        $button.addClass("loading").prop("disabled", true);
        $message.hide();

        // Collect form data
        const formData = {};
        $form.find("input, select, textarea").each(function () {
          const $field = $(this);
          const name = $field.attr("name");
          const value = $field.val();
          if (name && value) {
            formData[name] = value;
          }
        });

        // Submit to endpoint (CSRF-protected)
        getCsrfToken().then((csrfToken) => {
          fetch("/crm/quickadd/" + type + "/submit", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-Token": csrfToken,
            },
            body: JSON.stringify(formData),
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.status === "success") {
                showMessage($message, data.message, "success");

                // Redirect after 1 second
                setTimeout(function () {
                  if (data.redirect) {
                    window.location.href = data.redirect;
                  } else {
                    window.history.back();
                  }
                }, 1000);
              } else {
                showMessage(
                  $message,
                  data.message || "Có lỗi xảy ra.",
                  "error",
                );
                $button.removeClass("loading").prop("disabled", false);
              }
            })
            .catch((error) => {
              console.error("Error:", error);
              showMessage(
                $message,
                "Có lỗi xảy ra. Vui lòng thử lại.",
                "error",
              );
              $button.removeClass("loading").prop("disabled", false);
            });
        }); // end getCsrfToken
      }

      /**
       * Check for duplicate phone/email
       */
      function checkDuplicate(field, value, selector) {
        fetch("/crm/quickadd/check-duplicate", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            field: field,
            value: value,
          }),
        })
          .then((response) => response.json())
          .then((data) => {
            if (data.exists) {
              $(selector).text(data.message).css("color", "#ef4444");
            } else {
              $(selector).text("").css("color", "");
            }
          })
          .catch((error) => {
            console.error("Validation error:", error);
          });
      }

      /**
       * Show message
       */
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

        // Reinitialize Lucide icons
        if (typeof lucide !== "undefined") {
          lucide.createIcons();
        }
      }

      // Add Lucide Icons CDN if not already loaded
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
