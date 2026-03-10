/**
 * CRM Register Form JavaScript
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.crmRegisterForm = {
    attach: function (context, settings) {
      var $form = $(".crm-register-form", context);

      $form.each(function () {
        if ($(this).data("crm-register-form-attached")) {
          return;
        }
        $(this).data("crm-register-form-attached", true);

        // Add loading state on submit
        $(this).on("submit", function () {
          $(this).find(".auth-form-column").addClass("is-loading");
        });

        // Auto-focus lastname field
        $(this).find('input[name="lastname"]').focus();
      });
    },
  };
})(jQuery, Drupal);
