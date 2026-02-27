#!/bin/bash

###############################################################################
# Script: create_views.sh
# Purpose: Create Views for CRM application (VIEW-01 to VIEW-04)
# Tasks: 
#   - VIEW-01: Contacts Table View with fields
#   - VIEW-02: Exposed Filters for search
#   - VIEW-04: Kanban Pipeline Board
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Creating CRM Views"
echo "========================================="

# ============================================
# VIEW-01: Create Contacts Table View
# ============================================
echo ""
echo "📋 [VIEW-01] Creating Contacts Table View..."

ddev drush php-eval '
$view_id = "contacts";

// Check if view already exists
$view = \Drupal::entityTypeManager()->getStorage("view")->load($view_id);
if ($view) {
  echo "⚠️  View \"$view_id\" already exists. Deleting...\n";
  $view->delete();
}

// Create new view
$view = \Drupal\views\Entity\View::create([
  "id" => $view_id,
  "label" => "Contacts",
  "description" => "List of all contacts with search filters",
  "tag" => "CRM",
  "base_table" => "node_field_data",
  "display" => [
    "default" => [
      "id" => "default",
      "display_plugin" => "default",
      "display_title" => "Default",
      "position" => 0,
      "display_options" => [
        "title" => "Contacts",
        "fields" => [
          "title" => [
            "id" => "title",
            "table" => "node_field_data",
            "field" => "title",
            "relationship" => "none",
            "label" => "Name",
            "plugin_id" => "field",
            "entity_type" => "node",
            "entity_field" => "title",
            "type" => "string",
            "settings" => ["link_to_entity" => TRUE],
          ],
          "field_organization" => [
            "id" => "field_organization",
            "table" => "node__field_organization",
            "field" => "field_organization",
            "relationship" => "none",
            "label" => "Organization",
            "plugin_id" => "field",
            "type" => "entity_reference_label",
            "settings" => ["link" => TRUE],
          ],
          "field_phone" => [
            "id" => "field_phone",
            "table" => "node__field_phone",
            "field" => "field_phone",
            "relationship" => "none",
            "label" => "Phone",
            "plugin_id" => "field",
          ],
          "field_email" => [
            "id" => "field_email",
            "table" => "node__field_email",
            "field" => "field_email",
            "relationship" => "none",
            "label" => "Email",
            "plugin_id" => "field",
          ],
          "field_lead_source" => [
            "id" => "field_lead_source",
            "table" => "node__field_lead_source",
            "field" => "field_lead_source",
            "relationship" => "none",
            "label" => "Source",
            "plugin_id" => "field",
            "type" => "entity_reference_label",
          ],
        ],
        "filters" => [
          "status" => [
            "id" => "status",
            "table" => "node_field_data",
            "field" => "status",
            "value" => "1",
            "plugin_id" => "boolean",
            "entity_type" => "node",
            "entity_field" => "status",
            "group" => 1,
          ],
          "type" => [
            "id" => "type",
            "table" => "node_field_data",
            "field" => "type",
            "value" => ["contact" => "contact"],
            "plugin_id" => "bundle",
            "entity_type" => "node",
            "entity_field" => "type",
            "group" => 1,
          ],
        ],
        "sorts" => [
          "created" => [
            "id" => "created",
            "table" => "node_field_data",
            "field" => "created",
            "order" => "DESC",
            "plugin_id" => "date",
            "entity_type" => "node",
            "entity_field" => "created",
          ],
        ],
        "pager" => [
          "type" => "full",
          "options" => [
            "items_per_page" => 25,
            "offset" => 0,
          ],
        ],
        "style" => [
          "type" => "table",
          "options" => [
            "grouping" => [],
            "row_class" => "",
            "default_row_class" => TRUE,
            "columns" => [
              "title" => "title",
              "field_organization" => "field_organization",
              "field_phone" => "field_phone",
              "field_email" => "field_email",
              "field_lead_source" => "field_lead_source",
            ],
            "default" => "title",
            "info" => [
              "title" => [
                "sortable" => TRUE,
                "separator" => "",
                "align" => "",
              ],
              "field_organization" => [
                "sortable" => TRUE,
                "separator" => "",
                "align" => "",
              ],
              "field_phone" => [
                "sortable" => FALSE,
                "separator" => "",
                "align" => "",
              ],
              "field_email" => [
                "sortable" => FALSE,
                "separator" => "",
                "align" => "",
              ],
              "field_lead_source" => [
                "sortable" => TRUE,
                "separator" => "",
                "align" => "",
              ],
            ],
          ],
        ],
        "access" => [
          "type" => "perm",
          "options" => ["perm" => "access content"],
        ],
        "empty" => [
          "area_text_custom" => [
            "id" => "area_text_custom",
            "table" => "views",
            "field" => "area_text_custom",
            "content" => "<p>No contacts found. <a href=\"/node/add/contact\">Add a contact</a>.</p>",
            "plugin_id" => "text_custom",
          ],
        ],
      ],
    ],
    "page_1" => [
      "id" => "page_1",
      "display_plugin" => "page",
      "display_title" => "Page",
      "position" => 1,
      "display_options" => [
        "path" => "app/contacts",
        "menu" => [
          "type" => "normal",
          "title" => "Contacts",
          "description" => "View and manage contacts",
          "menu_name" => "main",
          "weight" => 10,
        ],
      ],
    ],
  ],
]);

