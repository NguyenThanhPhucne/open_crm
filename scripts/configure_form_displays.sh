#!/bin/bash

echo "=========================================="
echo "Configuring Form Displays for CRM System"
echo "=========================================="

# First, ensure form displays exist
echo -e "\n🔧 Creating default form displays if needed..."

ddev drush eval '
$bundles = ["organization", "contact", "deal", "activity"];
$storage = \Drupal::entityTypeManager()->getStorage("entity_form_display");

foreach ($bundles as $bundle) {
  $id = "node.$bundle.default";
  $form_display = $storage->load($id);
  
  if (!$form_display) {
    $form_display = $storage->create([
      "targetEntityType" => "node",
      "bundle" => $bundle,
      "mode" => "default",
      "status" => true,
    ]);
    $form_display->save();
    echo "✅ Created form display: $id\n";
  } else {
    echo "   Form display exists: $id\n";
  }
}
'

# FORM-01: Configure Inline Entity Form for Contact -> Organization
echo -e "\n📝 FORM-01: Configuring Inline Entity Form..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.contact.default");

if ($form_display) {
  // Configure field_organization to use inline_entity_form_complex widget
  $form_display->setComponent("field_organization", [
    "type" => "inline_entity_form_complex",
    "weight" => 3,
    "settings" => [
      "form_mode" => "default",
      "override_labels" => true,
      "label_singular" => "Organization",
      "label_plural" => "Organizations",
      "allow_new" => true,
      "allow_existing" => true,
      "match_operator" => "CONTAINS",
      "allow_duplicate" => false,
    ],
    "third_party_settings" => [],
  ]);
  
  $form_display->save();
  echo "✅ Configured Inline Entity Form for field_organization in Contact\n";
  echo "   - Allow users to add new Organizations inline\n";
  echo "   - Allow users to reference existing Organizations\n";
} else {
  echo "❌ Could not load form display for Contact\n";
}
'

# Also configure for Deal -> Organization and Contact
echo -e "\n📝 Configuring Inline Entity Form for Deal..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.deal.default");

if ($form_display) {
  // Configure field_organization
  $form_display->setComponent("field_organization", [
    "type" => "inline_entity_form_complex",
    "weight" => 5,
    "settings" => [
      "form_mode" => "default",
      "override_labels" => true,
      "label_singular" => "Organization",
      "label_plural" => "Organizations",
      "allow_new" => true,
      "allow_existing" => true,
      "match_operator" => "CONTAINS",
      "allow_duplicate" => false,
    ],
    "third_party_settings" => [],
  ]);
  
  // Configure field_contact
  $form_display->setComponent("field_contact", [
    "type" => "inline_entity_form_complex",
    "weight" => 4,
    "settings" => [
      "form_mode" => "default",
      "override_labels" => true,
      "label_singular" => "Contact",
      "label_plural" => "Contacts",
      "allow_new" => true,
      "allow_existing" => true,
      "match_operator" => "CONTAINS",
      "allow_duplicate" => false,
    ],
    "third_party_settings" => [],
  ]);
  
  $form_display->save();
  echo "✅ Configured Inline Entity Form for Deal\n";
  echo "   - field_organization: Allow inline add/reference\n";
  echo "   - field_contact: Allow inline add/reference\n";
} else {
  echo "❌ Could not load form display for Deal\n";
}
'

# FORM-02: Configure Field Groups and Layout
echo -e "\n📝 FORM-02: Creating Field Groups and Layout..."

# Create Field Groups for Contact
echo -e "\n📦 Creating Field Groups for Contact..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.contact.default");
  
