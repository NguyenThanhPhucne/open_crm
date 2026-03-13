<?php

namespace Drupal\crm\Commands;

use Drupal\crm\Service\DataIntegrityService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drush\Commands\DrushCommand;

/**
 * Drush commands for CRM data integrity checks and repairs.
 */
class DataIntegrityCommands extends DrushCommand {

  /**
   * The data integrity service.
   *
   * @var \Drupal\crm\Service\DataIntegrityService
   */
  protected $integrityService;

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
   * Constructs DataIntegrityCommands.
   *
   * @param \Drupal\crm\Service\DataIntegrityService $integrity_service
   *   The data integrity service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    DataIntegrityService $integrity_service,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct();
    $this->integrityService = $integrity_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Find all orphaned entities in the CRM.
   *
   * @command crm:find-orphans
   * @aliases crm-orphans
   */
  public function findOrphans() {
    $this->logger()->info('Scanning for orphaned entities...');

    $issues = $this->integrityService->findOrphanedEntities();

    if (empty($issues)) {
      $this->logger()->notice('✓ No orphaned entities found.');
      return;
    }

    $this->logger()->error('Found ' . count($issues) . ' orphan issues:');

    foreach ($issues as $category => $issue) {
      $this->logger()->error(
        sprintf(
          "  [%s] %s - Found %d entities",
          strtoupper($issue['severity']),
          $category,
          $issue['count']
        )
      );
      $this->logger()->error('    Description: ' . $issue['description']);
      $this->logger()->error('    Action: ' . $issue['action']);
      if (!empty($issue['entity_ids'])) {
        $this->logger()->error('    Entity IDs: ' . implode(', ', array_slice($issue['entity_ids'], 0, 5)) . 
          (count($issue['entity_ids']) > 5 ? '...' : ''));
      }
    }
  }

  /**
   * Find broken entity references.
   *
   * @command crm:find-broken-refs
   * @aliases crm-broken
   */
  public function findBrokenReferences() {
    $this->logger()->info('Scanning for broken entity references...');

    $broken = $this->integrityService->findBrokenReferences();

    if (empty($broken)) {
      $this->logger()->notice('✓ No broken references found.');
      return;
    }

    $this->logger()->error('Found ' . count($broken) . ' broken references:');

    foreach ($broken as $ref) {
      $this->logger()->error(
        sprintf(
          "  [%s] %s #%d - Field: %s points to missing entity (target_id: %d)",
          strtoupper($ref['severity']),
          $ref['type'],
          $ref['id'],
          $ref['field'],
          $ref['target_id']
        )
      );
      $this->logger()->error('    Action: ' . $ref['action']);
    }
  }

  /**
   * Validate all deal stage values.
   *
   * @command crm:validate-stages
   * @aliases crm-stages
   */
  public function validateStages() {
    $this->logger()->info('Validating deal stage values...');

    $invalid = $this->integrityService->validateStageFormat();

    if (empty($invalid)) {
      $this->logger()->notice('✓ All deals have valid stage values.');
      return;
    }

    $this->logger()->error('Found ' . count($invalid) . ' deals with invalid stages:');

    foreach ($invalid as $entry) {
      $this->logger()->error(
        sprintf(
          "  Deal #%d has invalid stage: '%s' (Valid: %s)",
          $entry['deal_id'],
          $entry['stage_value'],
          $entry['valid_options']
        )
      );
    }
  }

  /**
   * Verify dashboard statistics against actual data.
   *
   * @command crm:verify-stats
   * @aliases crm-stats
   * @option user User ID to verify stats for (optional)
   */
  public function verifyStatistics($options = ['user' => NULL]) {
    $user_id = $options['user'];

    if ($user_id) {
      $this->logger()->info('Verifying statistics for user #' . $user_id);
    } else {
      $this->logger()->info('Verifying overall statistics...');
    }

    $stats = $this->integrityService->verifyDashboardStatistics($user_id);

    $this->logger()->notice('Closed Won Deals: ' . $stats['actual_won']);
    $this->logger()->notice('Closed Lost Deals: ' . $stats['actual_lost']);
    $this->logger()->notice('Total Closed: ' . $stats['total_closed']);

    $this->logger()->notice('✓ Statistics verified successfully.');
  }