$view->save();
echo "✅ Contacts Table View created at /app/contacts\n";
'

# ============================================
# VIEW-02: Add Exposed Filters to Contacts View
# ============================================
echo ""
echo "🔍 [VIEW-02] Adding Exposed Filters to Contacts View..."

ddev drush php-eval '
$view = \Drupal::entityTypeManager()->getStorage("view")->load("contacts");

if (!$view) {
  echo "❌ Contacts view not found!\n";
  exit(1);
}

$display = &$view->getDisplay("default");

// Add exposed filter for Title (Name)
$display["display_options"]["filters"]["title"] = [
  "id" => "title",
  "table" => "node_field_data",
  "field" => "title",
  "relationship" => "none",
  "operator" => "contains",
  "value" => "",
  "exposed" => TRUE,
  "expose" => [
    "operator_id" => "title_op",
    "label" => "Name",
    "description" => "",
    "use_operator" => FALSE,
    "operator" => "title_op",
    "operator_limit_selection" => FALSE,
    "operator_list" => [],
    "identifier" => "title",
    "required" => FALSE,
    "remember" => FALSE,
    "multiple" => FALSE,
    "placeholder" => "Search by name...",
  ],
  "plugin_id" => "string",
  "entity_type" => "node",
  "entity_field" => "title",
  "group" => 1,
];

// Add exposed filter for Lead Source
$display["display_options"]["filters"]["field_lead_source_target_id"] = [
  "id" => "field_lead_source_target_id",
  "table" => "node__field_lead_source",
  "field" => "field_lead_source_target_id",
  "relationship" => "none",
  "operator" => "or",
  "value" => [],
  "exposed" => TRUE,
  "expose" => [
    "operator_id" => "field_lead_source_target_id_op",
    "label" => "Source",
    "description" => "",
    "use_operator" => FALSE,
    "operator" => "field_lead_source_target_id_op",
    "operator_limit_selection" => FALSE,
    "operator_list" => [],
    "identifier" => "field_lead_source_target_id",
    "required" => FALSE,
    "remember" => FALSE,
    "multiple" => TRUE,
    "reduce" => FALSE,
  ],
  "plugin_id" => "taxonomy_index_tid",
  "type" => "select",
];

// Add exposed filter for Organization
$display["display_options"]["filters"]["field_organization_target_id"] = [
  "id" => "field_organization_target_id",
  "table" => "node__field_organization",
  "field" => "field_organization_target_id",
  "relationship" => "none",
  "operator" => "or",
  "value" => [],
  "exposed" => TRUE,
  "expose" => [
    "operator_id" => "field_organization_target_id_op",
    "label" => "Organization",
    "description" => "",
    "use_operator" => FALSE,
    "operator" => "field_organization_target_id_op",
    "operator_limit_selection" => FALSE,
    "operator_list" => [],
    "identifier" => "field_organization_target_id",
    "required" => FALSE,
    "remember" => FALSE,
    "multiple" => TRUE,
    "reduce" => FALSE,
  ],
  "plugin_id" => "numeric",
  "type" => "select",
];

// Configure exposed form settings
$display["display_options"]["exposed_form"] = [
  "type" => "basic",
  "options" => [
    "submit_button" => "Search",
    "reset_button" => TRUE,
    "reset_button_label" => "Reset",
    "exposed_sorts_label" => "Sort by",
    "expose_sort_order" => TRUE,
    "sort_asc_label" => "Asc",
    "sort_desc_label" => "Desc",
  ],
];

$view->save();
echo "✅ Exposed filters added: Name, Source, Organization\n";
'

# ============================================
# VIEW-04: Create Kanban Pipeline View
# ============================================
echo ""
echo "📊 [VIEW-04] Creating Kanban Pipeline Board..."

ddev drush php-eval '
$view_id = "pipeline";

// Check if view already exists
$view = \Drupal::entityTypeManager()->getStorage("view")->load($view_id);
if ($view) {
  echo "⚠️  View \"$view_id\" already exists. Deleting...\n";
  $view->delete();
}

