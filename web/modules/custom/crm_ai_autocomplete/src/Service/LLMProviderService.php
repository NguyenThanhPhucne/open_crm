<?php

namespace Drupal\crm_ai_autocomplete\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Service for communicating with LLM providers.
 */
class LLMProviderService {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Call LLM provider.
   *
   * @param string $provider
   *   Provider name (groq, openai, anthropic, mock).
   * @param string $prompt
   *   Prompt for LLM.
   * @param array $options
   *   Additional options.
   *
   * @return array
   *   Parsed response from LLM.
   */
  public function callLLM($provider, $prompt, array $options = []) {
    switch ($provider) {
      case 'groq':
        return $this->callGroq($prompt, $options);

      case 'openai':
        return $this->callOpenAI($prompt, $options);

      case 'anthropic':
        return $this->callAnthropic($prompt, $options);

      case 'mock':
      default:
        return $this->callMock($prompt, $options);
    }
  }

  /**
   * Call Groq API.
   *
   * @param string $prompt
   *   Prompt text.
   * @param array $options
   *   Groq options.
   *
   * @return array
   *   Parsed response.
   */
  protected function callGroq($prompt, array $options) {
    try {
      $config = $this->configFactory->get('crm_ai_autocomplete.settings');
      $api_key = $config->get('groq_api_key');
      $model = $config->get('groq_model') ?? 'llama-3.1-8b-instant';

      if (!$api_key) {
        $this->loggerFactory->get('crm_ai_autocomplete')->warning('Groq API key not configured, falling back to mock');
        return $this->callMock($prompt, $options);
      }

      $response = $this->httpClient->post('https://api.groq.com/openai/v1/chat/completions', [
        'headers' => [
          'Authorization' => "Bearer {$api_key}",
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => 'You are a CRM assistant. Generate realistic and varied CRM data. You MUST respond with a valid JSON object only — no markdown, no code blocks, no explanation. Just the raw JSON object.'],
            ['role' => 'user', 'content' => $prompt],
          ],
          'temperature' => $options['temperature'] ?? 0.7,
          'max_tokens' => 600,
          'response_format' => ['type' => 'json_object'],
        ],
        'timeout' => 30,
      ]);

      $body = $response->getBody()->getContents();
      $data = json_decode($body, TRUE);

      if (!$data) {
        // If response is not JSON, log error and fallback to mock
        $this->loggerFactory->get('crm_ai_autocomplete')->error('Groq API returned invalid JSON: @body', ['@body' => substr($body, 0, 100)]);
        return $this->callMock($prompt, $options);
      }

      if (isset($data['choices'][0]['message']['content'])) {
        $content = $data['choices'][0]['message']['content'];

        // 1. Try direct json_decode first.
        $result = json_decode($content, TRUE);
        if (is_array($result) && !empty($result)) {
          $this->loggerFactory->get('crm_ai_autocomplete')->info('Groq API call successful');
          return $result;
        }

        // 2. Strip markdown code blocks (```json ... ``` or ``` ... ```).
        $stripped = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/', '$1', $content);
        $result = json_decode(trim($stripped), TRUE);
        if (is_array($result) && !empty($result)) {
          $this->loggerFactory->get('crm_ai_autocomplete')->info('Groq API call successful');
          return $result;
        }

        // 3. Extract first JSON object using balanced brace matching.
        $start = strpos($stripped, '{');
        if ($start !== FALSE) {
          $depth = 0;
          $end = $start;
          for ($i = $start; $i < strlen($stripped); $i++) {
            if ($stripped[$i] === '{') {
              $depth++;
            }
            elseif ($stripped[$i] === '}') {
              $depth--;
              if ($depth === 0) {
                $end = $i;
                break;
              }
            }
          }
          $json_str = substr($stripped, $start, $end - $start + 1);
          $result = json_decode($json_str, TRUE);
          if (is_array($result) && !empty($result)) {
            $this->loggerFactory->get('crm_ai_autocomplete')->info('Groq API call successful');
            return $result;
          }
        }
      }

