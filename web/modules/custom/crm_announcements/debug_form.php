<?php
/**
 * Diagnostic script to find action keys in node form for announcement
 */
$type = 'crm_announcement';
$node = \Drupal::entityTypeManager()->getStorage('node')->create(['type' => $type]);
$form = \Drupal::service('entity.form_builder')->getForm($node);

echo "Non-property Keys at Form Root:\n";
foreach ($form as $key => $value) {
    if (strpos($key, '#') !== 0) {
        echo "- $key\n";
    }
}

if (isset($form['actions'])) {
    echo "\nKeys inside 'actions':\n";
    foreach ($form['actions'] as $key => $value) {
        if (strpos($key, '#') !== 0) {
            echo "- $key\n";
        }
    }
} else {
    echo "\n'actions' NOT FOUND!\n";
}
