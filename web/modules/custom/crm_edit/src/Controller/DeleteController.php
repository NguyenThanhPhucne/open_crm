<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;

/**
 * Delete Controller for CRM entities.
 */
class DeleteController extends ControllerBase {

  /**
   * AJAX Delete handler.
   */
  public function ajaxDelete(Request $request) {
    $validated = $this->validateDeleteRequest($request);
    if (!$validated['valid']) {
      return new JsonResponse($validated['response'], $validated['status']);
    }

    try {
      $result = $this->executeDelete(
        $validated['node'],
        $validated['type'],
        $validated['requested_delete_mode']
      );
      return new JsonResponse($result['response'], $result['status']);
    }
    catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('Error deleting entity: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'status' => 'error',
        'message' => 'An error occurred while deleting. Please try again.',
      ], 500);
    }
  }

  /**
   * Validate delete request and resolve target node.
   */
  protected function validateDeleteRequest(Request $request) {
    $token = $request->headers->get('X-CSRF-Token');
    if (!$this->isValidCsrfToken($token)) {
      return $this->invalidValidationResult(403, 'CSRF token validation failed.');
    }

    $data = json_decode($request->getContent(), TRUE);
    return $this->validateDeletePayload($data);
  }

  /**
   * Validate delete payload after CSRF has been checked.
   */
  protected function validateDeletePayload(array $data) {
    $result = NULL;

    if (!isset($data['nid']) || !isset($data['type'])) {
      $result = $this->invalidValidationResult(400, 'Missing node ID or type');
    }
    else {
      $entity = \Drupal::entityTypeManager()->getStorage('node')->load($data['nid']);
      // Node storage always returns NodeInterface; the cast satisfies static analysis.
      $node = ($entity instanceof \Drupal\node\NodeInterface) ? $entity : NULL;
      if (!$node) {
        $result = $this->invalidValidationResult(404, 'Entity not found');
      }
      else {
        $type = (string) $data['type'];
        $requested_delete_mode = $this->normalizeDeleteMode($data['delete_mode'] ?? 'hard');
        $validation_error = $this->resolveDeleteValidationError($node, $type, $data, $requested_delete_mode);
        $result = $validation_error !== NULL
          ? $validation_error
          : [
            'valid' => TRUE,
            'node' => $node,
            'type' => $type,
            'requested_delete_mode' => $requested_delete_mode,
          ];
      }
    }

    return $result;
  }

  /**
   * Resolve request validation failure payload or NULL when valid.
   */
  protected function resolveDeleteValidationError(NodeInterface $node, $type, array $data, $requested_delete_mode) {
    $error = NULL;

    if ($node->bundle() !== $type) {
      $error = $this->invalidValidationResult(400, 'Invalid content type');
    }
    elseif (!$this->checkDeleteAccess($node)->isAllowed()) {
      $error = $this->invalidValidationResult(403, 'Access denied. You do not have permission to delete this content.');
    }
    elseif (!$this->isValidDeleteConfirmation($data, $node)) {
      $error = $this->invalidValidationResult(400, 'Confirmation text does not match. Please type the exact name to confirm deletion.');
    }
    elseif ($requested_delete_mode === 'hard' && !$this->canHardDelete()) {
      $error = $this->invalidValidationResult(403, 'You do not have permission to permanently delete records.');
    }

    return $error;
  }

  /**
   * Build a standardized invalid validation result payload.
   */
  protected function invalidValidationResult($status, $message) {
    return [
      'valid' => FALSE,
      'status' => (int) $status,
      'response' => [
        'success' => FALSE,
        'status' => 'error',
        'message' => (string) $message,
      ],
    ];
  }

  /**
   * Validate CSRF token value from request header.
   */
  protected function isValidCsrfToken($token) {
    return !empty($token)
      && \Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY);
  }

  /**
   * Validate confirmation text against current entity title.
   */
  protected function isValidDeleteConfirmation(array $data, NodeInterface $node) {
    $confirmation = trim((string) ($data['confirmation'] ?? ''));
    $title = trim((string) $node->getTitle());
    return $confirmation === $title;
  }

  /**
   * Execute delete operation after request validation.
   */
  protected function executeDelete(NodeInterface $node, $type, $requested_delete_mode) {
    $nid = (int) $node->id();
    $title = (string) $node->getTitle();
    $type_label = ucfirst($type);
    $bundle = $node->bundle();

    $performed_delete_mode = 'hard';
    if ($requested_delete_mode === 'soft') {
      if ($this->softDeleteEntity($node)) {
        $performed_delete_mode = 'soft';
        $deletion_message = "{$type_label} '{$title}' has been archived.";
      }
      else {
        return [
          'status' => 400,
          'response' => [
            'success' => FALSE,
            'status' => 'error',
            'message' => 'Soft-delete is not available for this content. Please contact admin.',
          ],
        ];
      }
    }
    else {
      $this->cleanupReferences($node);
      $node->delete();
      $deletion_message = "{$type_label} '{$title}' has been permanently deleted.";
    }

    $this->invalidateEntityCaches($nid, $bundle);

    \Drupal::logger('crm_edit')->notice('Deleted @type: @title (ID: @nid) by user @user', [
      '@type' => $type_label,
      '@title' => $title,
      '@nid' => $nid,
      '@user' => $this->currentUser()->getAccountName(),
    ]);

    return [
      'status' => 200,
      'response' => [
        'success' => TRUE,
        'status' => 'success',
        'message' => $deletion_message,
        'nid' => $nid,
        'delete_mode' => $performed_delete_mode,
      ],
    ];
  }

  /**
   * Normalize delete mode from request payload.
   */
  protected function normalizeDeleteMode($delete_mode) {
    $normalized = strtolower((string) $delete_mode);
    return in_array($normalized, ['soft', 'hard'], TRUE) ? $normalized : 'hard';
  }

  /**
   * Invalidate entity/list cache tags after delete operation.
   */
  protected function invalidateEntityCaches($nid, $bundle) {
    Cache::invalidateTags([
      'node:' . $nid,
      'node_list',
      'node_list:' . $bundle,
      'node_list:contact',
      'node_list:organization',
      'node_list:deal',
      'node_list:activity',
      'rendered',
    ]);
  }
  
  /**
   * Check if current user can delete this node.
   */
  protected function checkDeleteAccess(NodeInterface $node) {
    $account = $this->currentUser();
    // Use Drupal's standard node access system.
    if ($node->access('delete', $account)) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden('You do not have permission to delete this content.');
  }

  /**
   * Soft-delete entity using crm_data_quality service.
   */
  protected function softDeleteEntity(NodeInterface $node) {
    if (!$node->hasField('field_deleted_at')) {
      return FALSE;
    }

    try {
      return (bool) \Drupal::service('crm_data_quality.soft_delete')->softDelete($node);
    }
    catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('Soft delete failed for @nid: @msg', [
        '@nid' => $node->id(),
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }
  
  /**
   * Check if current user can perform a hard delete.
   *
   * Allowed roles:
   *   - Administrator (administer nodes)
   *   - Sales Manager (bypass crm team access OR sales_manager role)
   *   - Any user with explicit 'delete any * content' permission
   *
   * @return bool
   */
  protected function canHardDelete(): bool {
    $account = $this->currentUser();

    // Administrators always can.
    if ($account->hasPermission('administer nodes')) {
      return TRUE;
    }

    // Users with the bypass-team permission (Sales Managers) can delete.
    if ($account->hasPermission('bypass crm team access')) {
      return TRUE;
    }

    // Any role named 'sales_manager' can delete.
    /** @var \Drupal\user\UserInterface $full_account */
    $full_account = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->load($account->id());
    if ($full_account && in_array('sales_manager', $full_account->getRoles(), TRUE)) {
      return TRUE;
    }

    // User has explicit delete-any permission for at least one CRM type.
    $crm_types = ['contact', 'deal', 'organization', 'activity'];
    foreach ($crm_types as $type) {
      if ($account->hasPermission("delete any {$type} content")) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get ownership field name for content type.
   */
  protected function getOwnerField($bundle) {
    $fields = [
      'contact' => 'field_owner',
      'deal' => 'field_owner',
      'organization' => 'field_assigned_staff',
      'activity' => 'field_assigned_to',
    ];
    
    return $fields[$bundle] ?? 'field_owner';
  }

  /**
   * Clear references pointing to the deleted node to avoid orphan links.
   */
  protected function cleanupReferences(NodeInterface $node) {
    $nid = (int) $node->id();
    $bundle = $node->bundle();
    $storage = \Drupal::entityTypeManager()->getStorage('node');

    $rules = [
      'contact' => [
        ['type' => 'deal', 'field' => 'field_contact'],
        ['type' => 'activity', 'field' => 'field_contact'],
      ],
      'deal' => [
        ['type' => 'activity', 'field' => 'field_deal'],
      ],
      'organization' => [
        ['type' => 'contact', 'field' => 'field_organization'],
        ['type' => 'deal', 'field' => 'field_organization'],
        ['type' => 'activity', 'field' => 'field_organization'],
      ],
    ];

    if (empty($rules[$bundle])) {
      return;
    }

    foreach ($rules[$bundle] as $rule) {
      $target_type = $rule['type'];
      $field_name = $rule['field'];

      try {
        $ids = $storage->getQuery()
          ->condition('type', $target_type)
          ->condition($field_name, $nid)
          ->accessCheck(FALSE)
          ->execute();

        if (empty($ids)) {
          continue;
        }

        $entities = $storage->loadMultiple($ids);
        foreach ($entities as $entity) {
          if (!($entity instanceof \Drupal\node\NodeInterface)) {
            continue;
          }
          if ($entity->hasField($field_name)) {
            $entity->set($field_name, []);
            $entity->save();
          }
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('crm_edit')->warning('Reference cleanup failed for @type.@field -> @nid: @msg', [
          '@type' => $target_type,
          '@field' => $field_name,
          '@nid' => $nid,
          '@msg' => $e->getMessage(),
        ]);
      }
    }
  }
}
