<?php

namespace Drupal\crm_ai_autocomplete\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\crm_ai_autocomplete\Service\AIEntityAutoCompleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AI autocomplete API endpoints.
 *
 * Handles AJAX requests for AI entity auto-completion:
 * - Auto-complete form fields with AI suggestions
 * - Validates permissions and request data
 * - Returns JSON responses with field suggestions
 */
class AIAutoCompleteController extends ControllerBase {

  /**
   * AI autocomplete service.
   *
   * @var \Drupal\crm_ai_autocomplete\Service\AIEntityAutoCompleteService
   */
  protected $aiService;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructor.
   */
  public function __construct(
    AIEntityAutoCompleteService $ai_service,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->aiService = $ai_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_ai_autocomplete.entity_autocomplete_service'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * POST /api/crm/ai/autocomplete endpoint.
   *
   * Processes AI auto-complete requests for entity fields.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with suggestions or error message.
   */
  public function autoCompleteEndpoint(Request $request) {
    $account = $this->currentUser();

    // Validate permission.
    if (!$account->hasPermission('use crm ai autocomplete')) {
      $this->loggerFactory->get('crm_ai_autocomplete')->warning(
        'User %uid attempted to use AI autocomplete without permission.',
        ['%uid' => $account->id()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You do not have permission to use AI autocomplete.',
        'code' => 403,
      ], 403);
    }

    try {
      // Parse request body.
      $data = json_decode($request->getContent(), TRUE);

      if (!isset($data['entityType']) || !isset($data['fields'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Missing required parameters: entityType, fields',
          'code' => 400,
        ], 400);
      }

      // Get suggestions.
      $result = $this->aiService->autoCompleteEntity(
        $data['entityType'],
        $data['fields'],
        $data['nodeId'] ?? NULL
      );

      // Log successful completion.
      if ($result['success']) {
        $this->loggerFactory->get('crm_ai_autocomplete')->info(
          'User %uid used AI autocomplete for entity type %type.',
          [
            '%uid' => $account->id(),
            '%type' => $data['entityType'],
          ]
        );
      }

      $status_code = $result['success'] ? 200 : 400;
      return new JsonResponse($result, $status_code);
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm_ai_autocomplete')->error(
        'AI autocomplete error: %error',
        ['%error' => $e->getMessage()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'AI autocomplete failed. Please try again.',
        'code' => 500,
      ], 500);
    }
  }

