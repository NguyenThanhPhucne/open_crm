#!/bin/bash

echo "=========================================="
echo "Creating Content Types for CRM System"
echo "=========================================="

# CT-01: Organization (Công ty)
echo -e "\n📦 CT-01: Creating Content Type: Organization"
ddev drush eval '
$type = \Drupal\node\Entity\NodeType::load("organization");
if (!$type) {
  $type = \Drupal\node\Entity\NodeType::create([
    "type" => "organization",
    "name" => "Organization",
    "description" => "Company or organization entity",
  ]);
  $type->setDisplaySubmitted(false);
  $type->setPreviewMode(DRUPAL_DISABLED);
  $type->setNewRevision(true);
  $type->save();
  
  // Disable "Promote to front page"
  $entity_form_display = \Drupal::entityTypeManager()
    ->getStorage("entity_form_display")
    ->load("node.organization.default");
  if ($entity_form_display) {
    $entity_form_display->removeComponent("promote");
    $entity_form_display->save();
  }
  
  echo "✅ Created Content Type: Organization\n";
} else {
  echo "⚠️  Content Type Organization already exists\n";
}
'

# Add fields to Organization
echo "   Adding fields to Organization..."

# field_website (Link)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_website");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_website",
    "entity_type" => "node",
    "type" => "link",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "organization", "field_website");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "organization",
    "label" => "Website",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Website\n";
}
'

# field_address (Text)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_address");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_address",
    "entity_type" => "node",
    "type" => "string_long",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "organization", "field_address");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "organization",
    "label" => "Address",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Address\n";
}
'

# field_industry (Text)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_industry");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_industry",
    "entity_type" => "node",
    "type" => "string",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "organization", "field_industry");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "organization",
    "label" => "Industry",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Industry\n";
}
'

# CT-02: Contact (Khách hàng)
echo -e "\n📦 CT-02: Creating Content Type: Contact"
ddev drush eval '
$type = \Drupal\node\Entity\NodeType::load("contact");
if (!$type) {
  $type = \Drupal\node\Entity\NodeType::create([
    "type" => "contact",
    "name" => "Contact",
    "description" => "Contact or customer entity",
  ]);
  $type->setDisplaySubmitted(false);
  $type->setPreviewMode(DRUPAL_DISABLED);
  $type->setNewRevision(true);
  $type->save();
  
  // Disable "Promote to front page"
  $entity_form_display = \Drupal::entityTypeManager()
    ->getStorage("entity_form_display")
    ->load("node.contact.default");
  if ($entity_form_display) {
    $entity_form_display->removeComponent("promote");
    $entity_form_display->save();
  }
  
  echo "✅ Created Content Type: Contact\n";
} else {
  echo "⚠️  Content Type Contact already exists\n";
}
'

# Add fields to Contact
echo "   Adding fields to Contact..."

# field_email (Email)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_email");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_email",
    "entity_type" => "node",
    "type" => "email",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "contact", "field_email");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "contact",
    "label" => "Email",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Email\n";
}
'

# field_phone (Text - using string for phone)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_phone");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_phone",
    "entity_type" => "node",
    "type" => "string",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "contact", "field_phone");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "contact",
    "label" => "Phone",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Phone\n";
}
'

# field_position (Text)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_position");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_position",
    "entity_type" => "node",
    "type" => "string",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "contact", "field_position");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "contact",
    "label" => "Position",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Position\n";
}
'

# CT-03: Add Entity References to Contact
echo -e "\n📦 CT-03: Adding Entity References to Contact"

