<?php

namespace Drupal\crm_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for CRM Dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * Access check for dashboard pages.
   * 
   * Dashboard is accessible to all authenticated users.
   */
  public function accessView(Request $request, AccountInterface $account) {
    return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Display the dashboard.
   */
  public function view() {
    // Get current user to filter data
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Check if user is administrator
    $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager = in_array('sales_manager', $current_user->getRoles());
    $can_manage = $is_admin || $is_manager;
    
    // Use a consistent time window for this week and last week
    $now = \Drupal::time()->getCurrentTime();
    // Monday midnight of the current week (date('N') = 1 Mon … 7 Sun)
    $dow = (int) date('N', $now); // 1=Mon, 7=Sun
    $this_week_start = mktime(0, 0, 0, (int) date('n', $now), (int) date('j', $now) - ($dow - 1));
    
    // Get dashboard metrics (filtered by user ownership for non-admins)
    $contacts_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $contacts_query->condition('field_owner', $user_id);
    }
    $contacts_count = $contacts_query->count()->execute();

    // Contacts this week
    $contacts_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('created', $this_week_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $contacts_this_week_query->condition('field_owner', $user_id);
    }
    $contacts_this_week = $contacts_this_week_query->count()->execute();

    $orgs_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $orgs_query->condition('field_assigned_staff', $user_id);
    }
    $orgs_count = $orgs_query->count()->execute();

    // Midnight on the 1st of the current month
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);

    // Organizations this month
    $orgs_this_month_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('created', $month_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $orgs_this_month_query->condition('field_assigned_staff', $user_id);
    }
    $orgs_this_month = $orgs_this_month_query->count()->execute();

    $deals_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $deals_query->condition('field_owner', $user_id);
    }
    $deals_count = $deals_query->count()->execute();

    $activities_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $activities_query->condition('field_assigned_to', $user_id);
    }
    $activities_count = $activities_query->count()->execute();

    // Activities this week
    $activities_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('created', $this_week_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $activities_this_week_query->condition('field_assigned_to', $user_id);
    }
    $activities_this_week = $activities_this_week_query->count()->execute();

    // Load pipeline stages dynamically from taxonomy.
    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'pipeline_stage']);

    $stages = [];
    $stage_colors = [];
    $deals_by_stage = [];

    // Dynamic color palette — matches /crm/all-pipeline kanban column colors.
    $color_palette = [
      '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#10b981', '#ef4444',
      '#06b6d4', '#84cc16', '#f97316', '#a855f7', '#14b8a6', '#f43f5e',
    ];
    $color_index = 0;

    foreach ($stage_terms as $term) {
      $stage_id = $term->id();
      $stage_name = $term->getName();
      $stages[$stage_id] = $stage_name;
      $stage_colors[$stage_id] = $color_palette[$color_index % count($color_palette)];
      $color_index++;

      // Count deals in this stage (filtered by current user for non-admins).
      $stage_query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage_id)
        ->accessCheck(TRUE);
      if (!$can_manage && $user_id > 0) {
        $stage_query->condition('field_owner', $user_id);
      }
      $count = $stage_query->count()->execute();
      $deals_by_stage[$stage_id] = $count;
    }

    // Build lookup: term name (lowercase) → term ID for stage comparisons.
    // field_stage is an entity reference — must compare by target_id, NOT by string value.
    $stage_id_by_name = [];
    foreach ($stage_terms as $term) {
      $stage_id_by_name[strtolower($term->getName())] = $term->id();
    }
    $won_term_id  = $stage_id_by_name['won']  ?? null;
    $lost_term_id = $stage_id_by_name['lost'] ?? null;

    // Get total deal value and won/lost deals (filtered by current user for non-admins)
    // Use a single DB aggregate query instead of loading all deal entities into memory.
    $won_id  = (int) ($won_term_id  ?? 0);
    $lost_id = (int) ($lost_term_id ?? 0);
    $has_deleted_at_table = \Drupal::database()->schema()->tableExists('node__field_deleted_at');
    $agg = \Drupal::database()->select('node_field_data', 'n');
    $agg->leftJoin('node__field_amount', 'fa', 'fa.entity_id = n.nid AND fa.deleted = 0');
    $agg->leftJoin('node__field_stage',  'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $agg->condition('n.type', 'deal');
    if ($has_deleted_at_table) {
      $agg->leftJoin('node__field_deleted_at', 'fd', 'fd.entity_id = n.nid AND fd.deleted = 0');
      $agg->isNull('fd.field_deleted_at_value');
    }
    $agg->addExpression('COALESCE(SUM(fa.field_amount_value), 0)', 'total_value');
    $agg->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $won_id  THEN fa.field_amount_value ELSE 0 END), 0)", 'won_value');
    $agg->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $lost_id THEN fa.field_amount_value ELSE 0 END), 0)", 'lost_value');
    $agg->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $won_id  THEN 1 ELSE 0 END), 0)", 'won_count');
    $agg->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $lost_id THEN 1 ELSE 0 END), 0)", 'lost_count');
    $agg->addExpression("AVG(CASE WHEN fs.field_stage_target_id = $won_id THEN (UNIX_TIMESTAMP() - n.created) / 86400.0 END)", 'avg_days_won');
    if (!$can_manage && $user_id > 0) {
      $agg->leftJoin('node__field_owner', 'fo', 'fo.entity_id = n.nid AND fo.deleted = 0');
      $agg->condition('fo.field_owner_target_id', $user_id);
    }
    $totals     = $agg->execute()->fetchObject();
    $total_value = (float) ($totals->total_value ?? 0);
    $won_value   = (float) ($totals->won_value   ?? 0);
    $lost_value  = (float) ($totals->lost_value  ?? 0);
    $won_count   = (int)   ($totals->won_count   ?? 0);
    $lost_count  = (int)   ($totals->lost_count  ?? 0);
    $avg_days_in_pipeline = (int) round((float) ($totals->avg_days_won ?? 0));

    // Format values for display
    $total_value_display = '$' . number_format($total_value / 1000000, 1) . 'M';
    $won_value_display = '$' . number_format($won_value / 1000000, 1) . 'M';
    $lost_value_display = '$' . number_format($lost_value / 1000000, 1) . 'M';
    $active_value = $total_value - $won_value - $lost_value;
    
    // Calculate additional KPIs
    $total_closed = $won_count + $lost_count;
    $win_rate = $total_closed > 0 ? round(($won_count / $total_closed) * 100, 1) : 0;
    $avg_deal_size = $won_count > 0 ? round($won_value / $won_count, 0) : 0;
    $avg_deal_display = '$' . number_format($avg_deal_size / 1000, 0) . 'K';
    $conversion_rate = $deals_count > 0 ? round(($won_count / $deals_count) * 100, 1) : 0;
    
    // === ENHANCED METRICS FOR IMPROVED CRM INSIGHTS ===
    
    // 1. Overdue Activities - Activities with field_datetime in the past
    // CRITICAL for task management and follow-up
    $overdue_activities_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('field_datetime', date('Y-m-d\\TH:i:s', $now), '<=') // due date is today or earlier
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $overdue_activities_query->condition('field_assigned_to', $user_id);
    }
    $overdue_activities = $overdue_activities_query->count()->execute();
    
    // 2. Active Pipeline Value - Total value of non-closed deals
    // Shows open opportunities vs completed deals
    $active_value_display = '$' . number_format($active_value / 1000000, 1) . 'M';
    
    // 3. Revenue This Week - Won deals moved to Won stage this week (by changed timestamp)
    // Measures sales velocity and momentum
    $revenue_this_week_ids = [];
    $revenue_this_week = 0;
    if (!empty($won_term_id)) {
      $revenue_this_week_query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $won_term_id)
        ->condition('changed', $this_week_start, '>=')
        ->accessCheck(TRUE);
      if (!$can_manage && $user_id > 0) {
        $revenue_this_week_query->condition('field_owner', $user_id);
      }
      $revenue_this_week_ids = $revenue_this_week_query->execute();
    }
    if (!empty($revenue_this_week_ids)) {
      $revenue_deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($revenue_this_week_ids);
      foreach ($revenue_deals as $deal) {
        if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
          $revenue_this_week += floatval($deal->get('field_amount')->value);
        }
      }
    }
    $revenue_this_week_display = '$' . number_format($revenue_this_week / 1000000, 1) . 'M';
    $revenue_this_week_count = count($revenue_this_week_ids);
    
    // 4. Average Days in Pipeline comes from DB aggregate query above.
    
    // 5. Deals Due This Week - Deals with closing date in next 7 days
    // Helps prioritize urgent deals
    $week_end = $now + 604800; // 7 days from now
    $closed_term_ids = array_filter([$won_term_id, $lost_term_id]);
    $due_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_closing_date', date('Y-m-d', $now), '>=')
      ->condition('field_closing_date', date('Y-m-d', $week_end), '<=')
      ->accessCheck(TRUE);
    if (!empty($closed_term_ids)) {
      $due_this_week_query->condition('field_stage', $closed_term_ids, 'NOT IN');
    }
    if (!$can_manage && $user_id > 0) {
      $due_this_week_query->condition('field_owner', $user_id);
    }
    $due_this_week = $due_this_week_query->count()->execute();
    
    // 6. New Contacts This Month - Fresh leads added
    // Indicates pipeline filling
    $new_contacts_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('created', $month_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $new_contacts_query->condition('field_owner', $user_id);
    }
    $new_contacts = $new_contacts_query->count()->execute();
    
    // Get recent activities (last 30, filtered by current user for non-admins)
    $activity_ids_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 30);
    if (!$can_manage && $user_id > 0) {
      $activity_ids_query->condition('field_assigned_to', $user_id);
    }
    $activity_ids = $activity_ids_query->execute();
    
    $recent_activities = [];
    if (!empty($activity_ids)) {
      $activities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($activity_ids);
      $current_time = \Drupal::time()->getCurrentTime();
      
      foreach ($activities as $activity) {
        // Get activity type from taxonomy term (entity reference)
        $type_value = 'note'; // default
        if ($activity->hasField('field_type') && !$activity->get('field_type')->isEmpty()) {
          $type_term = $activity->get('field_type')->entity;
          if ($type_term) {
            $type_value = strtolower($type_term->getName());
          }
        }
        
        $type_icons = [
          'call' => 'phone',
          'meeting' => 'calendar',
          'email' => 'mail',
          'note' => 'file-text',
          'task' => 'check-square',
        ];
        
        // Get activity owner/assigned user
        $owner_name = '';
        if ($activity->hasField('field_assigned_to') && !$activity->get('field_assigned_to')->isEmpty()) {
          $owner = $activity->get('field_assigned_to')->entity;
          if ($owner) {
            $owner_name = $owner->getDisplayName();
          }
        }
        
        // Get contact associated with activity
        $contact_name = '';
        if ($activity->hasField('field_contact') && !$activity->get('field_contact')->isEmpty()) {
          $contact = $activity->get('field_contact')->entity;
          if ($contact) {
            $contact_name = $contact->getTitle();
          }
        }
        
        // Calculate relative time
        $time_diff = $current_time - $activity->getCreatedTime();
        if ($time_diff < 60) {
          $relative_time = $time_diff . ' seconds ago';
        } elseif ($time_diff < 3600) {
          $minutes = floor($time_diff / 60);
          $relative_time = $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 86400) {
          $hours = floor($time_diff / 3600);
          $relative_time = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($time_diff < 604800) {
          $days = floor($time_diff / 86400);
          $relative_time = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } else {
          $weeks = floor($time_diff / 604800);
          $relative_time = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        }
        
        // Generate URL to activity entity
        $activity_url = Url::fromRoute('entity.node.canonical', ['node' => $activity->id()])->toString();
        
        $recent_activities[] = [
          'id' => $activity->id(),
          'title' => Html::escape($activity->label()),
          'type' => ucfirst($type_value ?? 'note'),
          'icon' => $type_icons[$type_value] ?? 'activity',
          'owner' => Html::escape($owner_name),
          'contact' => Html::escape($contact_name),
          'url' => $activity_url,
          'relative_time' => $relative_time,
          'timestamp' => $activity->getCreatedTime(),
        ];
      }
    }
    
    // Get recent deals (last 8, newest-updated first, filtered by current user for non-admins)
    $deal_ids_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);
    if (!$can_manage && $user_id > 0) {
      $deal_ids_query->condition('field_owner', $user_id);
    }
    $deal_ids = $deal_ids_query->execute();
    
    $recent_deals = [];
    if (!empty($deal_ids)) {
      $deals_list = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids);
      foreach ($deals_list as $deal) {
        $amount = 0;
        if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
          $amount = floatval($deal->get('field_amount')->value);
        }
        
        $stage_key   = 'new';
        $stage_label = 'New';
        if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty() && $deal->get('field_stage')->entity) {
          $stage_term   = $deal->get('field_stage')->entity;
          $stage_label  = $stage_term->getName();
          $stage_key    = strtolower($stage_label);
        }

        // Keyed by lowercase term name (matches taxonomy: New, Qualified, Proposal, Negotiation, Won, Lost)
        $stage_colors_deals = [
          'new'         => '#dbeafe',
          'qualified'   => '#e9d5ff',
          'proposal'    => '#fed7aa',
          'negotiation' => '#fce7f3',
          'won'         => '#d1fae5',
          'lost'        => '#fee2e2',
        ];

        $contact_name = '';
        if ($deal->hasField('field_contact') && !$deal->get('field_contact')->isEmpty()) {
          $contact = $deal->get('field_contact')->entity;
          if ($contact) {
            $contact_name = $contact->getTitle();
          }
        }
        
        // Calculate relative time since last update
        $deal_changed = $deal->getChangedTime();
        $deal_now = \Drupal::time()->getCurrentTime();
        $deal_diff = $deal_now - $deal_changed;
        if ($deal_diff < 60) {
          $deal_relative_time = 'just now';
          $deal_freshness = 'hot';
        } elseif ($deal_diff < 3600) {
          $m = floor($deal_diff / 60);
          $deal_relative_time = $m . 'm ago';
          $deal_freshness = 'hot';
        } elseif ($deal_diff < 86400) {
          $h = floor($deal_diff / 3600);
          $deal_relative_time = $h . 'h ago';
          $deal_freshness = 'today';
        } elseif ($deal_diff < 604800) {
          $d = floor($deal_diff / 86400);
          $deal_relative_time = $d . 'd ago';
          $deal_freshness = 'week';
        } else {
          $wk = floor($deal_diff / 604800);
          $deal_relative_time = $wk . 'w ago';
          $deal_freshness = 'old';
        }

        $recent_deals[] = [
          'id'            => $deal->id(),
          'title'         => $deal->getTitle(),
          'amount'        => '$' . number_format($amount / 1000, 0) . 'K',
          'stage'         => $stage_label,
          'stage_color'   => $stage_colors_deals[$stage_key] ?? '#f1f5f9',
          'contact'       => $contact_name,
          'relative_time' => $deal_relative_time,
          'freshness'     => $deal_freshness,
        ];
      }
    }

    // ── Recent Contacts (last 8, sorted by changed DESC) ─────────────────────
    $rc_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);
    if (!$can_manage && $user_id > 0) {
      $rc_query->condition('field_owner', $user_id);
    }
    $recent_contacts = [];
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($rc_query->execute()) as $c) {
      $c_org = '';
      if ($c->hasField('field_organization') && !$c->get('field_organization')->isEmpty()) {
        $c_org_e = $c->get('field_organization')->entity;
        if ($c_org_e) { $c_org = $c_org_e->getTitle(); }
      }
      $c_source = '';
      if ($c->hasField('field_source') && !$c->get('field_source')->isEmpty()) {
        $c_source = $c->get('field_source')->value ?? '';
      }
      $c_diff = $now - $c->getChangedTime();
      if ($c_diff < 60)       { $c_time = 'just now'; }
      elseif ($c_diff < 3600) { $c_time = floor($c_diff / 60) . 'm ago'; }
      elseif ($c_diff < 86400){ $c_time = floor($c_diff / 3600) . 'h ago'; }
      elseif ($c_diff < 604800){ $c_time = floor($c_diff / 86400) . 'd ago'; }
      else                    { $c_time = floor($c_diff / 604800) . 'w ago'; }
      $c_name = $c->getTitle();
      $recent_contacts[] = [
        'id'            => $c->id(),
        'title'         => Html::escape($c_name),
        'initials'      => strtoupper(mb_substr($c_name, 0, 1)),
        'org'           => Html::escape($c_org),
        'source'        => Html::escape($c_source),
        'relative_time' => $c_time,
      ];
    }

    // ── Recent Organizations (last 8, sorted by changed DESC) ─────────────────
    $ro_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);
    if (!$can_manage && $user_id > 0) {
      $ro_query->condition('field_assigned_staff', $user_id);
    }
    $recent_organizations = [];
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($ro_query->execute()) as $o) {
      $o_industry = '';
      if ($o->hasField('field_industry') && !$o->get('field_industry')->isEmpty()) {
        $o_industry = $o->get('field_industry')->value ?? '';
      }
      $o_phone = '';
      if ($o->hasField('field_phone') && !$o->get('field_phone')->isEmpty()) {
        $o_phone = $o->get('field_phone')->value ?? '';
      }
      $o_diff = $now - $o->getChangedTime();
      if ($o_diff < 60)       { $o_time = 'just now'; }
      elseif ($o_diff < 3600) { $o_time = floor($o_diff / 60) . 'm ago'; }
      elseif ($o_diff < 86400){ $o_time = floor($o_diff / 3600) . 'h ago'; }
      elseif ($o_diff < 604800){ $o_time = floor($o_diff / 86400) . 'd ago'; }
      else                    { $o_time = floor($o_diff / 604800) . 'w ago'; }
      $o_name = $o->getTitle();
      $recent_organizations[] = [
        'id'            => $o->id(),
        'title'         => Html::escape($o_name),
        'initials'      => strtoupper(mb_substr($o_name, 0, 1)),
        'industry'      => Html::escape($o_industry),
        'phone'         => Html::escape($o_phone),
        'relative_time' => $o_time,
      ];
    }

    // ── Recent Pipeline Deals (last 8, active only, sorted by changed DESC) ───
    $rp_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 8);
    if (!empty($closed_term_ids)) {
      $rp_query->condition('field_stage', $closed_term_ids, 'NOT IN');
    }
    if (!$can_manage && $user_id > 0) {
      $rp_query->condition('field_owner', $user_id);
    }
    $pipeline_stage_palette = [
      'new'         => ['bg' => '#dbeafe', 'color' => '#1e40af'],
      'qualified'   => ['bg' => '#e9d5ff', 'color' => '#6b21a8'],
      'proposal'    => ['bg' => '#fed7aa', 'color' => '#9a3412'],
      'negotiation' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
    ];
    $recent_pipeline = [];
    foreach (\Drupal::entityTypeManager()->getStorage('node')->loadMultiple($rp_query->execute()) as $pd) {
      $pd_amount = 0;
      if ($pd->hasField('field_amount') && !$pd->get('field_amount')->isEmpty()) {
        $pd_amount = floatval($pd->get('field_amount')->value);
      }
      $pd_stage_label = 'New';
      $pd_stage_key   = 'new';
      if ($pd->hasField('field_stage') && !$pd->get('field_stage')->isEmpty() && $pd->get('field_stage')->entity) {
        $pd_st          = $pd->get('field_stage')->entity;
        $pd_stage_label = $pd_st->getName();
        $pd_stage_key   = strtolower($pd_stage_label);
      }
      $pd_sc   = $pipeline_stage_palette[$pd_stage_key] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
      $pd_diff = $now - $pd->getChangedTime();
      if ($pd_diff < 60)       { $pd_time = 'just now'; }
      elseif ($pd_diff < 3600) { $pd_time = floor($pd_diff / 60) . 'm ago'; }
      elseif ($pd_diff < 86400){ $pd_time = floor($pd_diff / 3600) . 'h ago'; }
      elseif ($pd_diff < 604800){ $pd_time = floor($pd_diff / 86400) . 'd ago'; }
      else                     { $pd_time = floor($pd_diff / 604800) . 'w ago'; }
      $recent_pipeline[] = [
        'id'            => $pd->id(),
        'title'         => Html::escape($pd->getTitle()),
        'amount'        => '$' . number_format($pd_amount / 1000, 0) . 'K',
        'stage'         => $pd_stage_label,
        'stage_bg'      => $pd_sc['bg'],
        'stage_color'   => $pd_sc['color'],
        'relative_time' => $pd_time,
      ];
    }

    // Build JSON data for charts
    $stage_labels_json = json_encode(array_values($stages));
    $stage_data_json = json_encode(array_values($deals_by_stage));
    $stage_colors_json = json_encode(array_values($stage_colors));

    // Define role-based routes for navigation using Drupal routing system
    // Admins see global CRM pages, regular users see personal pages
    // Uses Url::fromUserInput for Views pages and Url::fromRoute for custom routes
    $activities_url = $can_manage
      ? Url::fromUserInput('/crm/all-activities')->toString()
      : Url::fromUserInput('/crm/my-activities')->toString();

    $contacts_url = $can_manage
      ? Url::fromUserInput('/crm/all-contacts')->toString()
      : Url::fromUserInput('/crm/my-contacts')->toString();

    $organizations_url = $can_manage
      ? Url::fromUserInput('/crm/all-organizations')->toString()
      : Url::fromUserInput('/crm/my-organizations')->toString();

    $deals_url = $can_manage
      ? Url::fromUserInput('/crm/all-deals')->toString()
      : Url::fromUserInput('/crm/my-deals')->toString();

    $pipeline_url = Url::fromUserInput('/crm/my-pipeline')->toString();
    $dashboard_url = Url::fromUserInput('/crm/dashboard')->toString();

    // Greeting and date for dashboard hero
    $user_display_name = $current_user->getDisplayName() ?: 'there';
    $greeting_hour = (int) date('H', $now);
    $greeting = $greeting_hour < 12 ? 'Good morning' : ($greeting_hour < 18 ? 'Good afternoon' : 'Good evening');
    $today_display = date('l, F j, Y', $now);

    // Build HTML with professional design
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f0f2f5;
      min-height: 100vh;
      color: #1e293b;
    }
    
    .dashboard-container {
      max-width: 1600px;
      margin: 0 auto;
      animation: fadeIn 0.3s ease-in;
      padding: 0; 
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* ============================================
       MODERN SAAS CARD DESIGN SYSTEM
       ============================================ */

    /* Responsive card grid */
    .crm-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
      margin-bottom: 32px;
    }

    /* Base card styles */
    .crm-card {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 20px;
      border-radius: 16px;
      border: 1px solid #e5e7eb;
      background: white;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      text-decoration: none;
      color: inherit;
    }

    .crm-card:hover {
      transform: translateY(-4px);
      border-color: var(--card-color);
      background: var(--card-bg);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    }

    /* Card icon */
    .crm-card-icon {
      width: 40px;
      height: 40px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--card-bg);
      color: var(--card-color);
      flex-shrink: 0;
    }

    .crm-card-icon svg {
      width: 20px;
      height: 20px;
      stroke-width: 2;
    }

    /* Card content */
    .crm-card-content {
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex: 1;
    }

    .crm-card-title {
      font-size: 16px;
      font-weight: 600;
      color: #111827;
      letter-spacing: -0.01em;
    }

    .crm-card-desc {
      font-size: 14px;
      color: #6b7280;
      line-height: 1.4;
    }

    /* Card action */
    .crm-card-action {
      font-size: 14px;
      font-weight: 500;
      color: var(--card-color);
      margin-top: auto;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .crm-card:hover .crm-card-action {
      gap: 8px;
    }

    .crm-card-action::after {
      content: '→';
      transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Color variants */
    .crm-card-blue {
      --card-color: #3b82f6;
      --card-bg: #eff6ff;
    }

    .crm-card-green {
      --card-color: #10b981;
      --card-bg: #ecfdf5;
    }

    .crm-card-purple {
      --card-color: #8b5cf6;
      --card-bg: #f5f3ff;
    }

    .crm-card-orange {
      --card-color: #f59e0b;
      --card-bg: #fffbeb;
    }

    .crm-card-pink {
      --card-color: #ec4899;
      --card-bg: #fdf2f8;
    }

    .crm-card-teal {
      --card-color: #14b8a6;
      --card-bg: #f0fdfa;
    }

    .crm-card-cyan {
      --card-color: #06b6d4;
      --card-bg: #ecfdf5;
    }

    .crm-card-gray {
      --card-color: #6b7280;
      --card-bg: #f9fafb;
    }

    /* Responsive grid */
    @media (max-width: 1200px) {
      .crm-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (max-width: 768px) {
      .crm-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .crm-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ============================================
       END MODERN SAAS CARD DESIGN SYSTEM
       ============================================ */

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-bottom: 28px;
    }
    
    .main-content {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 24px;
      margin-bottom: 32px;
      align-items: stretch;
      min-height: 600px;
    }
    
    .left-column {
      display: flex;
      flex-direction: column;
      gap: 24px;
      height: 100%;
    }
    
    .left-column .section-card {
      flex: 1;
    }
    
    /* Responsive improvement for main content */
    @media (max-width: 1200px) {
      .main-content {
        grid-template-columns: 1fr;
      }
    }
    
    /* Data refresh indicator */
    .refresh-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 8px;
      border-radius: 16px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.7; }
    }
    
    .stat-card {
      background: white;
      border-radius: 16px;
      padding: 14px 18px;
      border: 1px solid #e9edf2;
      border-left: 3px solid var(--stat-color, #e2e8f0);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: flex;
      align-items: center;
      gap: 14px;
    }
    
    /* Color accent backgrounds for each stat-card variant */
    .stat-card.stat-card-blue {
      --stat-color: #3b82f6;
      --stat-bg: #eff6ff;
    }
    
    .stat-card.stat-card-green {
      --stat-color: #10b981;
      --stat-bg: #ecfdf5;
    }
    
    .stat-card.stat-card-purple {
      --stat-color: #8b5cf6;
      --stat-bg: #f5f3ff;
    }
    
    .stat-card.stat-card-orange {
      --stat-color: #f59e0b;
      --stat-bg: #fffbeb;
    }
    
    .stat-card.stat-card-pink {
      --stat-color: #ec4899;
      --stat-bg: #fdf2f8;
    }
    
    .stat-card.stat-card-red {
      --stat-color: #ef4444;
      --stat-bg: #fef2f2;
    }
    
    .stat-card.stat-card-teal {
      --stat-color: #14b8a6;
      --stat-bg: #f0fdfa;
    }
    
    .stat-card.stat-card-cyan {
      --stat-color: #06b6d4;
      --stat-bg: #ecfdf5;
    }
    
    .stat-card.stat-card-indigo {
      --stat-color: #4f46e5;
      --stat-bg: #eef2ff;
    }
    
    .stat-card.stat-card-emerald {
      --stat-color: #059669;
      --stat-bg: #ecfdf5;
    }
    
    .stat-card.stat-card-sky {
      --stat-color: #0284c7;
      --stat-bg: #e0f2fe;
    }
    
    .stat-card.stat-card-amber {
      --stat-color: #d97706;
      --stat-bg: #fef3c7;
    }
    
    .stat-card.stat-card-violet {
      --stat-color: #7c3aed;
      --stat-bg: #f5f3ff;
    }
    
    .stat-card::before {
      display: none;
    }
    
    .stat-card:hover {
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.09);
      transform: translateY(-2px);
      background: var(--stat-bg, white);
    }
    
    .stat-card:active {
      transform: translateY(0);
    }
    
    /* Old .stat-header hidden; layout now uses .stat-icon + .stat-body side by side */
    .stat-header {
      display: none;
    }

    .stat-body {
      flex: 1;
      min-width: 0;
    }

    .stat-icon {
      width: 42px;
      height: 42px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      color: var(--stat-color, #334155) !important;
      background: var(--stat-bg, #f8fafc);
    }

    .stat-card:hover .stat-icon,
    .stat-card:focus .stat-icon,
    .stat-card:focus-visible .stat-icon {
      color: var(--stat-color, #334155) !important;
    }
    
    .stat-icon i {
      width: 20px;
      height: 20px;
      stroke-width: 2;
    }

    .stat-icon svg,
    .section-title svg,
    .view-all-link svg,
    .recent-avatar svg,
    .hero-action-btn svg,
    .refresh-badge svg,
    .activity-icon svg {
      color: inherit !important;
      stroke: currentColor !important;
      fill: none !important;
    }

    .stat-icon svg {
      width: 20px;
      height: 20px;
      stroke-width: 2;
    }
    
    .stat-icon.blue { 
      background: #eff6ff;
      color: #3b82f6;
    }
    
    .stat-icon.green { 
      background: #ecfdf5;
      color: #10b981;
    }
    
    .stat-icon.purple { 
      background: #f5f3ff;
      color: #8b5cf6;
    }
    
    .stat-icon.orange { 
      background: #fffbeb;
      color: #f59e0b;
    }
    
    .stat-icon.pink { 
      background: #fdf2f8;
      color: #ec4899;
    }
    
    .stat-icon.cyan { 
      background: #ecf9fd;
      color: #06b6d4;
    }
    
    .stat-icon.red { 
      background: #fef2f2;
      color: #ef4444;
    }
    
    .stat-icon.indigo { 
      background: #eef2ff;
      color: #4f46e5;
    }
    
    .stat-icon.emerald { 
      background: #ecfdf5;
      color: #059669;
    }
    
    .stat-icon.sky { 
      background: #e0f2fe;
      color: #0284c7;
    }
    
    .stat-icon.amber { 
      background: #fef3c7;
      color: #d97706;
    }
    
    .stat-icon.violet { 
      background: #f5f3ff;
      color: #7c3aed;
    }
    
    .stat-label {
      font-size: 10.5px;
      color: #94a3b8;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.09em;
      margin-bottom: 2px;
    }
    
    .stat-value {
      font-size: 24px;
      font-weight: 800;
      color: #0f172a;
      line-height: 1;
      margin-bottom: 3px;
      letter-spacing: -0.02em;
      text-align: left;
    }
    
    .stat-desc {
      font-size: 11.5px;
      color: #94a3b8;
      font-weight: 500;
    }
    
    /* Trend indicator styles */
    .stat-trend {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 11.5px;
      font-weight: 600;
      margin-top: 3px;
    }
    
    .stat-trend.positive {
      color: #10b981;
    }
    
    .stat-trend.negative {
      color: #ef4444;
    }
    
    .stat-trend.neutral {
      color: #64748b;
    }
    
    .stat-trend-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 16px;
      height: 16px;
      border-radius: 3px;
      font-size: 10px;
    }
    
    .stat-trend.positive .stat-trend-icon {
      background: rgba(16, 185, 129, 0.1);
    }
    
    .stat-trend.negative .stat-trend-icon {
      background: rgba(239, 68, 68, 0.1);
    }
    
    .stat-trend.neutral .stat-trend-icon {
      background: rgba(100, 116, 139, 0.1);
    }
    
    .charts-section {
      display: flex;
      flex-direction: column;
      gap: 24px;
      margin-bottom: 0;
      flex: 1;
    }
    
    .section-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 2px 0 rgba(0,0,0,0.03), 0 4px 12px 0 rgba(0,0,0,0.04);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    
    .activities-card {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    
    .section-card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border-color: #cbd5e1;
    }

    .section-card canvas {
      max-height: 280px;
    }
    
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      padding-bottom: 12px;
      border-bottom: 1px solid #f1f5f9;
      flex: 0 0 auto;
    }
    
    .section-header > div:first-child {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 700;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 10px;
      letter-spacing: -0.01em;
    }
    
    .section-title i {
      color: #3b82f6;
      flex-shrink: 0;
    }
    
    .view-all-link {
      font-size: 13px;
      color: #3b82f6;
      text-decoration: none;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      padding: 6px 12px;
      border-radius: 16px;
    }
    
    .view-all-link:hover {
      color: #2563eb;
      background: rgba(59, 130, 246, 0.08);
    }
    
    /* Activity Items - Scrollable container */
    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 0;
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 0 8px 0 0;
      scroll-behavior: smooth;
      margin-right: -8px;
      min-height: 0;
    }
    
    /* Custom scrollbar for activities - modern style */
    .activity-list::-webkit-scrollbar {
      width: 6px;
    }
    
    .activity-list::-webkit-scrollbar-track {
      background: transparent;
    }
    
    .activity-list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 16px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .activity-list::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    /* Modern timeline-style activity item */
    .activity-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px;
      border-radius: 16px;
      transition: all 0.15s ease;
      border: 1px solid transparent;
      cursor: pointer;
      position: relative;
    }
    
    .activity-item:hover {
      background: #f7f9fb;
      border-color: #e6e8eb;
    }
    
    /* Activity icon - circular with gradient */
    .activity-icon {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      background: #eef2f7;
      color: #4c6ef5;
      font-weight: 600;
      min-width: 36px;
    }
    
    /* Icon type colors */
    .activity-icon.call { 
      background: #dbeafe;
      color: #1e40af;
    }
    .activity-icon.meeting { 
      background: #f5f3ff;
      color: #6b21a8;
    }
    .activity-icon.email { 
      background: #ecfdf5;
      color: #065f46;
    }
    .activity-icon.note { 
      background: #fffbeb;
      color: #92400e;
    }
    .activity-icon.task { 
      background: #fdf2f8;
      color: #831843;
    }
    
    /* Activity content - main info */
    .activity-content {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    
    /* Clickable activity title */
    .activity-title {
      font-weight: 500;
      text-decoration: none;
      color: #1f2937;
      font-size: 14px;
      transition: color 0.2s ease;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .activity-title:hover {
      color: #3b82f6;
    }
    
    /* Activity metadata - owner and relative time */
    .activity-meta {
      font-size: 12px;
      color: #6b7280;
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    
    .activity-meta .separator {
      color: #d1d5db;
    }
      padding: 2px 8px;
      border-radius: 16px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.02em;
      background: #f1f5f9;
      color: #475569;
    }
    
    .activity-contact {
      color: #3b82f6;
      font-weight: 500;
    }
    
    .activity-time {
      font-size: 11px;
      color: #94a3b8;
      flex-shrink: 0;
      white-space: nowrap;
    }
    
    /* Deal Items */
    .deals-section-card {
      max-height: 460px;
    }

    .deal-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      min-height: 0;
      padding: 0 8px 0 0;
      scroll-behavior: smooth;
      margin-right: -8px;
    }

    /* Custom scrollbar for deals — matches activity list */
    .deal-list::-webkit-scrollbar {
      width: 6px;
    }

    .deal-list::-webkit-scrollbar-track {
      background: transparent;
    }

    .deal-list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 16px;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .deal-list::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    .deal-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px;
      border-radius: 16px;
      border: 1px solid #f1f5f9;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      text-decoration: none;
      color: inherit;
    }
    
    .deal-item:hover {
      border-color: #cbd5e1;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
      transform: translateX(2px);
      background: #f8fafc;
    }

    .deal-right {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 6px;
      flex-shrink: 0;
    }

    .deal-updated {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 11px;
      color: #94a3b8;
      margin-top: 3px;
      font-weight: 500;
    }

    .freshness-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .freshness-dot.freshness-hot   { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); }
    .freshness-dot.freshness-today { background: #3b82f6; }
    .freshness-dot.freshness-week  { background: #f59e0b; }
    .freshness-dot.freshness-old   { background: #cbd5e1; }
    
    .deal-info {
      flex: 1;
      min-width: 0;
    }
    
    .deal-title {
      font-size: 14px;
      font-weight: 500;
      color: #1e293b;
      margin-bottom: 4px;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .deal-contact {
      font-size: 12px;
      color: #64748b;
    }
    
    .deal-amount {
      font-size: 15px;
      font-weight: 600;
      color: #0f172a;
      margin-right: 12px;
    }
    
    .deal-stage {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 16px;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    
    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #94a3b8;
    }
    
    .empty-state i {
      width: 48px;
      height: 48px;
      color: #cbd5e1;
      margin-bottom: 12px;
      opacity: 0.7;
    }
    
    .empty-state-text {
      font-size: 14px;
      color: #64748b;
      font-weight: 500;
    }
    
    .chart-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      flex: 1;
    }
    
    .chart-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #3b82f6, #8b5cf6, #ec4899);
      opacity: 0;
      transition: opacity 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .chart-card:hover {
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      transform: translateY(-2px);
      border-color: #cbd5e1;
    }
    
    .chart-card:hover::before {
      opacity: 1;
    }
    
    .chart-header {
      margin-bottom: 24px;
      padding-bottom: 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .chart-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .chart-title::before {
      content: '';
      width: 4px;
      height: 20px;
      background: linear-gradient(180deg, #3b82f6, #8b5cf6);
      border-radius: 2px;
    }
    
    .chart-subtitle {
      font-size: 13px;
      color: #64748b;
      margin-left: 14px;
    }
    
    .chart-container {
      position: relative;
      height: 280px;
      padding: 10px 0;
      flex: 1;
    }
    
    .chart-legend {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-top: 20px;
      padding-top: 16px;
      border-top: 1px solid #f1f5f9;
    }
    
    .legend-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #64748b;
    }
    
    .legend-color {
      width: 12px;
      height: 12px;
      border-radius: 3px;
    }
    
    /* CRM Navigation Bar - Drupal Toolbar Style */
    .crm-toolbar {
      background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%);
      border-bottom: 2px solid #3b82f6;
      box-shadow: 0 2px 6px rgba(59, 130, 246, 0.15);
      height: 42px;
      margin: -20px -20px 24px -20px;
    }
    
    .crm-toolbar-lining {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 100%;
      padding: 0 1rem;
      max-width: 100%;
    }
    
    .crm-toolbar-menu {
      display: flex;
      align-items: center;
      gap: 0;
      height: 100%;
    }
    
    .crm-toolbar-brand {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 0 16px;
      font-size: 14px;
      font-weight: 600;
      color: #333;
      text-decoration: none;
      height: 100%;
      border-right: 1px solid #e5e7eb;
    }
    
    .crm-toolbar-brand:hover {
      background: rgba(0, 0, 0, 0.03);
      color: #0969da;
    }
    
    .crm-toolbar-item {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 14px;
      height: 100%;
      font-size: 13px;
      font-weight: 500;
      color: #4b5563;
      text-decoration: none;
      border-right: 1px solid #f3f4f6;
      transition: all 0.15s ease;
      white-space: nowrap;
    }
    
    .crm-toolbar-item:hover {
      background: rgba(59, 130, 246, 0.08);
      color: #2563eb;
    }
    
    .crm-toolbar-item.active {
      background: linear-gradient(180deg, rgba(255,255,255,0.8) 0%, rgba(243,244,246,0.9) 100%);
      color: #1e40af;
      font-weight: 600;
      border-left: 1px solid #e5e7eb;
    }
    
    .crm-toolbar-item svg {
      width: 16px;
      height: 16px;
      stroke-width: 2;
    }
    
    .crm-toolbar-actions {
      display: flex;
      align-items: center;
      gap: 0;
      height: 100%;
    }
    
    .crm-toolbar-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 0 12px;
      height: 100%;
      font-size: 13px;
      font-weight: 600;
      color: #ffffff;
      text-decoration: none;
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      border-left: 1px solid rgba(255,255,255,0.1);
    }
    
    .crm-toolbar-btn:hover {
      background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.1);
    }
    
    .crm-toolbar-btn:active {
      transform: translateY(0);
    }
    
    .crm-toolbar-btn svg {
      width: 16px;
      height: 16px;
      stroke-width: 2.5;
    }
    
    .dashboard-container {
      padding-top: 39px;
    }
    
    /* Responsive Design for Activities Card */
    @media (max-width: 1200px) {
      /* Activities card stretches with left column */
    }
    
    @media (max-width: 768px) {
      .crm-toolbar-item span,
      .crm-toolbar-btn span,
      .crm-toolbar-brand span {
        display: none;
      }
      
      .crm-toolbar-item,
      .crm-toolbar-btn {
        padding: 0 10px;
      }
      
      .dashboard-header h1 {
        font-size: 24px;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .main-content {
        grid-template-columns: 1fr;
      }
      
      .activity-item {
        padding: 10px 6px;
      }
      
      .activity-icon {
        width: 36px;
        height: 36px;
      }
    }

    /* ================================================================
       BOTTOM SECTION — Recent Contacts / Organizations / Pipelines
       ================================================================ */
    .bottom-section {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      margin-bottom: 32px;
    }

    @media (max-width: 1200px) {
      .bottom-section { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 768px) {
      .bottom-section { grid-template-columns: 1fr; }
    }

    .recent-list {
      display: flex;
      flex-direction: column;
      gap: 0;
      overflow-y: auto;
      max-height: 360px;
      padding-right: 4px;
    }

    .recent-list::-webkit-scrollbar { width: 4px; }
    .recent-list::-webkit-scrollbar-track { background: transparent; }
    .recent-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 16px; }
    .recent-list::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    .recent-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      border-radius: 16px;
      transition: all 0.15s ease;
      border: 1px solid transparent;
      text-decoration: none;
      color: inherit;
      cursor: pointer;
    }

    .recent-item:hover {
      background: #f7f9fb;
      border-color: #e6e8eb;
    }

    .recent-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      font-size: 14px;
      font-weight: 700;
    }

    .recent-avatar.blue  { background: #dbeafe; color: #1e40af; }
    .recent-avatar.pink  { background: #fce7f3; color: #9d174d; }
    .recent-avatar.amber { background: #fef3c7; color: #92400e; }

    .recent-info {
      flex: 1;
      min-width: 0;
    }

    .recent-name {
      font-size: 14px;
      font-weight: 600;
      color: #111827;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .recent-sub {
      font-size: 12px;
      color: #6b7280;
      margin-top: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .recent-time {
      font-size: 11px;
      color: #94a3b8;
      flex-shrink: 0;
      white-space: nowrap;
      margin-left: auto;
      padding-left: 8px;
    }

    .pipeline-stage-mini {
      display: inline-block;
      padding: 2px 7px;
      border-radius: 16px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    /* ================================================================
       DASHBOARD HERO — Greeting header with quick-action buttons
       ================================================================ */
    .dashboard-hero {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(135deg, #ffffff 0%, #f0f5ff 60%, #f5f0ff 100%);
      border-radius: 16px;
      border: 1px solid #dde8ff;
      border-top: 3px solid #2563eb;
      padding: 26px 32px;
      margin-bottom: 28px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(37, 99, 235, 0.07);
    }

    .dashboard-hero::before {
      content: '';
      position: absolute;
      top: -30%;
      right: -3%;
      width: 340px;
      height: 340px;
      background: radial-gradient(circle, rgba(99, 102, 241, 0.06) 0%, transparent 70%);
      pointer-events: none;
    }

    .dashboard-hero::after {
      display: none;
    }

    .hero-left {
      position: relative;
      z-index: 1;
    }

    .hero-greeting {
      font-size: 23px;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 7px;
      letter-spacing: -0.01em;
    }

    .hero-date {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
    }

    .hero-date i {
      opacity: 0.6;
    }

    .hero-actions {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      position: relative;
      z-index: 1;
    }

    .hero-action-btn {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      border-radius: 16px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1.5px solid transparent;
      white-space: nowrap;
      cursor: pointer;
    }

    .hero-btn-primary {
      background: #2563eb;
      color: #ffffff;
      border-color: #2563eb;
    }

    .hero-btn-primary:hover {
      background: #1d4ed8;
      border-color: #1d4ed8;
      color: #ffffff;
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
    }

    .hero-btn-outline {
      background: #ffffff;
      color: #2563eb;
      border-color: #dbeafe;
    }

    .hero-btn-outline:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.12);
    }

    /* ================================================================
       STATS SECTION LABELS — Group headers inside the stats grid
       ================================================================ */
    .stats-row-label {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 10px;
      font-weight: 700;
      color: #b0b8c8;
      text-transform: uppercase;
      letter-spacing: 0.12em;
      padding: 10px 0 2px;
    }

    .stats-row-label:first-child {
      padding-top: 0;
    }

    .stats-row-label::after {
      content: '';
      flex: 1;
      height: 1px;
      background: #eaedf2;
    }

    @media (max-width: 768px) {
      .dashboard-hero {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
        padding: 20px;
      }
      .hero-actions {
        width: 100%;
      }
      .hero-action-btn {
        flex: 1;
        justify-content: center;
      }
    }
  </style>
  
  <div class="dashboard-container">

    <!-- Dashboard Hero: Greeting + Quick Actions -->
    <div class="dashboard-hero">
      <div class="hero-left">
        <div class="hero-greeting">{$greeting}, {$user_display_name}!</div>
        <div class="hero-date">
          <i data-lucide="calendar" width="13" height="13"></i>
          {$today_display}
        </div>
      </div>
      <div class="hero-actions">
        <a href="/crm/realtime-chat" class="hero-action-btn hero-btn-outline">
          <i data-lucide="messages-square" width="15" height="15"></i>
          <span>Open Chat</span>
        </a>
        <a href="/crm/add/contact" class="hero-action-btn hero-btn-primary">
          <i data-lucide="user-plus" width="15" height="15"></i>
          <span>New Contact</span>
        </a>
        <a href="/crm/add/organization" class="hero-action-btn hero-btn-primary">
          <i data-lucide="building-2" width="15" height="15"></i>
          <span>New Organization</span>
        </a>
        <a href="/crm/add/deal" class="hero-action-btn hero-btn-primary">
          <i data-lucide="plus-circle" width="15" height="15"></i>
          <span>New Deal</span>
        </a>
        <a href="/crm/add/activity" class="hero-action-btn hero-btn-primary">
          <i data-lucide="calendar-plus" width="15" height="15"></i>
          <span>Log Activity</span>
        </a>
      </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stats-row-label">Core Metrics</div>
      <a href="{$contacts_url}" class="stat-card stat-card-blue">
        <div class="stat-icon blue">
          <i data-lucide="users" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Contacts</div>
          <div class="stat-value">{$contacts_count}</div>
          <div class="stat-trend positive">
            <span class="stat-trend-icon">↑</span>
            <span>+{$contacts_this_week} this week</span>
          </div>
        </div>
      </a>
      
      <a href="{$organizations_url}" class="stat-card stat-card-pink">
        <div class="stat-icon pink">
          <i data-lucide="building-2" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Organizations</div>
          <div class="stat-value">{$orgs_count}</div>
          <div class="stat-trend positive">
            <span class="stat-trend-icon">↑</span>
            <span>+{$orgs_this_month} this month</span>
          </div>
        </div>
      </a>
      
      <a href="{$deals_url}" class="stat-card stat-card-orange">
        <div class="stat-icon orange">
          <i data-lucide="briefcase" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Deals</div>
          <div class="stat-value">{$deals_count}</div>
          <div class="stat-desc">In pipeline</div>
        </div>
      </a>
      
      <a href="{$deals_url}" class="stat-card stat-card-teal">
        <div class="stat-icon green">
          <i data-lucide="dollar-sign" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Total Value</div>
          <div class="stat-value">{$total_value_display}</div>
          <div class="stat-desc">Deal value</div>
        </div>
      </a>
      
      <div class="stats-row-label">Deal Performance</div>
      <a href="{$pipeline_url}" class="stat-card stat-card-green">
        <div class="stat-icon green">
          <i data-lucide="trending-up" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Won Deals</div>
          <div class="stat-value">{$won_count}</div>
          <div class="stat-desc">{$won_value_display} revenue</div>
        </div>
      </a>
      
      <a href="{$pipeline_url}" class="stat-card stat-card-red">
        <div class="stat-icon red">
          <i data-lucide="trending-down" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Lost Deals</div>
          <div class="stat-value">{$lost_count}</div>
          <div class="stat-desc">{$lost_value_display} lost</div>
        </div>
      </a>
      
      <a href="{$pipeline_url}" class="stat-card stat-card-pink">
        <div class="stat-icon pink">
          <i data-lucide="target" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Win Rate</div>
          <div class="stat-value">{$win_rate}%</div>
          <div class="stat-desc">Deals won rate</div>
        </div>
      </a>
      
      <a href="{$activities_url}" class="stat-card stat-card-cyan">
        <div class="stat-icon cyan">
          <i data-lucide="activity" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Activities</div>
          <div class="stat-value">{$activities_count}</div>
          <div class="stat-trend positive">
            <span class="stat-trend-icon">↑</span>
            <span>+{$activities_this_week} this week</span>
          </div>
        </div>
      </a>
      
      <div class="stats-row-label">Pipeline Intelligence</div>
      <a href="{$pipeline_url}" class="stat-card stat-card-purple">
        <div class="stat-icon blue">
          <i data-lucide="percent" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Conversion</div>
          <div class="stat-value">{$conversion_rate}%</div>
          <div class="stat-desc">Overall rate</div>
        </div>
      </a>
      
      <a href="{$deals_url}" class="stat-card stat-card-purple">
        <div class="stat-icon purple">
          <i data-lucide="bar-chart-3" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Avg Deal</div>
          <div class="stat-value">{$avg_deal_display}</div>
          <div class="stat-desc">Average value</div>
        </div>
      </a>
      
      <!-- Enhanced Metrics Row 1 -->
      <a href="{$activities_url}" class="stat-card stat-card-red">
        <div class="stat-icon red">
          <i data-lucide="alert-circle" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Overdue</div>
          <div class="stat-value">{$overdue_activities}</div>
          <div class="stat-trend negative">
            <span class="stat-trend-icon">!</span>
            <span>Needs attention</span>
          </div>
        </div>
      </a>
      
      <a href="{$pipeline_url}" class="stat-card stat-card-indigo">
        <div class="stat-icon indigo">
          <i data-lucide="layers" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Active Pipeline</div>
          <div class="stat-value">{$active_value_display}</div>
          <div class="stat-desc">Open opportunities</div>
        </div>
      </a>
      
      <div class="stats-row-label">Weekly Focus</div>
      <a href="{$deals_url}" class="stat-card stat-card-emerald">
        <div class="stat-icon emerald">
          <i data-lucide="dollar-sign" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">This Week</div>
          <div class="stat-value">{$revenue_this_week_display}</div>
          <div class="stat-trend positive">
            <span class="stat-trend-icon">↑</span>
            <span>{$revenue_this_week_count} deals closed</span>
          </div>
        </div>
      </a>
      
      <a href="{$deals_url}" class="stat-card stat-card-sky">
        <div class="stat-icon sky">
          <i data-lucide="calendar-check" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Due This Week</div>
          <div class="stat-value">{$due_this_week}</div>
          <div class="stat-desc">Deals closing soon</div>
        </div>
      </a>
      
      <!-- Enhanced Metrics Row 2 -->
      <a href="{$pipeline_url}" class="stat-card stat-card-amber">
        <div class="stat-icon amber">
          <i data-lucide="clock" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">Avg Cycle</div>
          <div class="stat-value">{$avg_days_in_pipeline}<span style="font-size:14px;font-weight:600;color:#94a3b8"> days</span></div>
          <div class="stat-desc">Days in pipeline</div>
        </div>
      </a>
      
      <a href="{$contacts_url}" class="stat-card stat-card-violet">
        <div class="stat-icon violet">
          <i data-lucide="user-plus" width="20" height="20"></i>
        </div>
        <div class="stat-body">
          <div class="stat-label">New Contacts</div>
          <div class="stat-value">{$new_contacts}</div>
          <div class="stat-trend positive">
            <span class="stat-trend-icon">↑</span>
            <span>This month</span>
          </div>
        </div>
      </a>
    </div>
    
    <!-- Main Content Grid -->
    <div class="main-content">
      <!-- Left Column: Charts + Recent Deals -->
      <div class="left-column">
        <!-- Charts -->
        <div class="charts-section">
          <div class="chart-card">
            <div class="chart-header">
              <div class="chart-title">Pipeline Stage Distribution</div>
              <div class="chart-subtitle">Current deals by stage</div>
            </div>
            <div class="chart-container">
              <canvas id="stageChart"
                data-labels='{$stage_labels_json}'
                data-values='{$stage_data_json}'></canvas>
            </div>
          </div>
          
          <div class="chart-card">
            <div class="chart-header">
              <div class="chart-title">Deal Value Overview</div>
              <div class="chart-subtitle">Won vs Lost vs Active</div>
            </div>
            <div class="chart-container">
              <canvas id="valueChart"
                data-won="{$won_value}"
                data-lost="{$lost_value}"
                data-active="{$active_value}"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Recent Deals -->
        <div class="section-card deals-section-card">
          <div class="section-header">
            <div class="section-title">
              <i data-lucide="briefcase" width="20" height="20"></i>
              Recent Deals
            </div>
            <a href="{$deals_url}" class="view-all-link">
              View all
              <i data-lucide="arrow-right" width="14" height="14"></i>
            </a>
          </div>
          <div class="deal-list">
HTML;

    // Add recent deals
    if (!empty($recent_deals)) {
      foreach ($recent_deals as $deal) {
        $deal_url = '/node/' . $deal['id'];
        $html .= <<<DEAL
            <a href="{$deal_url}" class="deal-item">
              <div class="deal-info">
                <div class="deal-title">{$deal['title']}</div>
                <div class="deal-contact">{$deal['contact']}</div>
                <div class="deal-updated">
                  <span class="freshness-dot freshness-{$deal['freshness']}"></span>
                  {$deal['relative_time']}
                </div>
              </div>
              <div class="deal-right">
                <div class="deal-amount">{$deal['amount']}</div>
                <span class="deal-stage" style="background: {$deal['stage_color']}; color: #0f172a;">{$deal['stage']}</span>
              </div>
            </a>
DEAL;
      }
    } else {
      $html .= <<<EMPTY
            <div class="empty-state">
              <i data-lucide="inbox"></i>
              <div class="empty-state-text">No deals yet</div>
            </div>
EMPTY;
    }

    $html .= <<<HTML
          </div>
        </div>
      </div>
      
      <!-- Right Sidebar: Recent Activities -->
      <div class="section-card activities-card">
        <div class="section-header">
          <div>
            <div class="section-title">
              <i data-lucide="activity" width="20" height="20"></i>
              Recent Activities
            </div>
            <div class="refresh-badge">↻ Live</div>
          </div>
          <a href="{$activities_url}" class="view-all-link">
            View all
            <i data-lucide="arrow-right" width="14" height="14"></i>
          </a>
        </div>
        <div class="activity-list">
HTML;

    // Add recent activities
    if (!empty($recent_activities)) {
      foreach ($recent_activities as $activity) {
        $type_class = strtolower($activity['type']);
        $meta_parts = [];
        
        // Add owner to metadata
        if ($activity['owner']) {
          $meta_parts[] = $activity['owner'];
        }
        
        // Add relative time
        $meta_parts[] = $activity['relative_time'];
        
        // Build metadata string
        $meta_string = implode(' • ', array_filter($meta_parts));
        
        // Build activity item HTML
        $html .= <<<ACTIVITY
          <div class="activity-item">
            <div class="activity-icon {$type_class}">
              <i data-lucide="{$activity['icon']}" width="18" height="18"></i>
            </div>
            <div class="activity-content">
              <a href="{$activity['url']}" class="activity-title" title="{$activity['title']}">
                {$activity['title']}
              </a>
              <div class="activity-meta">
                {$meta_string}
              </div>
            </div>
          </div>
ACTIVITY;
      }
    } else {
      $html .= <<<EMPTY
          <div class="empty-state">
            <i data-lucide="inbox"></i>
            <div class="empty-state-text">No activities yet</div>
          </div>
EMPTY;
    }

    $html .= <<<HTML
        </div>
      </div>
    </div>

    <!-- Bottom Section: Recent Contacts, Recent Organizations, Recent Pipelines -->
    <div class="bottom-section">
      <!-- Recent Contacts -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <i data-lucide="users" width="20" height="20"></i>
            Recent Contacts
          </div>
          <a href="{$contacts_url}" class="view-all-link">
            View all <i data-lucide="arrow-right" width="14" height="14"></i>
          </a>
        </div>
        <div class="recent-list recent-contacts-list">
HTML;
    if (!empty($recent_contacts)) {
      foreach ($recent_contacts as $rc) {
        $rc_url = '/node/' . $rc['id'];
        $rc_sub = $rc['org'] ?: ($rc['source'] ?: '');
        $html  .= '<a href="' . $rc_url . '" class="recent-item">'
          . '<div class="recent-avatar blue">' . $rc['initials'] . '</div>'
          . '<div class="recent-info"><div class="recent-name">' . $rc['title'] . '</div>'
          . '<div class="recent-sub">' . $rc_sub . '</div></div>'
          . '<span class="recent-time">' . $rc['relative_time'] . '</span></a>';
      }
    } else {
      $html .= '<div class="empty-state"><i data-lucide="user-x"></i><div class="empty-state-text">No contacts yet</div></div>';
    }
    $html .= <<<HTML
        </div>
      </div>

      <!-- Recent Organizations -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <i data-lucide="building-2" width="20" height="20"></i>
            Recent Organizations
          </div>
          <a href="{$organizations_url}" class="view-all-link">
            View all <i data-lucide="arrow-right" width="14" height="14"></i>
          </a>
        </div>
        <div class="recent-list recent-orgs-list">
HTML;
    if (!empty($recent_organizations)) {
      foreach ($recent_organizations as $ro) {
        $ro_url = '/node/' . $ro['id'];
        $ro_sub = $ro['industry'] ?: ($ro['phone'] ?: '');
        $html  .= '<a href="' . $ro_url . '" class="recent-item">'
          . '<div class="recent-avatar pink">' . $ro['initials'] . '</div>'
          . '<div class="recent-info"><div class="recent-name">' . $ro['title'] . '</div>'
          . '<div class="recent-sub">' . $ro_sub . '</div></div>'
          . '<span class="recent-time">' . $ro['relative_time'] . '</span></a>';
      }
    } else {
      $html .= '<div class="empty-state"><i data-lucide="building"></i><div class="empty-state-text">No organizations yet</div></div>';
    }
    $html .= <<<HTML
        </div>
      </div>

      <!-- Recent Pipelines -->
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <i data-lucide="git-branch" width="20" height="20"></i>
            Recent Pipelines
          </div>
          <a href="{$pipeline_url}" class="view-all-link">
            View all <i data-lucide="arrow-right" width="14" height="14"></i>
          </a>
        </div>
        <div class="recent-list recent-pipeline-list">
HTML;
    if (!empty($recent_pipeline)) {
      foreach ($recent_pipeline as $rp) {
        $rp_url = '/node/' . $rp['id'];
        $rp_bg  = $rp['stage_bg'];
        $rp_col = $rp['stage_color'];
        $html  .= '<a href="' . $rp_url . '" class="recent-item">'
          . '<div class="recent-avatar" style="background:' . $rp_bg . ';color:' . $rp_col . '">'
          . '<i data-lucide="circle-dot" width="16" height="16"></i></div>'
          . '<div class="recent-info"><div class="recent-name">' . $rp['title'] . '</div>'
          . '<div class="recent-sub"><span class="pipeline-stage-mini" style="background:' . $rp_bg . ';color:' . $rp_col . '">' . $rp['stage'] . '</span>&nbsp;' . $rp['amount'] . '</div></div>'
          . '<span class="recent-time">' . $rp['relative_time'] . '</span></a>';
      }
    } else {
      $html .= '<div class="empty-state"><i data-lucide="inbox"></i><div class="empty-state-text">No active pipeline deals</div></div>';
    }
    $html .= <<<HTML
        </div>
      </div>
    </div>
  </div>
  
  <script>
    function ensureLucideReady(callback) {
      if (window.lucide && typeof window.lucide.createIcons === 'function') {
        callback();
        return;
      }
      const existing = document.querySelector('script[data-lucide-fallback="1"]');
      if (existing) {
        existing.addEventListener('load', () => {
          if (window.lucide && typeof window.lucide.createIcons === 'function') callback();
        }, { once: true });
        return;
      }
      const script = document.createElement('script');
      script.src = 'https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js';
      script.defer = true;
      script.setAttribute('data-lucide-fallback', '1');
      script.onload = () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') callback();
      };
      document.head.appendChild(script);
    }

    // Real-time Data Synchronization System
    class DashboardSync {
      constructor() {
        this.refreshInterval = 5000; // 5 seconds
        this.isRefreshing = false;
        this.lastRefreshTime = Date.now();
        this.pollingTimer = null;
        this.init();
      }

      init() {
        document.addEventListener('crm:activity-created', () => this.refreshDashboard());
        document.addEventListener('crm:deal-updated',     () => this.refreshDashboard());
        document.addEventListener('crm:stage-changed',    () => this.refreshDashboard());
        window.addEventListener('focus', () => this.refreshDashboard());

        // Pause polling when tab is hidden, resume when visible
        document.addEventListener('visibilitychange', () => {
          if (document.hidden) {
            this.stopPolling();
          } else {
            this.refreshDashboard();
            this.startPolling();
          }
        });

        this.startPolling();
        this.showRefreshStatus('↻ Live');
        this.startTimeSince();
      }

      startPolling() {
        this.stopPolling();
        this.pollingTimer = setInterval(() => this.refreshDashboard(), this.refreshInterval);
      }

      stopPolling() {
        if (this.pollingTimer) { clearInterval(this.pollingTimer); this.pollingTimer = null; }
      }

      // Show "X s ago" counter in badge
      startTimeSince() {
        setInterval(() => {
          if (this.isRefreshing) return;
          const secs = Math.round((Date.now() - this.lastRefreshTime) / 1000);
          if (secs < 5)  { this.showRefreshStatus('↻ Live'); return; }
          if (secs < 60) { this.showRefreshStatus('↻ ' + secs + 's ago'); return; }
          this.showRefreshStatus('↻ ' + Math.round(secs / 60) + 'm ago');
        }, 5000);
      }

      async refreshDashboard() {
        if (this.isRefreshing) return;
        this.isRefreshing = true;
        this.showRefreshStatus('↻ ...');
        try {
          const response = await fetch('/crm/dashboard/refresh?_=' + Date.now(), {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json'
            }
          });
          if (!response.ok) throw new Error('HTTP ' + response.status);
          const payload = await response.json();
          if (!payload || !payload.success || !payload.metrics) {
            throw new Error('Invalid refresh payload');
          }

          this.applyMetrics(payload.metrics);
          this.applyStageDistribution(payload.stage_distribution || {});

          if (window_valueChart) {
            window_valueChart.data.datasets[0].data = [
              Number(payload.metrics.won_value || 0),
              Number(payload.metrics.lost_value || 0),
              Number(payload.metrics.active_value || 0)
            ];
            window_valueChart.update('active');
          }

          ensureLucideReady(() => window.lucide.createIcons());

          this.lastRefreshTime = Date.now();
          this.showRefreshStatus('↻ Live');
        } catch (error) {
          console.error('Dashboard refresh failed:', error);
          this.showRefreshStatus('⚠ Retry', true);
        } finally {
          this.isRefreshing = false;
        }
      }

      applyMetrics(metrics) {
        this.setCardValue('Contacts', Number(metrics.contacts || 0));
        this.setCardTrend('Contacts', '+' + Number(metrics.contacts_this_week || 0) + ' this week');

        this.setCardValue('Organizations', Number(metrics.organizations || 0));
        this.setCardTrend('Organizations', '+' + Number(metrics.orgs_this_month || 0) + ' this month');

        this.setCardValue('Deals', Number(metrics.deals || 0));
        this.setCardValue('Total Value', metrics.total_value_display || '$0.0M');

        this.setCardValue('Won Deals', Number(metrics.won || 0));
        this.setCardDesc('Won Deals', (metrics.won_value_display || '$0.0M') + ' revenue');

        this.setCardValue('Lost Deals', Number(metrics.lost || 0));
        this.setCardDesc('Lost Deals', (metrics.lost_value_display || '$0.0M') + ' lost');

        this.setCardValue('Win Rate', Number(metrics.win_rate || 0) + '%');

        this.setCardValue('Activities', Number(metrics.activities || 0));
        this.setCardTrend('Activities', '+' + Number(metrics.activities_this_week || 0) + ' this week');

        this.setCardValue('Conversion', Number(metrics.conversion_rate || 0) + '%');
        this.setCardValue('Avg Deal', metrics.avg_deal_display || '$0K');

        this.setCardValue('Overdue', Number(metrics.overdue_activities || 0));
        this.setCardValue('Active Pipeline', metrics.active_value_display || '$0.0M');

        this.setCardValue('This Week', metrics.revenue_this_week_display || '$0.0M');
        this.setCardTrend('This Week', Number(metrics.revenue_this_week_count || 0) + ' deals closed');

        this.setCardValue('Due This Week', Number(metrics.due_this_week || 0));
        this.setCardValue('Avg Cycle', Number(metrics.avg_days_in_pipeline || 0) + '<span style="font-size:14px;font-weight:600;color:#94a3b8"> days</span>', true);

        this.setCardValue('New Contacts', Number(metrics.new_contacts_this_month || 0));
      }

      applyStageDistribution(stageDistribution) {
        if (!window_stageChart || !stageDistribution) return;
        const labels = window_stageChart.data.labels || [];
        const values = labels.map((label) => {
          if (typeof stageDistribution[label] !== 'undefined') {
            return Number(stageDistribution[label] || 0);
          }
          const lowerLabel = String(label).toLowerCase();
          const key = Object.keys(stageDistribution).find((k) => String(k).toLowerCase() === lowerLabel);
          return key ? Number(stageDistribution[key] || 0) : 0;
        });
        window_stageChart.data.datasets[0].data = values;
        window_stageChart.update('active');
      }

      getCardByLabel(labelText) {
        const cards = document.querySelectorAll('.stat-card');
        for (const card of cards) {
          const label = card.querySelector('.stat-label');
          if (label && label.textContent && label.textContent.trim().toLowerCase() === labelText.toLowerCase()) {
            return card;
          }
        }
        return null;
      }

      setCardValue(labelText, value, isHtml = false) {
        const card = this.getCardByLabel(labelText);
        if (!card) return;
        const valueEl = card.querySelector('.stat-value');
        if (!valueEl) return;
        if (isHtml) {
          valueEl.innerHTML = String(value);
        } else {
          valueEl.textContent = String(value);
        }
      }

      setCardDesc(labelText, value) {
        const card = this.getCardByLabel(labelText);
        if (!card) return;
        const descEl = card.querySelector('.stat-desc');
        if (!descEl) return;
        descEl.textContent = String(value);
      }

      setCardTrend(labelText, value) {
        const card = this.getCardByLabel(labelText);
        if (!card) return;
        const trendText = card.querySelector('.stat-trend span:last-child');
        if (!trendText) return;
        trendText.textContent = String(value);
      }

      showRefreshStatus(message, isError = false) {
        const badge = document.querySelector('.refresh-badge');
        if (!badge) return;
        badge.textContent = message;
        badge.style.background = isError ? 'rgba(239,68,68,0.1)'   : 'rgba(16,185,129,0.1)';
        badge.style.color      = isError ? '#ef4444'               : '#10b981';
      }
    }
    
    // Initialize dashboard sync on page load
    document.addEventListener('DOMContentLoaded', () => {
      window.dashboardSync = new DashboardSync();
      ensureLucideReady(() => window.lucide.createIcons());
    });

    // ── Chart instances (global so DashboardSync can update them) ────────────
    let window_stageChart = null;
    let window_valueChart = null;
    
    // Modern color palette — matches /crm/all-pipeline kanban column colors
    const colors = {
      blue: '#3b82f6',
      blueLight: '#60a5fa',
      purple: '#8b5cf6',
      purpleLight: '#a78bfa',
      green: '#10b981',
      greenLight: '#34d399',
      yellow: '#f59e0b',
      yellowLight: '#fbbf24',
      pink: '#ec4899',
      pinkLight: '#f472b6',
      emerald: '#10b981',
      emeraldLight: '#34d399',
      red: '#ef4444',
      redLight: '#f87171',
      slate: '#64748b'
    };
    
    // Custom plugin to add data labels on bars
    const dataLabelsPlugin = {
      id: 'dataLabels',
      afterDatasetDraw(chart, args, options) {
        const { ctx } = chart;
        ctx.save();
        
        const dataset = args.meta.data;
        dataset.forEach((datapoint, index) => {
          const value = chart.data.datasets[0].data[index];
          if (value > 0) {
            const x = datapoint.x + 10;
            const y = datapoint.y + (datapoint.height / 2);
            
            ctx.font = 'bold 13px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'middle';
            ctx.fillText(value, x, y);
          }
        });
        
        ctx.restore();
      }
    };
    
    // Stage Chart - Enhanced Horizontal Bar with gradients
    const stageCtx = document.getElementById('stageChart').getContext('2d');
    
    // Create gradient backgrounds for each bar
    const createGradient = (ctx, color1, color2, width) => {
      const gradient = ctx.createLinearGradient(0, 0, width, 0);
      gradient.addColorStop(0, color1);
      gradient.addColorStop(1, color2);
      return gradient;
    };
    
    window_stageChart = new Chart(stageCtx, {
      type: 'bar',
      data: {
        labels: {$stage_labels_json},
        datasets: [{
          label: 'Deals',
          data: {$stage_data_json},
          backgroundColor: function(context) {
            const chart = context.chart;
            const {ctx, chartArea} = chart;
            if (!chartArea) return colors.blue;
            
            const colorPairs = [
              [colors.blue,   colors.blueLight],
              [colors.purple, colors.purpleLight],
              [colors.yellow, colors.yellowLight],
              [colors.pink,   colors.pinkLight],
              [colors.emerald, colors.emeraldLight],
              [colors.red,    colors.redLight]
            ];
            
            const index = context.dataIndex;
            const pair = colorPairs[index % colorPairs.length];
            return createGradient(ctx, pair[0], pair[1], chartArea.right);
          },
          borderRadius: 8,
          borderSkipped: false,
          maxBarThickness: 40,
          barPercentage: 0.75,
          categoryPercentage: 0.85,
          hoverBackgroundColor: function(context) {
            const index = context.dataIndex;
            const solidColors = [
              colors.blue, colors.purple, colors.yellow,
              colors.pink, colors.emerald, colors.red
            ];
            return solidColors[index % solidColors.length];
          },
          hoverBorderWidth: 0
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        animation: {
          duration: 1200,
          easing: 'easeInOutQuart',
          delay: (context) => {
            return context.dataIndex * 100;
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: 'rgba(30, 41, 59, 0.95)',
            padding: 16,
            titleFont: { 
              size: 14,
              weight: '600'
            },
            bodyFont: { 
              size: 16, 
              weight: 'bold' 
            },
            cornerRadius: 10,
            displayColors: false,
            callbacks: {
              title: function(context) {
                return context[0].label;
              },
              label: function(context) {
                const value = context.parsed.x;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                return value + ' deals (' + percentage + '%)';
              }
            },
            boxPadding: 6
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: {
                size: 13,
                weight: '600'
              },
              color: '#1e293b',
              padding: 12
            },
            grid: {
              display: false,
              drawBorder: false,
              lineWidth: 1
            },
            border: {
              display: false
            }
          },
          y: {
            ticks: {
              font: { 
                size: 13,
                weight: '600'
              },
              color: '#1e293b',
              padding: 12
            },
            grid: {
              display: false
            },
            border: {
              display: false
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'y'
        },
        onHover: (event, activeElements) => {
          event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
      },
      plugins: [dataLabelsPlugin]
    });
    
    // Value Chart - Enhanced Doughnut with gradients and animations
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    
    window_valueChart = new Chart(valueCtx, {
      type: 'doughnut',
      data: {
        labels: ['Won', 'Lost', 'Active Pipeline'],
        datasets: [{
          data: [{$won_value}, {$lost_value}, {$active_value}],
          backgroundColor: function(context) {
            const chart = context.chart;
            const {ctx, chartArea} = chart;
            if (!chartArea) return colors.green;
            
            const colorPairs = [
              [colors.green, colors.greenLight],      // Won
              [colors.red, colors.redLight],          // Lost
              [colors.blue, colors.blueLight]         // Active
            ];
            
            const index = context.dataIndex;
            const pair = colorPairs[index];
            
            const gradient = ctx.createLinearGradient(
              chartArea.left, 
              chartArea.top, 
              chartArea.right, 
              chartArea.bottom
            );
            gradient.addColorStop(0, pair[0]);
            gradient.addColorStop(1, pair[1]);
            return gradient;
          },
          borderWidth: 4,
          borderColor: '#ffffff',
          hoverBorderWidth: 6,
          hoverBorderColor: '#ffffff',
          hoverOffset: 12
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        animation: {
          animateRotate: true,
          animateScale: true,
          duration: 1400,
          easing: 'easeInOutQuart'
        },
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              padding: 20,
              font: {
                size: 13,
                weight: '600',
                family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'
              },
              color: '#1e293b',
              usePointStyle: true,
              pointStyle: 'circle',
              boxWidth: 8,
              boxHeight: 8,
              generateLabels: function(chart) {
                const data = chart.data;
                return data.labels.map((label, i) => {
                  const value = data.datasets[0].data[i];
                  const total = data.datasets[0].data.reduce((a, b) => a + b, 0);
                  const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                  
                  return {
                    text: label + ' (' + percentage + '%)',
                    fillStyle: i === 0 ? colors.green : i === 1 ? colors.red : colors.blue,
                    hidden: false,
                    index: i
                  };
                });
              }
            }
          },
          tooltip: {
            backgroundColor: 'rgba(30, 41, 59, 0.95)',
            padding: 16,
            titleFont: { 
              size: 14,
              weight: '600'
            },
            bodyFont: { 
              size: 13
            },
            cornerRadius: 10,
            displayColors: true,
            boxWidth: 12,
            boxHeight: 12,
            boxPadding: 8,
            usePointStyle: true,
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed;
                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                const formatted = new Intl.NumberFormat('en-US', {
                  style: 'currency',
                  currency: 'USD',
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 1,
                  notation: 'compact',
                  compactDisplay: 'short'
                }).format(value);
                
                return label + ': ' + formatted + ' (' + percentage + '%)';
              }
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'point'
        },
        onHover: (event, activeElements) => {
          event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
      }
    });
  </script>
HTML;

    // Return render array with Drupal toolbar support
    // IMPORTANT: Disable page cache to ensure real-time data updates
    // when admins or staff edit CRM entities
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => [
          'node_list:contact',
          'node_list:organization',
          'node_list:deal',
          'node_list:activity',
        ],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Trigger dashboard refresh when deals are updated.
   * This method is called by event hooks in other modules.
   * 
   * @param array $context
   *   Context data with 'entity' and 'operation' keys.
   */
  public static function onDealUpdate(array $context) {
    // This hook is called when a deal node is created/updated/deleted
    // Can be hooked from hook_node_insert, hook_node_update, hook_node_delete
    \Drupal::moduleHandler()->invokeAll('crm_dashboard_deal_updated', [$context]);
  }

  /**
   * Trigger dashboard refresh when activities are created.
   * 
   * @param array $context
   *   Context data with 'entity' and 'operation' keys.
   */
  public static function onActivityCreate(array $context) {
    // This hook is called when an activity node is created
    // Used by JavaScript to trigger real-time updates
    \Drupal::moduleHandler()->invokeAll('crm_dashboard_activity_created', [$context]);
  }

  /**
   * Get real-time dashboard data via AJAX endpoint.
   * Can be registered as a route for AJAX updates.
   * Returns all dashboard metrics for real-time display updates.
   * 
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with updated dashboard metrics.
   */
  public function getRefreshData() {
    try {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager = in_array('sales_manager', $current_user->getRoles());
    $can_manage = $is_admin || $is_manager;
    
    // Get timestamps for calculations (same as main view)
    $now = \Drupal::time()->getCurrentTime();
    $dow = (int) date('N', $now);
    $this_week_start = mktime(0, 0, 0, (int) date('n', $now), (int) date('j', $now) - ($dow - 1));
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);
    $week_end = $now + 604800;

    // Resolve Won/Lost taxonomy term IDs (field_stage is an entity reference).
    $stage_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['vid' => 'pipeline_stage']);
    $won_term_id = null; $lost_term_id = null;
    foreach ($stage_terms as $term) {
      $n = strtolower($term->getName());
      if ($n === 'won')  $won_term_id  = $term->id();
      if ($n === 'lost') $lost_term_id = $term->id();
    }
    
    // === FETCH ALL DASHBOARD METRICS IN REAL-TIME ===
    
    // 1. Contacts
    $contacts_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $contacts_query->condition('field_owner', $user_id);
    }
    $contacts_count = $contacts_query->count()->execute();
    
    // 2. Organizations
    $orgs_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $orgs_query->condition('field_assigned_staff', $user_id);
    }
    $orgs_count = $orgs_query->count()->execute();
    
    // 3. Total Deals
    $deals_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $deals_query->condition('field_owner', $user_id);
    }
    $deals_count = $deals_query->count()->execute();
    
    // 4. Activities
    $activities_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $activities_query->condition('field_assigned_to', $user_id);
    }
    $activities_count = $activities_query->count()->execute();

    $contacts_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('created', $this_week_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $contacts_this_week_query->condition('field_owner', $user_id);
    }
    $contacts_this_week = $contacts_this_week_query->count()->execute();

    $orgs_this_month_query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('created', $month_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $orgs_this_month_query->condition('field_assigned_staff', $user_id);
    }
    $orgs_this_month = $orgs_this_month_query->count()->execute();

    $activities_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('created', $this_week_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $activities_this_week_query->condition('field_assigned_to', $user_id);
    }
    $activities_this_week = $activities_this_week_query->count()->execute();

    $stage_distribution = [];
    foreach ($stage_terms as $term) {
      $stage_query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $term->id())
        ->accessCheck(TRUE);
      if (!$can_manage && $user_id > 0) {
        $stage_query->condition('field_owner', $user_id);
      }
      $stage_distribution[$term->getName()] = (int) $stage_query->count()->execute();
    }
    
      // 5. Won Deals
      $won_count = 0;
      if (!empty($won_term_id)) {
        $won_query = \Drupal::entityQuery('node')
          ->condition('type', 'deal')
          ->condition('field_stage', $won_term_id)
          ->accessCheck(TRUE);
        if (!$can_manage && $user_id > 0) {
          $won_query->condition('field_owner', $user_id);
        }
        $won_count = $won_query->count()->execute();
      }
      
      // 6. Lost Deals
      $lost_count = 0;
      if (!empty($lost_term_id)) {
        $lost_query = \Drupal::entityQuery('node')
          ->condition('type', 'deal')
          ->condition('field_stage', $lost_term_id)
          ->accessCheck(TRUE);
        if (!$can_manage && $user_id > 0) {
          $lost_query->condition('field_owner', $user_id);
        }
        $lost_count = $lost_query->count()->execute();
      }
    
    // 7. Overdue Activities
    $overdue_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('field_datetime', date('Y-m-d\\TH:i:s', $now), '<=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $overdue_query->condition('field_assigned_to', $user_id);
    }
    $overdue_activities = $overdue_query->count()->execute();
    
    // 8. Deals Due This Week
    $closed_tids = array_filter([$won_term_id, $lost_term_id]);
    $due_this_week_query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_closing_date', date('Y-m-d', $now), '>=')
      ->condition('field_closing_date', date('Y-m-d', $week_end), '<=')
      ->accessCheck(TRUE);
    if (!empty($closed_tids)) {
      $due_this_week_query->condition('field_stage', $closed_tids, 'NOT IN');
    }
    if (!$can_manage && $user_id > 0) {
      $due_this_week_query->condition('field_owner', $user_id);
    }
    $due_this_week = $due_this_week_query->count()->execute();
    
    // 9. New Contacts This Month
    $new_contacts_query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('created', $month_start, '>=')
      ->accessCheck(TRUE);
    if (!$can_manage && $user_id > 0) {
      $new_contacts_query->condition('field_owner', $user_id);
    }
    $new_contacts = $new_contacts_query->count()->execute();
    
    // 10. Revenue This Week (deals with amounts)
    $revenue_this_week_ids = [];
    $revenue_this_week = 0;
    $revenue_this_week_count = 0;
    if (!empty($won_term_id)) {
      $revenue_this_week_query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $won_term_id)
        ->condition('changed', $this_week_start, '>=')
        ->accessCheck(TRUE);
      if (!$can_manage && $user_id > 0) {
        $revenue_this_week_query->condition('field_owner', $user_id);
      }
      $revenue_this_week_ids = $revenue_this_week_query->execute();
    }
    if (!empty($revenue_this_week_ids)) {
      $revenue_deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($revenue_this_week_ids);
      foreach ($revenue_deals as $deal) {
        if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
          $revenue_this_week += floatval($deal->get('field_amount')->value);
          $revenue_this_week_count++;
        }
      }
    }
    
    // 11. Deal Values — single DB aggregate query, no entity loading.
    $won_id2  = (int) ($won_term_id  ?? 0);
    $lost_id2 = (int) ($lost_term_id ?? 0);
    $has_deleted_at_table = \Drupal::database()->schema()->tableExists('node__field_deleted_at');
    $agg2 = \Drupal::database()->select('node_field_data', 'n');
    $agg2->leftJoin('node__field_amount', 'fa', 'fa.entity_id = n.nid AND fa.deleted = 0');
    $agg2->leftJoin('node__field_stage',  'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $agg2->condition('n.type', 'deal');
    if ($has_deleted_at_table) {
      $agg2->leftJoin('node__field_deleted_at', 'fd2', 'fd2.entity_id = n.nid AND fd2.deleted = 0');
      $agg2->isNull('fd2.field_deleted_at_value');
    }
    $agg2->addExpression('COALESCE(SUM(fa.field_amount_value), 0)', 'total_value');
    $agg2->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $won_id2  THEN fa.field_amount_value ELSE 0 END), 0)", 'won_value');
    $agg2->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $lost_id2 THEN fa.field_amount_value ELSE 0 END), 0)", 'lost_value');
    $agg2->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id NOT IN ($won_id2, $lost_id2) OR fs.field_stage_target_id IS NULL THEN fa.field_amount_value ELSE 0 END), 0)", 'active_value');
    // Average days in pipeline for won deals
    $agg2->addExpression("AVG(CASE WHEN fs.field_stage_target_id = $won_id2 THEN (UNIX_TIMESTAMP() - n.created) / 86400.0 END)", 'avg_days_won');
    if (!$can_manage && $user_id > 0) {
      $agg2->leftJoin('node__field_owner', 'fo2', 'fo2.entity_id = n.nid AND fo2.deleted = 0');
      $agg2->condition('fo2.field_owner_target_id', $user_id);
    }
    $totals2            = $agg2->execute()->fetchObject();
    $total_value        = (float) ($totals2->total_value  ?? 0);
    $won_value          = (float) ($totals2->won_value    ?? 0);
    $lost_value         = (float) ($totals2->lost_value   ?? 0);
    $active_value       = (float) ($totals2->active_value ?? 0);
    $avg_days_in_pipeline = (int) round((float) ($totals2->avg_days_won ?? 0));
    
    // 12. Calculate KPIs
    $total_closed = $won_count + $lost_count;
    $win_rate = $total_closed > 0 ? round(($won_count / $total_closed) * 100, 1) : 0;
    $conversion_rate = $deals_count > 0 ? round(($won_count / $deals_count) * 100, 1) : 0;
    $avg_deal_size = $won_count > 0 ? round($won_value / $won_count, 0) : 0;
    
    // 13. Get recent activities
    $recent_activities_query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 10);
    if (!$can_manage && $user_id > 0) {
      $recent_activities_query->condition('field_assigned_to', $user_id);
    }
    $recent_activity_count = $recent_activities_query->count()->execute();
    
      // Return all metrics in real-time
      return new JsonResponse([
        'success' => TRUE,
        'timestamp' => $now,
        'message' => 'Dashboard data refreshed in real-time',
        'is_admin' => $is_admin,
        'stage_distribution' => $stage_distribution,
        'metrics' => [
          // Original 10 metrics
          'contacts' => $contacts_count,
          'contacts_this_week' => $contacts_this_week,
          'organizations' => $orgs_count,
          'orgs_this_month' => $orgs_this_month,
          'deals' => $deals_count,
          'activities' => $activities_count,
          'activities_this_week' => $activities_this_week,
          'won' => $won_count,
          'lost' => $lost_count,
          'activities_recent' => $recent_activity_count,
          'total_value' => round($total_value, 2),
          'won_value' => round($won_value, 2),
          'lost_value' => round($lost_value, 2),
          'active_value' => round($active_value, 2),
          'total_value_display' => '$' . number_format($total_value / 1000000, 1) . 'M',
          'won_value_display' => '$' . number_format($won_value / 1000000, 1) . 'M',
          'lost_value_display' => '$' . number_format($lost_value / 1000000, 1) . 'M',
          'active_value_display' => '$' . number_format($active_value / 1000000, 1) . 'M',
          'win_rate' => $win_rate,
          'conversion_rate' => $conversion_rate,
          'avg_deal' => $avg_deal_size,
          'avg_deal_display' => '$' . number_format($avg_deal_size / 1000, 0) . 'K',
          
          // New 6 enhanced metrics
          'overdue_activities' => $overdue_activities,
          'revenue_this_week' => round($revenue_this_week, 2),
          'revenue_this_week_display' => '$' . number_format($revenue_this_week / 1000000, 1) . 'M',
          'revenue_this_week_count' => $revenue_this_week_count,
          'due_this_week' => $due_this_week,
          'avg_days_in_pipeline' => $avg_days_in_pipeline,
          'new_contacts_this_month' => $new_contacts,
        ],
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('crm_dashboard')->error('Dashboard refresh failed: @message', [
        '@message' => $e->getMessage(),
      ]);

      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Dashboard refresh is temporarily unavailable.',
        'metrics' => new \stdClass(),
      ], 200);
    }
  }

}
