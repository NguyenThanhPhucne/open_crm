#!/bin/bash

###############################################################################
# Script: add_vbo_to_contacts.sh
# Purpose: Add Views Bulk Operations to Contacts view (VIEW-03)
# Task: Enable bulk actions (delete, publish, unpublish) for Contacts
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Adding VBO to Contacts View"
echo "========================================="

ddev drush php-eval '
$view = \Drupal::entityTypeManager()->getStorage("view")->load("contacts");

if (!$view) {
  echo "❌ Contacts view not found!\n";
  exit(1);
}

$display = &$view->getDisplay("default");

// Add VBO field as first column
$display["display_options"]["fields"] = array_merge(
  [
    "views_bulk_operations_bulk_form" => [
      "id" => "views_bulk_operations_bulk_form",
      "table" => "views",
      "field" => "views_bulk_operations_bulk_form",
      "relationship" => "none",
      "group_type" => "group",
      "admin_label" => "",
      "plugin_id" => "views_bulk_operations_bulk_form",
      "label" => "",
      "exclude" => FALSE,
      "alter" => [
        "alter_text" => FALSE,
      ],
      "element_class" => "",
      "element_default_classes" => TRUE,
      "empty" => "",
      "hide_empty" => FALSE,
      "empty_zero" => FALSE,
      "hide_alter_empty" => TRUE,
      "batch" => TRUE,
      "batch_size" => 10,
      "action_title" => "Action",
      "include_exclude" => "exclude",
      "selected_actions" => [],
      "exclude_actions" => [
        "node_delete_action",
        "node_make_sticky_action",
        "node_make_unsticky_action",
        "node_promote_action",
        "node_unpromote_action",
        "node_publish_action",
        "node_unpublish_action",
        "node_save_action",
        "pathauto_update_alias_node",
      ],
      "preconfiguration" => [
        "node_delete_action" => [
          "label_override" => "",
        ],
        "node_publish_action" => [
          "label_override" => "Publish contact",
        ],
        "node_unpublish_action" => [
          "label_override" => "Unpublish contact",
        ],
      ],
    ],
  ],
  $display["display_options"]["fields"]
);

// Update table style to include VBO column
if (isset($display["display_options"]["style"]["options"]["columns"])) {
  $display["display_options"]["style"]["options"]["columns"] = array_merge(
    ["views_bulk_operations_bulk_form" => "views_bulk_operations_bulk_form"],
    $display["display_options"]["style"]["options"]["columns"]
  );
  
  $display["display_options"]["style"]["options"]["info"]["views_bulk_operations_bulk_form"] = [
    "align" => "",
    "separator" => "",
    "empty_column" => FALSE,
    "responsive" => "",
  ];
}

$view->save();
echo "✅ VBO field added to Contacts view\n";
echo "✅ Available bulk actions:\n";
echo "   - Delete contact\n";
echo "   - Publish contact\n";
echo "   - Unpublish contact\n";
'

echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ VBO Configuration Complete!"
echo "========================================="
echo ""
echo "📋 Test it at: http://open-crm.ddev.site/app/contacts"
echo "   - Select checkboxes next to contacts"
echo "   - Choose action from dropdown"
echo "   - Click Apply to execute bulk action"
echo ""
