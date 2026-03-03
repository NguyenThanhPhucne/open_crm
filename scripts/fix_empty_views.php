<?php

/**
 * Create sample data for users who have empty views
 * Fix: manager và salesrep2 bị empty pages
 */

use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║       CREATING SAMPLE DATA FOR USERS WITH EMPTY VIEWS          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════
// MANAGER (uid=2) - 0 contacts, 0 deals
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Creating data for MANAGER (uid=2)...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Contacts for manager
$manager_contacts = [
  [
    'title' => 'Enterprise Client Alpha',
    'phone' => '+84911111111',
    'email' => 'contact@alpha-corp.vn',
    'position' => 'CEO',
  ],
  [
    'title' => 'Beta Corporation Contact',
    'phone' => '+84911111112',
    'email' => 'info@beta-corp.vn',
    'position' => 'CTO',
  ],
];

foreach ($manager_contacts as $data) {
  $contact = Node::create([
    'type' => 'contact',
    'title' => $data['title'],
    'field_owner' => 2, // manager
    'field_phone' => $data['phone'],
    'field_email' => $data['email'],
    'field_position' => $data['position'],
    'field_status' => 'active',
    'status' => 1,
    'uid' => 2,
  ]);
  $contact->save();
  echo "  ✅ Created contact: {$data['title']} (ID: {$contact->id()})\n";
}

// Deals for manager
// Stage term IDs: 1=New, 2=Qualified, 3=Proposal, 4=Negotiation, 5=Won, 6=Lost
$manager_deals = [
  [
    'title' => 'Enterprise Deal - Alpha Corp',
    'amount' => 150000,
    'stage' => 2, // Qualified
    'probability' => 50,
  ],
  [
    'title' => 'Strategic Partnership - Beta Corp',
    'amount' => 200000,
    'stage' => 3, // Proposal
    'probability' => 70,
  ],
];

foreach ($manager_deals as $data) {
  $deal = Node::create([
    'type' => 'deal',
    'title' => $data['title'],
    'field_owner' => 2, // manager
    'field_amount' => $data['amount'],
    'field_stage' => $data['stage'],
    'field_probability' => $data['probability'],
    'field_closing_date' => (new DrupalDateTime('+30 days'))->format('Y-m-d'),
    'field_status' => 'active',
    'status' => 1,
    'uid' => 2,
  ]);
  $deal->save();
  echo "  ✅ Created deal: {$data['title']} (ID: {$deal->id()})\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// SALESREP2 (uid=4) - 1 contact, 0 deals
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Creating data for SALESREP2 (uid=4)...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Additional contacts for salesrep2
$sr2_contacts = [
  [
    'title' => 'Small Business Owner',
    'phone' => '+84922222221',
    'email' => 'owner@smallbiz.vn',
    'position' => 'Owner',
  ],
];

foreach ($sr2_contacts as $data) {
  $contact = Node::create([
    'type' => 'contact',
    'title' => $data['title'],
    'field_owner' => 4, // salesrep2
    'field_phone' => $data['phone'],
    'field_email' => $data['email'],
    'field_position' => $data['position'],
    'field_status' => 'active',
    'status' => 1,
    'uid' => 4,
  ]);
  $contact->save();
  echo "  ✅ Created contact: {$data['title']} (ID: {$contact->id()})\n";
}

// Deals for salesrep2
// Stage term IDs: 1=New, 2=Qualified, 3=Proposal, 4=Negotiation, 5=Won, 6=Lost
$sr2_deals = [
  [
    'title' => 'SMB Package Deal',
    'amount' => 25000,
    'stage' => 1, // New
    'probability' => 30,
  ],
  [
    'title' => 'Starter Plan - Small Biz',
    'amount' => 15000,
    'stage' => 2, // Qualified
    'probability' => 60,
  ],
];

foreach ($sr2_deals as $data) {
  $deal = Node::create([
    'type' => 'deal',
    'title' => $data['title'],
    'field_owner' => 4, // salesrep2
    'field_amount' => $data['amount'],
    'field_stage' => $data['stage'],
    'field_probability' => $data['probability'],
    'field_closing_date' => (new DrupalDateTime('+45 days'))->format('Y-m-d'),
    'field_status' => 'active',
    'status' => 1,
    'uid' => 4,
  ]);
  $deal->save();
  echo "  ✅ Created deal: {$data['title']} (ID: {$deal->id()})\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════
// VERIFICATION
// ═══════════════════════════════════════════════════════════════

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "VERIFICATION - Data counts after creation\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$users_to_check = [
  1 => 'admin',
  2 => 'manager',
  3 => 'salesrep1',
  4 => 'salesrep2',
];

foreach ($users_to_check as $uid => $name) {
  $contact_count = \Drupal::entityQuery('node')
    ->condition('type', 'contact')
    ->condition('field_owner', $uid)
    ->condition('status', 1)
    ->accessCheck(FALSE)
    ->count()
    ->execute();
    
  $deal_count = \Drupal::entityQuery('node')
    ->condition('type', 'deal')
    ->condition('field_owner', $uid)
    ->condition('status', 1)
    ->accessCheck(FALSE)
    ->count()
    ->execute();
    
  $activity_count = \Drupal::entityQuery('node')
    ->condition('type', 'activity')
    ->condition('field_assigned_to', $uid)
    ->condition('status', 1)
    ->accessCheck(FALSE)
    ->count()
    ->execute();
  
  $status = ($contact_count > 0 && $deal_count > 0 && $activity_count > 0) ? '✅' : '⚠️ ';
  
  echo "{$status} {$name}: {$contact_count} contacts, {$deal_count} deals, {$activity_count} activities\n";
}

echo "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ COMPLETED! All users now have data for their views.\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📝 TESTING:\n";
echo "   1. Login as manager → Visit /crm/my-contacts (should see 2)\n";
echo "   2. Login as manager → Visit /crm/my-pipeline (should see 2)\n";
echo "   3. Login as salesrep2 → Visit /crm/my-pipeline (should see 2)\n";
echo "   4. All views should now have data!\n\n";
