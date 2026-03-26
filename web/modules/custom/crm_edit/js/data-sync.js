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
  "use strict";

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
      retryTimer: null,
      csrfToken: null,
      entityRevisions: {}, // Track current revision per entity
    },

    /**
     * Initialize real-time sync for an entity.
     */
    initEntity: function (entityType, entityId) {
      const key = entityType + ":" + entityId;

      // Load current entity data
      jQuery.ajax({
        url: "/api/v1/entity/" + entityType + "/" + entityId,
        type: "GET",
        dataType: "json",
        success: (data) => {
          this.state.entityRevisions[key] = {
            revisionId: data.revision_id,
            changed: data.changed,
            fields: data.fields,
          };
        },
        error: () => {
          console.warn("Could not load entity data for sync");
        },
      });
    },

    /**
     * Queue a field change for batch update.
     */
    queueChange: function (entityType, entityId, fieldName, newValue) {
      const key = entityType + ":" + entityId;
      
      // Get old value for rollback
      const selector = '[data-entity-id="' + entityId + '"][data-field-name="' + fieldName + '"]';
      const $field = jQuery(selector);
      let oldValue = "";
      if ($field.find(".field-display-value").length) {
        oldValue = $field.find(".field-display-value").text();
      } else if ($field.find("input, select, textarea").length) {
        oldValue = $field.find("input, select, textarea").val() || "";
      }
      const update = {
        entity_type: entityType,
        entity_id: entityId,
        field: fieldName,
        value: newValue,
        oldValue: oldValue,
        expected_revision_id: this.state.entityRevisions[key]
          ? this.state.entityRevisions[key].revisionId
          : null,
        timestamp: Date.now(),
        retryCount: 0,
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
      const batch = this.state.pendingUpdates.splice(
        0,
        this.config.maxBatchSize,
      );

      // Get CSRF token
      let csrfToken = this.state.csrfToken;
      if (!csrfToken) {
        fetch("/session/token", { credentials: "same-origin" })
          .then((resp) => {
            if (!resp.ok) {
              throw new Error("Failed to fetch CSRF token");
            }
            return resp;
          })
          .then((resp) => resp.text())
          .then((token) => {
            this.state.csrfToken = token.trim();
            this.sendBatch(batch);
          })
          .catch(() => {
            this.showToast(
              "error",
              "Could not get security token. Retrying updates...",
              5000,
            );
            this.scheduleRetry(batch, "csrf");
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
        url: "/api/v1/batch-update",
        type: "POST",
        timeout: 15000,
        contentType: "application/json",
        dataType: "json",
        xhrFields: {
          withCredentials: true,
        },
        headers: {
          "X-CSRF-Token": this.state.csrfToken,
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
        const key = "node:" + result.entity_id;
        if (this.state.entityRevisions[key]) {
          this.state.entityRevisions[key].revisionId = result.revision_id;
        }
      });

      // Clear optimistic saving indicators for acknowledged fields.
      batch.forEach((item) => {
        const selector =
          '[data-entity-id="' +
          item.entity_id +
          '"][data-field-name="' +
          item.field +
          '"]';
        const $cell = jQuery(selector);
        $cell.removeClass("is-saving has-conflict").addClass("is-synced");
        setTimeout(() => {
          $cell.removeClass("is-synced");
        }, 900);
      });

      // Show success toast
      if (response.updated > 0) {
        this.showToast(
          "success",
          response.updated + " field(s) updated successfully",
          3000,
        );
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
        if (error.error.includes("Conflict")) {
          // Conflict: Someone else edited this field
          this.handleConflict(error, originalBatch);
        } else {
          console.error("Update error:", error);
          this.showToast("error", "Error updating field: " + error.error, 5000);
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
        url: "/api/v1/entity/node/" + entityId,
        type: "GET",
        dataType: "json",
        success: (data) => {
          // Update local revision
          const key = "node:" + entityId;
          this.state.entityRevisions[key] = {
            revisionId: data.revision_id,
            changed: data.changed,
            fields: data.fields,
          };

          // Show conflict warning
          this.showToast(
            "warning",
            "Field was edited by another user. Values have been rolled back.",
            7000,
          );

          // Rollback to fresh data from server
          if (data.fields && data.fields[fieldName] !== undefined) {
             this.rollbackField(entityId, fieldName, data.fields[fieldName]);
          } else {
             const updateItem = originalBatch.find((u) => u.entity_id === entityId && u.field === fieldName);
             if (updateItem) {
               this.rollbackField(entityId, fieldName, updateItem.oldValue);
             } else {
               this.highlightField(entityId, fieldName);
             }
          }
        },
      });
    },

    /**
     * Handle batch error (network, server).
     */
    handleBatchError: function (xhr, batch) {
      const isRetryable =
        xhr.status === 0 || xhr.status === 429 || xhr.status >= 500;

      if (isRetryable) {
        this.showToast("error", "Network/server issue. Retrying...", 5000);
        this.scheduleRetry(batch, "network");
        return;
      }

      this.showToast(
        "error",
        "Update failed: " + (xhr.statusText || "Unknown error"),
        5000,
      );

      batch.forEach((item) => {
        const selector =
          '[data-entity-id="' +
          item.entity_id +
          '"][data-field-name="' +
          item.field +
          '"]';
        jQuery(selector).removeClass("is-saving").addClass("has-conflict");
        setTimeout(() => {
          jQuery(selector).removeClass("has-conflict");
        }, 1800);
      });
    },

    /**
     * Re-queue a failed batch and retry with backoff.
     */
    scheduleRetry: function (batch, reason) {
      const retryableItems = [];

      batch.forEach((item) => {
        const nextRetryCount = (item.retryCount || 0) + 1;
        if (nextRetryCount <= this.config.maxRetries) {
          retryableItems.push({
            ...item,
            retryCount: nextRetryCount,
          });
          return;
        }

        const selector =
          '[data-entity-id="' +
          item.entity_id +
          '"][data-field-name="' +
          item.field +
          '"]';
        jQuery(selector).removeClass("is-saving").addClass("has-conflict");
        setTimeout(() => {
          jQuery(selector).removeClass("has-conflict");
        }, 1800);
      });

      if (retryableItems.length === 0) {
        this.showToast(
          "error",
          "Some changes could not be synced and have been reverted.",
          6000,
        );
        // Rollback un-retryable items
        batch.forEach((item) => {
          this.rollbackField(item.entity_id, item.field, item.oldValue);
        });
        return;
      }

      this.state.pendingUpdates.unshift(...retryableItems);

      if (this.state.retryTimer) {
        return;
      }

      const maxAttempt = Math.max(
        ...retryableItems.map((u) => u.retryCount || 1),
      );
      const delayIndex = Math.min(
        maxAttempt - 1,
        this.config.retryDelays.length - 1,
      );
      const delay = this.config.retryDelays[delayIndex] || 1000;

      this.state.retryTimer = setTimeout(() => {
        this.state.retryTimer = null;
        this.flushBatch();
      }, delay);
    },

    /**
     * Show optimistic UI update immediately.
     */
    showOptimisticUpdate: function (entityType, entityId, fieldName, newValue) {
      const selector =
        '[data-entity-id="' +
        entityId +
        '"][data-field-name="' +
        fieldName +
        '"]';
      const $field = jQuery(selector);

      if ($field.length) {
        // Show visual feedback
        $field.removeClass("is-synced has-conflict").addClass("is-saving");
        $field.find(".field-value").fadeOut(100).fadeIn(100);

        // Change the displayed value
        if ($field.find(".field-display-value").length) {
          $field.find(".field-display-value").text(newValue);
        }
      }
    },

    /**
     * Highlight a field (for conflicts).
     */
    highlightField: function (entityId, fieldName) {
      const selector =
        '[data-entity-id="' +
        entityId +
        '"][data-field-name="' +
        fieldName +
        '"]';
      const $field = jQuery(selector);

      if ($field.length) {
        $field.addClass("has-conflict");
        setTimeout(() => {
          $field.removeClass("has-conflict");
        }, 3000);
      }
    },

    /**
     * Rollback a field to its previous value.
     */
    rollbackField: function (entityId, fieldName, oldValue) {
      const selector =
        '[data-entity-id="' +
        entityId +
        '"][data-field-name="' +
        fieldName +
        '"]';
      const $field = jQuery(selector);

      if ($field.length && oldValue !== undefined) {
        $field.removeClass("is-saving").addClass("has-conflict");
        
        // Restore display value
        const $display = $field.find(".field-display-value");
        if ($display.length) {
          $display.text(oldValue);
        }
        
        // Restore input value if exists
        const $input = $field.find("input, select, textarea");
        if ($input.length) {
          $input.val(oldValue);
        }

        setTimeout(() => {
          $field.removeClass("has-conflict");
        }, 3000);
      }
    },

    /**
     * Show toast notification.
     */
    showToast: function (type, message, duration) {
      let $container = jQuery("#crm-toast-container");
      if ($container.length === 0) {
        $container = jQuery('<div id="crm-toast-container"></div>').appendTo(
          "body",
        );
      }

      const $toast = jQuery(
        '<div class="crm-toast crm-toast-' + type + '"></div>',
      )
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
        return fetch("/session/token", { credentials: "same-origin" })
          .then((resp) => resp.text())
          .then((token) => {
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
        .once("crm-data-sync")
        .each(function () {
          const $field = jQuery(this);
          const entityId = $field.data("entity-id");
          const fieldName = $field.data("field-name");

          // Initialize entity
          CRMDataSync.initEntity("node", entityId);

          // On change, queue update
          $field.on("change", "input, textarea, select", function () {
            const newValue = jQuery(this).val();
            CRMDataSync.queueChange("node", entityId, fieldName, newValue);
          });
        });
    },
  };
})(Drupal, jQuery);
