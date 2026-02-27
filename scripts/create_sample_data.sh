#!/bin/bash

echo "=========================================="
echo "Creating Sample Data for CRM System"
echo "=========================================="

# Create sample Organizations
echo -e "\n📦 Creating Sample Organizations..."
ddev drush eval '
$organizations = [
  ["title" => "Acme Corporation", "website" => "https://acme.com", "address" => "123 Main St, New York, NY", "industry" => "Technology"],
  ["title" => "Global Enterprises", "website" => "https://global-ent.com", "address" => "456 Market St, San Francisco, CA", "industry" => "Finance"],
  ["title" => "Tech Solutions Inc", "website" => "https://techsolutions.com", "address" => "789 Innovation Dr, Austin, TX", "industry" => "Software"],
];

$admin_uid = 1; // Set owner = Admin
$org_ids = [];
foreach ($organizations as $data) {
  $node = \Drupal\node\Entity\Node::create([
    "type" => "organization",
    "title" => $data["title"],
    "field_website" => ["uri" => $data["website"]],
    "field_address" => $data["address"],
    "field_industry" => $data["industry"],
    "field_owner" => $admin_uid,
    "status" => 1,
    "uid" => $admin_uid,
  ]);
  $node->save();
  $org_ids[$data["title"]] = $node->id();
  echo "✅ Created: " . $data["title"] . "\n";
}

// Store org IDs for next steps
file_put_contents("/tmp/crm_org_ids.json", json_encode($org_ids));
'

# Create sample Contacts
echo -e "\n📦 Creating Sample Contacts..."
ddev drush eval '
$org_ids = json_decode(file_get_contents("/tmp/crm_org_ids.json"), true);

// Get Lead Source terms
$terms = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "lead_source"]);
$lead_sources = [];
foreach ($terms as $term) {
  $lead_sources[$term->getName()] = $term->id();
}

$contacts = [
  ["title" => "John Smith", "email" => "john.smith@acme.com", "phone" => "+1-555-0101", "position" => "CEO", "org" => "Acme Corporation", "source" => "Website"],
  ["title" => "Sarah Johnson", "email" => "sarah.j@global-ent.com", "phone" => "+1-555-0102", "position" => "CTO", "org" => "Global Enterprises", "source" => "Referral"],
  ["title" => "Mike Davis", "email" => "mike.d@techsolutions.com", "phone" => "+1-555-0103", "position" => "VP Sales", "org" => "Tech Solutions Inc", "source" => "Event"],
  ["title" => "Emily Brown", "email" => "emily.brown@acme.com", "phone" => "+1-555-0104", "position" => "Marketing Director", "org" => "Acme Corporation", "source" => "Call"],
];

$admin_uid = 1; // Set owner = Admin
$contact_ids = [];
foreach ($contacts as $data) {
  $node = \Drupal\node\Entity\Node::create([
    "type" => "contact",
    "title" => $data["title"],
    "field_email" => $data["email"],
    "field_phone" => $data["phone"],
    "field_position" => $data["position"],
    "field_organization" => ["target_id" => $org_ids[$data["org"]] ?? null],
    "field_source" => ["target_id" => $lead_sources[$data["source"]] ?? null],
    "field_owner" => $admin_uid,
    "status" => 1,
    "uid" => $admin_uid,
  ]);
  $node->save();
  $contact_ids[$data["title"]] = $node->id();
  echo "✅ Created: " . $data["title"] . " (" . $data["position"] . ")\n";
}

file_put_contents("/tmp/crm_contact_ids.json", json_encode($contact_ids));
'

# Create sample Deals
echo -e "\n📦 Creating Sample Deals..."
ddev drush eval '
$org_ids = json_decode(file_get_contents("/tmp/crm_org_ids.json"), true);
$contact_ids = json_decode(file_get_contents("/tmp/crm_contact_ids.json"), true);

// Get Pipeline Stage terms
$terms = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "pipeline_stage"]);
$stages = [];
foreach ($terms as $term) {
  $stages[$term->getName()] = $term->id();
}

