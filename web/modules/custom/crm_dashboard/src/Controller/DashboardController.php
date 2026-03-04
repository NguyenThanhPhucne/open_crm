<?php

namespace Drupal\crm_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * Controller for CRM Dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * Display the dashboard.
   */
  public function view() {
    // Get current user to filter data
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Get dashboard metrics (filtered by user ownership)
    $contacts_count = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $orgs_count = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('field_assigned_staff', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $deals_count = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $activities_count = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('field_assigned_to', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Load pipeline stages dynamically from taxonomy.
    $stage_terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'pipeline_stage']);

    $stages = [];
    $stage_colors = [];
    $deals_by_stage = [];

    // Dynamic color palette (cycles through colors based on stage order).
    $color_palette = [
      '#60a5fa', '#34d399', '#fbbf24', '#f472b6', '#10b981', '#ef4444',
      '#06b6d4', '#84cc16', '#f97316', '#a855f7', '#14b8a6', '#f43f5e',
    ];
    $color_index = 0;

    foreach ($stage_terms as $term) {
      $stage_id = $term->id();
      $stage_name = $term->getName();
      $stages[$stage_id] = $stage_name;
      $stage_colors[$stage_id] = $color_palette[$color_index % count($color_palette)];
      $color_index++;

      // Count deals in this stage (filtered by current user).
      $count = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage_id)
        ->condition('field_owner', $user_id)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      $deals_by_stage[$stage_id] = $count;
    }

    // Get total deal value and won/lost deals (filtered by current user)
    // NOTE: loadByProperties() does NOT work with entity reference fields like field_owner!
    // Must use entityQuery instead.
    $deal_ids = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->execute();
    
    $deals = !empty($deal_ids) ? \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids) : [];
    
    $total_value = 0;
    $won_value = 0;
    $lost_value = 0;
    $won_count = 0;
    $lost_count = 0;

    foreach ($deals as $deal) {
      $amount = 0;
      if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
        $amount = floatval($deal->get('field_amount')->value);
        $total_value += $amount;
      }
      
      if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
        $stage = $deal->get('field_stage')->value;
        if ($stage === 'closed_won') {
          $won_value += $amount;
          $won_count++;
        } elseif ($stage === 'closed_lost') {
          $lost_value += $amount;
          $lost_count++;
        }
      }
    }

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
    
    // Get recent activities (last 10, filtered by current user)
    $activity_ids = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('field_assigned_to', $user_id)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();
    
    $recent_activities = [];
    if (!empty($activity_ids)) {
      $activities = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($activity_ids);
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
        
        $type_colors = [
          'call' => '#3b82f6',
          'meeting' => '#8b5cf6',
          'email' => '#10b981',
          'note' => '#f59e0b',
          'task' => '#ec4899',
        ];
        
        $contact_name = '';
        if ($activity->hasField('field_contact') && !$activity->get('field_contact')->isEmpty()) {
          $contact = $activity->get('field_contact')->entity;
          if ($contact) {
            $contact_name = $contact->getTitle();
          }
        }
        
        $recent_activities[] = [
          'title' => $activity->getTitle(),
          'type' => ucfirst($type_value ?? 'note'),
          'icon' => $type_icons[$type_value] ?? 'activity',
          'color' => $type_colors[$type_value] ?? '#64748b',
          'contact' => $contact_name,
          'created' => \Drupal::service('date.formatter')->format($activity->getCreatedTime(), 'custom', 'd/m H:i'),
        ];
      }
    }
    
    // Get recent deals (last 8, filtered by current user)
    $deal_ids = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 8)
      ->execute();
    
    $recent_deals = [];
    if (!empty($deal_ids)) {
      $deals_list = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($deal_ids);
      foreach ($deals_list as $deal) {
        $amount = 0;
        if ($deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty()) {
          $amount = floatval($deal->get('field_amount')->value);
        }
        
        $stage = 'new';
        $stage_label = 'New';
        if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
          $stage = $deal->get('field_stage')->value ?? 'new';
          $stage_label = ucfirst(str_replace('_', ' ', $stage ?? 'new'));
        }
        
        $stage_colors_deals = [
          'new' => '#dbeafe',
          'qualified' => '#e9d5ff',
          'proposal' => '#fed7aa',
          'negotiation' => '#fce7f3',
          'closed_won' => '#d1fae5',
          'closed_lost' => '#fee2e2',
        ];
        
        $contact_name = '';
        if ($deal->hasField('field_contact') && !$deal->get('field_contact')->isEmpty()) {
          $contact = $deal->get('field_contact')->entity;
          if ($contact) {
            $contact_name = $contact->getTitle();
          }
        }
        
        $recent_deals[] = [
          'id' => $deal->id(),
          'title' => $deal->getTitle(),
          'amount' => '$' . number_format($amount / 1000, 0) . 'K',
          'stage' => $stage_label,
          'stage_color' => $stage_colors_deals[$stage] ?? '#f1f5f9',
          'contact' => $contact_name,
        ];
      }
    }
    
    // Build JSON data for charts
    $stage_labels_json = json_encode(array_values($stages));
    $stage_data_json = json_encode(array_values($deals_by_stage));
    $stage_colors_json = json_encode(array_values($stage_colors));

    // Build HTML with professional design
    $html = <<<HTML
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #f8fafc;
      min-height: 100vh;
      padding: 20px;
      color: #1e293b;
    }
    
    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
      animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }
    
    .main-content {
      display: grid;
      grid-template-columns: 1fr 380px;
      gap: 24px;
      margin-bottom: 32px;
    }
    
    .left-column {
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    
    .stat-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
      text-decoration: none;
      color: inherit;
      display: block;
    }
    
    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #3b82f6, #8b5cf6);
      opacity: 0;
      transition: opacity 0.2s;
    }
    
    .stat-card:hover {
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transform: translateY(-2px);
      border-color: #cbd5e1;
    }
    
    .stat-card:active {
      transform: translateY(0);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card:hover::before {
      opacity: 1;
    }
    
    .stat-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }
    
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .stat-icon i {
      width: 24px;
      height: 24px;
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
    
    .stat-icon.red { 
      background: #fef2f2;
      color: #ef4444;
    }
    
    .stat-label {
      font-size: 12px;
      color: #64748b;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    .stat-value {
      font-size: 32px;
      font-weight: 700;
      color: #0f172a;
      line-height: 1;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }
    
    .stat-desc {
      font-size: 13px;
      color: #94a3b8;
      font-weight: 400;
    }
    
    .charts-section {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }
    
    .section-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      padding-bottom: 16px;
      border-bottom: 1px solid #f1f5f9;
    }
    
    .section-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .section-title i {
      color: #3b82f6;
    }
    
    .view-all-link {
      font-size: 13px;
      color: #3b82f6;
      text-decoration: none;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 4px;
      transition: color 0.2s;
    }
    
    .view-all-link:hover {
      color: #2563eb;
    }
    
    /* Activity Items */
    .activity-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      max-height: 500px;
      overflow-y: auto;
    }
    
    .activity-item {
      display: flex;
      gap: 12px;
      padding: 12px;
      border-radius: 8px;
      transition: background 0.2s;
    }
    
    .activity-item:hover {
      background: #f8fafc;
    }
    
    .activity-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .activity-icon.call { background: #eff6ff; }
    .activity-icon.meeting { background: #f5f3ff; }
    .activity-icon.email { background: #ecfdf5; }
    .activity-icon.note { background: #fffbeb; }
    
    .activity-content {
      flex: 1;
      min-width: 0;
    }
    
    .activity-title {
      font-size: 14px;
      font-weight: 500;
      color: #1e293b;
      margin-bottom: 4px;
      display: -webkit-box;
      -webkit-line-clamp: 1;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .activity-meta {
      font-size: 12px;
      color: #64748b;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .activity-contact {
      color: #3b82f6;
    }
    
    .activity-time {
      font-size: 11px;
      color: #94a3b8;
      flex-shrink: 0;
    }
    
    /* Deal Items */
    .deal-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    
    .deal-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 8px;
      border: 1px solid #f1f5f9;
      transition: all 0.2s;
    }
    
    .deal-item:hover {
      border-color: #e2e8f0;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    
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
      border-radius: 12px;
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
    }
    
    .empty-state-text {
      font-size: 14px;
      color: #64748b;
    }
    
    .chart-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
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
      transition: opacity 0.3s;
    }
    
    .chart-card:hover {
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
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
      height: 320px;
      padding: 10px 0;
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
    
    @media (max-width: 768px) {
      .dashboard-header h1 {
        font-size: 24px;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .main-content {
        grid-template-columns: 1fr;
      }
      
      .charts-section {
        grid-template-columns: 1fr;
      }
    }
  </style>
  <div class="dashboard-container">
    <!-- Statistics Cards -->
    <div class="stats-grid">
      <a href="/crm/my-contacts" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon blue">
            <i data-lucide="users" width="24" height="24"></i>
          </div>
          <div class="stat-label">Contacts</div>
        </div>
        <div class="stat-value">{$contacts_count}</div>
        <div class="stat-desc">Active contacts</div>
      </a>
      
      <a href="/admin/content?type=organization" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon purple">
            <i data-lucide="building-2" width="24" height="24"></i>
          </div>
          <div class="stat-label">Organizations</div>
        </div>
        <div class="stat-value">{$orgs_count}</div>
        <div class="stat-desc">Companies</div>
      </a>
      
      <a href="/crm/my-deals" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon orange">
            <i data-lucide="briefcase" width="24" height="24"></i>
          </div>
          <div class="stat-label">Deals</div>
        </div>
        <div class="stat-value">{$deals_count}</div>
        <div class="stat-desc">In pipeline</div>
      </a>
      
      <a href="/crm/my-deals" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon green">
            <i data-lucide="dollar-sign" width="24" height="24"></i>
          </div>
          <div class="stat-label">Total Value</div>
        </div>
        <div class="stat-value">{$total_value_display}</div>
        <div class="stat-desc">Deal value</div>
      </a>
      
      <a href="/crm/pipeline" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon green">
            <i data-lucide="trending-up" width="24" height="24"></i>
          </div>
          <div class="stat-label">Won</div>
        </div>
        <div class="stat-value">{$won_count}</div>
        <div class="stat-desc">{$won_value_display} revenue</div>
      </a>
      
      <a href="/crm/pipeline" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon red">
            <i data-lucide="trending-down" width="24" height="24"></i>
          </div>
          <div class="stat-label">Lost</div>
        </div>
        <div class="stat-value">{$lost_count}</div>
        <div class="stat-desc">{$lost_value_display} lost</div>
      </a>
      
      <a href="/crm/pipeline" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon pink">
            <i data-lucide="target" width="24" height="24"></i>
          </div>
          <div class="stat-label">Win Rate</div>
        </div>
        <div class="stat-value">{$win_rate}%</div>
        <div class="stat-desc">Deals won rate</div>
      </a>
      
      <a href="/crm/pipeline" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon blue">
            <i data-lucide="percent" width="24" height="24"></i>
          </div>
          <div class="stat-label">Conversion</div>
        </div>
        <div class="stat-value">{$conversion_rate}%</div>
        <div class="stat-desc">Overall conversion</div>
      </a>
      
      <a href="/crm/my-deals" class="stat-card">
        <div class="stat-header">
          <div class="stat-icon purple">
            <i data-lucide="bar-chart-3" width="24" height="24"></i>
          </div>
          <div class="stat-label">Avg Deal</div>
        </div>
        <div class="stat-value">{$avg_deal_display}</div>
        <div class="stat-desc">Average value</div>
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
              <canvas id="stageChart"></canvas>
            </div>
          </div>
          
          <div class="chart-card">
            <div class="chart-header">
              <div class="chart-title">Deal Value Overview</div>
              <div class="chart-subtitle">Won vs Lost vs Active</div>
            </div>
            <div class="chart-container">
              <canvas id="valueChart"></canvas>
            </div>
          </div>
        </div>
        
        <!-- Recent Deals -->
        <div class="section-card">
          <div class="section-header">
            <div class="section-title">
              <i data-lucide="briefcase" width="20" height="20"></i>
              Recent Deals
            </div>
            <a href="/crm/pipeline" class="view-all-link">
              View all
              <i data-lucide="arrow-right" width="14" height="14"></i>
            </a>
          </div>
          <div class="deal-list">
HTML;

    // Add recent deals
    if (!empty($recent_deals)) {
      foreach ($recent_deals as $deal) {
        $html .= <<<DEAL
            <div class="deal-item">
              <div class="deal-info">
                <div class="deal-title">{$deal['title']}</div>
                <div class="deal-contact">{$deal['contact']}</div>
              </div>
              <div class="deal-amount">{$deal['amount']}</div>
              <span class="deal-stage" style="background: {$deal['stage_color']}; color: #0f172a;">{$deal['stage']}</span>
            </div>
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
      <div class="section-card">
        <div class="section-header">
          <div class="section-title">
            <i data-lucide="activity" width="20" height="20"></i>
            Recent Activities
          </div>
          <a href="/crm/my-activities" class="view-all-link">
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
        $contact_html = '';
        if ($activity['contact']) {
          $contact_html = '• <span class="activity-contact">' . $activity['contact'] . '</span>';
        }
        
        $html .= <<<ACTIVITY
          <div class="activity-item">
            <div class="activity-icon {$type_class}">
              <i data-lucide="{$activity['icon']}" width="18" height="18" style="color: {$activity['color']}"></i>
            </div>
            <div class="activity-content">
              <div class="activity-title">{$activity['title']}</div>
              <div class="activity-meta">
                <span class="activity-type">{$activity['type']}</span>
                {$contact_html}
              </div>
            </div>
            <div class="activity-time">{$activity['created']}</div>
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
  </div>
  
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Modern color palette with gradients
    const colors = {
      blue: '#3b82f6',
      blueLight: '#60a5fa',
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
    
    const stageChart = new Chart(stageCtx, {
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
              [colors.blue, colors.blueLight],
              [colors.green, colors.greenLight],
              [colors.yellow, colors.yellowLight],
              [colors.pink, colors.pinkLight],
              [colors.emerald, colors.emeraldLight],
              [colors.red, colors.redLight]
            ];
            
            const index = context.dataIndex;
            const pair = colorPairs[index % colorPairs.length];
            return createGradient(ctx, pair[0], pair[1], chartArea.right);
          },
          borderRadius: 8,
          borderSkipped: false,
          barThickness: 32,
          hoverBackgroundColor: function(context) {
            const chart = context.chart;
            const {ctx, chartArea} = chart;
            if (!chartArea) return colors.blue;
            
            const colorPairs = [
              [colors.blueLight, colors.blue],
              [colors.greenLight, colors.green],
              [colors.yellowLight, colors.yellow],
              [colors.pinkLight, colors.pink],
              [colors.emeraldLight, colors.emerald],
              [colors.redLight, colors.red]
            ];
            
            const index = context.dataIndex;
            const pair = colorPairs[index % colorPairs.length];
            return createGradient(ctx, pair[0], pair[1], chartArea.right);
          }
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
                size: 12,
                weight: '500'
              },
              color: colors.slate,
              padding: 8
            },
            grid: {
              color: 'rgba(241, 245, 249, 0.8)',
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
          mode: 'index'
        },
        onHover: (event, activeElements) => {
          event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
        }
      },
      plugins: [dataLabelsPlugin]
    });
    
    // Value Chart - Enhanced Doughnut with gradients and animations
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    
    const valueChart = new Chart(valueCtx, {
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
    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
    ];
  }

}
