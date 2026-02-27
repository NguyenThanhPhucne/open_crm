#!/bin/bash

###############################################################################
# Script: verify_ief_widgets.sh
# Purpose: Verify Inline Entity Form widgets are properly configured
# Task: FORM-01 - Quick Add functionality verification
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Verifying IEF Widget Configuration"
echo "========================================="

ddev drush eval '
$fd = \Drupal::entityTypeManager()->getStorage("entity_form_display")->load("node.contact.default");
$comp = $fd->getComponent("field_organization");
echo "📋 Contact -> Organization\n";
echo "   Widget: " . ($comp["type"] ?? "not set") . "\n";
echo "   Allow New: " . ($comp["settings"]["allow_new"] ? "✓" : "✗") . "\n";
echo "   Allow Existing: " . ($comp["settings"]["allow_existing"] ? "✓" : "✗") . "\n\n";

$fd = \Drupal::entityTypeManager()->getStorage("entity_form_display")->load("node.deal.default");
$comp = $fd->getComponent("field_organization");
echo "📋 Deal -> Organization\n";
echo "   Widget: " . ($comp["type"] ?? "not set") . "\n";
echo "   Allow New: " . ($comp["settings"]["allow_new"] ? "✓" : "✗") . "\n";
echo "   Allow Existing: " . ($comp["settings"]["allow_existing"] ? "✓" : "✗") . "\n\n";

$comp = $fd->getComponent("field_contact");
echo "📋 Deal -> Contact\n";
echo "   Widget: " . ($comp["type"] ?? "not set") . "\n";
echo "   Allow New: " . ($comp["settings"]["allow_new"] ? "✓" : "✗") . "\n";
echo "   Allow Existing: " . ($comp["settings"]["allow_existing"] ? "✓" : "✗") . "\n";
'

echo ""
echo "========================================="
echo "✅ IEF Widget Verification Complete!"
echo "========================================="
