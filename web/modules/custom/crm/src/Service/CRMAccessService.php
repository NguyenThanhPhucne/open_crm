<?php

namespace Drupal\crm\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Centralized service for CRM role-based access control.
 *
 * This service consolidates all access logic across the system.
 * All access decisions should go through this service to ensure consistency.
 *
 * Access Model:
 * - Administrator: Full access
 * - Sales Manager: Access to their team data (manage sales reps)
 * - Sales Rep: Access to own data only
 * - Anonymous: No access to CRM data
 */
class CRMAccessService {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    Connection $database,
    LoggerChannelFactoryInterface $logger_factory,
    $entity_type_manager = NULL
  ) {
    $this->database = $database;
    $this->loggerFactory = $logger_factory;
    if ($entity_type_manager) {
      $this->entityTypeManager = $entity_type_manager;
    }
  }

  /**
   * CRM entity types supported by this access service.
   *
   * @var array
   */
  protected $crmBundles = ['contact', 'deal', 'activity', 'organization'];

  /**
   * Determine if a user can view an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if user can view the entity, FALSE otherwise.
   */
  public function canUserViewEntity(EntityInterface $entity, AccountInterface $account) {
    return $this->checkEntityAccess($entity, $account, 'view');
  }

  /**
   * Determine if a user can edit an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if user can edit the entity, FALSE otherwise.
   */
  public function canUserEditEntity(EntityInterface $entity, AccountInterface $account) {
    return $this->checkEntityAccess($entity, $account, 'update');
  }

  /**
   * Determine if a user can delete an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return bool
   *   TRUE if user can delete the entity, FALSE otherwise.
   */
  public function canUserDeleteEntity(EntityInterface $entity, AccountInterface $account) {
    return $this->checkEntityAccess($entity, $account, 'delete');
  }

  /**
   * Check entity access for specific operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $op
   *   Operation: 'view', 'update', 'delete'.
   *
   * @return bool
   *   TRUE if access allowed, FALSE otherwise.
   */
  protected function checkEntityAccess(EntityInterface $entity, AccountInterface $account, $op) {
    // Only apply to CRM entities (nodes).
    if (!($entity instanceof NodeInterface)) {
      return TRUE;
    }

    $bundle = $entity->bundle();
    if (!in_array($bundle, $this->crmBundles)) {
      return TRUE;
    }

    // Admin gets full access.
    if ($account->hasRole('administrator')) {
      $this->log('allowed', $account, $entity, $op, 'Administrator');
      return TRUE;
    }

    // Sales Manager: can manage sales reps within the same team.
    // (Do NOT grant unrestricted access to all CRM content.)
    if ($account->hasRole('sales_manager')) {
      $owner_id = $this->getOwnerOfEntity($entity);

      if ($owner_id == $account->id()) {
        $this->log('allowed', $account, $entity, $op, 'Sales Manager — Entity owner');
        return TRUE;
      }

      if ($owner_id && $this->isSameTeam($account->id(), (int) $owner_id)) {
        $this->log('allowed', $account, $entity, $op, 'Sales Manager — Same team');
        return TRUE;
      }

      $this->log('denied', $account, $entity, $op, 'Sales Manager — Not owner and not same team');
      return FALSE;
    }

    // Bypass permission.
    if ($account->hasPermission('bypass crm team access')) {
      $this->log('allowed', $account, $entity, $op, 'Bypass permission');
      return TRUE;
    }

    // Anonymous users: deny ALL access to CRM data.
    if (!$account->id()) {
      $this->log('denied', $account, $entity, $op, 'Anonymous user — no CRM access');
      return FALSE;
    }

    // Sales Rep: only their own resources (no same-team expansion).
    if ($account->hasRole('sales_rep')) {
      $owner_id = $this->getOwnerOfEntity($entity);

      // Check 1: Is current user the owner?
      if ($owner_id == $account->id()) {
        $this->log('allowed', $account, $entity, $op, 'Entity owner');
        return TRUE;
      }

      $this->log('denied', $account, $entity, $op, 'Not owner, not same team');
      return FALSE;
    }

    // Other roles: deny by default.
    $this->log('denied', $account, $entity, $op, 'Role not recognized');
    return FALSE;
  }

  /**
   * Get owner field name for a bundle.
   *
   * @param string $bundle
   *   The node bundle.
   *
   * @return string
   *   The owner field name.
   */
  public function getOwnerField($bundle) {
    switch ($bundle) {
      case 'activity':
        return 'field_assigned_to';

      case 'organization':
        return 'field_assigned_staff';

      case 'contact':
      case 'deal':
      default:
        return 'field_owner';
    }
  }

  /**
   * Get the owner ID of an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return int|null
   *   The owner/assignee user ID, or NULL if not set.
   */
  public function getOwnerOfEntity(EntityInterface $entity) {
    if (!($entity instanceof NodeInterface)) {
      return NULL;
    }

    $owner_field = $this->getOwnerField($entity->bundle());

    if ($entity->hasField($owner_field)) {
      $field_value = $entity->get($owner_field);
      if (!$field_value->isEmpty()) {
        return $field_value->target_id;
      }
    }

    // Fallback to node uid.
    if ($entity->hasField('uid')) {
      $uid = $entity->get('uid')->target_id;
      if ($uid) {
        return $uid;
      }
    }

    return NULL;
  }

  /**
   * Check if two users are in the same team.
   *
   * @param int $uid1
   *   First user ID.
   * @param int $uid2
   *   Second user ID.
   *
   * @return bool
   *   TRUE if same team, FALSE otherwise.
   */
  public function isSameTeam($uid1, $uid2) {
    $team1 = $this->getUserTeam($uid1);
    $team2 = $this->getUserTeam($uid2);

    if (empty($team1) || empty($team2)) {
      return FALSE;
    }

    return ($team1 === $team2);
  }

  /**
   * Get user's team ID.
   *
   * @param int $uid
   *   User ID.
   *
   * @return int|null
   *   Team taxonomy term ID, or NULL if not set.
   */
  public function getUserTeam($uid) {
    if (!$this->entityTypeManager) {
      $this->entityTypeManager = \Drupal::entityTypeManager();
    }

    try {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);

      if ($user && $user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
        return $user->get('field_team')->target_id;
      }
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm_access')->error('Error getting user team: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Apply access filtering to an entity query.
   *
   * Automatically filters results based on user role.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The database query.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $node_alias
   *   The alias for the node_field_data table.
   */
  public function applyAccessFiltering(&$query, AccountInterface $account, $node_alias = 'n') {
    // Admin: no filtering needed.
    if ($account->hasRole('administrator') ||
        $account->hasPermission('bypass crm team access')) {
      return;
    }

    // Anonymous: deny all CRM data — add impossible condition.
    if (!$account->id()) {
      $query->condition($node_alias . '.nid', 0, '=');
      return;
    }

    // Sales Rep: only own resources (no same-team expansion).
    if ($account->hasRole('sales_rep')) {
      $this->applyAccessFilteringForSalesRep(
        $query,
        $account,
        $node_alias,
        FALSE
      );
      return;
    }

    // Sales Manager: filter by ownership plus same-team expansion.
    if ($account->hasRole('sales_manager')) {
      $this->applyAccessFilteringForSalesRep(
        $query,
        $account,
        $node_alias,
        TRUE
      );
      return;
    }
  }

  /**
   * Apply filtering for sales representatives.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The database query.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param string $node_alias
   *   The alias for the node_field_data table.
   */
  protected function applyAccessFilteringForSalesRep(&$query, AccountInterface $account, $node_alias, bool $allowSameTeam = FALSE) {
    // Check if the query object supports leftJoin operations (Entity queries might not)
    if (!method_exists($query, 'leftJoin')) {
      // If we cannot join, apply a strict fallback to ensure security:
      // only return nodes authored by the current user.
      $query->condition("{$node_alias}.uid", $account->id(), '=');
      return;
    }

    // Join with owner fields.
    $query->leftJoin('node__field_owner', 'crm_owner', "{$node_alias}.nid = crm_owner.entity_id");
    $query->leftJoin('node__field_assigned_to', 'crm_assigned', "{$node_alias}.nid = crm_assigned.entity_id");
    $query->leftJoin('node__field_assigned_staff', 'crm_staff', "{$node_alias}.nid = crm_staff.entity_id");

    // Build OR condition: user is owner/assignee.
    $or = $query->orConditionGroup()
      ->condition('crm_owner.field_owner_target_id', $account->id(), '=')
      ->condition('crm_assigned.field_assigned_to_target_id', $account->id(), '=')
      ->condition('crm_staff.field_assigned_staff_target_id', $account->id(), '=');

    // Optional: Add team-based filtering (used only for sales_manager).
    if ($allowSameTeam) {
      $user_team = $this->getUserTeam($account->id());
    }

    if (!empty($allowSameTeam) && $user_team) {
      // Join on field_owner_target_id (the CRM ownership field), NOT the Drupal
      // node author uid. Using uid would allow access based on who created the
      // Drupal node, which is different from who "owns" the CRM record.
      $query->leftJoin('user__field_team', 'crm_owner_team', "crm_owner.field_owner_target_id = crm_owner_team.entity_id");

      // Allow if the CRM record's owner is in the same team as the current user.
      $or->condition('crm_owner_team.field_team_target_id', $user_team, '=');
    }

    $query->condition($or);
  }

  /**
   * Get viewable entities for a user.
   *
   * Returns a query that is pre-filtered for the given user.
   *
   * @param string $bundle
   *   The node bundle to query.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The filtered query.
   */
  public function getViewableEntitiesQuery($bundle, AccountInterface $account) {
    $query = $this->database->select('node_field_data', 'n')
      ->fields('n', ['nid', 'title', 'type', 'created', 'changed'])
      ->condition('n.type', $bundle)
      ->condition('n.status', 1);

    // Apply access filtering.
    $this->applyAccessFiltering($query, $account, 'n');

    return $query;
  }

  /**
   * Log access decision.
   *
   * @param string $result
   *   'allowed' or 'denied'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $op
   *   The operation.
   * @param string $reason
   *   Why access was granted/denied.
   */
  protected function log($result, AccountInterface $account, EntityInterface $entity, $op, $reason) {
    if (!function_exists('drupal_static')) {
      return;
    }

    $logger = $this->loggerFactory->get('crm_access');

    $message = '@result: User @uid attempted @op on @type @entity (@reason)';
    $context = [
      '@result' => strtoupper($result),
      '@uid' => $account->id() ?: 'anonymous',
      '@op' => $op,
      '@type' => $entity->getEntityTypeId(),
      '@entity' => $entity->id(),
      '@reason' => $reason,
    ];

    if ($result === 'allowed') {
      $logger->info($message, $context);
    } else {
      $logger->notice($message, $context);
    }
  }

}
