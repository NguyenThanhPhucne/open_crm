<?php

/**
 * @file
 * Setup Customer Portal - Add contact reference field to User entity.
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "🔧 Setting up Customer Portal...\n\n";

// 1. Create field_contact_profile field storage
echo "1. Creating field_contact_profile field storage...\n";
$field_storage = FieldStorageConfig::loadByName('user', 'field_contact_profile');
if (!$field_storage) {
  $field_storage = FieldStorageConfig::create([
    'field_name' => 'field_contact_profile',
    'entity_type' => 'user',
    'type' => 'entity_reference',
    'settings' => [
      'target_type' => 'node',
    ],
    'cardinality' => 1,
  ]);
  $field_storage->save();
  echo "   ✅ Field storage created\n";
} else {
  echo "   ⚠️  Field storage already exists\n";
}

// 2. Create field on User entity
echo "2. Creating field instance on User entity...\n";
$field = FieldConfig::loadByName('user', 'user', 'field_contact_profile');
if (!$field) {
  $field = FieldConfig::create([
    'field_storage' => $field_storage,
    'bundle' => 'user',
    'label' => 'Contact Profile',
    'description' => 'Link to Contact node for Customer portal access',
    'settings' => [
      'handler' => 'default:node',
      'handler_settings' => [
        'target_bundles' => ['contact'],
      ],
    ],
    'required' => FALSE,
  ]);
  $field->save();
  echo "   ✅ Field created on User entity\n";
} else {
  echo "   ⚠️  Field already exists\n";
}

// 3. Configure form display
echo "3. Configuring form display...\n";
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('user.user.default');

if ($form_display && !$form_display->getComponent('field_contact_profile')) {
  $form_display->setComponent('field_contact_profile', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 20,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => 'Select contact profile',
    ],
  ]);
  $form_display->save();
  echo "   ✅ Form display configured\n";
} else {
  echo "   ⚠️  Form display already configured\n";
}

// 4. Configure view display
echo "4. Configuring view display...\n";
$view_display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('user.user.default');

if ($view_display && !$view_display->getComponent('field_contact_profile')) {
  $view_display->setComponent('field_contact_profile', [
    'type' => 'entity_reference_label',
    'weight' => 20,
    'label' => 'above',
    'settings' => [
      'link' => TRUE,
    ],
  ]);
  $view_display->save();
  echo "   ✅ View display configured\n";
} else {
  echo "   ⚠️  View display already configured\n";
}

echo "\n🎉 Customer Portal setup complete!\n";
echo "📝 Next steps:\n";
echo "   - Admin users can now link Customer accounts to Contact nodes\n";
echo "   - Run: ddev drush cr\n";
echo "   - Create /my/projects view\n";
