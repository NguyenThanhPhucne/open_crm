<?php

namespace Drupal\crm_ai\Service;

/**
 * Service for validating and formatting field values.
 */
class FieldValidatorService {

  /**
   * Validate and format a field value.
   *
   * @param string $field_name
   *   Field name.
   * @param mixed $value
   *   Value to validate.
   * @param string $field_type
   *   Field type.
   * @param string $bundle
   *   Entity bundle.
   *
   * @return mixed
   *   Validated value or NULL if invalid.
   */
  public function validateFieldValue($field_name, $value, $field_type, $bundle) {
    if (empty($value)) {
      return NULL;
    }

    switch ($field_type) {
      case 'string':
      case 'text':
      case 'text_long':
      case 'text_with_summary':
        return $this->validateStringField($value);

      case 'integer':
      case 'decimal':
      case 'float':
        return $this->validateNumericField($value, $field_type);

      case 'list_string':
      case 'list_integer':
        return $this->validateListField($value);

      case 'entity_reference':
        return $this->validateEntityReference($value, $bundle, $field_name);

      case 'boolean':
        return $this->validateBolean($value);

      case 'timestamp':
        return $this->validateTimestamp($value);

      default:
        return $value;
    }
  }

  /**
   * Validate string field.
   *
   * @param mixed $value
   *   Value to validate.
   *
   * @return string|null
   *   Validated string or NULL.
   */
  protected function validateStringField($value) {
    $value = trim($value);
    return !empty($value) ? $value : NULL;
  }

  /**
   * Validate numeric field.
   *
   * @param mixed $value
   *   Value to validate.
   * @param string $type
   *   Field type.
   *
   * @return int|float|null
   *   Validated number or NULL.
   */
  protected function validateNumericField($value, $type) {
    if ($type === 'integer') {
      $validated = (int) $value;
    } else {
      $validated = (float) $value;
    }
    return $validated !== 0 ? $validated : NULL;
  }

  /**
   * Validate list field.
   *
   * @param mixed $value
   *   Value to validate.
   *
   * @return mixed
   *   Validated value or NULL.
   */
  protected function validateListField($value) {
    if (is_array($value)) {
      return !empty($value) ? $value : NULL;
    }
    return !empty($value) ? [$value] : NULL;
  }

  /**
   * Validate entity reference.
   *
   * @param mixed $value
   *   Value to validate.
   * @param string $bundle
   *   Entity bundle.
   * @param string $field_name
   *   Field name.
   *
   * @return int|null
   *   Entity ID or NULL.
   */
  protected function validateEntityReference($value, $bundle, $field_name) {
    // Simplified - just return numeric values.
    if (is_numeric($value)) {
      return (int) $value;
    }
    return NULL;
  }

  /**
   * Validate boolean field.
   *
   * @param mixed $value
   *   Value to validate.
   *
   * @return int|null
   *   1 for TRUE, 0 for FALSE, NULL if invalid.
   */
  protected function validateBolean($value) {
    if (is_bool($value)) {
      return $value ? 1 : 0;
    }
    if (in_array($value, ['true', '1', 1, TRUE], TRUE)) {
      return 1;
    }
    if (in_array($value, ['false', '0', 0, FALSE], TRUE)) {
      return 0;
    }
    return NULL;
  }

  /**
   * Validate timestamp field.
   *
   * @param mixed $value
   *   Value to validate.
   *
   * @return int|null
   *   Unix timestamp or NULL.
   */
  protected function validateTimestamp($value) {
    if (is_numeric($value)) {
      return (int) $value;
    }
    $timestamp = strtotime($value);
    return $timestamp !== FALSE ? $timestamp : NULL;
  }

}
