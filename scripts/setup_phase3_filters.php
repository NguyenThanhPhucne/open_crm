<?php

/**
 * Phase 3: Add Advanced Filters to CRM Views (Simpler approach)
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     PHASE 3: Advanced Filters for CRM Views              ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Creating advanced exposed filters for existing views...\n\n";

// Load existing views
$view_storage = \Drupal::entityTypeManager()->getStorage('view');

echo "[1/2] Enhancing My Contacts view with filters...\n";

$contacts_view = $view_storage->load('my_contacts');
if ($contacts_view) {
  $display = &$contacts_view->getDisplay('default');
  
  // Enable exposed filters
  $display['display_options']['exposed_form'] = [
    'type' => 'basic',
    'options' => [
      'submit_button' => 'Search',
      'reset_button' => TRUE,
      'reset_button_label' => 'Reset',
      'exposed_sorts_label' => 'Sort by',
      'sort_asc_label' => 'Asc',
      'sort_desc_label' => 'Desc',
    ],
  ];
  
  // Add fulltext search filter
  $display['display_options']['filters']['title'] = [
    'id' => 'title',
    'table' => 'node_field_data',
    'field' => 'title',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => 'contains',
    'value' => '',
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'title_op',
      'label' => 'Name',
      'description' => 'Search by contact name',
      'use_operator' => FALSE,
      'operator' => 'title_op',
      'identifier' => 'name',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => FALSE,
      'remember_roles' => [
        'authenticated' => 'authenticated',
      ],
      'placeholder' => 'Enter contact name...',
    ],
    'is_grouped' => FALSE,
    'plugin_id' => 'string',
  ];
  
  // Add email filter
  $display['display_options']['filters']['field_email_value'] = [
    'id' => 'field_email_value',
    'table' => 'node__field_email',
    'field' => 'field_email_value',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => 'contains',
    'value' => '',
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_email_value_op',
      'label' => 'Email',
      'description' => 'Search by email',
      'use_operator' => FALSE,
      'operator' => 'field_email_value_op',
      'identifier' => 'email',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => FALSE,
      'placeholder' => 'Enter email...',
    ],
    'is_grouped' => FALSE,
    'plugin_id' => 'string',
  ];
  
  // Add source filter
  $display['display_options']['filters']['field_source_target_id'] = [
    'id' => 'field_source_target_id',
    'table' => 'node__field_source',
    'field' => 'field_source_target_id',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => 'or',
    'value' => [],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_source_target_id_op',
      'label' => 'Source',
      'description' => 'Filter by lead source',
      'use_operator' => FALSE,
      'operator' => 'field_source_target_id_op',
      'identifier' => 'source',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => TRUE,
      'remember_roles' => [
        'authenticated' => 'authenticated',
      ],
      'reduce' => FALSE,
    ],
    'is_grouped' => FALSE,
    'vid' => 'source',
    'type' => 'select',
    'hierarchy' => FALSE,
    'limit' => TRUE,
    'error_message' => TRUE,
    'plugin_id' => 'taxonomy_index_tid',
  ];
  
  // Add customer type filter
  $display['display_options']['filters']['field_customer_type_target_id'] = [
    'id' => 'field_customer_type_target_id',
    'table' => 'node__field_customer_type',
    'field' => 'field_customer_type_target_id',
    'relationship' => 'none',
    'group_type' => 'group',
    'admin_label' => '',
    'operator' => 'or',
    'value' => [],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_customer_type_target_id_op',
      'label' => 'Customer Type',
      'description' => 'Filter by customer type (VIP, New, etc.)',
      'use_operator' => FALSE,
      'operator' => 'field_customer_type_target_id_op',
      'identifier' => 'customer_type',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => TRUE,
      'reduce' => FALSE,
    ],
    'is_grouped' => FALSE,
    'vid' => 'customer_type',
    'type' => 'select',
    'hierarchy' => FALSE,
    'limit' => TRUE,
    'error_message' => TRUE,
    'plugin_id' => 'taxonomy_index_tid',
  ];
  
  $contacts_view->save();
  echo "  ✓ Added 4 exposed filters to My Contacts view:\n";
  echo "    - Name (fulltext search)\n";
  echo "    - Email (fulltext search)\n";
  echo "    - Source (taxonomy filter)\n";
  echo "    - Customer Type (taxonomy filter)\n";
} else {
  echo "  ✗ My Contacts view not found\n";
}

echo "\n[2/2] Enhancing My Deals view with filters...\n";

$deals_view = $view_storage->load('my_deals');
if ($deals_view) {
  $display = &$deals_view->getDisplay('default');
  
  // Enable exposed filters
  $display['display_options']['exposed_form'] = [
    'type' => 'basic',
    'options' => [
      'submit_button' => 'Search',
      'reset_button' => TRUE,
      'reset_button_label' => 'Reset',
    ],
  ];
  
  // Add title search filter
  $display['display_options']['filters']['title'] = [
    'id' => 'title',
    'table' => 'node_field_data',
    'field' => 'title',
    'relationship' => 'none',
    'group_type' => 'group',
    'operator' => 'contains',
    'value' => '',
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'title_op',
      'label' => 'Deal Name',
      'description' => 'Search by deal name',
      'use_operator' => FALSE,
      'operator' => 'title_op',
      'identifier' => 'title',
      'required' => FALSE,
      'remember' => FALSE,
      'placeholder' => 'Enter deal name...',
    ],
    'is_grouped' => FALSE,
    'plugin_id' => 'string',
  ];
  
  // Add amount range filter
  $display['display_options']['filters']['field_amount_value'] = [
    'id' => 'field_amount_value',
    'table' => 'node__field_amount',
    'field' => 'field_amount_value',
    'relationship' => 'none',
    'group_type' => 'group',
    'operator' => 'between',
    'value' => [
      'min' => '',
      'max' => '',
    ],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_amount_value_op',
      'label' => 'Amount Range',
      'description' => 'Filter by deal amount',
      'use_operator' => FALSE,
      'operator' => 'field_amount_value_op',
      'identifier' => 'amount',
      'required' => FALSE,
      'remember' => FALSE,
      'min_placeholder' => 'Min',
      'max_placeholder' => 'Max',
    ],
    'is_grouped' => FALSE,
    'plugin_id' => 'numeric',
  ];
  
  // Add stage filter
  $display['display_options']['filters']['field_stage_target_id'] = [
    'id' => 'field_stage_target_id',
    'table' => 'node__field_stage',
    'field' => 'field_stage_target_id',
    'relationship' => 'none',
    'group_type' => 'group',
    'operator' => 'or',
    'value' => [],
    'group' => 1,
    'exposed' => TRUE,
    'expose' => [
      'operator_id' => 'field_stage_target_id_op',
      'label' => 'Pipeline Stage',
      'description' => 'Filter by stage',
      'use_operator' => FALSE,
      'operator' => 'field_stage_target_id_op',
      'identifier' => 'stage',
      'required' => FALSE,
      'remember' => FALSE,
      'multiple' => TRUE,
      'reduce' => FALSE,
    ],
    'is_grouped' => FALSE,
    'vid' => 'pipeline_stage',
    'type' => 'select',
    'hierarchy' => FALSE,
    'limit' => TRUE,
    'error_message' => TRUE,
    'plugin_id' => 'taxonomy_index_tid',
  ];
  
  $deals_view->save();
  echo "  ✓ Added 3 exposed filters to My Deals view:\n";
  echo "    - Deal Name (fulltext search)\n";
  echo "    - Amount Range (numeric filter)\n";
  echo "    - Pipeline Stage (taxonomy filter)\n";
} else {
  echo "  ✗ My Deals view not found\n";
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║              PHASE 3 COMPLETED                            ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  ✅ My Contacts view: 4 exposed filters added             ║\n";
echo "║  ✅ My Deals view: 3 exposed filters added                ║\n";
echo "║  ✅ Search & filter functionality ready                   ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "TEST URLS:\n";
echo "  • Contacts with filters: http://open-crm.ddev.site/crm/my-contacts\n";
echo "  • Deals with filters: http://open-crm.ddev.site/crm/my-deals\n\n";

echo "Available Filters:\n";
echo "  CONTACTS:\n";
echo "    📝 Name - Search by contact name\n";
echo "    📧 Email - Search by email address\n";
echo "    🌐 Source - Filter by lead source (Website, Referral, etc.)\n";
echo "    ⭐ Customer Type - Filter by VIP, New, Potential\n\n";
echo "  DEALS:\n";
echo "    📝 Deal Name - Search by deal title\n";
echo "    💰 Amount Range - Filter by min/max amount\n";
echo "    📊 Pipeline Stage - Filter by New, Proposal, Negotiation, etc.\n\n";
