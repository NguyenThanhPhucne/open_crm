<?php

/**
 * Import my_activities and my_contacts view configs.
 */

use Drupal\Component\Serialization\Yaml;

$config_factory = \Drupal::configFactory();
$source = '/var/www/html/config';

// Import my_activities
echo "Importing my_activities view...\n";
$config = $config_factory->getEditable('views.view.my_activities');
$yaml = file_get_contents($source . '/views.view.my_activities.yml');
$data = Yaml::decode($yaml);
foreach ($data as $key => $value) {
  if ($key !== 'uuid' && $key !== '_core') {
    $config->set($key, $value);
  }
}
$config->save();
echo "✅ Imported my_activities\n";

// Import my_contacts
echo "Importing my_contacts view...\n";
$config = $config_factory->getEditable('views.view.my_contacts');
$yaml = file_get_contents($source . '/views.view.my_contacts.yml');
$data = Yaml::decode($yaml);
foreach ($data as $key => $value) {
  if ($key !== 'uuid' && $key !== '_core') {
    $config->set($key, $value);
  }
}
$config->save();
echo "✅ Imported my_contacts\n";

// Clear caches
drupal_flush_all_caches();
echo "✅ Cache cleared!\n";
echo "\nDone! Edit/Delete buttons added to My Activities and My Contacts pages.\n";
