<?php

/**
 * @file
 * Add field_outcome to Activity content type.
 *
 * Run with: ddev drush scr scripts/add_activity_outcome_field.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "Starting field creation...\n\n";

// Create field storage
$storage = FieldStorageConfig::loadByName('node', 'field_outcome');
if (!$storage) {
  $storage = FieldStorageConfig::create([
    'field_name' => 'field_outcome',
    'entity_type' => 'node',
    'type' => 'text_long',
    'cardinality' => 1,
  ]);
  $storage->save();
  echo "✅ Created field storage: field_outcome\n";
} else {
  echo "⚠️  Field storage already exists\n";
}

// Create field for Activity
$field = FieldConfig::loadByName('node', 'activity', 'field_outcome');
if (!$field) {
  $field = FieldConfig::create([
    'field_name' => 'field_outcome',
    'entity_type' => 'node',
    'bundle' => 'activity',
    'label' => 'Kết quả',
    'description' => 'Kết quả hoạt động (VD: Khách nghe máy - Quan tâm, Đã gửi báo giá...)',
    'required' => FALSE,
  ]);
  $field->save();
  echo "✅ Created field for Activity content type\n";
} else {
  echo "⚠️  Field already exists for Activity\n";
}

echo "\n🎉 Done! Field field_outcome added successfully.\n";
