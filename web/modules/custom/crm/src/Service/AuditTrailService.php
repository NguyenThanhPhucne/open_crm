<?php

namespace Drupal\crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Service for tracking entity changes and audit trail.
 */
class AuditTrailService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs an AuditTrailService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    $database
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->database = $database;
  }

  /**
   * Track field changes on entity save.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node being saved.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account making the change.
   */
  public function trackFieldChange(NodeInterface $node, AccountInterface $account) {
    $bundle = $node->bundle();
    $crm_types = ['contact', 'deal', 'organization', 'activity'];

    // Only track CRM entities
    if (!in_array($bundle, $crm_types)) {
      return;
    }

    try {
      // Update the updated_by field
      if ($node->hasField('field_updated_by')) {
        $node->set('field_updated_by', $account->id());
      }

      // Log the change
      $this->logAuditEvent($node, $account, 'Updated');
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm_audit')
        ->error('Error tracking field change: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Log audit event to database.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node that was changed.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user making the change.
   * @param string $action
   *   The action performed (Created, Updated, Deleted).
   */
  protected function logAuditEvent(NodeInterface $node, AccountInterface $account, $action) {
    try {
      $this->database->insert('crm_audit_log')
        ->fields([
          'entity_type' => 'node',
          'entity_id' => $node->id(),
          'entity_bundle' => $node->bundle(),
          'entity_title' => $node->getTitle(),
          'action' => $action,
          'user_id' => $account->id(),
          'user_name' => $account->getAccountName(),
          'changed' => time(),
        ])
        ->execute();
    } catch (\Exception $e) {
      // Table might not exist yet, silently fail
      $this->loggerFactory->get('crm_audit')
        ->debug('Could not log audit event: @error', ['@error' => $e->getMessage()]);
    }
  }

  /**
   * Get audit trail for an entity.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity ID.
   *
   * @return array
   *   Array of audit log entries.
   */
  public function getEntityAuditTrail($entity_type, $entity_id) {
    try {
      $query = $this->database->select('crm_audit_log', 'al')
        ->fields('al')
        ->condition('entity_type', $entity_type)
        ->condition('entity_id', $entity_id)
        ->orderBy('changed', 'DESC')
        ->range(0, 100);

      return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
      return [];
    }
  }

  /**
   * Get recent changes by user.
   *
   * @param int $user_id
   *   The user ID.
   * @param int $limit
   *   The number of records to return.
   *
   * @return array
   *   Array of recent changes.
   */
  public function getUserRecentChanges($user_id, $limit = 50) {
    try {
      $query = $this->database->select('crm_audit_log', 'al')
        ->fields('al')
        ->condition('user_id', $user_id)
        ->orderBy('changed', 'DESC')
        ->range(0, $limit);

      return $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
      return [];
    }
  }

}
