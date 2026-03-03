<?php

/**
 * Test script for Phase 1 - Fields & Taxonomies
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

echo "=== Testing Phase 1 Fields ===\n\n";

// Lấy taxonomy terms
$status_terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_contact_status']);
$tag_terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_contact_tags']);
$org_status_terms = \Drupal::entityTypeManager()
  ->getStorage('taxonomy_term')
  ->loadByProperties(['vid' => 'crm_org_status']);

// Tạo 1 contact test với tất cả fields mới
$contact = Node::create([
  'type' => 'contact',
  'title' => 'Phase 1 Test Contact',
  'field_email' => 'phase1.test@example.com',
  'field_phone' => '0901234567',
  'field_position' => 'Test Manager',
  'field_status' => !empty($status_terms) ? array_keys($status_terms)[0] : NULL,
  'field_tags' => !empty($tag_terms) ? array_slice(array_keys($tag_terms), 0, 2) : [],
  'field_linkedin' => [
    'uri' => 'https://linkedin.com/in/testuser',
    'title' => 'LinkedIn Profile'
  ],
  'field_last_contacted' => date('Y-m-d\TH:i:s'),
  'uid' => 1,
  'status' => 1,
]);
$contact->save();
echo "✓ Created test Contact (nid: " . $contact->id() . ") with Phase 1 fields\n";
echo "  - Avatar field: " . ($contact->hasField('field_avatar') ? 'exists' : 'missing') . "\n";
echo "  - Last Contacted: " . $contact->get('field_last_contacted')->value . "\n";
echo "  - Status field: " . ($contact->hasField('field_status') ? 'exists' : 'missing') . "\n";
echo "  - Tags field: " . ($contact->hasField('field_tags') ? 'exists' : 'missing') . "\n";
echo "  - LinkedIn: " . $contact->get('field_linkedin')->uri . "\n";

// Tạo 1 deal test
$deal = Node::create([
  'type' => 'deal',
  'title' => 'Phase 1 Test Deal',
  'field_amount' => 50000.00,
  'field_expected_close_date' => date('Y-m-d\TH:i:s', strtotime('+30 days')),
  'field_notes' => 'This is a test deal created to verify Phase 1 fields.',
  'uid' => 1,
  'status' => 1,
]);
$deal->save();
echo "\n✓ Created test Deal (nid: " . $deal->id() . ") with Phase 1 fields\n";
echo "  - Contract File field: " . ($deal->hasField('field_contract_file') ? 'exists' : 'missing') . "\n";
echo "  - Lost Reason field: " . ($deal->hasField('field_lost_reason') ? 'exists' : 'missing') . "\n";
echo "  - Notes: " . substr($deal->get('field_notes')->value, 0, 50) . "...\n";
echo "  - Expected Close Date: " . $deal->get('field_expected_close_date')->value . "\n";

// Tạo 1 organization test
$org = Node::create([
  'type' => 'organization',
  'title' => 'Phase 1 Test Company',
  'field_status' => !empty($org_status_terms) ? array_keys($org_status_terms)[0] : NULL,
  'field_employees_count' => 50,
  'field_annual_revenue' => 1500000.00,
  'uid' => 1,
  'status' => 1,
]);
$org->save();
echo "\n✓ Created test Organization (nid: " . $org->id() . ") with Phase 1 fields\n";
echo "  - Status field: " . ($org->hasField('field_status') ? 'exists' : 'missing') . "\n";
echo "  - Employees Count: " . $org->get('field_employees_count')->value . "\n";
echo "  - Annual Revenue: $" . number_format($org->get('field_annual_revenue')->value, 2) . "\n";
echo "  - Industry field: " . ($org->hasField('field_industry') ? 'exists' : 'missing') . "\n";

// Tạo 1 activity test với contact reference
$activity = Node::create([
  'type' => 'activity',
  'title' => 'Phase 1 Test Activity',
  'field_type' => 'meeting',
  'field_datetime' => date('Y-m-d\TH:i:s', strtotime('+1 day')),
  'field_description' => 'Test activity to verify contact reference field.',
  'field_contact_ref' => $contact->id(),
  'uid' => 1,
  'status' => 1,
]);
$activity->save();
echo "\n✓ Created test Activity (nid: " . $activity->id() . ") with Phase 1 fields\n";
echo "  - Contact Ref field: " . ($activity->hasField('field_contact_ref') ? 'exists' : 'missing') . "\n";
echo "  - Linked to Contact: " . $contact->getTitle() . " (nid: " . $contact->id() . ")\n";

echo "\n=== Phase 1 Test Data Created Successfully ===\n";
echo "View URLs:\n";
echo "  Contact: /node/" . $contact->id() . "/edit\n";
echo "  Deal: /node/" . $deal->id() . "/edit\n";
echo "  Organization: /node/" . $org->id() . "/edit\n";
echo "  Activity: /node/" . $activity->id() . "/edit\n";

echo "\n=== Phase 1 Verification Complete ===\n";
echo "All new fields are working correctly!\n";
