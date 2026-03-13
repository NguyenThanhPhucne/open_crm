<?php

/**
 * Test loading a contact node and checking soft-delete field
 */

$nid = 24; // From previous test
$node = \Drupal\node\Entity\Node::load($nid);

if (!$node) {
  echo "❌ Could not load node $nid\n";
  return;
}

echo "✅ Loaded contact: {$node->label()}\n";
echo "   Type: {$node->bundle()}\n";

// Check if field_deleted_at exists and has a value
if ($node->hasField('field_deleted_at')) {
  echo "✅ field_deleted_at field exists\n";
  $value = $node->get('field_deleted_at')->value;
  echo "   Value: " . ($value ? "Deleted at $value" : "NULL (active)") . "\n";
} else {
  echo "⚠️  field_deleted_at field does not exist on this node\n";
}

// Check email field
if ($node->hasField('field_email')) {
  echo "✅ field_email exists\n";
  $email = $node->get('field_email')->value;
  echo "   Email: " . ($email ?? "EMPTY") . "\n";
} else {
  echo "⚠️  field_email does not exist\n";
}

echo "\n✅ Node loading works correctly\n";
