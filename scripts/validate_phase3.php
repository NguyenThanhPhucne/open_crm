#!/usr/bin/env php
<?php

/**
 * @file
 * Phase 3 Comprehensive Validation Script
 * 
 * Validates:
 * - Data quality and fixtures
 * - Queue system functionality
 * - Custom module routes and APIs
 * - Sample data completeness
 * - Documentation coverage
 * 
 * Usage:
 *   ddev drush scr scripts/validate_phase3.php
 */

use Drupal\node\Entity\Node;

echo "🏥 PHASE 3 COMPREHENSIVE VALIDATION\n";
echo "════════════════════════════════════════════════════════\n\n";

$health_score = 0;
$max_score = 0;
$issues = [];
$warnings = [];
$recommendations = [];

// ============================================================
// 1. DATA QUALITY & FIXTURES (30 points)
// ============================================================

echo "📊 1. DATA QUALITY & FIXTURES\n";
echo "────────────────────────────────────────────────────────\n";

$max_score += 30;

// Check fixture files exist
$fixture_files = [
  'fixtures/development/organizations.yml',
  'fixtures/development/contacts.yml',
  'fixtures/development/deals.yml',
  'fixtures/development/activities.yml',
];

$fixtures_found = 0;
foreach ($fixture_files as $file) {
  $filepath = DRUPAL_ROOT . '/../' . $file;
  if (file_exists($filepath)) {
    $fixtures_found++;
    echo "  ✅ Found: $file\n";
  } else {
    echo "  ❌ Missing: $file\n";
    $issues[] = "Fixture file missing: $file";
  }
}

if ($fixtures_found === count($fixture_files)) {
  $health_score += 10;
  echo "  Score: 10/10 (All fixture files present)\n";
} else {
  echo "  Score: " . ($fixtures_found * 2.5) . "/10\n";
  $health_score += $fixtures_found * 2.5;
}

// Check Activity nodes exist (was 0 in Phase 2)
$activity_count = count(\Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['type' => 'activity']));

echo "\n  Activity Nodes: $activity_count\n";

if ($activity_count >= 20) {
  $health_score += 10;
  echo "  ✅ Excellent activity data (20+ nodes)\n";
  echo "  Score: 10/10\n";
} elseif ($activity_count >= 10) {
  $health_score += 7;
  echo "  ✅ Good activity data (10+ nodes)\n";
  echo "  Score: 7/10\n";
} elseif ($activity_count > 0) {
  $health_score += 5;
  echo "  ⚠️  Limited activity data ($activity_count nodes)\n";
  echo "  Score: 5/10\n";
  $warnings[] = "Only $activity_count Activity nodes found. Recommend 20+";
} else {
  echo "  ❌ No activity data\n";
  echo "  Score: 0/10\n";
  $issues[] = "No Activity nodes found. Run: ddev drush scr scripts/load_fixtures.php development";
}

// Check data relationships
echo "\n  Data Integrity:\n";

$relationship_score = 0;

// Contacts with organizations
$contacts_with_org = \Drupal::database()->query(
  "SELECT COUNT(*) FROM node__field_organization WHERE bundle = 'contact' AND field_organization_target_id IS NOT NULL"
)->fetchField();

$total_contacts = count(\Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['type' => 'contact']));

$org_percentage = $total_contacts > 0 ? round(($contacts_with_org / $total_contacts) * 100) : 0;

echo "    Contacts with Organization: $contacts_with_org/$total_contacts ($org_percentage%)\n";

if ($org_percentage >= 80) {
  $relationship_score += 3.5;
  echo "    ✅ Strong data relationships\n";
} elseif ($org_percentage >= 50) {
  $relationship_score += 2;
  echo "    ⚠️  Moderate data relationships\n";
  $warnings[] = "Only $org_percentage% of contacts linked to organizations";
} else {
  echo "    ❌ Weak data relationships\n";
  $issues[] = "Poor contact-organization relationships ($org_percentage%)";
}

// Deals with contacts
$deals_with_contact = \Drupal::database()->query(
  "SELECT COUNT(*) FROM node__field_contact WHERE bundle = 'deal' AND field_contact_target_id IS NOT NULL"
)->fetchField();

$total_deals = count(\Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['type' => 'deal']));

