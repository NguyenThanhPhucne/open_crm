<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Markup;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;

/**
 * Inline Edit Controller for CRM entities.
 */
class InlineEditController extends ControllerBase {

  /**
   * Check if current user can edit this node.
   */
  protected function checkEditAccess(NodeInterface $node) {
    $account = $this->currentUser();
    // Use Drupal's standard node access system.
    if ($node->access('update', $account)) {
      return AccessResult::allowed();
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
      
      // EXCLUDE redundant or internal fields from inline editing
      $excluded_fields = [
        'field_contract', // Redundant with field_contract_file
        'field_deleted_at', // Internal soft-delete flag
      ];
      if (in_array($field_name, $excluded_fields)) {
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
        
      case 'file':
      case 'image':
        $files = [];
        foreach ($field_item as $item) {
          $file = $item->entity;
          if ($file) {
            $files[] = [
              'fid' => $file->id(),
              'name' => $file->getFilename(),
            ];
          }
        }
        return !empty($files) ? json_encode($files) : '';
        
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

      .btn svg {
        width: 18px !important;
        height: 18px !important;
        stroke: currentColor !important;
        fill: none !important;
        stroke-width: 2px !important;
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
            const csrfToken = await fetch('/session/token').then(r => r.text());
            const response = await fetch('/crm/edit/ajax/save', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken,
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
        
      case 'file':
      case 'image':
        // Show current files info (read-only summary)
        if (!empty($value)) {
          $files_data = json_decode($value, TRUE);
          if (is_array($files_data)) {
            echo "<div class='file-current-info'>";
            foreach ($files_data as $f) {
              echo "<span class='file-badge'>" . htmlspecialchars($f['name'] ?? '') . "</span> ";
            }
            echo "</div>";
          }
        }
        $field_name_lower = strtolower($name);
        $is_image = strpos($field_name_lower, 'image') !== false || strpos($field_name_lower, 'avatar') !== false || strpos($field_name_lower, 'logo') !== false;
        $accept = $is_image ? '.jpg,.jpeg,.png,.gif,.webp' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip';
        $help_text = $is_image ? 'JPG, PNG, GIF, WEBP — Max 10MB' : 'PDF, DOC, XLS, PPT, TXT, ZIP — Max 10MB';
        
        echo "<input type='hidden' name='{$name}__removed_fids' value='' class='removed-fids-input'>";
        echo "<input type='file' name='{$name}' " . ($required ? 'required' : '') . " accept='{$accept}'>";
        echo "<small>{$help_text}</small>";
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
          
          // Handle empty values for clearable fields — allow user to blank out a field
          if ($value === '' || $value === null) {
            if (in_array($field_type, ['decimal', 'float', 'integer', 'datetime', 'date', 'timestamp'])) {
              // Numeric/date: set to NULL to clear properly
              $node->set($field_name, NULL);
              continue;
            }
          }

          $node->set($field_name, $value);
        }
      }

      $node->save();

      // Invalidate caches so views, dashboard, and detail pages reflect changes immediately
      Cache::invalidateTags(['node:' . $node->id(), 'node_list']);
      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);

      return new JsonResponse([
        'success' => true,
        'message' => 'Content updated successfully',
        'nid' => $node->id(),
        'title' => $node->getTitle(),
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('ajaxSave error for nid @nid: @msg', [
        '@nid' => $data['nid'] ?? '?',
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => false,
        'message' => 'Error saving: ' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * AJAX Save handler with file upload support (multipart/form-data).
   */
  public function ajaxSaveWithFiles(Request $request) {
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return new JsonResponse(['success' => false, 'message' => 'CSRF token validation failed.'], 403);
    }

    $nid = $request->request->get('nid');
    $type = $request->request->get('type');

    if (!$nid || !$type) {
      return new JsonResponse(['success' => false, 'message' => 'Missing node ID or type'], 400);
    }

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (!$node) {
      return new JsonResponse(['success' => false, 'message' => 'Node not found'], 404);
    }

    $access = $this->checkEditAccess($node);
    if (!$access->isAllowed()) {
      return new JsonResponse(['success' => false, 'message' => 'Access denied'], 403);
    }

    try {
      // Process regular form fields
      foreach ($request->request->all() as $field_name => $value) {
        if (in_array($field_name, ['nid', 'type']) || str_ends_with($field_name, '__removed_fids')) {
          continue;
        }

        if ($field_name === 'title') {
          $node->setTitle($value);
        } elseif ($node->hasField($field_name)) {
          $field_definition = $node->getFieldDefinition($field_name);
          $field_type = $field_definition ? $field_definition->getType() : NULL;

          // Skip file fields — handled below
          if (in_array($field_type, ['file', 'image'])) {
            continue;
          }

          if ($field_type === 'entity_reference' || $field_type === 'entity_reference_revisions') {
            if ($value === '' || $value === NULL) {
              $node->set($field_name, []);
              continue;
            }
          }
          if ($value === '' || $value === NULL) {
            if (in_array($field_type, ['decimal', 'float', 'integer', 'datetime', 'date', 'timestamp'])) {
              $node->set($field_name, NULL);
              continue;
            }
          }
          $node->set($field_name, $value);
        }
      }

      // Process file fields
      foreach ($request->files->all() as $field_name => $uploaded_files) {
        if (!$node->hasField($field_name)) {
          continue;
        }
        $field_definition = $node->getFieldDefinition($field_name);
        $field_type = $field_definition ? $field_definition->getType() : NULL;
        if (!in_array($field_type, ['file', 'image'])) {
          continue;
        }

        // Handle removed files
        $removed_fids_key = $field_name . '__removed_fids';
        $removed_fids_str = $request->request->get($removed_fids_key, '');
        $removed_fids = array_filter(array_map('intval', explode(',', $removed_fids_str)));

        // Get current file references, filter out removed ones
        $current_values = [];
        if (!$node->get($field_name)->isEmpty()) {
          foreach ($node->get($field_name) as $item) {
            $fid = $item->target_id;
            if (!in_array((int) $fid, $removed_fids)) {
              $current_values[] = ['target_id' => $fid, 'display' => 1, 'description' => $item->description ?? ''];
            }
          }
        }

        // Handle new uploads
        if (!is_array($uploaded_files)) {
          $uploaded_files = [$uploaded_files];
        }
        $file_settings = $field_definition->getSettings();
        $upload_dir = $file_settings['uri_scheme'] ?? 'public';
        $file_directory = $file_settings['file_directory'] ?? 'crm/uploads';
        // Replace tokens in directory path
        $file_directory = \Drupal::token()->replace($file_directory);
        $destination = $upload_dir . '://' . $file_directory;
        \Drupal::service('file_system')->prepareDirectory($destination, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY);

        foreach ($uploaded_files as $uploaded_file) {
          if (!$uploaded_file || !$uploaded_file->isValid()) {
            continue;
          }
          $file_entity = \Drupal\file\Entity\File::create([
            'uri' => $uploaded_file->getRealPath(),
            'filename' => $uploaded_file->getClientOriginalName(),
            'status' => 1,
            'uid' => \Drupal::currentUser()->id(),
          ]);
          // Move file to proper destination
          $moved = \Drupal::service('file_system')->move(
            $uploaded_file->getRealPath(),
            $destination . '/' . $uploaded_file->getClientOriginalName(),
            \Drupal\Core\File\FileSystemInterface::EXISTS_RENAME
          );
          if ($moved) {
            $file_entity->setFileUri($moved);
            $file_entity->setPermanent();
            $file_entity->save();
            $current_values[] = ['target_id' => $file_entity->id(), 'display' => 1, 'description' => ''];
          }
        }

        $node->set($field_name, $current_values);
      }

      // If no new files uploaded but files were removed, still update
      foreach ($request->request->all() as $key => $value) {
        if (!str_ends_with($key, '__removed_fids') || empty($value)) {
          continue;
        }
        $field_name = str_replace('__removed_fids', '', $key);
        if (!$node->hasField($field_name) || $request->files->has($field_name)) {
          continue; // Already handled above
        }
        $removed_fids = array_filter(array_map('intval', explode(',', $value)));
        if (empty($removed_fids)) {
          continue;
        }
        $current_values = [];
        if (!$node->get($field_name)->isEmpty()) {
          foreach ($node->get($field_name) as $item) {
            if (!in_array((int) $item->target_id, $removed_fids)) {
              $current_values[] = ['target_id' => $item->target_id, 'display' => 1, 'description' => $item->description ?? ''];
            }
          }
        }
        $node->set($field_name, $current_values);
      }

      $node->save();
      Cache::invalidateTags(['node:' . $node->id(), 'node_list']);
      \Drupal::entityTypeManager()->getStorage('node')->resetCache([$node->id()]);

      return new JsonResponse([
        'success' => true,
        'message' => 'Content updated successfully',
        'nid' => $node->id(),
        'title' => $node->getTitle(),
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('ajaxSaveWithFiles error for nid @nid: @msg', [
        '@nid' => $nid,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => false,
        'message' => 'Error saving: ' . $e->getMessage(),
      ], 500);
    }
  }
  
  /**
   * AJAX Validate handler — validates field values client-side before save.
   *
   * POST /crm/edit/ajax/validate
   * Body: { nid, type, title, field_phone, field_email, field_amount, ... }
   */
  public function ajaxValidate(Request $request) {
    $data  = json_decode($request->getContent(), TRUE) ?? [];
    $errors = [];
    $type  = $data['type'] ?? '';
    $title = trim($data['title'] ?? '');

    // Title is required for all CRM types.
    if ($title === '') {
      $errors[] = 'Title / Name is required.';
    }

    switch ($type) {
      case 'contact':
        $phone = trim($data['field_phone'] ?? '');
        if ($phone === '') {
          $errors[] = 'Phone number is required for contacts.';
        }
        $email = trim($data['field_email'] ?? '');
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $errors[] = 'Email address is not valid.';
        }
        break;

      case 'deal':
        if (isset($data['field_amount']) && trim($data['field_amount']) !== '') {
          if (!is_numeric($data['field_amount']) || floatval($data['field_amount']) < 0) {
            $errors[] = 'Deal amount must be a non-negative number.';
          }
        }
        $has_contact = !empty($data['field_contact']);
        $has_org     = !empty($data['field_organization']);
        if (!$has_contact && !$has_org) {
          $errors[] = 'Deal must reference at least one Contact or Organization.';
        }
        break;

      case 'activity':
        $has_contact = !empty($data['field_contact']);
        $has_deal    = !empty($data['field_deal']);
        if (!$has_contact && !$has_deal) {
          $errors[] = 'Activity must reference at least one Contact or Deal.';
        }
        break;
    }

    return new JsonResponse([
      'valid'  => empty($errors),
      'errors' => $errors,
    ]);
  }

  /**
   * API Debug endpoint - verify API is available.
   */
  public function apiDebug() {
    return new JsonResponse([
      'status' => 'available',
      'message' => 'CRM Inline Edit API v1',
      'user' => $this->currentUser()->getDisplayName(),
      'endpoints' => [
        'PATCH /api/v1/{entity_type}/{entity_id}/{field_name}' => 'Update a single field',
      ],
    ]);
  }

  /**
   * Update a single field on an entity via PATCH.
   *
   * @param string $entity_type
   *   The entity type (e.g., 'node').
   * @param int $entity_id
   *   The entity ID.
   * @param string $field_name
   *   The field name to update.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with success/error status.
   */
  public function updateField($entity_type, $entity_id, $field_name, Request $request) {
    try {
      // Validate CSRF token for state-changing API updates.
      $token = $request->headers->get('X-CSRF-Token');
      if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
        return new JsonResponse(['error' => 'CSRF token validation failed.'], 403);
      }

      // Parse request body
      $data = json_decode($request->getContent(), TRUE);
      
      if (!isset($data['value'])) {
        return new JsonResponse(
          ['error' => 'Missing "value" in request body'],
          400
        );
      }

      $new_value = $data['value'];

      // Load entity
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $entity = $storage->load($entity_id);

      if (!$entity) {
        return new JsonResponse(
          ['error' => 'Entity not found'],
          404
        );
      }

      // Check access
      if (!$entity->access('update', $this->currentUser())) {
        return new JsonResponse(
          ['error' => 'Access denied'],
          403
        );
      }

      // Verify field exists
      if (!$entity->hasField($field_name)) {
        return new JsonResponse(
          ['error' => sprintf('Field "%s" does not exist', $field_name)],
          400
        );
      }

      // Get field definition for type checking
      $field_definition = $entity->getFieldDefinition($field_name);
      $field_type = $field_definition->getType();

      // Convert and validate value based on field type
      $field_value = $new_value;
      
      // Type conversion for common field types
      switch ($field_type) {
        case 'integer':
          $field_value = intval($new_value);
          break;
        case 'decimal':
        case 'float':
          $field_value = floatval($new_value);
          break;
        case 'boolean':
          $field_value = (bool) $new_value;
          break;
        case 'entity_reference':
          // For entity reference, the value should be the target entity ID
          if (!is_numeric($new_value)) {
            return new JsonResponse(
              ['error' => 'Entity reference value must be numeric ID'],
              400
            );
          }
          
          $target_type = $field_definition->getSetting('target_type');
          $target_storage = \Drupal::entityTypeManager()->getStorage($target_type);
          $target_entity = $target_storage->load($new_value);
          
          if (!$target_entity) {
            return new JsonResponse(
              ['error' => sprintf('Referenced entity with ID %d not found', $new_value)],
              400
            );
          }
          
          $field_value = $target_entity;
          break;
        // Default: keep as string
      }

      // Set the field value
      $entity->set($field_name, $field_value);

      // Save the entity directly (skip validation to avoid field_deleted_at query issues)
      // The database will handle constraint violations
      try {
        $entity->save();
      } catch (\Exception $e) {
        return new JsonResponse(
          ['error' => 'Failed to save: ' . $e->getMessage()],
          400
        );
      }

      // Keep UI and backend data in sync immediately.
      Cache::invalidateTags(['node:' . $entity_id, 'node_list']);
      if ($entity_type === 'node') {
        \Drupal::entityTypeManager()->getStorage('node')->resetCache([$entity_id]);
      }

      // Get display value from the field
      $field = $entity->get($field_name);
      $display_value = !$field->isEmpty() ? $field->value : '—';
      
      // For entity reference, show the label
      if ($field_definition->getType() === 'entity_reference' && $field->entity) {
        $display_value = $field->entity->label();
      }

      return new JsonResponse([
        'success' => TRUE,
        'entity_id' => $entity_id,
        'field_name' => $field_name,
        'value' => $new_value,
        'display_value' => (string) $display_value,
        'message' => 'Field updated successfully',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error(
        'Inline field edit error: @message',
        ['@message' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Server error: ' . $e->getMessage()],
        500
      );
    }
  }

  /**
   * Return available pipeline stages.
   */
  public function getStages(Request $request) {
    $stages = [];
    try {
      $stage_terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree('pipeline_stage', 0, NULL, TRUE);

      foreach ($stage_terms as $term) {
        $stages[$term->id()] = $term->getName();
      }
    } catch (\Exception $e) {
      \Drupal::logger('crm_edit')->error('Failed to load stages: @message', ['@message' => $e->getMessage()]);
    }

    return new JsonResponse($stages);
  }
}
