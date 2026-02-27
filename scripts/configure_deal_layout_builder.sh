#!/bin/bash

###############################################################################
# Script: configure_deal_layout_builder.sh
# Purpose: Enable Layout Builder for Deal content type (VIEW-05)
# Task: Create 2-column layout with Deal info + Related Activities view
# Author: DevOps Team
# Date: 2026-02-26
###############################################################################

echo "========================================="
echo "Configuring Layout Builder for Deal"
echo "========================================="

# ============================================
# Step 1: Create Related Activities View Block
# ============================================
echo ""
echo "📊 Creating Related Activities View Block..."

ddev drush php-eval '
$view_id = "related_activities";

// Check if view already exists
$view = \Drupal::entityTypeManager()->getStorage("view")->load($view_id);
if ($view) {
  echo "⚠️  View \"$view_id\" already exists. Deleting...\n";
  $view->delete();
}

// Create related activities view
$view = \Drupal\views\Entity\View::create([
  "id" => $view_id,
  "label" => "Related Activities",
  "description" => "Activities related to current deal",
  "tag" => "CRM",
  "base_table" => "node_field_data",
  "display" => [
    "default" => [
      "id" => "default",
      "display_plugin" => "default",
      "display_title" => "Default",
      "position" => 0,
      "display_options" => [
        "title" => "Related Activities",
        "fields" => [
          "field_activity_type" => [
            "id" => "field_activity_type",
            "table" => "node__field_activity_type",
            "field" => "field_activity_type",
            "relationship" => "none",
            "label" => "Type",
            "plugin_id" => "field",
            "type" => "entity_reference_label",
          ],
          "title" => [
            "id" => "title",
            "table" => "node_field_data",
            "field" => "title",
            "relationship" => "none",
            "label" => "Activity",
            "plugin_id" => "field",
            "entity_type" => "node",
            "entity_field" => "title",
            "type" => "string",
            "settings" => ["link_to_entity" => TRUE],
          ],
          "field_activity_datetime" => [
            "id" => "field_activity_datetime",
            "table" => "node__field_activity_datetime",
            "field" => "field_activity_datetime",
            "relationship" => "none",
            "label" => "Date",
            "plugin_id" => "field",
            "settings" => ["format_type" => "short"],
          ],
          "field_activity_description" => [
            "id" => "field_activity_description",
            "table" => "node__field_activity_description",
            "field" => "field_activity_description",
            "relationship" => "none",
            "label" => "Description",
            "plugin_id" => "field",
            "type" => "text_trimmed",
            "settings" => ["trim_length" => 200],
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
            "value" => ["activity" => "activity"],
            "plugin_id" => "bundle",
            "entity_type" => "node",
            "entity_field" => "type",
            "group" => 1,
          ],
        ],
        "sorts" => [
          "field_activity_datetime_value" => [
            "id" => "field_activity_datetime_value",
            "table" => "node__field_activity_datetime",
            "field" => "field_activity_datetime_value",
            "order" => "DESC",
            "plugin_id" => "datetime",
          ],
        ],
        "pager" => [
          "type" => "some",
          "options" => [
            "items_per_page" => 10,
            "offset" => 0,
          ],
        ],
        "style" => [
          "type" => "default",
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
            "content" => "<p>No activities yet. <a href=\"/node/add/activity\">Add an activity</a>.</p>",
            "plugin_id" => "text_custom",
          ],
        ],
        "arguments" => [
          "field_deal_target_id" => [
            "id" => "field_deal_target_id",
            "table" => "node__field_deal",
            "field" => "field_deal_target_id",
            "relationship" => "none",
            "default_action" => "default",
            "default_argument_type" => "node",
            "default_argument_options" => [],
            "summary" => [
              "number_of_records" => 0,
              "format" => "default_summary",
            ],
            "specify_validation" => TRUE,
            "validate" => [
              "type" => "entity:node",
              "fail" => "not found",
            ],
            "validate_options" => [
              "bundles" => ["deal" => "deal"],
            ],
            "break_phrase" => FALSE,
            "not" => FALSE,
            "plugin_id" => "numeric",
          ],
        ],
      ],
    ],
    "block_1" => [
      "id" => "block_1",
      "display_plugin" => "block",
      "display_title" => "Block",
      "position" => 1,
      "display_options" => [
        "display_description" => "Shows activities related to current deal",
        "block_description" => "Related Activities",
      ],
    ],
  ],
]);

$view->save();
echo "✅ Related Activities view block created\n";
'

