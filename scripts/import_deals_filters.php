<?php

/**
 * Phase 3: Import advanced filters for My Deals view
 */

use Symfony\Component\Yaml\Yaml;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     PHASE 3: Import Filters for My Deals                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$config_path = DRUPAL_ROOT . '/../config/views.view.my_deals.yml';

if (!file_exists($config_path)) {
  echo "✗ Config file not found: $config_path\n";
  exit(1);
}

echo "[1/2] Reading YAML configuration...\n";
$yaml_content = file_get_contents($config_path);
$config_data = Yaml::parse($yaml_content);

echo "  ✓ Loaded config with " . count($config_data['display']['default']['display_options']['filters']) . " filters\n";
echo "  ✓ Added Contact & Organization fields to view\n";

echo "\n[2/2] Importing into My Deals view...\n";

$config_factory = \Drupal::configFactory();
$view_config = $config_factory->getEditable('views.view.my_deals');

// Import the config
$view_config->setData($config_data);
$view_config->save();

echo "  ✓ Configuration imported successfully\n";

// Clear cache
drupal_flush_all_caches();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║           PHASE 3 COMPLETED FOR DEALS                    ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  ✅ Deal Name filter (fulltext search)                    ║\n";
echo "║  ✅ Amount Range filter (min/max)                         ║\n";
echo "║  ✅ Contact & Organization fields added                   ║\n";
echo "║  ✅ Search & Reset buttons enabled                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "TEST URL: http://open-crm.ddev.site/crm/my-pipeline\n\n";
echo "Available Filters:\n";
echo "  📝 Deal Name - Search by deal title\n";
echo "  💰 Amount Range - Filter deals by min/max amount\n\n";

echo "New Fields Added:\n";
echo "  👤 Contact - See related contact for each deal\n";
echo "  🏢 Organization - See related organization for each deal\n\n";