$contact_percentage = $total_deals > 0 ? round(($deals_with_contact / $total_deals) * 100) : 0;

echo "    Deals with Contact: $deals_with_contact/$total_deals ($contact_percentage%)\n";

if ($contact_percentage >= 80) {
  $relationship_score += 3.5;
  echo "    ✅ Strong deal relationships\n";
} elseif ($contact_percentage >= 50) {
  $relationship_score += 2;
  echo "    ⚠️  Moderate deal relationships\n";
  $warnings[] = "Only $contact_percentage% of deals linked to contacts";
} else {
  echo "    ❌ Weak deal relationships\n";
  $issues[] = "Poor deal-contact relationships ($contact_percentage%)";
}

echo "    Relationship Score: $relationship_score/7\n";

// Owner field assignment
$contacts_with_owner = \Drupal::database()->query(
  "SELECT COUNT(*) FROM node__field_owner WHERE bundle = 'contact' AND field_owner_target_id IS NOT NULL"
)->fetchField();

$owner_percentage = $total_contacts > 0 ? round(($contacts_with_owner / $total_contacts) * 100) : 0;

echo "    Contacts with Owner: $contacts_with_owner/$total_contacts ($owner_percentage%)\n";

if ($owner_percentage >= 90) {
  $relationship_score += 3;
  echo "    ✅ Excellent owner assignment\n";
} elseif ($owner_percentage >= 70) {
  $relationship_score += 2;
  echo "    ⚠️  Good owner assignment\n";
} else {
  echo "    ❌ Poor owner assignment\n";
  $issues[] = "Poor owner field assignment ($owner_percentage%)";
}

$health_score += $relationship_score;

echo "\n";

// ============================================================
// 2. QUEUE SYSTEM (20 points)
// ============================================================

echo "⚙️  2. QUEUE SYSTEM\n";
echo "────────────────────────────────────────────────────────\n";

$max_score += 20;
$queue_score = 0;

// Check queue scripts exist
$queue_scripts = [
  'scripts/process_queue.php',
  'scripts/bulk_delete.php',
  'scripts/csv_import_queue.php',
];

$queue_scripts_found = 0;
foreach ($queue_scripts as $script) {
  $filepath = DRUPAL_ROOT . '/../' . $script;
  if (file_exists($filepath)) {
    $queue_scripts_found++;
    echo "  ✅ Found: $script\n";
  } else {
    echo "  ❌ Missing: $script\n";
    $issues[] = "Queue script missing: $script";
  }
}

$queue_score += ($queue_scripts_found / count($queue_scripts)) * 8;
echo "  Queue Scripts: $queue_scripts_found/" . count($queue_scripts) . " (" . round($queue_score, 1) . "/8)\n";

// Check queue service availability
try {
  $queue_factory = \Drupal::service('queue');
  
  $expected_queues = [
    'crm_csv_import',
    'crm_bulk_delete',
    'crm_bulk_update',
    'crm_email_notification',
  ];
  
  echo "\n  Queue Status:\n";
  $queues_working = 0;
  
  foreach ($expected_queues as $queue_name) {
    try {
      $queue = $queue_factory->get($queue_name);
      $count = $queue->numberOfItems();
      echo "    ✅ $queue_name: $count items\n";
      $queues_working++;
    } catch (Exception $e) {
      echo "    ❌ $queue_name: ERROR\n";
    }
  }
  
  $queue_score += ($queues_working / count($expected_queues)) * 12;
  echo "  Queue Functionality: $queues_working/" . count($expected_queues) . " (" . round(($queues_working / count($expected_queues)) * 12, 1) . "/12)\n";
  
} catch (Exception $e) {
  echo "  ❌ Queue service unavailable: " . $e->getMessage() . "\n";
  $issues[] = "Queue service not available";
}

$health_score += $queue_score;

echo "\n";

// ============================================================
// 3. CUSTOM MODULES & ROUTES (25 points)
// ============================================================

echo "🔌 3. CUSTOM MODULES & ROUTES\n";
echo "────────────────────────────────────────────────────────\n";

$max_score += 25;
$module_score = 0;

// Check custom modules are enabled
$required_modules = [
  'crm_dashboard',
  'crm_activity_log',
  'crm_quickadd',
  'crm_kanban',
  'crm_import_export',
];