# field_organization (Entity Reference -> Organization)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_organization");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_organization",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "node",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "contact", "field_organization");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "contact",
    "label" => "Organization",
    "required" => false,
    "settings" => [
      "handler" => "default:node",
      "handler_settings" => [
        "target_bundles" => ["organization" => "organization"],
        "sort" => [
          "field" => "title",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Organization (Entity Reference)\n";
}
'

# field_source (Entity Reference -> Taxonomy Lead Source)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_source");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_source",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "taxonomy_term",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "contact", "field_source");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "contact",
    "label" => "Lead Source",
    "required" => false,
    "settings" => [
      "handler" => "default:taxonomy_term",
      "handler_settings" => [
        "target_bundles" => ["lead_source" => "lead_source"],
        "sort" => [
          "field" => "name",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Lead Source (Taxonomy Reference)\n";
}
'

# CT-04: Deal (Cơ hội)
echo -e "\n📦 CT-04: Creating Content Type: Deal"
ddev drush eval '
$type = \Drupal\node\Entity\NodeType::load("deal");
if (!$type) {
  $type = \Drupal\node\Entity\NodeType::create([
    "type" => "deal",
    "name" => "Deal",
    "description" => "Sales opportunity or deal entity",
  ]);
  $type->setDisplaySubmitted(false);
  $type->setPreviewMode(DRUPAL_DISABLED);
  $type->setNewRevision(true);
  $type->save();
  
  // Disable "Promote to front page"
  $entity_form_display = \Drupal::entityTypeManager()
    ->getStorage("entity_form_display")
    ->load("node.deal.default");
  if ($entity_form_display) {
    $entity_form_display->removeComponent("promote");
    $entity_form_display->save();
  }
  
  echo "✅ Created Content Type: Deal\n";
} else {
  echo "⚠️  Content Type Deal already exists\n";
}
'

# Add fields to Deal
echo "   Adding fields to Deal..."

# field_amount (Number Decimal)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_amount");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_amount",
    "entity_type" => "node",
    "type" => "decimal",
    "cardinality" => 1,
    "settings" => [
      "precision" => 10,
      "scale" => 2,
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_amount");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Amount",
    "required" => false,
    "settings" => [
      "min" => 0,
      "prefix" => "$",
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Amount\n";
}
'

# field_closing_date (Date)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_closing_date");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_closing_date",
    "entity_type" => "node",
    "type" => "datetime",
    "cardinality" => 1,
    "settings" => [
      "datetime_type" => "date",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_closing_date");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Closing Date",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Closing Date\n";
}
'

# field_probability (Number Integer)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_probability");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_probability",
    "entity_type" => "node",
    "type" => "integer",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_probability");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Probability (%)",
    "required" => false,
    "settings" => [
      "min" => 0,
      "max" => 100,
      "suffix" => "%",
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Probability\n";
}
'

# CT-05: Add Entity References to Deal
echo -e "\n📦 CT-05: Adding Entity References to Deal"

# field_stage (Taxonomy Reference -> Pipeline Stage)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_stage");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_stage",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "taxonomy_term",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_stage");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Pipeline Stage",
    "required" => true,
    "settings" => [
      "handler" => "default:taxonomy_term",
      "handler_settings" => [
        "target_bundles" => ["pipeline_stage" => "pipeline_stage"],
        "sort" => [
          "field" => "weight",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Pipeline Stage (Taxonomy Reference)\n";
}
'

# field_contact (Entity Reference -> Contact) - reusing field_contact
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_contact");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_contact",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "node",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_contact");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Contact",
    "required" => false,
    "settings" => [
      "handler" => "default:node",
      "handler_settings" => [
        "target_bundles" => ["contact" => "contact"],
        "sort" => [
          "field" => "title",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Contact (Entity Reference)\n";
}
'

# field_organization for Deal (reuse existing field storage)
ddev drush eval '
$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "deal", "field_organization");
if (!$field) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_organization");
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "deal",
    "label" => "Organization",
    "required" => false,
    "settings" => [
      "handler" => "default:node",
      "handler_settings" => [
        "target_bundles" => ["organization" => "organization"],
        "sort" => [
          "field" => "title",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Organization (Entity Reference)\n";
}
'

# CT-06: Activity (Hoạt động)
echo -e "\n📦 CT-06: Creating Content Type: Activity"
ddev drush eval '
$type = \Drupal\node\Entity\NodeType::load("activity");
if (!$type) {
  $type = \Drupal\node\Entity\NodeType::create([
    "type" => "activity",
    "name" => "Activity",
    "description" => "Activity or task entity",
  ]);
  $type->setDisplaySubmitted(false);
  $type->setPreviewMode(DRUPAL_DISABLED);
  $type->setNewRevision(true);
  $type->save();
  
  // Disable "Promote to front page"
  $entity_form_display = \Drupal::entityTypeManager()
    ->getStorage("entity_form_display")
    ->load("node.activity.default");
  if ($entity_form_display) {
    $entity_form_display->removeComponent("promote");
    $entity_form_display->save();
  }
  
  echo "✅ Created Content Type: Activity\n";
} else {
  echo "⚠️  Content Type Activity already exists\n";
}
'

# Add fields to Activity
echo "   Adding fields to Activity..."

# field_type (Taxonomy Reference -> Activity Type)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_type");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_type",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "taxonomy_term",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "activity", "field_type");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "activity",
    "label" => "Activity Type",
    "required" => true,
    "settings" => [
      "handler" => "default:taxonomy_term",
      "handler_settings" => [
        "target_bundles" => ["activity_type" => "activity_type"],
        "sort" => [
          "field" => "name",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Activity Type (Taxonomy Reference)\n";
}
'

# field_datetime (Date and time)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_datetime");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_datetime",
    "entity_type" => "node",
    "type" => "datetime",
    "cardinality" => 1,
    "settings" => [
      "datetime_type" => "datetime",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "activity", "field_datetime");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "activity",
    "label" => "Date & Time",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Date & Time\n";
}
'

# field_description (Text long)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_description");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_description",
    "entity_type" => "node",
    "type" => "text_long",
    "cardinality" => 1,
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "activity", "field_description");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "activity",
    "label" => "Description",
    "required" => false,
  ]);
  $field->save();
  echo "   ✅ Added field: Description\n";
}
'

# Add Entity References to Activity (Deal and Contact)
echo "   Adding Entity References to Activity..."

# field_deal (Entity Reference -> Deal)
ddev drush eval '
$field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_deal");
if (!$field_storage) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::create([
    "field_name" => "field_deal",
    "entity_type" => "node",
    "type" => "entity_reference",
    "cardinality" => 1,
    "settings" => [
      "target_type" => "node",
    ],
  ]);
  $field_storage->save();
}

$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "activity", "field_deal");
if (!$field) {
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "activity",
    "label" => "Related Deal",
    "required" => false,
    "settings" => [
      "handler" => "default:node",
      "handler_settings" => [
        "target_bundles" => ["deal" => "deal"],
        "sort" => [
          "field" => "title",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Related Deal (Entity Reference)\n";
}
'

# field_contact for Activity (reuse existing field storage)
ddev drush eval '
$field = \Drupal\field\Entity\FieldConfig::loadByName("node", "activity", "field_contact");
if (!$field) {
  $field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName("node", "field_contact");
  $field = \Drupal\field\Entity\FieldConfig::create([
    "field_storage" => $field_storage,
    "bundle" => "activity",
    "label" => "Related Contact",
    "required" => false,
    "settings" => [
      "handler" => "default:node",
      "handler_settings" => [
        "target_bundles" => ["contact" => "contact"],
        "sort" => [
          "field" => "title",
          "direction" => "asc",
        ],
        "auto_create" => false,
      ],
    ],
  ]);
  $field->save();
  echo "   ✅ Added field: Related Contact (Entity Reference)\n";
}
'

echo -e "\n=========================================="
echo "✅ All Content Types Created Successfully!"
echo "=========================================="
