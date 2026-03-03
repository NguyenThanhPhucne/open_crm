<?php

/**
 * Phase 3: Setup Search API indexes for CRM entities
 */

use Drupal\search_api\Entity\Server;
use Drupal\search_api\Entity\Index;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║        PHASE 3: Search API & Advanced Filters            ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "[1/4] Creating Search API Server...\n";

// Create search server using database backend
$server = Server::load('crm_database');
if (!$server) {
  $server = Server::create([
    'id' => 'crm_database',
    'name' => 'CRM Database Server',
    'description' => 'Database server for CRM search',
    'backend' => 'search_api_db',
    'backend_config' => [
      'database' => 'default:default',
      'min_chars' => 3,
      'matching' => 'words',
    ],
  ]);
  $server->save();
  echo "  ✓ Created server: CRM Database Server\n";
} else {
  echo "  ⊙ Server already exists: CRM Database Server\n";
}

echo "\n[2/4] Creating Search Index for Contacts...\n";

// Create Contacts index
$contacts_index = Index::load('crm_contacts');
if (!$contacts_index) {
  $contacts_index = Index::create([
    'id' => 'crm_contacts',
    'name' => 'CRM Contacts',
    'description' => 'Search index for CRM contacts',
    'server' => 'crm_database',
    'datasource_settings' => [
      'entity:node' => [
        'bundles' => [
          'default' => FALSE,
          'selected' => ['contact'],
        ],
      ],
    ],
    'tracker_settings' => [
      'default' => [],
    ],
    'processor_settings' => [
      'html_filter' => [
        'weights' => ['preprocess_index' => -10, 'preprocess_query' => -10],
      ],
      'ignorecase' => [
        'weights' => ['preprocess_index' => -20, 'preprocess_query' => -20],
      ],
      'tokenizer' => [
        'weights' => ['preprocess_index' => -6, 'preprocess_query' => -6],
      ],
    ],
  ]);
  
  // Add fields to index
  $contacts_index->addField(
    $contacts_index->createField('title', [
      'label' => 'Title (Name)',
      'type' => 'text',
      'datasource_id' => 'entity:node',
      'property_path' => 'title',
      'boost' => 8.0,
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_email', [
      'label' => 'Email',
      'type' => 'string',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_email',
      'boost' => 5.0,
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_phone', [
      'label' => 'Phone',
      'type' => 'string',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_phone',
      'boost' => 3.0,
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_position', [
      'label' => 'Position',
      'type' => 'string',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_position',
      'boost' => 2.0,
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_organization', [
      'label' => 'Organization',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_organization',
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_source', [
      'label' => 'Source',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_source',
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_customer_type', [
      'label' => 'Customer Type',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_customer_type',
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('field_owner', [
      'label' => 'Owner',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_owner',
    ])
  );
  
  $contacts_index->addField(
    $contacts_index->createField('created', [
      'label' => 'Created Date',
      'type' => 'date',
      'datasource_id' => 'entity:node',
      'property_path' => 'created',
    ])
  );
  
  $contacts_index->save();
  echo "  ✓ Created index: CRM Contacts\n";
  echo "  ✓ Added 9 searchable fields\n";
} else {
  echo "  ⊙ Index already exists: CRM Contacts\n";
}

echo "\n[3/4] Creating Search Index for Deals...\n";

// Create Deals index
$deals_index = Index::load('crm_deals');
if (!$deals_index) {
  $deals_index = Index::create([
    'id' => 'crm_deals',
    'name' => 'CRM Deals',
    'description' => 'Search index for CRM deals',
    'server' => 'crm_database',
    'datasource_settings' => [
      'entity:node' => [
        'bundles' => [
          'default' => FALSE,
          'selected' => ['deal'],
        ],
      ],
    ],
    'tracker_settings' => [
      'default' => [],
    ],
    'processor_settings' => [
      'html_filter' => [
        'weights' => ['preprocess_index' => -10, 'preprocess_query' => -10],
      ],
      'ignorecase' => [
        'weights' => ['preprocess_index' => -20, 'preprocess_query' => -20],
      ],
      'tokenizer' => [
        'weights' => ['preprocess_index' => -6, 'preprocess_query' => -6],
      ],
    ],
  ]);
  
  // Add fields to index
  $deals_index->addField(
    $deals_index->createField('title', [
      'label' => 'Title',
      'type' => 'text',
      'datasource_id' => 'entity:node',
      'property_path' => 'title',
      'boost' => 8.0,
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_amount', [
      'label' => 'Amount',
      'type' => 'decimal',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_amount',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_stage', [
      'label' => 'Stage',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_stage',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_contact', [
      'label' => 'Contact',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_contact',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_organization', [
      'label' => 'Organization',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_organization',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_owner', [
      'label' => 'Owner',
      'type' => 'integer',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_owner',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('field_expected_close_date', [
      'label' => 'Expected Close Date',
      'type' => 'date',
      'datasource_id' => 'entity:node',
      'property_path' => 'field_expected_close_date',
    ])
  );
  
  $deals_index->addField(
    $deals_index->createField('created', [
      'label' => 'Created Date',
      'type' => 'date',
      'datasource_id' => 'entity:node',
      'property_path' => 'created',
    ])
  );
  
  $deals_index->save();
  echo "  ✓ Created index: CRM Deals\n";
  echo "  ✓ Added 8 searchable fields\n";
} else {
  echo "  ⊙ Index already exists: CRM Deals\n";
}

echo "\n[4/4] Indexing content...\n";

// Index contacts
if ($contacts_index) {
  $contacts_index->indexItems();
  $indexed = $contacts_index->getTrackerInstance()->getIndexedItemsCount();
  $total = $contacts_index->getTrackerInstance()->getTotalItemsCount();
  echo "  ✓ Contacts indexed: $indexed/$total items\n";
}

// Index deals
if ($deals_index) {
  $deals_index->indexItems();
  $indexed = $deals_index->getTrackerInstance()->getIndexedItemsCount();
  $total = $deals_index->getTrackerInstance()->getTotalItemsCount();
  echo "  ✓ Deals indexed: $indexed/$total items\n";
}

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║              PHASE 3 - PART 1 COMPLETED                   ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  ✅ Search API Server created                             ║\n";
echo "║  ✅ Contacts search index created                         ║\n";
echo "║  ✅ Deals search index created                            ║\n";
echo "║  ✅ Content indexed                                       ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "NEXT STEPS:\n";
echo "  1. Create search views with exposed filters\n";
echo "  2. Add faceted search blocks\n";
echo "  3. Configure search UI\n\n";
