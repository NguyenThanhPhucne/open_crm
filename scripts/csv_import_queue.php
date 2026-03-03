#!/usr/bin/env php
<?php

/**
 * @file
 * CSV Import Queue Manager
 * 
 * Import contacts and deals from CSV files using queue system.
 * Prevents timeout on large imports.
 * 
 * Usage:
 *   ddev drush scr scripts/csv_import_queue.php contacts sample_contacts.csv
 *   ddev drush scr scripts/csv_import_queue.php deals sample_deals.csv
 */

echo "📥 CSV IMPORT QUEUE MANAGER\n";
echo "════════════════════════════════════════════════════════\n\n";

$type = $args[0] ?? null;
$filename = $args[1] ?? null;

if (!$type || !$filename) {
  echo "❌ Usage: ddev drush scr scripts/csv_import_queue.php <type> <filename>\n";
  echo "\nType: contacts, deals, organizations\n";
  echo "Filename: CSV file in workspace root\n";
  exit(1);
}

if (!in_array($type, ['contacts', 'deals', 'organizations'])) {
  echo "❌ Invalid type: $type\n";
  echo "   Valid types: contacts, deals, organizations\n";
  exit(1);
}

$filepath = DRUPAL_ROOT . '/../' . $filename;

if (!file_exists($filepath)) {
  echo "❌ File not found: $filepath\n";
  exit(1);
}

echo "📂 File: $filename\n";
echo "📋 Type: $type\n";
echo "\n";

// Read CSV file
echo "🔍 Reading CSV file...\n";

$handle = fopen($filepath, 'r');
$headers = fgetcsv($handle);
$rows = [];

while (($row = fgetcsv($handle)) !== FALSE) {
  if (count($row) === count($headers)) {
    $rows[] = array_combine($headers, $row);
  }
}

fclose($handle);

echo "   Found " . count($rows) . " rows\n";
echo "   Headers: " . implode(', ', $headers) . "\n";
echo "\n";

if (empty($rows)) {
  echo "✅ No data to import\n";
  exit(0);
}

// Add to queue
echo "📤 Adding to import queue...\n";

$queue = \Drupal::service('queue')->get('crm_csv_import');
$batch_size = 25;
$batches = array_chunk($rows, $batch_size);

foreach ($batches as $batch_index => $batch_rows) {
  $queue->createItem([
    'operation' => 'import_' . $type,
    'type' => $type,
    'rows' => $batch_rows,
    'batch' => $batch_index + 1,
    'total_batches' => count($batches),
    'filename' => $filename,
  ]);
}

echo "✅ Added " . count($batches) . " batches to queue\n";
echo "   Batch size: $batch_size rows per batch\n";
echo "   Total rows: " . count($rows) . "\n";
echo "\n";
echo "💡 Process the queue:\n";
echo "   ddev drush scr scripts/process_queue.php crm_csv_import\n";
echo "\n";
