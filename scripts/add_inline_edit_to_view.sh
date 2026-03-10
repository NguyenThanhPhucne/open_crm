#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════╗"
echo "║          ADD INLINE EDIT TO MY CONTACTS VIEW                     ║"
echo "╚══════════════════════════════════════════════════════════════════╝"
echo ""

# Step 1: Add CRM Quick Edit Link field to My Contacts view
echo "1️⃣  Adding Quick Edit Link field to My Contacts view..."

ddev drush eval '
use Drupal\views\Entity\View;

$view = View::load("my_contacts");

if ($view) {
  $display = &$view->getDisplay("default");
  
  // Add crm_edit_link field
  $fields = $display["display_options"]["fields"];
  
  // Add at the end
  $fields["crm_edit_link"] = [
    "id" => "crm_edit_link",
    "table" => "node",
    "field" => "crm_edit_link",
    "plugin_id" => "crm_edit_link",
    "label" => "Actions",
    "element_class" => "views-field-edit-actions",
    "element_wrapper_class" => "edit-actions-wrapper",
  ];
  
  $display["display_options"]["fields"] = $fields;
  
  $view->save();
  echo "✅ Added Quick Edit Link field to My Contacts view\n";
} else {
  echo "❌ View not found: my_contacts\n";
}
'

# Step 2: Clear cache
echo ""
echo "2️⃣  Clearing cache..."
ddev drush cr

echo ""
echo "✅ DONE! Visit: http://open-crm.ddev.site/crm/my-contacts"
echo ""
