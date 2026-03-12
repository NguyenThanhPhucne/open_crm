<?php

namespace Drupal\crm_edit\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
 * Field handler to show CRM edit link.
 *
 * @ViewsField("crm_edit_link")
 */
class CrmEditLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query changes needed.
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $entity = $values->_entity;
    
    if (!$entity || $entity->getEntityTypeId() !== 'node') {
      return '';
    }
    
    $bundle = $entity->bundle();
    $crm_types = ['contact', 'deal', 'organization', 'activity'];
    
    if (!in_array($bundle, $crm_types)) {
      return '';
    }
    
    $account = \Drupal::currentUser();
    $nid = $entity->id();
    $title = htmlspecialchars($entity->getTitle() ?? '', ENT_QUOTES);
    
    // Check if user can edit
    $can_edit = $this->checkPermission($account, $entity, $bundle, 'edit');
    
    // Check if user can delete
    $can_delete = $this->checkPermission($account, $entity, $bundle, 'delete');
    
    if (!$can_edit && !$can_delete) {
      return '';
    }
    
    $buttons = '<div class="crm-action-buttons">';
    
    if ($can_edit) {
      $buttons .= '
        <button 
          class="crm-action-btn crm-edit-btn crm-edit-action" 
          data-nid="' . $nid . '" 
          data-bundle="' . $bundle . '"
          title="Quick Edit"
          type="button">
          <i data-lucide="edit-2"></i>
          <span>Edit</span>
        </button>';
    }
    
    if ($can_delete) {
      $buttons .= '
        <button 
          class="crm-action-btn crm-delete-btn crm-delete-action" 
          data-nid="' . $nid . '" 
          data-bundle="' . $bundle . '"
          data-title="' . $title . '"
          title="Delete"
          type="button">
          <i data-lucide="trash-2"></i>
          <span>Delete</span>
        </button>';
    }
    
    $buttons .= '</div>';
    
    return [
      '#markup' => Markup::create($buttons),
      '#attached' => [
        'library' => [
          'crm_edit/lucide',
          'crm_edit/inline_edit',
        ],
      ],
    ];
  }
  
  /**
   * Check if user has permission for action.
   */
  protected function checkPermission($account, $entity, $bundle, $action) {
    // Use Drupal's standard node access system.
    $drupal_op = ($action === 'delete') ? 'delete' : 'update';
    return $entity->access($drupal_op, $account);
  }
  
  /**
   * Get ownership field name.
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
