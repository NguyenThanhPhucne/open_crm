<?php

namespace Drupal\crm_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Html;
use Drupal\crm_dashboard\Support\DashboardHtmlRenderer;
use Drupal\crm_dashboard\Support\DashboardRecentDataAssembler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for CRM Dashboard.
 */
class DashboardController extends ControllerBase {

  protected const OPERATOR_NOT_IN = 'NOT IN';
  protected const RELATIVE_TIME_JUST_NOW = 'just now';
  protected const RELATIVE_TIME_MINUTE_SUFFIX = 'm ago';
  protected const RELATIVE_TIME_HOUR_SUFFIX = 'h ago';
  protected const RELATIVE_TIME_DAY_SUFFIX = 'd ago';
  protected const RELATIVE_TIME_WEEK_SUFFIX = 'w ago';
  protected const NODE_PATH_PREFIX = '/node/';

  /**
   * Access check for dashboard pages.
   *
   * Dashboard is accessible to all authenticated users.
   */
  public function accessView(AccountInterface $account) {
    return $account->isAuthenticated() ? AccessResult::allowed() : AccessResult::forbidden();
  }

  /**
   * Display the dashboard.
   */
  public function view() {
    $html = $this->buildDashboardLegacyHtml();

    // IMPORTANT: Disable page cache to ensure real-time data updates
    // when admins or staff edit CRM entities.
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'crm_dashboard/dashboard',
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
   * Build dashboard HTML using existing legacy implementation.
   */
  protected function buildDashboardLegacyHtml() {
    $context = $this->buildRefreshContext();
    $can_manage = (bool) $context['can_manage'];
    $user_id = (int) $context['user_id'];
    $now = (int) $context['now'];

    $base_counts = $this->collectRefreshBaseCounts($context);
    $stage_distribution = $this->collectRefreshStageDistribution($context);
    $won_lost_counts = $this->collectRefreshWonLostCounts($context);
    $enhanced_metrics = $this->collectRefreshEnhancedMetrics($context);
    $value_totals = $this->collectRefreshDealValueTotals($context);
    $kpis = $this->calculateRefreshKpis($won_lost_counts, $base_counts, $value_totals);

    $closed_term_ids = array_filter([$context['won_term_id'], $context['lost_term_id']]);
    $recent = DashboardRecentDataAssembler::collect_all($can_manage, $user_id, $now, $closed_term_ids);

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

    $current_user = \Drupal::currentUser();
    $user_display_name = $current_user->getDisplayName() ?: 'there';
    $greeting_hour = (int) date('H', $now);
    if ($greeting_hour < 12) {
      $greeting = 'Good morning';
    }
    elseif ($greeting_hour < 18) {
      $greeting = 'Good afternoon';
    }
    else {
      $greeting = 'Good evening';
    }

    $view_data = [
      'greeting' => $greeting,
      'user_display_name' => $user_display_name,
      'today_display' => date('l, F j, Y', $now),
      'contacts_url' => $contacts_url,
      'organizations_url' => $organizations_url,
      'deals_url' => $deals_url,
      'activities_url' => $activities_url,
      'pipeline_url' => $pipeline_url,
      'contacts_count' => $base_counts['contacts'],
      'contacts_this_week' => $base_counts['contacts_this_week'],
      'orgs_count' => $base_counts['organizations'],
      'orgs_this_month' => $base_counts['orgs_this_month'],
      'deals_count' => $base_counts['deals'],
      'activities_count' => $base_counts['activities'],
      'activities_this_week' => $base_counts['activities_this_week'],
      'total_value_display' => '$' . number_format($value_totals['total_value'] / 1000000, 1) . 'M',
      'won_value_display' => '$' . number_format($value_totals['won_value'] / 1000000, 1) . 'M',
      'lost_value_display' => '$' . number_format($value_totals['lost_value'] / 1000000, 1) . 'M',
      'won_count' => $won_lost_counts['won'],
      'lost_count' => $won_lost_counts['lost'],
      'win_rate' => $kpis['win_rate'],
      'avg_deal_display' => '$' . number_format($kpis['avg_deal'] / 1000, 0) . 'K',
      'conversion_rate' => $kpis['conversion_rate'],
      'overdue_activities' => $enhanced_metrics['overdue_activities'],
      'active_value_display' => '$' . number_format($value_totals['active_value'] / 1000000, 1) . 'M',
      'revenue_this_week_display' => '$' . number_format($enhanced_metrics['revenue_this_week'] / 1000000, 1) . 'M',
      'revenue_this_week_count' => $enhanced_metrics['revenue_this_week_count'],
      'due_this_week' => $enhanced_metrics['due_this_week'],
      'avg_days_in_pipeline' => $value_totals['avg_days_in_pipeline'],
      'new_contacts' => $enhanced_metrics['new_contacts_this_month'],
      'stage_labels_json' => json_encode(array_keys($stage_distribution)),
      'stage_data_json' => json_encode(array_values($stage_distribution)),
      'won_value' => $value_totals['won_value'],
      'lost_value' => $value_totals['lost_value'],
      'active_value' => $value_totals['active_value'],
      'recent_deals' => $recent['deals'],
      'recent_activities' => $recent['activities'],
      'recent_contacts' => $recent['contacts'],
      'recent_organizations' => $recent['organizations'],
      'recent_pipeline' => $recent['pipeline'],
    ];

    return DashboardHtmlRenderer::render(
      $view_data,
      fn($section, array $items) => $this->renderRecentSectionItems($section, $items)
    );
  }

  /**
   * Render one recent-data block by section key.
   */
  protected function renderRecentSectionItems($section, array $items) {
    $html = '';

    $empty_map = [
      'deals' => '<div class="empty-state"><i data-lucide="inbox"></i><div class="empty-state-text">No deals yet</div></div>',
      'activities' => '<div class="empty-state"><i data-lucide="inbox"></i><div class="empty-state-text">No activities yet</div></div>',
      'contacts' => '<div class="empty-state"><i data-lucide="user-x"></i><div class="empty-state-text">No contacts yet</div></div>',
      'organizations' => '<div class="empty-state"><i data-lucide="building"></i><div class="empty-state-text">No organizations yet</div></div>',
      'pipeline' => '<div class="empty-state"><i data-lucide="inbox"></i><div class="empty-state-text">No active pipeline deals</div></div>',
    ];

    $renderers = [
      'deals' => function (array $deal) {
        $deal_url = self::NODE_PATH_PREFIX . $deal['id'];
        return '<a href="' . $deal_url . '" class="deal-item">'
          . '<div class="deal-info"><div class="deal-title">' . $deal['title'] . '</div>'
          . '<div class="deal-contact">' . $deal['contact'] . '</div>'
          . '<div class="deal-updated"><span class="freshness-dot freshness-' . $deal['freshness'] . '"></span>' . $deal['relative_time'] . '</div></div>'
          . '<div class="deal-right"><div class="deal-amount">' . $deal['amount'] . '</div>'
          . '<span class="deal-stage ' . $deal['stage_class'] . '">' . $deal['stage'] . '</span></div></a>';
      },
      'activities' => function (array $activity) {
        $type_class = strtolower($activity['type']);
        $meta_string = implode(' • ', array_filter([$activity['owner'], $activity['relative_time']]));
        return '<div class="activity-item"><div class="activity-icon ' . $type_class . '">'
          . '<i data-lucide="' . $activity['icon'] . '" width="18" height="18"></i></div>'
          . '<div class="activity-content"><a href="' . $activity['url'] . '" class="activity-title" title="' . $activity['title'] . '">'
          . $activity['title'] . '</a><div class="activity-meta">' . $meta_string . '</div></div></div>';
      },
      'contacts' => function (array $rc) {
        $rc_url = self::NODE_PATH_PREFIX . $rc['id'];
        $rc_sub = $rc['org'] ?: ($rc['source'] ?: '');
        return '<a href="' . $rc_url . '" class="recent-item">'
          . '<div class="recent-avatar blue">' . $rc['initials'] . '</div>'
          . '<div class="recent-info"><div class="recent-name">' . $rc['title'] . '</div><div class="recent-sub">' . $rc_sub . '</div></div>'
          . '<span class="recent-time">' . $rc['relative_time'] . '</span></a>';
      },
      'organizations' => function (array $ro) {
        $ro_url = self::NODE_PATH_PREFIX . $ro['id'];
        $ro_sub = $ro['industry'] ?: ($ro['phone'] ?: '');
        return '<a href="' . $ro_url . '" class="recent-item">'
          . '<div class="recent-avatar pink">' . $ro['initials'] . '</div>'
          . '<div class="recent-info"><div class="recent-name">' . $ro['title'] . '</div><div class="recent-sub">' . $ro_sub . '</div></div>'
          . '<span class="recent-time">' . $ro['relative_time'] . '</span></a>';
      },
      'pipeline' => function (array $rp) {
        $rp_url = self::NODE_PATH_PREFIX . $rp['id'];
        return '<a href="' . $rp_url . '" class="recent-item">'
          . '<div class="recent-avatar recent-avatar--pipeline ' . $rp['stage_class'] . '"><i data-lucide="circle-dot" width="16" height="16"></i></div>'
          . '<div class="recent-info"><div class="recent-name">' . $rp['title'] . '</div>'
          . '<div class="recent-sub"><span class="pipeline-stage-mini ' . $rp['stage_class'] . '">' . $rp['stage'] . '</span>&nbsp;' . $rp['amount'] . '</div></div>'
          . '<span class="recent-time">' . $rp['relative_time'] . '</span></a>';
      },
    ];

    if (empty($items)) {
      $html = $empty_map[$section] ?? '';
    }
    elseif (isset($renderers[$section])) {
      foreach ($items as $item) {
        $html .= $renderers[$section]($item);
      }
    }

    return $html;
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
      $context = $this->buildRefreshContext();
      $base_counts = $this->collectRefreshBaseCounts($context);
      $stage_distribution = $this->collectRefreshStageDistribution($context);
      $won_lost_counts = $this->collectRefreshWonLostCounts($context);
      $enhanced_metrics = $this->collectRefreshEnhancedMetrics($context);
      $value_totals = $this->collectRefreshDealValueTotals($context);
      $kpis = $this->calculateRefreshKpis($won_lost_counts, $base_counts, $value_totals);
      $recent_activity_count = $this->countRecentActivitiesForRefresh($context);

      return new JsonResponse([
        'success' => TRUE,
        'timestamp' => $context['now'],
        'message' => 'Dashboard data refreshed in real-time',
        'is_admin' => $context['is_admin'],
        'stage_distribution' => $stage_distribution,
        'metrics' => [
          'contacts' => $base_counts['contacts'],
          'contacts_this_week' => $base_counts['contacts_this_week'],
          'organizations' => $base_counts['organizations'],
          'orgs_this_month' => $base_counts['orgs_this_month'],
          'deals' => $base_counts['deals'],
          'activities' => $base_counts['activities'],
          'activities_this_week' => $base_counts['activities_this_week'],
          'won' => $won_lost_counts['won'],
          'lost' => $won_lost_counts['lost'],
          'activities_recent' => $recent_activity_count,
          'total_value' => round($value_totals['total_value'], 2),
          'won_value' => round($value_totals['won_value'], 2),
          'lost_value' => round($value_totals['lost_value'], 2),
          'active_value' => round($value_totals['active_value'], 2),
          'total_value_display' => '$' . number_format($value_totals['total_value'] / 1000000, 1) . 'M',
          'won_value_display' => '$' . number_format($value_totals['won_value'] / 1000000, 1) . 'M',
          'lost_value_display' => '$' . number_format($value_totals['lost_value'] / 1000000, 1) . 'M',
          'active_value_display' => '$' . number_format($value_totals['active_value'] / 1000000, 1) . 'M',
          'win_rate' => $kpis['win_rate'],
          'conversion_rate' => $kpis['conversion_rate'],
          'avg_deal' => $kpis['avg_deal'],
          'avg_deal_display' => '$' . number_format($kpis['avg_deal'] / 1000, 0) . 'K',
          'overdue_activities' => $enhanced_metrics['overdue_activities'],
          'revenue_this_week' => round($enhanced_metrics['revenue_this_week'], 2),
          'revenue_this_week_display' => '$' . number_format($enhanced_metrics['revenue_this_week'] / 1000000, 1) . 'M',
          'revenue_this_week_count' => $enhanced_metrics['revenue_this_week_count'],
          'due_this_week' => $enhanced_metrics['due_this_week'],
          'avg_days_in_pipeline' => $value_totals['avg_days_in_pipeline'],
          'new_contacts_this_month' => $enhanced_metrics['new_contacts_this_month'],
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

  /**
   * Build common refresh context used by dashboard metric helpers.
   */
  protected function buildRefreshContext() {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $is_admin = in_array('administrator', $current_user->getRoles()) || $user_id == 1;
    $is_manager = in_array('sales_manager', $current_user->getRoles());
    $can_manage = $is_admin || $is_manager;

    $now = \Drupal::time()->getCurrentTime();
    $dow = (int) date('N', $now);
    $this_week_start = mktime(0, 0, 0, (int) date('n', $now), (int) date('j', $now) - ($dow - 1));
    $month_start = mktime(0, 0, 0, (int) date('n', $now), 1);
    $week_end = $now + 604800;

    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'pipeline_stage']);

    $won_term_id = NULL;
    $lost_term_id = NULL;
    foreach ($stage_terms as $term) {
      $n = strtolower($term->getName());
      if ($n === 'won') {
        $won_term_id = $term->id();
      }
      if ($n === 'lost') {
        $lost_term_id = $term->id();
      }
    }

    return [
      'user_id' => $user_id,
      'is_admin' => $is_admin,
      'can_manage' => $can_manage,
      'now' => $now,
      'this_week_start' => $this_week_start,
      'month_start' => $month_start,
      'week_end' => $week_end,
      'stage_terms' => $stage_terms,
      'won_term_id' => $won_term_id,
      'lost_term_id' => $lost_term_id,
    ];
  }

  /**
   * Count scoped entities for one bundle with optional extra conditions.
   */
  protected function countScopedBundle(array $context, $bundle, array $conditions = []) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->accessCheck(TRUE);

    $this->applyNotArchivedCondition($query, $bundle);

    foreach ($conditions as $condition) {
      $field = $condition['field'];
      $value = $condition['value'];
      $operator = $condition['operator'] ?? '=';
      $query->condition($field, $value, $operator);
    }

    if (!$context['can_manage'] && $context['user_id'] > 0) {
      $query->condition($this->getOwnerFieldForBundle($bundle), $context['user_id']);
    }

    return (int) $query->count()->execute();
  }

  /**
   * Return ownership field by bundle for non-admin scoping.
   */
  protected function getOwnerFieldForBundle($bundle) {
    $owner_fields = [
      'contact' => 'field_owner',
      'deal' => 'field_owner',
      'organization' => 'field_assigned_staff',
      'activity' => 'field_assigned_to',
    ];

    return $owner_fields[$bundle] ?? 'field_owner';
  }

  /**
   * Collect base dashboard counts for refresh payload.
   */
  protected function collectRefreshBaseCounts(array $context) {
    return [
      'contacts' => $this->countScopedBundle($context, 'contact'),
      'organizations' => $this->countScopedBundle($context, 'organization'),
      'deals' => $this->countScopedBundle($context, 'deal'),
      'activities' => $this->countScopedBundle($context, 'activity'),
      'contacts_this_week' => $this->countScopedBundle($context, 'contact', [
        ['field' => 'created', 'value' => $context['this_week_start'], 'operator' => '>='],
      ]),
      'orgs_this_month' => $this->countScopedBundle($context, 'organization', [
        ['field' => 'created', 'value' => $context['month_start'], 'operator' => '>='],
      ]),
      'activities_this_week' => $this->countScopedBundle($context, 'activity', [
        ['field' => 'created', 'value' => $context['this_week_start'], 'operator' => '>='],
      ]),
    ];
  }

  /**
   * Collect count per stage name for stage distribution chart.
   */
  protected function collectRefreshStageDistribution(array $context) {
    $distribution = [];

    foreach ($context['stage_terms'] as $term) {
      $distribution[$term->getName()] = $this->countScopedBundle($context, 'deal', [
        ['field' => 'field_stage', 'value' => $term->id()],
      ]);
    }

    return $distribution;
  }

  /**
   * Collect won/lost deal counts for refresh payload.
   */
  protected function collectRefreshWonLostCounts(array $context) {
    $won = 0;
    $lost = 0;

    if (!empty($context['won_term_id'])) {
      $won = $this->countScopedBundle($context, 'deal', [
        ['field' => 'field_stage', 'value' => $context['won_term_id']],
      ]);
    }

    if (!empty($context['lost_term_id'])) {
      $lost = $this->countScopedBundle($context, 'deal', [
        ['field' => 'field_stage', 'value' => $context['lost_term_id']],
      ]);
    }

    return [
      'won' => $won,
      'lost' => $lost,
    ];
  }

  /**
   * Collect overdue, due-this-week, new-contacts and weekly revenue metrics.
   */
  protected function collectRefreshEnhancedMetrics(array $context) {
    $overdue_activities = $this->countScopedBundle($context, 'activity', [
      ['field' => 'field_datetime', 'value' => date('Y-m-d\\TH:i:s', $context['now']), 'operator' => '<='],
    ]);

    $closed_tids = array_filter([$context['won_term_id'], $context['lost_term_id']]);
    $due_conditions = [
      ['field' => 'field_closing_date', 'value' => date('Y-m-d', $context['now']), 'operator' => '>='],
      ['field' => 'field_closing_date', 'value' => date('Y-m-d', $context['week_end']), 'operator' => '<='],
    ];
    if (!empty($closed_tids)) {
      $due_conditions[] = ['field' => 'field_stage', 'value' => $closed_tids, 'operator' => self::OPERATOR_NOT_IN];
    }
    $due_this_week = $this->countScopedBundle($context, 'deal', $due_conditions);

    $new_contacts_this_month = $this->countScopedBundle($context, 'contact', [
      ['field' => 'created', 'value' => $context['month_start'], 'operator' => '>='],
    ]);

    $revenue = $this->collectRefreshRevenueThisWeek($context);

    return [
      'overdue_activities' => $overdue_activities,
      'due_this_week' => $due_this_week,
      'new_contacts_this_month' => $new_contacts_this_month,
      'revenue_this_week' => $revenue['value'],
      'revenue_this_week_count' => $revenue['count'],
    ];
  }

  /**
   * Collect weekly revenue from won deals changed during current week.
   */
  protected function collectRefreshRevenueThisWeek(array $context) {
    $value = 0.0;
    $count = 0;
    $deal_ids = [];

    if (!empty($context['won_term_id'])) {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $context['won_term_id'])
        ->condition('changed', $context['this_week_start'], '>=')
        ->accessCheck(TRUE);
      $this->applyNotArchivedCondition($query, 'deal');
      if (!$context['can_manage'] && $context['user_id'] > 0) {
        $query->condition('field_owner', $context['user_id']);
      }
      $deal_ids = $query->execute();
    }

    if (!empty($deal_ids)) {
      $deals = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids);
      foreach ($deals as $deal) {
        if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
          $value += (float) $deal->get('field_amount')->value;
          $count++;
        }
      }
    }