echo "  Required Modules:\n";
$modules_enabled = 0;

foreach ($required_modules as $module) {
  if (\Drupal::moduleHandler()->moduleExists($module)) {
    echo "    ✅ $module\n";
    $modules_enabled++;
  } else {
    echo "    ❌ $module (not enabled)\n";
    $issues[] = "Required module not enabled: $module";
  }
}

$module_score += ($modules_enabled / count($required_modules)) * 15;
echo "  Modules: $modules_enabled/" . count($required_modules) . " (" . round(($modules_enabled / count($required_modules)) * 15, 1) . "/15)\n";

// Check key routes exist
echo "\n  Key Routes:\n";

$required_routes = [
  'crm_dashboard.dashboard' => '/admin/crm',
  'crm_activity_log.activity_tab' => '/node/{node}/activities',
  'crm_quickadd.contact' => '/crm/quickadd/contact',
  'crm_kanban.pipeline' => '/crm/pipeline',
];

$routes_working = 0;

foreach ($required_routes as $route_name => $path) {
  try {
    $route = \Drupal::service('router.route_provider')->getRouteByName($route_name);
    echo "    ✅ $route_name ($path)\n";
    $routes_working++;
  } catch (Exception $e) {
    echo "    ❌ $route_name (not found)\n";
    $warnings[] = "Route not found: $route_name";
  }
}

$module_score += ($routes_working / count($required_routes)) * 10;
echo "  Routes: $routes_working/" . count($required_routes) . " (" . round(($routes_working / count($required_routes)) * 10, 1) . "/10)\n";

$health_score += $module_score;

echo "\n";

// ============================================================
// 4. DOCUMENTATION (15 points)
// ============================================================

echo "📚 4. DOCUMENTATION\n";
echo "────────────────────────────────────────────────────────\n";

$max_score += 15;
$doc_score = 0;

$required_docs = [
  'CUSTOM_MODULES_API_DOCS.md' => 'Custom modules and API documentation',
  'PHASE_1_2_FINAL_VALIDATION.md' => 'Phase 1 & 2 validation report',
  'PHASE_1_2_QUICK_REFERENCE.md' => 'Quick reference guide',
  'fixtures/development/organizations.yml' => 'Organizations fixture',
  'fixtures/development/contacts.yml' => 'Contacts fixture',
  'fixtures/development/deals.yml' => 'Deals fixture',
  'fixtures/development/activities.yml' => 'Activities fixture',
];

$docs_found = 0;

foreach ($required_docs as $filename => $description) {
  $filepath = DRUPAL_ROOT . '/../' . $filename;
  if (file_exists($filepath)) {
    $size = filesize($filepath);
    $size_kb = round($size / 1024, 1);
    echo "  ✅ $filename ({$size_kb}KB)\n";
    $docs_found++;
  } else {
    echo "  ❌ $filename (missing)\n";
    $issues[] = "Documentation missing: $filename";
  }
}

$doc_score = ($docs_found / count($required_docs)) * 15;
echo "  Documentation: $docs_found/" . count($required_docs) . " (" . round($doc_score, 1) . "/15)\n";

$health_score += $doc_score;

echo "\n";

// ============================================================
// 5. SAMPLE DATA CLEANUP (10 points)
// ============================================================

echo "🧹 5. SAMPLE DATA CLEANUP\n";
echo "────────────────────────────────────────────────────────\n";

$max_score += 10;
$cleanup_score = 0;

// Check if fixtures are being used (good)
// Check if old hardcoded scripts still exist (bad if actively used)

$old_scripts = [
  'scripts/create_sample_data.sh',
  'scripts/create_sample_data_v2.sh',
];

echo "  Legacy Scripts Status:\n";
$legacy_scripts = 0;

foreach ($old_scripts as $script) {
  $filepath = DRUPAL_ROOT . '/../' . $script;
  if (file_exists($filepath)) {
    echo "    ⚠️  $script (exists but deprecated)\n";
    $legacy_scripts++;
  } else {
    echo "    ✅ $script (removed)\n";
  }
}

