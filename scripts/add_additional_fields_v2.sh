#!/bin/bash
# Add additional CRM fields based on Excel requirements
# Run this to add field_source, field_customer_type, field_industry, field_probability, field_contract

echo "=== Adding Additional CRM Fields from Excel Requirements ==="

# 1. Add field_source to Contact (Lead Source)
echo ""
echo "1. Adding field_source (Nguồn khách hàng) to Contact..."
ddev drush field:create node.contact.field_source \
  --field-label="Nguồn khách hàng" \
  --field-type=entity_reference \
  --target-type=taxonomy_term \
  --cardinality=1 \
  --required=false 2>&1 | grep -v "already exists" || echo "   ✅ Field created/exists"

# Configure field_source handler settings
ddev drush ev "
\$field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'contact', 'field_source');
if (\$field_config) {
  \$handler_settings = [
    'target_bundles' => ['crm_source' => 'crm_source'],
    'auto_create' => FALSE,
  ];
  \$field_config->setSetting('handler_settings', \$handler_settings);
  \$field_config->setDescription('Nguồn khách hàng (Website, Referral, Event, Cold Call, etc.)');
  \$field_config->save();
  echo \"   ✅ Configured to use crm_source vocabulary\n\";
}
"

# 2. Add field_customer_type to Contact
echo ""
echo "2. Adding field_customer_type (Phân loại KH) to Contact..."
ddev drush field:create node.contact.field_customer_type \
  --field-label="Phân loại khách hàng" \
  --field-type=entity_reference \
  --target-type=taxonomy_term \
  --cardinality=1 \
  --required=false 2>&1 | grep -v "already exists" || echo "   ✅ Field created/exists"

# Configure field_customer_type handler settings
ddev drush ev "
\$field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'contact', 'field_customer_type');
if (\$field_config) {
  \$handler_settings = [
    'target_bundles' => ['crm_customer_type' => 'crm_customer_type'],
    'auto_create' => FALSE,
  ];
  \$field_config->setSetting('handler_settings', \$handler_settings);
  \$field_config->setDescription('VIP, Khách hàng mới, Tiềm năng, Đang theo dõi, Khách hàng cũ');
  \$field_config->save();
  echo \"   ✅ Configured to use crm_customer_type vocabulary\n\";
}
"

# 3. Add field_industry to Organization
echo ""
echo "3. Adding field_industry (Ngành nghề) to Organization..."
ddev drush field:create node.organization.field_industry \
  --field-label="Ngành nghề" \
  --field-type=entity_reference \
  --target-type=taxonomy_term \
  --cardinality=1 \
  --required=false 2>&1 | grep -v "already exists" || echo "   ✅ Field created/exists"

# Configure field_industry handler settings
ddev drush ev "
\$field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'organization', 'field_industry');
if (\$field_config) {
  \$handler_settings = [
    'target_bundles' => ['crm_industry' => 'crm_industry'],
    'auto_create' => FALSE,
  ];
  \$field_config->setSetting('handler_settings', \$handler_settings);
  \$field_config->setDescription('Ngành nghề của tổ chức (Technology, Finance, Healthcare, etc.)');
  \$field_config->save();
  echo \"   ✅ Configured to use crm_industry vocabulary\n\";
}
"

# 4. Add field_probability to Deal
echo ""
echo "4. Adding field_probability (Khả năng chốt %) to Deal..."
ddev drush field:create node.deal.field_probability \
  --field-label="Khả năng chốt (%)" \
  --field-type=integer \
  --cardinality=1 \
  --required=false 2>&1 | grep -v "already exists" || echo "   ✅ Field created/exists"

# Configure field_probability settings
ddev drush ev "
\$field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'deal', 'field_probability');
if (\$field_config) {
  \$field_config->setSetting('min', 0);
  \$field_config->setSetting('max', 100);
  \$field_config->setDefaultValue([['value' => 50]]);
  \$field_config->setDescription('Khả năng chốt deal (0-100%). Tự động cập nhật theo Stage: New=10%, Qualified=25%, Proposal=50%, Negotiation=75%, Won=100%, Lost=0%.');
  \$field_config->save();
  echo \"   ✅ Configured with range 0-100%, default 50%\n\";
}
"

# 5. Add field_contract to Deal (File field)
echo ""
echo "5. Adding field_contract (Hợp đồng/File) to Deal..."
ddev drush field:create node.deal.field_contract \
  --field-label="Hợp đồng/File đính kèm" \
  --field-type=file \
  --cardinality=-1 \
  --required=false 2>&1 | grep -v "already exists" || echo "   ✅ Field created/exists"

# Configure field_contract file settings
ddev drush ev "
\$field_config = \Drupal\field\Entity\FieldConfig::loadByName('node', 'deal', 'field_contract');
if (\$field_config) {
  \$field_config->setSetting('file_extensions', 'pdf doc docx xls xlsx ppt pptx txt zip');
  \$field_config->setSetting('file_directory', 'crm/contracts/[date:custom:Y]-[date:custom:m]');
  \$field_config->setSetting('max_filesize', '10 MB');
  \$field_config->setDescription('Upload hợp đồng, báo giá, hoặc tài liệu đính kèm. Chấp nhận: PDF, DOC, XLS, PPT, TXT, ZIP. Tối đa 10MB.');
  \$field_config->save();
  echo \"   ✅ Configured: PDF/DOC/XLS/PPT/TXT/ZIP, max 10MB\n\";
}
"

# Clear cache
echo ""
echo "Clearing cache..."
ddev drush cr

echo ""
echo "✅ All additional fields have been added successfully!"
echo ""
echo "📋 Summary:"
echo "   Contact:"
echo "     • field_source → Nguồn khách hàng (crm_source taxonomy)"
echo "     • field_customer_type → Phân loại KH (crm_customer_type taxonomy)"
echo ""
echo "   Organization:"
echo "     • field_industry → Ngành nghề (crm_industry taxonomy)"
echo ""
echo "   Deal:"
echo "     • field_probability → Khả năng chốt 0-100%"
echo "     • field_contract → File hợp đồng/đính kèm"
echo ""
