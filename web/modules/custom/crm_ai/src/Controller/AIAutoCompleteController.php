<?php

namespace Drupal\crm_ai\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\crm_ai\Service\AIEntityAutoCompleteService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for AI autocomplete API endpoints.
 */
class AIAutoCompleteController extends ControllerBase {

  /**
   * AI autocomplete service.
   *
   * @var \Drupal\crm_ai\Service\AIEntityAutoCompleteService
   */
  protected $aiService;

  /**
   * Constructor.
   */
  public function __construct(
    AIEntityAutoCompleteService $ai_service
  ) {
    $this->aiService = $ai_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_ai.entity_autocomplete_service')
    );
  }

  /**
   * POST /api/crm/ai/autocomplete endpoint.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with suggestions.
   */
  public function autoCompleteEndpoint(Request $request) {
    // Validate permission.
    if (!$this->currentUser()->hasPermission('use crm ai autocomplete')) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You do not have permission to use AI autocomplete.',
      ], 403);
    }

    // Parse request body.
    $data = json_decode($request->getContent(), TRUE);

    if (!isset($data['entityType']) || !isset($data['fields'])) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Missing required parameters: entityType, fields',
      ], 400);
    }

    // Get suggestions.
    $result = $this->aiService->autoCompleteEntity(
      $data['entityType'],
      $data['fields'],
      $data['nodeId'] ?? NULL
    );

    $status_code = $result['success'] ? 200 : 400;
    return new JsonResponse($result, $status_code);
  }

  /**
   * Access callback for autocomplete endpoint.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current user account.
   *
   * @return bool
   *   TRUE if user can access.
   */
  public function accessAutoComplete(AccountProxyInterface $account) {
    return $account->hasPermission('use crm ai autocomplete');
  }

}

