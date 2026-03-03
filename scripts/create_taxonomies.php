<?php

/**
 * Create additional CRM taxonomies
 * Run with: ddev drush scr scripts/create_taxonomies.php
 */

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\Entity\Term;

echo "=== Creating CRM Taxonomies ===\n\n";

// 1. Create Lead Source vocabulary (crm_source)
$vid = 'crm_source';
$name = 'CRM: Nguồn khách hàng';
if (!Vocabulary::load($vid)) {
  $vocabulary = Vocabulary::create([
    'vid' => $vid,
    'name' => $name,
    'description' => 'Nguồn khách hàng (Lead Source)',
  ]);
  $vocabulary->save();
  echo "✅ Created vocabulary: $name\n";
} else {
  echo "⚠️  Vocabulary $vid already exists\n";
}

// Add terms for Lead Source
$terms = ['Website', 'Referral', 'Event', 'Cold Call', 'Social Media', 'Email Campaign'];
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($terms as $term_name) {
  $existing = $storage->loadByProperties(['name' => $term_name, 'vid' => $vid]);
  if (empty($existing)) {
    $term = $storage->create([
      'vid' => $vid,
      'name' => $term_name,
    ]);
    $term->save();
    echo "  ✓ Created term: $term_name\n";
  }
}

echo "\n";

// 2. Create Industry vocabulary (crm_industry)
$vid = 'crm_industry';
$name = 'CRM: Ngành nghề';
if (!Vocabulary::load($vid)) {
  $vocabulary = Vocabulary::create([
    'vid' => $vid,
    'name' => $name,
    'description' => 'Ngành nghề của tổ chức',
  ]);
  $vocabulary->save();
  echo "✅ Created vocabulary: $name\n";
} else {
  echo "⚠️  Vocabulary $vid already exists\n";
}

// Add terms for Industry
$terms = [
  'Technology/CNTT',
  'Finance/Tài chính',
  'Healthcare/Y tế',
  'Education/Giáo dục',
  'Retail/Bán lẻ',
  'Manufacturing/Sản xuất',
  'Real Estate/Bất động sản',
  'Consulting/Tư vấn',
  'Marketing/Quảng cáo',
  'Logistics/Vận tải'
];
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($terms as $term_name) {
  $existing = $storage->loadByProperties(['name' => $term_name, 'vid' => $vid]);
  if (empty($existing)) {
    $term = $storage->create([
      'vid' => $vid,
      'name' => $term_name,
    ]);
    $term->save();
    echo "  ✓ Created term: $term_name\n";
  }
}

echo "\n";

// 3. Create Customer Type vocabulary (crm_customer_type)
$vid = 'crm_customer_type';
$name = 'CRM: Phân loại khách hàng';
if (!Vocabulary::load($vid)) {
  $vocabulary = Vocabulary::create([
    'vid' => $vid,
    'name' => $name,
    'description' => 'Phân loại khách hàng (VIP, Mới, Tiềm năng)',
  ]);
  $vocabulary->save();
  echo "✅ Created vocabulary: $name\n";
} else {
  echo "⚠️  Vocabulary $vid already exists\n";
}

// Add terms for Customer Type
$terms = ['VIP', 'Khách hàng mới', 'Tiềm năng', 'Đang theo dõi', 'Khách hàng cũ'];
$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach ($terms as $term_name) {
  $existing = $storage->loadByProperties(['name' => $term_name, 'vid' => $vid]);
  if (empty($existing)) {
    $term = $storage->create([
      'vid' => $vid,
      'name' => $term_name,
    ]);
    $term->save();
    echo "  ✓ Created term: $term_name\n";
  }
}

echo "\n✅ Taxonomies setup completed!\n";
