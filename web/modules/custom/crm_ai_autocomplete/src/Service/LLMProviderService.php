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
   *   Provider name (openai, anthropic, mock).
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
   *   Mock suggestions.
   */
  protected function callMock($prompt, array $options) {
    // Return realistic mock data for testing.
    return [
      'field_company' => 'Tech Solutions Inc.',
      'field_email' => 'contact@example.com',
      'field_phone' => '+1 (555) 123-4567',
      'field_source' => 'Website',
      'field_customer_type' => 'Enterprise',
      'title' => 'John Smith',
      'field_value' => '50000',
      'field_probability' => '75',
      'field_industry' => 'Technology',
      'body' => 'Meeting discussed project requirements and timeline',
    ];
  }

}
