<?php

/**
 * Phase 1: Bổ sung Fields & Taxonomies thiếu
 * Upgrade hệ thống lên 10/10
 */

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

echo "=== PHASE 1: UPGRADE FIELDS & TAXONOMIES ===\n\n";

// ============================================================================
// STEP 1: TẠO TAXONOMIES THIẾU
// ============================================================================

echo "[1/6] Creating missing vocabularies...\n";

$vocabularies = [
  'crm_industry' => [
    'name' => 'CRM Industry',
    'description' => 'Ngành nghề của tổ chức',
    'terms' => [
      'Technology' => 'Công nghệ thông tin',
      'Finance' => 'Tài chính ngân hàng',
      'Healthcare' => 'Y tế sức khỏe',
      'Education' => 'Giáo dục đào tạo',
      'Retail' => 'Bán lẻ',
      'Manufacturing' => 'Sản xuất',
      'Real Estate' => 'Bất động sản',
      'Consulting' => 'Tư vấn',
      'Media' => 'Truyền thông',
      'Other' => 'Khác',
    ],
  ],
  'crm_contact_status' => [
    'name' => 'CRM Contact Status',
    'description' => 'Trạng thái khách hàng',
    'terms' => [
      'New Lead' => 'Khách hàng mới',
      'Contacted' => 'Đã liên hệ',
      'Qualified' => 'Đã đánh giá',
      'Negotiating' => 'Đang đàm phán',
      'Customer' => 'Khách hàng',
      'VIP' => 'Khách hàng VIP',
      'Inactive' => 'Không hoạt động',
      'Lost' => 'Mất khách',
    ],
  ],
  'crm_org_status' => [
    'name' => 'CRM Organization Status',
    'description' => 'Trạng thái tổ chức',
    'terms' => [
      'Active' => 'Đang hoạt động',
      'Inactive' => 'Ngừng hoạt động',
      'Potential' => 'Tiềm năng',
      'Partner' => 'Đối tác',
    ],
  ],
  'crm_contact_tags' => [
    'name' => 'CRM Contact Tags',
    'description' => 'Tags phân loại khách hàng',
    'terms' => [
      'VIP' => 'Khách hàng quan trọng',
      'Hot Lead' => 'Lead nóng',
      'Decision Maker' => 'Người quyết định',
      'Influencer' => 'Người ảnh hưởng',
      'Budget Approved' => 'Đã duyệt ngân sách',
      'Need Follow-up' => 'Cần theo dõi',
      'Large Account' => 'Tài khoản lớn',
      'Referral' => 'Giới thiệu',
    ],
  ],
];

foreach ($vocabularies as $vid => $vocab_data) {
  $vocabulary = Vocabulary::load($vid);
  
  if (!$vocabulary) {
    $vocabulary = Vocabulary::create([
      'vid' => $vid,
      'name' => $vocab_data['name'],
      'description' => $vocab_data['description'],
    ]);
    $vocabulary->save();
    echo "  ✓ Created vocabulary: {$vocab_data['name']}\n";
    
    // Tạo terms
    foreach ($vocab_data['terms'] as $name => $description) {
      $term = Term::create([
        'vid' => $vid,
        'name' => $name,
        'description' => $description,
      ]);
      $term->save();
    }
    echo "    → Added " . count($vocab_data['terms']) . " terms\n";
  } else {
    echo "  ⊙ Vocabulary already exists: {$vocab_data['name']}\n";
  }
}

echo "\n";

// ============================================================================
// STEP 2: BỔ SUNG FIELDS CHO CONTACT
// ============================================================================

echo "[2/6] Adding missing fields to Contact...\n";

