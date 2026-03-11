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
    // Per-field descriptions with strict instructions.
    $field_instructions = [
      // Contact fields
      'title' => [
        'contact'      => 'Full person name — first name + last name only (e.g., "James Carter", "Linda Park"). NEVER use Mr./Ms./Dr./Prof. NEVER use job titles or category words.',
        'deal'         => 'DEAL/PROJECT name only — a short business opportunity title (e.g., "Enterprise SaaS License Q3", "Cloud Migration Package", "Annual Support Contract", "ERP Upgrade Phase 2", "Marketing Automation Setup"). NEVER a person name. NEVER a company name alone.',
        'organization' => 'Company name (e.g., "Apex Solutions", "BrightWave Technologies"). NOT a person name.',
        'activity'     => 'Short action description (e.g., "Follow-up call with client", "Proposal sent to Acme").',
      ],
      'field_email'          => [
        'contact'      => 'Work email address (e.g., "james.carter@techcorp.com"). Must match the contact name.',
        'organization' => 'Company contact email matching the company domain (e.g., "info@apexsolutions.com", "contact@brightwavetech.io"). Match the company name in the domain.',
      ],
      'field_phone'          => [
        'contact'      => 'Personal business phone in format +1 (XXX) XXX-XXXX.',
        'organization' => 'Company main phone number in format +1 (XXX) XXX-XXXX.',
      ],
      'field_website'        => 'Company website URL starting with https:// (e.g., "https://apexsolutions.com", "https://brightwavetech.io"). Must be a plausible URL derived from the company name in "title".',
      'field_position'       => 'Job title at a company (e.g., "VP of Sales", "Head of Engineering", "CFO").',
      'field_source'         => 'Lead source — one of: Website, LinkedIn, Referral, Cold Call, Email Campaign, Trade Show, Partner, Direct.',
      'field_customer_type'  => 'Account category — one of: Enterprise, SMB, Startup, Individual, Non-Profit, Government.',
      'field_amount'         => 'Deal value as a plain number without currency symbol (e.g., "45000").',
      'field_probability'    => 'Win probability as integer 1–99 (e.g., "65").',
      'field_industry'       => 'Industry sector (e.g., "Technology", "Healthcare", "Finance", "Retail", "Manufacturing").',
      'field_employees_count'=> 'Number of employees as integer (e.g., "250").',
      'field_annual_revenue' => 'Annual revenue as plain number without symbol (e.g., "4500000").',
      'field_outcome'        => 'Brief outcome description (e.g., "Deal closed successfully", "Meeting scheduled").',
      'field_type'           => 'Activity type — one of: Call, Email, Meeting, Demo, Follow-up.',
    ];

    $field_descriptions = [];
    foreach ($empty_fields as $field_name => $info) {
      if (isset($field_instructions[$field_name])) {
        $instr = $field_instructions[$field_name];
        // Support per-bundle overrides (e.g., title differs by bundle).
        if (is_array($instr)) {
          $instr = $instr[$bundle] ?? $instr['contact'] ?? json_encode($instr);
        }
        $field_descriptions[] = "\"$field_name\": $instr";
      }
      else {
        $field_descriptions[] = "\"$field_name\": {$info['label']} ({$info['type']})";
      }
    }

    $context = [];
    foreach ($provided_fields as $key => $value) {
      if (!empty($value) && $key !== 'type') {
        $context[] = "  $key: $value";
      }
    }

    $fieldList = implode(",\n  ", $field_descriptions);
    $contextStr = $context ? implode("\n", $context) : '  (none)';

    $prompt = "Generate a realistic CRM $bundle record. Return ONLY a valid JSON object with these fields:\n";
    $prompt .= "{\n  $fieldList\n}\n\n";
    $prompt .= "RULES:\n";
    $prompt .= "- \"title\" for a contact MUST be a realistic Western or Asian person name (First Last). No honorifics (Mr./Ms./Dr.) ever.\n";
    $prompt .= "- \"title\" for a deal MUST be a project/opportunity/product name like \"Cloud ERP Migration Q2\" or \"Annual SaaS License Renewal\". NEVER a person name.\n";
    $prompt .= "- \"field_customer_type\" MUST be one of: Enterprise, SMB, Startup, Individual, Non-Profit, Government.\n";
    $prompt .= "- \"field_source\" MUST be one of: Website, LinkedIn, Referral, Cold Call, Email Campaign, Trade Show, Partner, Direct.\n";
    $prompt .= "- All names must be fully random and different each time. Do NOT reuse examples.\n";
    if ($context) {
      $prompt .= "\nExisting context (use for coherence):\n$contextStr\n";
    }
    $prompt .= "\nRespond with a JSON object only. No explanation, no markdown.";

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
