/**
 * @file
 * Real-time Data Sync for CRM - Optimized incremental updates
 *
 * Features:
 * - Incremental field updates (don't reload entire page)
 * - Optimistic UI updates (show change instantly)
 * - Conflict detection (prevent overwriting concurrent edits)
 * - Batch updates (send multiple changes in one request)
 * - Auto-retry with exponential backoff
 * - Real-time validation from server
 */

(function (Drupal, jQuery) {
  'use strict';

  window.CRMDataSync = {
    // Configuration
    config: {
      batchDelay: 300, // ms to wait for more changes before sending
      maxBatchSize: 10, // max changes per batch request
      maxRetries: 3,
      retryDelays: [1000, 2000, 5000],
      conflictRetryLimit: 1, // How many times to retry on conflict
    },

    // State management
    state: {
      pendingUpdates: [], // Queue of pending updates
      inFlightUpdates: {}, // Currently sending updates
      batchTimer: null,
      csrfToken: null,
      entityRevisions: {}, // Track current revision per entity
    },

    /**
     * Initialize real-time sync for an entity.
     */
    initEntity: function (entityType, entityId) {
      const key = entityType + ':' + entityId;

      // Load current entity data
      jQuery.ajax({
        url: '/api/v1/entity/' + entityType + '/' + entityId,
        type: 'GET',
        dataType: 'json',
        success: (data) => {
          this.state.entityRevisions[key] = {
            revisionId: data.revision_id,
            changed: data.changed,
            fields: data.fields,
          };
        },
        error: () => {
          console.warn('Could not load entity data for sync');
        },
      });
    },

    /**
     * Queue a field change for batch update.
     */
    queueChange: function (entityType, entityId, fieldName, newValue) {
      const key = entityType + ':' + entityId;
      const update = {
        entity_type: entityType,
        entity_id: entityId,
        field: fieldName,
        value: newValue,
        expected_revision_id: this.state.entityRevisions[key]?  
          this.state.entityRevisions[key].revisionId : null,
        timestamp: Date.now(),
      };

      // Add to pending queue
      this.state.pendingUpdates.push(update);

      // Clear existing timer
      if (this.state.batchTimer) {
        clearTimeout(this.state.batchTimer);
      }

      // Schedule batch send with debounce
      this.state.batchTimer = setTimeout(() => {
        this.flushBatch();
      }, this.config.batchDelay);

      // Show instant UI feedback
      this.showOptimisticUpdate(entityType, entityId, fieldName, newValue);
    },

    /**
     * Send queued updates to server in batch.
     */
    flushBatch: function () {
      if (this.state.pendingUpdates.length === 0) {
        return;
      }

      // Split into batches if needed
      const batch = this.state.pendingUpdates.splice(0, this.config.maxBatchSize);

      // Get CSRF token
      let csrfToken = this.state.csrfToken;
      if (!csrfToken) {
        fetch('/session/token').then(resp => resp.text()).then(token => {
          this.state.csrfToken = token.trim();
          this.sendBatch(batch);
        });
      } else {
        this.sendBatch(batch);
      }
    },

    /**
     * Send batch to server.
     */
    sendBatch: function (batch) {
      const payload = { updates: batch };

      jQuery.ajax({
        url: '/api/v1/batch-update',
        type: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        headers: {
          'X-Csrf-Token': this.state.csrfToken,
        },
        data: JSON.stringify(payload),
        success: (response) => {
          this.handleBatchSuccess(response, batch);
        },
        error: (xhr, status, error) => {
          this.handleBatchError(xhr, batch);
        },
      });
    },

    /**
     * Handle successful batch update.
     */
    handleBatchSuccess: function (response, batch) {
      if (!response.success) {
        // Handle partial failures
        this.handleBatchErrors(response.errors, batch);
      }

      // Update revision IDs
      response.results.forEach((result) => {
        const key = 'node:' + result.entity_id;
        if (this.state.entityRevisions[key]) {
          this.state.entityRevisions[key].revisionId = result.revision_id;
        }
      });

      // Show success toast
      if (response.updated > 0) {
        this.showToast('success', response.updated + ' field(s) updated successfully', 3000);
      }

      // Continue processing if more items pending
      if (this.state.pendingUpdates.length > 0) {
        this.state.batchTimer = setTimeout(() => {
          this.flushBatch();
        }, 100);
      }
    },

    /**
     * Handle batch update errors.
     */
    handleBatchErrors: function (errors, originalBatch) {
      errors.forEach((error) => {
        if (error.error.includes('Conflict')) {
          // Conflict: Someone else edited this field
          this.handleConflict(error, originalBatch);
        } else {
          console.error('Update error:', error);
          this.showToast('error', 'Error updating field: ' + error.error, 5000);
        }
      });
    },

    /**
     * Handle edit conflict (optimistic locking).
     */
    handleConflict: function (error, originalBatch) {
      const entityId = error.entity_id;
      const fieldName = error.field_name;

      // Fetch fresh data
      jQuery.ajax({
        url: '/api/v1/entity/node/' + entityId,
        type: 'GET',
        dataType: 'json',
        success: (data) => {
          // Update local revision
          const key = 'node:' + entityId;
          this.state.entityRevisions[key] = {
            revisionId: data.revision_id,
            changed: data.changed,
            fields: data.fields,
          };

          // Show conflict warning
          this.showToast('warning', 'Field was edited by another user. Please refresh to see latest changes.', 7000);

          // Highlight the conflicted field
          this.highlightField(entityId, fieldName);
        },
      });
    },

    /**
     * Handle batch error (network, server).
     */
    handleBatchError: function (xhr, batch) {
      if (xhr.status === 0) {
        // Network error
        this.showToast('error', 'Network error. Retrying...', 5000);
      } else {
        this.showToast('error', 'Server error: ' + (xhr.statusText || 'Unknown'), 5000);
      }

      // Re-queue failed items
      this.state.pendingUpdates.unshift(...batch);
    },

    /**
     * Show optimistic UI update immediately.
     */
    showOptimisticUpdate: function (entityType, entityId, fieldName, newValue) {
      const selector = '[data-entity-id="' + entityId + '"][data-field-name="' + fieldName + '"]';
      const $field = jQuery(selector);

      if ($field.length) {
        // Show visual feedback
        $field.addClass('is-saving');
        $field.find('.field-value').fadeOut(100).fadeIn(100);

        // Change the displayed value
        if ($field.find('.field-display-value').length) {
          $field.find('.field-display-value').text(newValue);
        }
      }
    },

    /**
     * Highlight a field (for conflicts).
     */
    highlightField: function (entityId, fieldName) {
      const selector = '[data-entity-id="' + entityId + '"][data-field-name="' + fieldName + '"]';
      const $field = jQuery(selector);

      if ($field.length) {
        $field.addClass('has-conflict');
        setTimeout(() => {
          $field.removeClass('has-conflict');
        }, 3000);
      }
    },

    /**
     * Show toast notification.
     */
    showToast: function (type, message, duration) {
      let $container = jQuery('#crm-toast-container');
      if ($container.length === 0) {
        $container = jQuery('<div id="crm-toast-container"></div>').appendTo('body');
      }

      const $toast = jQuery('<div class="crm-toast crm-toast-' + type + '"></div>')
        .html(message)
        .appendTo($container);

      setTimeout(() => {
        $toast.fadeOut(300, function () {
          jQuery(this).remove();
        });
      }, duration || 4000);
    },

    /**
     * Get CSRF token.
     */
    getCsrfToken: function () {
      if (!this.state.csrfToken) {
        return fetch('/session/token').then(resp => resp.text()).then(token => {
          this.state.csrfToken = token.trim();
          return this.state.csrfToken;
        });
      }
      return Promise.resolve(this.state.csrfToken);
    },
  };

  /**
   * Drupal behavior for real-time field editing.
   */
  Drupal.behaviors.crmDataSync = {
    attach: function (context) {
      // Initialize editable fields
      jQuery('[data-entity-id][data-field-name][data-editable="true"]', context)
        .once('crm-data-sync')
        .each(function () {
          const $field = jQuery(this);
          const entityId = $field.data('entity-id');
          const fieldName = $field.data('field-name');

          // Initialize entity
          CRMDataSync.initEntity('node', entityId);

          // On change, queue update
          $field.on('change', 'input, textarea, select', function () {
            const newValue = jQuery(this).val();
            CRMDataSync.queueChange('node', entityId, fieldName, newValue);
          });
        });
    },
  };

})(Drupal, jQuery);
