<?php

namespace Drupal\crm\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service for detecting and fixing data integrity issues in CRM.
 */
class DataIntegrityService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a DataIntegrityService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Find all orphaned entities (entities without required owner/assignment).
   *
   * @return array
   *   Array of orphaned entity issues.
   */
  public function findOrphanedEntities() {
    $issues = [];

    // DEALS WITHOUT OWNERS
    $deal_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'deal')
      ->condition('field_owner', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($deal_ids)) {
      $issues['orphaned_deals_without_owner'] = [
        'count' => count($deal_ids),
        'entity_ids' => array_values($deal_ids),
        'severity' => 'critical',
        'description' => 'Deals without assigned owner - dashboard filters exclude these',
        'action' => 'Assign to creator or sales team member',
      ];
    }

    // ACTIVITIES WITHOUT ASSIGNMENTS
    $activity_ids = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'activity')
      ->condition('field_assigned_to', NULL, 'IS NULL')
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($activity_ids)) {
      $issues['orphaned_activities_unassigned'] = [
        'count' => count($activity_ids),
        'entity_ids' => array_values($activity_ids),
        'severity' => 'critical',
        'description' => 'Activities without assigned user - no one responsible',
        'action' => 'Assign to user or delete if no longer needed',
      ];
    }

    // DEALS WITHOUT CONTACT OR ORGANIZATION
    $all_deals = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'deal']);

    $unlinked_deals = [];
    foreach ($all_deals as $deal) {
      $has_contact = !$deal->get('field_contact')->isEmpty();
      $has_org = !$deal->get('field_organization')->isEmpty();

      if (!$has_contact && !$has_org) {
        $unlinked_deals[] = $deal->id();
      }
    }

    if (!empty($unlinked_deals)) {
      $issues['deals_without_contact_or_org'] = [
        'count' => count($unlinked_deals),
        'entity_ids' => $unlinked_deals,
        'severity' => 'major',
        'description' => 'Deals not linked to contact or organization',
        'action' => 'Link to contact or delete if duplicate',
      ];
    }

    return $issues;
  }

  /**
   * Find broken entity references.
   *
   * @return array
   *   Array of broken references.
   */
  public function findBrokenReferences() {
    $broken = [];

    // DEALS REFERENCING DELETED CONTACTS
    $deals = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'deal']);

    foreach ($deals as $deal) {
      if (!$deal->get('field_contact')->isEmpty()) {
        $contact = $deal->get('field_contact')->entity;
        if (!$contact) {
          $broken[] = [
            'type' => 'deal',
            'id' => $deal->id(),
            'title' => $deal->getTitle(),
            'field' => 'field_contact',
            'target_id' => $deal->get('field_contact')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete deal',
          ];
        }
      }

      if (!$deal->get('field_organization')->isEmpty()) {
        $org = $deal->get('field_organization')->entity;
        if (!$org) {
          $broken[] = [
            'type' => 'deal',
            'id' => $deal->id(),
            'title' => $deal->getTitle(),
            'field' => 'field_organization',
            'target_id' => $deal->get('field_organization')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete deal',
          ];
        }
      }
    }

    // ACTIVITIES REFERENCING DELETED DEALS
    $activities = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'activity']);

    foreach ($activities as $activity) {
      if (!$activity->get('field_deal')->isEmpty()) {
        $deal = $activity->get('field_deal')->entity;
        if (!$deal) {
          $broken[] = [
            'type' => 'activity',
            'id' => $activity->id(),
            'title' => $activity->getTitle(),
            'field' => 'field_deal',
            'target_id' => $activity->get('field_deal')->target_id,
            'severity' => 'major',
            'action' => 'Remove reference or delete activity',
          ];
        }
      }
    }

    return $broken;
  }

  /**
   * Validate stage values are in correct format.
   *
   * @return array
   *   Array of invalid stage entries.
   */
  public function validateStageFormat() {
    $valid_stage_ids = array_values($this->getPipelineStageIds());

    $deals_query = \Drupal::database()->select('node__field_stage', 'fs')
      ->fields('fs', ['entity_id', 'field_stage_target_id'])
      ->condition('fs.bundle', 'deal')
      ->condition('fs.deleted', 0)
      ->execute();

    $invalid = [];
    foreach ($deals_query as $row) {
      $stage_target_id = (int) $row->field_stage_target_id;
      if (!in_array($stage_target_id, $valid_stage_ids, TRUE)) {
        $invalid[] = [
          'deal_id' => $row->entity_id,
          'stage_value' => $stage_target_id,
          'valid_options' => implode(', ', array_keys($this->getPipelineStageIds())),
        ];
      }
    }

    return $invalid;
  }

  /**
   * Verify dashboard statistics match actual entity counts.
   *
   * @param int|null $user_id
   *   Optional user ID to filter by owner.
   *
   * @return array
   *   Array with actual deal counts.
   */
  public function verifyDashboardStatistics($user_id = NULL) {
    $stage_ids = $this->getPipelineStageIds();
    $won_stage_id = $stage_ids['won'] ?? NULL;
    $lost_stage_id = $stage_ids['lost'] ?? NULL;

    $buildQuery = function () use ($user_id) {
      $query = $this->entityTypeManager->getStorage('node')->getQuery()
        ->condition('type', 'deal')
        ->accessCheck(FALSE);

      if ($user_id) {
        $query->condition('field_owner', $user_id);
      }

      return $query;
    };

    $won_count = 0;
    if ($won_stage_id) {
      $won_count = $buildQuery()
        ->condition('field_stage', $won_stage_id)
        ->count()
        ->execute();
    }

    $lost_count = 0;
    if ($lost_stage_id) {
      $lost_count = $buildQuery()
        ->condition('field_stage', $lost_stage_id)
        ->count()
        ->execute();
    }

    return [
      'actual_won' => $won_count,
      'actual_lost' => $lost_count,
      'total_closed' => $won_count + $lost_count,
    ];
  }

  /**
   * Get pipeline stage term IDs keyed by normalized label.
   *
   * @return array<string,int>
   *   Example: ['new' => 1, 'qualified' => 2, 'proposal' => 3].
   */
  protected function getPipelineStageIds() {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'pipeline_stage']);

    $stage_ids = [];
    foreach ($terms as $term) {
      $stage_ids[strtolower($term->label())] = (int) $term->id();
    }

    return $stage_ids;
  }

  /**
   * Log integrity issue.
   *
   * @param string $category
   *   Issue category.
   * @param string $entity_type
   *   Entity type.
   * @param int $entity_id
   *   Entity ID.
   * @param string $issue_description
   *   Issue description.
   */
  public function logIssue($category, $entity_type, $entity_id, $issue_description) {
    $this->loggerFactory->get('crm_integrity')->warning(
      'Integrity issue: [@category] @type #@id - @description',
      [
        '@category' => $category,
        '@type' => $entity_type,
        '@id' => $entity_id,
        '@description' => $issue_description,
      ]
    );
  }
}
