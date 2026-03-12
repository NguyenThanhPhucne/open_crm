<?php

namespace Drupal\crm_quickadd\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\Cache;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * QuickAdd Controller for CRM entities.
 */
class QuickAddController extends ControllerBase {

  /**
   * Contact quick add form.
   */
  public function contactForm() {
    // Get organizations for dropdown
    $org_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('title', 'ASC');
    $org_nids = $org_query->execute();
    $organizations = Node::loadMultiple($org_nids);
    
    $org_options = '<option value="">-- Select company --</option>';
    $org_options .= '<option value="__new__">+ Create new company</option>';
    foreach ($organizations as $org) {
      $org_options .= '<option value="' . $org->id() . '">' . htmlspecialchars($org->getTitle()) . '</option>';
    }

    // Get customer types for dropdown
    $customer_types = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('crm_customer_type');
    $type_options = '<option value="">-- Select customer type --</option>';
    foreach ($customer_types as $type) {
      $type_options .= '<option value="' . $type->tid . '">' . htmlspecialchars($type->name) . '</option>';
    }

    // Get lead sources for dropdown
    $sources = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('crm_source');
    $source_options = '<option value="">-- Select lead source --</option>';
    foreach ($sources as $source) {
      $source_options .= '<option value="' . $source->tid . '">' . htmlspecialchars($source->name) . '</option>';
    }

    return [
      '#theme' => 'quickadd_contact_form',
      '#org_options' => $org_options,
      '#type_options' => $type_options,
      '#source_options' => $source_options,
      '#attached' => ['library' => ['crm_quickadd/quickadd']],
    ];
  }

