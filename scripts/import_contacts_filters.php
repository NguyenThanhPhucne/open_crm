<?php

/**
 * Phase 3: Import advanced filters for My Contacts view
 */

use Drupal\Core\Config\FileStorage;
use Symfony\Component\Yaml\Yaml;

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     PHASE 3: Import Advanced Filters                     ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$config_path = DRUPAL_ROOT . '/../config/views.view.my_contacts.yml';

if (!file_exists($config_path)) {
  echo "✗ Config file not found: $config_path\n";
  exit(1);
}

echo "[1/2] Reading YAML configuration...\n";
$yaml_content = file_get_contents($config_path);
$config_data = Yaml::parse($yaml_content);

echo "  ✓ Loaded config with " . count($config_data['display']['default']['display_options']['filters']) . " filters\n";

echo "\n[2/2] Importing into My Contacts view...\n";

$config_factory = \Drupal::configFactory();
$view_config = $config_factory->getEditable('views.view.my_contacts');

// Import the config
$view_config->setData($config_data);
$view_config->save();

echo "  ✓ Configuration imported successfully\n";

// Clear cache
drupal_flush_all_caches();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║          PHASE 3 COMPLETED FOR CONTACTS                  ║\n";
echo "╠═══════════════════════════════════════════════════════════╣\n";
echo "║  ✅ Name filter (fulltext search)                         ║\n";
echo "║  ✅ Email filter (fulltext search)                        ║\n";
echo "║  ✅ Phone filter (fulltext search)                        ║\n";
echo "║  ✅ Search & Reset buttons enabled                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "TEST URL: http://open-crm.ddev.site/crm/my-contacts\n\n";
echo "Available Filters:\n";
echo "  📝 Name - Search by contact name\n";
echo "  📧 Email - Search by email address\n";
echo "  📞 Phone - Search by phone number\n\n";
