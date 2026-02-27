#!/bin/bash

echo "🔒 THÊM FIELDS OWNER/ASSIGNEE CHO DATA PRIVACY"
echo "================================================"
echo ""

# Bước 1: Thêm field_owner vào Contact
echo "📋 1. Thêm field_owner vào Contact..."
ddev drush eval "
\$field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
  'field_name' => 'field_owner',
  'entity_type' => 'node',
  'type' => 'entity_reference',
  'settings' => [
    'target_type' => 'user',
  ],
  'cardinality' => 1,
]);
\$field_storage->save();

\$field_instance = \Drupal\field\Entity\FieldConfig::create([
  'field_storage' => \$field_storage,
  'bundle' => 'contact',
  'label' => 'Owner (Sales phụ trách)',
  'required' => TRUE,
  'settings' => [
    'handler' => 'default:user',
    'handler_settings' => [
      'filter' => [
        'type' => 'role',
        'role' => [
          'sales_manager' => 'sales_manager',
          'sales_representative' => 'sales_representative',
        ],
      ],
    ],
  ],
]);
\$field_instance->save();

echo '✅ field_owner đã được thêm vào Contact' . PHP_EOL;
" || echo "⚠️  field_owner đã tồn tại trong Contact"

echo ""

# Bước 2: Thêm field_owner vào Deal
echo "💼 2. Thêm field_owner vào Deal..."
ddev drush eval "
\$field_instance = \Drupal\field\Entity\FieldConfig::create([
  'field_storage' => \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_owner'),
  'bundle' => 'deal',
  'label' => 'Owner (Sales phụ trách)',
  'required' => TRUE,
  'settings' => [
    'handler' => 'default:user',
    'handler_settings' => [
      'filter' => [
        'type' => 'role',
        'role' => [
          'sales_manager' => 'sales_manager',
          'sales_representative' => 'sales_representative',
        ],
      ],
    ],
  ],
]);
\$field_instance->save();

echo '✅ field_owner đã được thêm vào Deal' . PHP_EOL;
" || echo "⚠️  field_owner đã tồn tại trong Deal"

echo ""

# Bước 3: Thêm field_owner vào Organization
echo "🏢 3. Thêm field_owner vào Organization..."
ddev drush eval "
\$field_instance = \Drupal\field\Entity\FieldConfig::create([
  'field_storage' => \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_owner'),
  'bundle' => 'organization',
  'label' => 'Owner (Sales phụ trách)',
  'required' => TRUE,
  'settings' => [
    'handler' => 'default:user',
    'handler_settings' => [
      'filter' => [
        'type' => 'role',
        'role' => [
          'sales_manager' => 'sales_manager',
          'sales_representative' => 'sales_representative',
        ],
      ],
    ],
  ],
]);
\$field_instance->save();

echo '✅ field_owner đã được thêm vào Organization' . PHP_EOL;
" || echo "⚠️  field_owner đã tồn tại trong Organization"

echo ""

# Bước 4: Thêm field_assigned_to vào Activity
echo "📅 4. Thêm field_assigned_to vào Activity..."
ddev drush eval "
\$field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
  'field_name' => 'field_assigned_to',
  'entity_type' => 'node',
  'type' => 'entity_reference',
  'settings' => [
    'target_type' => 'user',
  ],
  'cardinality' => 1,
]);
\$field_storage->save();

\$field_instance = \Drupal\field\Entity\FieldConfig::create([
  'field_storage' => \$field_storage,
  'bundle' => 'activity',
  'label' => 'Assigned To (Người xử lý)',
  'required' => TRUE,
  'settings' => [
    'handler' => 'default:user',
    'handler_settings' => [
      'filter' => [
        'type' => 'role',
        'role' => [
          'sales_manager' => 'sales_manager',
          'sales_representative' => 'sales_representative',
        ],
      ],
    ],
  ],
]);
\$field_instance->save();

echo '✅ field_assigned_to đã được thêm vào Activity' . PHP_EOL;
" || echo "⚠️  field_assigned_to đã tồn tại trong Activity"

echo ""

# Bước 5: Cập nhật sample data với owner = admin
echo "🔄 5. Cập nhật sample data với owner = Admin (uid=1)..."
ddev drush eval "
\$admin_uid = 1;

// Cập nhật Contacts
\$contacts = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact']);
foreach (\$contacts as \$contact) {
  \$contact->set('field_owner', \$admin_uid);
  \$contact->save();
}
echo '✅ Cập nhật ' . count(\$contacts) . ' Contacts với owner = Admin' . PHP_EOL;

// Cập nhật Deals
\$deals = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal']);
foreach (\$deals as \$deal) {
  \$deal->set('field_owner', \$admin_uid);
  \$deal->save();
}
echo '✅ Cập nhật ' . count(\$deals) . ' Deals với owner = Admin' . PHP_EOL;

// Cập nhật Activities
\$activities = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'activity']);
foreach (\$activities as \$activity) {
  \$activity->set('field_assigned_to', \$admin_uid);
  \$activity->save();
}
echo '✅ Cập nhật ' . count(\$activities) . ' Activities với assigned_to = Admin' . PHP_EOL;

// Cập nhật Organizations
\$orgs = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'organization']);
foreach (\$orgs as \$org) {
  \$org->set('field_owner', \$admin_uid);
  \$org->save();
}
echo '✅ Cập nhật ' . count(\$orgs) . ' Organizations với owner = Admin' . PHP_EOL;
"

echo ""

# Bước 6: Cấu hình Form Display để hiển thị owner fields
echo "🎨 6. Cấu hình Form Display..."
ddev drush eval "
// Contact form display
\$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.contact.default');
if (\$form_display) {
  \$form_display->setComponent('field_owner', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 20,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => 'Chọn Sales phụ trách...',
    ],
  ])->save();
}

// Deal form display
\$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.deal.default');
if (\$form_display) {
  \$form_display->setComponent('field_owner', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 20,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => 'Chọn Sales phụ trách...',
    ],
  ])->save();
}

// Organization form display
\$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.organization.default');
if (\$form_display) {
  \$form_display->setComponent('field_owner', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 20,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => 'Chọn Sales phụ trách...',
    ],
  ])->save();
}

// Activity form display
\$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.activity.default');
if (\$form_display) {
  \$form_display->setComponent('field_assigned_to', [
    'type' => 'entity_reference_autocomplete',
    'weight' => 20,
    'settings' => [
      'match_operator' => 'CONTAINS',
      'size' => 60,
      'placeholder' => 'Chọn người xử lý...',
    ],
  ])->save();
}

echo '✅ Form Display đã được cấu hình' . PHP_EOL;
"

echo ""
echo "✨ HOÀN THÀNH! Owner/Assignee fields đã được thêm vào tất cả content types."
echo ""
echo "📌 Tiếp theo:"
echo "   1. Cập nhật Views để filter theo Owner"
echo "   2. Cấu hình permissions: Sales chỉ xem được data của mình"
echo "   3. Tạo Dashboard cá nhân"
