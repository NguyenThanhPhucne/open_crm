<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Delete Controller for CRM entities.
 */
class DeleteController extends ControllerBase {

  /**
   * AJAX Delete handler.
   */
  public function ajaxDelete(Request $request) {
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
      $node->delete();
      
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
    
    // Admin has full access
    if ($account->hasRole('administrator')) {
      return AccessResult::allowed();
    }
    
    $bundle = $node->bundle();
    
    // Managers can delete any content
    if ($account->hasRole('sales_manager')) {
      if ($account->hasPermission("delete any {$bundle} content")) {
        return AccessResult::allowed();
      }
    }
    
    // Sales reps can only delete own content
    if ($account->hasRole('sales_rep')) {
      $owner_field = $this->getOwnerField($bundle);
      
      if ($node->hasField($owner_field)) {
        $owner_id = $node->get($owner_field)->target_id;
        
        if ($owner_id == $account->id()) {
          if ($account->hasPermission("delete own {$bundle} content")) {
            return AccessResult::allowed();
          }
        }
      }
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
