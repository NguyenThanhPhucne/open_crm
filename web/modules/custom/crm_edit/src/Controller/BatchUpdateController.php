<?php

namespace Drupal\crm_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\NodeInterface;

/**
 * API Controller for batch field updates with conflict detection.
 */
class BatchUpdateController extends ControllerBase {

  /**
   * Update multiple fields in batch with conflict detection.
   *
   * POST /api/v1/batch-update
   * {
   *   "updates": [
   *     {"entity_type": "node", "entity_id": 1, "field": "title", "value": "New Title"}
   *   ]
   * }
   */
  public function batchUpdate(Request $request) {
    try {
      // Parse request
      $data = json_decode($request->getContent(), TRUE);

      if (!isset($data['updates']) || !is_array($data['updates'])) {
        return new JsonResponse(
          ['error' => 'Invalid request format'],
          400
        );
      }

      $updates = $data['updates'];
      $results = [];
      $errors = [];

      foreach ($updates as $update) {
        $entity_type = $update['entity_type'] ?? 'node';
        $entity_id = $update['entity_id'] ?? NULL;
        $field_name = $update['field'] ?? NULL;
        $new_value = $update['value'] ?? NULL;

        if (!$entity_type || !$entity_id || !$field_name) {
          $errors[] = [
            'update' => $update,
            'error' => 'Missing required fields: entity_type, entity_id, field',
          ];
          continue;
        }

        // Load entity
        $storage = $this->entityTypeManager()->getStorage($entity_type);
        $entity = $storage->load($entity_id);

        if (!$entity) {
          $errors[] = [
            'entity_id' => $entity_id,
            'error' => 'Entity not found',
          ];
          continue;
        }

        // Check access
        if (!$entity->access('update', $this->currentUser())) {
          $errors[] = [
            'entity_id' => $entity_id,
            'error' => 'Access denied',
          ];
          continue;
        }

        // Verify field exists
        if (!$entity->hasField($field_name)) {
          $errors[] = [
            'field_name' => $field_name,
            'error' => 'Field does not exist',
          ];
          continue;
        }

        /**
         * CONFLICT DETECTION:
         * Check if someone else modified this field since client loaded data
         */
        if (isset($update['expected_revision_id'])) {
          // Check revision (optimistic locking)
          if ($entity->getRevisionId() != $update['expected_revision_id']) {
            $errors[] = [
              'entity_id' => $entity_id,
              'field_name' => $field_name,
              'error' => 'Conflict: Entity was modified by another user',
              'current_revision_id' => $entity->getRevisionId(),
            ];
            continue;
          }
        }

        try {
          // Get field definition for type conversion
          $field_definition = $entity->getFieldDefinition($field_name);
          $field_type = $field_definition->getType();

          // Convert value based on field type
          $field_value = $this->convertFieldValue($new_value, $field_type);

          // Set and save
          $entity->set($field_name, $field_value);
          $entity->save();

          // Get display value for UI
          $field = $entity->get($field_name);
          $display_value = !$field->isEmpty() 
            ? ($field_definition->getType() === 'entity_reference' && $field->entity
              ? $field->entity->label()
              : $field->value)
            : '—';

          $results[] = [
            'entity_id' => $entity_id,
            'field_name' => $field_name,
            'success' => TRUE,
            'value' => $new_value,
            'display_value' => (string) $display_value,
            'revision_id' => $entity->getRevisionId(),
          ];
        } catch (\Exception $e) {
          $errors[] = [
            'entity_id' => $entity_id,
            'field_name' => $field_name,
            'error' => 'Save error: ' . $e->getMessage(),
          ];
        }
      }

      return new JsonResponse([
        'success' => count($errors) === 0,
        'results' => $results,
        'errors' => $errors,
        'total' => count($updates),
        'updated' => count($results),
        'failed' => count($errors),
      ]);

    } catch (\Exception $e) {
      $this->getLogger('crm_edit')->error(
        'Batch update error: @error',
        ['@error' => $e->getMessage()]
      );

      return new JsonResponse(
        ['error' => 'Server error'],
        500
      );
    }
  }

  /**
   * Get entity data with current values and revision info.
   *
   * GET /api/v1/entity/{entity_type}/{entity_id}
   */
  public function getEntity($entity_type, $entity_id) {
    try {
      if ($entity_type !== 'node') {
        return new JsonResponse(['error' => 'Only node entities supported'], 400);
      }

      $storage = $this->entityTypeManager()->getStorage($entity_type);
      $entity = $storage->load($entity_id);

      if (!$entity) {
        return new JsonResponse(['error' => 'Entity not found'], 404);
      }

      if (!$entity->access('view', $this->currentUser())) {
        return new JsonResponse(['error' => 'Access denied'], 403);
      }

      // Get field values
      $fields = [];
      $entity_field_manager = $this->container->get('entity_field.manager');
      $field_definitions = $entity_field_manager->getFieldDefinitions('node', $entity->bundle());

      foreach ($field_definitions as $field_name => $field_def) {
        if (!$entity->hasField($field_name)) {
          continue;
        }

        $field = $entity->get($field_name);
        $value = NULL;

        if (!$field->isEmpty()) {
          if ($field_def->getType() === 'entity_reference') {
            $value = $field->target_id;
          } else {
            $value = $field->value;
          }
        }

        $fields[$field_name] = [
          'value' => $value,
          'type' => $field_def->getType(),
        ];
      }

      return new JsonResponse([
        'entity_id' => $entity->id(),
        'entity_type' => $entity_type,
        'bundle' => $entity->bundle(),
        'title' => $entity->getTitle(),
        'revision_id' => $entity->getRevisionId(),
        'changed' => $entity->getChangedTime(),
        'fields' => $fields,
      ]);

    } catch (\Exception $e) {
      return new JsonResponse(['error' => 'Server error'], 500);
    }
  }

  /**
   * Convert field value based on type.
   */
  protected function convertFieldValue($value, $field_type) {
    switch ($field_type) {
      case 'integer':
        return intval($value);
      case 'decimal':
      case 'float':
        return floatval($value);
      case 'boolean':
        return (bool) $value;
      case 'entity_reference':
        if (is_numeric($value)) {
          return $this->entityTypeManager()
            ->getStorage('node')
            ->load($value);
        }
        return NULL;
      default:
        return $value;
    }
  }

}
