<?php

namespace Drupal\crm_ai_autocomplete\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Main service for AI entity field autocomplete functionality.
 */
class AIEntityAutoCompleteService {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Entity schema service.
   *
   * @var \Drupal\crm_ai_autocomplete\Service\EntitySchemaService
   */
  protected $schemaService;

  /**
   * LLM provider service.
   *
   * @var \Drupal\crm_ai_autocomplete\Service\LLMProviderService
   */
  protected $llmService;

  /**
   * Field validator service.
   *
   * @var \Drupal\crm_ai_autocomplete\Service\FieldValidatorService
   */
  protected $validatorService;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    EntitySchemaService $schema_service,
    LLMProviderService $llm_service,
    FieldValidatorService $validator_service,
    MessengerInterface $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->schemaService = $schema_service;
    $this->llmService = $llm_service;
    $this->validatorService = $validator_service;
    $this->messenger = $messenger;
  }

  /**
   * Auto-complete entity fields based on provided input.
   *
   * @param string $entity_type
   *   The entity type (e.g., 'node').
   * @param array $provided_fields
   *   Array of field values provided by user.
   * @param int $nid
   *   Optional node ID for context.
   *
   * @return array
   *   Suggestions for empty fields.
   */
  public function autoCompleteEntity($entity_type, array $provided_fields, $nid = NULL) {
    try {
      // Get configuration.
      $config = $this->configFactory->get('crm_ai_autocomplete.settings');
      $provider = $config->get('ai_provider') ?? 'mock';

      // Log which provider is being used (for debugging)
      $this->loggerFactory->get('crm_ai_autocomplete')->info(
        'Using AI provider: @provider',
        ['@provider' => $provider]
      );

      // Identify empty fields that can be auto-completed.
      $entity_bundle = $provided_fields['type'] ?? 'contact';
      $empty_fields = $this->identifyEmptyFields($entity_bundle, $provided_fields);

      if (empty($empty_fields)) {
        return [
          'success' => TRUE,
          'message' => 'No empty fields to complete',
          'suggestions' => [],
        ];
      }

      // Build context-aware prompt.
      $prompt = $this->buildAIPrompt($entity_bundle, $provided_fields, $empty_fields);

      // Get suggestions from LLM.
      $suggestions = $this->llmService->callLLM($provider, $prompt, [
        'temperature' => $config->get('llm_temperature') ?? 0.7,
        'model' => $config->get('llm_model') ?? 'gpt-3.5-turbo',
      ]);

      if (empty($suggestions)) {
        return [
          'success' => FALSE,
          'message' => 'AI failed to generate suggestions',
          'suggestions' => [],
        ];
      }

      // Validate and format suggestions.
      $validated = $this->validateSuggestions($suggestions, $entity_bundle, $empty_fields);

      $this->loggerFactory->get('crm_ai')->info(
        'AI autocomplete completed for @bundle with @count suggestions',
        ['@bundle' => $entity_bundle, '@count' => count($validated)]
      );

      return [
        'success' => TRUE,
        'suggestions' => $validated,
        'timestamp' => time(),
      ];
    } catch (\Exception $e) {
      $this->loggerFactory->get('crm_ai')->error(
        'AI autocomplete failed: @message',
        ['@message' => $e->getMessage()]
      );

      return [
        'success' => FALSE,
        'message' => 'Error during AI completion: ' . $e->getMessage(),
        'suggestions' => [],
      ];
    }
  }

  /**
   * Identify empty fields that can be auto-completed.
   *
   * @param string $bundle
   *   Entity bundle name.
   * @param array $provided_fields
   *   Current field values.
   *
   * @return array
   *   Empty field names that are auto-completable.
   */
  protected function identifyEmptyFields($bundle, array $provided_fields) {
    $auto_completable = $this->schemaService->getAutoCompletableFields($bundle);
    $empty_fields = [];

    foreach ($auto_completable as $field_name) {
      if (empty($provided_fields[$field_name])) {
        $empty_fields[$field_name] = [
          'label' => $this->schemaService->getFieldLabel($bundle, $field_name),
          'type' => $this->schemaService->getFieldType($bundle, $field_name),
        ];
      }
    }

    return $empty_fields;
  }

  /**
   * Build AI prompt for field completion.
   *
   * @param string $bundle
   *   Entity bundle.
   * @param array $provided_fields
   *   User-provided field values.
   * @param array $empty_fields
   *   Fields to complete.
   *
   * @return string
   *   Formatted prompt for LLM.
   */
  protected function buildAIPrompt($bundle, array $provided_fields, array $empty_fields) {
    $field_descriptions = [];

    foreach ($empty_fields as $field_name => $info) {
      $field_descriptions[] = "- {$info['label']} ({$field_name}): {$info['type']}";
    }

    $context = [];
    foreach ($provided_fields as $key => $value) {
      if (!empty($value) && $key !== 'type') {
        $context[] = "{$key}: {$value}";
      }
    }

    $prompt = "You are a CRM assistant. Based on the following information:\n\n";
    $prompt .= "Context:\n" . implode("\n", $context) . "\n\n";
    $prompt .= "Please fill in the following fields:\n" . implode("\n", $field_descriptions) . "\n\n";
    $prompt .= "Return a JSON object with field names as keys and suggested values as values. Use realistic data.";

    return $prompt;
  }

  /**
   * Validate and format suggestions.
   *
   * @param array $suggestions
   *   Raw suggestions from LLM.
   * @param string $bundle
   *   Entity bundle.
   * @param array $empty_fields
   *   Empty fields metadata.
   *
   * @return array
   *   Validated and formatted suggestions.
   */
  protected function validateSuggestions(array $suggestions, $bundle, array $empty_fields) {
    $validated = [];

    foreach ($suggestions as $field_name => $value) {
      if (!isset($empty_fields[$field_name])) {
        continue;
      }

      $field_info = $empty_fields[$field_name];
      $validated_value = $this->validatorService->validateFieldValue(
        $field_name,
        $value,
        $field_info['type'],
        $bundle
      );

      if ($validated_value !== NULL) {
        $validated[$field_name] = [
          'value' => $validated_value,
          'confidence' => 0.85,
          'field_type' => $field_info['type'],
          'label' => $field_info['label'],
        ];
      }
    }

    return $validated;
  }

  /**
   * Mark fields as AI-generated.
   *
   * @param array $suggestions
   *   Suggested fields.
   *
   * @return array
   *   Suggestions with AI metadata.
   */
  public function markAsAIGenerated(array $suggestions) {
    foreach ($suggestions as &$suggestion) {
      $suggestion['ai_generated'] = TRUE;
      $suggestion['ai_timestamp'] = time();
    }
    return $suggestions;
  }

}
