#!/usr/bin/env php
<?php

/**
 * @file
 * Fixture Loader - Load sample data from YAML fixtures
 * 
 * This script loads sample data from fixtures/ directory into Drupal.
 * Separates development fixtures from production data.
 * 
 * Usage:
 *   ddev drush scr scripts/load_fixtures.php development
 *   ddev drush scr scripts/load_fixtures.php production
 *   ddev drush scr scripts/load_fixtures.php development --clean
 */

use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;

echo "🔧 CRM FIXTURE LOADER\n";
echo "════════════════════════════════════════════════════════\n\n";

// Parse arguments
$environment = $args[0] ?? 'development';
$clean = in_array('--clean', $args ?? []);

if (!in_array($environment, ['development', 'production'])) {
  echo "❌ Invalid environment: $environment\n";
  echo "   Valid options: development, production\n";
  exit(1);
}

$fixtures_path = DRUPAL_ROOT . '/../fixtures/' . $environment;

if (!is_dir($fixtures_path)) {
  echo "❌ Fixtures directory not found: $fixtures_path\n";
  exit(1);
}

echo "📂 Environment: $environment\n";
echo "📂 Path: $fixtures_path\n";
echo "🧹 Clean mode: " . ($clean ? 'Yes (will delete existing sample data)' : 'No') . "\n";
echo "\n";

// Storage for created entity IDs
$entity_map = [
  'organizations' => [],
  'contacts' => [],
  'deals' => [],
  'activities' => [],
];

// Storage for user IDs
$user_map = [];

// Helper function: Load YAML file
function loadFixture($path, $filename) {
  $filepath = $path . '/' . $filename;
  if (!file_exists($filepath)) {
    echo "⚠️  File not found: $filename\n";
    return null;
  }
  
  try {
    $content = file_get_contents($filepath);
    $data = Yaml::parse($content);
    echo "✅ Loaded: $filename\n";
    return $data;
  } catch (Exception $e) {
    echo "❌ Error loading $filename: " . $e->getMessage() . "\n";
    return null;
  }
}

// Helper function: Get or load users
function getUserMap() {
  global $user_map;
  
  if (!empty($user_map)) {
    return $user_map;
  }
  
  $usernames = ['admin', 'manager', 'salesrep1', 'salesrep2'];
  foreach ($usernames as $username) {
    $users = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);
    
    if ($user = reset($users)) {
      $user_map[$username] = $user->id();
    } else {
      $user_map[$username] = 1; // Fallback to admin
    }
  }
  
  return $user_map;
}

// Helper function: Get taxonomy term ID by vocabulary and name
function getTermId($vocab, $name) {
  static $term_cache = [];
  
  $cache_key = $vocab . ':' . $name;
  if (isset($term_cache[$cache_key])) {
    return $term_cache[$cache_key];
  }
  
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties(['vid' => $vocab, 'name' => $name]);
  
  $term = reset($terms);
  $term_id = $term ? $term->id() : null;
  $term_cache[$cache_key] = $term_id;
  
  return $term_id;
}

// Helper function: Map industry name to vocabulary
function getIndustryTermId($name) {
  return getTermId('industry', $name);
}

// Helper function: Map lead source to vocabulary
function getLeadSourceTermId($name) {
  return getTermId('lead_source', $name);
}

// Helper function: Map deal stage to vocabulary
function getDealStageTermId($name) {
  return getTermId('pipeline_stage', $name);
}

// Helper function: Map activity type to vocabulary
function getActivityTypeTermId($name) {
  return getTermId('activity_type', $name);
}

// Clean existing sample data if requested
if ($clean) {
  echo "🧹 CLEANING EXISTING SAMPLE DATA\n";
  echo "────────────────────────────────────────────────────────\n";
  
  $types = ['activity', 'deal', 'contact', 'organization'];
  foreach ($types as $type) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $type)
      ->accessCheck(FALSE);
    $nids = $query->execute();
    
    if (!empty($nids)) {
      $nodes = Node::loadMultiple($nids);
      foreach ($nodes as $node) {
        $node->delete();
      }
      echo "🗑️  Deleted " . count($nids) . " $type nodes\n";
    }
  }
  echo "\n";
}

// Step 1: Load Organizations
echo "🏢 LOADING ORGANIZATIONS\n";
echo "────────────────────────────────────────────────────────\n";

