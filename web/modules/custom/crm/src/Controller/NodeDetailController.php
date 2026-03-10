<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Renders professional detail pages for CRM node content types.
 *
 * Handles contact, deal, organization, and activity nodes.
 */
class NodeDetailController extends ControllerBase {

  /**
   * CRM content types handled by this controller.
   */
  const CRM_TYPES = ['contact', 'deal', 'organization', 'activity'];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Access check for node detail pages.
   */
  public function access(NodeInterface $node, AccountInterface $account) {
    return $node->access('view', $account, TRUE);
  }

  /**
   * Returns title for the page.
   */
  public function getTitle(NodeInterface $node) {
    return $node->getTitle();
  }

  /**
   * Main dispatcher — routes to specific build method by content type.
   */
  public function view(NodeInterface $node) {
    $type = $node->bundle();

    if (!in_array($type, self::CRM_TYPES)) {
      // Fall back to default node view for non-CRM types.
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      return $view_builder->view($node, 'full');
    }

    switch ($type) {
      case 'contact':
        return $this->buildContact($node);

      case 'deal':
        return $this->buildDeal($node);

      case 'organization':
        return $this->buildOrganization($node);

      case 'activity':
        return $this->buildActivity($node);
    }
  }

  // ─────────────────────────────────────────────
  // CONTACT
  // ─────────────────────────────────────────────

  /**
   * Build render array for a contact node.
   */
  protected function buildContact(NodeInterface $node) {
    $nid = $node->id();
    $title = $node->getTitle();
    $initial = strtoupper(mb_substr($title, 0, 1));

    $email    = $this->fieldValue($node, 'field_email');
    $phone    = $this->fieldValue($node, 'field_phone');
    $position = $this->fieldValue($node, 'field_position');
    $source   = $this->fieldValue($node, 'field_source');
    $linkedin = $this->fieldValue($node, 'field_linkedin');
    $notes    = $this->fieldValue($node, 'body');
    $created  = $node->getCreatedTime();
    $changed  = $node->getChangedTime();

    // Organization ref.
    $org_name = '';
    $org_url  = '';
    if ($node->hasField('field_organization') && !$node->get('field_organization')->isEmpty()) {
      $org = $node->get('field_organization')->entity;
      if ($org) {
        $org_name = $org->getTitle();
        $org_url  = $org->toUrl()->toString();
      }
    }

    // Owner (sales rep).
    $owner_name = '';
    $owner_url  = '';
    if ($node->hasField('field_owner') && !$node->get('field_owner')->isEmpty()) {
      $owner = $node->get('field_owner')->entity;
      if ($owner) {
        $owner_name = $owner->getDisplayName();
        $owner_url  = '/user/' . $owner->id();
      }
    }

    // Related deals.
    $deals = $this->getRelatedDeals($nid);

    // Recent activities.
    $activities = $this->getRelatedActivities($nid, 'field_contact_ref', 5);

    // Customer type & tags.
    $customer_type = $this->fieldValue($node, 'field_customer_type');
    $tags = $this->getTaxonomyTermNames($node, 'field_tags');

    // Last contacted date.
    $last_contacted = NULL;
    if ($node->hasField('field_last_contacted') && !$node->get('field_last_contacted')->isEmpty()) {
      $last_contacted = $node->get('field_last_contacted')->value;
    }

    $current_user = $this->currentUser();
    $can_edit = $node->access('update', $current_user);

    return [
      '#theme' => 'crm_node_contact',
      '#node' => $node,
      '#nid' => $nid,
      '#title' => $title,
      '#initial' => $initial,
      '#email' => $email,
      '#phone' => $phone,
      '#position' => $position,
      '#source' => $source,
      '#linkedin' => $linkedin,
      '#notes' => $notes,
      '#created' => $created,
      '#changed' => $changed,
      '#org_name' => $org_name,
      '#org_url' => $org_url,
      '#owner_name' => $owner_name,
      '#owner_url' => $owner_url,
      '#deals' => $deals,
      '#activities' => $activities,
      '#customer_type' => $customer_type,
      '#tags' => $tags,
      '#last_contacted' => $last_contacted,
      '#can_edit' => $can_edit,
      '#cache' => ['max-age' => 0, 'contexts' => ['user'], 'tags' => ['node:' . $nid]],
      '#attached' => ['library' => ['crm/node_detail_styles']],
    ];
  }

