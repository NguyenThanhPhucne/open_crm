<?php

/**
 * @file
 * Import My Activities view from YAML file.
 */

use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Config\FileStorage;

$config_name = 'views.view.my_activities';
$config_path = '/var/www/html/config/views.view.my_activities.yml';

if (!file_exists($config_path)) {
  echo "❌ ERROR: Config file not found: $config_path\n";
  exit(1);
}

// Load YAML content
$yaml_content = file_get_contents($config_path);
$config_data = Yaml::parse($yaml_content);

// Get config factory
$config_factory = \Drupal::configFactory();
$config = $config_factory->getEditable($config_name);

// Set all config data
foreach ($config_data as $key => $value) {
  $config->set($key, $value);
}

// Save
$config->save();

echo "✅ Successfully imported '$config_name'\n\n";
echo "📊 View Details:\n";
echo "   ID: " . $config->get('id') . "\n";
echo "   Label: " . $config->get('label') . "\n";
echo "   Description: " . $config->get('description') . "\n";
echo "   Path: /" . $config->get('display.page_1.display_options.path') . "\n\n";

echo "📋 Fields:\n";
$fields = $config->get('display.default.display_options.fields');
foreach ($fields as $field_name => $field_config) {
  echo "   - " . $field_config['label'] . " ($field_name)\n";
}

echo "\n🔄 Clearing cache...\n";
drupal_flush_all_caches();
echo "✅ Cache cleared\n\n";
echo "🌐 Visit: http://open-crm.ddev.site/crm/my-activities\n";