  /**
   * Auto-fix: Normalize stage format from numeric to string values.
   *
   * @command crm:normalize-stages
   * @aliases crm-normalize
   */
  public function normalizeStages() {
    $this->logger()->warning('Starting stage format normalization...');

    $deals = $this->entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => 'deal']);

    $term_ids = $this->getPipelineStageIds();
    $stage_mapping = [
      '0' => 'new',
      '1' => 'qualified',
      '2' => 'proposal',
      '3' => 'negotiation',
      '4' => 'negotiation',
      '5' => 'won',
      '6' => 'lost',
      'closed_won' => 'won',
      'closed_lost' => 'lost',
    ];

    $count = 0;
    foreach ($deals as $deal) {
      if ($deal->get('field_stage')->isEmpty()) {
        continue;
      }

      $term = $deal->get('field_stage')->entity;
      if ($term && $term->bundle() === 'pipeline_stage') {
        continue;
      }

      $stage = strtolower(trim((string) $deal->get('field_stage')->getString()));

      if (isset($stage_mapping[$stage]) && isset($term_ids[$stage_mapping[$stage]])) {
        $deal->set('field_stage', ['target_id' => $term_ids[$stage_mapping[$stage]]]);
        $deal->save();
        $count++;
        $this->logger()->notice(sprintf(
          'Updated deal #%d: %s → %s',
          $deal->id(),
          $stage,
          $stage_mapping[$stage]
        ));
      }
    }

    $this->logger()->notice(sprintf('✓ Normalized %d deals', $count));
  }

  /**
   * Get pipeline stage term IDs keyed by normalized label.
   *
   * @return array<string,int>
   *   Example: ['new' => 1, 'qualified' => 2].
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
   * Run complete integrity check (all checks together).
   *
   * @command crm:integrity-check
   * @aliases crm-check
   */
  public function integrityCheck() {
    $this->logger()->notice('Running complete CRM integrity check...');
    $this->logger()->notice('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

    $this->logger()->notice('[1/4] Checking for orphaned entities...');
    $orphans = $this->integrityService->findOrphanedEntities();
    if (empty($orphans)) {
      $this->logger()->notice('  ✓ No orphaned entities');
    } else {
      $this->logger()->error('  ✗ Found ' . count($orphans) . ' orphan issues');
    }

    $this->logger()->notice('[2/4] Checking for broken references...');
    $broken = $this->integrityService->findBrokenReferences();
    if (empty($broken)) {
      $this->logger()->notice('  ✓ No broken references');
    } else {
      $this->logger()->error('  ✗ Found ' . count($broken) . ' broken references');
    }

    $this->logger()->notice('[3/4] Validating stage formats...');
    $invalid = $this->integrityService->validateStageFormat();
    if (empty($invalid)) {
      $this->logger()->notice('  ✓ All stages valid');
    } else {
      $this->logger()->error('  ✗ Found ' . count($invalid) . ' invalid stages');
    }

    $this->logger()->notice('[4/4] Verifying statistics...');
    $stats = $this->integrityService->verifyDashboardStatistics();
    $this->logger()->notice(sprintf('  Closed Won: %d', $stats['actual_won']));
    $this->logger()->notice(sprintf('  Closed Lost: %d', $stats['actual_lost']));

    $this->logger()->notice('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    $total_issues = count($orphans) + count($broken) + count($invalid);
    if ($total_issues === 0) {
      $this->logger()->notice('✓ INTEGRITY CHECK PASSED - All systems nominal');
    } else {
      $this->logger()->error('✗ INTEGRITY CHECK FAILED - ' . $total_issues . ' issues found');
      $this->logger()->error('Run individual commands for details:');
      $this->logger()->error('  drush crm:find-orphans');
      $this->logger()->error('  drush crm:find-broken-refs');
      $this->logger()->error('  drush crm:validate-stages');
    }
  }
}
