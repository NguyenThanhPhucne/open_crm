<?php

namespace Drupal\crm_ai_autocomplete\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for CRM AI AutoComplete settings.
 */
class AIConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['crm_ai.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_ai_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('crm_ai.settings');

    $form['llm_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('LLM Provider Settings'),
      '#description' => $this->t('Configure the AI provider for generating suggestions.'),
    ];

    $form['llm_section']['llm_provider'] = [
      '#type' => 'select',
      '#title' => $this->t('LLM Provider'),
      '#options' => [
        'mock' => $this->t('Mock (for testing)'),
        'openai' => $this->t('OpenAI'),
        'anthropic' => $this->t('Anthropic'),
      ],
      '#default_value' => $config->get('llm_provider') ?? 'mock',
      '#description' => $this->t('Select the AI provider to use for generating suggestions.'),
    ];

    $form['llm_section']['openai_api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('OpenAI API Key'),
      '#default_value' => $config->get('openai_api_key') ?? '',
      '#description' => $this->t('Your OpenAI API key. Get it from https://platform.openai.com/api-keys'),
      '#attributes' => ['placeholder' => 'sk-...'],
    ];

    $form['llm_section']['openai_model'] = [
      '#type' => 'select',
      '#title' => $this->t('OpenAI Model'),
      '#options' => [
        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
        'gpt-4' => 'GPT-4',
        'gpt-4-turbo' => 'GPT-4 Turbo',
      ],
      '#default_value' => $config->get('openai_model') ?? 'gpt-3.5-turbo',
    ];

    $form['llm_section']['anthropic_api_key'] = [
      '#type' => 'password',
      '#title' => $this->t('Anthropic API Key'),
      '#default_value' => $config->get('anthropic_api_key') ?? '',
      '#description' => $this->t('Your Anthropic API key. Get it from https://console.anthropic.com'),
      '#attributes' => ['placeholder' => 'sk-ant-...'],
    ];

    $form['llm_section']['anthropic_model'] = [
      '#type' => 'select',
      '#title' => $this->t('Anthropic Model'),
      '#options' => [
        'claude-3-haiku-20240307' => 'Claude 3 Haiku',
        'claude-3-sonnet-20240229' => 'Claude 3 Sonnet',
        'claude-3-opus-20240229' => 'Claude 3 Opus',
      ],
      '#default_value' => $config->get('anthropic_model') ?? 'claude-3-haiku-20240307',
    ];

    $form['llm_section']['llm_temperature'] = [
      '#type' => 'number',
      '#title' => $this->t('Temperature'),
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.1,
      '#default_value' => $config->get('llm_temperature') ?? 0.7,
      '#description' => $this->t('Controls randomness (0=deterministic, 1=random).'),
    ];

    $form['llm_section']['llm_model'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Model Name'),
      '#default_value' => $config->get('llm_model') ?? 'gpt-3.5-turbo',
      '#description' => $this->t('Default model to use.'),
    ];

    $form['performance_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Performance & Security'),
    ];

    $form['performance_section']['cache_suggestions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache suggestions'),
      '#default_value' => $config->get('cache_suggestions') ?? TRUE,
      '#description' => $this->t('Enable caching of AI suggestions (1-hour TTL).'),
    ];

    $form['performance_section']['rate_limit_per_hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Rate limit (requests per hour)'),
      '#min' => 1,
      '#max' => 100,
      '#default_value' => $config->get('rate_limit_per_hour') ?? 10,
      '#description' => $this->t('Maximum AI requests allowed per user per hour.'),
    ];

    $form['entities_section'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled Entities'),
      '#description' => $this->t('Select which entity types should have AI autocomplete.'),
    ];

    $entities = [
      'contact' => $this->t('Contact'),
      'deal' => $this->t('Deal'),
      'organization' => $this->t('Organization'),
      'activity' => $this->t('Activity'),
    ];

    $enabled = $config->get('enabled_entities') ?? ['contact', 'deal', 'organization'];

    foreach ($entities as $entity_id => $entity_label) {
      $form['entities_section']["enabled_entities_{$entity_id}"] = [
        '#type' => 'checkbox',
        '#title' => $entity_label,
        '#default_value' => in_array($entity_id, $enabled),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('crm_ai.settings');

    $config->set('llm_provider', $form_state->getValue('llm_provider'))
      ->set('openai_api_key', $form_state->getValue('openai_api_key'))
      ->set('openai_model', $form_state->getValue('openai_model'))
      ->set('anthropic_api_key', $form_state->getValue('anthropic_api_key'))
      ->set('anthropic_model', $form_state->getValue('anthropic_model'))
      ->set('llm_temperature', $form_state->getValue('llm_temperature'))
      ->set('llm_model', $form_state->getValue('llm_model'))
      ->set('cache_suggestions', $form_state->getValue('cache_suggestions'))
      ->set('rate_limit_per_hour', $form_state->getValue('rate_limit_per_hour'))
      ->save();

    // Collect enabled entities.
    $enabled = [];
    foreach (['contact', 'deal', 'organization', 'activity'] as $entity_id) {
      if ($form_state->getValue("enabled_entities_{$entity_id}")) {
        $enabled[] = $entity_id;
      }
    }
    $config->set('enabled_entities', $enabled)->save();

    parent::submitForm($form, $form_state);
    $this->messenger()->addStatus($this->t('Configuration saved successfully.'));
  }

}
