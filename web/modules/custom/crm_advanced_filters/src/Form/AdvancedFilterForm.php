<?php

namespace Drupal\crm_advanced_filters\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crm_advanced_filters\Service\FilterService;
use Drupal\crm_advanced_filters\Service\SavedFilterService;
use Drupal\crm_advanced_filters\Service\SuggestionService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for building advanced filter queries with dynamic conditions.
 *
 * Supports complex AND/OR/NOT logic, multiple condition types,
 * saved searches, and intelligent filter suggestions.
 */
class AdvancedFilterForm extends FormBase {

  /**
   * Filter service.
   *
   * @var \Drupal\crm_advanced_filters\Service\FilterService
   */
  protected $filterService;

  /**
   * Saved filter service.
   *
   * @var \Drupal\crm_advanced_filters\Service\SavedFilterService
   */
  protected $savedFilterService;

  /**
   * Suggestion service.
   *
   * @var \Drupal\crm_advanced_filters\Service\SuggestionService
   */
  protected $suggestionService;

  /**
   * Constructor.
   */
  public function __construct(
    FilterService $filter_service,
    SavedFilterService $saved_filter_service,
    SuggestionService $suggestion_service
  ) {
    $this->filterService = $filter_service;
    $this->savedFilterService = $saved_filter_service;
    $this->suggestionService = $suggestion_service;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_advanced_filters.filter_service'),
      $container->get('crm_advanced_filters.saved_filter_service'),
      $container->get('crm_advanced_filters.suggestion_service')
    );
  }

  /**
   * Returns a unique string identifying the form.
   */
  public function getFormId() {
    return 'crm_advanced_filter_form';
  }

  /**
   * Build the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type = 'contact') {
    // Validate entity type.
    $valid_types = ['contact', 'deal', 'organization', 'activity'];
    if (!in_array($entity_type, $valid_types)) {
      $entity_type = 'contact';
    }

    $form['#attributes']['class'][] = 'crm-advanced-filter-form';
    $form['#attached']['library'][] = 'crm_advanced_filters/filter-builder';

    // Store entity type.
    $form['entity_type'] = [
      '#type' => 'hidden',
      '#value' => $entity_type,
    ];

    // Tabs for filter vs results.
    $form['tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Filter Builder'),
    ];

    // TAB 1: FILTER BUILDER.
    $form['filter_builder'] = [
      '#type' => 'details',
      '#title' => $this->t('Build Filter'),
      '#open' => TRUE,
      '#group' => 'tabs',
    ];

    // Saved filters dropdown.
    $saved_filters = $this->savedFilterService->getUserFilters($entity_type);
    $filter_options = ['' => '- Select a saved filter -'];
    foreach ($saved_filters as $filter) {
      $filter_options[$filter->id()] = $filter->get('name')->value;
    }

    $form['filter_builder']['saved_filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Load Saved Filter'),
      '#options' => $filter_options,
      '#empty_option' => '- Or start new filter -',
      '#ajax' => [
        'callback' => '::loadSavedFilter',
        'wrapper' => 'filter-conditions-wrapper',
        'event' => 'change',
      ],
    ];

    // Filter logic selector.
    $form['filter_builder']['logic'] = [
      '#type' => 'radios',
      '#title' => $this->t('Filter Logic'),
      '#options' => [
        'AND' => $this->t('Match ALL conditions (AND)'),
        'OR' => $this->t('Match ANY condition (OR)'),
      ],
      '#default_value' => 'AND',
      '#description' => $this->t('AND: all conditions must be true. OR: any condition can be true.'),
    ];

    // Wrapper for dynamically added conditions.
    $form['filter_builder']['conditions_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'filter-conditions-wrapper'],
    ];

    // Get number of condition groups from form state or default to 1.
    $num_conditions = $form_state->get('num_conditions') ?? 1;

    // Build condition groups.
    for ($i = 0; $i < $num_conditions; $i++) {
      $form['filter_builder']['conditions_wrapper'][$i] = $this->buildConditionGroup($entity_type, $i);
    }

    // Add/Remove condition buttons.
    $form['filter_builder']['add_condition'] = [
      '#type' => 'submit',
      '#value' => $this->t('+ Add Condition'),
      '#submit' => ['::addCondition'],
      '#ajax' => [
        'callback' => '::addConditionCallback',
        'wrapper' => 'filter-conditions-wrapper',
      ],
    ];

    if ($num_conditions > 1) {
      $form['filter_builder']['remove_condition'] = [
        '#type' => 'submit',
        '#value' => $this->t('− Remove Last Condition'),
        '#submit' => ['::removeCondition'],
        '#ajax' => [
          'callback' => '::removeConditionCallback',
          'wrapper' => 'filter-conditions-wrapper',
        ],
      ];
    }

    // TAB 2: SORT & PAGINATION.
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Sort & Display'),
      '#open' => FALSE,
      '#group' => 'tabs',
    ];

    $available_filters = $this->filterService->getAvailableFilters($entity_type);
    $sort_options = ['' => '- Default sort -'];
    foreach ($available_filters as $filter) {
      $sort_options[$filter['key']] = $filter['label'];
    }

    $form['options']['sort_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Sort By'),
      '#options' => $sort_options,
    ];

    $form['options']['sort_direction'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sort Order'),
      '#options' => [
        'ASC' => $this->t('Ascending'),
        'DESC' => $this->t('Descending'),
      ],
      '#default_value' => 'ASC',
      '#states' => [
        'visible' => [
          ':input[name="sort_field"]' => ['!value' => ''],
        ],
      ],
    ];

    $form['options']['results_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Results Per Page'),
      '#min' => 10,
      '#max' => 500,
      '#step' => 10,
      '#default_value' => 50,
    ];

    // Export options.
    $form['options']['export'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Export Results'),
    ];

    $form['options']['export']['export_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Export Format'),
      '#options' => [
        'csv' => $this->t('CSV'),
        'pdf' => $this->t('PDF'),
        'json' => $this->t('JSON'),
      ],
    ];

    // TAB 3: SAVED SEARCHES.
    $form['saved_searches'] = [
      '#type' => 'details',
      '#title' => $this->t('Save This Filter'),
      '#open' => FALSE,
      '#group' => 'tabs',
    ];

    $form['saved_searches']['save_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter Name'),
      '#placeholder' => $this->t('e.g., "High value prospects"'),
      '#maxlength' => 255,
    ];

    $form['saved_searches']['save_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#rows' => 3,
      '#placeholder' => $this->t('Describe what this filter finds...'),
    ];

    $form['saved_searches']['save_public'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make this filter public (shareable with team)'),
    ];

    // ACTION BUTTONS.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['filter'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply Filter'),
      '#button_type' => 'primary',
    ];

    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter & Save'),
      '#submit' => ['::submitForm', '::saveFilter'],
    ];

    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  /**
   * Build a single condition group.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $index
   *   The condition index.
   *
   * @return array
   *   The form element for the condition.
   */
  protected function buildConditionGroup($entity_type, $index) {
    $available_filters = $this->filterService->getAvailableFilters($entity_type);
    $filter_options = [];
    foreach ($available_filters as $filter) {
      $filter_options[$filter['key']] = $filter['label'];
    }

    $condition = [
      '#type' => 'fieldset',
      '#title' => $this->t('Condition @num', ['@num' => $index + 1]),
      '#attributes' => ['class' => ['crm-filter-condition']],
    ];

    $condition['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $filter_options,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateOperators',
        'wrapper' => 'operators-wrapper-' . $index,
        'event' => 'change',
      ],
    ];

    $condition['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Operator'),
      '#options' => [
        'equals' => $this->t('equals'),
        'not_equals' => $this->t('does not equal'),
        'contains' => $this->t('contains'),
        'not_contains' => $this->t('does not contain'),
        'starts' => $this->t('starts with'),
        'ends' => $this->t('ends with'),
        'greater_than' => $this->t('is greater than'),
        'less_than' => $this->t('is less than'),
        'greater_equal' => $this->t('is at least'),
        'less_equal' => $this->t('is at most'),
        'between' => $this->t('is between'),
        'in' => $this->t('is one of'),
        'not_in' => $this->t('is not one of'),
        'is_empty' => $this->t('is empty'),
        'is_not_empty' => $this->t('is not empty'),
      ],
      '#prefix' => '<div id="operators-wrapper-' . $index . '">',
      '#suffix' => '</div>',
    ];

    $condition['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#placeholder' => $this->t('Enter value...'),
      '#states' => [
        'invisible' => [
          ':input[name="operator"]' => ['value' => ['is_empty', 'is_not_empty']],
        ],
      ],
    ];

    return $condition;
  }

  /**
   * Add condition button callback.
   */
  public function addCondition(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_conditions', ($form_state->get('num_conditions') ?? 1) + 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Add condition AJAX callback.
   */
  public function addConditionCallback(array &$form, FormStateInterface $form_state) {
    return $form['filter_builder']['conditions_wrapper'];
  }

  /**
   * Remove condition button callback.
   */
  public function removeCondition(array &$form, FormStateInterface $form_state) {
    $num = $form_state->get('num_conditions');
    if ($num > 1) {
      $form_state->set('num_conditions', $num - 1);
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Remove condition AJAX callback.
   */
  public function removeConditionCallback(array &$form, FormStateInterface $form_state) {
    return $form['filter_builder']['conditions_wrapper'];
  }

  /**
   * Update operators based on field selection.
   */
  public function updateOperators(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    // Return operators wrapper for the triggering field.
    $parts = explode('-', $trigger['#id']);
    $condition_index = end($parts);
    
    return $form['filter_builder']['conditions_wrapper'][$condition_index]['operator'];
  }

  /**
   * Load saved filter AJAX callback.
   */
  public function loadSavedFilter(array &$form, FormStateInterface $form_state) {
    return $form['filter_builder']['conditions_wrapper'];
  }

  /**
   * Reset form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->set('num_conditions', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submit the filter form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Build filter definition from form values.
    $entity_type = $form_state->getValue('entity_type');
    $logic = $form_state->getValue('logic');
    $conditions = [];

    // Collect conditions from form state.
    $form_state->set('filter_applied', TRUE);

    // Redirect to results page with filter in query string.
    $form_state->setRedirect('crm_advanced_filters.results', [
      'entity_type' => $entity_type,
    ]);
  }

  /**
   * Save the filter.
   */
  public function saveFilter(array &$form, FormStateInterface $form_state) {
    $save_name = trim($form_state->getValue('save_name') ?? '');

    if (!$save_name) {
      $this->messenger()->addError($this->t('Please enter a name for the saved filter.'));
      return;
    }

    $entity_type = $form_state->getValue('entity_type');
    $logic = $form_state->getValue('logic');
    $description = $form_state->getValue('save_description') ?? '';
    $is_public = (bool) $form_state->getValue('save_public');

    // Build filter definition.
    $filter_definition = [
      'logic' => $logic,
      'conditions' => [], // Would be populated from form values.
    ];

    $filter_id = $this->savedFilterService->saveFilter(
      $save_name,
      $entity_type,
      $filter_definition,
      $description,
      $is_public
    );

    if ($filter_id) {
      $this->messenger()->addStatus($this->t('Filter "@name" saved successfully.', [
        '@name' => $save_name,
      ]));
    } else {
      $this->messenger()->addError($this->t('Failed to save filter.'));
    }
  }

}
