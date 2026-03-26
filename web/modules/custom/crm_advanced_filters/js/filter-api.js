/**
 * @file
 * API helper for advanced filter operations.
 */

(function (Drupal) {
  'use strict';

  /**
   * Filter API helper.
   */
  Drupal.crmAdvancedFilters.FilterAPI = {
    /**
     * Execute a filter query.
     *
     * @param {string} entityType - The entity type (contact, deal, etc.)
     * @param {object} filterDefinition - The filter definition.
     * @param {object} options - Additional options (sort, limit, offset).
     * @param {function} callback - Callback function.
     */
    executeQuery: function (entityType, filterDefinition, options, callback) {
      const url = '/api/crm/filters/query';
      const data = {
        entity_type: entityType,
        filter_definition: filterDefinition,
        sort: options.sort || {},
        limit: options.limit || 50,
        offset: options.offset || 0,
      };

      this._post(url, data, callback);
    },

    /**
     * Get available filters for entity type.
     *
     * @param {string} entityType - The entity type.
     * @param {function} callback - Callback function.
     */
    getAvailableFilters: function (entityType, callback) {
      const url = `/api/crm/filters/available/${entityType}`;
      this._get(url, callback);
    },

    /**
     * Get field options.
     *
     * @param {string} entityType - The entity type.
     * @param {string} field - The field name.
     * @param {function} callback - Callback function.
     */
    getFieldOptions: function (entityType, field, callback) {
      const url = `/api/crm/filters/options/${entityType}/${field}`;
      this._get(url, callback);
    },

    /**
     * Save a filter.
     *
     * @param {object} filterData - The filter data.
     * @param {function} callback - Callback function.
     */
    saveFilter: function (filterData, callback) {
      const url = '/api/crm/filters/save';
      this._post(url, filterData, callback);
    },

    /**
     * Get user's saved filters.
     *
     * @param {string} entityType - Optional: filter by entity type.
     * @param {function} callback - Callback function.
     */
    getSavedFilters: function (entityType, callback) {
      let url = '/api/crm/filters/saved';
      if (entityType) {
        url += `?entity_type=${entityType}`;
      }
      this._get(url, callback);
    },

    /**
     * Delete a saved filter.
     *
     * @param {number} filterId - The saved filter ID.
     * @param {function} callback - Callback function.
     */
    deleteFilter: function (filterId, callback) {
      const url = `/api/crm/filters/${filterId}`;
      this._delete(url, callback);
    },

    /**
     * POST request helper.
     *
     * @private
     */
    _post: function (url, data, callback) {
      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this._getCsrfToken(),
        },
        body: JSON.stringify(data),
      })
        .then((response) => response.json())
        .then((data) => {
          if (callback) callback(data);
        })
        .catch((error) => {
          console.error('API error:', error);
          if (callback) callback({ error: error.message });
        });
    },

    /**
     * GET request helper.
     *
     * @private
     */
    _get: function (url, callback) {
      fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (callback) callback(data);
        })
        .catch((error) => {
          console.error('API error:', error);
          if (callback) callback({ error: error.message });
        });
    },

    /**
     * DELETE request helper.
     *
     * @private
     */
    _delete: function (url, callback) {
      fetch(url, {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': this._getCsrfToken(),
        },
      })
        .then((response) => response.json())
        .then((data) => {
          if (callback) callback(data);
        })
        .catch((error) => {
          console.error('API error:', error);
          if (callback) callback({ error: error.message });
        });
    },

    /**
     * Get CSRF token.
     *
     * @private
     */
    _getCsrfToken: function () {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
  };
}(Drupal));
