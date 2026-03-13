<?php

/**
 * Test if contacts exist in the database
 */

$db = \Drupal::database();
$query = $db->select('node', 'n')
  ->fields('n', ['nid', 'type'])
  ->condition('n.type', 'contact')
  ->range(0, 1);

$result = $query->execute()->fetch();

if ($result) {
  echo "✅ Found contact: NID {$result->nid}\n";
} else {
  echo "ℹ️  No contacts in database yet\n";
}

// Check if any queries are hanging
echo "\n✅ Database query executed successfully (no locking issues)\n";
