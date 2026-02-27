#!/bin/bash

echo "📦 TẠO SAMPLE DATA VỚI OWNER FIELDS - PHIÊN BẢN FIX"
echo "===================================================="
echo ""

# Lấy User IDs
echo "👥 Lấy User IDs..."
ADMIN_UID=$(ddev drush eval "echo \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'admin'])[1]->id();")
MANAGER_UID=$(ddev drush eval "\$u = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'manager']); echo reset(\$u) ? reset(\$u)->id() : 1;")
SALESREP1_UID=$(ddev drush eval "\$u = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'salesrep1']); echo reset(\$u) ? reset(\$u)->id() : 1;")
SALESREP2_UID=$(ddev drush eval "\$u = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => 'salesrep2']); echo reset(\$u) ? reset(\$u)->id() : 1;")

echo "   Admin: $ADMIN_UID"
echo "   Manager: $MANAGER_UID"
echo "   SalesRep1: $SALESREP1_UID"
echo "   SalesRep2: $SALESREP2_UID"
echo ""

# Lấy Taxonomy Term IDs
echo "🏷️  Lấy Taxonomy Term IDs..."
ddev drush eval "
\$terms = [
  'deal_stage' => ['Prospect', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost'],
  'activity_type' => ['Call', 'Email', 'Meeting', 'Task'],
  'lead_source' => ['Website', 'Referral', 'Event', 'Call', 'Social Media'],
  'industry' => ['Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing'],
];

\$term_ids = [];
foreach (\$terms as \$vocab => \$names) {
  foreach (\$names as \$name) {
    \$terms_loaded = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => \$vocab, 'name' => \$name]);
    if (\$term = reset(\$terms_loaded)) {
      \$term_ids[\$vocab][\$name] = \$term->id();
    }
  }
}

file_put_contents('/tmp/crm_term_ids.json', json_encode(\$term_ids));
echo '✅ Saved term IDs' . PHP_EOL;
"

echo ""

# Tạo Organizations
echo "🏢 Tạo Organizations với field_owner..."
ddev drush eval "
\$term_ids = json_decode(file_get_contents('/tmp/crm_term_ids.json'), true);

\$organizations = [
  ['title' => 'Acme Corporation', 'website' => 'https://acme.com', 'address' => '123 Main St, New York, NY', 'industry' => 'Technology', 'owner' => $SALESREP1_UID],
  ['title' => 'Global Enterprises', 'website' => 'https://global-ent.com', 'address' => '456 Market St, San Francisco, CA', 'industry' => 'Finance', 'owner' => $SALESREP1_UID],
  ['title' => 'Tech Solutions Inc', 'website' => 'https://techsolutions.com', 'address' => '789 Innovation Dr, Austin, TX', 'industry' => 'Technology', 'owner' => $SALESREP2_UID],
  ['title' => 'Retail Plus', 'website' => 'https://retailplus.com', 'address' => '321 Commerce Blvd, Chicago, IL', 'industry' => 'Retail', 'owner' => $SALESREP2_UID],
];

\$org_ids = [];
foreach (\$organizations as \$data) {
  \$node = \Drupal\node\Entity\Node::create([
    'type' => 'organization',
    'title' => \$data['title'],
    'field_website' => ['uri' => \$data['website']],
    'field_address' => \$data['address'],
    'field_industry' => ['target_id' => \$term_ids['industry'][\$data['industry']] ?? null],
    'field_owner' => ['target_id' => \$data['owner']],
    'status' => 1,
  ]);
  \$node->save();
  \$org_ids[\$data['title']] = \$node->id();
  echo '✅ Created: ' . \$data['title'] . ' (Owner UID: ' . \$data['owner'] . ')' . PHP_EOL;
}

file_put_contents('/tmp/crm_org_ids.json', json_encode(\$org_ids));
"

echo ""

# Tạo Contacts
echo "👥 Tạo Contacts với field_owner..."
ddev drush eval "
\$org_ids = json_decode(file_get_contents('/tmp/crm_org_ids.json'), true);
\$term_ids = json_decode(file_get_contents('/tmp/crm_term_ids.json'), true);

