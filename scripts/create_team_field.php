<?php

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Create field storage for field_team
$field_storage = FieldStorageConfig::loadByName('user', 'field_team');

if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'field_team',
    'entity_type' => 'user',
    'type' => 'entity_reference',
    'cardinality' => 1,
    'settings' => [
      'target_type' => 'taxonomy_term',
    ],
  ]);
  $field_storage->save();
  echo "Created field storage: field_team\n";
} else {
  echo "Field storage field_team already exists\n";
}

// Create field instance for user entity
$field = FieldConfig::loadByName('user', 'user', 'field_team');

if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'user',
    'label' => 'Team',
    'description' => 'The team this user belongs to',
    'required' => FALSE,
    'settings' => [
      'handler' => 'default:taxonomy_term',
      'handler_settings' => [
        'target_bundles' => [
          'crm_team' => 'crm_team',
        ],
      ],
    ],
  ]);
  $field->save();
  echo "Created field instance: field_team on user\n";
} else {
  echo "Field instance field_team already exists on user\n";
}

// Configure form display
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('user.user.default');

if ($form_display && !$form_display->getComponent('field_team')) {
  $form_display->setComponent('field_team', [
    'type' => 'options_select',
    'weight' => 20,
  ])->save();
  echo "Configured form display for field_team\n";
} else {
  echo "Form display already configured or not found\n";
}

// Configure view display
$view_display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('user.user.default');

if ($view_display && !$view_display->getComponent('field_team')) {
  $view_display->setComponent('field_team', [
    'type' => 'entity_reference_label',
    'label' => 'above',
    'weight' => 20,
  ])->save();
  echo "Configured view display for field_team\n";
} else {
  echo "View display already configured or not found\n";
}

echo "\nField field_team created and configured successfully!\n";
