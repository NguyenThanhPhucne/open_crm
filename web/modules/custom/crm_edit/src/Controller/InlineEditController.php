<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Markup;

/**
 * Inline Edit Controller for CRM entities.
 */
class InlineEditController extends ControllerBase {

  /**
   * Check if current user can edit this node.
   */
  protected function checkEditAccess(NodeInterface $node) {
    $account = $this->currentUser();
    
    // Admin has full access
    if ($account->hasRole('administrator')) {
      return AccessResult::allowed();
    }
    
    $bundle = $node->bundle();
    
    // Managers can edit any content
    if ($account->hasRole('sales_manager')) {
      if ($account->hasPermission("edit any {$bundle} content")) {
        return AccessResult::allowed();
      }
    }
    
    // Sales reps can only edit own content
    if ($account->hasRole('sales_rep')) {
      // Determine ownership field
      $owner_field = $this->getOwnerField($bundle);
      
      if ($node->hasField($owner_field)) {
        $owner_id = $node->get($owner_field)->target_id;
        
        if ($owner_id == $account->id()) {
          if ($account->hasPermission("edit own {$bundle} content")) {
            return AccessResult::allowed();
          }
        }
      }
    }
    
    return AccessResult::forbidden('You do not have permission to edit this content.');
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
   * Edit Contact.
   */
  public function editContact(NodeInterface $node) {
    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return [
        '#markup' => '<div class="error-message">Access denied. You cannot edit this contact.</div>',
      ];
    }
    
    return $this->buildEditForm($node, 'contact');
  }
  
  /**
   * Edit Deal.
   */
  public function editDeal(NodeInterface $node) {
    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return [
        '#markup' => '<div class="error-message">Access denied. You cannot edit this deal.</div>',
      ];
    }
    
