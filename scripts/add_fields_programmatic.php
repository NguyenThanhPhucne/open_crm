<?php

/**
 * Add additional CRM fields programmatically
 * Run with: ddev drush scr scripts/add_fields_programmatic.php
 */

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "=== Creating Additional CRM Fields ===\n\n";

// Helper function to create field storage
function create_field_storage($field_name, $type, $entity_type = 'node', $settings = []) {
  $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
  if (!$storage) {
    $storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => $type,
      'cardinality' => isset($settings['cardinality']) ? $settings['cardinality'] : 1,
      'settings' => isset($settings['storage_settings']) ? $settings['storage_settings'] : [],
    ]);
    $storage->save();
    echo "  ✓ Created field storage: $field_name\n";
    return true;
  }
  echo "  ⚠️  Field storage exists: $field_name\n";
  return false;
}

// Helper function to create field instance
function create_field_instance($bundle, $field_name, $label, $description = '', $required = FALSE, $settings = []) {
  $field = FieldConfig::loadByName('node', $bundle, $field_name);
  if (!$field) {
    $field = FieldConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => $label,
      'description' => $description,
      'required' => $required,
      'settings' => $settings,
    ]);
    $field->save();
    echo "  ✓ Created field instance: $bundle.$field_name\n";
    return $field;
  }
  echo "  ⚠️  Field instance exists: $bundle.$field_name\n";
  return $field;
}

// 1. Add field_source to Contact
echo "1. Creating field_source (Nguồn khách hàng) for Contact...\n";
create_field_storage('field_source', 'entity_reference', 'node', [
  'storage_settings' => ['target_type' => 'taxonomy_term'],
]);
$field = create_field_instance(
  'contact',
  'field_source',
  'Nguồn khách hàng',
  'Nguồn khách hàng (Website, Ref erral, Event, Cold Call, Social Media, Email Campaign)',
  FALSE,
  [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['crm_source' => 'crm_source'],
      'auto_create' => FALSE,
    ],
  ]
);
echo "\n";

// 2. Add field_customer_type to Contact
echo "2. Creating field_customer_type (Phân loại KH) for Contact...\n";
create_field_storage('field_customer_type', 'entity_reference', 'node', [
  'storage_settings' => ['target_type' => 'taxonomy_term'],
]);
$field = create_field_instance(
  'contact',
  'field_customer_type',
  'Phân loại khách hàng',
  'VIP, Khách hàng mới, Tiềm năng, Đang theo dõi, Khách hàng cũ',
  FALSE,
  [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['crm_customer_type' => 'crm_customer_type'],
      'auto_create' => FALSE,
    ],
  ]
);
echo "\n";

// 3. Add field_industry to Organization
echo "3. Creating field_industry (Ngành nghề) for Organization...\n";
create_field_storage('field_industry', 'entity_reference', 'node', [
  'storage_settings' => ['target_type' => 'taxonomy_term'],
]);
$field = create_field_instance(
  'organization',
  'field_industry',
  'Ngành nghề',
  'Ngành nghề của tổ chức (Technology, Finance, Healthcare, Education, Retail, Manufacturing, Real Estate, Consulting, Marketing, Logistics)',
  FALSE,
  [
    'handler' => 'default:taxonomy_term',
    'handler_settings' => [
      'target_bundles' => ['crm_industry' => 'crm_industry'],
      'auto_create' => FALSE,
    ],
  ]
);
echo "\n";

// 4. Add field_probability to Deal
echo "4. Creating field_probability (Khả năng chốt %) for Deal...\n";
create_field_storage('field_probability', 'integer', 'node');
$field = create_field_instance(
  'deal',
  'field_probability',
  'Khả năng chốt (%)',
  'Khả năng chốt deal (0-100%). Tự động cập nhật theo Stage: New=10%, Qualified=25%, Proposal=50%, Negotiation=75%, Won=100%, Lost=0%.',
  FALSE,
  [
    'min' => 0,
    'max' => 100,
  ]
);
if ($field) {
  $field->setDefaultValue([['value' => 50]]);
  $field->save();
  echo "  ✓ Set default value: 50%\n";
}
echo "\n";

// 5. Add field_contract to Deal
echo "5. Creating field_contract (Hợp đồng/File) for Deal...\n";
create_field_storage('field_contract', 'file', 'node', [
  'cardinality' => -1, // Unlimited
]);
$field = create_field_instance(
  'deal',
  'field_contract',
  'Hợp đồng/File đính kèm',
  'Upload hợp đồng, báo giá, hoặc tài liệu đính kèm. Chấp nhận: PDF, DOC, XLS, PPT, TXT, ZIP. Tối đa 10MB.',
  FALSE,
  [
    'file_extensions' => 'pdf doc docx xls xlsx ppt pptx txt zip',
    'file_directory' => 'crm/contracts/[date:custom:Y]-[date:custom:m]',
    'max_filesize' => '10 MB',
  ]
);
echo "\n";

echo "✅ All fields created successfully!\n\n";

echo "📋 Summary:\n";
echo "   Contact:\n";
echo "     • field_source → Nguồn khách hàng (taxonomy reference)\n";
echo "     • field_customer_type → Phân loại KH (taxonomy reference)\n";
echo "\n";
echo "   Organization:\n";
echo "     • field_industry → Ngành nghề (taxonomy reference)\n";
echo "\n";
echo "   Deal:\n";
echo "     • field_probability → Khả năng chốt 0-100% (integer)\n";
echo "     • field_contract → File hợp đồng (file, unlimited)\n";
echo "\n";