  // ─────────────────────────────────────────────
  // DEAL
  // ─────────────────────────────────────────────

  /**
   * Build render array for a deal node.
   */
  protected function buildDeal(NodeInterface $node) {
    $nid   = $node->id();
    $title = $node->getTitle();

    $amount      = $this->fieldValue($node, 'field_amount');
    $probability = $this->fieldValue($node, 'field_probability');
    $notes       = $this->fieldValue($node, 'field_notes');
    $source      = $this->fieldValue($node, 'field_source');
    $lost_reason = $this->fieldValue($node, 'field_lost_reason');
    $created     = $node->getCreatedTime();
    $changed     = $node->getChangedTime();

    // Stage (taxonomy term).
    $stage_name  = '';
    $stage_class = 'stage-default';
    if ($node->hasField('field_stage') && !$node->get('field_stage')->isEmpty()) {
      $term = $node->get('field_stage')->entity;
      if ($term) {
        $stage_name  = $term->getName();
        $stage_class = 'stage-' . strtolower(str_replace([' ', '/'], ['-', '-'], $stage_name));
      }
    }

    // Expected close date.
    $close_date = NULL;
    if ($node->hasField('field_expected_close_date') && !$node->get('field_expected_close_date')->isEmpty()) {
      $close_date = $node->get('field_expected_close_date')->value;
    }
    if (!$close_date && $node->hasField('field_closing_date') && !$node->get('field_closing_date')->isEmpty()) {
      $close_date = $node->get('field_closing_date')->value;
    }

    // Contact ref.
    $contact_name = '';
    $contact_url  = '';
    if ($node->hasField('field_contact_ref') && !$node->get('field_contact_ref')->isEmpty()) {
      $contact = $node->get('field_contact_ref')->entity;
      if ($contact) {
        $contact_name = $contact->getTitle();
        $contact_url  = $contact->toUrl()->toString();
      }
    }

    // Organization ref.
    $org_name = '';
    $org_url  = '';
    if ($node->hasField('field_organization') && !$node->get('field_organization')->isEmpty()) {
      $org = $node->get('field_organization')->entity;
      if ($org) {
        $org_name = $org->getTitle();
        $org_url  = $org->toUrl()->toString();
      }
    }

    // Owner.
    $owner_name = '';
    $owner_url  = '';
    if ($node->hasField('field_owner') && !$node->get('field_owner')->isEmpty()) {
      $owner = $node->get('field_owner')->entity;
      if ($owner) {
        $owner_name = $owner->getDisplayName();
        $owner_url  = '/user/' . $owner->id();
      }
    }

    // Recent activities for this deal.
    $activities = $this->getRelatedActivities($nid, 'field_deal', 5);

    $can_edit = $node->access('update', $this->currentUser());

    return [
      '#theme' => 'crm_node_deal',
      '#node'        => $node,
      '#nid'         => $nid,
      '#title'       => $title,
      '#stage_name'  => $stage_name,
      '#stage_class' => $stage_class,
      '#amount'      => $amount,
      '#probability' => $probability,
      '#close_date'  => $close_date,
      '#source'      => $source,
      '#lost_reason' => $lost_reason,
      '#notes'       => $notes,
      '#contact_name' => $contact_name,
      '#contact_url'  => $contact_url,
      '#org_name'    => $org_name,
      '#org_url'     => $org_url,
      '#owner_name'  => $owner_name,
      '#owner_url'   => $owner_url,
      '#activities'  => $activities,
      '#created'     => $created,
      '#changed'     => $changed,
      '#can_edit'    => $can_edit,
      '#cache' => ['max-age' => 0, 'contexts' => ['user'], 'tags' => ['node:' . $nid]],
      '#attached' => ['library' => ['crm/node_detail_styles']],
    ];
  }

