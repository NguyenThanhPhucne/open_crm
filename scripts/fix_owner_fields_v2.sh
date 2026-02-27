#!/bin/bash

echo "🔧 FIX OWNER FIELDS - PHIÊN BẢN SỬA LỖI"
echo "========================================"
echo ""

# Bước 1: Xóa các field cũ nếu có lỗi
echo "🧹 1. Xóa field cũ (nếu có lỗi)..."
ddev drush eval "
// Xóa field_assigned_staff từ Organization (sẽ thay bằng field_owner)
try {
  \$field = \Drupal\field\Entity\FieldConfig::loadByName('node', 'organization', 'field_assigned_staff');
  if (\$field) {
    \$field->delete();
    echo '✅ Đã xóa field_assigned_staff cũ' . PHP_EOL;
  }
} catch (Exception \$e) {
  echo '⚠️  field_assigned_staff không tồn tại' . PHP_EOL;
}
" 2>/dev/null || echo "⚠️  Skip"

echo ""

# Bước 2: Tạo field_owner storage (chỉ 1 lần, dùng chung cho tất cả)
echo "📦 2. Tạo field_owner storage..."
ddev drush eval "
try {
  \$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_owner');
  if (!\$field_storage) {
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
    echo '✅ Tạo field_owner storage' . PHP_EOL;
  } else {
    echo '✅ field_owner storage đã tồn tại' . PHP_EOL;
  }
} catch (Exception \$e) {
  echo '❌ Lỗi: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""

# Bước 3: Tạo field_assigned_to storage cho Activity
echo "📦 3. Tạo field_assigned_to storage..."
ddev drush eval "
try {
  \$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_assigned_to');
  if (!\$field_storage) {
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
    echo '✅ Tạo field_assigned_to storage' . PHP_EOL;
  } else {
    echo '✅ field_assigned_to storage đã tồn tại' . PHP_EOL;
  }
} catch (Exception \$e) {
  echo '❌ Lỗi: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""

# Bước 4: Thêm field_owner vào Contact, Deal, Organization
echo "📋 4. Thêm field_owner vào Content Types..."
ddev drush eval "
\$bundles = ['contact', 'deal', 'organization'];
\$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_owner');

foreach (\$bundles as \$bundle) {
  try {
    \$field = \Drupal\field\Entity\FieldConfig::loadByName('node', \$bundle, 'field_owner');
    if (!\$field) {
      \$field = \Drupal\field\Entity\FieldConfig::create([
        'field_storage' => \$field_storage,
        'bundle' => \$bundle,
        'label' => 'Owner (Sales phụ trách)',
        'required' => TRUE,
        'settings' => [
          'handler' => 'default:user',
          'handler_settings' => [
            'filter' => [
              'type' => 'role',
              'role' => [
                'sales_representative' => 'sales_representative',
                'sales_manager' => 'sales_manager',
              ],
            ],
          ],
        ],
      ]);
      \$field->save();
      echo '✅ Thêm field_owner vào ' . \$bundle . PHP_EOL;
    } else {
      echo '✅ field_owner đã tồn tại trong ' . \$bundle . PHP_EOL;
    }
  } catch (Exception \$e) {
    echo '❌ Lỗi ' . \$bundle . ': ' . \$e->getMessage() . PHP_EOL;
  }
}
"

echo ""

# Bước 5: Thêm field_assigned_to vào Activity
echo "📅 5. Thêm field_assigned_to vào Activity..."
ddev drush eval "
try {
  \$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'field_assigned_to');
  \$field = \Drupal\field\Entity\FieldConfig::loadByName('node', 'activity', 'field_assigned_to');
  
  if (!\$field) {
    \$field = \Drupal\field\Entity\FieldConfig::create([
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
              'sales_representative' => 'sales_representative',
              'sales_manager' => 'sales_manager',
            ],
          ],
        ],
      ],
    ]);
    \$field->save();
    echo '✅ Thêm field_assigned_to vào Activity' . PHP_EOL;
  } else {
    echo '✅ field_assigned_to đã tồn tại trong Activity' . PHP_EOL;
  }
} catch (Exception \$e) {
  echo '❌ Lỗi: ' . \$e->getMessage() . PHP_EOL;
}
"

echo ""

# Bước 6: Cấu hình Form Display
echo "🎨 6. Cấu hình Form Display..."
ddev drush eval "
\$configs = [
  ['type' => 'contact', 'field' => 'field_owner', 'label' => 'Sales phụ trách'],
  ['type' => 'deal', 'field' => 'field_owner', 'label' => 'Sales phụ trách'],
  ['type' => 'organization', 'field' => 'field_owner', 'label' => 'Sales phụ trách'],
  ['type' => 'activity', 'field' => 'field_assigned_to', 'label' => 'Người xử lý'],
];

foreach (\$configs as \$config) {
  \$form_display = \Drupal::entityTypeManager()
    ->getStorage('entity_form_display')
    ->load('node.' . \$config['type'] . '.default');
  
  if (\$form_display) {
    \$form_display->setComponent(\$config['field'], [
      'type' => 'entity_reference_autocomplete',
      'weight' => 20,
      'settings' => [
        'match_operator' => 'CONTAINS',
        'size' => 60,
        'placeholder' => 'Chọn ' . \$config['label'] . '...',
      ],
    ])->save();
    echo '✅ Configured form display for ' . \$config['type'] . PHP_EOL;
  }
}
"

echo ""

# Bước 7: Set default value = current user khi tạo node mới
echo "🔧 7. Setup default value = current user..."
cat > /tmp/crm_owner_default.php << 'EOF'
<?php

/**
 * Implements hook_form_alter().
 * Set field_owner và field_assigned_to default = current user
 */
function crm_form_node_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $node = $form_state->getFormObject()->getEntity();
  
  // Chỉ áp dụng khi tạo node mới (chưa có ID)
  if ($node->isNew()) {
    $current_user = \Drupal::currentUser();
    
    // Set field_owner cho Contact, Deal, Organization
    if (in_array($node->bundle(), ['contact', 'deal', 'organization'])) {
      if (isset($form['field_owner'])) {
        $form['field_owner']['widget'][0]['target_id']['#default_value'] = \Drupal\user\Entity\User::load($current_user->id());
      }
    }
    
    // Set field_assigned_to cho Activity
    if ($node->bundle() === 'activity') {
      if (isset($form['field_assigned_to'])) {
        $form['field_assigned_to']['widget'][0]['target_id']['#default_value'] = \Drupal\user\Entity\User::load($current_user->id());
      }
    }
  }
}
EOF

echo "✅ Created default value logic (cần integrate vào custom module)"

echo ""

# Bước 8: Clear cache
echo "🧹 8. Clear cache..."
ddev drush cr

echo ""
echo "✅ HOÀN THÀNH!"
echo ""
echo "📌 ĐÃ FIX:"
echo "   ✅ Field storage created đúng"
echo "   ✅ field_owner cho Contact, Deal, Organization"
echo "   ✅ field_assigned_to cho Activity"
echo "   ✅ Role filter đúng: sales_representative + sales_manager"
echo "   ✅ Form display configured"
echo ""
echo "⚠️  CẦN THỰC HIỆN TIẾP:"
echo "   1. Cập nhật existing data với owner"
echo "   2. Tạo sample data MỚI với owner fields"
echo "   3. Update views với owner filters"
echo "   4. Configure permissions: view own content only"
