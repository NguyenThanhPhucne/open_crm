<?php

namespace Drupal\crm_dashboard\Support;

use Drupal\Component\Utility\Html;
use Drupal\Core\Url;

final class DashboardRecentDataAssembler {

  private const OPERATOR_NOT_IN = 'NOT IN';
  private const RELATIVE_TIME_JUST_NOW = 'just now';
  private const RELATIVE_TIME_MINUTE_SUFFIX = 'm ago';
  private const RELATIVE_TIME_HOUR_SUFFIX = 'h ago';
  private const RELATIVE_TIME_DAY_SUFFIX = 'd ago';
  private const RELATIVE_TIME_WEEK_SUFFIX = 'w ago';

  /**
   * Collect all recent datasets used by dashboard sections.
   */
  public static function collect_all($can_manage, $user_id, $now, array $closed_term_ids) {
    return [
      'activities' => self::collect_recent_activities($can_manage, $user_id),
      'deals' => self::collect_recent_deals($can_manage, $user_id),
      'contacts' => self::collect_recent_contacts($can_manage, $user_id, $now),
      'organizations' => self::collect_recent_organizations($can_manage, $user_id, $now),
      'pipeline' => self::collect_recent_pipeline($can_manage, $user_id, $now, $closed_term_ids),
    ];
  }

  private static function collect_recent_activities($can_manage, $user_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 30);

    self::apply_not_archived_condition($query, 'activity');
    if (!$can_manage && $user_id > 0) {
      $query->condition('field_assigned_to', $user_id);
    }

    $activity_ids = $query->execute() ?: [];
    $activities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($activity_ids);
    $current_time = \Drupal::time()->getCurrentTime();
    $items = [];

    foreach ($activities as $activity) {
      $items[] = self::map_activity($activity, $current_time);
    }