$deals = [
  ["title" => "Enterprise Software License", "amount" => 150000, "probability" => 75, "stage" => "Proposal", "contact" => "John Smith", "org" => "Acme Corporation", "closing_date" => "2026-04-15"],
  ["title" => "Cloud Migration Project", "amount" => 250000, "probability" => 60, "stage" => "Negotiation", "contact" => "Sarah Johnson", "org" => "Global Enterprises", "closing_date" => "2026-05-20"],
  ["title" => "Annual Support Contract", "amount" => 50000, "probability" => 90, "stage" => "Won", "contact" => "Mike Davis", "org" => "Tech Solutions Inc", "closing_date" => "2026-03-01"],
 admin_uid = 1; // Set owner = Admin
$deal_ids = [];
foreach ($deals as $data) {
  $node = \Drupal\node\Entity\Node::create([
    "type" => "deal",
    "title" => $data["title"],
    "field_amount" => $data["amount"],
    "field_probability" => $data["probability"],
    "field_stage" => ["target_id" => $stages[$data["stage"]] ?? null],
    "field_contact" => ["target_id" => $contact_ids[$data["contact"]] ?? null],
    "field_organization" => ["target_id" => $org_ids[$data["org"]] ?? null],
    "field_closing_date" => $data["closing_date"],
    "field_owner" => $admin_uid,
    "status" => 1,
    "uid" => $admin_uidt" => ["target_id" => $contact_ids[$data["contact"]] ?? null],
    "field_organization" => ["target_id" => $org_ids[$data["org"]] ?? null],
    "field_closing_date" => $data["closing_date"],
    "status" => 1,
  ]);
  $node->save();
  $deal_ids[$data["title"]] = $node->id();
  echo "✅ Created: " . $data["title"] . " ($" . number_format($data["amount"]) . ")\n";
}

file_put_contents("/tmp/crm_deal_ids.json", json_encode($deal_ids));
'

# Create sample Activities
echo -e "\n📦 Creating Sample Activities..."
ddev drush eval '
$contact_ids = json_decode(file_get_contents("/tmp/crm_contact_ids.json"), true);
$deal_ids = json_decode(file_get_contents("/tmp/crm_deal_ids.json"), true);

// Get Activity Type terms
$terms = \Drupal::entityTypeManager()->getStorage("taxonomy_term")->loadByProperties(["vid" => "activity_type"]);
$activity_types = [];
foreach ($terms as $term) {
  $activity_types[$term->getName()] = $term->id();
}

$activities = [
  ["title" => "Initial discovery call", "type" => "Call", "contact" => "John Smith", "deal" => "Enterprise Software License", "datetime" => "2026-02-20T10:00:00", "description" => "Discussed business needs and software requirements"],
  ["title" => "Follow-up email sent", "type" => "Email", "contact" => "Sarah Johnson", "deal" => "Cloud Migration Project", "datetime" => "2026-02-22T14:30:00", "description" => "Sent proposal document and pricing details"],
  ["title" => "Demo meeting scheduled", "type" => "Meeting", "contact" => "Mike Davis", "deal" => "Annual Support Contract", "datetime" => "2026-02-25T15:00:00", "description" => "Product demonstration and Q&A session"],
$admin_uid = 1; // Set assigned_to = Admin
foreach ($activities as $data) {
  $node = \Drupal\node\Entity\Node::create([
    "type" => "activity",
    "title" => $data["title"],
    "field_type" => ["target_id" => $activity_types[$data["type"]] ?? null],
    "field_contact" => ["target_id" => $contact_ids[$data["contact"]] ?? null],
    "field_deal" => ["target_id" => $deal_ids[$data["deal"]] ?? null],
    "field_datetime" => $data["datetime"],
    "field_description" => ["value" => $data["description"], "format" => "plain_text"],
    "field_assigned_to" => $admin_uid,
    "status" => 1,
    "uid" => $admin_uid=> ["target_id" => $deal_ids[$data["deal"]] ?? null],
    "field_datetime" => $data["datetime"],
    "field_description" => ["value" => $data["description"], "format" => "plain_text"],
    "status" => 1,
  ]);
  $node->save();
  echo "✅ Created: " . $data["title"] . " (" . $data["type"] . ")\n";
}
'

echo -e "\n=========================================="
echo "✅ Sample Data Created Successfully!"
echo "=========================================="

# Clean up temp files
rm -f /tmp/crm_org_ids.json /tmp/crm_contact_ids.json /tmp/crm_deal_ids.json

# Show summary
bash scripts/verify_content_types.sh
