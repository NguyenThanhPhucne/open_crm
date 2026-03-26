<?php
$n = \Drupal\node\Entity\Node::load(146);
$b = $n->get('body')->value;

// Replace the duplicate Blue color with a unique Rose color for the Export Data card
$b = str_replace(
  '<a href="/admin/content/export" class="qac-card" style="--cc:#3b82f6;--ci:#eff6ff;">',
  '<a href="/admin/content/export" class="qac-card" style="--cc:#f43f5e;--ci:#fff1f2;">',
  $b
);

$n->set('body', ['value' => $b, 'format' => 'full_html']);
$n->save();
echo "Export Data card color updated.";
