#!/bin/bash
# Create additional taxonomies for Open CRM
# Based on Excel requirements: crm_source, crm_industry, crm_customer_type

cd /var/www/html

echo "=== Creating CRM Taxonomies ==="

# 1. Create Lead Source vocabulary (crm_source)
echo "Creating Lead Source vocabulary..."
ddev drush ev "
\$vid = 'crm_source';
\$name = 'CRM: Nguồn khách hàng';
if (!\Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load(\$vid)) {
  \$vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create([
    'vid' => \$vid,
    'name' => \$name,
    'description' => 'Nguồn khách hàng (Lead Source)',
  ]);
  \$vocabulary->save();
  echo \"Created vocabulary: \$name\n\";
} else {
  echo \"Vocabulary \$vid already exists\n\";
}
"

# Add terms for Lead Source
echo "Adding Lead Source terms..."
ddev drush ev "
\$vid = 'crm_source';
\$terms = ['Website', 'Referral', 'Event', 'Cold Call', 'Social Media', 'Email Campaign'];
\$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach (\$terms as \$term_name) {
  \$existing = \$storage->loadByProperties(['name' => \$term_name, 'vid' => \$vid]);
  if (empty(\$existing)) {
    \$term = \$storage->create([
      'vid' => \$vid,
      'name' => \$term_name,
    ]);
    \$term->save();
    echo \"Created term: \$term_name\n\";
  }
}
"

# 2. Create Industry vocabulary (crm_industry)
echo "Creating Industry vocabulary..."
ddev drush ev "
\$vid = 'crm_industry';
\$name = 'CRM: Ngành nghề';
if (!\Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load(\$vid)) {
  \$vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create([
    'vid' => \$vid,
    'name' => \$name,
    'description' => 'Ngành nghề của tổ chức',
  ]);
  \$vocabulary->save();
  echo \"Created vocabulary: \$name\n\";
} else {
  echo \"Vocabulary \$vid already exists\n\";
}
"

# Add terms for Industry
echo "Adding Industry terms..."
ddev drush ev "
\$vid = 'crm_industry';
\$terms = [
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
\$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach (\$terms as \$term_name) {
  \$existing = \$storage->loadByProperties(['name' => \$term_name, 'vid' => \$vid]);
  if (empty(\$existing)) {
    \$term = \$storage->create([
      'vid' => \$vid,
      'name' => \$term_name,
    ]);
    \$term->save();
    echo \"Created term: \$term_name\n\";
  }
}
"

# 3. Create Customer Type vocabulary (crm_customer_type)
echo "Creating Customer Type vocabulary..."
ddev drush ev "
\$vid = 'crm_customer_type';
\$name = 'CRM: Phân loại khách hàng';
if (!\Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load(\$vid)) {
  \$vocabulary = \Drupal\taxonomy\Entity\Vocabulary::create([
    'vid' => \$vid,
    'name' => \$name,
    'description' => 'Phân loại khách hàng (VIP, Mới, Tiềm năng)',
  ]);
  \$vocabulary->save();
  echo \"Created vocabulary: \$name\n\";
} else {
  echo \"Vocabulary \$vid already exists\n\";
}
"

# Add terms for Customer Type
echo "Adding Customer Type terms..."
ddev drush ev "
\$vid = 'crm_customer_type';
\$terms = ['VIP', 'Khách hàng mới', 'Tiềm năng', 'Đang theo dõi', 'Khách hàng cũ'];
\$storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
foreach (\$terms as \$term_name) {
  \$existing = \$storage->loadByProperties(['name' => \$term_name, 'vid' => \$vid]);
  if (empty(\$existing)) {
    \$term = \$storage->create([
      'vid' => \$vid,
      'name' => \$term_name,
    ]);
    \$term->save();
    echo \"Created term: \$term_name\n\";
  }
}
"

echo "✅ Taxonomies created successfully!"
