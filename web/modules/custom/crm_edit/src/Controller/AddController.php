<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Markup;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\Core\Access\CsrfRequestHeaderAccessCheck;

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

    // Contact, deal and organization have dedicated quickadd form pages.
    // Redirect to them so we avoid showing a duplicate Drupal page title
    // alongside the inline-edit modal header.
    if (in_array($type, ['contact', 'deal', 'organization'])) {
      return new RedirectResponse('/crm/quickadd/' . $type);
    }

    $type_label = ucfirst($type);
    
    return [
      '#markup' => Markup::create('
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
      '),
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
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token, CsrfRequestHeaderAccessCheck::TOKEN_KEY)) {
      return new JsonResponse(['success' => false, 'message' => 'CSRF token validation failed.'], 403);
    }

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

      // Invalidate list caches so views/dashboard reflect new entity immediately
      Cache::invalidateTags(['node_list']);

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
    // Use Drupal's standard permission check.
    return $account->hasPermission("create {$type} content");
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
    
    // Map type to icon and accent color
    $type_icon_map = [
      'contact' => 'user-plus',
      'deal'    => 'trending-up',
      'organization' => 'building-2',
      'activity' => 'calendar-plus',
    ];
    $type_icon = $type_icon_map[$type] ?? 'plus-circle';

    // Contextual placeholder text for each known field
    $placeholder_map = [
      // Title — per type (resolved below)
      'title' => [
        'contact'      => 'e.g. John Smith',
        'deal'         => 'e.g. Enterprise License Q1 2026',
        'organization' => 'e.g. Acme Corporation',
        'activity'     => 'e.g. Follow-up call with John',
      ],
      // Contact / shared
      'field_email'          => 'e.g. john@company.com',
      'field_phone'          => 'e.g. +1 (555) 000-0000',
      'field_mobile'         => 'e.g. +1 (555) 999-1234',
      'field_job_title'      => 'e.g. Sales Manager',
      'field_position'       => 'e.g. CEO, Developer, Consultant…',
      'field_linkedin'       => 'e.g. https://linkedin.com/in/username',
      'field_website'        => 'e.g. https://www.company.com',
      'field_address'        => 'e.g. 123 Main St, New York, NY 10001',
      'field_city'           => 'e.g. Ho Chi Minh City',
      'field_country'        => 'e.g. Vietnam',
      'field_notes'          => 'Add any additional notes…',
      'field_description'    => 'Describe in detail…',
      'field_tags'           => 'e.g. VIP, enterprise, hot-lead',
      // Deal-specific
      'field_value'          => 'e.g. 50000',
      'field_deal_value'     => 'e.g. 50000',
      'field_amount'         => 'e.g. 50000',
      'field_probability'    => 'e.g. 75',
      'field_expected_revenue' => 'e.g. 1000000',
      // Organization-specific
      'field_employees'      => 'e.g. 250',
      'field_employee_count' => 'e.g. 250',
      'field_revenue'        => 'e.g. 5000000',
    ];

    ob_start();
    ?>
    <div class="crm-modal-container add-modal add-modal-<?= $type ?>">
      <div class="crm-modal-header add-modal-header">
        <div class="add-modal-header-icon">
          <i data-lucide="<?= $type_icon ?>"></i>
        </div>
        <div class="add-modal-header-text">
          <h2>Create New <?= $type_label ?></h2>
          <p class="add-modal-subtitle">Fill in the details below to add a new <?= strtolower($type_label) ?> to your CRM</p>
        </div>
        <button class="crm-modal-close" type="button" aria-label="Close">
          <i data-lucide="x"></i>
        </button>
      </div>
      
      <form class="crm-modal-form add-form" data-type="<?= $type ?>">
        <div class="crm-modal-body">
          <?php foreach ($fields as $field): ?>
            <div class="form-field <?= $field['required'] ? 'required-field' : '' ?>">
              <label <?= $field['required'] ? 'class="required"' : '' ?>>
                <?= htmlspecialchars($field['label'] ?? '') ?>
                <?= $field['required'] ? '<span class="required-mark"></span>' : '' ?>
              </label>
              
              <?php
                $field_name = $field['name'];
                $field_type = $field['type'];
                $field_settings = $field['settings'];
                $field_label_str = (string)($field['label'] ?? $field_name);
                $value = htmlspecialchars($field['value'] ?? '');
                $req_attr = $field['required'] ? 'required' : '';

                // Resolve contextual placeholder
                if ($field_name === 'title') {
                  $ph = $placeholder_map['title'][$type] ?? 'Enter ' . strtolower($type_label) . ' name';
                } elseif (isset($placeholder_map[$field_name])) {
                  $ph = $placeholder_map[$field_name];
                } else {
                  $ph = 'Enter ' . strtolower($field_label_str) . '…';
                }
                $ph = htmlspecialchars($ph);
                $select_prompt = htmlspecialchars('Select ' . strtolower($field_label_str) . '…');

                // Render appropriate input based on field type
                switch ($field_type) {
                  case 'string':
                  case 'email':
                  case 'telephone':
                    $input_type = $field_type === 'email' ? 'email' : ($field_type === 'telephone' ? 'tel' : 'text');
                    echo '<input type="' . $input_type . '" name="' . $field_name . '" value="' . $value . '" placeholder="' . $ph . '" ' . $req_attr . ' />';
                    break;

                  case 'text':
                  case 'text_long':
                  case 'string_long':
                    echo '<textarea name="' . $field_name . '" rows="4" placeholder="' . $ph . '" ' . $req_attr . '>' . $value . '</textarea>';
                    break;

                  case 'text_with_summary':
                    echo '<textarea name="' . $field_name . '" rows="6" placeholder="' . $ph . '" ' . $req_attr . '>' . $value . '</textarea>';
                    break;

                  case 'integer':
                  case 'decimal':
                  case 'float':
                    echo '<input type="number" name="' . $field_name . '" value="' . $value . '" placeholder="' . $ph . '" step="' . ($field_type === 'integer' ? '1' : '0.01') . '" ' . $req_attr . ' />';
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
                    echo '<input type="datetime-local" name="' . $field_name . '" value="' . $value . '" ' . $req_attr . ' />';
                    break;

                  case 'list_string':
                  case 'list_integer':
                    echo '<select name="' . $field_name . '" ' . $req_attr . '>';
                    echo '<option value="" disabled selected hidden>' . $select_prompt . '</option>';
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
                    $ref_prompt = $target_type === 'user'
                      ? 'Choose a user…'
                      : $select_prompt;
                    echo '<select name="' . $field_name . '" ' . $req_attr . '>';
                    echo '<option value="" disabled selected hidden>' . htmlspecialchars($ref_prompt) . '</option>';

                    if ($target_type === 'user') {
                      $uids = \Drupal::entityTypeManager()->getStorage('user')->getQuery()
                        ->condition('status', 1)
                        ->sort('name', 'ASC')
                        ->accessCheck(FALSE)
                        ->execute();
                      $users = !empty($uids) ? \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids) : [];
                      foreach ($users as $user) {
                        if ($user->id() > 0) {
                          $selected = ($value == $user->id()) ? 'selected' : '';
                          echo '<option value="' . $user->id() . '" ' . $selected . '>' . htmlspecialchars($user->getDisplayName() ?? '') . '</option>';
                        }
                      }
                    } elseif ($target_type === 'taxonomy_term') {
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
                      $target_bundles = $field_settings['target_bundles'] ?? [];
                      if (!empty($target_bundles)) {
                        // Use entity query with sort + limit to avoid loading unbounded data
                        $nq = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
                          ->condition('type', array_values($target_bundles), 'IN')
                          ->condition('status', 1)
                          ->sort('title', 'ASC')
                          ->range(0, 200)
                          ->accessCheck(FALSE);
                        $nids = $nq->execute();
                        if (!empty($nids)) {
                          $ref_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
                          foreach ($ref_nodes as $ref_node) {
                            $selected = ($value == $ref_node->id()) ? 'selected' : '';
                            echo '<option value="' . $ref_node->id() . '" ' . $selected . '>' . htmlspecialchars($ref_node->getTitle() ?? '') . '</option>';
                          }
                        }
                      }
                    }

                    echo '</select>';
                    break;

                  default:
                    echo '<input type="text" name="' . $field_name . '" value="' . $value . '" placeholder="' . $ph . '" ' . $req_attr . ' />';
                    break;
                }
              ?>
              
              <?php if (!empty($field['description'])): ?>
                <div class="field-description"><?= $field['description'] ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        
        <div class="crm-modal-footer add-modal-footer">
          <span class="required-hint"><span class="required-mark"></span> Required fields</span>
          <div class="add-modal-footer-actions">
            <div class="save-status"></div>
            <button type="button" class="btn-cancel">
              <i data-lucide="x"></i>
              <span>Cancel</span>
            </button>
            <button type="submit" class="btn-save save-btn">
              <i data-lucide="<?= $type_icon ?>"></i>
              <span>Create <?= $type_label ?></span>
            </button>
          </div>
        </div>
      </form>
    </div>
    <?php
    return ob_get_clean();
  }
}
