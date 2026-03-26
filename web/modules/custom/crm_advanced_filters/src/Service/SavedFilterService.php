<?php

namespace Drupal\crm_advanced_filters\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Service for managing saved filter searches.
 *
 * Allows users to save, retrieve, update, and delete filter combinations.
 */
class SavedFilterService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Save a filter search.
   *
   * @param string $name
   *   The saved filter name.
   * @param string $entity_type
   *   The entity type (contact, deal, organization, activity).
   * @param array $filter_definition
   *   The filter definition array.
   * @param string $description
   *   Optional description.
   * @param bool $is_public
   *   Whether the filter is publicly available (for sharing).
   *
   * @return int|null
   *   The saved filter ID or NULL if save failed.
   */
  public function saveFilter($name, $entity_type, array $filter_definition, $description = '', $is_public = FALSE) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');

      // Check for duplicate names for this user.
      $existing = $storage->loadByProperties([
        'name' => $name,
        'entity_type' => $entity_type,
        'uid' => $this->currentUser->id(),
      ]);

      if (!empty($existing)) {
        // Update existing filter.
        $filter = reset($existing);
      } else {
        // Create new filter.
        $filter = $storage->create([
          'entity_type' => $entity_type,
          'uid' => $this->currentUser->id(),
        ]);
      }

      $filter->set('name', $name);
      $filter->set('description', $description);
      $filter->set('filter_definition', $filter_definition);
      $filter->set('is_public', $is_public);
      $filter->set('last_used', time());

      $filter->save();

      return $filter->id();
    } catch (\Exception $e) {
      \Drupal::logger('crm_advanced_filters')->error('Error saving filter: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Load a saved filter.
   *
   * @param int $filter_id
   *   The saved filter ID.
   *
   * @return object|null
   *   The saved filter entity or NULL.
   */
  public function loadFilter($filter_id) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');
      $filter = $storage->load($filter_id);

      if (!$filter) {
        return NULL;
      }

      // Check access.
      if ($filter->get('uid')->target_id != $this->currentUser->id() && !$filter->get('is_public')->value) {
        return NULL; // Access denied for private filters.
      }

      // Update last used timestamp.
      $filter->set('last_used', time());
      $filter->save();

      return $filter;
    } catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Get all saved filters for current user.
   *
   * @param string $entity_type
   *   Optional: filter by entity type.
   *
   * @return array
   *   Array of saved filter entities.
   */
  public function getUserFilters($entity_type = NULL) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $this->currentUser->id())
        ->sort('last_used', 'DESC');

      if ($entity_type) {
        $query->condition('entity_type', $entity_type);
      }

      $ids = $query->execute();
      return $storage->loadMultiple($ids);
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Delete a saved filter.
   *
   * @param int $filter_id
   *   The saved filter ID.
   *
   * @return bool
   *   TRUE if deleted, FALSE otherwise.
   */
  public function deleteFilter($filter_id) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');
      $filter = $storage->load($filter_id);

      if (!$filter) {
        return FALSE;
      }

      // Check ownership.
      if ($filter->get('uid')->target_id != $this->currentUser->id()) {
        return FALSE;
      }

      $filter->delete();
      return TRUE;
    } catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Get frequently used filters.
   *
   * @param string $entity_type
   *   The entity type filter.
   * @param int $limit
   *   Number of results to return.
   *
   * @return array
   *   Array of most used saved filters.
   */
  public function getFrequentFilters($entity_type = NULL, $limit = 10) {
    try {
      $storage = $this->entityTypeManager->getStorage('saved_filter');

      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->sort('last_used', 'DESC')
        ->range(0, $limit);

      if ($entity_type) {
        $query->condition('entity_type', $entity_type);
      }

      $ids = $query->execute();
      return $storage->loadMultiple($ids);
    } catch (\Exception $e) {
      return [];
    }
  }

}
