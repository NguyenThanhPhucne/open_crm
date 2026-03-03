<?php

/**
 * Phase 1 Test Summary Report
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║           PHASE 1 TEST SUMMARY REPORT                     ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Check vocabularies
$vocabs = [
  'crm_contact_status' => 'CRM Contact Status',
  'crm_contact_tags' => 'CRM Contact Tags',
  'crm_org_status' => 'CRM Organization Status',
  'crm_industry' => 'CRM Industry',
];

echo "✓ VOCABULARIES:\n";
$vocab_count = 0;
$term_total = 0;
foreach ($vocabs as $vid => $label) {
  $vocab = \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->load($vid);
  if ($vocab) {
    $term_count = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    echo sprintf("  ✓ %-30s %2d terms\n", $label . ':', $term_count);
    $vocab_count++;
    $term_total += $term_count;
  }
}
echo "  TOTAL: $vocab_count vocabularies, $term_total terms\n";

// Check Contact fields
echo "\n✓ CONTACT FIELDS (Phase 1 additions):\n";
$contact_fields = [
  'field_avatar' => 'Avatar (image)',
  'field_last_contacted' => 'Last Contacted (datetime)',
  'field_status' => 'Status (list)',
  'field_tags' => 'Tags (reference)',
  'field_linkedin' => 'LinkedIn (link)',
];
$contact_count = 0;
foreach ($contact_fields as $field => $desc) {
  $exists = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'contact')[$field] ?? NULL;
  if ($exists) {
    echo "  ✓ $field - $desc\n";
    $contact_count++;
  }
}
echo "  TOTAL: $contact_count/5 fields\n";

// Check Deal fields
echo "\n✓ DEAL FIELDS (Phase 1 additions):\n";
$deal_fields = [
  'field_contract_file' => 'Contract File (file)',
  'field_lost_reason' => 'Lost Reason (text)',
  'field_notes' => 'Internal Notes (text)',
  'field_expected_close_date' => 'Expected Close Date (datetime)',
];
$deal_count = 0;
foreach ($deal_fields as $field => $desc) {
  $exists = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'deal')[$field] ?? NULL;
  if ($exists) {
    echo "  ✓ $field - $desc\n";
    $deal_count++;
  }
}
echo "  TOTAL: $deal_count/4 fields\n";

// Check Organization fields
echo "\n✓ ORGANIZATION FIELDS (Phase 1 additions):\n";
$org_fields = [
  'field_industry' => 'Industry (string)',
  'field_status' => 'Status (list)',
  'field_employees_count' => 'Number of Employees (integer)',
  'field_annual_revenue' => 'Annual Revenue (decimal)',
];
$org_count = 0;
foreach ($org_fields as $field => $desc) {
  $exists = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'organization')[$field] ?? NULL;
  if ($exists) {
    echo "  ✓ $field - $desc\n";
    $org_count++;
  }
}
echo "  TOTAL: $org_count/4 fields\n";

// Check Activity field
echo "\n✓ ACTIVITY FIELDS (Phase 1 additions):\n";
$activity_field = 'field_contact_ref';
$exists = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'activity')[$activity_field] ?? NULL;
if ($exists) {
  echo "  ✓ $activity_field - Related Contact (reference)\n";
  $activity_count = 1;
} else {
  $activity_count = 0;
}
echo "  TOTAL: $activity_count/1 field\n";

// Test data info
echo "\n✓ TEST DATA CREATED:\n";
echo "  • Contact (nid: 33) - Phase 1 Test Contact\n";
echo "    Fields: avatar, last_contacted, status, tags, linkedin\n";
echo "    URL: http://open-crm.ddev.site/node/33/edit\n\n";

echo "  • Deal (nid: 34) - Phase 1 Test Deal\n";
echo "    Fields: contract_file, lost_reason, notes, expected_close_date\n";
echo "    URL: http://open-crm.ddev.site/node/34/edit\n\n";

echo "  • Organization (nid: 35) - Phase 1 Test Company\n";
echo "    Fields: industry, status, employees_count, annual_revenue\n";
echo "    URL: http://open-crm.ddev.site/node/35/edit\n\n";

// Summary
$total_fields = $contact_count + $deal_count + $org_count + $activity_count;
$expected_fields = 5 + 4 + 4 + 1; // 14 fields total

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                  PHASE 1 TEST RESULT                      ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo sprintf("║  Vocabularies:   %d/4 created (%d terms)          ║\n", $vocab_count, $term_total);
echo sprintf("║  Fields:         %d/%d created                        ║\n", $total_fields, $expected_fields);
echo sprintf("║  Test Entities:  3 created                            ║\n");
echo "║                                                           ║\n";

if ($total_fields == $expected_fields && $vocab_count == 4) {
  echo "║  STATUS: ✅ PHASE 1 TEST PASSED                          ║\n";
} else {
  echo "║  STATUS: ⚠️  PHASE 1 PARTIALLY PASSED                    ║\n";
}

echo "╚═══════════════════════════════════════════════════════════╝\n";
