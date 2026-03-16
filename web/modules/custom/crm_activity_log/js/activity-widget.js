(function ($, Drupal, drupalSettings) {
  "use strict";

  // Cache CSRF token for the lifetime of this page
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

  Drupal.behaviors.crmActivityLog = {
    attach: function (context, settings) {
      // Initialize Lucide icons
      if (typeof lucide !== "undefined") {
        lucide.createIcons();
      }

      // Log Call Form Handler
      $("#log-call-form", context).each(function () {
        if (!$(this).data("log-call-attached")) {
          $(this).data("log-call-attached", true);
          $(this).on("submit", function (e) {
            e.preventDefault();
            submitActivityForm("log-call", $(this));
          });
        }
      });

      // Schedule Meeting Form Handler
      $("#schedule-meeting-form", context).each(function () {
        if (!$(this).data("schedule-meeting-attached")) {
          $(this).data("schedule-meeting-attached", true);
          $(this).on("submit", function (e) {
            e.preventDefault();
            submitActivityForm("schedule-meeting", $(this));
          });
        }
      });

      /**
       * Submit activity form via AJAX
       */
      function submitActivityForm(type, $form) {
        const $button = $form.find('[type="submit"]');
        const $message = $form.find("#activity-message");
        const contactId = $form.find('input[name="contact_id"]').val();

        // Disable button and show loading
        $button.addClass("loading").prop("disabled", true);
        $message.hide();

        // Collect form data
        const formData = {};
        $form.find("input, select, textarea").each(function () {
          const $field = $(this);
          const name = $field.attr("name");
          const value = $field.val();
          if (name && name !== "contact_id" && value) {
            formData[name] = value;
          }
        });

        // Special handling for meeting date + time
        if (type === "schedule-meeting") {
          const date = formData.meeting_date;
          const time = formData.meeting_time || "14:00";
          formData.meeting_date = date + "T" + time + ":00";
          delete formData.meeting_time;
        }

        // Determine endpoint
        const endpoint = "/crm/activity/" + type + "/" + contactId + "/submit";

        // Submit to endpoint (CSRF-protected)
        getCsrfToken().then((csrfToken) => {
          fetch(endpoint, {
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
       * Show message
       */
      function showMessage($element, message, type) {
        const icon = type === "success" ? "check-circle" : "alert-circle";
        $element
          .removeClass("success error")
          .addClass(type)
          .html('<i data-lucide="' + icon + '"></i>' + message)
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

      // Auto-update deal dropdown based on contact
      function loadContactDeals(contactId, selectId) {
        const $select = $("#" + selectId);
        if ($select.length === 0 || !contactId) return;

        fetch(
          "/jsonapi/node/deal?filter[field_contact.id]=" +
            contactId +
            "&sort=-created&page[limit]=50",
        )
          .then((response) => response.json())
          .then((data) => {
            if (data.data && data.data.length > 0) {
              data.data.forEach((deal) => {
                const option = document.createElement("option");
                // Extract UUID from data.id
                const uuid = deal.id;
                // We need to get the node ID, but JSON:API returns UUID
                // For now, we'll use a different approach
                option.value = deal.attributes.drupal_internal__nid || deal.id;
                option.textContent = deal.attributes.title;
                $select.append(option);
              });
            }
          })
          .catch((error) => {
            console.error("Error loading deals:", error);
          });
      }

      // Smooth scroll animations
      $(".activity-item", context).each(function (index) {
        if ($(this).data("activity-animate-attached")) {
          return;
        }
        $(this).data("activity-animate-attached", true);
        $(this)
          .css({
            opacity: "0",
            transform: "translateY(20px)",
          })
          .delay(index * 50)
          .animate(
            {
              opacity: "1",
            },
            300,
          )
          .css("transform", "translateY(0)");
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
