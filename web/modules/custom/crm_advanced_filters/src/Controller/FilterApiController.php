<?php

namespace Drupal\crm_advanced_filters\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\crm_advanced_filters\Service\FilterService;
use Drupal\crm_advanced_filters\Service\SavedFilterService;
use Drupal\crm_advanced_filters\Service\SuggestionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * REST API controller for advanced filters.
 *
 * Provides endpoints for:
 * - POST /api/crm/filters/query - Execute complex filter queries
 * - GET  /api/crm/filters/available/{type} - Get available filters
 * - GET  /api/crm/filters/options/{type}/{field} - Get field options
 * - POST /api/crm/filters/save - Save a filter
 * - GET  /api/crm/filters/saved - Get user's saved filters
 * - DELETE /api/crm/filters/{id} - Delete a saved filter
 */
class FilterApiController extends ControllerBase {

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
   * Execute a filter query.
   *
   * POST /api/crm/filters/query
   *
   * Request body:
   * {
   *   "entity_type": "contact",
   *   "filter_definition": {
   *     "logic": "AND",
   *     "conditions": [
   *       {
   *         "field": "email",
   *         "operator": "contains",
   *         "value": "@example.com"
   *       }
   *     ]
   *   },
   *   "sort": { "title": "ASC" },
   *   "limit": 50,
   *   "offset": 0
   * }
   */
  public function query(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);

      $entity_type = $data['entity_type'] ?? NULL;
      $filter_definition = $data['filter_definition'] ?? [];
      $sort = $data['sort'] ?? [];
      $limit = $data['limit'] ?? 50;
      $offset = $data['offset'] ?? 0;

      // Validate entity type.
      if (!$entity_type || !in_array($entity_type, ['contact', 'deal', 'organization', 'activity'])) {
        return new JsonResponse([
          'error' => 'Invalid entity type',
          'valid_types' => ['contact', 'deal', 'organization', 'activity'],
        ], 400);
      }

      // Limit result set for performance.
      $limit = min($limit, 500);
      $offset = max($offset, 0);

      // Execute filter.
      $results = $this->filterService->executeFilter(
        $entity_type,
        $filter_definition,
        $sort,
        $limit,
        $offset,
        $this->currentUser()
      );

