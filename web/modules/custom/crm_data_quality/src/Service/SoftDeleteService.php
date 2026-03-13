<?php

namespace Drupal\crm_data_quality\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\NodeInterface;

/**
 * Service for handling soft-delete of CRM entities.
 */
class SoftDeleteService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a SoftDeleteService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
  }

  /**
   * Soft-delete a node (mark as deleted instead of hard delete).
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to soft-delete.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function softDelete(NodeInterface $node) {
    // Set deleted_at timestamp if field exists
    if ($node->hasField('field_deleted_at')) {
      $node->set('field_deleted_at', \Drupal::time()->getCurrentTime());
      $node->save();
      return TRUE;
    }

    // Fallback: log deletion
    \Drupal::logger('crm_data_quality')->notice(
      'Soft-delete attempted on node @nid without field_deleted_at field.',
      ['@nid' => $node->id()]
    );

    return FALSE;
  }

  /**
   * Restore a soft-deleted node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to restore.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function restore(NodeInterface $node) {
    if ($node->hasField('field_deleted_at')) {
      $node->set('field_deleted_at', NULL);
      $node->save();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Permanently delete a soft-deleted node (hard delete).
   *
   * @param int $nid
   *   Node ID to permanently delete.
   *
   * @return bool
   *   TRUE if successful.
   */
  public function permanentlyDelete($nid) {
    try {
      $node = $this->entityTypeManager->getStorage('node')->load($nid);
      if ($node) {
        $node->delete();
        return TRUE;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('crm_data_quality')->error('Permanent delete failed: @msg', 
        ['@msg' => $e->getMessage()]);
    }

    return FALSE;
  }

  /**
   * Get soft-deleted nodes of a given type.
   *
   * @param string $type
   *   Node type (contact, deal, etc).
   *
   * @return array
   *   Array of soft-deleted node IDs.
   */
  public function getSoftDeleted($type) {
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', $type)
      ->condition('field_deleted_at', NULL, 'IS NOT NULL')
      ->accessCheck(FALSE);

    return $query->execute();
  }
}