  /**
   * Submit contact quick add form.
   */
  public function contactSubmit(Request $request) {
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token)) {
      return new JsonResponse(['status' => 'error', 'message' => 'CSRF token validation failed.'], 403);
    }

    try {
      $data = json_decode($request->getContent(), TRUE);
      
      // Validate required fields
      if (empty($data['name']) || empty($data['phone'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Please enter name and phone number.',
        ], 400);
      }

      // Check duplicate phone
      $existing = \Drupal::entityQuery('node')
        ->condition('type', 'contact')
        ->condition('field_phone', $data['phone'])
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();
      
      if (!empty($existing)) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'This phone number already exists in the system.',
        ], 409);
      }

      // Handle inline organization creation
      $org_id = $data['organization'] ?? NULL;
      if ($org_id === '__new__' && !empty($data['organization_name'])) {
        $new_org = Node::create([
          'type' => 'organization',
          'title' => $data['organization_name'],
          'field_status' => 'active',
          'field_assigned_staff' => ['target_id' => \Drupal::currentUser()->id()],
          'uid' => \Drupal::currentUser()->id(),
        ]);
        $new_org->save();
        $org_id = $new_org->id();
      }

      // Create contact
      $contact = Node::create([
        'type' => 'contact',
        'title' => $data['name'],
        'field_email' => $data['email'] ?? '',
        'field_phone' => $data['phone'],
        'field_position' => $data['position'] ?? '',
        'field_organization' => $org_id ? ['target_id' => $org_id] : NULL,
        'field_customer_type' => !empty($data['customer_type']) ? ['target_id' => $data['customer_type']] : NULL,
        'field_source' => !empty($data['source']) ? ['target_id' => $data['source']] : NULL,
        'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $contact->save();

      // Invalidate list caches so views reflect new contact immediately
      Cache::invalidateTags(['node_list']);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Contact created successfully: ' . $data['name'],
        'entity_id' => $contact->id(),
        'redirect' => '/crm/my-contacts',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_quickadd')->error('Contact creation error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'status' => 'error',
        'message' => 'An error occurred. Please try again.',
      ], 500);
    }
  }

  /**
   * Deal quick add form.
   */
  public function dealForm() {
    // Get contacts for dropdown
    $contact_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('status', 1)
      ->accessCheck(TRUE)
      ->sort('title', 'ASC')
      ->range(0, 100);
    $contact_nids = $contact_query->execute();
    $contacts = Node::loadMultiple($contact_nids);
    
    $contact_options = '<option value="">-- Select contact --</option>';
    foreach ($contacts as $contact) {
      $contact_options .= '<option value="' . $contact->id() . '">' . htmlspecialchars($contact->getTitle()) . '</option>';
    }

    // Get stages for dropdown
    $stages = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('pipeline_stage');
    $stage_options = '';
    foreach ($stages as $stage) {
      $selected = $stage->name === 'New' ? ' selected' : '';
      $stage_options .= '<option value="' . $stage->tid . '"' . $selected . '>' . htmlspecialchars($stage->name) . '</option>';
    }

    return [
      '#theme' => 'quickadd_deal_form',
      '#contact_options' => $contact_options,
      '#stage_options' => $stage_options,
      '#attached' => ['library' => ['crm_quickadd/quickadd']],
    ];
  }

  /**
   * Submit deal quick add form.
   */
  public function dealSubmit(Request $request) {
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token)) {
      return new JsonResponse(['status' => 'error', 'message' => 'CSRF token validation failed.'], 403);
    }

    try {
      $data = json_decode($request->getContent(), TRUE);
      
      if (empty($data['title']) || empty($data['amount'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Please enter deal name and value.',
        ], 400);
      }

      // Get organization from contact if provided
      $org_id = NULL;
      if (!empty($data['contact'])) {
        $contact = Node::load($data['contact']);
        if ($contact && $contact->hasField('field_organization') && !$contact->get('field_organization')->isEmpty()) {
          $org_id = $contact->get('field_organization')->target_id;
        }
      }

      // Auto-set probability based on stage
      $probability_map = [
        'New' => 10,
        'Qualified' => 25,
        'Proposal' => 50,
        'Negotiation' => 75,
        'Won' => 100,
        'Lost' => 0,
      ];
      $stage_name = 'New'; // Default
      if (!empty($data['stage'])) {
        $stage_term = Term::load($data['stage']);
        if ($stage_term) {
          $stage_name = $stage_term->getName();
        }
      }
      $probability = $probability_map[$stage_name] ?? 50;

      // Create deal
      $deal = Node::create([
        'type' => 'deal',
        'title' => $data['title'],
        'field_amount' => ['value' => $data['amount']],
        'field_stage' => !empty($data['stage']) ? ['target_id' => $data['stage']] : NULL,
        'field_closing_date' => !empty($data['closing_date']) ? ['value' => $data['closing_date']] : NULL,
        'field_related_contact' => !empty($data['contact']) ? ['target_id' => $data['contact']] : NULL,
        'field_related_organization' => $org_id ? ['target_id' => $org_id] : NULL,
        'field_probability' => ['value' => $probability],
        'field_owner' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $deal->save();

      // Invalidate list caches so pipeline views reflect new deal immediately
      Cache::invalidateTags(['node_list']);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Deal created successfully: ' . $data['title'],
        'entity_id' => $deal->id(),
        'redirect' => '/crm/my-pipeline',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_quickadd')->error('Deal creation error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'status' => 'error',
        'message' => 'An error occurred. Please try again.',
      ], 500);
    }
  }

  /**
   * Organization quick add form.
   */
  public function organizationForm() {
    // Get industries for dropdown
    $industries = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('crm_industry');
    $industry_options = '<option value="">-- Select industry --</option>';
    foreach ($industries as $industry) {
      $industry_options .= '<option value="' . $industry->tid . '">' . htmlspecialchars($industry->name) . '</option>';
    }

    return [
      '#theme' => 'quickadd_organization_form',
      '#industry_options' => $industry_options,
      '#attached' => ['library' => ['crm_quickadd/quickadd']],
    ];
  }

  /**
   * Submit organization quick add form.
   */
  public function organizationSubmit(Request $request) {
    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token');
    if (empty($token) || !\Drupal::service('csrf_token')->validate($token)) {
      return new JsonResponse(['status' => 'error', 'message' => 'CSRF token validation failed.'], 403);
    }

    try {
      $data = json_decode($request->getContent(), TRUE);
      
      if (empty($data['name'])) {
        return new JsonResponse([
          'status' => 'error',
          'message' => 'Please enter the company name.',
        ], 400);
      }

      // Create organization
      $org = Node::create([
        'type' => 'organization',
        'title' => $data['name'],
        'field_website' => !empty($data['website']) ? ['uri' => $data['website']] : NULL,
        'field_address' => $data['address'] ?? '',
        'field_industry' => !empty($data['industry']) ? ['target_id' => $data['industry']] : NULL,
        'field_status' => 'active',
        'field_assigned_staff' => ['target_id' => \Drupal::currentUser()->id()],
        'uid' => \Drupal::currentUser()->id(),
        'status' => 1,
      ]);
      $org->save();

      // Invalidate list caches so organization views reflect new org immediately
      Cache::invalidateTags(['node_list']);

      return new JsonResponse([
        'status' => 'success',
        'message' => 'Organization created successfully: ' . $data['name'],
        'entity_id' => $org->id(),
        'redirect' => '/crm/my-organizations',
      ]);

    } catch (\Exception $e) {
      \Drupal::logger('crm_quickadd')->error('Organization creation error: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse([
        'status' => 'error',
        'message' => 'An error occurred. Please try again.',
      ], 500);
    }
  }

  /**
   * Check for duplicate phone/email.
   */
  public function checkDuplicate(Request $request) {
    try {
      $data = json_decode($request->getContent(), TRUE);
      $field = $data['field'] ?? '';
      $value = $data['value'] ?? '';

      if (empty($field) || empty($value)) {
        return new JsonResponse(['exists' => false]);
      }

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'contact')
        ->condition($field, $value)
        ->accessCheck(FALSE)
        ->range(0, 1);
      
      $results = $query->execute();

      return new JsonResponse([
        'exists' => !empty($results),
        'message' => !empty($results) ? ($field === 'field_phone' ? 'Phone number already exists' : 'Email already exists') : '',
      ]);

    } catch (\Exception $e) {
      return new JsonResponse(['exists' => false]);
    }
  }

}
