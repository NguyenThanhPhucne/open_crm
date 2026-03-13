<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for Contacts list AJAX operations (delete, etc.).
 *
 * Handles AJAX requests for contact list management:
 * - Delete contact with confirmation
 * - Respects role-based access control
 * - Returns JSON responses for UI updates
 */
class ContactsListController extends ControllerBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * Delete a contact via AJAX.
   *
   * @param integer $nid
   *   The node ID of the contact to delete.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with status and message.
   */
  public function deleteContact(Request $request, $nid) {
    $account = $this->currentUser();

    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'CSRF token validation failed.',
        'code' => 403,
      ], 403);
    }

    // Load the contact.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node) {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Contact not found.',
        'code' => 404,
      ], 404);
    }

    // Verify it's a contact.
    if ($node->bundle() !== 'contact') {
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Invalid entity type.',
        'code' => 400,
      ], 400);
    }

    // Check delete access using the service.
    $access_service = $this->container->get('crm.access_service');
    if (!$access_service->canUserDeleteEntity($node, $account)) {
      $this->loggerFactory->get('crm')->warning(
        'User %uid attempted to delete contact %nid without permission.',
        ['%uid' => $account->id(), '%nid' => $nid]
      );

      return new JsonResponse([
        'status' => 'error',
        'message' => 'You do not have permission to delete this contact.',
        'code' => 403,
      ], 403);
    }

    // Log the deletion action.
    $contact_name = $node->label();
    $this->loggerFactory->get('crm')->info(
      'User %uid deleted contact "%name" (NID: %nid).',
      [
        '%uid' => $account->id(),
        '%name' => $contact_name,
        '%nid' => $nid,
      ]
    );

    try {
      // Delete the contact.
      $nid_deleted = $node->id();
      $node->delete();

      // Invalidate caches so list views update immediately without waiting
      Cache::invalidateTags(['node:' . $nid_deleted, 'node_list']);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Contact deleted successfully.',
        'nid' => $nid,
      ], 200);
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm')->error(
        'Failed to delete contact %nid: %error',
        [
          '%nid' => $nid,
          '%error' => $e->getMessage(),
        ]
      );

      return new JsonResponse([
        'status' => 'error',
        'message' => 'Failed to delete contact. Please try again.',
        'code' => 500,
      ], 500);
    }
  }

  /**
   * Display the contacts list page.
   *
   * @return array
   *   Render array for the contacts list page.
   */
  public function view() {
    $account = $this->currentUser();
    
    // DEBUG: Log that controller was called
    \Drupal::logger('crm')->info('ContactsListController::view() called for user @user', ['@user' => $account->getAccountName()]);

    // Get filtered contacts list based on user permissions.
    $contacts = $this->getContactsList($account);

    // Check if AI module is enabled.
    $ai_autocomplete_enabled = \Drupal::moduleHandler()->moduleExists('crm_ai_autocomplete');
    \Drupal::logger('crm')->info('AI autocomplete enabled: @enabled', ['@enabled' => $ai_autocomplete_enabled ? 'YES' : 'NO']);

    // Prepare render array using theme template.
    $build = [
      '#theme' => 'crm_contacts_list',
      '#contacts' => $contacts,
      '#account' => $account,
      '#ai_autocomplete_enabled' => $ai_autocomplete_enabled,
      '#attached' => [
        'library' => ['crm/contacts_list'],
      ],
    ];

    return $build;
  }

  /**
   * Get contacts list filtered by user permissions.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   *
   * @return array
   *   Array of contact data.
   */
  protected function getContactsList(AccountInterface $account) {
    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'contact')
      ->sort('changed', 'DESC')
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    $access_service = $this->container->get('crm.access_service');
    $contacts = [];
    $current_time = time();

    foreach ($nodes as $node) {
      // Check if user can view this contact.
      if (!$access_service->canUserViewEntity($node, $account)) {
        continue;
      }

      $changed = $node->getChangedTime();
      $created = $node->getCreatedTime();

      // Format timestamp to relative time.
      $changed_relative = $this->formatTimestamp($changed);
      $created_relative = $this->formatTimestamp($created);

      // Check if contact is badge-eligible (within 10 minutes).
      $is_new = $this->isBadgeEligible($created, 600);
      $is_updated = $this->isBadgeEligible($changed, 600) && $changed > $created;

      // Get owner name.
      $owner_name = '';
      if ($node->hasField('field_owner') && !$node->get('field_owner')->isEmpty()) {
        $owner_ref = $node->get('field_owner')->getValue();
        if (!empty($owner_ref[0]['target_id'])) {
          $owner = $this->entityTypeManager->getStorage('user')->load($owner_ref[0]['target_id']);
          if ($owner) {
            $owner_name = $owner->getDisplayName();
          }
        }
      }

      // Build contact data.
      $organization_name = '';
      if ($node->hasField('field_organization') && !$node->get('field_organization')->isEmpty()) {
        $organization = $node->get('field_organization')->entity;
        if ($organization) {
          $organization_name = $organization->label();
        }
      }

      $contact_data = [
        'nid' => $node->id(),
        'name' => $node->label(),
        'email' => $node->hasField('field_email') ? $node->get('field_email')->value : '',
        'organization' => $organization_name,
        'owner' => $owner_name,
        'created' => $created,
        'created_relative' => $created_relative,
        'changed' => $changed,
        'changed_relative' => $changed_relative,
        'is_new' => $is_new,
        'is_updated' => $is_updated,
        'can_edit' => $access_service->canUserEditEntity($node, $account),
        'can_delete' => $access_service->canUserDeleteEntity($node, $account),
      ];

      $contacts[] = $contact_data;
    }

    return $contacts;
  }

  /**
   * Format Unix timestamp to relative time string.
   *
   * @param int $timestamp
   *   Unix timestamp.
   *
   * @return string
   *   Relative time string like "2 minutes ago", "Yesterday", etc.
   */
  protected function formatTimestamp($timestamp) {
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
      return 'just now';
    } elseif ($diff < 3600) {
      $minutes = floor($diff / 60);
      return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
      $hours = floor($diff / 3600);
      return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
      $days = floor($diff / 86400);
      if ($days == 1) {
        return 'yesterday';
      }
      return $days . ' days ago';
    } else {
      $weeks = floor($diff / 604800);
      return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
  }

  /**
   * Check if item is badge-eligible (within time window).
   *
   * @param int $timestamp
   *   Unix timestamp of the event.
   * @param int $window_seconds
   *   Time window in seconds (default 600 = 10 minutes).
   *
   * @return bool
   *   TRUE if within window, FALSE otherwise.
   */
  protected function isBadgeEligible($timestamp, $window_seconds = 600) {
    $now = time();
    return ($now - $timestamp) <= $window_seconds;
  }

  /**
   * Access check for delete contact.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param integer $nid
   *   The node ID to delete.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function accessDeleteContact(AccountInterface $account, $nid) {
    // Load the node.
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node || $node->bundle() !== 'contact') {
      return AccessResult::forbidden();
    }

    // Use the access service to check delete permission.
    $access_service = $this->container->get('crm.access_service');
    $can_delete = $access_service->canUserDeleteEntity($node, $account);

    return $can_delete ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