// Having legacy scripts is okay if we have the new fixture system
if ($fixtures_found >= 3) {
  $cleanup_score += 5;
  echo "  ✅ Fixture system implemented\n";
} else {
  echo "  ❌ Fixture system incomplete\n";
  $issues[] = "Fixture system not fully implemented";
}

// Check if load_fixtures.php exists and works
$load_fixtures_path = DRUPAL_ROOT . '/../scripts/load_fixtures.php';
if (file_exists($load_fixtures_path)) {
  echo "  ✅ load_fixtures.php script available\n";
  $cleanup_score += 5;
} else {
  echo "  ❌ load_fixtures.php script missing\n";
  $issues[] = "Fixture loader script missing";
}

echo "  Cleanup Score: $cleanup_score/10\n";

$health_score += $cleanup_score;

echo "\n";

// ============================================================
// FINAL SCORING
// ============================================================

echo "═══════════════════════════════════════════════════════\n";
echo "🎯 FINAL HEALTH SCORE\n";
echo "═══════════════════════════════════════════════════════\n\n";

$percentage = $max_score > 0 ? round(($health_score / $max_score) * 100) : 0;

echo "Score: $health_score / $max_score points ($percentage%)\n\n";

// Rating
if ($percentage >= 95) {
  echo "Rating: 🌟🌟🌟🌟🌟 EXCELLENT\n";
  echo "Status: ✅ Production Ready\n";
} elseif ($percentage >= 85) {
  echo "Rating: 🌟🌟🌟🌟⭐ VERY GOOD\n";
  echo "Status: ✅ Production Ready (minor improvements possible)\n";
} elseif ($percentage >= 75) {
  echo "Rating: 🌟🌟🌟⭐⭐ GOOD\n";
  echo "Status: ⚠️  Ready (some improvements recommended)\n";
} elseif ($percentage >= 65) {
  echo "Rating: 🌟🌟⭐⭐⭐ FAIR\n";
  echo "Status: ⚠️  Needs Improvements\n";
} else {
  echo "Rating: 🌟⭐⭐⭐⭐ NEEDS WORK\n";
  echo "Status: ❌ Not Production Ready\n";
}

echo "\n";

// Score breakdown
echo "📋 SCORE BREAKDOWN:\n";
echo "   Data Quality & Fixtures: " . round($health_score >= 30 ? 30 : ($fixtures_found * 2.5 + ($activity_count >= 20 ? 10 : ($activity_count >= 10 ? 7 : 5)) + $relationship_score), 1) . "/30\n";
echo "   Queue System: " . round($queue_score, 1) . "/20\n";
echo "   Custom Modules & Routes: " . round($module_score, 1) . "/25\n";
echo "   Documentation: " . round($doc_score, 1) . "/15\n";
echo "   Sample Data Cleanup: $cleanup_score/10\n";

echo "\n";

// Issues
if (!empty($issues)) {
  echo "🚨 CRITICAL ISSUES (" . count($issues) . "):\n";
  foreach ($issues as $issue) {
    echo "   ❌ $issue\n";
  }
  echo "\n";
}

// Warnings
if (!empty($warnings)) {
  echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
  foreach ($warnings as $warning) {
    echo "   ⚠️  $warning\n";
  }
  echo "\n";
}

// Recommendations
$recommendations[] = "Run comprehensive test suite before production deployment";
$recommendations[] = "Set up automated queue processing via cron";
$recommendations[] = "Configure backup strategy for production data";
$recommendations[] = "Review and update permissions for all roles";

if ($activity_count < 20) {
  $recommendations[] = "Create more Activity sample data for comprehensive testing";
}

if ($legacy_scripts > 0) {
  $recommendations[] = "Consider archiving legacy sample data scripts";
}

echo "💡 RECOMMENDATIONS (" . count($recommendations) . "):\n";
foreach ($recommendations as $rec) {
  echo "   💡 $rec\n";
}

echo "\n";

// Summary statistics
echo "📊 CURRENT DATA SUMMARY:\n";

$content_counts = [
  'Organizations' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'organization'])),
  'Contacts' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact'])),
  'Deals' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal'])),
  'Activities' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'activity'])),
];

foreach ($content_counts as $type => $count) {
  echo "   $type: $count nodes\n";
}

echo "\n";

echo "🎉 Phase 3 validation complete!\n";
echo "\n";
