<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;

/**
 * Controller for creating new CRM entities.
 */
class AddController extends ControllerBase {

  /**
   * Page that triggers add modal automatically.
   */
  public function addPage($type) {
    $crm_types = ['contact', 'deal', 'organization', 'activity'];
    if (!in_array($type, $crm_types)) {
      return [
        '#markup' => '<div class="error-message">Invalid content type</div>',
      ];
    }
    
    // Check create access
    $access = $this->checkCreateAccess($type);
    if (!$access) {
      return [
        '#markup' => '<div class="error-message">Access denied. You do not have permission to create this content type.</div>',
      ];
    }
    
    $type_label = ucfirst($type);
    
    return [
      '#markup' => '
        <div class="crm-add-page">
          <div class="loading-spinner">
            <i data-lucide="loader"></i>
            <p>Loading create form for ' . $type_label . '...</p>
          </div>
        </div>
        <script>
          document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
            if (typeof CRMInlineEdit !== "undefined") {
              CRMInlineEdit.openAddModal("' . $type . '");
            } else {
              setTimeout(function() {
                if (typeof CRMInlineEdit !== "undefined") {
                  CRMInlineEdit.openAddModal("' . $type . '");
                }
              }, 500);
            }
          });
        </script>
        <style>
          .crm-add-page {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 400px;
          }
          .loading-spinner {
            text-align: center;
          }
          .loading-spinner i {
            width: 48px;
            height: 48px;
            color: #3498db;
            animation: spin 1s linear infinite;
          }
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
          .loading-spinner p {
            margin-top: 20px;
            font-size: 16px;
            color: #666;
          }
        </style>
      ',
      '#attached' => [
        'library' => [
          'crm_edit/lucide',
          'crm_edit/inline_edit',
        ],
      ],
    ];
  }

  /**
   * Get create form HTML for modal.
   */
  public function getCreateForm(Request $request) {
    $type = $request->query->get('type');
    
    if (!$type) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing content type',
      ], 400);
    }
    
    $crm_types = ['contact', 'deal', 'organization', 'activity'];
    if (!in_array($type, $crm_types)) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Invalid content type',
      ], 400);
    }
    
    // Check create access
    $access = $this->checkCreateAccess($type);
    if (!$access) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Access denied. You do not have permission to create this content type.',
      ], 403);
    }
    
    // Get field definitions for this content type
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions('node', $type);
    
    $editable_fields = [];
    foreach ($fields as $field_name => $field_def) {
      if ($field_name === 'title' || strpos($field_name, 'field_') === 0) {
        if (!in_array($field_name, ['created', 'changed', 'uid'])) {
          $field_type = $field_def->getType();
          $field_label = $field_def->getLabel();
          $is_required = $field_def->isRequired();
          
          $field_settings = [];
          
          // Get field settings for entity references and lists
          if ($field_type === 'entity_reference') {
            $settings = $field_def->getSettings();
            $handler_settings = $settings['handler_settings'] ?? [];
            $target_type = $settings['target_type'] ?? null;
            $field_settings = [
              'target_type' => $target_type,
              'target_bundles' => $handler_settings['target_bundles'] ?? [],
            ];
          } elseif ($field_type === 'list_string' || $field_type === 'list_integer') {
            $field_settings['allowed_values'] = $field_def->getSetting('allowed_values') ?? [];
          } elseif ($field_type === 'boolean') {
            $field_settings['on_label'] = $field_def->getSetting('on_label') ?? 'Yes';
            $field_settings['off_label'] = $field_def->getSetting('off_label') ?? 'No';
          }
          
          $editable_fields[] = [
            'name' => $field_name,
            'label' => $field_label,
            'type' => $field_type,
            'required' => $is_required,
            'value' => '', // Empty for new content
            'settings' => $field_settings,
            'description' => $field_def->getDescription(),
          ];
        }
      }
    }
    
    return new JsonResponse([
      'success' => true,
      'html' => $this->generateCreateModalHTML($type, $editable_fields),
      'type' => $type,
    ]);
  }
  
  /**
   * Create new entity via AJAX.
   */
  public function ajaxCreate(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);
      $type = $data['type'] ?? null;
      
      if (!$type) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Missing content type',
        ], 400);
      }
      
      // Check create access
      $access = $this->checkCreateAccess($type);
      if (!$access) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Access denied. You do not have permission to create this content type.',
        ], 403);
      }
      
      // Create new node
      $node_data = [
        'type' => $type,
        'title' => $data['title'] ?? 'Untitled ' . ucfirst($type),
        'uid' => $this->currentUser()->id(),
        'status' => 1, // Published
      ];
      
      // Add field values
      foreach ($data as $field_name => $field_value) {
        if ($field_name !== 'type' && $field_name !== 'title' && strpos($field_name, 'field_') === 0) {
          if (!empty($field_value)) {
            $node_data[$field_name] = $field_value;
          }
        }
      }
      
      // Set owner field to current user if not specified
      $owner_field = $this->getOwnerField($type);
      if (!isset($node_data[$owner_field])) {
        $node_data[$owner_field] = ['target_id' => $this->currentUser()->id()];
      }
      
      $node = Node::create($node_data);
      $node->save();
      
      \Drupal::logger('crm_edit')->info('Created new @type: @title (nid: @nid) by user @user', [
        '@type' => $type,
        '@title' => $node->getTitle(),
        '@nid' => $node->id(),
        '@user' => $this->currentUser()->getAccountName(),
      ]);
      
      return new JsonResponse([
        'success' => true,
        'message' => ucfirst($type) . ' created successfully',
        'nid' => $node->id(),
        'title' => $node->getTitle(),
      ]);
      
    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('Error creating entity: @error', [
        '@error' => $e->getMessage(),
      ]);
      
      return new JsonResponse([
        'success' => false,
        'message' => 'An error occurred while creating the content: ' . $e->getMessage(),
      ], 500);
    }
  }
  
  /**
   * Check if user can create content of this type.
   */
  protected function checkCreateAccess($type) {
    $account = $this->currentUser();
    
    // Admin has full access
    if ($account->hasRole('administrator')) {
      return TRUE;
    }
    
    // Sales manager can create any content
    if ($account->hasRole('sales_manager')) {
      if ($account->hasPermission("create {$type} content")) {
        return TRUE;
      }
    }
    
    // Sales rep can create own content
    if ($account->hasRole('sales_rep')) {
      if ($account->hasPermission("create {$type} content")) {
        return TRUE;
      }
    }
    
    return FALSE;
  }
  
  /**
   * Get owner field name for content type.
   */
  protected function getOwnerField($type) {
    $fields = [
      'contact' => 'field_owner',
      'deal' => 'field_owner',
      'organization' => 'field_assigned_staff',
      'activity' => 'field_assigned_to',
    ];
    
    return $fields[$type] ?? 'field_owner';
  }
  
  /**
   * Generate create modal HTML.
   */
  protected function generateCreateModalHTML($type, $fields) {
    $type_label = ucfirst($type);
    
    ob_start();
    ?>
    <div class="crm-modal-container add-modal">
      <div class="crm-modal-header">
        <h2>
          <i data-lucide="plus-circle"></i>
          Create New <?= $type_label ?>
        </h2>
        <button class="crm-modal-close" type="button">
          <i data-lucide="x"></i>
        </button>
      </div>
      
      <form class="crm-modal-form add-form" data-type="<?= $type ?>">
        <div class="crm-modal-body">
          <?php foreach ($fields as $field): ?>
            <div class="form-field <?= $field['required'] ? 'required-field' : '' ?>">
              <label <?= $field['required'] ? 'class="required"' : '' ?>>
                <?= htmlspecialchars($field['label'] ?? '') ?>
                <?= $field['required'] ? '<span class="required-mark">*</span>' : '' ?>
              </label>
              
              <?php
                $field_name = $field['name'];
                $field_type = $field['type'];
                $field_settings = $field['settings'];
                $value = htmlspecialchars($field['value'] ?? '');
                
                // Render appropriate input based on field type
                switch ($field_type) {
                  case 'string':
                  case 'email':
                  case 'telephone':
                    $input_type = $field_type === 'email' ? 'email' : ($field_type === 'telephone' ? 'tel' : 'text');
                    echo '<input type="' . $input_type . '" name="' . $field_name . '" value="' . $value . '" ' . ($field['required'] ? 'required' : '') . ' />';
                    break;
                    
                  case 'text':
                  case 'text_long':
                  case 'string_long':
                    echo '<textarea name="' . $field_name . '" rows="4" ' . ($field['required'] ? 'required' : '') . '>' . $value . '</textarea>';
                    break;
                    
                  case 'text_with_summary':
                    echo '<textarea name="' . $field_name . '" rows="6" ' . ($field['required'] ? 'required' : '') . '>' . $value . '</textarea>';
                    break;
                    
                  case 'integer':
                  case 'decimal':
                  case 'float':
                    echo '<input type="number" name="' . $field_name . '" value="' . $value . '" step="' . ($field_type === 'integer' ? '1' : '0.01') . '" ' . ($field['required'] ? 'required' : '') . ' />';
                    break;
                    
                  case 'boolean':
                    $checked = $value ? 'checked' : '';
                    echo '<label class="checkbox-label">';
                    echo '<input type="checkbox" name="' . $field_name . '" value="1" ' . $checked . ' />';
                    echo '<span>' . ($field_settings['on_label'] ?? 'Yes') . '</span>';
                    echo '</label>';
                    break;
                    
                  case 'datetime':
                  case 'timestamp':
                    echo '<input type="datetime-local" name="' . $field_name . '" value="' . $value . '" ' . ($field['required'] ? 'required' : '') . ' />';
                    break;
                    
                  case 'list_string':
                  case 'list_integer':
                    echo '<select name="' . $field_name . '" ' . ($field['required'] ? 'required' : '') . '>';
                    echo '<option value="">- Select -</option>';
                    if (!empty($field_settings['allowed_values'])) {
                      foreach ($field_settings['allowed_values'] as $key => $label) {
                        $selected = ($value == $key) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars((string)$key) . '" ' . $selected . '>' . htmlspecialchars($label ?? '') . '</option>';
                      }
                    }
                    echo '</select>';
                    break;
                    
                  case 'entity_reference':
                    $target_type = $field_settings['target_type'] ?? null;
                    echo '<select name="' . $field_name . '" ' . ($field['required'] ? 'required' : '') . '>';
                    echo '<option value="">- Select -</option>';
                    
                    if ($target_type === 'user') {
                      // Load users for assignment
                      $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['status' => 1]);
                      foreach ($users as $user) {
                        if ($user->id() > 0) { // Skip anonymous
                          $selected = ($value == $user->id()) ? 'selected' : '';
                          echo '<option value="' . $user->id() . '" ' . $selected . '>' . htmlspecialchars($user->getDisplayName() ?? '') . '</option>';
                        }
                      }
                    } elseif ($target_type === 'taxonomy_term') {
                      // Load taxonomy terms
                      $target_bundles = $field_settings['target_bundles'] ?? [];
                      if (!empty($target_bundles)) {
                        foreach ($target_bundles as $vocabulary) {
                          $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
                          foreach ($terms as $term) {
                            $selected = ($value == $term->tid) ? 'selected' : '';
                            echo '<option value="' . $term->tid . '" ' . $selected . '>' . htmlspecialchars($term->name ?? '') . '</option>';
                          }
                        }
                      }
                    } elseif ($target_type === 'node') {
                      // Load related nodes
                      $target_bundles = $field_settings['target_bundles'] ?? [];
                      if (!empty($target_bundles)) {
                        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => $target_bundles]);
                        foreach ($nodes as $node) {
                          $selected = ($value == $node->id()) ? 'selected' : '';
                          echo '<option value="' . $node->id() . '" ' . $selected . '>' . htmlspecialchars($node->getTitle() ?? '') . '</option>';
                        }
                      }
                    }
                    
                    echo '</select>';
                    break;
                    
                  default:
                    echo '<input type="text" name="' . $field_name . '" value="' . $value . '" ' . ($field['required'] ? 'required' : '') . ' />';
                    break;
                }
              ?>
              
              <?php if (!empty($field['description'])): ?>
                <div class="field-description"><?= $field['description'] ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="crm-modal-footer">
          <button type="submit" class="btn btn-primary save-btn">
            <i data-lucide="plus-circle"></i>
            <span>Create <?= $type_label ?></span>
          </button>
          <button type="button" class="btn btn-secondary cancel-btn crm-modal-close">
            <i data-lucide="x"></i>
            <span>Cancel</span>
          </button>
          <div class="save-status"></div>
        </div>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
}
