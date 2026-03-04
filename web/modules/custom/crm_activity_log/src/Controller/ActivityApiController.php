<?php

namespace Drupal\crm_activity_log\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * REST API Controller for Activity Log helpers.
 */
class ActivityApiController extends ControllerBase {

  /**
   * Get deals by contact ID.
   */
  public function getDealsByContact($contact) {
    try {
      // Query deals related to this contact
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('status', 1)
        ->condition('field_contact', $contact)
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->accessCheck(TRUE);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        return new JsonResponse(['deals' => []]);
      }
      
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      
      $deals = [];
      foreach ($nodes as $node) {
        $value = '';
        if ($node->hasField('field_value') && !$node->get('field_value')->isEmpty()) {
          $value = number_format($node->get('field_value')->value, 0, ',', '.') . ' đ';
        }
        
        $stage = '';
        if ($node->hasField('field_stage') && !$node->get('field_stage')->isEmpty()) {
          $stage_term = $node->get('field_stage')->entity;
          if ($stage_term) {
            $stage = $stage_term->getName();
          }
        }
        
        $deals[] = [
          'nid' => $node->id(),
          'title' => $node->getTitle(),
          'value' => $value,
          'stage' => $stage,
        ];
      }
      
      return new JsonResponse(['deals' => $deals]);
      
    } catch (\Exception $e) {
      \Drupal::logger('crm_activity_log')->error('Error loading deals: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['deals' => []], 200);
    }
  }

  /**
   * Get activities by contact ID.
   */
  public function getActivitiesByContact($contact) {
    try {
      // Query activities for this contact
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'activity')
        ->condition('status', 1)
        ->condition('field_contact', $contact)
        ->sort('created', 'DESC')
        ->range(0, 20)
        ->accessCheck(TRUE);
      
      $nids = $query->execute();
      
      if (empty($nids)) {
        return new JsonResponse(['activities' => []]);
      }
      
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
      
      $activities = [];
      foreach ($nodes as $node) {
        $type = '';
        if ($node->hasField('field_type') && !$node->get('field_type')->isEmpty()) {
          $type = $node->get('field_type')->value;
        }
        
        $description = '';
        if ($node->hasField('field_description') && !$node->get('field_description')->isEmpty()) {
          $description = $node->get('field_description')->value;
        }
        
        $activities[] = [
          'nid' => $node->id(),
          'title' => $node->getTitle(),
          'type' => $type,
          'description' => substr($description, 0, 200),
          'created' => $node->getCreatedTime(),
          'created_formatted' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'custom', 'd/m/Y H:i'),
        ];
      }
      
      return new JsonResponse(['activities' => $activities]);
      
    } catch (\Exception $e) {
      \Drupal::logger('crm_activity_log')->error('Error loading activities: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['activities' => []], 200);
    }
  }
}