  // ─────────────────────────────────────────────
  // ORGANIZATION
  // ─────────────────────────────────────────────

  /**
   * Build render array for an organization node.
   */
  protected function buildOrganization(NodeInterface $node) {
    $nid   = $node->id();
    $title = $node->getTitle();
    $initial = strtoupper(mb_substr($title, 0, 1));

    // Logo.
    $logo_url  = '';
    $has_logo  = FALSE;
    if ($node->hasField('field_logo') && !$node->get('field_logo')->isEmpty()) {
      $file = $node->get('field_logo')->entity;
      if ($file) {
        $logo_url = \Drupal::service('file_url_generator')
          ->generateAbsoluteString($file->getFileUri());
        $has_logo = TRUE;
      }
    }

    // Industry (taxonomy).
    $industry = '';
    if ($node->hasField('field_industry') && !$node->get('field_industry')->isEmpty()) {
      $term = $node->get('field_industry')->entity;
      if ($term) {
        $industry = $term->getName();
      }
    }

    $website         = '';
    $website_display = '';
    if ($node->hasField('field_website') && !$node->get('field_website')->isEmpty()) {
      $website         = $node->get('field_website')->uri ?? '';
      $website_display = $node->get('field_website')->title ?: $website;
    }

    $annual_revenue  = $this->fieldValue($node, 'field_annual_revenue');
    $employees_count = $this->fieldValue($node, 'field_employees_count');
    $notes           = $this->fieldValue($node, 'field_notes');
    $created         = $node->getCreatedTime();
    $changed         = $node->getChangedTime();

    // Assigned staff.
    $staff_name = '';
    $staff_url  = '';
    if ($node->hasField('field_assigned_staff') && !$node->get('field_assigned_staff')->isEmpty()) {
      $staff = $node->get('field_assigned_staff')->entity;
      if ($staff) {
        $staff_name = $staff->getDisplayName();
        $staff_url  = '/user/' . $staff->id();
      }
    }

    // Contacts count & list.
    $contacts = $this->getOrgContacts($nid, 5);
    $contacts_count = $this->getOrgContactsCount($nid);

    // Deals count.
    $deals_count   = $this->getOrgDealsCount($nid);
    $active_deals  = $this->getOrgDeals($nid, 5);

    $tags = $this->getTaxonomyTermNames($node, 'field_tags');

    $can_edit = $node->access('update', $this->currentUser());

    return [
      '#theme' => 'crm_node_organization',
      '#node'            => $node,
      '#nid'             => $nid,
      '#title'           => $title,
      '#initial'         => $initial,
      '#logo_url'        => $logo_url,
      '#has_logo'        => $has_logo,
      '#industry'        => $industry,
      '#website'         => $website,
      '#website_display' => $website_display,
      '#annual_revenue'  => $annual_revenue,
      '#employees_count' => $employees_count,
      '#notes'           => $notes,
      '#staff_name'      => $staff_name,
      '#staff_url'       => $staff_url,
      '#contacts'        => $contacts,
      '#contacts_count'  => $contacts_count,
      '#deals_count'     => $deals_count,
      '#active_deals'    => $active_deals,
      '#tags'            => $tags,
      '#created'         => $created,
      '#changed'         => $changed,
      '#can_edit'        => $can_edit,
      '#cache' => ['max-age' => 0, 'contexts' => ['user'], 'tags' => ['node:' . $nid]],
      '#attached' => ['library' => ['crm/node_detail_styles']],
    ];
  }

  // ─────────────────────────────────────────────
  // ACTIVITY
  // ─────────────────────────────────────────────

