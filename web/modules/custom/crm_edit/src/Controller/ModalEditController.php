<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;

/**
 * Modal Edit Controller for inline editing in views.
 */
class ModalEditController extends ControllerBase {

  /**
   * Get edit form HTML for modal.
   */
  public function getEditForm(Request $request) {
    $nid = $request->query->get('nid');
    $type = $request->query->get('type');
    
    if (!$nid || !$type) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Missing node ID or type',
      ], 400);
    }
    
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    
    if (!$node) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Node not found',
      ], 404);
    }
    
    // Check access
    $access = $this->checkEditAccess($node);
    if (!$access) {
      return new JsonResponse([
        'success' => false,
        'message' => 'Access denied',
      ], 403);
    }
    
    // Get field definitions
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions('node', $type);
    
    $editable_fields = [];
    foreach ($fields as $field_name => $field_def) {
      if ($field_name === 'title' || strpos($field_name, 'field_') === 0) {
        if (!in_array($field_name, ['created', 'changed', 'uid'])) {
          $field_type = $field_def->getType();
          $field_label = $field_def->getLabel();
          $is_required = $field_def->isRequired();
          
          $current_value = '';
          $field_settings = [];
          
          if ($node->hasField($field_name)) {
            $field_item = $node->get($field_name);
            $current_value = $this->formatFieldValue($field_item, $field_type);
            
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
          } elseif ($field_name === 'title') {
            $current_value = $node->getTitle();
          }
          
          $editable_fields[] = [
            'name' => $field_name,
            'label' => $field_label,
            'type' => $field_type,
            'required' => $is_required,
            'value' => $current_value,
            'settings' => $field_settings,
            'description' => $field_def->getDescription(),
          ];
        }
      }
    }
    
    return new JsonResponse([
      'success' => true,
      'html' => $this->generateModalHTML($node, $editable_fields),
      'nid' => $nid,
      'type' => $type,
      'title' => $node->getTitle(),
    ]);
  }
  
  /**
   * Check edit access.
   */
  protected function checkEditAccess(NodeInterface $node) {
    $account = $this->currentUser();

    // Use Drupal's standard node access system.
    if ($node->access('update', $account)) {
      return TRUE;
    }

    return FALSE;
  }
  
  /**
   * Get owner field.
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
   * Format field value.
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
        $file_entities = [];
        foreach ($field_item as $item) {
          $file = $item->entity;
          if ($file) {
            $file_entities[] = [
              'fid' => $file->id(),
              'name' => $file->getFilename(),
              'size' => $file->getSize(),
              'url' => \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()),
            ];
          }
        }
        return $file_entities;
      default:
        return $value;
    }
  }
  
  /**
   * Generate modal HTML.
   */
  protected function generateModalHTML($node, $fields) {
    $nid = $node->id();
    $title = htmlspecialchars($node->getTitle() ?? '');
    $type = $node->bundle();
    
    ob_start();
    ?>
    <div class="crm-modal-container">
      <div class="crm-modal-header">
        <h2>
          <i data-lucide="edit-2"></i>
          Edit <?= ucfirst($type) ?>: <?= $title ?>
        </h2>
        <button class="crm-modal-close" type="button">
          <i data-lucide="x"></i>
        </button>
      </div>
      
      <form class="crm-modal-form" data-nid="<?= $nid ?>" data-type="<?= $type ?>">
        <div class="crm-modal-body">
          <?php foreach ($fields as $field): ?>
            <div class="form-field">
              <label <?= $field['required'] ? 'class="required"' : '' ?>>
                <?= htmlspecialchars($field['label'] ?? '') ?>
                <?= $field['required'] ? '<span class="required-mark">*</span>' : '' ?>
              </label>
              <?php
                $field_name = $field['name'];
                $field_type = $field['type'];
                $field_value = $field['value'] ?? '';
                $field_settings = $field['settings'] ?? [];
                $required = $field['required'] ? 'required' : '';
                $description = $field['description'] ?? '';
                
                // Render field widget based on type
                switch ($field_type) {
                  case 'entity_reference':
                    $this->renderEntityReferenceField($field_name, $field_value, $field_settings, $required);
                    break;
                    
                  case 'list_string':
                  case 'list_integer':
                    $this->renderListField($field_name, $field_value, $field_settings, $required);
                    break;
                    
                  case 'boolean':
                    $this->renderBooleanField($field_name, $field_value, $field_settings, $required);
                    break;
                    
                  case 'file':
                  case 'image':
                    $this->renderFileField($field_name, $field_value, $field_settings, $required);
                    break;
                    
                  case 'email':
                    echo "<input type='email' name='$field_name' value='" . htmlspecialchars($field_value ?? '') . "' $required class='form-control'>";
                    break;
                    
                  case 'decimal':
                  case 'float':
                    echo "<input type='number' step='0.01' name='$field_name' value='" . htmlspecialchars($field_value ?? '') . "' $required class='form-control'>";
                    break;
                    
                  case 'integer':
                    echo "<input type='number' name='$field_name' value='" . htmlspecialchars($field_value ?? '') . "' $required class='form-control'>";
                    break;
                    
                  case 'datetime':
                    $formatted_value = $this->formatDatetimeForInput($field_value);
                    echo "<input type='datetime-local' name='$field_name' value='$formatted_value' $required class='form-control'>";
                    break;
                    
                  case 'text_long':
                  case 'text_with_summary':
                    echo "<textarea name='$field_name' $required class='form-control' rows='4'>" . htmlspecialchars($field_value ?? '') . "</textarea>";
                    break;
                    
                  case 'telephone':
                    echo "<input type='tel' name='$field_name' value='" . htmlspecialchars($field_value ?? '') . "' $required class='form-control'>";
                    break;
                    
                  default:
                    echo "<input type='text' name='$field_name' value='" . htmlspecialchars($field_value ?? '') . "' $required class='form-control'>";
                    break;
                }
                
                // Show field description
                if ($description) {
                  echo "<small class='field-description'>" . htmlspecialchars($description ?? '') . "</small>";
                }
              ?>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="crm-modal-footer">
          <button type="button" class="btn-cancel">
            <i data-lucide="x"></i>
            <span>Cancel</span>
          </button>
          <button type="submit" class="btn-save">
            <i data-lucide="save"></i>
            <span>Save Changes</span>
          </button>
        </div>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
  
  /**
   * Render entity reference field as select dropdown.
   */
  protected function renderEntityReferenceField($field_name, $current_value, $settings, $required) {
    echo "<select name='$field_name' $required class='form-control'>";
    echo "<option value=''>- Select -</option>";
    
    $target_type = $settings['target_type'] ?? null;
    
    if ($target_type) {
      $storage = \Drupal::entityTypeManager()->getStorage($target_type);
      $query = $storage->getQuery()->accessCheck(TRUE);
      
      // Filter by target bundles if specified
      if (!empty($settings['target_bundles'])) {
        $bundle_key = $storage->getEntityType()->getKey('bundle');
        if ($bundle_key) {
          $query->condition($bundle_key, $settings['target_bundles'], 'IN');
        }
      }
      
      $label_key = $storage->getEntityType()->getKey('label');
      if ($label_key) {
        $query->sort($label_key, 'ASC');
      }
      $query->range(0, 200);
      $entity_ids = $query->execute();
      
      if ($entity_ids) {
        $entities = $storage->loadMultiple($entity_ids);
        foreach ($entities as $entity) {
          $selected = ($entity->id() == $current_value) ? 'selected' : '';
          $label = $entity->label() ?? $entity->id() ?? '';
          echo "<option value='{$entity->id()}' $selected>" . htmlspecialchars($label) . "</option>";
        }
      }
    }
    
    echo "</select>";
  }
  
  /**
   * Render list field as select dropdown.
   */
  protected function renderListField($field_name, $current_value, $settings, $required) {
    $allowed_values = $settings['allowed_values'] ?? [];
    
    echo "<select name='$field_name' $required class='form-control'>";
    echo "<option value=''>- Select -</option>";
    
    foreach ($allowed_values as $key => $label) {
      $selected = ($key == $current_value) ? 'selected' : '';
      echo "<option value='" . htmlspecialchars((string)$key) . "' $selected>" . htmlspecialchars($label ?? '') . "</option>";
    }
    
    echo "</select>";
  }
  
  /**
   * Render boolean field as checkbox.
   */
  protected function renderBooleanField($field_name, $current_value, $settings, $required) {
    $checked = $current_value ? 'checked' : '';
    $on_label = $settings['on_label'] ?? 'Yes';
    
    echo "<div class='checkbox-wrapper'>";
    echo "<label class='checkbox-label'>";
    echo "<input type='hidden' name='$field_name' value='0'>";
    echo "<input type='checkbox' name='$field_name' value='1' $checked class='form-checkbox'>";
    echo "<span class='checkbox-text'>" . htmlspecialchars($on_label) . "</span>";
    echo "</label>";
    echo "</div>";
  }
  
  /**
   * Render file upload field.
   */
  protected function renderFileField($field_name, $current_files, $settings, $required) {
    $is_image = strpos(strtolower($field_name), 'image') !== false || strpos(strtolower($field_name), 'avatar') !== false;
    $accept = $is_image ? '.jpg,.jpeg,.png,.gif,.webp' : '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip';
    $help_text = $is_image ? 'JPG, PNG, GIF, WEBP — Max 10MB' : 'PDF, DOC, XLS, PPT, TXT, ZIP — Max 10MB';

    // Show current files
    if (!empty($current_files) && is_array($current_files)) {
      echo "<div class='file-current-files' id='current-files-{$field_name}'>";
      foreach ($current_files as $file) {
        $fid = $file['fid'];
        $fname = htmlspecialchars($file['name']);
        $fsize = $this->formatFileSize($file['size'] ?? 0);
        $url = htmlspecialchars($file['url']);
        
        echo "<div class='file-item' data-fid='{$fid}' id='file-item-{$fid}'>";
        if ($is_image) {
          echo "<div class='file-image-preview' style='background-image: url(\"{$url}\"); width: 40px; height: 40px; background-size: cover; background-position: center; border-radius: 4px; border: 1px solid #e2e8f0; flex-shrink: 0;'></div>";
        } else {
          echo "<span class='file-icon'><svg viewBox='0 0 24 24' width='16' height='16' fill='none' stroke='currentColor' stroke-width='2'><path d='M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z'/><polyline points='14 2 14 8 20 8'/></svg></span>";
        }
        
        echo "<div class='file-info-col' style='display:flex; flex-direction:column; flex:1; min-width:0; margin-left:12px;'>";
        echo "<span class='file-name' style='white-space:nowrap; overflow:hidden; text-overflow:ellipsis;'>{$fname}</span>";
        echo "<span class='file-size' style='font-size:12px; color:#64748b;'>{$fsize}</span>";
        echo "</div>";
        
        echo "<div class='file-actions' style='display:flex; gap:8px; margin-left:12px;'>";
        echo "<a href='{$url}' target='_blank' class='file-download btn-icon' title='Download' style='color:#3b82f6;'><svg viewBox='0 0 24 24' width='16' height='16' fill='none' stroke='currentColor' stroke-width='2'><path d='M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4'/><polyline points='7 10 12 15 17 10'/><line x1='12' y1='15' x2='12' y2='3'/></svg></a>";
        echo "<button type='button' class='file-remove btn-icon' onclick='CRMInlineEdit.removeFileItem({$fid}, \"{$field_name}\")' title='Remove' style='color:#ef4444; background:none; border:none; cursor:pointer;'><svg viewBox='0 0 24 24' width='16' height='16' fill='none' stroke='currentColor' stroke-width='2'><line x1='18' y1='6' x2='6' y2='18'/><line x1='6' y1='6' x2='18' y2='18'/></svg></button>";
        echo "</div>";
        
        echo "</div>";
      }
      echo "</div>";
    }
    // Hidden field to track removed file IDs
    echo "<input type='hidden' name='{$field_name}__removed_fids' value='' class='removed-fids-input'>";
    // File upload input
    echo "<div class='file-upload-zone' style='margin-top:10px;'>";
    echo "<input type='file' name='{$field_name}' class='form-control file-input' {$required} accept='{$accept}'>";
    echo "<small class='file-help' style='display:block; margin-top:6px; color:#64748b; font-size:13px;'>{$help_text}</small>";
    echo "</div>";
  }

  /**
   * Format file size for display.
   */
  protected function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
      return round($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
      return round($bytes / 1024, 0) . ' KB';
    }
    return $bytes . ' B';
  }

  /**
   * Format datetime value for input field.
   */
  protected function formatDatetimeForInput($value) {
    if (empty($value)) {
      return '';
    }
    
    try {
      // Handle different datetime formats
      if (is_numeric($value)) {
        $date = new \DateTime('@' . $value);
      } else {
        $date = new \DateTime($value);
      }
      return $date->format('Y-m-d\TH:i');
    } catch (\Exception $e) {
      return '';
    }
  }
}
