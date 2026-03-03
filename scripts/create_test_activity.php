<?php

use Drupal\node\Entity\Node;

$contact_query = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->range(0, 1)
  ->execute();

$contact_ids = array_values($contact_query);
$current_user = 1;

if (count($contact_ids) > 0) {
  $cid = $contact_ids[0];
  
  $activity1 = Node::create([
    'type' => 'activity',
    'title' => 'Call: Tư vấn sản phẩm',
    'field_type' => 'Call',
    'field_description' => '[Outcome: Khách nghe máy - Quan tâm]

Khách hàng quan tâm đến gói Enterprise. Hỏi về giá và thời gian triển khai.',
    'field_contact' => ['target_id' => $cid],
    'field_assigned_to' => ['target_id' => $current_user],
    'uid' => $current_user,
    'status' => 1,
  ]);
  $activity1->save();
  
  echo "✅ Created Call activity for Contact NID $cid\n";
  echo "Test at: http://open-crm.ddev.site/node/$cid/activities\n";
} else {
  echo "❌ No contacts found\n";
}
