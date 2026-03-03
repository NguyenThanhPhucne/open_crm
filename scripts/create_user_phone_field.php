<?php

/**
 * Create user phone field
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "📱 Creating user phone field...\n";

// Fix phone field - use string type instead of telephone
if (!FieldStorageConfig::loadByName('user', 'field_phone')) {
  FieldStorageConfig::create([
    'field_name' => 'field_phone',
    'entity_type' => 'user',
    'type' => 'string',
    'cardinality' => 1,
  ])->save();
  
  FieldConfig::create([
    'field_name' => 'field_phone',
    'entity_type' => 'user',
    'bundle' => 'user',
    'label' => 'Phone Number',
    'required' => FALSE,
  ])->save();
  
  echo "✅ Created field_phone (string type)\n";
} else {
  echo "ℹ️  field_phone already exists\n";
}
