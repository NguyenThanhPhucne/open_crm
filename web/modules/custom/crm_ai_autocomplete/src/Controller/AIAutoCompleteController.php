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
    if ($entity->hasField('field_owner')) {
      $entity->set('field_owner', $account->id());
    } elseif ($entity->hasField('uid')) {
      $entity->set('uid', $account->id());
    }

    // Mark as published if applicable.
    if ($entity->hasField('status')) {
      $entity->set('status', 1);
    }

    // Save entity.
    $entity->save();

    return $entity;
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