      // If we can't extract valid data, fallback to mock
      $this->loggerFactory->get('crm_ai_autocomplete')->warning('Could not parse Groq response, falling back to mock');
      return $this->callMock($prompt, $options);
    } catch (GuzzleException $e) {
      $this->loggerFactory->get('crm_ai_autocomplete')->error('Groq API error: @message. Falling back to mock.', ['@message' => $e->getMessage()]);
      return $this->callMock($prompt, $options);
    }
  }

  /**
   * Call OpenAI API.
   *
   * @param string $prompt
   *   Prompt text.
   * @param array $options
   *   OpenAI options.
   *
   * @return array
   *   Parsed response.
   */
  protected function callOpenAI($prompt, array $options) {
    try {
      $config = $this->configFactory->get('crm_ai.settings');
      $api_key = $config->get('openai_api_key');
      $model = $options['model'] ?? 'gpt-3.5-turbo';

      if (!$api_key) {
        return [];
      }

      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => "Bearer {$api_key}",
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => 'You are a CRM assistant. Respond with valid JSON only.'],
            ['role' => 'user', 'content' => $prompt],
          ],
          'temperature' => $options['temperature'] ?? 0.7,
        ],
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (isset($data['choices'][0]['message']['content'])) {
        $content = $data['choices'][0]['message']['content'];
        // Try to extract JSON from response.
        $json_match = [];
        if (preg_match('/\{.*\}/s', $content, $json_match)) {
          $result = json_decode($json_match[0], TRUE);
          return is_array($result) ? $result : [];
        }
      }

      return [];
    } catch (GuzzleException $e) {
      $this->loggerFactory->get('crm_ai')->error('OpenAI API error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Call Anthropic API.
   *
   * @param string $prompt
   *   Prompt text.
   * @param array $options
   *   Anthropic options.
   *
   * @return array
   *   Parsed response.
   */
  protected function callAnthropic($prompt, array $options) {
    try {
      $config = $this->configFactory->get('crm_ai.settings');
      $api_key = $config->get('anthropic_api_key');
      $model = $options['model'] ?? 'claude-3-haiku-20240307';

      if (!$api_key) {
        return [];
      }

      $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
        'headers' => [
          'x-api-key' => $api_key,
          'anthropic-version' => '2023-06-01',
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'max_tokens' => 1024,
          'messages' => [
            ['role' => 'user', 'content' => $prompt],
          ],
        ],
        'timeout' => 30,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);

      if (isset($data['content'][0]['text'])) {
        $content = $data['content'][0]['text'];
        $json_match = [];
        if (preg_match('/\{.*\}/s', $content, $json_match)) {
          $result = json_decode($json_match[0], TRUE);
          return is_array($result) ? $result : [];
        }
      }

      return [];
    } catch (GuzzleException $e) {
      $this->loggerFactory->get('crm_ai')->error('Anthropic API error: @message', ['@message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Call Mock LLM (for testing).
   *
   * @param string $prompt
   *   Prompt text.
   * @param array $options
   *   Options (unused).
   *
   * @return array
   *   Mock suggestions with random data.
   */
  protected function callMock($prompt, array $options) {
    // Generate random data for each call
    $first_names = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Jessica', 'James', 'Lisa', 'William', 'Jennifer', 'Richard', 'Patricia', 'Charles', 'Barbara'];
    $last_names = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Miller', 'Davis', 'Rodriguez', 'Garcia', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas'];
    $companies = ['Tech Solutions Inc.', 'Digital Innovations Ltd.', 'Cloud Systems Corp.', 'Data Analytics Pro', 'Software House LLC', 'Innovation Labs', 'Digital Ventures', 'Tech Startup Inc.', 'Web Services Co.', 'Mobile Solutions'];
    $industries = ['Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing', 'Education', 'Real Estate', 'Telecommunications', 'Entertainment', 'Transportation'];
    $sources = ['Website', 'LinkedIn', 'Referral', 'Cold Call', 'Email Campaign', 'Trade Show', 'Partner', 'Direct Purchase'];
    $customer_types = ['Enterprise', 'SMB', 'Startup', 'Individual', 'Non-Profit', 'Government'];
    
    $first_name = $first_names[array_rand($first_names)];
    $last_name = $last_names[array_rand($last_names)];
    $company = $companies[array_rand($companies)];
    $industry = $industries[array_rand($industries)];
    $source = $sources[array_rand($sources)];
    $customer_type = $customer_types[array_rand($customer_types)];
    
    $value = rand(10000, 500000);
    $probability = rand(20, 95);
    $phone = '+1 (' . rand(200, 999) . ') ' . rand(100, 999) . '-' . rand(1000, 9999);
    $email = strtolower(str_replace(' ', '.', $first_name . '.' . $last_name)) . '@' . strtolower(str_replace(' ', '', $company)) . '.com';
    
    // Return realistic mock data with random values
    return [
      'field_company' => $company,
      'field_email' => $email,
      'field_phone' => $phone,
      'field_source' => $source,
      'field_customer_type' => $customer_type,
      'title' => $first_name . ' ' . $last_name,
      'field_value' => (string)$value,
      'field_probability' => (string)$probability,
      'field_industry' => $industry,
      'body' => 'Generated contact via AI autocomplete on ' . date('Y-m-d H:i:s'),
    ];
  }

}
