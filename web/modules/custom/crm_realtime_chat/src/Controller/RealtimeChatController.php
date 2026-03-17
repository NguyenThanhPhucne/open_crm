<?php

namespace Drupal\crm_realtime_chat\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller for rendering realtime chat in CRM.
 */
class RealtimeChatController extends ControllerBase {

  /**
   * Chat page.
   */
  public function page(): array {
    $config = $this->config('crm_realtime_chat.settings');
    $frontend_url = (string) $config->get('frontend_url');
    $sso_secret = (string) $config->get('sso_secret');
    $origin = \Drupal::request()->getSchemeAndHttpHost();

    if ($frontend_url === '') {
      $frontend_url = $origin;
    }

    if ($sso_secret === '') {
      $sso_secret = 'open-crm-chat-sso-dev-secret';
    }

    $current_user = $this->currentUser();
    $uid = (int) $current_user->id();
    $username = (string) $current_user->getAccountName();
    $display_name = (string) $current_user->getDisplayName();
    $email = '';

    $user_entity = \Drupal\user\Entity\User::load($uid);
    if ($user_entity) {
      $email = (string) $user_entity->getEmail();
    }

    $timestamp = (string) time();
    $payload = implode('|', [$uid, $username, $email, $display_name, $timestamp]);
    $signature = hash_hmac('sha256', $payload, $sso_secret);

    $query = http_build_query([
      'crm_sso' => '1',
      'uid' => (string) $uid,
      'username' => $username,
      'email' => $email,
      'displayName' => $display_name,
      'ts' => $timestamp,
      'sig' => $signature,
    ]);

    $glue = strpos($frontend_url, '?') === FALSE ? '?' : '&';
    $frontend_url_with_sso = $frontend_url . $glue . $query;

    return [
      '#theme' => 'crm_realtime_chat_page',
      '#chat_frontend_url' => $frontend_url_with_sso,
      '#chat_user' => [
        'uid' => $uid,
        'name' => $display_name,
        'mail' => $email,
      ],
      '#chat_settings_url' => Url::fromRoute('crm_realtime_chat.settings')->toString(),
      '#attached' => [
        'library' => [
          'crm_realtime_chat/chat_embed',
        ],
        'drupalSettings' => [
          'crmRealtimeChat' => [
            'frontendUrl' => $frontend_url_with_sso,
          ],
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

}
