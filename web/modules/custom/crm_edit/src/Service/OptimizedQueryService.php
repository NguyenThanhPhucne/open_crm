<?php

namespace Drupal\crm_edit\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Optimized Query Service.
 *
 * Provides optimized queries that avoid N+1 problems and use proper indexing.
 * Works in conjunction with QueryCacheService for maximum performance.
 */
class OptimizedQueryService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs OptimizedQueryService.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get contact list with efficient query (avoids N+1 problem).
   *
   * OPTIMIZATION: Instead of loading each contact separately, this query:
   * - Loads all needed fields in a single query
   * - Uses JOINs to get related data
   * - Only selects needed columns
   * - Returns array of simple objects (not full node entities)
   *
   * @param array $options
   *   Options array.
   *
   * @return array
   *   Array of contact objects with loaded fields.
   */
  public function getContactListOptimized($options = []) {
    $limit = $options['limit'] ?? 50;
    $offset = $options['offset'] ?? 0;

    $query = $this->database->select('node', 'n');
    $query->innerJoin('node__field_email', 'fe', 'n.nid = fe.entity_id');
    $query->leftJoin('node__field_phone', 'fp', 'n.nid = fp.entity_id');
    $query->leftJoin('node__field_deleted_at', 'fd', 'n.nid = fd.entity_id');
    $query->leftJoin('user_field_data', 'u', 'n.uid = u.uid');

    // Only select needed columns (huge performance gain)
    $query->fields('n', ['nid', 'title', 'created', 'changed']);
    $query->fields('fe', ['field_email_value']);
    $query->fields('fp', ['field_phone_value']);
    $query->fields('u', ['name']);

    // Filter
    $query->condition('n.type', 'contact');
    $query->condition('n.status', 1);
    $query->condition('fd.field_deleted_at_value', NULL, 'IS NULL');

    // Sort & Paginate
    $query->orderBy('n.created', 'DESC');
    $query->range($offset, $limit);

    return $query->execute()->fetchAll();
  }

  /**
   * Get contact with all related data efficiently.
   *
   * OPTIMIZATION: Batch load related entities instead of individual queries.
   *
   * @param int $nid
   *   Node ID.
   *
   * @return object|null
   *   Contact object or NULL.
   */
  public function getContactWithRelations($nid) {
    $query = $this->database->select('node', 'n');
    $query->leftJoin('node__field_email', 'fe', 'n.nid = fe.entity_id');
    $query->leftJoin('node__field_phone', 'fp', 'n.nid = fp.entity_id');
    $query->leftJoin('node__field_organization', 'fo', 'n.nid = fo.entity_id');
    $query->leftJoin('node__field_owner', 'fowner', 'n.nid = fowner.entity_id');
    $query->leftJoin('user_field_data', 'u', 'fowner.field_owner_target_id = u.uid');

    $query->fields('n', ['nid', 'title', 'created', 'changed', 'type']);
    $query->fields('fe', ['field_email_value']);
    $query->fields('fp', ['field_phone_value']);
    $query->fields('fo', ['field_organization_target_id']);
    $query->fields('u', ['uid', 'name']);

    $query->condition('n.nid', $nid);

    return $query->execute()->fetchObject();
  }

  /**
   * Batch load multiple contacts efficiently.
   *
   * @param array $nids
   *   Array of node IDs.
   *
   * @return array
   *   Array of contact objects keyed by nid.
   */
  public function getContactsBatch($nids) {
    if (empty($nids)) {
      return [];
    }

    $query = $this->database->select('node', 'n');
    $query->leftJoin('node__field_email', 'fe', 'n.nid = fe.entity_id');
    $query->leftJoin('node__field_phone', 'fp', 'n.nid = fp.entity_id');

    $query->fields('n', ['nid', 'title', 'created', 'changed']);
    $query->fields('fe', ['field_email_value']);
    $query->fields('fp', ['field_phone_value']);

    $query->condition('n.nid', $nids, 'IN');
    $query->condition('n.type', 'contact');

    $results = $query->execute()->fetchAllAssoc('nid');

    return $results;
  }

  /**
   * Count totals efficiently.
   *
   * @param string $type
   *   Node type.
   *
   * @return int
   *   Count of active nodes.
   */
  public function countByType($type) {
    $query = $this->database->select('node', 'n');
    $query->condition('n.type', $type);
    $query->condition('n.status', 1);

    // Add soft-delete filter if available
    $field_exists = $this->fieldExists('field_deleted_at');
    if ($field_exists) {
      $query->leftJoin('node__field_deleted_at', 'fd', 'n.nid = fd.entity_id');
      $query->isNull('fd.field_deleted_at_value');
    }

    return $query->countQuery()->execute()->fetchField();
  }

  /**
   * Check if field exists.
   *
   * @param string $field_name
   *   Field name.
   *
   * @return bool
   *   TRUE if field exists.
   */
  protected function fieldExists($field_name) {
    try {
      $field = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', $field_name);
      return $field !== NULL;
    } catch (\Exception $e) {
      return FALSE;
    }
  }
}
