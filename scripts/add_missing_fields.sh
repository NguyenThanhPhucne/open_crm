#!/bin/bash

# Script: Add Missing Fields According to Master Plan
# Description: Add field_position to Contact, field_logo and field_status to Organization

echo "📝 Adding missing fields according to Master Plan..."

# Add field_position to Contact
echo ""
echo "1. Adding field_position to Contact..."
ddev drush field:create node.contact.field_position \
  --field-label="Position" \
  --field-type=string \
  --cardinality=1 \
  --required=false

echo "   ✅ field_position created!"

# Add field_logo to Organization
echo ""
echo "2. Adding field_logo to Organization..."
ddev drush field:create node.organization.field_logo \
  --field-label="Logo" \
  --field-type=image \
  --cardinality=1 \
  --required=false

echo "   ✅ field_logo created!"

# Add field_status to Organization
echo ""
echo "3. Adding field_status to Organization..."
ddev drush field:create node.organization.field_status \
  --field-label="Status" \
  --field-type=list_string \
  --cardinality=1 \
  --required=true

# Set allowed values for field_status
echo ""
echo "4. Configuring field_status allowed values..."
ddev drush ev "
\$field_storage = \Drupal::entityTypeManager()
  ->getStorage('field_storage_config')
  ->load('node.field_status');
  
if (\$field_storage) {
  \$settings = \$field_storage->getSettings();
  \$settings['allowed_values'] = [
    'active' => 'Active',
    'inactive' => 'Inactive',
  ];
  \$field_storage->setSettings(\$settings);
  \$field_storage->save();
  echo 'Status field configured: Active/Inactive\n';
}
"

# Set default value for field_status
echo ""
echo "5. Setting default value for field_status..."
ddev drush ev "
\$field_config = \Drupal::entityTypeManager()
  ->getStorage('field_config')
  ->load('node.organization.field_status');
  
if (\$field_config) {
  \$field_config->setDefaultValue([['value' => 'active']]);
  \$field_config->save();
  echo 'Default value set to Active\n';
}
"

# Update form display
echo ""
echo "6. Updating form displays..."
ddev drush ev "
\$entity_form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.contact.default');
  
if (\$entity_form_display) {
  \$entity_form_display->setComponent('field_position', [
    'type' => 'string_textfield',
    'weight' => 4,
    'settings' => [
      'size' => 60,
      'placeholder' => 'e.g., Sales Manager, CEO',
    ],
  ]);
  \$entity_form_display->save();
  echo 'Contact form display updated\n';
}

\$entity_form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.organization.default');
  
if (\$entity_form_display) {
  // field_logo
  \$entity_form_display->setComponent('field_logo', [
    'type' => 'image_image',
    'weight' => 1,
    'settings' => [
      'preview_image_style' => 'thumbnail',
      'progress_indicator' => 'throbber',
    ],
  ]);
  
  // field_status
  \$entity_form_display->setComponent('field_status', [
    'type' => 'options_select',
    'weight' => 10,
  ]);
  
  \$entity_form_display->save();
  echo 'Organization form display updated\n';
}
"

# Update view display
echo ""
echo "7. Updating view displays..."
ddev drush ev "
\$entity_view_display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('node.contact.default');
  
if (\$entity_view_display) {
  \$entity_view_display->setComponent('field_position', [
    'type' => 'string',
    'weight' => 4,
    'label' => 'inline',
  ]);
  \$entity_view_display->save();
  echo 'Contact view display updated\n';
}

\$entity_view_display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('node.organization.default');
  
if (\$entity_view_display) {
  // field_logo
  \$entity_view_display->setComponent('field_logo', [
    'type' => 'image',
    'weight' => 0,
    'label' => 'hidden',
    'settings' => [
      'image_style' => 'medium',
      'image_link' => '',
    ],
  ]);
  
  // field_status (as badge)
  \$entity_view_display->setComponent('field_status', [
    'type' => 'list_default',
    'weight' => 10,
    'label' => 'inline',
  ]);
  
  \$entity_view_display->save();
  echo 'Organization view display updated\n';
}
"

# Update existing organizations to set default status
echo ""
echo "8. Setting default status for existing organizations..."
ddev drush ev "
\$nids = \Drupal::entityQuery('node')
  ->condition('type', 'organization')
  ->accessCheck(FALSE)
  ->execute();

\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadMultiple(\$nids);

\$count = 0;
foreach (\$nodes as \$node) {
  if (\$node->hasField('field_status') && \$node->get('field_status')->isEmpty()) {
    \$node->set('field_status', 'active');
    \$node->save();
    \$count++;
  }
}

echo 'Updated ' . \$count . ' existing organizations with Active status\n';
"

# Clear cache
echo ""
echo "9. Clearing cache..."
ddev drush cr

echo ""
echo "✅ All missing fields added successfully!"
echo ""
echo "📋 Summary:"
echo "  - Contact: field_position (Text) ✅"
echo "  - Organization: field_logo (Image) ✅"
echo "  - Organization: field_status (Active/Inactive) ✅"
echo ""
echo "🎯 Next: Update sample data with these fields"
