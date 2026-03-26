<?php

namespace Drupal\crm_advanced_filters\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\crm_advanced_filters\Service\FilterService;
use Drupal\crm_advanced_filters\Service\SavedFilterService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for displaying filter results.
 */
class FilterResultsController extends ControllerBase {

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
   * Constructor.
   */
  public function __construct(
    FilterService $filter_service,
    SavedFilterService $saved_filter_service
  ) {
    $this->filterService = $filter_service;
    $this->savedFilterService = $saved_filter_service;
  }

  /**
   * Create function for dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_advanced_filters.filter_service'),
      $container->get('crm_advanced_filters.saved_filter_service')
    );
  }

  /**
   * Display filter results.
   *
   * @param string $entity_type
   *   The entity type being filtered.
   *
   * @return array
   *   Render array for filter results page.
   */
  public function results($entity_type) {
    // Validate entity type.
    if (!in_array($entity_type, ['contact', 'deal', 'organization', 'activity'])) {
      $this->messenger()->addError($this->t('Invalid entity type.'));
      return [];
    }

    $build = [];

    // Get page parameters.
    $page = \Drupal::request()->query->get('page', 0);
    $limit = \Drupal::request()->query->get('limit', 50);
    $offset = $page * $limit;

    // Get filter definition from query string or session.
    $filter_definition = \Drupal::request()->query->all();

    // Execute filter query if definition exists.
    $results = [];
    $filter_description = '';

    if (!empty($filter_definition)) {
      $result_data = $this->filterService->executeFilter(
        $entity_type,
        $filter_definition,
        [],
        $limit,
        $offset,
        $this->currentUser()
      );

      $results = $result_data['results'];
      $filter_description = $result_data['filter_description'];

      $build['filter_description'] = [
        '#type' => 'markup',
        '#markup' => '<div class="filter-description">' .
                     $this->t('Found @count results matching: @description', [
                       '@count' => $result_data['total_count'],
                       '@description' => $filter_description,
                     ]) .
                     '</div>',
      ];

      // Build results table.
      $headers = $this->getTableHeaders($entity_type);
      $rows = $this->buildTableRows($results, $entity_type);

      $build['results_table'] = [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $rows,
        '#empty' => $this->t('No results found.'),
        '#attributes' => ['class' => ['crm-filter-results-table']],
      ];

      // Add pagination.
      if ($result_data['total_count'] > $limit) {
        $build['pagination'] = [
          '#type' => 'pager',
        ];
      }

      // Add bulk actions.
      $build['bulk_actions'] = $this->buildBulkActions($entity_type, count($results));

      // Export options.
      $build['export'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Export Results'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      ];

      $build['export']['format'] = [
        '#type' => 'radios',
        '#title' => $this->t('Select format:'),
        '#options' => [
          'csv' => $this->t('CSV'),
          'pdf' => $this->t('PDF'),
          'json' => $this->t('JSON'),
        ],
        '#default_value' => 'csv',
      ];

      $build['export']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Export'),
      ];
    } else {
      $build['no_filter'] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('No filter applied. Use the filter builder to find records.') . '</p>',
      ];
    }

    // Add filter builder link.
    $build['back_to_filter'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Filter Builder'),
      '#url' => \Drupal\Core\Url::fromRoute('crm_advanced_filters.builder', [
        'entity_type' => $entity_type,
      ]),
      '#attributes' => ['class' => ['button', 'button--secondary']],
    ];

