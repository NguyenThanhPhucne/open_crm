/**
 * Javascript for CRM Announcements feed
 */
(function ($, Drupal, once) {
  Drupal.behaviors.crmAnnouncements = {
    attach: function (context, settings) {
      // Find our custom close button in the header using the modern once library
      const elements = once('crm-announcements-close', '.crm-form-header-close', context);
      
      $(elements).on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Try multiple ways to find the parent dialog to be safe
        var $dialogContent = $(this).closest('.ui-dialog-content');
        if ($dialogContent.length && $dialogContent.data('ui-dialog')) {
          $dialogContent.dialog('close');
        } else {
          // Fallback if structure is unexpected
          $('.crm-announcement-modal .ui-dialog-content').dialog('close');
        }
      });
    }
  };
})(jQuery, Drupal, once);
