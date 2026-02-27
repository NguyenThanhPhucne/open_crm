<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Create field_logo storage
$field_storage = FieldStorageConfig::loadByName('node', 'field_logo');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'field_logo',
    'entity_type' => 'node',
    'type' => 'image',
    'cardinality' => 1,
  ]);
  $field_storage->save();
  echo "✅ field_logo storage created\n";
} else {
  echo "⚠️  field_logo storage already exists\n";
}

// Create field_logo instance for Organization
$field = FieldConfig::loadByName('node', 'organization', 'field_logo');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'organization',
    'label' => 'Logo',
    'required' => FALSE,
    'settings' => [
      'file_directory' => 'logos',
      'file_extensions' => 'png jpg jpeg',
      'max_filesize' => '2 MB',
      'max_resolution' => '800x800',
      'alt_field' => TRUE,
      'alt_field_required' => FALSE,
      'title_field' => FALSE,
    ],
  ]);
  $field->save();
  echo "✅ field_logo added to Organization\n";
} else {
  echo "⚠️  field_logo already exists on Organization\n";
}

// Create field_status storage
$field_storage = FieldStorageConfig::loadByName('node', 'field_status');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'field_status',
    'entity_type' => 'node',
    'type' => 'list_string',
    'cardinality' => 1,
    'settings' => [
      'allowed_values' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
      ],
    ],
  ]);
  $field_storage->save();
  echo "✅ field_status storage created\n";
} else {
  echo "⚠️  field_status storage already exists\n";
}

// Create field_status instance for Organization
$field = FieldConfig::loadByName('node', 'organization', 'field_status');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'organization',
    'label' => 'Status',
    'required' => TRUE,
    'default_value' => [['value' => 'active']],
  ]);
  $field->save();
  echo "✅ field_status added to Organization\n";
} else {
  echo "⚠️  field_status already exists on Organization\n";
}

// Configure form displays
echo "\nConfiguring form displays...\n";

$entity_form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.organization.default');

if ($entity_form_display) {
  // field_logo
  $entity_form_display->setComponent('field_logo', [
    'type' => 'image_image',
    'weight' => 1,
    'settings' => [
      'preview_image_style' => 'thumbnail',
      'progress_indicator' => 'throbber',
    ],
  ]);
  
  // field_status
  $entity_form_display->setComponent('field_status', [
    'type' => 'options_select',
    'weight' => 10,
  ]);
  
  $entity_form_display->save();
  echo "✅ Organization form display updated\n";
}

// Configure view displays
$entity_view_display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('node.organization.default');

if ($entity_view_display) {
  // field_logo
  $entity_view_display->setComponent('field_logo', [
    'type' => 'image',
    'weight' => 0,
    'label' => 'hidden',
    'settings' => [
      'image_style' => 'medium',
      'image_link' => '',
    ],
  ]);
  
  // field_status
  $entity_view_display->setComponent('field_status', [
    'type' => 'list_default',
    'weight' => 10,
    'label' => 'inline',
  ]);
  
  $entity_view_display->save();
  echo "✅ Organization view display updated\n";
}

// Update existing organizations
echo "\nUpdating existing organizations...\n";
$nids = \Drupal::entityQuery('node')
  ->condition('type', 'organization')
  ->accessCheck(FALSE)
  ->execute();

$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadMultiple($nids);

$count = 0;
foreach ($nodes as $node) {
  if ($node->hasField('field_status') && $node->get('field_status')->isEmpty()) {
    $node->set('field_status', 'active');
    $node->save();
    $count++;
  }
}

echo "✅ Updated {$count} existing organizations with Active status\n";

echo "\n✅ All fields created successfully!\n";
