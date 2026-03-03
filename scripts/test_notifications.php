<?php

/**
 * Test CRM Notifications - Simulate email scenarios
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║         TEST: CRM Email Notifications                    ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Get the email service
$email_service = \Drupal::service('crm_notifications.email_service');
$current_user = \Drupal::currentUser();

echo "[1/5] Loading test data...\n";

// Get a sample deal
$deal_query = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'deal')
  ->condition('status', 1)
  ->range(0, 1);
$deal_nids = $deal_query->execute();

if (empty($deal_nids)) {
  echo "  ✗ No deals found. Please create a deal first.\n";
  exit(1);
}

$deal = \Drupal::entityTypeManager()->getStorage('node')->load(reset($deal_nids));
echo "  ✓ Loaded deal: {$deal->getTitle()}\n";

// Get a sample contact
$contact_query = \Drupal::entityQuery('node')
  ->accessCheck(FALSE)
  ->condition('type', 'contact')
  ->condition('status', 1)
  ->range(0, 1);
$contact_nids = $contact_query->execute();

if (empty($contact_nids)) {
  echo "  ✗ No contacts found. Please create a contact first.\n";
  exit(1);
}

$contact = \Drupal::entityTypeManager()->getStorage('node')->load(reset($contact_nids));
echo "  ✓ Loaded contact: {$contact->getTitle()}\n";

// Get test user (current user or user 1)
$user = \Drupal::entityTypeManager()->getStorage('user')->load($current_user->id());
if (!$user || !$user->getEmail()) {
  $user = \Drupal::entityTypeManager()->getStorage('user')->load(1);
}

if (!$user || !$user->getEmail()) {
  echo "  ✗ No user with email found\n";
  exit(1);
}

echo "  ✓ Test recipient: {$user->getDisplayName()} ({$user->getEmail()})\n";

echo "\n[2/5] Testing New Deal Assigned notification...\n";
$result1 = $email_service->sendNewDealAssigned($deal, $user);
echo $result1 ? "  ✓ Email sent\n" : "  ✗ Email failed\n";

echo "\n[3/5] Testing Deal Won notification...\n";
$result2 = $email_service->sendDealWon($deal, $user);
echo $result2 ? "  ✓ Email sent\n" : "  ✗ Email failed\n";

echo "\n[4/5] Testing Contact Reassigned notification...\n";
$result3 = $email_service->sendContactReassigned($contact, $user);
echo $result3 ? "  ✓ Email sent\n" : "  ✗ Email failed\n";

echo "\n[5/5] Testing Deal Closing Soon reminder...\n";
$result4 = $email_service->sendDealClosingSoonReminder($deal, $user);
echo $result4 ? "  ✓ Email sent\n" : "  ✗ Email failed\n";

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║              TEST RESULTS SUMMARY                         ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";

$total = 4;
$passed = ($result1 ? 1 : 0) + ($result2 ? 1 : 0) + ($result3 ? 1 : 0) + ($result4 ? 1 : 0);

echo "║  Total tests: $total                                           ║\n";
echo "║  Passed: $passed                                               ║\n";
echo "║  Failed: " . ($total - $passed) . "                                               ║\n";

if ($passed === $total) {
  echo "╠═══════════════════════════════════════════════════════════╣\n";
  echo "║  ✅ ALL TESTS PASSED                                      ║\n";
} else {
  echo "╠═══════════════════════════════════════════════════════════╣\n";
  echo "║  ⚠️  SOME TESTS FAILED                                     ║\n";
  echo "║  Check maillog or recent log messages                    ║\n";
}

echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "NOTE: Emails are queued by Drupal's mail system.\n";
echo "Check your mail configuration at /admin/config/system/site-information\n";
echo "For development, install maillog or devel modules to capture emails.\n\n";

echo "INSTALL MAILLOG (Recommended for testing):\n";
echo "  ddev composer require drupal/maillog\n";
echo "  ddev drush en maillog -y\n";
echo "  ddev drush cset maillog.settings send false -y\n";
echo "  View emails at: /admin/reports/maillog\n\n";