  /**
   * Build render array for an activity node.
   */
  protected function buildActivity(NodeInterface $node) {
    $nid   = $node->id();
    $title = $node->getTitle();

    $outcome = $this->fieldValue($node, 'field_outcome');
    $notes   = $this->fieldValue($node, 'field_notes');
    $status  = $this->fieldValue($node, 'field_status');
    $created = $node->getCreatedTime();
    $changed = $node->getChangedTime();

    // Activity type (taxonomy or plain text).
    $activity_type = '';
    if ($node->hasField('field_type') && !$node->get('field_type')->isEmpty()) {
      $type_field = $node->get('field_type');
      $first_item = $type_field->first();
      if ($first_item) {
        // Try getting entity (taxonomy term).
        $entity = isset($first_item->entity) ? $first_item->entity : NULL;
        if ($entity) {
          $activity_type = $entity->getName();
        }
        else {
          $activity_type = $first_item->value ?? '';
        }
      }
    }

    // Activity datetime.
    $activity_date = NULL;
    if ($node->hasField('field_datetime') && !$node->get('field_datetime')->isEmpty()) {
      $activity_date = $node->get('field_datetime')->value;
    }

    // Assigned to (user).
    $assigned_name = '';
    $assigned_url  = '';
    if ($node->hasField('field_assigned_to') && !$node->get('field_assigned_to')->isEmpty()) {
      $user = $node->get('field_assigned_to')->entity;
      if ($user) {
        $assigned_name = $user->getDisplayName();
        $assigned_url  = '/user/' . $user->id();
      }
    }

    // Contact ref.
    $contact_name = '';
    $contact_url  = '';
    if ($node->hasField('field_contact_ref') && !$node->get('field_contact_ref')->isEmpty()) {
      $contact = $node->get('field_contact_ref')->entity;
      if ($contact) {
        $contact_name = $contact->getTitle();
        $contact_url  = $contact->toUrl()->toString();
      }
    }

    // Deal ref.
    $deal_name = '';
    $deal_url  = '';
    if ($node->hasField('field_deal') && !$node->get('field_deal')->isEmpty()) {
      $deal = $node->get('field_deal')->entity;
      if ($deal) {
        $deal_name = $deal->getTitle();
        $deal_url  = $deal->toUrl()->toString();
      }
    }

    $status_class = 'status-' . strtolower(str_replace(' ', '-', $status ?: 'pending'));

    $can_edit = $node->access('update', $this->currentUser());

    return [
      '#theme' => 'crm_node_activity',
      '#node'          => $node,
      '#nid'           => $nid,
      '#title'         => $title,
      '#activity_type' => $activity_type,
      '#activity_date' => $activity_date,
      '#status'        => $status,
      '#status_class'  => $status_class,
      '#outcome'       => $outcome,
      '#notes'         => $notes,
      '#assigned_name' => $assigned_name,
      '#assigned_url'  => $assigned_url,
      '#contact_name'  => $contact_name,
      '#contact_url'   => $contact_url,
      '#deal_name'     => $deal_name,
      '#deal_url'      => $deal_url,
      '#created'       => $created,
      '#changed'       => $changed,
      '#can_edit'      => $can_edit,
      '#cache' => ['max-age' => 0, 'contexts' => ['user'], 'tags' => ['node:' . $nid]],
      '#attached' => ['library' => ['crm/node_detail_styles']],
    ];
  }

  // ─────────────────────────────────────────────
  // HELPERS
  // ─────────────────────────────────────────────

  /**
   * Safely get plain text value of a single-value field.
   */
  protected function fieldValue(NodeInterface $node, string $field_name): string {
    if (!$node->hasField($field_name)) {
      return '';
    }
    $field = $node->get($field_name);
    if ($field->isEmpty()) {
      return '';
    }
    $item = $field->first();
    // Prefer 'value'; fall back to 'uri' (link fields).
    return (string) ($item->value ?? $item->uri ?? '');
  }

  /**
   * Get taxonomy term names from a multi-value term reference field.
   */
  protected function getTaxonomyTermNames(NodeInterface $node, string $field_name): array {
    $names = [];
    if (!$node->hasField($field_name)) {
      return $names;
    }
    foreach ($node->get($field_name) as $item) {
      $term = $item->entity;
      if ($term) {
        $names[] = $term->getName();
      }
    }
    return $names;
  }

