/**
 * @file
 * Filter builder interactive UI for advanced CRM filtering.
 *
 * Handles dynamic condition management, API interactions, and user interface.
 */

(function (Drupal) {
  'use strict';

  /**
   * Filter Builder object.
   */
  Drupal.crmAdvancedFilters = Drupal.crmAdvancedFilters || {};

  const FilterBuilder = {
    /**
     * Configuration.
     */
    config: {
      entityType: 'contact',
      conditionCount: 1,
      logic: 'AND',
      apiUrl: '/api/crm/filters',
    },

    /**
     * Initialize filter builder.
     */
    init: function (entityType) {
      this.config.entityType = entityType || 'contact';
      this.attachEventHandlers();
      this.loadAvailableFilters();
    },

    /**
     * Attach event handlers.
     */
    attachEventHandlers: function () {
      const self = this;

      // Logic selector buttons.
      document.querySelectorAll('.logic-btn').forEach((btn) => {
        btn.addEventListener('click', function () {
          self.setLogic(this.dataset.logic);
        });
      });

      // Add condition button.
      document.getElementById('add-condition')?.addEventListener('click', function () {
        self.addCondition();
      });

      // Apply filter button.
      document.getElementById('apply-filter')?.addEventListener('click', function () {
        self.applyFilter();
      });

      // Clear filter button.
      document.getElementById('clear-filter')?.addEventListener('click', function () {
        self.clearFilter();
      });

      // Save filter button.
      document.getElementById('save-filter')?.addEventListener('click', function () {
        self.showSaveModal();
      });

      // Save filter form submission.
      const saveForm = document.getElementById('save-filter-form');
      if (saveForm) {
        saveForm.addEventListener('submit', function (e) {
          e.preventDefault();
          self.submitSaveFilter();
        });
      }

      // Modal close buttons.
      document.querySelectorAll('.close').forEach((btn) => {
        btn.addEventListener('click', function () {
          this.closest('.modal').style.display = 'none';
        });
      });

      // Modal cancel buttons.
      document.querySelectorAll('.modal button.cancel').forEach((btn) => {
        btn.addEventListener('click', function () {
          this.closest('.modal').style.display = 'none';
        });
      });

      // Suggestion items click.
      document.querySelectorAll('.suggestion-item').forEach((item) => {
        item.addEventListener('click', function () {
          self.addConditionWithField(this.dataset.field);
        });
      });

      // Trending filters click.
      document.querySelectorAll('.trending-filters a').forEach((link) => {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          self.loadTrendingFilter(this.dataset.filterId);
        });
      });
    },

    /**
     * Load available filters for the entity type.
     */
    loadAvailableFilters: function () {
      const self = this;
      const url = `${this.config.apiUrl}/available/${this.config.entityType}`;

      fetch(url)
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          if (data.success) {
            self.availableFilters = data.data.available_filters;
            self.renderFirstCondition();
          }
        })
        .catch((error) => {
          console.error('Error loading filters:', error);
        });
    },

    /**
     * Get operators for a field.
     */
    getOperatorsForField: function (field) {
      const fieldDefs = {
        'text': ['contains', 'equals', 'starts', 'ends', 'not_contains'],
        'email': ['equals', 'contains', 'ends'],
        'phone': ['contains', 'equals'],
        'number': ['equals', 'greater_than', 'less_than', 'between'],
        'date': ['equals', 'greater_than', 'less_than', 'between'],
        'select': ['equals', 'in', 'not_in'],
        'url': ['contains', 'equals'],
      };

      const fieldType = this.getFieldType(field);
      return fieldDefs[fieldType] || ['equals', 'contains'];
    },

    /**
     * Get field type.
     */
    getFieldType: function (field) {
      if (!this.availableFilters) return 'text';

      const fieldDef = this.availableFilters.find((f) => f.key === field);
      return fieldDef ? fieldDef.type : 'text';
    },

    /**
     * Render the first condition.
     */
    renderFirstCondition: function () {
      const conditionsList = document.getElementById('conditions-list');
      if (conditionsList && conditionsList.children.length === 0) {
        this.addCondition();
      }
    },

    /**
     * Add a new condition group.
     */
    addCondition: function (field = null) {
      const conditionsList = document.getElementById('conditions-list');
      if (!conditionsList) return;

      const index = conditionsList.children.length;
      const condition = this.createConditionElement(index, field);

      conditionsList.appendChild(condition);

      // Add remove button functionality.
      const removeBtn = condition.querySelector('.remove-condition');
      if (removeBtn) {
        removeBtn.addEventListener('click', () => {
          condition.remove();
        });
      }

      // Add field change listener for operator updates.
      const fieldSelect = condition.querySelector('.condition-field');
      if (fieldSelect) {
        fieldSelect.addEventListener('change', (e) => {
          this.updateOperators(index, e.target.value);
        });
      }
    },

    /**
     * Add condition with specific field.
     */
    addConditionWithField: function (field) {
      this.addCondition(field);
    },

    /**
     * Create a condition element.
     */
    createConditionElement: function (index, field = null) {
      const div = document.createElement('div');
      div.className = 'condition-group';

      // Build field options.
      let fieldOptions = '<option value="">- Select field -</option>';
      if (this.availableFilters) {
        this.availableFilters.forEach((f) => {
          const selected = field === f.key ? 'selected' : '';
          fieldOptions += `<option value="${f.key}" ${selected}>${f.label}</option>`;
        });
      }

      // Build operator options (will be updated on field change).
      let operatorOptions = '<option value="">- Select operator -</option>';
      if (field) {
        const operators = this.getOperatorsForField(field);
        operators.forEach((op) => {
          operatorOptions += `<option value="${op}">${this.formatOperatorLabel(op)}</option>`;
        });
      }

      div.innerHTML = `
        <div class="condition-row">
          <select class="condition-field" data-index="${index}">
            ${fieldOptions}
          </select>
          
          <select class="condition-operator" data-index="${index}">
            ${operatorOptions}
          </select>
          
          <input type="text" class="condition-value" data-index="${index}" 
                 placeholder="Enter value...">
          
          <button type="button" class="btn btn-icon remove-condition" title="Remove condition">
            ✕
          </button>
        </div>
      `;

      return div;
    },

    /**
     * Update operators for a field.
     */
    updateOperators: function (index, field) {
      const operators = this.getOperatorsForField(field);
      const operatorSelect = document.querySelector(
        `.condition-operator[data-index="${index}"]`
      );

      if (!operatorSelect) return;

      operatorSelect.innerHTML = '<option value="">- Select operator -</option>';
      operators.forEach((op) => {
        const option = document.createElement('option');
        option.value = op;
        option.textContent = this.formatOperatorLabel(op);
        operatorSelect.appendChild(option);
      });
    },

    /**
     * Format operator label.
     */
    formatOperatorLabel: function (op) {
      const labels = {
        'equals': 'Equals',
        'not_equals': 'Does not equal',
        'contains': 'Contains',
        'not_contains': 'Does not contain',
        'starts': 'Starts with',
        'ends': 'Ends with',
        'greater_than': 'Greater than',
        'less_than': 'Less than',
        'greater_equal': 'At least',
        'less_equal': 'At most',
        'between': 'Between',
        'in': 'Is one of',
        'not_in': 'Is not one of',
        'is_empty': 'Is empty',
        'is_not_empty': 'Is not empty',
      };

      return labels[op] || op;
    },

    /**
     * Set filter logic.
     */
    setLogic: function (logic) {
      this.config.logic = logic;

      // Update button states.
      document.querySelectorAll('.logic-btn').forEach((btn) => {
        btn.classList.remove('active');
        if (btn.dataset.logic === logic) {
          btn.classList.add('active');
        }
      });
    },

    /**
     * Build filter definition from form.
     */
    buildFilterDefinition: function () {
      const conditions = [];

      document.querySelectorAll('.condition-row').forEach((row) => {
        const field = row.querySelector('.condition-field').value;
        const operator = row.querySelector('.condition-operator').value;
        const value = row.querySelector('.condition-value').value;

        if (field && operator) {
          conditions.push({
            field: field,
            operator: operator,
            value: value || null,
          });
        }
      });

      return {
        logic: this.config.logic,
        conditions: conditions,
      };
    },

    /**
     * Apply the filter.
     */
    applyFilter: function () {
      const filterDefinition = this.buildFilterDefinition();

      if (filterDefinition.conditions.length === 0) {
        alert('Please add at least one condition.');
        return;
      }

      const url = `${this.config.apiUrl}/query`;

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken(),
        },
        body: JSON.stringify({
          entity_type: this.config.entityType,
          filter_definition: filterDefinition,
          limit: 50,
          offset: 0,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            this.displayResults(data.data);
          } else {
            alert('Error executing filter: ' + (data.error || 'Unknown error'));
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          alert('Error applying filter');
        });
    },

    /**
     * Display results.
     */
    displayResults: function (data) {
      // Navigate to results page or display in modal.
      console.log('Results:', data);
      // Implementation depends on UI design.
    },

    /**
     * Clear filter.
     */
    clearFilter: function () {
      const conditionsList = document.getElementById('conditions-list');
      if (conditionsList) {
        conditionsList.innerHTML = '';
      }
      this.config.conditionCount = 0;
      this.loadAvailableFilters();
    },

    /**
     * Show save filter modal.
     */
    showSaveModal: function () {
      const modal = document.getElementById('modal-save-filter');
      if (modal) {
        modal.style.display = 'block';
      }
    },

    /**
     * Submit save filter.
     */
    submitSaveFilter: function () {
      const name = document.getElementById('filter-name').value;
      const description = document.getElementById('filter-description').value;
      const isPublic = document.getElementById('filter-public').checked;

      if (!name) {
        alert('Please enter a filter name.');
        return;
      }

      const filterDefinition = this.buildFilterDefinition();

      const url = `${this.config.apiUrl}/save`;

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.getCsrfToken(),
        },
        body: JSON.stringify({
          name: name,
          description: description,
          entity_type: this.config.entityType,
          filter_definition: filterDefinition,
          is_public: isPublic,
        }),
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert('Filter saved successfully!');
            document.getElementById('modal-save-filter').style.display = 'none';
            // Reset form.
            document.getElementById('save-filter-form').reset();
          } else {
            alert('Error saving filter: ' + (data.error || 'Unknown error'));
          }
        })
        .catch((error) => {
          console.error('Error:', error);
          alert('Error saving filter');
        });
    },

    /**
     * Load trending filter.
     */
    loadTrendingFilter: function (filterId) {
      // Load filter definition and populate form.
      console.log('Loading trending filter:', filterId);
    },

    /**
     * Get CSRF token.
     */
    getCsrfToken: function () {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
  };

  /**
   * Drupal behavior.
   */
  Drupal.behaviors.crmAdvancedFilters = {
    attach: function (context) {
      const wrapper = document.querySelector('.crm-filter-builder-wrapper', context);
      if (wrapper && !wrapper.dataset.processed) {
        const entityType = wrapper.dataset.entityType || 'contact';
        FilterBuilder.init(entityType);
        wrapper.dataset.processed = 'true';
      }
    },
  };
}(Drupal));
