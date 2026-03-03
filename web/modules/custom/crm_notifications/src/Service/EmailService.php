<?php

namespace Drupal\crm_notifications\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\node\NodeInterface;
use Drupal\user\UserInterface;

/**
 * Email service for CRM notifications.
 */
class EmailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs an EmailService object.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user,
    UrlGeneratorInterface $url_generator
  ) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->urlGenerator = $url_generator;
  }

  /**
   * Send email when a new contact is assigned.
   */
  public function sendNewContactAssigned(NodeInterface $contact, UserInterface $owner) {
    $params = [
      'contact' => $contact,
      'owner' => $owner,
      'contact_name' => $contact->getTitle(),
      'contact_url' => $this->getAbsoluteUrl($contact),
    ];

    $this->sendMail($owner->getEmail(), 'new_contact_assigned', $params);
  }

  /**
   * Send email when a new deal is assigned.
   */
  public function sendNewDealAssigned(NodeInterface $deal, UserInterface $owner) {
    $params = [
      'deal' => $deal,
      'owner' => $owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'amount' => $this->formatAmount($deal),
    ];

    $this->sendMail($owner->getEmail(), 'new_deal_assigned', $params);
  }

  /**
   * Send email when a contact is reassigned.
   */
  public function sendContactReassigned(NodeInterface $contact, UserInterface $new_owner) {
    $params = [
      'contact' => $contact,
      'owner' => $new_owner,
      'contact_name' => $contact->getTitle(),
      'contact_url' => $this->getAbsoluteUrl($contact),
    ];

    $this->sendMail($new_owner->getEmail(), 'contact_reassigned', $params);
  }

  /**
   * Send email when a deal is reassigned.
   */
  public function sendDealReassigned(NodeInterface $deal, UserInterface $new_owner) {
    $params = [
      'deal' => $deal,
      'owner' => $new_owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'amount' => $this->formatAmount($deal),
    ];

    $this->sendMail($new_owner->getEmail(), 'deal_reassigned', $params);
  }

  /**
   * Send email when deal stage changes.
   */
  public function sendDealStageChanged(NodeInterface $deal, UserInterface $owner, string $new_stage) {
    $params = [
      'deal' => $deal,
      'owner' => $owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'new_stage' => $new_stage,
      'amount' => $this->formatAmount($deal),
    ];

    $this->sendMail($owner->getEmail(), 'deal_stage_changed', $params);
  }

  /**
   * Send email when a deal is won.
   */
  public function sendDealWon(NodeInterface $deal, UserInterface $owner) {
    $params = [
      'deal' => $deal,
      'owner' => $owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'amount' => $this->formatAmount($deal),
    ];

    $this->sendMail($owner->getEmail(), 'deal_won', $params);
  }

  /**
   * Send email when a deal is lost.
   */
  public function sendDealLost(NodeInterface $deal, UserInterface $owner) {
    $params = [
      'deal' => $deal,
      'owner' => $owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'amount' => $this->formatAmount($deal),
    ];

    $this->sendMail($owner->getEmail(), 'deal_lost', $params);
  }

  /**
   * Send reminder for deals closing soon.
   */
  public function sendDealClosingSoonReminder(NodeInterface $deal, UserInterface $owner) {
    $closing_date = '';
    if ($deal->hasField('field_closing_date') && !$deal->get('field_closing_date')->isEmpty()) {
      $closing_date = $deal->get('field_closing_date')->value;
    }

    $params = [
      'deal' => $deal,
      'owner' => $owner,
      'deal_name' => $deal->getTitle(),
      'deal_url' => $this->getAbsoluteUrl($deal),
      'amount' => $this->formatAmount($deal),
      'closing_date' => $closing_date,
    ];

    $this->sendMail($owner->getEmail(), 'deal_closing_soon', $params);
  }

  /**
   * Send an email.
   */
  protected function sendMail(string $to, string $key, array $params) {
    $site_config = $this->configFactory->get('system.site');
    $site_name = $site_config->get('name') ?: 'CRM System';
    
    $params['site_name'] = $site_name;
    
    $langcode = $this->configFactory->get('system.site')->get('langcode') ?: 'en';
    
    $result = $this->mailManager->mail(
      'crm_notifications',
      $key,
      $to,
      $langcode,
      $params,
      NULL,
      TRUE
    );

    if ($result['result']) {
      \Drupal::logger('crm_notifications')->notice(
        'Sent @key email to @email',
        ['@key' => $key, '@email' => $to]
      );
    }
    else {
      \Drupal::logger('crm_notifications')->error(
        'Failed to send @key email to @email',
        ['@key' => $key, '@email' => $to]
      );
    }

    return $result['result'];
  }

  /**
   * Get absolute URL for an entity.
   */
  protected function getAbsoluteUrl(NodeInterface $node): string {
    return $node->toUrl('canonical', ['absolute' => TRUE])->toString();
  }

  /**
   * Format amount field with currency.
   */
  protected function formatAmount(NodeInterface $deal): string {
    if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
      $amount = $deal->get('field_amount')->value;
      return number_format($amount, 0, '.', ',') . ' VND';
    }
    return 'N/A';
  }

}