    return $build;
  }

  /**
   * Get table headers for entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Table header array.
   */
  protected function getTableHeaders($entity_type) {
    $headers = [
      'id' => $this->t('ID'),
      'title' => $this->t('Name/Title'),
    ];

    // Add type-specific columns.
    switch ($entity_type) {
      case 'contact':
        $headers += [
          'email' => $this->t('Email'),
          'phone' => $this->t('Phone'),
          'organization' => $this->t('Organization'),
          'owner' => $this->t('Owner'),
        ];
        break;

      case 'deal':
        $headers += [
          'amount' => $this->t('Amount'),
          'stage' => $this->t('Stage'),
          'probability' => $this->t('Probability'),
          'owner' => $this->t('Owner'),
        ];
        break;

      case 'organization':
        $headers += [
          'email' => $this->t('Email'),
          'phone' => $this->t('Phone'),
          'website' => $this->t('Website'),
          'industry' => $this->t('Industry'),
        ];
        break;

      case 'activity':
        $headers += [
          'type' => $this->t('Type'),
          'contact' => $this->t('Contact'),
          'datetime' => $this->t('Date/Time'),
        ];
        break;
    }

    $headers['actions'] = $this->t('Actions');

    return $headers;
  }

  /**
   * Build table rows from results.
   *
   * @param array $entities
   *   The entity results.
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Table rows array.
   */
  protected function buildTableRows(array $entities, $entity_type) {
    $rows = [];

    foreach ($entities as $entity) {
      $row = [
        'id' => $entity->id(),
        'title' => \Drupal::l($entity->getTitle(), $entity->toUrl()),
      ];

      // Add type-specific data.
      switch ($entity_type) {
        case 'contact':
          $row['email'] = $entity->get('field_email')->value ?? '-';
          $row['phone'] = $entity->get('field_phone_number')->value ?? '-';
          $org = $entity->get('field_organization')->entity;
          $row['organization'] = $org ? $org->getTitle() : '-';
          $owner = $entity->get('field_owner')->entity;
          $row['owner'] = $owner ? $owner->getAccountName() : '-';
          break;

        case 'deal':
          $row['amount'] = $entity->get('field_amount')->value ?? '-';
          $row['stage'] = $entity->get('field_stage')->value ?? '-';
          $row['probability'] = $entity->get('field_probability')->value ?? '-';
          $owner = $entity->get('field_owner')->entity;
          $row['owner'] = $owner ? $owner->getAccountName() : '-';
          break;

        case 'organization':
          $row['email'] = $entity->get('field_email')->value ?? '-';
          $row['phone'] = $entity->get('field_phone_number')->value ?? '-';
          $row['website'] = $entity->get('field_website')->uri ?? '-';
          $row['industry'] = $entity->get('field_industry')->value ?? '-';
          break;

        case 'activity':
          $row['type'] = $entity->get('field_type')->value ?? '-';
          $contact = $entity->get('field_contact')->entity;
          $row['contact'] = $contact ? $contact->getTitle() : '-';
          $row['datetime'] = $entity->get('field_datetime')->value ?? '-';
          break;
      }

      // Actions column.
      $row['actions'] = [
        'data' => [
          '#type' => 'dropbutton',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => $entity->toUrl('edit-form'),
            ],
            'view' => [
              'title' => $this->t('View'),
              'url' => $entity->toUrl('canonical'),
            ],
          ],
        ],
      ];

      $rows[] = $row;
    }

    return $rows;
  }

  /**
   * Build bulk actions form.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $count
   *   Number of results.
   *
   * @return array
   *   Render array for bulk actions.
   */
  protected function buildBulkActions($entity_type, $count) {
    if ($count === 0) {
      return [];
    }

    $build = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bulk Actions'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => ['class' => ['crm-bulk-actions']],
    ];

    $build['select_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all @count results', [
        '@count' => $count,
      ]),
    ];

    $build['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action:'),
      '#options' => $this->getBulkActionOptions($entity_type),
      '#empty_option' => '- Select action -',
    ];

    $build['apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
    ];

    return $build;
  }

  /**
   * Get available bulk actions for entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Array of bulk action options.
   */
  protected function getBulkActionOptions($entity_type) {
    $options = [
      'edit' => $this->t('Edit selected'),
      'delete' => $this->t('Delete selected'),
      'change_owner' => $this->t('Change owner'),
    ];

    switch ($entity_type) {
      case 'contact':
        $options['merge'] = $this->t('Merge duplicates');
        $options['segment'] = $this->t('Add to segment');
        break;

      case 'deal':
        $options['change_stage'] = $this->t('Change stage');
        $options['bulk_update'] = $this->t('Bulk update fields');
        break;

      case 'activity':
        $options['mark_complete'] = $this->t('Mark as complete');
        break;
    }

    return $options;
  }

}
