<?php

/**
 * Create test activity for admin user
 */

use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;

// Bootstrap Drupal
require_once '/var/www/html/web/core/includes/bootstrap.inc';
require_once '/var/www/html/vendor/autoload.php';

$kernel = \Drupal\Core\DrupalKernel::createFromRequest(
  \Symfony\Component\HttpFoundation\Request::createFromGlobals(),
  \Drupal\Core\Autoloader\ClassLoader::findByClass()
);

$kernel->boot();
$container = $kernel->getContainer();
$container->get('request_stack')
  ->push(\Symfony\Component\HttpFoundation\Request::createFromGlobals());

echo "🎯 Creating test activity for admin...\n\n";

// Create activity for admin
$activity = Node::create([
  'type' => 'activity',
  'title' => 'Admin Overview Meeting',
  'field_activity_type' => 'meeting',
  'field_assigned_to' => 1, // admin
  'field_activity_date' => (new DrupalDateTime('now'))->format('Y-m-d\TH:i:s'),
  'field_description' => [
    'value' => 'Monthly overview meeting to review system performance and team activities.',
    'format' => 'basic_html',
  ],
  'status' => 1,
  'uid' => 1, // Created by admin
]);

$activity->save();

echo "✅ Activity created successfully!\n";
echo "   ID: " . $activity->id() . "\n";
echo "   Title: " . $activity->getTitle() . "\n";
echo "   Assigned to: admin (uid=1)\n";
echo "   Type: Meeting\n\n";

// Create another activity for admin
$activity2 = Node::create([
  'type' => 'activity',
  'title' => 'System Maintenance Check',
  'field_activity_type' => 'task',
  'field_assigned_to' => 1, // admin
  'field_activity_date' => (new DrupalDateTime('+1 day'))->format('Y-m-d\TH:i:s'),
  'field_description' => [
    'value' => 'Regular system maintenance and security updates.',
    'format' => 'basic_html',
  ],
  'status' => 1,
  'uid' => 1,
]);

$activity2->save();

echo "✅ Second activity created!\n";
echo "   ID: " . $activity2->id() . "\n";
echo "   Title: " . $activity2->getTitle() . "\n";
echo "   Assigned to: admin (uid=1)\n";
echo "   Type: Task\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🎉 DONE! Admin now has 2 activities.\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📝 Next steps:\n";
echo "   1. Login as admin: http://open-crm.ddev.site/user/login\n";
echo "   2. Go to: http://open-crm.ddev.site/crm/my-activities\n";
echo "   3. You should see 2 activities now!\n\n";

// Verify
$query = \Drupal::entityQuery('node')
  ->condition('type', 'activity')
  ->condition('field_assigned_to', 1)
  ->condition('status', 1)
  ->accessCheck(FALSE);

$nids = $query->execute();

echo "✅ Verification: Admin now has " . count($nids) . " activities\n";