# ============================================
# Step 2: Enable Layout Builder for Deal
# ============================================
echo ""
echo "🏗️ Enabling Layout Builder for Deal content type..."

ddev drush php-eval '
$entity_type = "node";
$bundle = "deal";

// Load the entity view display
$display = \Drupal::entityTypeManager()
  ->getStorage("entity_view_display")
  ->load("$entity_type.$bundle.default");

if (!$display) {
  echo "❌ Display not found. Creating...\n";
  $display = \Drupal\Core\Entity\Entity\EntityViewDisplay::create([
    "targetEntityType" => $entity_type,
    "bundle" => $bundle,
    "mode" => "default",
    "status" => TRUE,
  ]);
}

// Enable Layout Builder
$display->setThirdPartySetting("layout_builder", "enabled", TRUE);
$display->setThirdPartySetting("layout_builder", "allow_custom", FALSE);

// Create 2-column layout with sections
$sections = [];

// Section 1: Two column layout
$section = new \Drupal\layout_builder\Section(
  "layout_twocol_section",
  [
    "column_widths" => "50-50",
    "label" => "",
  ]
);

// Left column: Deal Information
$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:title",
    "label" => "Title",
    "provider" => "layout_builder",
    "label_display" => "0",
    "formatter" => [
      "label" => "hidden",
      "type" => "string",
      "settings" => ["link_to_entity" => FALSE],
      "third_party_settings" => [],
      "weight" => 0,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_amount",
    "label" => "Amount",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "number_decimal",
      "settings" => [
        "thousand_separator" => ",",
        "decimal_separator" => ".",
        "scale" => 2,
        "prefix_suffix" => TRUE,
      ],
      "third_party_settings" => [],
      "weight" => 1,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_stage",
    "label" => "Stage",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "entity_reference_label",
      "settings" => ["link" => FALSE],
      "third_party_settings" => [],
      "weight" => 2,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_closing_date",
    "label" => "Closing Date",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "datetime_default",
      "settings" => ["format_type" => "medium"],
      "third_party_settings" => [],
      "weight" => 3,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_probability",
    "label" => "Probability",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "number_integer",
      "settings" => [
        "thousand_separator" => "",
        "prefix_suffix" => TRUE,
      ],
      "third_party_settings" => [],
      "weight" => 4,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_organization",
    "label" => "Organization",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "entity_reference_label",
      "settings" => ["link" => TRUE],
      "third_party_settings" => [],
      "weight" => 5,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "first",
  [
    "id" => "field_block:node:deal:field_contact",
    "label" => "Contact",
    "provider" => "layout_builder",
    "label_display" => "above",
    "formatter" => [
      "label" => "above",
      "type" => "entity_reference_label",
      "settings" => ["link" => TRUE],
      "third_party_settings" => [],
      "weight" => 6,
    ],
    "context_mapping" => ["entity" => "layout_builder.entity"],
  ]
);
$section->appendComponent($component);

// Right column: Related Activities block
$component = new \Drupal\layout_builder\SectionComponent(
  \Drupal::service("uuid")->generate(),
  "second",
  [
    "id" => "views_block:related_activities-block_1",
    "label" => "Related Activities",
    "provider" => "views",
    "label_display" => "visible",
    "views_label" => "Related Activities",
    "items_per_page" => "none",
    "context_mapping" => [],
  ]
);
$section->appendComponent($component);

$sections[] = $section;

// Set sections to display
$display->setThirdPartySetting("layout_builder", "sections", $sections);

$display->save();
echo "✅ Layout Builder enabled for Deal content type\n";
echo "✅ 2-column layout created:\n";
echo "   - Left: Deal Information (Amount, Stage, Date, Probability, Organization, Contact)\n";
echo "   - Right: Related Activities (View Block)\n";
'

# ============================================
# Clear Cache
# ============================================
echo ""
echo "🔄 Clearing cache..."
ddev drush cr

echo ""
echo "========================================="
echo "✅ Layout Builder Configuration Complete!"
echo "========================================="
echo ""
echo "📋 Test the layout:"
echo "   1. Visit any Deal page (e.g., http://open-crm.ddev.site/node/[deal-id])"
echo "   2. You should see a 2-column layout:"
echo "      - Left: Deal details and related entities"
echo "      - Right: List of related activities"
echo ""
echo "⚙️  Manage layout at:"
echo "   http://open-crm.ddev.site/admin/structure/types/manage/deal/display"
echo ""
