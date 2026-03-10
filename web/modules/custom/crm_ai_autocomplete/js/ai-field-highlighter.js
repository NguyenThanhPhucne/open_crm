(function (Drupal, $) {
  "use strict";

  Drupal.behaviors.crm_ai_field_highlighter = {
    attach: function (context, settings) {
      // Apply field highlighting for AI-generated fields
      $(context)
        .find('[data-ai-generated="true"]')
        .each(function () {
          if (!$(this).data("ai-field-highlighted-done")) {
            $(this).data("ai-field-highlighted-done", true);
            highlightField($(this));
          }
        });
    },
  };

  /**
   * Highlight an AI-generated field.
   */
  function highlightField($field) {
    var $wrapper =
      $field.closest(".form-group") ||
      $field.closest(".form-item") ||
      $field.parent();

    // Add visual indicators
    $field.addClass("crm-ai-generated");
    $wrapper.addClass("crm-ai-field-highlighted");

    // Add badge with confidence
    if (!$wrapper.find(".ai-badge").length) {
      var confidence = $field.data("ai-confidence") || 0.85;
      var confidencePercent = Math.round(confidence * 100);
      var $badge = $(
        '<span class="ai-badge" title="AI-generated suggestion with ' +
          confidencePercent +
          '% confidence">' +
          confidencePercent +
          "%</span>",
      );
      $wrapper.append($badge);
    }

    // Add tooltip on hover
    $field.on("mouseenter", function () {
      var $tooltip = $('<span class="ai-tooltip">AI Suggested</span>');
      $wrapper.append($tooltip);

      $field.on("mouseleave", function () {
        $tooltip.remove();
      });
    });
  }

  /**
   * Remove AI marking from field.
   */
  function removeAIMarking($field) {
    var $wrapper =
      $field.closest(".form-group") ||
      $field.closest(".form-item") ||
      $field.parent();
    $field.removeClass("crm-ai-generated");
    $wrapper.removeClass("crm-ai-field-highlighted");
    $wrapper.find(".ai-badge").remove();
    $wrapper.find(".ai-tooltip").remove();
    $field.removeAttr("data-ai-generated");
  }

  // Expose to global Drupal object for external use
  Drupal.crmAI = {
    highlightField: highlightField,
    removeAIMarking: removeAIMarking,
  };
})(Drupal, jQuery);
