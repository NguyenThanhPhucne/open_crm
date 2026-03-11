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
            ['role' => 'system', 'content' => 'You are a CRM data generator. Rules: (1) Respond with a single valid JSON object only — no markdown, no code blocks, no explanation. (2) Contact "title" must be a realistic full person name like "James Carter" — NEVER use Mr./Ms./Dr./Eng. prefixes, NEVER use category words like "Potential Client" or "New Lead" as names. (3) Use varied, realistic names from diverse backgrounds each time. (4) All values must be plausible for a real business CRM.'],
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
    // Detect bundle from the prompt so we generate the right kind of data.
    preg_match('/Generate a realistic CRM (\w+) record/', $prompt, $m);
    $bundle = $m[1] ?? 'contact';

    // ── Shared pool data ─────────────────────────────────────────────────────
    $first_names = ['James', 'Linda', 'Michael', 'Sarah', 'David', 'Emily', 'Robert', 'Jessica', 'William', 'Jennifer', 'Richard', 'Patricia', 'Charles', 'Barbara', 'Daniel', 'Helen'];
    $last_names  = ['Carter', 'Park', 'Chen', 'Johnson', 'Williams', 'Brown', 'Davis', 'Garcia', 'Martinez', 'Wilson', 'Anderson', 'Thomas', 'Lee', 'Taylor', 'Harris', 'Walker'];
    $phone = '+1 (' . mt_rand(200, 999) . ') ' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999);

    // ── ORGANIZATION ─────────────────────────────────────────────────────────
    if ($bundle === 'organization') {
      $org_names  = ['Apex Solutions', 'BrightWave Technologies', 'NovaCrest Inc.', 'Meridian Systems', 'BlueHorizon Corp.', 'ClearPath Consulting', 'IronBridge Group', 'SummitEdge Digital', 'PinnacleSoft', 'TerraLogic Ltd.', 'VantagePoint Analytics', 'CobaltStream LLC', 'OmniForge Technologies', 'SilverOak Ventures', 'RedMast Enterprises'];
      $industries  = ['Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing', 'Education', 'Real Estate', 'Logistics', 'Consulting', 'Telecommunications'];
      $org_name    = $org_names[array_rand($org_names)];
      $industry    = $industries[array_rand($industries)];
      // Build a URL-safe slug from the org name.
      $slug        = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', str_replace(' ', '', $org_name)));
      $tld         = ['com', 'io', 'co', 'net'][array_rand(['com', 'io', 'co', 'net'])];
      $domain      = $slug . '.' . $tld;
      $emp_count   = mt_rand(5, 10) * mt_rand(10, 200);   // 50 – 2000
      $revenue     = mt_rand(1, 50) * 100000;              // 100 000 – 5 000 000
      return [
        'title'                => $org_name,
        'field_industry'       => $industry,
        'field_website'        => 'https://www.' . $domain,
        'field_email'          => 'info@' . $domain,
        'field_phone'          => $phone,
        'field_employees_count'=> (string) $emp_count,
        'field_annual_revenue' => (string) $revenue,
      ];
    }

    // ── ACTIVITY ─────────────────────────────────────────────────────────────
    if ($bundle === 'activity') {
      $titles   = ['Follow-up call with client', 'Product demo scheduled', 'Proposal sent to prospect', 'Quarterly review meeting', 'Contract negotiation call', 'Technical requirements discussion', 'Onboarding kickoff meeting', 'Support issue escalation call', 'Partnership exploration meeting', 'Renewal discussion with account'];
      $outcomes = ['Successfully discussed next steps', 'Client requested a follow-up', 'Sent proposal for review', 'Meeting rescheduled for next week', 'Agreement reached on terms', 'Demo went well — trial requested', 'Identified key decision maker', 'Issue resolved; client satisfied'];
      return [
        'title'        => $titles[array_rand($titles)],
        'field_outcome'=> $outcomes[array_rand($outcomes)],
      ];
    }

    // ── CONTACT (default) ────────────────────────────────────────────────────
    $companies     = ['Tech Solutions Inc.', 'Digital Innovations Ltd.', 'Cloud Systems Corp.', 'Data Analytics Pro', 'Software House LLC', 'Innovation Labs'];
    $sources       = ['Website', 'LinkedIn', 'Referral', 'Cold Call', 'Email Campaign', 'Trade Show', 'Partner', 'Direct'];
    $customer_types= ['Enterprise', 'SMB', 'Startup', 'Individual', 'Non-Profit', 'Government'];
    $positions     = ['VP of Sales', 'Head of Engineering', 'CFO', 'Marketing Manager', 'Operations Director', 'CEO', 'Product Manager', 'CTO', 'Account Executive', 'Business Development Manager'];
    $first_name    = $first_names[array_rand($first_names)];
    $last_name     = $last_names[array_rand($last_names)];
    $company       = $companies[array_rand($companies)];
    $email_domain  = strtolower(preg_replace('/[^a-z0-9]+/', '', strtolower($company))) . '.com';
    return [
      'title'               => $first_name . ' ' . $last_name,
      'field_email'         => strtolower($first_name . '.' . $last_name) . '@' . $email_domain,
      'field_phone'         => $phone,
      'field_position'      => $positions[array_rand($positions)],
      'field_source'        => $sources[array_rand($sources)],
      'field_customer_type' => $customer_types[array_rand($customer_types)],
    ];
  }

}
