(function (Drupal, $) {
  'use strict';

  Drupal.behaviors.crm_ai_complete_button = {
    attach: function (context, settings) {
      // Handle AI Complete button click
      $(context)
        .find('.btn-ai-complete')
        .once('ai-complete-button')
        .on('click', function (e) {
          e.preventDefault();
          handleAIComplete.call(this, context, settings);
        });
    },
  };

  /**
   * Handle AI Complete button click.
   */
  function handleAIComplete(context, settings) {
    var $form = $(this).closest('form');
    var formId = $form.attr('id') || 'entity-form';
    var entityType = getEntityTypeFromForm($form);

    if (!entityType) {
      alert(Drupal.t('Could not determine entity type'));
      return;
    }

    // Show loading state
    var $button = $(this);
    var originalText = $button.val();
    $button.prop('disabled', true).val(Drupal.t('Generating...'));

    // Collect form data
    var formData = collectFormData($form);

    // Make API request
    $.ajax({
      url: '/api/crm/ai/autocomplete',
      method: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({
        entityType: entityType,
        fields: formData,
        nodeId: getNodeId($form),
      }),
      success: function (response) {
        if (response.success) {
          applySuggestions($form, response.suggestions);
          showSuccessMessage(Drupal.t('AI suggestions applied successfully!'));
        } else {
          showErrorMessage(response.message || Drupal.t('AI suggestion failed'));
        }
      },
      error: function (xhr, status, error) {
        showErrorMessage(Drupal.t('Error: @message', { '@message': error }));
      },
      complete: function () {
        // Restore button
        $button.prop('disabled', false).val(originalText);
      },
    });
  }

  /**
   * Get entity type from form.
   */
  function getEntityTypeFromForm($form) {
    var formId = $form.attr('id');

    // Extract bundle type from form ID or field
    if (formId) {
      var matches = formId.match(/node_(\w+)_(form|edit_form)/);
      if (matches && matches[1]) {
        var bundleMap = {
          contact: 'contact',
          deal: 'deal',
          organization: 'organization',
          activity: 'activity',
        };
        return bundleMap[matches[1]] || 'node';
      }
    }

    // Try to find type field
    var $typeField = $form.find('input[name="type"]');
    if ($typeField.length) {
      return $typeField.val();
    }

    return null;
  }

  /**
   * Collect form data.
   */
  function collectFormData($form) {
    var formData = {};
    var fieldMap = {
      title: 'title',
      'field_company[0][value]': 'field_company',
      'field_email[0][value]': 'field_email',
      'field_phone[0][value]': 'field_phone',
      'field_source[target_id]': 'field_source',
      'field_customer_type[target_id]': 'field_customer_type',
      'field_owner[target_id]': 'field_owner',
      'field_value[0][value]': 'field_value',
      'field_probability[0][value]': 'field_probability',
      'field_industry[target_id]': 'field_industry',
      'field_website[0][value]': 'field_website',
      'field_size[target_id]': 'field_size',
      'body[0][value]': 'body',
    };

    // Collect all form fields
    $form.find('input, textarea, select').each(function () {
      var $field = $(this);
      var name = $field.attr('name');
      var value = $field.val();

      if (name && value) {
        formData[name] = value;
      }
    });

    // Map to standard field names
    var mapped = {};
    Object.keys(fieldMap).forEach(function (key) {
      if (formData[key]) {
        mapped[fieldMap[key]] = formData[key];
      }
    });

    return Object.keys(mapped).length > 0 ? mapped : formData;
  }

  /**
   * Get node ID from form.
   */
  function getNodeId($form) {
    var $nid = $form.find('input[name="nid"]');
    return $nid.length ? $nid.val() : null;
  }

  /**
   * Apply suggestions to form.
   */
  function applySuggestions($form, suggestions) {
    if (!suggestions || typeof suggestions !== 'object') {
      return;
    }

    Object.keys(suggestions).forEach(function (fieldName) {
      var suggestion = suggestions[fieldName];
      var value = suggestion.value || suggestion;
      var $field = findFormField($form, fieldName);

      if ($field.length) {
        $field.val(value);
        markAsAIGenerated($field, suggestion);
        $field.trigger('change');
      }
    });
  }

  /**
   * Find form field by name.
   */
  function findFormField($form, fieldName) {
    // Try exact match
    var $field = $form.find('[name="' + fieldName + '"]');
    if ($field.length) {
      return $field;
    }

    // Try with [0][value] suffix
    $field = $form.find('[name="' + fieldName + '[0][value]"]');
    if ($field.length) {
      return $field;
    }

    // Try with [target_id] suffix
    $field = $form.find('[name="' + fieldName + '[target_id]"]');
    if ($field.length) {
      return $field;
    }

    return $();
  }

  /**
   * Mark field as AI-generated.
   */
  function markAsAIGenerated($field, suggestion) {
    $field.addClass('crm-ai-generated crm-ai-field-highlighted');
    $field.attr('data-ai-generated', 'true');

    // Add confidence badge if available
    if (suggestion.confidence) {
      var confidencePercent = Math.round(suggestion.confidence * 100);
      var $badge = $(
        '<span class="ai-badge" title="AI Confidence">' + confidencePercent + '%</span>'
      );
      var $wrapper = $field.closest('.form-group') || $field.parent();
      $wrapper.append($badge);
    }

    // Add undo button
    addUndoButton($field);
  }

  /**
   * Add undo button for AI-generated field.
   */
  function addUndoButton($field) {
    var $wrapper = $field.closest('.form-group') || $field.parent();
    if ($wrapper.find('.ai-undo-btn').length > 0) {
      return; // Already has undo button
    }

    var $undoBtn = $(
      '<button type="button" class="ai-undo-btn" title="Remove AI suggestion">✕</button>'
    );
    $wrapper.append($undoBtn);

    $undoBtn.on('click', function (e) {
      e.preventDefault();
      $field.removeClass('crm-ai-generated crm-ai-field-highlighted');
      $field.removeAttr('data-ai-generated');
      $field.val('');
      $wrapper.find('.ai-badge').remove();
      $undoBtn.remove();
    });
  }

  /**
   * Show success message.
   */
  function showSuccessMessage(message) {
    showMessage(message, 'status');
    setTimeout(function () {
      $('[role="status"]').fadeOut(function () {
        $(this).remove();
      });
    }, 5000);
  }

  /**
   * Show error message.
   */
  function showErrorMessage(message) {
    showMessage(message, 'error');
    setTimeout(function () {
      $('[role="alert"]').fadeOut(function () {
        $(this).remove();
      });
    }, 7000);
  }

  /**
   * Show message.
   */
  function showMessage(message, type) {
    var $messages = $('#messages');
    if (!$messages.length) {
      $messages = $('<div id="messages"></div>').prependTo('main');
    }

    var classes = type === 'status' ? 'messages messages--status' : 'messages messages--error';
    var role = type === 'status' ? 'status' : 'alert';
    var $message = $(
      '<div class="' + classes + '" role="' + role + '"><div class="crm-ai-message">' + message + '</div></div>'
    );
    $messages.append($message);
  }
})(Drupal, jQuery);
