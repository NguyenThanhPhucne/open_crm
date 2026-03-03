<?php

/**
 * @file
 * Phase 1 & 2 Comprehensive Validation and Health Check.
 */

echo "🏥 PHASE 1 & 2 COMPREHENSIVE VALIDATION\n";
echo "=========================================\n\n";

$health_score = 0;
$max_score = 0;
$issues = [];
$recommendations = [];

// ============================================
// PHASE 1: CRITICAL INFRASTRUCTURE
// ============================================

echo "📦 PHASE 1: CRITICAL INFRASTRUCTURE\n";
echo "-----------------------------------------\n\n";

// 1.1 Database Indexes
echo "1️⃣  Database Indexes (9 expected)...\n";
$database = \Drupal::database();
$indexed_tables = [
  'node__field_owner' => 'Owner assignments',
  'node__field_organization' => 'Organization references',
  'node__field_stage' => 'Pipeline stages',
  'node__field_contact' => 'Contact references',
  'node__field_contact_ref' => 'Contact refs (alt)',
  'node__field_deal' => 'Deal references',
  'node__field_type' => 'Activity types',
  'node__field_assigned_to' => 'Activity assignments',
  'node__field_assigned_staff' => 'Staff assignments',
];

$index_count = 0;
foreach ($indexed_tables as $table => $description) {
  $query = "SELECT COUNT(*) FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '$table' 
            AND INDEX_NAME LIKE 'idx_%'";
  $count = $database->query($query)->fetchField();
  
  if ($count > 0) {
    echo "   ✅ $table: indexed\n";
    $index_count++;
    $health_score += 5;
  } else {
    echo "   ❌ $table: NO INDEX\n";
    $issues[] = "Missing index on $table ($description)";
  }
}
$max_score += 45; // 9 indexes × 5 points
echo "   Score: $index_count/9 indexes\n\n";

// 1.2 Search API Indexes
echo "2️⃣  Search API Indexes (3 expected)...\n";
$search_indexes = \Drupal\search_api\Entity\Index::loadMultiple();
$expected_indexes = ['crm_contacts_index', 'crm_deals_index', 'crm_organizations_index'];

$search_score = 0;
foreach ($expected_indexes as $index_id) {
  if (isset($search_indexes[$index_id])) {
    $index = $search_indexes[$index_id];
    $total = $index->getTrackerInstance()->getTotalItemsCount();
    $indexed = $index->getTrackerInstance()->getIndexedItemsCount();
    $percentage = $total > 0 ? round(($indexed / $total) * 100) : 0;
    
    if ($indexed === $total && $total > 0) {
      echo "   ✅ " . $index->label() . ": $indexed/$total (100%)\n";
      $health_score += 10;
      $search_score += 10;
    } elseif ($indexed > 0) {
      echo "   ⚠️  " . $index->label() . ": $indexed/$total ($percentage%)\n";
      $health_score += 5;
      $search_score += 5;
      $issues[] = $index->label() . " not fully indexed ($percentage%)";
    } else {
      echo "   ❌ " . $index->label() . ": No items indexed\n";
      $issues[] = $index->label() . " has no indexed items";
    }
  } else {
    echo "   ❌ $index_id: NOT FOUND\n";
    $issues[] = "Search index $index_id missing";
  }
}
$max_score += 30; // 3 indexes × 10 points
echo "   Score: $search_score/30 points\n\n";

// 1.3 field_outcome on Activity
echo "3️⃣  Activity Content Type Fields...\n";
$activity_fields = ['field_outcome', 'field_type', 'field_description', 'field_contact'];
$field_count = 0;

foreach ($activity_fields as $field_name) {
  $field = \Drupal\field\Entity\FieldConfig::loadByName('node', 'activity', $field_name);
  if ($field) {
    echo "   ✅ $field_name: configured\n";
    $field_count++;
    $health_score += 2.5;
  } else {
    echo "   ❌ $field_name: MISSING\n";
    $issues[] = "Activity missing $field_name field";
  }
}
$max_score += 10; // 4 fields × 2.5 points
echo "   Score: $field_count/4 fields\n\n";

// ============================================
// PHASE 2: CUSTOMER PORTAL & CACHING
// ============================================

echo "\n🚀 PHASE 2: CUSTOMER PORTAL & CACHING\n";
echo "-----------------------------------------\n\n";

// 2.1 Customer Portal Field
echo "4️⃣  Customer Portal Setup...\n";
$portal_field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'field_contact_profile');
if ($portal_field) {
  echo "   ✅ field_contact_profile: configured\n";
  $settings = $portal_field->getSettings();
  $target_bundles = $settings['handler_settings']['target_bundles'] ?? [];
  
  if (in_array('contact', $target_bundles)) {
    echo "   ✅ Target bundle: contact\n";
    $health_score += 10;
  } else {
    echo "   ⚠️  Target bundle misconfigured\n";
    $health_score += 5;
    $issues[] = "field_contact_profile not targeting contact bundle";
  }
} else {
  echo "   ❌ field_contact_profile: MISSING\n";
  $issues[] = "Customer portal field not configured";
}
$max_score += 10;
echo "\n";