$org_data = loadFixture($fixtures_path, 'organizations.yml');
if ($org_data && isset($org_data['organizations'])) {
  $user_map = getUserMap();
  
  foreach ($org_data['organizations'] as $org) {
    // Determine owner (round-robin between sales reps for dev data)
    $owner_uid = $user_map['salesrep1'] ?? 1;
    if (isset($org['id']) && substr($org['id'], -2) === 'co') {
      $owner_uid = $user_map['salesrep2'] ?? 1;
    }
    
    $node = Node::create([
      'type' => 'organization',
      'title' => $org['title'],
      'field_website' => !empty($org['website']) ? ['uri' => $org['website']] : null,
      'field_address' => $org['address'] ?? null,
      'field_industry' => !empty($org['industry']) ? ['target_id' => getIndustryTermId($org['industry'])] : null,
      'field_owner' => ['target_id' => $owner_uid],
      'field_annual_revenue' => $org['annual_revenue'] ?? null,
      'field_employees_count' => $org['employees_count'] ?? null,
      'body' => !empty($org['notes']) ? ['value' => $org['notes'], 'format' => 'plain_text'] : null,
      'status' => 1,
    ]);
    
    $node->save();
    $entity_map['organizations'][$org['id']] = $node->id();
    
    echo "  ✅ " . $org['title'] . " (NID: " . $node->id() . ", Owner: $owner_uid)\n";
  }
  
  echo "  📊 Created " . count($entity_map['organizations']) . " organizations\n";
}
echo "\n";

// Step 2: Load Contacts
echo "👥 LOADING CONTACTS\n";
echo "────────────────────────────────────────────────────────\n";

$contact_data = loadFixture($fixtures_path, 'contacts.yml');
if ($contact_data && isset($contact_data['contacts'])) {
  $user_map = getUserMap();
  
  foreach ($contact_data['contacts'] as $contact) {
    // Resolve organization reference
    $org_nid = null;
    if (!empty($contact['organization']) && isset($entity_map['organizations'][$contact['organization']])) {
      $org_nid = $entity_map['organizations'][$contact['organization']];
    }
    
    // Inherit owner from organization
    $owner_uid = $user_map['salesrep1'] ?? 1;
    if ($org_nid) {
      $org_node = Node::load($org_nid);
      if ($org_node && $org_node->hasField('field_owner') && !$org_node->get('field_owner')->isEmpty()) {
        $owner_uid = $org_node->get('field_owner')->target_id;
      }
    }
    
    $node = Node::create([
      'type' => 'contact',
      'title' => $contact['title'],
      'field_email' => $contact['email'] ?? null,
      'field_phone' => $contact['phone'] ?? null,
      'field_position' => $contact['position'] ?? null,
      'field_organization' => $org_nid ? ['target_id' => $org_nid] : null,
      'field_lead_source' => !empty($contact['lead_source']) ? ['target_id' => getLeadSourceTermId($contact['lead_source'])] : null,
      'field_owner' => ['target_id' => $owner_uid],
      'body' => !empty($contact['notes']) ? ['value' => $contact['notes'], 'format' => 'plain_text'] : null,
      'status' => 1,
    ]);
    
    $node->save();
    $entity_map['contacts'][$contact['id']] = $node->id();
    
    echo "  ✅ " . $contact['title'] . " (" . $contact['position'] . ", NID: " . $node->id() . ", Owner: $owner_uid)\n";
  }
  
  echo "  📊 Created " . count($entity_map['contacts']) . " contacts\n";
}
echo "\n";

// Step 3: Load Deals
echo "💼 LOADING DEALS\n";
echo "────────────────────────────────────────────────────────\n";

