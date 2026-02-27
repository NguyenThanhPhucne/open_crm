#!/bin/bash

# Update homepage with new Quick Access page

ddev drush ev '
$html = file_get_contents("/var/www/html/lucide_icons_page.html");
$node = \Drupal::entityTypeManager()->getStorage("node")->load(23);
if ($node) {
  $node->body->value = $html;
  $node->body->format = "full_html";
  $node->save();
  echo "✅ Updated homepage (Node 23) with new Quick Access links\n";
  echo "   - Pipeline: /crm/pipeline (Kanban board)\n";
  echo "   - Import: /crm/import (CSV import)\n";
  echo "   - Updated icons and colors\n";
} else {
  echo "❌ Node 23 not found\n";
}
'