if ($form_display) {
  // Remove existing groups if any
  $third_party_settings = $form_display->getThirdPartySettings("field_group");
  foreach (array_keys($third_party_settings) as $group_name) {
    $form_display->unsetThirdPartySetting("field_group", $group_name);
  }
  
  // Group 1: Basic Information
  $form_display->setThirdPartySetting("field_group", "group_basic_info", [
    "label" => "Basic Information",
    "children" => [
      "title",
      "field_email",
      "field_phone",
      "field_position",
    ],
    "parent_name" => "group_tabs",
    "weight" => 1,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "closed",
      "required_fields" => true,
    ],
  ]);
  
  // Group 2: Organization & Source
  $form_display->setThirdPartySetting("field_group", "group_relationship", [
    "label" => "Organization & Lead",
    "children" => [
      "field_organization",
      "field_source",
    ],
    "parent_name" => "group_tabs",
    "weight" => 2,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "closed",
      "required_fields" => true,
    ],
  ]);
  
  // Main tabs container
  $form_display->setThirdPartySetting("field_group", "group_tabs", [
    "label" => "Tabs",
    "children" => [
      "group_basic_info",
      "group_relationship",
    ],
    "parent_name" => "",
    "weight" => 0,
    "format_type" => "tabs",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "direction" => "horizontal",
    ],
  ]);
  
  // Reorder components
  $form_display->setComponent("title", ["weight" => 0]);
  $form_display->setComponent("field_email", ["weight" => 1]);
  $form_display->setComponent("field_phone", ["weight" => 2]);
  $form_display->setComponent("field_position", ["weight" => 3]);
  $form_display->setComponent("field_organization", ["weight" => 4]);
  $form_display->setComponent("field_source", ["weight" => 5]);
  
  $form_display->save();
  echo "✅ Created Field Groups for Contact:\n";
  echo "   Tab 1 - Basic Information: Title, Email, Phone, Position\n";
  echo "   Tab 2 - Organization & Lead: Organization, Lead Source\n";
} else {
  echo "❌ Could not load form display for Contact\n";
}
'

# Create Field Groups for Deal
echo -e "\n📦 Creating Field Groups for Deal..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.deal.default");
  
if ($form_display) {
  // Remove existing groups if any
  $third_party_settings = $form_display->getThirdPartySettings("field_group");
  foreach (array_keys($third_party_settings) as $group_name) {
    $form_display->unsetThirdPartySetting("field_group", $group_name);
  }
  
  // Group 1: Deal Information
  $form_display->setThirdPartySetting("field_group", "group_deal_info", [
    "label" => "Deal Information",
    "children" => [
      "title",
      "field_stage",
      "field_amount",
      "field_probability",
      "field_closing_date",
    ],
    "parent_name" => "group_tabs",
    "weight" => 1,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "open",
      "required_fields" => true,
    ],
  ]);
  
  // Group 2: Related Entities
  $form_display->setThirdPartySetting("field_group", "group_relations", [
    "label" => "Relationships",
    "children" => [
      "field_contact",
      "field_organization",
    ],
    "parent_name" => "group_tabs",
    "weight" => 2,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "closed",
      "required_fields" => true,
    ],
  ]);
  
  // Main tabs container
  $form_display->setThirdPartySetting("field_group", "group_tabs", [
    "label" => "Tabs",
    "children" => [
      "group_deal_info",
      "group_relations",
    ],
    "parent_name" => "",
    "weight" => 0,
    "format_type" => "tabs",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "direction" => "horizontal",
    ],
  ]);
  
  // Reorder components
  $form_display->setComponent("title", ["weight" => 0]);
  $form_display->setComponent("field_stage", ["weight" => 1]);
  $form_display->setComponent("field_amount", ["weight" => 2]);
  $form_display->setComponent("field_probability", ["weight" => 3]);
  $form_display->setComponent("field_closing_date", ["weight" => 4]);
  $form_display->setComponent("field_contact", ["weight" => 5]);
  $form_display->setComponent("field_organization", ["weight" => 6]);
  
  $form_display->save();
  echo "✅ Created Field Groups for Deal:\n";
  echo "   Tab 1 - Deal Information: Title, Stage, Amount, Probability, Closing Date\n";
  echo "   Tab 2 - Relationships: Contact, Organization\n";
} else {
  echo "❌ Could not load form display for Deal\n";
}
'

# Create Field Groups for Organization
echo -e "\n📦 Creating Field Groups for Organization..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.organization.default");
  
