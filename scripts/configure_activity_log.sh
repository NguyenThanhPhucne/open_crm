#!/bin/bash
# Configure Activity Log display on Contact content type

echo "=== Configuring Activity Log for Contact pages ==="

# Enable the Activity Quick Actions extra field on Contact display
ddev drush ev "
\$display = \Drupal::entityTypeManager()
  ->getStorage('entity_view_display')
  ->load('node.contact.default');

if (\$display) {
  \$content = \$display->get('content');
  
  // Add activity_quick_actions field
  \$content['activity_quick_actions'] = [
    'weight' => 5,
    'region' => 'content',
  ];
  
  \$display->set('content', \$content);
  \$display->save();
  
  echo \"✅ Enabled Activity Quick Actions on Contact display\n\";
} else {
  echo \"⚠️  Contact default display not found\n\";
}
"

# Create sample activities for testing
echo ""
echo "Creating sample activities for testing..."
ddev drush ev "
// Get first 3 contacts
\$contact_query = \Drupal::entityQuery('node')
  ->condition('type', 'contact')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->range(0, 3)
  ->execute();

\$contact_ids = array_values(\$contact_query);
\$current_user = \Drupal::currentUser()->id();

if (!empty(\$contact_ids)) {
  // Create Call activity
  \$activity1 = \Drupal\node\Entity\Node::create([
    'type' => 'activity',
    'title' => 'Call: Tư vấn sản phẩm',
    'field_type' => 'Call',
    'field_description' => '[Outcome: Khách nghe máy - Quan tâm]\n\nKhách hàng quan tâm đến gói Enterprise. Hỏi về giá và thời gian triển khai. Hẹn gọi lại vào thứ 6 tuần sau.',
    'field_contact' => ['target_id' => \$contact_ids[0]],
    'field_assigned_to' => ['target_id' => \$current_user],
    'uid' => \$current_user,
    'status' => 1,
  ]);
  \$activity1->save();
  echo \"✅ Created Call activity\n\";

  // Create Meeting activity
  \$activity2 = \Drupal\node\Entity\Node::create([
    'type' => 'activity',
    'title' => 'Meeting: Demo sản phẩm',
    'field_type' => 'Meeting',
    'field_description' => 'Demo tính năng chính của hệ thống CRM. Mời thêm CTO và Sales Manager tham gia.',
    'field_datetime' => date('Y-m-d', strtotime('+3 days')) . 'T14:00:00',
    'field_contact' => ['target_id' => \$contact_ids[0]],
    'field_assigned_to' => ['target_id' => \$current_user],
    'uid' => \$current_user,
    'status' => 1,
  ]);
  \$activity2->save();
  echo \"✅ Created Meeting activity\n\";

  // Create Email activity
  \$activity3 = \Drupal\node\Entity\Node::create([
    'type' => 'activity',
    'title' => 'Email: Gửi báo giá',
    'field_type' => 'Email',
    'field_description' => '[Outcome: Khách hàng sẽ review và phản hồi trong tuần]\n\nĐã gửi báo giá chi tiết cho gói Enterprise. Bao gồm: giá license, chi phí triển khai, support 1 năm.',
    'field_contact' => ['target_id' => \$contact_ids[0]],
    'field_assigned_to' => ['target_id' => \$current_user],
    'uid' => \$current_user,
    'status' => 1,
  ]);
  \$activity3->save();
  echo \"✅ Created Email activity\n\";

  echo \"\n📊 Summary: Created 3 sample activities for Contact NID \" . \$contact_ids[0] . \"\n\";
} else {
  echo \"⚠️  No contacts found to create activities\n\";
}
"

echo ""
echo "✅ Activity Log configuration completed!"
echo ""
echo "Test it by visiting:"
echo "  - Contact page: /node/[contact_id]"
echo "  - Activities tab: /node/[contact_id]/activities"
echo "  - Log call: /crm/activity/log-call/[contact_id]"
echo "  - Schedule meeting: /crm/activity/schedule-meeting/[contact_id]"