\$contacts = [
  ['title' => 'John Smith', 'email' => 'john.smith@acme.com', 'phone' => '+1-555-0101', 'position' => 'CEO', 'org' => 'Acme Corporation', 'source' => 'Website', 'owner' => $SALESREP1_UID],
  ['title' => 'Sarah Johnson', 'email' => 'sarah.j@global-ent.com', 'phone' => '+1-555-0102', 'position' => 'CTO', 'org' => 'Global Enterprises', 'source' => 'Referral', 'owner' => $SALESREP1_UID],
  ['title' => 'Mike Davis', 'email' => 'mike.d@techsolutions.com', 'phone' => '+1-555-0103', 'position' => 'VP Sales', 'org' => 'Tech Solutions Inc', 'source' => 'Event', 'owner' => $SALESREP2_UID],
  ['title' => 'Emily Brown', 'email' => 'emily.brown@acme.com', 'phone' => '+1-555-0104', 'position' => 'Marketing Director', 'org' => 'Acme Corporation', 'source' => 'Call', 'owner' => $SALESREP1_UID],
  ['title' => 'David Wilson', 'email' => 'david.w@retailplus.com', 'phone' => '+1-555-0105', 'position' => 'Operations Manager', 'org' => 'Retail Plus', 'source' => 'Referral', 'owner' => $SALESREP2_UID],
];

\$contact_ids = [];
foreach (\$contacts as \$data) {
  \$node = \Drupal\node\Entity\Node::create([
    'type' => 'contact',
    'title' => \$data['title'],
    'field_email' => \$data['email'],
    'field_phone' => \$data['phone'],
    'field_position' => \$data['position'],
    'field_organization' => ['target_id' => \$org_ids[\$data['org']] ?? null],
    'field_lead_source' => ['target_id' => \$term_ids['lead_source'][\$data['source']] ?? null],
    'field_owner' => ['target_id' => \$data['owner']],
    'status' => 1,
  ]);
  \$node->save();
  \$contact_ids[\$data['title']] = \$node->id();
  echo '✅ Created: ' . \$data['title'] . ' (' . \$data['position'] . ', Owner UID: ' . \$data['owner'] . ')' . PHP_EOL;
}

file_put_contents('/tmp/crm_contact_ids.json', json_encode(\$contact_ids));
"

echo ""

# Tạo Deals
echo "💼 Tạo Deals với field_owner..."
ddev drush eval "
\$org_ids = json_decode(file_get_contents('/tmp/crm_org_ids.json'), true);
\$contact_ids = json_decode(file_get_contents('/tmp/crm_contact_ids.json'), true);
\$term_ids = json_decode(file_get_contents('/tmp/crm_term_ids.json'), true);

\$deals = [
  ['title' => 'Enterprise Software License', 'amount' => 150000, 'probability' => 75, 'stage' => 'Proposal', 'contact' => 'John Smith', 'org' => 'Acme Corporation', 'closing_date' => '2026-04-15', 'owner' => $SALESREP1_UID],
  ['title' => 'Cloud Migration Project', 'amount' => 250000, 'probability' => 60, 'stage' => 'Negotiation', 'contact' => 'Sarah Johnson', 'org' => 'Global Enterprises', 'closing_date' => '2026-05-20', 'owner' => $SALESREP1_UID],
  ['title' => 'Annual Support Contract', 'amount' => 50000, 'probability' => 90, 'stage' => 'Won', 'contact' => 'Mike Davis', 'org' => 'Tech Solutions Inc', 'closing_date' => '2026-03-01', 'owner' => $SALESREP2_UID],
  ['title' => 'Consulting Services', 'amount' => 75000, 'probability' => 50, 'stage' => 'Qualified', 'contact' => 'Emily Brown', 'org' => 'Acme Corporation', 'closing_date' => '2026-06-30', 'owner' => $SALESREP1_UID],
  ['title' => 'Retail POS System', 'amount' => 120000, 'probability' => 65, 'stage' => 'Proposal', 'contact' => 'David Wilson', 'org' => 'Retail Plus', 'closing_date' => '2026-07-15', 'owner' => $SALESREP2_UID],
];

\$deal_ids = [];
foreach (\$deals as \$data) {
  \$node = \Drupal\node\Entity\Node::create([
    'type' => 'deal',
    'title' => \$data['title'],
    'field_amount' => \$data['amount'],
    'field_probability' => \$data['probability'],
    'field_stage' => ['target_id' => \$term_ids['deal_stage'][\$data['stage']] ?? null],
    'field_contact' => ['target_id' => \$contact_ids[\$data['contact']] ?? null],
    'field_organization' => ['target_id' => \$org_ids[\$data['org']] ?? null],
    'field_closing_date' => \$data['closing_date'],
    'field_owner' => ['target_id' => \$data['owner']],
    'status' => 1,
  ]);
  \$node->save();
  \$deal_ids[\$data['title']] = \$node->id();
  echo '✅ Created: ' . \$data['title'] . ' ($' . number_format(\$data['amount']) . ', Owner UID: ' . \$data['owner'] . ')' . PHP_EOL;
}