      // Format results for API output.
      $formatted_results = [];
      foreach ($results['results'] as $entity) {
        $formatted_results[] = $this->formatEntity($entity);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'results' => $formatted_results,
          'count' => $results['count'],
          'total_count' => $results['total_count'],
          'filter_description' => $results['filter_description'],
          'pagination' => [
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $results['total_count'],
          ],
        ],
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('crm_advanced_filters')->error('Query API error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'error' => 'Error executing filter query',
        'message' => 'Please try again or contact support',
      ], 500);
    }
  }

  /**
   * Get available filters for an entity type.
   *
   * GET /api/crm/filters/available/{entity_type}
   */
  public function available($entity_type) {
    try {
      if (!in_array($entity_type, ['contact', 'deal', 'organization', 'activity'])) {
        return new JsonResponse([
          'error' => 'Invalid entity type',
        ], 400);
      }

      $available = $this->filterService->getAvailableFilters($entity_type);
      $suggestions = $this->suggestionService->getSmartSuggestions($entity_type);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'entity_type' => $entity_type,
          'available_filters' => $available,
          'suggestions' => $suggestions,
          'trending' => $this->formatTrendingFilters(
            $this->suggestionService->getTrendingFilters($entity_type)
          ),
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'error' => 'Error fetching available filters',
      ], 500);
    }
  }

  /**
   * Get available options for a field.
   *
   * GET /api/crm/filters/options/{entity_type}/{field}
   */
  public function options($entity_type, $field) {
    try {
      if (!in_array($entity_type, ['contact', 'deal', 'organization', 'activity'])) {
        return new JsonResponse(['error' => 'Invalid entity type'], 400);
      }

      $options = $this->filterService->getFieldOptions($entity_type, $field);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'field' => $field,
          'options' => $options,
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error fetching field options'], 500);
    }
  }

  /**
   * Save a filter search.
   *
   * POST /api/crm/filters/save
   *
   * Request body:
   * {
   *   "name": "High value deals",
   *   "description": "Deals with amount > 50000",
   *   "entity_type": "deal",
   *   "filter_definition": { ... },
   *   "is_public": false
   * }
   */
  public function save(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);

      $name = trim($data['name'] ?? '');
      $description = trim($data['description'] ?? '');
      $entity_type = $data['entity_type'] ?? NULL;
      $filter_definition = $data['filter_definition'] ?? [];
      $is_public = (bool) ($data['is_public'] ?? FALSE);

      if (!$name) {
        return new JsonResponse(['error' => 'Filter name is required'], 400);
      }

      if (!$entity_type || !in_array($entity_type, ['contact', 'deal', 'organization', 'activity'])) {
        return new JsonResponse(['error' => 'Invalid entity type'], 400);
      }

      if (empty($filter_definition) || empty($filter_definition['conditions'])) {
        return new JsonResponse(['error' => 'Filter definition is required'], 400);
      }

      $filter_id = $this->savedFilterService->saveFilter(
        $name,
        $entity_type,
        $filter_definition,
        $description,
        $is_public
      );

      if (!$filter_id) {
        return new JsonResponse(['error' => 'Failed to save filter'], 500);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'filter_id' => $filter_id,
          'name' => $name,
          'message' => 'Filter saved successfully',
        ],
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('crm_advanced_filters')->error('Save filter API error: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse(['error' => 'Error saving filter'], 500);
    }
  }

  /**
   * Get user's saved filters.
   *
   * GET /api/crm/filters/saved?entity_type=contact
   */
  public function saved(Request $request) {
    try {
      $entity_type = $request->query->get('entity_type');

      $saved = $this->savedFilterService->getUserFilters($entity_type);

      $formatted = [];
      foreach ($saved as $filter) {
        $formatted[] = [
          'id' => $filter->id(),
          'name' => $filter->get('name')->value,
          'description' => $filter->get('description')->value,
          'entity_type' => $filter->get('entity_type')->value,
          'is_public' => $filter->get('is_public')->value,
          'created' => $filter->getCreatedTime(),
          'last_used' => $filter->get('last_used')->value,
        ];
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'filters' => $formatted,
          'count' => count($formatted),
        ],
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error fetching saved filters'], 500);
    }
  }

  /**
   * Delete a saved filter.
   *
   * DELETE /api/crm/filters/{filter_id}
   */
  public function delete($filter_id) {
    try {
      $success = $this->savedFilterService->deleteFilter($filter_id);

      if (!$success) {
        return new JsonResponse(['error' => 'Filter not found or access denied'], 404);
      }

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Filter deleted successfully',
      ]);
    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Error deleting filter'], 500);
    }
  }

  /**
   * Format an entity for API output.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to format.
   *
   * @return array
   *   Formatted entity data.
   */
  protected function formatEntity($entity) {
    return [
      'id' => $entity->id(),
      'type' => $entity->getType(),
      'title' => $entity->getTitle(),
      'created' => $entity->getCreatedTime(),
      'modified' => $entity->getChangedTime(),
      'status' => $entity->isPublished() ? 'published' : 'draft',
      'url' => $entity->toUrl()->toString(),
    ];
  }

  /**
   * Format trending filters for output.
   *
   * @param array $filters
   *   Array of trending filter entities.
   *
   * @return array
   *   Formatted trending filters.
   */
  protected function formatTrendingFilters(array $filters) {
    $formatted = [];
    foreach ($filters as $filter) {
      $formatted[] = [
        'id' => $filter->id(),
        'name' => $filter->get('name')->value,
        'entity_type' => $filter->get('entity_type')->value,
        'created_by' => $filter->get('uid')->entity->getAccountName(),
        'last_used' => $filter->get('last_used')->value,
      ];
    }
    return $formatted;
  }

}