$contact_fields = [
  'field_avatar' => [
    'type' => 'image',
    'label' => 'Avatar',
    'description' => 'Ảnh đại diện khách hàng',
    'settings' => [
      'file_extensions' => 'png jpg jpeg gif',
      'max_filesize' => '2 MB',
      'max_resolution' => '800x800',
    ],
    'cardinality' => 1,
  ],
  'field_last_contacted' => [
    'type' => 'datetime',
    'label' => 'Last Contacted',
    'description' => 'Lần liên hệ cuối cùng',
    'settings' => [
      'datetime_type' => 'datetime',
    ],
    'cardinality' => 1,
  ],
  'field_status' => [
    'type' => 'entity_reference',
    'label' => 'Status',
    'description' => 'Trạng thái khách hàng',
    'settings' => [
      'target_type' => 'taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['crm_contact_status' => 'crm_contact_status'],
      ],
    ],
    'cardinality' => 1,
  ],
  'field_tags' => [
    'type' => 'entity_reference',
    'label' => 'Tags',
    'description' => 'Tags phân loại',
    'settings' => [
      'target_type' => 'taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['crm_contact_tags' => 'crm_contact_tags'],
      ],
    ],
    'cardinality' => -1, // Unlimited
  ],
  'field_linkedin' => [
    'type' => 'link',
    'label' => 'LinkedIn',
    'description' => 'LinkedIn profile URL',
    'settings' => [],
    'cardinality' => 1,
  ],
];

foreach ($contact_fields as $field_name => $field_data) {
  // Check field storage
  $field_storage = FieldStorageConfig::loadByName('node', $field_name);
  
  if (!$field_storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_data['type'],
      'cardinality' => $field_data['cardinality'],
    ];
    
    if (isset($field_data['settings'])) {
      $storage_config['settings'] = $field_data['settings'];
    }
    
    $field_storage = FieldStorageConfig::create($storage_config);
    $field_storage->save();
    echo "  ✓ Created field storage: $field_name\n";
  }
  
  // Check field instance
  $field = FieldConfig::loadByName('node', 'contact', $field_name);
  
  if (!$field) {
    $field_config = [
      'field_storage' => $field_storage,
      'bundle' => 'contact',
      'label' => $field_data['label'],
      'description' => $field_data['description'],
      'required' => FALSE,
    ];
    
    if (isset($field_data['settings'])) {
      $field_config['settings'] = $field_data['settings'];
    }
    
    $field = FieldConfig::create($field_config);
    $field->save();
    echo "  ✓ Added field to Contact: {$field_data['label']}\n";
  }
}

echo "\n";

// ============================================================================
// STEP 3: BỔ SUNG FIELDS CHO DEAL
// ============================================================================

echo "[3/6] Adding missing fields to Deal...\n";

$deal_fields = [
  'field_contract_file' => [
    'type' => 'file',
    'label' => 'Contract File',
    'description' => 'File hợp đồng (yêu cầu khi Won)',
    'settings' => [
      'file_extensions' => 'pdf doc docx',
      'max_filesize' => '10 MB',
    ],
    'cardinality' => -1,
  ],
  'field_lost_reason' => [
    'type' => 'text_long',
    'label' => 'Lost Reason',
    'description' => 'Lý do mất deal',
    'settings' => [],
    'cardinality' => 1,
  ],
  'field_notes' => [
    'type' => 'text_long',
    'label' => 'Internal Notes',
    'description' => 'Ghi chú nội bộ',
    'settings' => [],
    'cardinality' => 1,
  ],
  'field_expected_close_date' => [
    'type' => 'datetime',
    'label' => 'Expected Close Date',
    'description' => 'Ngày dự kiến chốt deal',
    'settings' => [
      'datetime_type' => 'date',
    ],
    'cardinality' => 1,
  ],
];

foreach ($deal_fields as $field_name => $field_data) {
  $field_storage = FieldStorageConfig::loadByName('node', $field_name);
  
  if (!$field_storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_data['type'],
      'cardinality' => $field_data['cardinality'],
    ];
    
    if (isset($field_data['settings'])) {
      $storage_config['settings'] = $field_data['settings'];
    }
    
    $field_storage = FieldStorageConfig::create($storage_config);
    $field_storage->save();
    echo "  ✓ Created field storage: $field_name\n";
  }
  
  $field = FieldConfig::loadByName('node', 'deal', $field_name);
  
  if (!$field) {
    $field_config = [
      'field_storage' => $field_storage,
      'bundle' => 'deal',
      'label' => $field_data['label'],
      'description' => $field_data['description'],
      'required' => FALSE,
    ];
    
    if (isset($field_data['settings'])) {
      $field_config['settings'] = $field_data['settings'];
    }
    
    $field = FieldConfig::create($field_config);
    $field->save();
    echo "  ✓ Added field to Deal: {$field_data['label']}\n";
  }
}