if ($form_display) {
  // Remove existing groups if any
  $third_party_settings = $form_display->getThirdPartySettings("field_group");
  foreach (array_keys($third_party_settings) as $group_name) {
    $form_display->unsetThirdPartySetting("field_group", $group_name);
  }
  
  // Group 1: Company Details
  $form_display->setThirdPartySetting("field_group", "group_company_details", [
    "label" => "Company Details",
    "children" => [
      "title",
      "field_industry",
      "field_website",
    ],
    "parent_name" => "group_tabs",
    "weight" => 1,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "open",
      "required_fields" => true,
    ],
  ]);
  
  // Group 2: Address
  $form_display->setThirdPartySetting("field_group", "group_location", [
    "label" => "Location",
    "children" => [
      "field_address",
    ],
    "parent_name" => "group_tabs",
    "weight" => 2,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "closed",
      "required_fields" => true,
    ],
  ]);
  
  // Main tabs container
  $form_display->setThirdPartySetting("field_group", "group_tabs", [
    "label" => "Tabs",
    "children" => [
      "group_company_details",
      "group_location",
    ],
    "parent_name" => "",
    "weight" => 0,
    "format_type" => "tabs",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "direction" => "horizontal",
    ],
  ]);
  
  // Reorder components
  $form_display->setComponent("title", ["weight" => 0]);
  $form_display->setComponent("field_industry", ["weight" => 1]);
  $form_display->setComponent("field_website", ["weight" => 2]);
  $form_display->setComponent("field_address", ["weight" => 3]);
  
  $form_display->save();
  echo "✅ Created Field Groups for Organization:\n";
  echo "   Tab 1 - Company Details: Name, Industry, Website\n";
  echo "   Tab 2 - Location: Address\n";
} else {
  echo "❌ Could not load form display for Organization\n";
}
'

# Create Field Groups for Activity
echo -e "\n📦 Creating Field Groups for Activity..."

ddev drush eval '
$form_display = \Drupal::entityTypeManager()
  ->getStorage("entity_form_display")
  ->load("node.activity.default");
  
if ($form_display) {
  // Remove existing groups if any
  $third_party_settings = $form_display->getThirdPartySettings("field_group");
  foreach (array_keys($third_party_settings) as $group_name) {
    $form_display->unsetThirdPartySetting("field_group", $group_name);
  }
  
  // Group 1: Activity Details
  $form_display->setThirdPartySetting("field_group", "group_activity_details", [
    "label" => "Activity Details",
    "children" => [
      "title",
      "field_type",
      "field_datetime",
      "field_description",
    ],
    "parent_name" => "group_tabs",
    "weight" => 1,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "open",
      "required_fields" => true,
    ],
  ]);
  
  // Group 2: Related Items
  $form_display->setThirdPartySetting("field_group", "group_activity_relations", [
    "label" => "Related To",
    "children" => [
      "field_deal",
      "field_contact",
    ],
    "parent_name" => "group_tabs",
    "weight" => 2,
    "format_type" => "tab",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "description" => "",
      "formatter" => "closed",
      "required_fields" => true,
    ],
  ]);
  
  // Main tabs container
  $form_display->setThirdPartySetting("field_group", "group_tabs", [
    "label" => "Tabs",
    "children" => [
      "group_activity_details",
      "group_activity_relations",
    ],
    "parent_name" => "",
    "weight" => 0,
    "format_type" => "tabs",
    "format_settings" => [
      "id" => "",
      "classes" => "",
      "direction" => "horizontal",
    ],
  ]);
  
  // Reorder components
  $form_display->setComponent("title", ["weight" => 0]);
  $form_display->setComponent("field_type", ["weight" => 1]);
  $form_display->setComponent("field_datetime", ["weight" => 2]);
  $form_display->setComponent("field_description", ["weight" => 3]);
  $form_display->setComponent("field_deal", ["weight" => 4]);
  $form_display->setComponent("field_contact", ["weight" => 5]);
  
  $form_display->save();
  echo "✅ Created Field Groups for Activity:\n";
  echo "   Tab 1 - Activity Details: Title, Type, Date/Time, Description\n";
  echo "   Tab 2 - Related To: Deal, Contact\n";
} else {
  echo "❌ Could not load form display for Activity\n";
}
'

echo -e "\n=========================================="
echo "✅ Form Displays Configured Successfully!"
echo "=========================================="