    return $this->buildEditForm($node, 'deal');
  }
  
  /**
   * Edit Organization.
   */
  public function editOrganization(NodeInterface $node) {
    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return [
        '#markup' => '<div class="error-message">Access denied. You cannot edit this organization.</div>',
      ];
    }
    
    return $this->buildEditForm($node, 'organization');
  }
  
  /**
   * Edit Activity.
   */
  public function editActivity(NodeInterface $node) {
    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return [
        '#markup' => '<div class="error-message">Access denied. You cannot edit this activity.</div>',
      ];
    }
    
    return $this->buildEditForm($node, 'activity');
  }
  
  /**
   * Build inline edit form HTML with AJAX.
   */
  protected function buildEditForm(NodeInterface $node, $type) {
    $nid = $node->id();
    $title = $node->getTitle();
    $account = $this->currentUser();
    
    // Get field definitions
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions('node', $type);
    
    // Build editable fields list
    $editable_fields = $this->getEditableFields($node, $fields);
    
    $html = $this->generateEditFormHTML($node, $type, $editable_fields);
    
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => ['crm_edit/inline_edit'],
      ],
    ];
  }
  
  /**
   * Get editable fields for node.
   */
  protected function getEditableFields(NodeInterface $node, $fields) {
    $editable = [];
    
    foreach ($fields as $field_name => $field_def) {
      // Skip base fields except title
      if ($field_name !== 'title' && strpos($field_name, 'field_') !== 0) {
        continue;
      }
      
      if ($field_name === 'created' || $field_name === 'changed' || $field_name === 'uid') {
        continue;
      }
      
      $field_type = $field_def->getType();
      $field_label = $field_def->getLabel();
      $is_required = $field_def->isRequired();
      
      $current_value = '';
      if ($node->hasField($field_name)) {
        $field_item = $node->get($field_name);
        $current_value = $this->formatFieldValue($field_item, $field_type);
      } elseif ($field_name === 'title') {
        $current_value = $node->getTitle();
      }
      
      $editable[] = [
        'name' => $field_name,
        'label' => $field_label,
        'type' => $field_type,
        'required' => $is_required,
        'value' => $current_value,
        'definition' => $field_def,
      ];
    }
    
    return $editable;
  }
  
  /**
   * Format field value for display in form.
   */
  protected function formatFieldValue($field_item, $field_type) {
    if ($field_item->isEmpty()) {
      return '';
    }
    
    $value = $field_item->value;
    
    switch ($field_type) {
      case 'entity_reference':
        $target = $field_item->entity;
        return $target ? $target->id() : '';
        
      case 'datetime':
        return $field_item->value;
        
      case 'decimal':
      case 'integer':
      case 'float':
        return $value;
        
      case 'email':
      case 'string':
      case 'text':
      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
        return $value;
        
      default:
        return $value;
    }
  }
  
  /**
   * Generate edit form HTML.
   */
  protected function generateEditFormHTML(NodeInterface $node, $type, $editable_fields) {
    $nid = $node->id();
    $type_label = ucfirst($type);
    
    ob_start();
    ?>
    <div class="crm-edit-container">
      <div class="crm-edit-header">
        <h2><?= $type_label ?>: <?= htmlspecialchars($node->getTitle() ?? '') ?></h2>
        <a href="/node/<?= $nid ?>" class="back-link">← Back to View</a>
      </div>
      
      <form id="crm-edit-form" class="crm-edit-form" data-nid="<?= $nid ?>" data-type="<?= $type ?>">
        <input type="hidden" name="nid" value="<?= $nid ?>">
        <input type="hidden" name="type" value="<?= $type ?>">
        
        <div class="crm-edit-fields">
          <?php foreach ($editable_fields as $field): ?>
            <?php $this->renderField($field); ?>
          <?php endforeach; ?>
        </div>
        
        <div class="crm-edit-actions">
          <button type="submit" class="btn btn-primary save-btn">
            <i data-lucide="save"></i> Save Changes
          </button>
          <button type="button" class="btn btn-secondary cancel-btn" onclick="history.back()">
            <i data-lucide="x"></i> Cancel
          </button>
          <div class="save-status"></div>
        </div>
      </form>
    </div>
    
    <style>
      .crm-edit-container {
        max-width: 900px;
        margin: 20px auto;
        padding: 30px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      
      .crm-edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e0e0e0;
      }
      
      .crm-edit-header h2 {
        margin: 0;
        font-size: 24px;
        color: #2c3e50;
      }
      
      .back-link {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s;
      }
      
      .back-link:hover {
        color: #2980b9;
      }
      
      .crm-edit-fields {
        display: grid;
        gap: 20px;
        margin-bottom: 30px;
      }
      
      .form-field {
        display: flex;
        flex-direction: column;
      }
      
      .form-field label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #34495e;
        font-size: 14px;
      }
      
      .form-field label.required::after {
        content: ' *';
        color: #e74c3c;
      }
      
      .form-field input[type="text"],
      .form-field input[type="email"],
      .form-field input[type="number"],
      .form-field input[type="date"],
      .form-field input[type="datetime-local"],
      .form-field select,
      .form-field textarea {
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.2s;
      }
      
      .form-field input:focus,
      .form-field select:focus,
      .form-field textarea:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
      }
      
      .form-field textarea {
        min-height: 100px;
        resize: vertical;
      }
      
      .crm-edit-actions {
        display: flex;
        gap: 12px;
        align-items: center;
        padding-top: 20px;
        border-top: 2px solid #e0e0e0;
      }
      
      .btn {
        padding: 10px 20px;
        border: 1.5px solid;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background .15s, border-color .15s, color .15s;
      }
      
      .btn-primary {
        background: #fff;
        color: #2563eb;
        border-color: #2563eb;
      }
      
      .btn-primary:hover {
        background: #eff6ff;
        color: #1d4ed8;
        border-color: #1d4ed8;
      }
      
      .btn-secondary {
        background: #fff;
        color: #64748b;
        border-color: #cbd5e1;
      }
      
      .btn-secondary:hover {
        background: #f8fafc;
        color: #475569;
        border-color: #94a3b8;
      }
      
      .save-status {
        margin-left: auto;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        display: none;
      }
      
      .save-status.success {
        display: block;
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }
      
      .save-status.error {
        display: block;
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }
      
      .field-error {
        color: #e74c3c;
        font-size: 13px;
        margin-top: 4px;
      }
    </style>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        
        const form = document.getElementById('crm-edit-form');
        const statusDiv = document.querySelector('.save-status');
        
        form.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          // Clear previous errors
          document.querySelectorAll('.field-error').forEach(el => el.remove());
          statusDiv.className = 'save-status';
          statusDiv.textContent = '';
          
          // Collect form data
          const formData = new FormData(form);
          const data = {};
          formData.forEach((value, key) => {
            data[key] = value;
          });
          
          // Show saving status
          const saveBtn = form.querySelector('.save-btn');
          const originalText = saveBtn.innerHTML;
          saveBtn.innerHTML = '<i data-lucide="loader"></i> Saving...';
          saveBtn.disabled = true;
          lucide.createIcons();
          
          try {
            const response = await fetch('/crm/edit/ajax/save', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
              statusDiv.className = 'save-status success';
              statusDiv.innerHTML = '<i data-lucide="check-circle"></i> Saved successfully!';
              lucide.createIcons();
              
              // Redirect after 1 second
              setTimeout(() => {
                window.location.href = '/node/' + data.nid;
              }, 1000);
            } else {
              throw new Error(result.message || 'Save failed');
            }
          } catch (error) {
            statusDiv.className = 'save-status error';
            statusDiv.innerHTML = '<i data-lucide="alert-circle"></i> ' + error.message;
            lucide.createIcons();
            
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
            lucide.createIcons();
          }
        });
      });
    </script>
    <?php
    return ob_get_clean();
  }
  
  /**
   * Render individual field.
   */
  protected function renderField($field) {
    $name = $field['name'];
    $label = $field['label'];
    $type = $field['type'];
    $required = $field['required'];
    $value = htmlspecialchars($field['value'] ?? '');
    $required_class = $required ? 'required' : '';
    
    echo '<div class="form-field">';
    echo "<label class='$required_class'>$label</label>";
    
    switch ($type) {
      case 'email':
        echo "<input type='email' name='$name' value='$value' " . ($required ? 'required' : '') . ">";
        break;
        
      case 'decimal':
      case 'float':
        echo "<input type='number' step='0.01' name='$name' value='$value' " . ($required ? 'required' : '') . ">";
        break;
        
      case 'integer':
        echo "<input type='number' name='$name' value='$value' " . ($required ? 'required' : '') . ">";
        break;
        
      case 'datetime':
        echo "<input type='datetime-local' name='$name' value='$value' " . ($required ? 'required' : '') . ">";
        break;
        
      case 'text_long':
      case 'text_with_summary':
        echo "<textarea name='$name' " . ($required ? 'required' : '') . ">$value</textarea>";
        break;
        
      case 'entity_reference':
        echo "<input type='text' name='$name' value='$value' placeholder='Entity ID' " . ($required ? 'required' : '') . ">";
        echo "<small>Enter entity ID or use autocomplete (to be implemented)</small>";
        break;
        
      default:
        echo "<input type='text' name='$name' value='$value' " . ($required ? 'required' : '') . ">";
        break;
    }
    
    echo '</div>';
  }
  
  /**
   * AJAX Save handler.
   */
  public function ajaxSave(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    if (!isset($data['nid']) || !isset($data['type'])) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing node ID or type',
      ], 400);
    }
    
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($data['nid']);
    
    if (!$node) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Node not found',
      ], 404);
    }
    
    // Check access
    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Access denied',
      ], 403);
    }
    
    // Update fields
    try {
      foreach ($data as $field_name => $value) {
        if (in_array($field_name, ['nid', 'type'])) {
          continue;
        }
        
        if ($field_name === 'title') {
          $node->setTitle($value);
        } elseif ($node->hasField($field_name)) {
          // Get field definition to check type
          $field_definition = $node->getFieldDefinition($field_name);
          $field_type = $field_definition ? $field_definition->getType() : null;
          
          // Handle empty values for entity reference fields (taxonomy, etc)
          if ($field_type === 'entity_reference' || $field_type === 'entity_reference_revisions') {
            if ($value === '' || $value === null || (is_array($value) && empty($value))) {
              // Set to empty array instead of empty string to avoid SQL errors
              $node->set($field_name, []);
              continue;
            }
          }
          
          // Handle other empty values
          if ($value === '' && !in_array($field_type, ['string', 'string_long', 'text', 'text_long', 'text_with_summary'])) {
            // For numeric/date fields, skip empty strings
            continue;
          }
          
          $node->set($field_name, $value);
        }
      }
      
      $node->save();
      
      return new JsonResponse([
        'success' => true,
        'message' => 'Content updated successfully',
        'nid' => $node->id(),
      ]);
    } catch (\Exception $e) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Error saving: ' . $e->getMessage(),
      ], 500);
    }
  }
  
  /**
   * AJAX Validate handler.
   */
  public function ajaxValidate(Request $request) {
    $data = json_decode($request->getContent(), TRUE);
    
    // Validation logic here
    
    return new JsonResponse([
      'valid' => true,
      'errors' => [],
    ]);
  }
}