  /**
   * Access check for AI autocomplete endpoint.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function accessAutoComplete(AccountInterface $account) {
    $can_access = $account->hasPermission('use crm ai autocomplete');
    return $can_access ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Auto-create entity with AI-generated data.
   *
   * This endpoint generates a complete entity with AI data and saves it.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with created entity details.
   */
  public function autoCreateEntity(Request $request) {
    $account = $this->currentUser();

    // Validate permission.
    if (!$account->hasPermission('use crm ai autocomplete')) {
      $this->loggerFactory->get('crm_ai_autocomplete')->warning(
        'User %uid attempted to auto-create entity without permission.',
        ['%uid' => $account->id()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You do not have permission to use AI autocomplete.',
        'code' => 403,
      ], 403);
    }

    try {
      // Parse request.
      $data = json_decode($request->getContent(), TRUE);

      if (!isset($data['entityType'])) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Missing required parameter: entityType',
          'code' => 400,
        ], 400);
      }

      $entity_type = $data['entityType'];
      $entity_bundle = $data['bundle'] ?? $entity_type;

      // Generate AI suggestions for new entity (empty fields).
      $result = $this->aiService->autoCompleteEntity(
        $entity_type,
        ['type' => $entity_bundle], // Only bundle type, no other fields
        NULL
      );

      if (!$result['success']) {
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'Failed to generate entity with AI.',
          'code' => 400,
        ], 400);
      }

      // Create the entity.
      $entity = $this->createEntityFromSuggestions(
        $entity_type,
        $entity_bundle,
        $result['suggestions'],
        $account
      );

      // Log the creation.
      $this->loggerFactory->get('crm_ai_autocomplete')->info(
        'User %uid auto-created %type entity (ID: %id) via AI.',
        [
          '%uid' => $account->id(),
          '%type' => $entity_type,
          '%id' => $entity->id(),
        ]
      );

      // Get the provider that was used
      $provider = \Drupal::config('crm_ai_autocomplete.settings')->get('ai_provider') ?? 'mock';

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Entity created successfully with AI!',
        'provider' => $provider,
        'entity_id' => $entity->id(),
        'entity_type' => $entity_type,
        'entity_url' => $entity->toUrl('canonical')->toString(),
        'entity_label' => $entity->label(),
      ], 200);
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm_ai_autocomplete')->error(
        'Auto-create entity error: %error',
        ['%error' => $e->getMessage()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Error creating entity: ' . $e->getMessage(),
        'code' => 500,
      ], 500);
    }
  }

  /**
   * Create entity from AI suggestions with owner assignment.
   *
   * @param string $entity_type
   *   The entity type (or bundle name like "contact").
   * @param string $bundle
   *   The entity bundle.
   * @param array $suggestions
   *   AI-generated suggestions (from autoCompleteEntity).
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account (entity owner).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   *
   * @throws \Exception
   */
  protected function createEntityFromSuggestions(
    $entity_type,
    $bundle,
    array $suggestions,
    AccountInterface $account
  ) {
    // Map bundle names to actual entity types if needed.
    // "contact", "deal", "organization", "activity" are node bundles.
    $bundle_to_entity_type = [
      'contact' => 'node',
      'deal' => 'node',
      'organization' => 'node',
      'activity' => 'node',
    ];

    $actual_entity_type = $bundle_to_entity_type[$entity_type] ?? $entity_type;
    $actual_bundle = $entity_type; // Use passed entity_type as the bundle

    // Create empty entity with correct entity type and bundle.
    $entity_storage = $this->entityTypeManager->getStorage($actual_entity_type);
    $entity = $entity_storage->create(['type' => $actual_bundle]);

    // Apply AI suggestions to entity fields.
    foreach ($suggestions as $field_name => $suggestion_data) {
      $value = $suggestion_data['value'] ?? NULL;

      if ($value !== NULL) {
        try {
          if ($entity->hasField($field_name)) {
            $entity->set($field_name, $value);
          }
        } catch (\Exception $e) {
          // Log field assignment failures but continue.
          $this->loggerFactory->get('crm_ai_autocomplete')->warning(
            'Failed to set field %field on entity: %error',
            ['%field' => $field_name, '%error' => $e->getMessage()]
          );
        }
      }
    }

    // Assign owner - CRITICAL: Always from current session.
    // Always set uid (node authorship) so the node is never owned by Anonymous.
    if ($entity->hasField('uid')) {
      $entity->set('uid', $account->id());
    }
    if ($entity->hasField('field_owner')) {
      $entity->set('field_owner', $account->id());
    }
    if ($entity->hasField('field_assigned_staff')) {
      $entity->set('field_assigned_staff', $account->id());
    }

    // Assign random taxonomy terms for entity reference fields.
    $this->assignRandomTaxonomyFields($entity, $actual_bundle);

    // Assign bundle-specific entity references.
    if ($actual_bundle === 'contact') {
      $this->assignRandomOrganization($entity);
    }
    if ($actual_bundle === 'deal') {
      $this->assignRandomDealReferences($entity);
    }
    if ($actual_bundle === 'activity') {
      $this->assignRandomActivityReferences($entity);
    }

    // Mark as published if applicable.
    if ($entity->hasField('status')) {
      $entity->set('status', 1);
    }

    // Safety net: ensure node title is never null before saving.
    if ($entity instanceof \Drupal\node\NodeInterface && empty(trim((string) $entity->label()))) {
      $entity->set('title', ucfirst($actual_bundle) . ' ' . date('Y-m-d H:i'));
    }

    // Save entity.
    $entity->save();

    return $entity;
  }

  /**
   * Assign random taxonomy terms for entity reference fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param string $bundle
   *   The entity bundle.
   */
  protected function assignRandomTaxonomyFields($entity, $bundle) {
    $vocab_map = [
      'contact' => [
        'field_source'        => 'crm_source',
        'field_customer_type' => 'crm_customer_type',
      ],
    ];

    if (!isset($vocab_map[$bundle])) {
      return;
    }

    foreach ($vocab_map[$bundle] as $field_name => $vid) {
      if (!$entity->hasField($field_name)) {
        continue;
      }
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vid]);
      if (!empty($terms)) {
        $keys = array_keys($terms);
        $random_tid = $keys[array_rand($keys)];
        $entity->set($field_name, $random_tid);
      }
    }
  }

  /**
   * Assign a random contact or deal to an activity node.
   *
   * Activities require at least one: field_contact or field_deal.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The activity entity.
   */
  protected function assignRandomActivityReferences($entity) {
    $storage = $this->entityTypeManager->getStorage('node');

    // Try to assign a random contact.
    if ($entity->hasField('field_contact')) {
      $nids = $storage->getQuery()
        ->condition('type', 'contact')
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->range(0, 50)
        ->execute();
      if (!empty($nids)) {
        $entity->set('field_contact', $nids[array_rand($nids)]);
        return;
      }
    }

    // Fallback: assign a random deal if no contacts exist.
    if ($entity->hasField('field_deal')) {
      $nids = $storage->getQuery()
        ->condition('type', 'deal')
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->range(0, 50)
        ->execute();
      if (!empty($nids)) {
        $entity->set('field_deal', $nids[array_rand($nids)]);
      }
    }
  }

  /**
   * Assign random contact + organization + pipeline stage to a deal node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The deal entity.
   */
  protected function assignRandomDealReferences($entity) {
    $storage = $this->entityTypeManager->getStorage('node');

    // Fallback deal title if AI left it empty or generated a person name.
    $title = $entity->label();
    $is_person_name = (bool) preg_match('/^[A-Z][a-z]+\s[A-Z][a-z]+$/', trim((string) $title));
    if (empty($title) || $is_person_name) {
      $deal_titles = [
        'Enterprise SaaS License Q%d', 'Cloud Migration Package %d', 'Annual Support Contract',
        'ERP Upgrade Phase %d', 'Marketing Automation Setup', 'Data Analytics Platform',
        'Security Suite Enterprise', 'CRM Implementation Project', 'DevOps Automation Bundle',
        'Digital Transformation Package', 'API Integration Services', 'Infrastructure Modernization',
        'Business Intelligence Suite', 'Managed Services Contract', 'Software Development Retainer',
      ];
      $picked = $deal_titles[array_rand($deal_titles)];
      $entity->set('title', sprintf($picked, mt_rand(1, 4)));
    }

    // Assign a random contact.
    if ($entity->hasField('field_contact')) {
      $nids = $storage->getQuery()
        ->condition('type', 'contact')
        ->condition('status', 1)
        ->range(0, 50)
        ->accessCheck(FALSE)
        ->execute();
      if (!empty($nids)) {
        $nids = array_values($nids);
        $entity->set('field_contact', $nids[array_rand($nids)]);
      }
    }

    // Assign a random organization.
    if ($entity->hasField('field_organization')) {
      $nids = $storage->getQuery()
        ->condition('type', 'organization')
        ->condition('status', 1)
        ->range(0, 50)
        ->accessCheck(FALSE)
        ->execute();
      if (!empty($nids)) {
        $nids = array_values($nids);
        $entity->set('field_organization', $nids[array_rand($nids)]);
      }
    }

    // Assign a random pipeline stage (exclude Won/Lost for new deals).
    $stage_tid = NULL;
    if ($entity->hasField('field_stage')) {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'pipeline_stage']);
      // Prefer active stages (New, Qualified, Proposal, Negotiation).
      $active = array_filter($terms, function ($t) {
        return !in_array($t->getName(), ['Won', 'Lost']);
      });
      $pool = !empty($active) ? $active : $terms;
      if (!empty($pool)) {
        $keys = array_keys($pool);
        $stage_tid = $keys[array_rand($keys)];
        $entity->set('field_stage', $stage_tid);
      }
    }

    // Assign deal amount — varied random ranges to reflect deal size.
    if ($entity->hasField('field_amount')) {
      // Pick a random tier: micro / small / medium / large / enterprise.
      $amount_ranges = [
        [1000, 9999],       // micro deals
        [10000, 49999],     // small deals
        [50000, 149999],    // medium deals
        [150000, 499999],   // large deals
        [500000, 1500000],  // enterprise deals
      ];
      // Weight toward small/medium (indices 0-2 get higher chance).
      $weights = [15, 35, 30, 15, 5];
      $rand = mt_rand(1, 100);
      $cumulative = 0;
      $range = $amount_ranges[1];
      foreach ($weights as $i => $w) {
        $cumulative += $w;
        if ($rand <= $cumulative) {
          $range = $amount_ranges[$i];
          break;
        }
      }
      // Round to nearest 500 for realism.
      $raw_amount = mt_rand($range[0], $range[1]);
      $amount = round($raw_amount / 500) * 500;
      $entity->set('field_amount', $amount);
    }

    // Assign probability correlated with pipeline stage.
    if ($entity->hasField('field_probability')) {
      // TID 1=New, 2=Qualified, 3=Proposal, 4=Negotiation (5=Won, 6=Lost excluded).
      $prob_ranges = [
        1 => [5,  25],   // New
        2 => [25, 50],   // Qualified
        3 => [50, 70],   // Proposal
        4 => [70, 90],   // Negotiation
      ];
      $range = isset($stage_tid, $prob_ranges[$stage_tid]) ? $prob_ranges[$stage_tid] : [20, 80];
      $entity->set('field_probability', mt_rand($range[0], $range[1]));
    }

    // Assign closing date correlated with pipeline stage.
    if ($entity->hasField('field_closing_date')) {
      // Further stages have closer close dates.
      $day_ranges = [
        1 => [120, 365],  // New: 4-12 months out
        2 => [60,  240],  // Qualified: 2-8 months out
        3 => [30,  120],  // Proposal: 1-4 months out
        4 => [14,   60],  // Negotiation: 2 weeks to 2 months out
      ];
      $range = isset($stage_tid, $day_ranges[$stage_tid]) ? $day_ranges[$stage_tid] : [30, 180];
      $days_out = mt_rand($range[0], $range[1]);
      // Round to next Monday for realism.
      $close_ts = strtotime("+$days_out days");
      $dow = (int) date('N', $close_ts); // 1=Mon, 7=Sun
      if ($dow > 5) {
        $close_ts = strtotime('+' . (8 - $dow) . ' days', $close_ts);
      }
      $entity->set('field_closing_date', date('Y-m-d', $close_ts));
    }
  }

  /**
   * Assign a random existing organization to a contact node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The contact entity.
   */
  protected function assignRandomOrganization($entity) {
    if (!$entity->hasField('field_organization')) {
      return;
    }
    $nids = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->condition('type', 'organization')
      ->condition('status', 1)
      ->range(0, 50)
      ->accessCheck(FALSE)
      ->execute();
    if (!empty($nids)) {
      $nids = array_values($nids);
      $entity->set('field_organization', $nids[array_rand($nids)]);
    }
  }

  /**
   * Access check for auto-create endpoint.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access result.
   */
  public function accessAutoCreate(AccountInterface $account) {
    $can_access = $account->hasPermission('use crm ai autocomplete') &&
                  $account->hasPermission('create contact content');
    return $can_access ? AccessResult::allowed() : AccessResult::forbidden();
  }

}


