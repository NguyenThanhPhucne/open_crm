<?php

use Drupal\node\Entity\NodeType;

// Create the CRM Announcement content type if it doesn't exist.
$type = NodeType::load('crm_announcement');
if (!$type) {
  $type = NodeType::create([
    'type' => 'crm_announcement',
    'name' => 'Internal Announcement',
    'description' => 'Used for internal CRM team announcements.',
    'display_submitted' => TRUE,
  ]);
  $type->save();
  node_add_body_field($type);
  echo "Content type crm_announcement created successfully.\n";
} else {
  echo "Content type crm_announcement already exists.\n";
}
