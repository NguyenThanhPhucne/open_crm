<?php

namespace Drupal\crm_import_export\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Data validation and integrity service for CRM.
 *
 * Provides comprehensive validation for CRM data to ensure
 * data quality and integrity in production environments.
 */
class DataValidationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a DataValidationService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    AccountProxyInterface $current_user
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->currentUser = $current_user;
  }

  /**
   * Validate email format.
   *
   * @param string $email
   *   Email address to validate.
   *
   * @return array
   *   Validation result with 'valid' boolean and 'message' string.
   */
  public function validateEmail($email) {
    if (empty($email)) {
      return ['valid' => FALSE, 'message' => 'Email is required.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return ['valid' => FALSE, 'message' => 'Invalid email format.'];
    }

    // Check for disposable email domains (production security).
    $disposable_domains = ['tempmail.com', 'throwaway.email', '10minutemail.com'];
    $domain = substr(strrchr($email, "@"), 1);
    if (in_array($domain, $disposable_domains)) {
      return ['valid' => FALSE, 'message' => 'Disposable email addresses are not allowed.'];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Validate phone number (Vietnamese format).
   *
   * @param string $phone
   *   Phone number to validate.
   *
   * @return array
   *   Validation result.
   */
  public function validatePhone($phone) {
    if (empty($phone)) {
      return ['valid' => FALSE, 'message' => 'Phone number is required.'];
    }

    // Remove spaces and special characters.
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);

    // Vietnamese phone format: 10 digits starting with 0, or +84.
    if (!preg_match('/^(0|\+84)[0-9]{9}$/', $cleaned)) {
      return ['valid' => FALSE, 'message' => 'Invalid phone number format (e.g. 0912345678).'];
    }

    return ['valid' => TRUE, 'message' => '', 'cleaned' => $cleaned];
  }

  /**
   * Check for duplicate email in contacts.
   *
   * @param string $email
   *   Email to check.
   * @param int|null $exclude_nid
   *   Node ID to exclude from check (for updates).
   *
   * @return array
   *   Result with 'exists' boolean and 'nid' if found.
   */
  public function checkDuplicateEmail($email, $exclude_nid = NULL) {
    if (empty($email)) {
      return ['exists' => FALSE];
    }

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'contact')
      ->condition('field_email', $email)
      ->accessCheck(FALSE)
      ->range(0, 1);

    if ($exclude_nid) {
      $query->condition('nid', $exclude_nid, '<>');
    }

    $nids = $query->execute();

    if (!empty($nids)) {
      return ['exists' => TRUE, 'nid' => reset($nids)];
    }

    return ['exists' => FALSE];
  }

  /**
   * Check for duplicate phone in contacts.
   *
   * @param string $phone
   *   Phone to check.
   * @param int|null $exclude_nid
   *   Node ID to exclude from check.
   *
   * @return array
   *   Result with 'exists' boolean and 'nid' if found.
   */
  public function checkDuplicatePhone($phone, $exclude_nid = NULL) {
    if (empty($phone)) {
      return ['exists' => FALSE];
    }

    // Clean phone number for comparison.
    $cleaned = preg_replace('/[^0-9+]/', '', $phone);

    $query = $this->entityTypeManager->getStorage('node')->getQuery()
      ->condition('type', 'contact')
      ->condition('field_phone', $cleaned)
      ->accessCheck(FALSE)
      ->range(0, 1);

    if ($exclude_nid) {
      $query->condition('nid', $exclude_nid, '<>');
    }

    $nids = $query->execute();

    if (!empty($nids)) {
      return ['exists' => TRUE, 'nid' => reset($nids)];
    }

    return ['exists' => FALSE];
  }

  /**
   * Validate deal amount.
   *
   * @param mixed $amount
   *   Amount to validate.
   *
   * @return array
   *   Validation result.
   */
  public function validateAmount($amount) {
    if ($amount === '' || $amount === NULL) {
      return ['valid' => FALSE, 'message' => 'Deal value is required.'];
    }

    // Check for negative sign first (before removing it in cleaning).
    if (is_string($amount) && strpos($amount, '-') !== FALSE) {
      return ['valid' => FALSE, 'message' => 'Deal value cannot be negative.'];
    }

    // Remove currency symbols and commas.
    $cleaned = preg_replace('/[^0-9.]/', '', $amount);

    if (!is_numeric($cleaned)) {
      return ['valid' => FALSE, 'message' => 'Deal value must be a number.'];
    }

    $value = floatval($cleaned);

    if ($value < 0) {
      return ['valid' => FALSE, 'message' => 'Deal value cannot be negative.'];
    }

    if ($value > 999999999999) {
      return ['valid' => FALSE, 'message' => 'Deal value is too large.'];
    }

    return ['valid' => TRUE, 'message' => '', 'cleaned' => $value];
  }

  /**
   * Validate required field.
   *
   * @param string $value
   *   Value to validate.
   * @param string $field_name
   *   Field name for error message.
   *
   * @return array
   *   Validation result.
   */
  public function validateRequired($value, $field_name) {
    if (empty($value) && $value !== '0') {
      return [
        'valid' => FALSE,
        'message' => sprintf('%s is required.', $field_name),
      ];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Validate date format.
   *
   * @param string $date
   *   Date string to validate.
   * @param string $format
   *   Expected format (default: Y-m-d).
   *
   * @return array
   *   Validation result.
   */
  public function validateDate($date, $format = 'Y-m-d') {
    if (empty($date)) {
      return ['valid' => FALSE, 'message' => 'Date is required.'];
    }

    $d = \DateTime::createFromFormat($format, $date);
    if (!$d || $d->format($format) !== $date) {
      return [
        'valid' => FALSE,
        'message' => sprintf('Invalid date format (%s).', $format),
      ];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Validate taxonomy term exists.
   *
   * @param int $tid
   *   Term ID.
   * @param string $vocabulary
   *   Vocabulary ID.
   *
   * @return array
   *   Validation result.
   */
  public function validateTaxonomyTerm($tid, $vocabulary) {
    if (empty($tid)) {
      return ['valid' => FALSE, 'message' => 'Please select a value.'];
    }

    $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);

    if (!$term || $term->bundle() !== $vocabulary) {
      return ['valid' => FALSE, 'message' => 'Invalid value.'];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Validate node reference exists.
   *
   * @param int $nid
   *   Node ID.
   * @param string $type
   *   Node type.
   *
   * @return array
   *   Validation result.
   */
  public function validateNodeReference($nid, $type) {
    if (empty($nid)) {
      return ['valid' => FALSE, 'message' => 'Please select.'];
    }

    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (!$node || $node->bundle() !== $type) {
      return ['valid' => FALSE, 'message' => 'Invalid reference.'];
    }

    return ['valid' => TRUE, 'message' => ''];
  }

  /**
   * Sanitize text input (prevent XSS).
   *
   * @param string $text
   *   Text to sanitize.
   *
   * @return string
   *   Sanitized text.
   */
  public function sanitizeText($text) {
    return htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');
  }

  /**
   * Log validation error.
   *
   * @param string $context
   *   Context (form name, import process, etc.).
   * @param string $message
   *   Error message.
   * @param array $data
   *   Additional data.
   */
  public function logValidationError($context, $message, array $data = []) {
    $this->loggerFactory->get('crm_validation')->error(
      '@context: @message | Data: @data',
      [
        '@context' => $context,
        '@message' => $message,
        '@data' => json_encode($data),
      ]
    );
  }

  /**
   * Comprehensive contact validation.
   *
   * @param array $data
   *   Contact data to validate.
   * @param int|null $exclude_nid
   *   Node ID to exclude (for updates).
   *
   * @return array
   *   Validation result with 'valid' boolean and 'errors' array.
   */
  public function validateContact(array $data, $exclude_nid = NULL) {
    $errors = [];

    // Required: Name.
    $name_validation = $this->validateRequired($data['name'] ?? '', 'Contact Name');
    if (!$name_validation['valid']) {
      $errors['name'] = $name_validation['message'];
    }

    // Required: Phone.
    $phone_validation = $this->validatePhone($data['phone'] ?? '');
    if (!$phone_validation['valid']) {
      $errors['phone'] = $phone_validation['message'];
    }
    else {
      // Check duplicate phone.
      $duplicate = $this->checkDuplicatePhone($phone_validation['cleaned'], $exclude_nid);
      if ($duplicate['exists']) {
        $errors['phone'] = sprintf(
          'Phone number already exists (Contact ID: %d).',
          $duplicate['nid']
        );
      }
    }

    // Optional: Email (but validate format if provided).
    if (!empty($data['email'])) {
      $email_validation = $this->validateEmail($data['email']);
      if (!$email_validation['valid']) {
        $errors['email'] = $email_validation['message'];
      }
      else {
        // Check duplicate email.
        $duplicate = $this->checkDuplicateEmail($data['email'], $exclude_nid);
        if ($duplicate['exists']) {
          $errors['email'] = sprintf(
            'Email already exists (Contact ID: %d).',
            $duplicate['nid']
          );
        }
      }
    }

    // Optional: Organization reference.
    if (!empty($data['organization']) && $data['organization'] !== '__new__') {
      $org_validation = $this->validateNodeReference($data['organization'], 'organization');
      if (!$org_validation['valid']) {
        $errors['organization'] = $org_validation['message'];
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
    ];
  }

  /**
   * Comprehensive deal validation.
   *
   * @param array $data
   *   Deal data to validate.
   *
   * @return array
   *   Validation result with 'valid' boolean and 'errors' array.
   */
  public function validateDeal(array $data) {
    $errors = [];

    // Required: Title.
    $title_validation = $this->validateRequired($data['title'] ?? '', 'Deal Name');
    if (!$title_validation['valid']) {
      $errors['title'] = $title_validation['message'];
    }

    // Required: Amount.
    $amount_validation = $this->validateAmount($data['amount'] ?? '');
    if (!$amount_validation['valid']) {
      $errors['amount'] = $amount_validation['message'];
    }

    // Optional: Stage (validate if provided).
    if (!empty($data['stage'])) {
      $stage_validation = $this->validateTaxonomyTerm($data['stage'], 'pipeline_stage');
      if (!$stage_validation['valid']) {
        $errors['stage'] = $stage_validation['message'];
      }
    }

    // Optional: Contact reference.
    if (!empty($data['contact'])) {
      $contact_validation = $this->validateNodeReference($data['contact'], 'contact');
      if (!$contact_validation['valid']) {
        $errors['contact'] = $contact_validation['message'];
      }
    }

    // Optional: Expected close date.
    if (!empty($data['expected_close_date'])) {
      $date_validation = $this->validateDate($data['expected_close_date']);
      if (!$date_validation['valid']) {
        $errors['expected_close_date'] = $date_validation['message'];
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
    ];
  }

  /**
   * Comprehensive activity validation.
   *
   * @param array $data
   *   Activity data to validate.
   *
   * @return array
   *   Validation result with 'valid' boolean and 'errors' array.
   */
  public function validateActivity(array $data) {
    $errors = [];

    // Required: Title.
    $title_validation = $this->validateRequired($data['title'] ?? '', 'Activity Title');
    if (!$title_validation['valid']) {
      $errors['title'] = $title_validation['message'];
    }

    // Required: Activity type.
    $type_validation = $this->validateRequired($data['type'] ?? '', 'Activity Type');
    if (!$type_validation['valid']) {
      $errors['type'] = $type_validation['message'];
    }

    // Required: Assigned to user.
    if (empty($data['assigned_to'])) {
      $errors['assigned_to'] = 'Assigned user is required.';
    }

    // CRITICAL: Must have Contact OR Deal.
    $has_contact = !empty($data['contact']);
    $has_deal = !empty($data['deal']);

    if (!$has_contact && !$has_deal) {
      $errors['contact_deal'] = 'Activity must be linked to a Contact or Deal.';
    }

    // Validate contact reference if provided.
    if ($has_contact) {
      $contact_validation = $this->validateNodeReference($data['contact'], 'contact');
      if (!$contact_validation['valid']) {
        $errors['contact'] = $contact_validation['message'];
      }
    }

    // Validate deal reference if provided.
    if ($has_deal) {
      $deal_validation = $this->validateNodeReference($data['deal'], 'deal');
      if (!$deal_validation['valid']) {
        $errors['deal'] = $deal_validation['message'];
      }
    }

    // Optional: Due date (validate format if provided).
    if (!empty($data['due_date'])) {
      $date_validation = $this->validateDate($data['due_date']);
      if (!$date_validation['valid']) {
        $errors['due_date'] = $date_validation['message'];
      }
    }

    // Optional: Priority.
    if (!empty($data['priority'])) {
      $valid_priorities = ['low', 'normal', 'high', 'urgent'];
      if (!in_array(strtolower($data['priority']), $valid_priorities)) {
        $errors['priority'] = sprintf(
          'Invalid priority. Choose: %s.',
          implode(', ', $valid_priorities)
        );
      }
    }

    return [
      'valid' => empty($errors),
      'errors' => $errors,
    ];
  }

}
