<?php

namespace Drupal\crm_ai_autocomplete\Service;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for managing entity field definitions and schema.
 */
class EntitySchemaService {

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    EntityFieldManagerInterface $field_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->fieldManager = $field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get all field definitions for a bundle.
   *
   * @param string $bundle
   *   Bundle name.
   *
   * @return array
   *   Field definitions.
   */
  public function getFieldDefinitions($bundle) {
    return $this->fieldManager->getFieldDefinitions('node', $bundle) ?? [];
  }

  /**
   * Get auto-completable fields for a bundle.
   *
   * @param string $bundle
   *   Bundle name.
   *
   * @return array
   *   Field names that can be auto-completed.
   */
  public function getAutoCompletableFields($bundle) {
    $auto_completable = [
      'contact' => [
        'title',
        'field_email',
        'field_phone',
        'field_position',
      ],
      'deal' => [
        'title',
      ],
      'organization' => [
        'title',
        'field_website',
        'field_email',
        'field_phone',
        'field_employees_count',
        'field_annual_revenue',
      ],
      'activity' => [
        'title',
        'field_outcome',
      ],
    ];

    return $auto_completable[$bundle] ?? [];
  }

  /**
   * Get field label.
   *
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Field label.
   */
  public function getFieldLabel($bundle, $field_name) {
    $definitions = $this->getFieldDefinitions($bundle);
    return isset($definitions[$field_name]) ? $definitions[$field_name]->getLabel() : ucfirst($field_name);
  }

  /**
   * Get field type.
   *
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Field type.
   */
  public function getFieldType($bundle, $field_name) {
    $definitions = $this->getFieldDefinitions($bundle);
    return isset($definitions[$field_name]) ? $definitions[$field_name]->getType() : 'string';
  }

  /**
   * Get available options for a select field.
   *
   * @param string $bundle
   *   Bundle name.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Available options.
   */
  public function getFieldOptions($bundle, $field_name) {
    $definitions = $this->getFieldDefinitions($bundle);
    $field = $definitions[$field_name] ?? NULL;

    if (!$field || !method_exists($field, 'getSettings')) {
      return [];
    }

    $settings = $field->getSettings();
    return $settings['allowed_values'] ?? [];
  }

}