echo "\n";

// ============================================================================
// STEP 4: BỔ SUNG FIELDS CHO ORGANIZATION
// ============================================================================

echo "[4/6] Adding missing fields to Organization...\n";

$org_fields = [
  'field_industry' => [
    'type' => 'entity_reference',
    'label' => 'Industry',
    'description' => 'Ngành nghề',
    'settings' => [
      'target_type' => 'taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['crm_industry' => 'crm_industry'],
      ],
    ],
    'cardinality' => 1,
  ],
  'field_status' => [
    'type' => 'entity_reference',
    'label' => 'Status',
    'description' => 'Trạng thái tổ chức',
    'settings' => [
      'target_type' => 'taxonomy_term',
      'handler_settings' => [
        'target_bundles' => ['crm_org_status' => 'crm_org_status'],
      ],
    ],
    'cardinality' => 1,
  ],
  'field_employees_count' => [
    'type' => 'integer',
    'label' => 'Number of Employees',
    'description' => 'Số lượng nhân viên',
    'settings' => [
      'min' => 0,
    ],
    'cardinality' => 1,
  ],
  'field_annual_revenue' => [
    'type' => 'decimal',
    'label' => 'Annual Revenue',
    'description' => 'Doanh thu hàng năm (VND)',
    'settings' => [
      'precision' => 15,
      'scale' => 0,
    ],
    'cardinality' => 1,
  ],
];

foreach ($org_fields as $field_name => $field_data) {
  $field_storage = FieldStorageConfig::loadByName('node', $field_name);
  
  if (!$field_storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_data['type'],
      'cardinality' => $field_data['cardinality'],
    ];
    
    if (isset($field_data['settings'])) {
      $storage_config['settings'] = $field_data['settings'];
    }
    
    $field_storage = FieldStorageConfig::create($storage_config);
    $field_storage->save();
    echo "  ✓ Created field storage: $field_name\n";
  }
  
  $field = FieldConfig::loadByName('node', 'organization', $field_name);
  
  if (!$field) {
    $field_config = [
      'field_storage' => $field_storage,
      'bundle' => 'organization',
      'label' => $field_data['label'],
      'description' => $field_data['description'],
      'required' => FALSE,
    ];
    
    if (isset($field_data['settings'])) {
      $field_config['settings'] = $field_data['settings'];
    }
    
    $field = FieldConfig::create($field_config);
    $field->save();
    echo "  ✓ Added field to Organization: {$field_data['label']}\n";
  }
}

echo "\n";

// ============================================================================
// STEP 5: BỔ SUNG FIELDS CHO ACTIVITY
// ============================================================================

echo "[5/6] Adding missing fields to Activity...\n";

$activity_fields = [
  'field_contact_ref' => [
    'type' => 'entity_reference',
    'label' => 'Related Contact',
    'description' => 'Khách hàng liên quan',
    'settings' => [
      'target_type' => 'node',
      'handler_settings' => [
        'target_bundles' => ['contact' => 'contact'],
      ],
    ],
    'cardinality' => 1,
  ],
];

