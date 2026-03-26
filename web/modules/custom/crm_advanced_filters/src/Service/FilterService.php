<?php

namespace Drupal\crm_advanced_filters\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\crm\Service\CRMAccessService;
use Drupal\node\NodeInterface;

/**
 * Service for building and executing advanced filter queries.
 *
 * Supports complex filter logic including AND/OR/NOT operators,
 * range filters, date filters, and fuzzy search.
 */
class FilterService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * CRM Access Service.
   *
   * @var \Drupal\crm\Service\CRMAccessService
   */
  protected $crmAccessService;

  /**
   * Supported operators.
   */
  const OPERATORS = [
    'equals' => '=',
    'not_equals' => '!=',
    'contains' => 'LIKE',
    'not_contains' => 'NOT LIKE',
    'starts' => 'LIKE',
    'ends' => 'LIKE',
    'greater_than' => '>',
    'less_than' => '<',
    'greater_equal' => '>=',
    'less_equal' => '<=',
    'between' => 'BETWEEN',
    'in' => 'IN',
    'not_in' => 'NOT IN',
    'is_empty' => 'IS NULL',
    'is_not_empty' => 'IS NOT NULL',
  ];

  /**
   * Core field mappings for each entity type.
   */
  const ENTITY_FIELD_MAPPING = [
    'contact' => [
      'title' => 'n.title',
      'email' => 'nfd_email.field_email_value',
      'phone' => 'nfd_phone.field_phone_number_value',
      'organization' => 'nfd_org.field_organization_target_id',
      'source' => 'nfd_source.field_source_value',
      'customer_type' => 'nfd_type.field_customer_type_value',
      'owner' => 'nfd_owner.field_owner_target_id',
      'created' => 'n.created',
      'modified' => 'n.changed',
      'status' => 'n.status',
      'tags' => 'nfd_tags.field_tags_target_id',
    ],
    'deal' => [
      'title' => 'n.title',
      'amount' => 'nfd_amount.field_amount_value',
      'stage' => 'nfd_stage.field_stage_value',
      'probability' => 'nfd_prob.field_probability_value',
      'closing_date' => 'nfd_date.field_closing_date_value',
      'contact' => 'nfd_contact.field_contact_target_id',
      'organization' => 'nfd_org.field_organization_target_id',
      'owner' => 'nfd_owner.field_owner_target_id',
      'created' => 'n.created',
      'modified' => 'n.changed',
      'status' => 'n.status',
    ],
    'organization' => [
      'title' => 'n.title',
      'email' => 'nfd_email.field_email_value',
      'phone' => 'nfd_phone.field_phone_number_value',
      'website' => 'nfd_website.field_website_uri',
      'industry' => 'nfd_industry.field_industry_value',
      'address' => 'nfd_address.field_address_value',
      'annual_revenue' => 'nfd_revenue.field_annual_revenue_value',
      'employees_count' => 'nfd_employees.field_employees_count_value',
      'owner' => 'nfd_owner.field_owner_target_id',
      'created' => 'n.created',
      'modified' => 'n.changed',
      'status' => 'n.status',
    ],
    'activity' => [
      'title' => 'n.title',
      'type' => 'nfd_type.field_type_value',
      'contact' => 'nfd_contact.field_contact_target_id',
      'deal' => 'nfd_deal.field_deal_target_id',
      'organization' => 'nfd_org.field_organization_target_id',
      'datetime' => 'nfd_datetime.field_datetime_value',
      'notes' => 'n.body',
      'created' => 'n.created',
      'modified' => 'n.changed',
      'status' => 'n.status',
    ],
  ];

  /**
   * Filter field definitions (types, options, display).
   */
  const FIELD_DEFINITIONS = [
    'contact' => [
      'title' => ['type' => 'text', 'label' => 'Contact Name'],
      'email' => ['type' => 'email', 'label' => 'Email'],
      'phone' => ['type' => 'phone', 'label' => 'Phone'],
      'organization' => ['type' => 'select', 'label' => 'Organization'],
      'source' => ['type' => 'select', 'label' => 'Source', 'options' => ['website', 'referral', 'social', 'cold_call', 'event']],
      'customer_type' => ['type' => 'select', 'label' => 'Customer Type', 'options' => ['prospect', 'customer', 'partner', 'competitor']],
      'owner' => ['type' => 'select', 'label' => 'Assigned Owner'],
      'created' => ['type' => 'date', 'label' => 'Created Date'],
      'modified' => ['type' => 'date', 'label' => 'Last Modified'],
      'status' => ['type' => 'select', 'label' => 'Status', 'options' => ['draft', 'published']],
    ],
    'deal' => [
      'title' => ['type' => 'text', 'label' => 'Deal Name'],
      'amount' => ['type' => 'number', 'label' => 'Amount'],
      'stage' => ['type' => 'select', 'label' => 'Stage', 'options' => ['lead', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost']],
      'probability' => ['type' => 'number', 'label' => 'Probability (%)'],
      'closing_date' => ['type' => 'date', 'label' => 'Expected Close Date'],
      'contact' => ['type' => 'select', 'label' => 'Primary Contact'],
      'organization' => ['type' => 'select', 'label' => 'Organization'],
      'owner' => ['type' => 'select', 'label' => 'Assigned Owner'],
      'created' => ['type' => 'date', 'label' => 'Created Date'],
      'modified' => ['type' => 'date', 'label' => 'Last Modified'],
    ],
    'organization' => [
      'title' => ['type' => 'text', 'label' => 'Organization Name'],
      'email' => ['type' => 'email', 'label' => 'Email'],
      'phone' => ['type' => 'phone', 'label' => 'Phone'],
      'website' => ['type' => 'url', 'label' => 'Website'],
      'industry' => ['type' => 'select', 'label' => 'Industry'],
      'address' => ['type' => 'text', 'label' => 'Address'],
      'annual_revenue' => ['type' => 'number', 'label' => 'Annual Revenue'],
      'employees_count' => ['type' => 'number', 'label' => 'Employees Count'],
      'owner' => ['type' => 'select', 'label' => 'Assigned Owner'],
      'created' => ['type' => 'date', 'label' => 'Created Date'],
      'modified' => ['type' => 'date', 'label' => 'Last Modified'],
    ],
    'activity' => [
      'title' => ['type' => 'text', 'label' => 'Subject'],
      'type' => ['type' => 'select', 'label' => 'Activity Type', 'options' => ['call', 'email', 'meeting', 'task', 'note']],
      'contact' => ['type' => 'select', 'label' => 'Contact'],
      'deal' => ['type' => 'select', 'label' => 'Deal'],
      'organization' => ['type' => 'select', 'label' => 'Organization'],
      'datetime' => ['type' => 'date', 'label' => 'Activity Date'],
      'notes' => ['type' => 'text', 'label' => 'Notes'],
      'created' => ['type' => 'date', 'label' => 'Created Date'],
      'modified' => ['type' => 'date', 'label' => 'Last Modified'],
    ],
  ];

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
    CRMAccessService $crm_access_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->logger = $logger_factory->get('crm_advanced_filters');
    $this->crmAccessService = $crm_access_service;
  }

  /**
   * Get available filters for an entity type.
   *
   * @param string $entity_type
   *   The entity type (contact, deal, organization, activity).
   *
   * @return array
   *   Array of available filter definitions.
   */
  public function getAvailableFilters($entity_type) {
    if (!isset(self::FIELD_DEFINITIONS[$entity_type])) {
      return [];
    }

    $filters = [];
    foreach (self::FIELD_DEFINITIONS[$entity_type] as $field_key => $definition) {
      $filter = [
        'key' => $field_key,
        'label' => $definition['label'],
        'type' => $definition['type'],
      ];

      if (isset($definition['options'])) {
        $filter['options'] = $definition['options'];
      }

      $filters[] = $filter;
    }

    return $filters;
  }

  /**
   * Get available options for a specific field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_key
   *   The field key.
   *
   * @return array
   *   Array of options for the field.
   */
  public function getFieldOptions($entity_type, $field_key) {
    if (!isset(self::FIELD_DEFINITIONS[$entity_type][$field_key])) {
      return [];
    }

    $definition = self::FIELD_DEFINITIONS[$entity_type][$field_key];

    // Return predefined options if they exist.
    if (isset($definition['options'])) {
      return $definition['options'];
    }

    // For reference fields, fetch from database.
    if ($definition['type'] === 'select') {
      return $this->fetchReferenceOptions($entity_type, $field_key);
    }

    return [];
  }

  /**
   * Fetch reference field options from database.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_key
   *   The field key.
   *
   * @return array
   *   Array of options.
   */
  protected function fetchReferenceOptions($entity_type, $field_key) {
    $options = [];

    // Map field keys to target entity types.
    $reference_map = [
      'organization' => 'node',
      'contact' => 'node',
      'deal' => 'node',
      'owner' => 'user',
    ];

    if (!isset($reference_map[$field_key])) {
      return [];
    }

    try {
      if ($field_key === 'owner') {
        // Fetch active users.
        $query = $this->database->select('users_field_data', 'u')
          ->fields('u', ['uid', 'name'])
          ->condition('u.status', 1)
          ->orderBy('u.name', 'ASC')
          ->execute();

        while ($row = $query->fetchAssoc()) {
          $options[$row['uid']] = $row['name'];
        }
      } else {
        // Fetch entities of the target type.
        $target_type = $reference_map[$field_key];
        $query = $this->database->select('node_field_data', 'n')
          ->fields('n', ['nid', 'title'])
          ->condition('n.type', $field_key)
          ->condition('n.status', 1)
          ->orderBy('n.title', 'ASC')
          ->execute();

        while ($row = $query->fetchAssoc()) {
          $options[$row['nid']] = $row['title'];
        }
      }
    } catch (\Exception $e) {
      $this->logger->error('Error fetching reference options: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return $options;
  }

  /**
   * Build and execute a filter query.
   *
   * @param string $entity_type
   *   The entity type to filter.
   * @param array $filter_definition
   *   Array of filter conditions.
   * @param array $sort
   *   Sorting configuration.
   * @param int $limit
   *   Result limit (default 50).
   * @param int $offset
   *   Result offset for pagination.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The user account for access control (current user if NULL).
   *
   * @return array
   *   Array with keys:
   *   - results: Array of entity objects
   *   - count: Total matching count
   *   - total_count: Total without limit
   *   - filter_description: Human-readable filter description
   */
  public function executeFilter($entity_type, array $filter_definition, array $sort = [], $limit = 50, $offset = 0, $account = NULL) {
    try {
      $query = $this->buildQuery($entity_type, $filter_definition, $account);

      if (!$query) {
        return [
          'results' => [],
          'count' => 0,
          'total_count' => 0,
          'filter_description' => 'Invalid filter definition',
        ];
      }

      // Get count before limit.
      $count_query = clone $query;
      $total_count = $count_query->countQuery()->execute()->fetchField();

      // Apply sorting.
      if (!empty($sort)) {
        foreach ($sort as $field => $direction) {
          $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
          $db_field = self::ENTITY_FIELD_MAPPING[$entity_type][$field] ?? NULL;
          if ($db_field) {
            $query->orderBy($db_field, $direction);
          }
        }
      }

      // Apply pagination.
      $query->range($offset, $limit);

      // Execute query.
      $result = $query->execute();
      $ids = $result->fetchCol();

      // Load entities.
      $entities = [];
      if (!empty($ids)) {
        $entities = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
      }

      return [
        'results' => array_values($entities),
        'count' => count($entities),
        'total_count' => (int) $total_count,
        'filter_description' => $this->describeFilter($filter_definition),
      ];
    } catch (\Exception $e) {
      $this->logger->error('Filter execution error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return [
        'results' => [],
        'count' => 0,
        'total_count' => 0,
        'filter_description' => 'Error executing filter',
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Build the database query from filter definition.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $filter_definition
   *   The filter conditions array.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account for access control.
   *
   * @return \Drupal\Core\Database\Query\Select|null
   *   The database query or NULL if invalid.
   */
  protected function buildQuery($entity_type, array $filter_definition, $account = NULL) {
    if (!isset(self::ENTITY_FIELD_MAPPING[$entity_type])) {
      $this->logger->error('Invalid entity type: @type', ['@type' => $entity_type]);
      return NULL;
    }

    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid'])
      ->condition('n.type', $entity_type);

    // Apply access control.
    $this->crmAccessService->applyAccessFiltering($query, $entity_type, $account);

    // Apply filter conditions.
    if (!empty($filter_definition['conditions'])) {
      $this->applyConditions($query, $entity_type, $filter_definition['conditions'], $filter_definition['logic'] ?? 'AND');
    }

    return $query;
  }

  /**
   * Apply filter conditions to query.
   *
   * @param \Drupal\Core\Database\Query\Select $query
   *   The database query.
   * @param string $entity_type
   *   The entity type.
   * @param array $conditions
   *   Array of conditions.
   * @param string $logic
   *   Logic operator (AND/OR).
   */
  protected function applyConditions(&$query, $entity_type, array $conditions, $logic = 'AND') {
    if (empty($conditions)) {
      return;
    }

    $group = $query->conditionGroup($logic);

    foreach ($conditions as $condition) {
      if (isset($condition['conditions'])) {
        // Nested condition group.
        $this->applyConditionGroup($group, $entity_type, $condition, $query);
      } else {
        // Simple condition.
        $this->applySimpleCondition($group, $entity_type, $condition, $query);
      }
    }

    $query->condition($group);
  }

  /**
   * Apply a condition group (nested conditions).
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $group
   *   The condition group.
   * @param string $entity_type
   *   The entity type.
   * @param array $condition
   *   The condition definition.
   * @param \Drupal\Core\Database\Query\Select $query
   *   The main query (for joins).
   */
  protected function applyConditionGroup(&$group, $entity_type, array $condition, &$query) {
    $nested_group = $group->conditionGroup($condition['logic'] ?? 'AND');

    foreach ($condition['conditions'] as $nested_condition) {
      if (isset($nested_condition['conditions'])) {
        // Deeper nesting.
        $this->applyConditionGroup($nested_group, $entity_type, $nested_condition, $query);
      } else {
        $this->applySimpleCondition($nested_group, $entity_type, $nested_condition, $query);
      }
    }

    $group->condition($nested_group);
  }

  /**
   * Apply a single condition to the group.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $group
   *   The condition group.
   * @param string $entity_type
   *   The entity type.
   * @param array $condition
   *   The condition definition.
   * @param \Drupal\Core\Database\Query\Select $query
   *   The main query (for joins).
   */
  protected function applySimpleCondition(&$group, $entity_type, array $condition, &$query) {
    $field = $condition['field'] ?? NULL;
    $operator = $condition['operator'] ?? 'equals';
    $value = $condition['value'] ?? NULL;

    if (!$field || !isset(self::ENTITY_FIELD_MAPPING[$entity_type][$field])) {
      return;
    }

    $db_field = self::ENTITY_FIELD_MAPPING[$entity_type][$field];

    // Handle NULL operators.
    if (in_array($operator, ['is_empty', 'is_not_empty'])) {
      if ($operator === 'is_empty') {
        $group->isNull($db_field);
      } else {
        $group->isNotNull($db_field);
      }
      return;
    }

    // Handle BETWEEN operator.
    if ($operator === 'between') {
      if (isset($value['from']) && isset($value['to'])) {
        $group->condition($db_field, [$value['from'], $value['to']], 'BETWEEN');
      }
      return;
    }

    // Handle IN/NOT IN operators.
    if (in_array($operator, ['in', 'not_in'])) {
      if (is_array($value) && !empty($value)) {
        $op = $operator === 'in' ? 'IN' : 'NOT IN';
        $group->condition($db_field, $value, $op);
      }
      return;
    }

    // Handle LIKE patterns.
    if (in_array($operator, ['contains', 'not_contains', 'starts', 'ends'])) {
      if ($operator === 'starts') {
        $value = $value . '%';
      } elseif ($operator === 'ends') {
        $value = '%' . $value;
      } elseif (in_array($operator, ['contains', 'not_contains'])) {
        $value = '%' . $value . '%';
      }

      $op = in_array($operator, ['not_contains']) ? 'NOT LIKE' : 'LIKE';
      $group->condition($db_field, $value, $op);
      return;
    }

    // Standard operators.
    $db_operator = self::OPERATORS[$operator] ?? '=';
    $group->condition($db_field, $value, $db_operator);
  }

  /**
   * Generate human-readable filter description.
   *
   * @param array $filter_definition
   *   The filter definition.
   *
   * @return string
   *   Human-readable description.
   */
  protected function describeFilter(array $filter_definition) {
    if (empty($filter_definition['conditions'])) {
      return 'No filters applied';
    }

    $descriptions = [];
    $logic = $filter_definition['logic'] ?? 'AND';

    foreach ($filter_definition['conditions'] as $condition) {
      if (isset($condition['conditions'])) {
        $descriptions[] = '(' . $this->describeConditionGroup($condition) . ')';
      } else {
        $descriptions[] = $this->describeSimpleCondition($condition);
      }
    }

    return implode(' ' . $logic . ' ', $descriptions);
  }

  /**
   * Describe a condition group.
   *
   * @param array $group
   *   The condition group.
   *
   * @return string
   *   Description.
   */
  protected function describeConditionGroup(array $group) {
    $logic = $group['logic'] ?? 'AND';
    $descriptions = [];

    foreach ($group['conditions'] as $condition) {
      if (isset($condition['conditions'])) {
        $descriptions[] = '(' . $this->describeConditionGroup($condition) . ')';
      } else {
        $descriptions[] = $this->describeSimpleCondition($condition);
      }
    }

    return implode(' ' . $logic . ' ', $descriptions);
  }

  /**
   * Describe a simple condition.
   *
   * @param array $condition
   *   The condition.
   *
   * @return string
   *   Description.
   */
  protected function describeSimpleCondition(array $condition) {
    $field = $condition['field'] ?? '';
    $operator = $condition['operator'] ?? '';
    $value = $condition['value'] ?? '';

    $operator_labels = [
      'equals' => 'equals',
      'not_equals' => 'does not equal',
      'contains' => 'contains',
      'not_contains' => 'does not contain',
      'starts' => 'starts with',
      'ends' => 'ends with',
      'greater_than' => 'is greater than',
      'less_than' => 'is less than',
      'greater_equal' => 'is at least',
      'less_equal' => 'is at most',
      'between' => 'is between',
      'in' => 'is one of',
      'not_in' => 'is not one of',
      'is_empty' => 'is empty',
      'is_not_empty' => 'is not empty',
    ];

    $op_label = $operator_labels[$operator] ?? $operator;

    if (is_array($value)) {
      $value = implode(', ', $value);
    }

    return ucfirst($field) . ' ' . $op_label . ' ' . $value;
  }

}
