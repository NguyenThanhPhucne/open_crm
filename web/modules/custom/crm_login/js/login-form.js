/**
 * CRM Login Form JavaScript
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.crmLoginForm = {
    attach: function (context, settings) {
      var $form = $(".crm-login-form", context).once("crm-login-form");

      if ($form.length === 0) {
        return;
      }

      // Add loading state on submit
      $form.on("submit", function () {
        $(this).find(".auth-form-column").addClass("is-loading");
      });

      // Auto-focus username field
      $form.find('input[name="username"]').focus();
    },
  };
})(jQuery, Drupal);