// Create pipeline kanban view
$view = \Drupal\views\Entity\View::create([
  "id" => $view_id,
  "label" => "Pipeline",
  "description" => "Deal pipeline kanban board",
  "tag" => "CRM",
  "base_table" => "node_field_data",
  "display" => [
    "default" => [
      "id" => "default",
      "display_plugin" => "default",
      "display_title" => "Default",
      "position" => 0,
      "display_options" => [
        "title" => "Pipeline",
        "fields" => [
          "title" => [
            "id" => "title",
            "table" => "node_field_data",
            "field" => "title",
            "relationship" => "none",
            "label" => "",
            "plugin_id" => "field",
            "entity_type" => "node",
            "entity_field" => "title",
            "type" => "string",
            "settings" => ["link_to_entity" => TRUE],
          ],
          "field_amount" => [
            "id" => "field_amount",
            "table" => "node__field_amount",
            "field" => "field_amount",
            "relationship" => "none",
            "label" => "Amount",
            "plugin_id" => "field",
            "settings" => [
              "thousand_separator" => ",",
              "prefix_suffix" => TRUE,
              "decimal_separator" => ".",
              "scale" => 2,
            ],
          ],
          "field_contact" => [
            "id" => "field_contact",
            "table" => "node__field_contact",
            "field" => "field_contact",
            "relationship" => "none",
            "label" => "Contact",
            "plugin_id" => "field",
            "type" => "entity_reference_label",
            "settings" => ["link" => TRUE],
          ],
          "field_organization" => [
            "id" => "field_organization",
            "table" => "node__field_organization",
            "field" => "field_organization",
            "relationship" => "none",
            "label" => "Organization",
            "plugin_id" => "field",
            "type" => "entity_reference_label",
            "settings" => ["link" => TRUE],
          ],
          "field_closing_date" => [
            "id" => "field_closing_date",
            "table" => "node__field_closing_date",
            "field" => "field_closing_date",
            "relationship" => "none",
            "label" => "Close Date",
            "plugin_id" => "field",
            "settings" => ["format_type" => "short"],
          ],
        ],
        "filters" => [
          "status" => [
            "id" => "status",
            "table" => "node_field_data",
            "field" => "status",
            "value" => "1",
            "plugin_id" => "boolean",
            "entity_type" => "node",
            "entity_field" => "status",
            "group" => 1,
          ],
          "type" => [
            "id" => "type",
            "table" => "node_field_data",
            "field" => "type",
            "value" => ["deal" => "deal"],
            "plugin_id" => "bundle",
            "entity_type" => "node",
            "entity_field" => "type",
            "group" => 1,
          ],
        ],
        "sorts" => [
          "field_amount_value" => [
            "id" => "field_amount_value",
            "table" => "node__field_amount",
            "field" => "field_amount_value",
            "order" => "DESC",
            "plugin_id" => "standard",
          ],
        ],
        "pager" => [
          "type" => "none",
          "options" => ["offset" => 0],
        ],
        "style" => [
          "type" => "kanban",
          "options" => [
            "grouping" => [
              [
                "field" => "field_stage",
                "rendered" => TRUE,
                "rendered_strip" => FALSE,
              ],
            ],
            "columns" => [],
          ],
        ],
        "row" => [
          "type" => "fields",
        ],
        "access" => [
          "type" => "perm",
          "options" => ["perm" => "access content"],
        ],
        "empty" => [
          "area_text_custom" => [
            "id" => "area_text_custom",
            "table" => "views",
            "field" => "area_text_custom",
            "content" => "<p>No deals found. <a href=\"/node/add/deal\">Add a deal</a>.</p>",
            "plugin_id" => "text_custom",
          ],
        ],
      ],
    ],
    "page_1" => [
      "id" => "page_1",
      "display_plugin" => "page",
      "display_title" => "Page",
      "position" => 1,
      "display_options" => [
        "path" => "app/pipeline",
        "menu" => [
          "type" => "normal",
          "title" => "Pipeline",
          "description" => "View deal pipeline kanban board",
          "menu_name" => "main",
          "weight" => 20,
        ],
      ],
    ],
  ],
]);

$view->save();
echo "✅ Pipeline Kanban View created at /app/pipeline\n";
'

# ============================================
# Clear Cache
# ============================================
echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ Views Created Successfully!"
echo "========================================="
echo ""
echo "📋 Available Views:"
echo "  - Contacts Table: http://open-crm.ddev.site/app/contacts"
echo "  - Pipeline Kanban: http://open-crm.ddev.site/app/pipeline"
echo ""
echo "🔍 Features:"
echo "  - Contacts: Searchable table with Name, Organization, Phone, Email, Source"
echo "  - Pipeline: Kanban board grouped by Deal Stage"
echo ""
