<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Add Level and Target fields to crm_announcement.
 */

// 1. Level field
if (!FieldStorageConfig::loadByName('node', 'field_announcement_level')) {
  FieldStorageConfig::create([
    'field_name' => 'field_announcement_level',
    'entity_type' => 'node',
    'type' => 'list_string',
    'settings' => [
      'allowed_values' => [
        'info' => 'Information',
        'success' => 'Success',
        'warning' => 'Warning',
        'danger' => 'Critical / Danger',
      ],
    ],
  ])->save();
}

if (!FieldConfig::loadByName('node', 'crm_announcement', 'field_announcement_level')) {
  FieldConfig::create([
    'field_name' => 'field_announcement_level',
    'entity_type' => 'node',
    'bundle' => 'crm_announcement',
    'label' => 'Announcement Level',
    'required' => TRUE,
    'default_value' => [['value' => 'info']],
  ])->save();
  
  // Set form display
  \Drupal::service('entity_display.repository')
    ->getFormDisplay('node', 'crm_announcement', 'default')
    ->setComponent('field_announcement_level', [
      'type' => 'options_select',
      'weight' => 1,
    ])
    ->save();
}

// 2. Target field
if (!FieldStorageConfig::loadByName('node', 'field_announcement_target')) {
  FieldStorageConfig::create([
    'field_name' => 'field_announcement_target',
    'entity_type' => 'node',
    'type' => 'list_string',
    'settings' => [
      'allowed_values' => [
        'all' => 'All Users',
        'admin' => 'Administrators Only',
        'sales' => 'Sales Team',
      ],
    ],
  ])->save();
}

if (!FieldConfig::loadByName('node', 'crm_announcement', 'field_announcement_target')) {
  FieldConfig::create([
    'field_name' => 'field_announcement_target',
    'entity_type' => 'node',
    'bundle' => 'crm_announcement',
    'label' => 'Target Audience',
    'required' => TRUE,
    'default_value' => [['value' => 'all']],
  ])->save();

  // Set form display
  \Drupal::service('entity_display.repository')
    ->getFormDisplay('node', 'crm_announcement', 'default')
    ->setComponent('field_announcement_target', [
      'type' => 'options_select',
      'weight' => 3,
    ])
    ->save();
}

// Adjust weights for Title and Body
\Drupal::service('entity_display.repository')
  ->getFormDisplay('node', 'crm_announcement', 'default')
  ->setComponent('title', ['weight' => 0])
  ->setComponent('body', ['weight' => 2])
  ->save();

echo "Fields field_announcement_level and field_announcement_target added successfully.\n";
