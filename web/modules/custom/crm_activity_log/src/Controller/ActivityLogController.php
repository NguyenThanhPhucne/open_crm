<?php

namespace Drupal\crm_activity_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Activity Log Controller.
 */
class ActivityLogController extends ControllerBase {

  /**
   * Activity tab on Contact/Deal/Organization page.
   */
  public function activityTab(NodeInterface $node) {
    $allowed_types = ['contact', 'deal', 'organization'];
    if (!in_array($node->bundle(), $allowed_types)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    // Determine which field to query based on content type
    $field_map = [
      'contact' => 'field_contact',
      'deal' => 'field_deal',
      'organization' => 'field_organization',
    ];
    $reference_field = $field_map[$node->bundle()];

    // Get all activities related to this node
    $activity_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition($reference_field, $node->id())
      ->accessCheck(TRUE)
      ->sort('created', 'DESC')
      ->range(0, 50);
    
    $activity_nids = $activity_query->execute();
    $activities = Node::loadMultiple($activity_nids);

    // Format activities for template
    $activity_items = [];
    foreach ($activities as $activity) {
      $type = '';
      if ($activity->hasField('field_type') && !$activity->get('field_type')->isEmpty()) {
        $type = $activity->get('field_type')->value;
      }

      $description = '';
      if ($activity->hasField('field_description') && !$activity->get('field_description')->isEmpty()) {
        $description = $activity->get('field_description')->value;
      }

      // Extract outcome from description if present
      $outcome = '';
      if (preg_match('/\[Outcome: (.+?)\]/', $description, $matches)) {
        $outcome = $matches[1];
        $description = trim(str_replace($matches[0], '', $description));
      }

      $activity_items[] = [
        'id' => $activity->id(),
        'title' => $activity->getTitle(),
        'type' => $type,
        'description' => $description,
        'outcome' => $outcome,
        'created' => date('d/m/Y H:i', $activity->getCreatedTime()),
        'author' => $activity->getOwner()->getDisplayName(),
      ];
    }

    // Generic variable names to support contact/deal/organization
    return [
      '#theme' => 'activity_log_tab',
      '#contact_id' => $node->id(),  // Note: Variable name kept for backward compatibility
      '#contact_name' => $node->getTitle(),
      '#entity_type' => $node->bundle(),
      '#activities' => $activity_items,
      '#attached' => ['library' => ['crm_activity_log/activity_widget']],
    ];
  }

  /**
   * Log call form.
   */
  public function logCallForm($contact) {
    $contact_node= Node::load($contact);
    if (!$contact_node || $contact_node->bundle() !== 'contact') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    return [
      '#theme' => 'log_call_form',
      '#contact_id' => $contact,
      '#contact_name' => $contact_node->getTitle(),
      '#attached' => ['library' => ['crm_activity_log/activity_widget']],
    ];
  }

  /**
   * Log call submit.
   */
  public function logCallSubmit($contact, Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);

      $contact_node = Node::load($contact);
      if (!$contact_node || $contact_node->bundle() !== 'contact') {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Contact not found.',
        ], 404);
      }

      if (empty($data['outcome'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Vui lòng chọn kết quả cuộc gọi.',
        ], 400);
      }

      // Get related deal if exists
      $deal_id = NULL;
      if (!empty($data['deal'])) {
        $deal_id = $data['deal'];
      }

      // Create activity
      $notes = $data['notes'] ?? '';
      $outcome_text = '[Outcome: ' . $data['outcome'] . ']';
      $full_description = $notes ? $outcome_text . "\n\n" . $notes : $outcome_text;
      
      $activity = Node::create([
        'type' => 'activity',
        'title' => 'Call: ' . $contact_node->getTitle(),
        'field_type' => 'Call',
        'field_description' => $full_description,
        'field_contact' => ['target_id' => $contact],
        'field_deal' => $deal_id ? ['target_id' => $deal_id] : NULL,
        'field_assigned_to' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $activity->save();

      // Update contact's last contacted date
      if ($contact_node->hasField('field_last_contacted')) {
        $contact_node->set('field_last_contacted', date('Y-m-d\TH:i:s'));
        $contact_node->save();
      }

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Đã ghi nhận cuộc gọi thành công.',
        'activity_id' => $activity->id(),
        'redirect' => '/node/' . $contact . '/activities',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_activity_log')->error('Log call error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại.',
      ], 500);
    }
  }

  /**
   * Schedule meeting form.
   */
  public function scheduleMeetingForm($contact) {
    $contact_node = Node::load($contact);
    if (!$contact_node || $contact_node->bundle() !== 'contact') {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    return [
      '#theme' => 'schedule_meeting_form',
      '#contact_id' => $contact,
      '#contact_name' => $contact_node->getTitle(),
      '#attached' => ['library' => ['crm_activity_log/activity_widget']],
    ];
  }

  /**
   * Schedule meeting submit.
   */
  public function scheduleMeetingSubmit($contact, Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);

      $contact_node = Node::load($contact);
      if (!$contact_node || $contact_node->bundle() !== 'contact') {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Contact not found.',
        ], 404);
      }

      if (empty($data['meeting_date']) || empty($data['title'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Vui lòng nhập đầy đủ Tiêu đề và Thời gian.',
        ], 400);
      }

      // Get related deal if exists
      $deal_id = NULL;
      if (!empty($data['deal'])) {
        $deal_id = $data['deal'];
      }

      // Create activity
      $activity = Node::create([
        'type' => 'activity',
        'title' => $data['title'],
        'field_type' => 'Meeting',
        'field_description' => $data['agenda'] ?? '',
        'field_datetime' => $data['meeting_date'],
        'field_contact' => ['target_id' => $contact],
        'field_deal' => $deal_id ? ['target_id' => $deal_id] : NULL,
        'field_assigned_to' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $activity->save();

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Đã đặt lịch hẹn thành công.',
        'activity_id' => $activity->id(),
        'redirect' => '/node/' . $contact . '/activities',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_activity_log')->error('Schedule meeting error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'status' => 'error',
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại.',
      ], 500);
    }
  }

}