foreach ($activity_fields as $field_name => $field_data) {
  $field_storage = FieldStorageConfig::loadByName('node', $field_name);
  
  if (!$field_storage) {
    $storage_config = [
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => $field_data['type'],
      'cardinality' => $field_data['cardinality'],
    ];
    
    if (isset($field_data['settings'])) {
      $storage_config['settings'] = $field_data['settings'];
    }
    
    $field_storage = FieldStorageConfig::create($storage_config);
    $field_storage->save();
    echo "  ✓ Created field storage: $field_name\n";
  }
  
  $field = FieldConfig::loadByName('node', 'activity', $field_name);
  
  if (!$field) {
    $field_config = [
      'field_storage' => $field_storage,
      'bundle' => 'activity',
      'label' => $field_data['label'],
      'description' => $field_data['description'],
      'required' => FALSE,
    ];
    
    if (isset($field_data['settings'])) {
      $field_config['settings'] = $field_data['settings'];
    }
    
    $field = FieldConfig::create($field_config);
    $field->save();
    echo "  ✓ Added field to Activity: {$field_data['label']}\n";
  }
}

echo "\n";

// ============================================================================
// STEP 6: CẤU HÌNH FORM DISPLAYS
// ============================================================================

echo "[6/6] Configuring form displays...\n";

// Contact form display
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.contact.default');

if ($form_display) {
  $components = [
    'field_avatar' => [
      'type' => 'image_image',
      'weight' => 1,
      'settings' => [
        'progress_indicator' => 'throbber',
        'preview_image_style' => 'thumbnail',
      ],
    ],
    'field_status' => [
      'type' => 'options_select',
      'weight' => 10,
    ],
    'field_tags' => [
      'type' => 'entity_reference_autocomplete_tags',
      'weight' => 11,
    ],
    'field_last_contacted' => [
      'type' => 'datetime_default',
      'weight' => 15,
    ],
    'field_linkedin' => [
      'type' => 'link_default',
      'weight' => 20,
    ],
  ];
  
  foreach ($components as $field_name => $config) {
    if (!$form_display->getComponent($field_name)) {
      $form_display->setComponent($field_name, $config);
    }
  }
  
  $form_display->save();
  echo "  ✓ Updated Contact form display\n";
}

// Deal form display
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.deal.default');

if ($form_display) {
  $components = [
    'field_expected_close_date' => [
      'type' => 'datetime_default',
      'weight' => 15,
    ],
    'field_notes' => [
      'type' => 'text_textarea',
      'weight' => 20,
    ],
    'field_contract_file' => [
      'type' => 'file_generic',
      'weight' => 25,
    ],
    'field_lost_reason' => [
      'type' => 'text_textarea',
      'weight' => 30,
    ],
  ];
  
  foreach ($components as $field_name => $config) {
    if (!$form_display->getComponent($field_name)) {
      $form_display->setComponent($field_name, $config);
    }
  }
  
  $form_display->save();
  echo "  ✓ Updated Deal form display\n";
}

// Organization form display
$form_display = \Drupal::entityTypeManager()
  ->getStorage('entity_form_display')
  ->load('node.organization.default');

if ($form_display) {
  $components = [
    'field_industry' => [
      'type' => 'options_select',
      'weight' => 10,
    ],
    'field_status' => [
      'type' => 'options_select',
      'weight' => 11,
    ],
    'field_employees_count' => [
      'type' => 'number',
      'weight' => 15,
    ],
    'field_annual_revenue' => [
      'type' => 'number',
      'weight' => 16,
    ],
  ];
  
  foreach ($components as $field_name => $config) {
    if (!$form_display->getComponent($field_name)) {
      $form_display->setComponent($field_name, $config);
    }
  }
  
  $form_display->save();
  echo "  ✓ Updated Organization form display\n";
}

echo "\n";
echo "=== PHASE 1 COMPLETED ===\n";
echo "Đã bổ sung:\n";
echo "  • 4 vocabularies mới (Industry, Contact Status, Org Status, Tags)\n";
echo "  • 5 fields cho Contact (avatar, status, tags, last_contacted, linkedin)\n";
echo "  • 4 fields cho Deal (contract_file, lost_reason, notes, expected_close_date)\n";
echo "  • 4 fields cho Organization (industry, status, employees_count, annual_revenue)\n";
echo "  • 1 field cho Activity (contact_ref)\n";
echo "  • Form displays đã được cấu hình\n";
echo "\nChạy 'ddev drush cr' để clear cache!\n";