// 2.2 my_projects View
echo "5️⃣  Customer Portal View (/my/projects)...\n";
$projects_view = \Drupal\views\Views::getView('my_projects');
if ($projects_view) {
  echo "   ✅ View exists\n";
  
  // Check enabled
  if ($projects_view->storage->status()) {
    echo "   ✅ Status: Enabled\n";
    $health_score += 5;
  } else {
    echo "   ❌ Status: Disabled\n";
    $issues[] = "my_projects view is disabled";
  }
  
  // Check page display
  $displays = $projects_view->storage->get('display');
  if (isset($displays['page_1'])) {
    echo "   ✅ Page display configured\n";
    $path = $displays['page_1']['display_options']['path'] ?? null;
    if ($path === 'my/projects') {
      echo "   ✅ Path: /my/projects\n";
      $health_score += 5;
    } else {
      echo "   ⚠️  Path: $path (unexpected)\n";
      $health_score += 2;
    }
  }
} else {
  echo "   ❌ View not found\n";
  $issues[] = "my_projects view missing";
}
$max_score += 10;
echo "\n";

// 2.3 Caching Modules
echo "6️⃣  Caching Configuration...\n";
$cache_modules = [
  'dynamic_page_cache' => 'Dynamic Page Cache',
  'page_cache' => 'Internal Page Cache',
];

$cache_count = 0;
foreach ($cache_modules as $module => $label) {
  if (\Drupal::moduleHandler()->moduleExists($module)) {
    echo "   ✅ $label: Enabled\n";
    $cache_count++;
    $health_score += 5;
  } else {
    echo "   ❌ $label: Disabled\n";
    $issues[] = "$label not enabled";
  }
}
$max_score += 10; // 2 modules × 5 points
echo "   Score: $cache_count/2 modules\n\n";

// 2.4 Views Caching
echo "7️⃣  Views Cache Configuration...\n";
$cached_views = ['my_contacts', 'my_deals', 'my_projects', 'my_organizations', 'pipeline'];
$cached_count = 0;

foreach ($cached_views as $view_id) {
  $view = \Drupal\views\Views::getView($view_id);
  if ($view) {
    $displays = $view->storage->get('display');
    $has_cache = false;
    
    foreach ($displays as $display) {
      if (isset($display['display_options']['cache']['type']) && 
          $display['display_options']['cache']['type'] === 'tag') {
        $has_cache = true;
        break;
      }
    }
    
    if ($has_cache) {
      echo "   ✅ $view_id: tag cache enabled\n";
      $cached_count++;
      $health_score += 2;
    } else {
      echo "   ⚠️  $view_id: no tag cache\n";
      $recommendations[] = "Enable tag-based caching for $view_id";
    }
  }
}
$max_score += 10; // 5 views × 2 points
echo "   Score: $cached_count/5 views cached\n\n";

// ============================================
// CONTENT & DATA QUALITY
// ============================================

echo "\n📊 CONTENT & DATA QUALITY\n";
echo "-----------------------------------------\n\n";

echo "8️⃣  Content Types & Counts...\n";
$content_types = ['contact', 'deal', 'organization', 'activity'];
$content_exists = false;

foreach ($content_types as $type) {
  $count = $database->query(
    "SELECT COUNT(*) FROM {node_field_data} WHERE type = :type AND status = 1",
    [':type' => $type]
  )->fetchField();
  
  echo "   📌 " . ucfirst($type) . ": $count nodes\n";
  
  if ($count > 0) {
    $content_exists = true;
    $health_score += 2.5;
  }
}
$max_score += 10; // 4 types × 2.5 points

if ($content_exists) {
  echo "   ✅ Content data present\n";
} else {
  echo "   ⚠️  No content found\n";
  $recommendations[] = "Add sample content for testing";
}
echo "\n";

// ============================================
// FINAL SCORE & RECOMMENDATIONS
// ============================================

$percentage = round(($health_score / $max_score) * 100);

echo "=========================================\n";
echo "🎯 FINAL HEALTH SCORE\n";
echo "=========================================\n\n";

echo "Score: $health_score / $max_score points ($percentage%)\n\n";

// Rating
if ($percentage >= 95) {
  echo "Rating: 🌟🌟🌟🌟🌟 EXCELLENT\n";
  echo "Status: ✅ Production Ready\n";
} elseif ($percentage >= 85) {
  echo "Rating: 🌟🌟🌟🌟 VERY GOOD\n";
  echo "Status: ✅ Near Production Ready\n";
} elseif ($percentage >= 75) {
  echo "Rating: 🌟🌟🌟 GOOD\n";
  echo "Status: ⚠️  Minor Issues\n";
} elseif ($percentage >= 60) {
  echo "Rating: 🌟🌟 FAIR\n";
  echo "Status: ⚠️  Several Issues\n";
} else {
  echo "Rating: 🌟 NEEDS WORK\n";
  echo "Status: ❌ Major Issues\n";
}

echo "\n";

// Issues
if (!empty($issues)) {
  echo "⚠️  CRITICAL ISSUES (" . count($issues) . "):\n";
  foreach ($issues as $i => $issue) {
    echo "   " . ($i + 1) . ". $issue\n";
  }
  echo "\n";
}

// Recommendations
if (!empty($recommendations)) {
  echo "💡 RECOMMENDATIONS (" . count($recommendations) . "):\n";
  foreach ($recommendations as $i => $rec) {
    echo "   " . ($i + 1) . ". $rec\n";
  }
  echo "\n";
}

// Summary breakdown
echo "📋 SCORE BREAKDOWN:\n";
echo "   Phase 1 - Database Indexes: " . ($index_count * 5) . "/45\n";
echo "   Phase 1 - Search API: $search_score/30\n";
echo "   Phase 1 - Activity Fields: " . ($field_count * 2.5) . "/10\n";
echo "   Phase 2 - Customer Portal: Variable/20\n";
echo "   Phase 2 - Caching: " . ($cache_count * 5 + $cached_count * 2) . "/20\n";
echo "   Content Quality: Variable/10\n";

echo "\n🎉 Validation complete!\n";

// Return status code
return ($percentage >= 85) ? 0 : 1;
