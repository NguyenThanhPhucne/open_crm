<?php

namespace Drupal\crm_workflow\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Service for managing deal probability calculations based on pipeline stage.
 */
class ProbabilityService {

  /**
   * Probability mapping for each pipeline stage.
   */
  private const PROBABILITY_MAP = [
    'new' => 10,
    'qualified' => 25,
    'proposal' => 50,
    'negotiation' => 75,
    'won' => 100,
    'lost' => 0,
  ];

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get probability value for a given stage name.
   *
   * @param string $stage_name
   *   The pipeline stage name (case-insensitive).
   *
   * @return int
   *   The probability value (0-100).
   */
  public function getProbabilityByStage(string $stage_name): int {
    $normalized = strtolower(trim($stage_name));
    return self::PROBABILITY_MAP[$normalized] ?? 50;
  }

  /**
   * Get probability by stage term ID.
   *
   * @param int $stage_tid
   *   The taxonomy term ID of the pipeline stage.
   *
   * @return int
   *   The probability value (0-100).
   */
  public function getProbabilityByStageId(int $stage_tid): int {
    try {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($stage_tid);
      if ($term && $term->bundle() === 'pipeline_stage') {
        return $this->getProbabilityByStage($term->getName());
      }
    } catch (\Exception $e) {
      // Fall through to default
    }
    return 50; // Default
  }

  /**
   * Auto-update deal probability when stage changes.
   *
   * @param \Drupal\node\NodeInterface $deal
   *   The deal node being updated.
   * @param int $new_stage_id
   *   The target stage taxonomy term ID.
   *
   * @return bool
   *   TRUE if probability was updated, FALSE otherwise.
   */
  public function updateProbabilityByStage(NodeInterface $deal, int $new_stage_id): bool {
    if (!$deal->hasField('field_stage') || !$deal->hasField('field_probability')) {
      return FALSE;
    }

    $probability = $this->getProbabilityByStageId($new_stage_id);
    $deal->set('field_probability', $probability);

    return TRUE;
  }

  /**
   * Calculate probability-weighted deal value.
   *
   * @param \Drupal\node\NodeInterface $deal
   *   The deal node.
   *
   * @return float
   *   The weighted deal value (amount × probability%).
   */
  public function getWeightedValue(NodeInterface $deal): float {
    if (!$deal->hasField('field_amount') || !$deal->hasField('field_probability')) {
      return 0.0;
    }

    $amount = (float) ($deal->get('field_amount')->value ?? 0);
    $probability = (int) ($deal->get('field_probability')->value ?? 50);

    return $amount * ($probability / 100);
  }

  /**
   * Get all probability mappings.
   *
   * @return array
   *   Array of stage names to probability values.
   */
  public function getProbabilityMappings(): array {
    return self::PROBABILITY_MAP;
  }

  /**
   * Validate probability value.
   *
   * @param int $probability
   *   The probability value to validate.
   *
   * @return bool
   *   TRUE if valid (0-100), FALSE otherwise.
   */
  public function isValidProbability(int $probability): bool {
    return $probability >= 0 && $probability <= 100;
  }

  /**
   * Get display label for probability range.
   *
   * @param int $probability
   *   The probability value.
   *
   * @return string
   *   The display label.
   */
  public function getProbabilityLabel(int $probability): string {
    if ($probability >= 80) {
      return 'Very High';
    }
    if ($probability >= 60) {
      return 'High';
    }
    if ($probability >= 40) {
      return 'Medium';
    }
    if ($probability >= 20) {
      return 'Low';
    }
    return 'Very Low';
  }
}
