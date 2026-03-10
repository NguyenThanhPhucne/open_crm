/**
 * CRM Login Form JavaScript
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.crmLoginForm = {
    attach: function (context, settings) {
      var $form = $(".crm-login-form", context);
      $form.each(function () {
        if ($(this).data("crm-login-form-attached")) {
          return;
        }
        $(this).data("crm-login-form-attached", true);

        // Add loading state on submit
        $(this).on("submit", function () {
          $(this).find(".auth-form-column").addClass("is-loading");
        });

        // Auto-focus username field
        $(this).find('input[name="username"]').focus();
      });
    },
  };
})(jQuery, Drupal);