file_put_contents('/tmp/crm_deal_ids.json', json_encode(\$deal_ids));
"

echo ""

# Tạo Activities
echo "📅 Tạo Activities với field_assigned_to..."
ddev drush eval "
\$contact_ids = json_decode(file_get_contents('/tmp/crm_contact_ids.json'), true);
\$deal_ids = json_decode(file_get_contents('/tmp/crm_deal_ids.json'), true);
\$term_ids = json_decode(file_get_contents('/tmp/crm_term_ids.json'), true);

\$activities = [
  ['title' => 'Initial discovery call', 'type' => 'Call', 'contact' => 'John Smith', 'deal' => 'Enterprise Software License', 'datetime' => '2026-02-20T10:00:00', 'description' => 'Discussed business needs and software requirements', 'assigned' => $SALESREP1_UID],
  ['title' => 'Follow-up email sent', 'type' => 'Email', 'contact' => 'Sarah Johnson', 'deal' => 'Cloud Migration Project', 'datetime' => '2026-02-22T14:30:00', 'description' => 'Sent proposal document and pricing details', 'assigned' => $SALESREP1_UID],
  ['title' => 'Demo meeting completed', 'type' => 'Meeting', 'contact' => 'Mike Davis', 'deal' => 'Annual Support Contract', 'datetime' => '2026-02-25T15:00:00', 'description' => 'Product demonstration and Q&A session', 'assigned' => $SALESREP2_UID],
  ['title' => 'Contract review task', 'type' => 'Task', 'contact' => 'Emily Brown', 'deal' => 'Consulting Services', 'datetime' => '2026-02-28T09:00:00', 'description' => 'Review and finalize consulting agreement', 'assigned' => $SALESREP1_UID],
  ['title' => 'Site visit scheduled', 'type' => 'Meeting', 'contact' => 'David Wilson', 'deal' => 'Retail POS System', 'datetime' => '2026-03-05T13:00:00', 'description' => 'On-site assessment of retail locations', 'assigned' => $SALESREP2_UID],
];

foreach (\$activities as \$data) {
  \$node = \Drupal\node\Entity\Node::create([
    'type' => 'activity',
    'title' => \$data['title'],
    'field_activity_type' => ['target_id' => \$term_ids['activity_type'][\$data['type']] ?? null],
    'field_contact' => ['target_id' => \$contact_ids[\$data['contact']] ?? null],
    'field_deal' => ['target_id' => \$deal_ids[\$data['deal']] ?? null],
    'field_datetime' => \$data['datetime'],
    'field_description' => ['value' => \$data['description'], 'format' => 'plain_text'],
    'field_assigned_to' => ['target_id' => \$data['assigned']],
    'status' => 1,
  ]);
  \$node->save();
  echo '✅ Created: ' . \$data['title'] . ' (' . \$data['type'] . ', Assigned UID: ' . \$data['assigned'] . ')' . PHP_EOL;
}
"

echo ""

# Summary
echo "📊 SUMMARY:"
ddev drush eval "
\$counts = [
  'Organizations' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'organization'])),
  'Contacts' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact'])),
  'Deals' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal'])),
  'Activities' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'activity'])),
];

foreach (\$counts as \$type => \$count) {
  echo '   ' . \$type . ': ' . \$count . PHP_EOL;
}
"

echo ""
echo "✅ HOÀN THÀNH! Sample data đã được tạo với owner fields."
echo ""
echo "📌 DATA ASSIGNMENT:"
echo "   SalesRep1 owns: Acme Corporation, Global Enterprises"
echo "   SalesRep2 owns: Tech Solutions Inc, Retail Plus"
echo "   => Contacts/Deals/Activities được phân theo owner"
echo ""
echo "🎯 TIẾP THEO:"
echo "   1. Login as salesrep1 → Chỉ thấy data của mình"
echo "   2. Login as manager → Thấy tất cả data"
echo "   3. Verify views filters hoạt động đúng"