    return $items;
  }

  private static function collect_recent_deals($can_manage, $user_id) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);

    if (!$can_manage && $user_id > 0) {
      $query->condition('field_owner', $user_id);
    }

    $deal_ids = $query->execute() ?: [];
    $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids);
    $now = \Drupal::time()->getCurrentTime();
    $items = [];

    foreach ($deals as $deal) {
      $items[] = self::map_deal($deal, $now);
    }

    return $items;
  }

  private static function collect_recent_contacts($can_manage, $user_id, $now) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);

    if (!$can_manage && $user_id > 0) {
      $query->condition('field_owner', $user_id);
    }

    $contacts = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query->execute());
    $items = [];

    foreach ($contacts as $contact) {
      $org = '';
      if ($contact->hasField('field_organization') && !$contact->get('field_organization')->isEmpty()) {
        $org_entity = $contact->get('field_organization')->entity;
        if ($org_entity) {
          $org = $org_entity->getTitle();
        }
      }

      $source = '';
      if ($contact->hasField('field_source') && !$contact->get('field_source')->isEmpty()) {
        $source = $contact->get('field_source')->value ?? '';
      }

      $name = $contact->getTitle();
      $items[] = [
        'id' => $contact->id(),
        'title' => Html::escape($name),
        'initials' => strtoupper(mb_substr($name, 0, 1)),
        'org' => Html::escape($org),
        'source' => Html::escape($source),
        'relative_time' => self::format_relative_time($now - $contact->getChangedTime()),
      ];
    }

    return $items;
  }

  private static function collect_recent_organizations($can_manage, $user_id, $now) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);

    if (!$can_manage && $user_id > 0) {
      $query->condition('field_assigned_staff', $user_id);
    }

    $organizations = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query->execute());
    $items = [];

    foreach ($organizations as $org) {
      $industry = '';
      if ($org->hasField('field_industry') && !$org->get('field_industry')->isEmpty()) {
        $industry_item = $org->get('field_industry')->first();
        $industry_entity = $industry_item->entity ?? NULL;
        $industry = $industry_entity ? $industry_entity->getName() : ($industry_item->value ?? '');
      }

      $phone = '';
      if ($org->hasField('field_phone') && !$org->get('field_phone')->isEmpty()) {
        $phone = $org->get('field_phone')->value ?? '';
      }

      $name = $org->getTitle();
      $items[] = [
        'id' => $org->id(),
        'title' => Html::escape($name),
        'initials' => strtoupper(mb_substr($name, 0, 1)),
        'industry' => Html::escape($industry),
        'phone' => Html::escape($phone),
        'relative_time' => self::format_relative_time($now - $org->getChangedTime()),
      ];
    }

    return $items;
  }

  private static function collect_recent_pipeline($can_manage, $user_id, $now, array $closed_term_ids) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);

    if (!empty($closed_term_ids)) {
      $query->condition('field_stage', $closed_term_ids, self::OPERATOR_NOT_IN);
    }
    if (!$can_manage && $user_id > 0) {
      $query->condition('field_owner', $user_id);
    }

    $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query->execute());
    $items = [];

    foreach ($deals as $deal) {
      $amount = 0;
      if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
        $amount = (float) $deal->get('field_amount')->value;
      }

      $stage_label = 'New';
      $stage_key = 'new';
      if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty() && $deal->get('field_stage')->entity) {
        $stage_term = $deal->get('field_stage')->entity;
        $stage_label = $stage_term->getName();
        $stage_key = strtolower($stage_label);
      }

      $items[] = [
        'id' => $deal->id(),
        'title' => Html::escape($deal->getTitle()),
        'amount' => '$' . number_format($amount / 1000, 0) . 'K',
        'stage' => $stage_label,
        'stage_class' => 'pipeline-stage--' . preg_replace('/[^a-z0-9-]+/', '-', $stage_key),
        'relative_time' => self::format_relative_time($now - $deal->getChangedTime()),
      ];
    }

    return $items;
  }

  private static function format_relative_time($diff) {
    $relative_time = self::RELATIVE_TIME_JUST_NOW;

    if ($diff >= 604800) {
      $relative_time = floor($diff / 604800) . self::RELATIVE_TIME_WEEK_SUFFIX;
    }
    elseif ($diff >= 86400) {
      $relative_time = floor($diff / 86400) . self::RELATIVE_TIME_DAY_SUFFIX;
    }
    elseif ($diff >= 3600) {
      $relative_time = floor($diff / 3600) . self::RELATIVE_TIME_HOUR_SUFFIX;
    }
    elseif ($diff >= 60) {
      $relative_time = floor($diff / 60) . self::RELATIVE_TIME_MINUTE_SUFFIX;
    }

    return $relative_time;
  }

  private static function map_activity($activity, $current_time) {
    $type_value = self::read_entity_ref_name($activity, 'field_type', 'note', TRUE);
    $type_icons = [
      'call' => 'phone',
      'meeting' => 'calendar',
      'email' => 'mail',
      'note' => 'file-text',
      'task' => 'check-square',
    ];

    $time_diff = $current_time - $activity->getCreatedTime();
    $relative_time = self::format_verbose_relative_time($time_diff);

    return [
      'id' => $activity->id(),
      'title' => Html::escape($activity->label()),
      'type' => ucfirst($type_value),
      'icon' => $type_icons[$type_value] ?? 'activity',
      'owner' => Html::escape(self::read_entity_ref_name($activity, 'field_assigned_to', '', FALSE)),
      'contact' => Html::escape(self::read_entity_ref_title($activity, 'field_contact', '')),
      'url' => Url::fromRoute('entity.node.canonical', ['node' => $activity->id()])->toString(),
      'relative_time' => $relative_time,
      'timestamp' => $activity->getCreatedTime(),
    ];
  }

  private static function map_deal($deal, $now) {
    $amount = self::read_numeric_field($deal, 'field_amount');
    $stage_label = self::read_entity_ref_name($deal, 'field_stage', 'New', FALSE);
    $stage_key = strtolower($stage_label);
    $deal_diff = $now - $deal->getChangedTime();

    return [
      'id' => $deal->id(),
      'title' => $deal->getTitle(),
      'amount' => '$' . number_format($amount / 1000, 0) . 'K',
      'stage' => $stage_label,
      'stage_class' => 'deal-stage--' . preg_replace('/[^a-z0-9-]+/', '-', $stage_key),
      'contact' => self::read_entity_ref_title($deal, 'field_contact', ''),
      'relative_time' => self::format_relative_time($deal_diff),
      'freshness' => self::resolve_deal_freshness($deal_diff),
    ];
  }

  private static function resolve_deal_freshness($deal_diff) {
    $freshness = 'old';
    if ($deal_diff < 60 || $deal_diff < 3600) {
      $freshness = 'hot';
    }
    elseif ($deal_diff < 86400) {
      $freshness = 'today';
    }
    elseif ($deal_diff < 604800) {
      $freshness = 'week';
    }

    return $freshness;
  }

  private static function read_entity_ref_name($entity, $field_name, $default = '', $lower = FALSE) {
    $value = $default;
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $ref_entity = $entity->get($field_name)->entity;
      if ($ref_entity) {
        if (method_exists($ref_entity, 'getName')) {
          $value = $ref_entity->getName();
        }
        elseif (method_exists($ref_entity, 'getTitle')) {
          $value = $ref_entity->getTitle();
        }
        elseif (method_exists($ref_entity, 'getDisplayName')) {
          $value = $ref_entity->getDisplayName();
        }
        elseif (method_exists($ref_entity, 'label')) {
          $value = $ref_entity->label();
        }
      }
    }

    return $lower ? strtolower((string) $value) : (string) $value;
  }

  private static function read_entity_ref_title($entity, $field_name, $default = '') {
    $value = $default;
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $ref_entity = $entity->get($field_name)->entity;
      if ($ref_entity) {
        $value = $ref_entity->getTitle();
      }
    }

    return $value;
  }

  private static function read_numeric_field($entity, $field_name) {
    $value = 0.0;
    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
      $value = (float) $entity->get($field_name)->value;
    }

    return $value;
  }

  private static function format_verbose_relative_time($time_diff) {
    $relative_time = $time_diff . ' seconds ago';
    if ($time_diff >= 604800) {
      $weeks = floor($time_diff / 604800);
      $relative_time = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    }
    elseif ($time_diff >= 86400) {
      $days = floor($time_diff / 86400);
      $relative_time = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
    elseif ($time_diff >= 3600) {
      $hours = floor($time_diff / 3600);
      $relative_time = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    elseif ($time_diff >= 60) {
      $minutes = floor($time_diff / 60);
      $relative_time = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    }

    return $relative_time;
  }

  private static function apply_not_archived_condition($query, $bundle) {
    try {
      $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $bundle);
      if (isset($definitions['field_deleted_at'])) {
        $query->notExists('field_deleted_at');
      }
    }
    catch (\Throwable $e) {
      // Keep dashboard resilient even if field metadata is temporarily unavailable.
    }
  }

}
