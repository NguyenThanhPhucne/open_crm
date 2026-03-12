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
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return new JsonResponse(['success' => false, 'message' => 'CSRF token validation failed.'], 403);
    }

    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data['nid']) || !isset($data['type'])) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing node ID or type',
      ], 400);
    }
    
    $nid = $data['nid'];
    $type = $data['type'];
    $confirmation = $data['confirmation'] ?? '';
    
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    
    if (!$node) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Entity not found',
      ], 404);
    }
    
    // Verify content type matches
    if ($node->bundle() !== $type) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Invalid content type',
      ], 400);
    }
    
    // Check delete access
    $access = $this->checkDeleteAccess($node);
    if (!$access->isAllowed()) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Access denied. You do not have permission to delete this content.',
      ], 403);
    }
    
    // Store node title for validation
    $title = $node->getTitle();
    $type_label = ucfirst($type);
    
    // Require exact title confirmation
    if (trim($confirmation) !== trim($title)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Confirmation text does not match. Please type the exact name to confirm deletion.',
      ], 400);
    }
    
    // Perform deletion
    try {
      $nid_to_delete = $node->id();
      $node->delete();

      // Invalidate caches so lists/dashboard update immediately
      Cache::invalidateTags(['node:' . $nid_to_delete, 'node_list']);
      
      // Log the deletion
      \Drupal::logger('crm_edit')->notice('Deleted @type: @title (ID: @nid) by user @user', [
        '@type' => $type_label,
        '@title' => $title,
        '@nid' => $nid,
        '@user' => $this->currentUser()->getAccountName(),
      ]);
      
      return new JsonResponse([
        'success' => true,
        'message' => "{$type_label} '{$title}' has been permanently deleted.",
        'nid' => $nid,
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('Error deleting @type: @message', [
        '@type' => $type_label,
        '@message' => $e->getMessage(),
      ]);
      
      return new JsonResponse([
        'success' => false,
        'message' => 'An error occurred while deleting. Please try again.',
      ], 500);
    }
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
}