  /**
   * Load deals related to a contact node.
   */
  protected function getRelatedDeals(int $nid, int $limit = 5): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_contact_ref', $nid)
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }

    $deals = [];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    foreach ($nodes as $deal) {
      $stage = '';
      if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
        $term = $deal->get('field_stage')->entity;
        if ($term) {
          $stage = $term->getName();
        }
      }
      $amount = $deal->hasField('field_amount') ? ($deal->get('field_amount')->value ?? '') : '';
      $deals[] = [
        'title'  => $deal->getTitle(),
        'url'    => $deal->toUrl()->toString(),
        'stage'  => $stage,
        'amount' => $amount ? number_format((float) $amount, 0, '.', ',') : '',
      ];
    }
    return $deals;
  }

  /**
   * Load activities related to a node by a reference field.
   */
  protected function getRelatedActivities(int $nid, string $ref_field, int $limit = 5): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('status', 1)
      ->condition($ref_field, $nid)
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }

    $activities = [];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    foreach ($nodes as $activity) {
      $type = '';
      if ($activity->hasField('field_type') && !$activity->get('field_type')->isEmpty()) {
        $type_item = $activity->get('field_type')->first();
        if (isset($type_item->entity)) {
          $type = $type_item->entity->getName();
        }
        else {
          $type = $type_item->value ?? '';
        }
      }
      $date = NULL;
      if ($activity->hasField('field_datetime') && !$activity->get('field_datetime')->isEmpty()) {
        $date = $activity->get('field_datetime')->value;
      }
      $status = $activity->hasField('field_status') ? ($activity->get('field_status')->value ?? '') : '';
      $activities[] = [
        'title'  => $activity->getTitle(),
        'url'    => $activity->toUrl()->toString(),
        'type'   => $type,
        'date'   => $date,
        'status' => $status,
      ];
    }
    return $activities;
  }

  /**
   * Get contacts belonging to an organization.
   */
  protected function getOrgContacts(int $org_nid, int $limit = 5): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('status', 1)
      ->condition('field_organization', $org_nid)
      ->sort('title', 'ASC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }
    $contacts = [];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    foreach ($nodes as $contact) {
      $email    = $contact->hasField('field_email') ? ($contact->get('field_email')->value ?? '') : '';
      $position = $contact->hasField('field_position') ? ($contact->get('field_position')->value ?? '') : '';
      $contacts[] = [
        'title'    => $contact->getTitle(),
        'url'      => $contact->toUrl()->toString(),
        'email'    => $email,
        'position' => $position,
        'initial'  => strtoupper(mb_substr($contact->getTitle(), 0, 1)),
      ];
    }
    return $contacts;
  }

  /**
   * Count contacts in an organization.
   */
  protected function getOrgContactsCount(int $org_nid): int {
    return (int) \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('status', 1)
      ->condition('field_organization', $org_nid)
      ->count()
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Count deals linked to an organization.
   */
  protected function getOrgDealsCount(int $org_nid): int {
    return (int) \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_organization', $org_nid)
      ->count()
      ->accessCheck(FALSE)
      ->execute();
  }

  /**
   * Get deals linked to an organization.
   */
  protected function getOrgDeals(int $org_nid, int $limit = 5): array {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_organization', $org_nid)
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    $ids = $query->execute();
    if (!$ids) {
      return [];
    }
    $deals = [];
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ids);
    foreach ($nodes as $deal) {
      $stage = '';
      if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
        $term = $deal->get('field_stage')->entity;
        if ($term) {
          $stage = $term->getName();
        }
      }
      $amount = $deal->hasField('field_amount') ? ($deal->get('field_amount')->value ?? '') : '';
      $deals[] = [
        'title'  => $deal->getTitle(),
        'url'    => $deal->toUrl()->toString(),
        'stage'  => $stage,
        'amount' => $amount ? number_format((float) $amount, 0, '.', ',') : '',
      ];
    }
    return $deals;
  }

}
