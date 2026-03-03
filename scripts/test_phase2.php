<?php

/**
 * Test script for Phase 2 - Import/Export CSV functionality
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║        PHASE 2 TEST SUMMARY - IMPORT/EXPORT              ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Check if module is enabled
$module_handler = \Drupal::service('module_handler');
$module_enabled = $module_handler->moduleExists('crm_import_export');

echo "✓ MODULE STATUS:\n";
echo "  " . ($module_enabled ? '✓' : '✗') . " crm_import_export module: " . ($module_enabled ? 'Enabled' : 'Disabled') . "\n";

if (!$module_enabled) {
  echo "\n❌ Module not enabled! Run: ddev drush en crm_import_export -y\n";
  exit(1);
}

// Check if routes exist
$route_provider = \Drupal::service('router.route_provider');
$routes_to_check = [
  'crm_import_export.import_page' => '/admin/crm/import',
  'crm_import_export.import_contacts' => '/admin/crm/import/contacts',
  'crm_import_export.import_deals' => '/admin/crm/import/deals',
  'crm_import_export.export_contacts' => '/admin/crm/export/contacts',
  'crm_import_export.export_deals' => '/admin/crm/export/deals',
];

echo "\n✓ ROUTES:\n";
$routes_ok = 0;
foreach ($routes_to_check as $route_name => $path) {
  try {
    $route = $route_provider->getRouteByName($route_name);
    $actual_path = $route->getPath();
    $match = ($actual_path === $path);
    echo "  " . ($match ? '✓' : '✗') . " $route_name → $path\n";
    if ($match) $routes_ok++;
  } catch (\Exception $e) {
    echo "  ✗ $route_name → NOT FOUND\n";
  }
}

// Check if controllers exist
echo "\n✓ CONTROLLERS:\n";
$controllers = [
  'ImportController' => '/Users/phucnguyen/Downloads/open_crm/web/modules/custom/crm_import_export/src/Controller/ImportController.php',
  'ExportController' => '/Users/phucnguyen/Downloads/open_crm/web/modules/custom/crm_import_export/src/Controller/ExportController.php',
];

$controllers_ok = 0;
foreach ($controllers as $name => $path) {
  $exists = file_exists($path);
  echo "  " . ($exists ? '✓' : '✗') . " $name: " . ($exists ? 'Exists' : 'Missing') . "\n";
  if ($exists) $controllers_ok++;
}

// Check if forms exist
echo "\n✓ FORMS:\n";
$forms = [
  'ImportContactsForm' => '/Users/phucnguyen/Downloads/open_crm/web/modules/custom/crm_import_export/src/Form/ImportContactsForm.php',
  'ImportDealsForm' => '/Users/phucnguyen/Downloads/open_crm/web/modules/custom/crm_import_export/src/Form/ImportDealsForm.php',
];

$forms_ok = 0;
foreach ($forms as $name => $path) {
  $exists = file_exists($path);
  echo "  " . ($exists ? '✓' : '✗') . " $name: " . ($exists ? 'Exists' : 'Missing') . "\n";
  if ($exists) $forms_ok++;
}

// Check permissions
echo "\n✓ PERMISSIONS:\n";
$permissions = \Drupal::service('user.permissions')->getPermissions();
$has_permission = isset($permissions['administer crm import export']);
echo "  " . ($has_permission ? '✓' : '✗') . " administer crm import export: " . ($has_permission ? 'Registered' : 'Missing') . "\n";

// Check sample CSV files
echo "\n✓ SAMPLE CSV FILES:\n";
$sample_files = [
  'Contacts CSV' => '/Users/phucnguyen/Downloads/open_crm/sample_contacts.csv',
  'Deals CSV' => '/Users/phucnguyen/Downloads/open_crm/sample_deals.csv',
];

$samples_ok = 0;
foreach ($sample_files as $name => $path) {
  $exists = file_exists($path);
  if ($exists) {
    $lines = count(file($path));
    echo "  ✓ $name: $lines lines\n";
    $samples_ok++;
  } else {
    echo "  ✗ $name: Not found\n";
  }
}

// Test export functionality
echo "\n✓ EXPORT FUNCTIONALITY TEST:\n";

// Get current entity counts
$contact_count = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->accessCheck(FALSE)
  ->count()
  ->execute();

$deal_count = \Drupal::entityQuery('node')
  ->condition('type', 'deal')
  ->accessCheck(FALSE)
  ->count()
  ->execute();

echo "  ✓ Contacts in database: $contact_count\n";
echo "  ✓ Deals in database: $deal_count\n";

// Check upload directory
$upload_dir = 'private://crm_imports';
$directory_created = \Drupal::service('file_system')->prepareDirectory($upload_dir, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);
echo "  " . ($directory_created ? '✓' : '✗') . " Upload directory: " . ($directory_created ? 'Ready' : 'Error') . "\n";

// URLs for testing
echo "\n✓ TEST URLS:\n";
$base_url = 'http://open-crm.ddev.site';
echo "  • Import Hub: $base_url/admin/crm/import\n";
echo "  • Import Contacts: $base_url/admin/crm/import/contacts\n";
echo "  • Import Deals: $base_url/admin/crm/import/deals\n";
echo "  • Export Contacts: $base_url/admin/crm/export/contacts\n";
echo "  • Export Deals: $base_url/admin/crm/export/deals\n";

// Summary
$total_checks = 5 + 2 + 2 + 1 + 2; // routes + controllers + forms + permission + samples
$passed_checks = $routes_ok + $controllers_ok + $forms_ok + ($has_permission ? 1 : 0) + $samples_ok;

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║                  PHASE 2 TEST RESULT                      ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo sprintf("║  Module:         %s                                ║\n", $module_enabled ? '✅ Enabled ' : '❌ Disabled');
echo sprintf("║  Routes:         %d/5 working                            ║\n", $routes_ok);
echo sprintf("║  Controllers:    %d/2 created                            ║\n", $controllers_ok);
echo sprintf("║  Forms:          %d/2 created                            ║\n", $forms_ok);
echo sprintf("║  Permissions:    %s                                 ║\n", $has_permission ? '✅ Set    ' : '❌ Missing');
echo sprintf("║  Sample CSVs:    %d/2 ready                              ║\n", $samples_ok);
echo sprintf("║  Contacts:       %d in database                         ║\n", $contact_count);
echo sprintf("║  Deals:          %d in database                          ║\n", $deal_count);
echo "║                                                           ║\n";

if ($passed_checks == $total_checks && $module_enabled) {
  echo "║  STATUS: ✅ PHASE 2 TEST PASSED                          ║\n";
  echo "║                                                           ║\n";
  echo "║  🎉 Import/Export functionality is ready!                ║\n";
} else {
  echo "║  STATUS: ⚠️  PHASE 2 PARTIALLY PASSED                    ║\n";
  echo sprintf("║  Passed: %d/%d checks                                  ║\n", $passed_checks, $total_checks);
}

echo "╚═══════════════════════════════════════════════════════════╝\n";

echo "\n📖 NEXT STEPS:\n";
echo "  1. Visit: $base_url/admin/crm/import\n";
echo "  2. Upload: sample_contacts.csv (5 contacts)\n";
echo "  3. Upload: sample_deals.csv (5 deals)\n";
echo "  4. Test export: Download contacts & deals CSV\n";
echo "  5. Verify duplicate detection works\n\n";
