#!/bin/bash

###############################################################################
# Script: enable_layout_builder_simple.sh
# Purpose: Enable Layout Builder for Deal with default layout (VIEW-05)
# Task: Enable Layout Builder to allow custom 2-column layouts
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Enabling Layout Builder for Deal"
echo "========================================="

ddev drush php-eval '
$entity_type = "node";
$bundle = "deal";

// Load the entity view display
$display = \Drupal::entityTypeManager()
  ->getStorage("entity_view_display")
  ->load("$entity_type.$bundle.default");

if (!$display) {
  echo "Creating display configuration...\n";
  $display = \Drupal\Core\Entity\Entity\EntityViewDisplay::create([
    "targetEntityType" => $entity_type,
    "bundle" => $bundle,
    "mode" => "default",
    "status" => TRUE,
  ]);
}

// Enable Layout Builder with custom layouts allowed
$display->setThirdPartySetting("layout_builder", "enabled", TRUE);
$display->setThirdPartySetting("layout_builder", "allow_custom", TRUE);

$display->save();
echo "✅ Layout Builder enabled for Deal content type\n";
echo "✅ Custom layouts allowed for each Deal\n";
'

echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ Layout Builder Enabled!"
echo "========================================="
echo ""
echo "📋 To create the 2-column layout:"
echo "   1. Go to: http://open-crm.ddev.site/admin/structure/types/manage/deal/display"
echo "   2. Click \"Manage layout\" button"
echo "   3. Add a \"Two column\" section"
echo "   4. Left column: Add Deal fields (Amount, Stage, Contact, Organization)"
echo "   5. Right column: Add \"Related Activities\" view block"
echo "   6. Click \"Save layout\""
echo ""
echo "   OR visit any Deal page and click \"Layout\" tab to customize"
echo ""
