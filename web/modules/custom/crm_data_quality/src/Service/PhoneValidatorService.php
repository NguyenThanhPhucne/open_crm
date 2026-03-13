<?php

namespace Drupal\crm_data_quality\Service;

/**
 * Service for validating and normalizing Vietnamese phone numbers.
 */
class PhoneValidatorService {

  /**
   * Validate Vietnamese phone number format.
   *
   * @param string $phone
   *   Phone number to validate.
   *
   * @return array
   *   Array with 'valid' boolean and 'message' string.
   */
  public function validate($phone) {
    if (empty($phone)) {
      return ['valid' => FALSE, 'message' => 'Phone number is required.'];
    }

    $phone = trim($phone);

    // Remove common separators
    $clean = preg_replace('/[\s\-().]/', '', $phone);

    // Vietnamese mobile: 03x, 04x, 05x, 07x, 08x, 09x (10 digits starting with 0)
    // Vietnamese landline: 02x (10 digits)
    // International: +84 prefix (11-12 digits total)
    
    if (preg_match('/^\+84[1-9]\d{8,10}$/', $clean)) {
      // International format: +84xxxxxxxxx (11-13 digits)
      return ['valid' => TRUE, 'message' => '', 'normalized' => $clean];
    }
    
    if (preg_match('/^0[1-9]\d{8,9}$/', $clean)) {
      // Domestic format: 0xxxxxxxxx (10-11 digits)
      return ['valid' => TRUE, 'message' => '', 'normalized' => $clean];
    }

    return [
      'valid' => FALSE,
      'message' => 'Invalid Vietnamese phone format. Use 0987654321 or +84987654321.',
    ];
  }

  /**
   * Normalize phone number to standard format.
   *
   * @param string $phone
   *   Raw phone number.
   *
   * @return string|null
   *   Normalized phone (0xxx format) or NULL if invalid.
   */
  public function normalize($phone) {
    $result = $this->validate($phone);
    
    if (!$result['valid']) {
      return NULL;
    }

    $normalized = $result['normalized'] ?? '';

    // Convert +84 to 0
    if (strpos($normalized, '+84') === 0) {
      $normalized = '0' . substr($normalized, 3);
    }

    return $normalized;
  }
}
