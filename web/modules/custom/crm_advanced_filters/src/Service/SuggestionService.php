<?php

namespace Drupal\crm_advanced_filters\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;

/**
 * Service for providing filter suggestions and auto-complete.
 *
 * Analyzes data patterns and provides intelligent suggestions for filters.
 */
class SuggestionService {

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
   * Filter service.
   *
   * @var \Drupal\crm_advanced_filters\Service\FilterService
   */
  protected $filterService;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    FilterService $filter_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->filterService = $filter_service;
  }

  /**
   * Get field value suggestions for auto-complete.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   * @param string $input
   *   The partial input to match.
   * @param int $limit
   *   Maximum number of suggestions.
   *
   * @return array
   *   Array of suggested values.
   */
  public function getFieldValueSuggestions($entity_type, $field, $input = '', $limit = 10) {
    try {
      $field_mapping = FilterService::ENTITY_FIELD_MAPPING[$entity_type] ?? [];

      if (!isset($field_mapping[$field])) {
        return [];
      }

      $db_field = $field_mapping[$field];
      $suggestions = [];

      // Get distinct values for the field.
      $query = $this->database->select('node_field_data', 'n')
        ->distinct()
        ->fields('n', [$db_field])
        ->condition('n.type', $entity_type)
        ->condition('n.status', 1)
        ->orderBy($db_field, 'ASC')
        ->range(0, $limit);

      if (!empty($input)) {
        $query->condition($db_field, '%' . $input . '%', 'LIKE');
      }

      $result = $query->execute();

      while ($row = $result->fetchAssoc()) {
        $value = array_values($row)[0];
        if (!empty($value)) {
          $suggestions[] = $value;
        }
      }

      return $suggestions;
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get smart filter suggestions based on data patterns.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Array of suggested filters.
   */
  public function getSmartSuggestions($entity_type) {
    $suggestions = [];

    // Analyze which fields have the most variance (good for filtering).
    $field_variance = $this->calculateFieldVariance($entity_type);

    // Top 5 fields by variance make the best filters.
    arsort($field_variance);
    $top_fields = array_slice(array_keys($field_variance), 0, 5);

    foreach ($top_fields as $field) {
      $suggestions[] = [
        'field' => $field,
        'label' => ucfirst(str_replace('_', ' ', $field)),
        'variance_score' => $field_variance[$field],
        'suggested_operators' => $this->getSuggestedOperators($entity_type, $field),
      ];
    }

    return $suggestions;
  }

  /**
   * Calculate field variance (diversity of values).
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return array
   *   Array of field => variance score.
   */
  protected function calculateFieldVariance($entity_type) {
    $variance = [];
    $field_mapping = FilterService::ENTITY_FIELD_MAPPING[$entity_type] ?? [];

    foreach ($field_mapping as $field_key => $db_field) {
      try {
        // Count distinct values for this field.
        $distinct_count = $this->database->select('node_field_data', 'n')
          ->distinct()
          ->fields('n', [$db_field])
          ->condition('n.type', $entity_type)
          ->condition('n.status', 1)
          ->countQuery()
          ->execute()
          ->fetchField();

        // Get total document count.
        $total_count = $this->database->select('node_field_data', 'n')
          ->condition('n.type', $entity_type)
          ->condition('n.status', 1)
          ->countQuery()
          ->execute()
          ->fetchField();

        // Variance is ratio of distinct values to total (higher = better for filtering).
        $variance[$field_key] = $total_count > 0 ? $distinct_count / $total_count : 0;
      } catch (\Exception $e) {
        $variance[$field_key] = 0;
      }
    }

    return $variance;
  }

  /**
   * Get suggested operators for a field.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field
   *   The field name.
   *
   * @return array
   *   Array of suggested operators.
   */
  protected function getSuggestedOperators($entity_type, $field) {
    $field_defs = FilterService::FIELD_DEFINITIONS[$entity_type] ?? [];

    if (!isset($field_defs[$field])) {
      return [];
    }

    $type = $field_defs[$field]['type'] ?? 'text';

    $operator_suggestions = [
      'text' => ['contains', 'equals', 'starts', 'ends'],
      'email' => ['equals', 'contains', 'ends'],
      'phone' => ['contains', 'equals'],
      'number' => ['equals', 'greater_than', 'less_than', 'between'],
      'date' => ['equals', 'greater_than', 'less_than', 'between'],
      'select' => ['equals', 'in', 'not_in'],
      'url' => ['contains', 'equals'],
    ];

    return $operator_suggestions[$type] ?? ['equals', 'contains'];
  }

  /**
   * Get commonly used filter combinations (trending filters).
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $limit
   *   Number of results.
   *
   * @return array
   *   Array of trending filter combinations.
   */
  public function getTrendingFilters($entity_type, $limit = 5) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('entity_type', $entity_type)
        ->condition('is_public', TRUE)
        ->sort('last_used', 'DESC')
        ->range(0, $limit);

      $ids = $query->execute();
      return $storage->loadMultiple($ids);
    } catch (\Exception $e) {
      return [];
    }
  }

}