    return [
      'value' => $value,
      'count' => $count,
    ];
  }

  /**
   * Collect aggregate deal value totals and average days in pipeline.
   */
  protected function collectRefreshDealValueTotals(array $context) {
    $won_id = (int) ($context['won_term_id'] ?? 0);
    $lost_id = (int) ($context['lost_term_id'] ?? 0);

    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->leftJoin('node__field_amount', 'fa', 'fa.entity_id = n.nid AND fa.deleted = 0');
    $query->leftJoin('node__field_stage', 'fs', 'fs.entity_id = n.nid AND fs.deleted = 0');
    $query->condition('n.type', 'deal');

    if (\Drupal::database()->schema()->tableExists('node__field_deleted_at')) {
      $query->leftJoin('node__field_deleted_at', 'fd2', 'fd2.entity_id = n.nid AND fd2.deleted = 0');
      $query->isNull('fd2.field_deleted_at_value');
    }

    $query->addExpression('COALESCE(SUM(fa.field_amount_value), 0)', 'total_value');
    $query->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $won_id THEN fa.field_amount_value ELSE 0 END), 0)", 'won_value');
    $query->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id = $lost_id THEN fa.field_amount_value ELSE 0 END), 0)", 'lost_value');
    $query->addExpression("COALESCE(SUM(CASE WHEN fs.field_stage_target_id NOT IN ($won_id, $lost_id) OR fs.field_stage_target_id IS NULL THEN fa.field_amount_value ELSE 0 END), 0)", 'active_value');
    $query->addExpression("AVG(CASE WHEN fs.field_stage_target_id = $won_id THEN (UNIX_TIMESTAMP() - n.created) / 86400.0 END)", 'avg_days_won');

    if (!$context['can_manage'] && $context['user_id'] > 0) {
      $query->leftJoin('node__field_owner', 'fo2', 'fo2.entity_id = n.nid AND fo2.deleted = 0');
      $query->condition('fo2.field_owner_target_id', $context['user_id']);
    }

    $totals = $query->execute()->fetchObject();

    return [
      'total_value' => (float) ($totals->total_value ?? 0),
      'won_value' => (float) ($totals->won_value ?? 0),
      'lost_value' => (float) ($totals->lost_value ?? 0),
      'active_value' => (float) ($totals->active_value ?? 0),
      'avg_days_in_pipeline' => (int) round((float) ($totals->avg_days_won ?? 0)),
    ];
  }

  /**
   * Calculate KPI values from base counts and value totals.
   */
  protected function calculateRefreshKpis(array $won_lost_counts, array $base_counts, array $value_totals) {
    $won = (int) $won_lost_counts['won'];
    $lost = (int) $won_lost_counts['lost'];
    $deals = (int) $base_counts['deals'];
    $won_value = (float) $value_totals['won_value'];

    $total_closed = $won + $lost;
    $win_rate = $total_closed > 0 ? round(($won / $total_closed) * 100, 1) : 0;
    $conversion_rate = $deals > 0 ? round(($won / $deals) * 100, 1) : 0;
    $avg_deal = $won > 0 ? round($won_value / $won, 0) : 0;

    return [
      'win_rate' => $win_rate,
      'conversion_rate' => $conversion_rate,
      'avg_deal' => $avg_deal,
    ];
  }

  /**
   * Count recent activities using same query constraints as current dashboard.
   */
  protected function countRecentActivitiesForRefresh(array $context) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range(0, 10);

    $this->applyNotArchivedCondition($query, 'activity');
    if (!$context['can_manage'] && $context['user_id'] > 0) {
      $query->condition('field_assigned_to', $context['user_id']);
    }

    return (int) $query->count()->execute();
  }

  /**
   * Exclude soft-deleted records from node queries when bundle supports it.
   */
  protected function applyNotArchivedCondition($query, $bundle) {
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
