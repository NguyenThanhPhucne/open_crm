<?php

namespace Drupal\crm_realtime_chat\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure endpoints for CRM realtime chat integration.
 */
class RealtimeChatSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['crm_realtime_chat.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'crm_realtime_chat_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('crm_realtime_chat.settings');

    $form['frontend_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Chat frontend URL'),
      '#default_value' => $config->get('frontend_url') ?: 'http://localhost:5173',
      '#required' => TRUE,
      '#description' => $this->t('Example: http://localhost:5173'),
    ];

    $form['backend_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Chat backend URL'),
      '#default_value' => $config->get('backend_url') ?: 'http://localhost:5001',
      '#required' => TRUE,
      '#description' => $this->t('Used for operations and diagnostics. Example: http://localhost:5001'),
    ];

    $form['socket_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Socket URL'),
      '#default_value' => $config->get('socket_url') ?: 'http://localhost:5001',
      '#required' => TRUE,
      '#description' => $this->t('Socket.IO endpoint that frontend must connect to.'),
    ];

    $form['sso_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SSO shared secret'),
      '#default_value' => $config->get('sso_secret') ?: 'open-crm-chat-sso-dev-secret',
      '#required' => TRUE,
      '#description' => $this->t('Shared HMAC secret used by Drupal and chat backend for auto-login. Use a strong secret in production.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->configFactory->getEditable('crm_realtime_chat.settings')
      ->set('frontend_url', trim((string) $form_state->getValue('frontend_url')))
      ->set('backend_url', trim((string) $form_state->getValue('backend_url')))
      ->set('socket_url', trim((string) $form_state->getValue('socket_url')))
      ->set('sso_secret', trim((string) $form_state->getValue('sso_secret')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