$deal_data = loadFixture($fixtures_path, 'deals.yml');
if ($deal_data && isset($deal_data['deals'])) {
  foreach ($deal_data['deals'] as $deal) {
    // Resolve references
    $contact_nid = null;
    $org_nid = null;
    $owner_uid = 1;
    
    if (!empty($deal['contact']) && isset($entity_map['contacts'][$deal['contact']])) {
      $contact_nid = $entity_map['contacts'][$deal['contact']];
      
      // Inherit owner from contact
      $contact_node = Node::load($contact_nid);
      if ($contact_node && $contact_node->hasField('field_owner') && !$contact_node->get('field_owner')->isEmpty()) {
        $owner_uid = $contact_node->get('field_owner')->target_id;
      }
    }
    
    if (!empty($deal['organization']) && isset($entity_map['organizations'][$deal['organization']])) {
      $org_nid = $entity_map['organizations'][$deal['organization']];
    }
    
    $node = Node::create([
      'type' => 'deal',
      'title' => $deal['title'],
      'field_amount' => $deal['amount'] ?? null,
      'field_probability' => $deal['probability'] ?? null,
      'field_stage' => !empty($deal['stage']) ? ['target_id' => getDealStageTermId($deal['stage'])] : null,
      'field_contact' => $contact_nid ? ['target_id' => $contact_nid] : null,
      'field_organization' => $org_nid ? ['target_id' => $org_nid] : null,
      'field_closing_date' => $deal['closing_date'] ?? null,
      'field_owner' => ['target_id' => $owner_uid],
      'body' => !empty($deal['description']) ? ['value' => $deal['description'], 'format' => 'plain_text'] : null,
      'status' => 1,
    ]);
    
    $node->save();
    $entity_map['deals'][$deal['id']] = $node->id();
    
    echo "  ✅ " . $deal['title'] . " ($" . number_format($deal['amount']) . ", NID: " . $node->id() . ", Owner: $owner_uid)\n";
  }
  
  echo "  📊 Created " . count($entity_map['deals']) . " deals\n";
}
echo "\n";

// Step 4: Load Activities
echo "📅 LOADING ACTIVITIES\n";
echo "────────────────────────────────────────────────────────\n";

$activity_data = loadFixture($fixtures_path, 'activities.yml');
if ($activity_data && isset($activity_data['activities'])) {
  $user_map = getUserMap();
  
  foreach ($activity_data['activities'] as $activity) {
    // Resolve references
    $contact_nid = null;
    $deal_nid = null;
    $assigned_uid = $user_map['salesrep1'] ?? 1;
    
    if (!empty($activity['contact']) && isset($entity_map['contacts'][$activity['contact']])) {
      $contact_nid = $entity_map['contacts'][$activity['contact']];
    }
    
    if (!empty($activity['deal']) && isset($entity_map['deals'][$activity['deal']])) {
      $deal_nid = $entity_map['deals'][$activity['deal']];
    }
    
    if (!empty($activity['assigned_to']) && isset($user_map[$activity['assigned_to']])) {
      $assigned_uid = $user_map[$activity['assigned_to']];
    }
    
    $node = Node::create([
      'type' => 'activity',
      'title' => $activity['title'],
      'field_type' => !empty($activity['type']) ? ['target_id' => getActivityTypeTermId($activity['type'])] : null,
      'field_contact' => $contact_nid ? ['target_id' => $contact_nid] : null,
      'field_deal' => $deal_nid ? ['target_id' => $deal_nid] : null,
      'field_datetime' => $activity['datetime'] ?? null,
      'field_description' => !empty($activity['description']) ? ['value' => $activity['description'], 'format' => 'plain_text'] : null,
      'field_outcome' => !empty($activity['outcome']) ? ['value' => $activity['outcome'], 'format' => 'plain_text'] : null,
      'field_assigned_to' => ['target_id' => $assigned_uid],
      'status' => 1,
    ]);
    
    $node->save();
    $entity_map['activities'][$activity['id']] = $node->id();
    
    echo "  ✅ " . $activity['title'] . " (" . $activity['type'] . ", NID: " . $node->id() . ")\n";
  }
  
  echo "  📊 Created " . count($entity_map['activities']) . " activities\n";
}
echo "\n";

// Summary
echo "═══════════════════════════════════════════════════════\n";
echo "✅ FIXTURE LOADING COMPLETE!\n";
echo "═══════════════════════════════════════════════════════\n\n";

echo "📊 SUMMARY:\n";
echo "  🏢 Organizations: " . count($entity_map['organizations']) . "\n";
echo "  👥 Contacts: " . count($entity_map['contacts']) . "\n";
echo "  💼 Deals: " . count($entity_map['deals']) . "\n";
echo "  📅 Activities: " . count($entity_map['activities']) . "\n";
echo "\n";

// Verify actual counts in database
$counts = [
  'Organizations' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'organization'])),
  'Contacts' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact'])),
  'Deals' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal'])),
  'Activities' => count(\Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'activity'])),
];

echo "🔍 DATABASE VERIFICATION:\n";
foreach ($counts as $type => $count) {
  echo "  $type: $count nodes\n";
}
echo "\n";

echo "💡 NEXT STEPS:\n";
echo "  1. Test views: ddev drush scr scripts/test_views.php\n";
echo "  2. Re-index search: ddev drush search-api:index\n";
echo "  3. Clear cache: ddev drush cr\n";
echo "\n";
