/**
 * CRM Register Form JavaScript
 */
(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.crmRegisterForm = {
    attach: function (context, settings) {
      var $form = $(".crm-register-form", context).once("crm-register-form");

      if ($form.length === 0) {
        return;
      }

      // Add loading state on submit
      $form.on("submit", function () {
        $(this).find(".auth-form-column").addClass("is-loading");
      });

      // Auto-focus lastname field
      $form.find('input[name="lastname"]').focus();
    },
  };
})(jQuery, Drupal);
