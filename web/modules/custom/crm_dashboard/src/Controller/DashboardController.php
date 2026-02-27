<?php

namespace Drupal\crm_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for CRM Dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * Display the dashboard.
   */
  public function view() {
    // Get dashboard metrics
    $contacts_count = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $orgs_count = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $deals_count = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $activities_count = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    // Get deals by stage
    $stages = [
      'prospecting' => 'Prospecting',
      'qualification' => 'Qualification',
      'proposal' => 'Proposal',
      'negotiation' => 'Negotiation',
      'closed_won' => 'Closed Won',
      'closed_lost' => 'Closed Lost'
    ];

    $deals_by_stage = [];
    $stage_colors = [
      'prospecting' => '#60a5fa',
      'qualification' => '#34d399',
      'proposal' => '#fbbf24',
      'negotiation' => '#f472b6',
      'closed_won' => '#10b981',
      'closed_lost' => '#ef4444'
    ];

    foreach (array_keys($stages) as $stage) {
      $count = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('field_stage', $stage)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      $deals_by_stage[$stage] = $count;
    }

    // Get total deal value and won/lost deals
    $deals = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'deal']);
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
    
    // Build JSON data for charts
    $stage_labels_json = json_encode(array_values($stages));
    $stage_data_json = json_encode(array_values($deals_by_stage));
    $stage_colors_json = json_encode(array_values($stage_colors));

    // Build HTML with professional design
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM Dashboard</title>
  <link rel="icon" type="image/x-icon" href="/core/misc/favicon.ico">
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
      padding: 32px 20px;
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
    
    .dashboard-header {
      margin-bottom: 32px;
      padding-bottom: 20px;
      border-bottom: 1px solid #e2e8f0;
    }
    
    .dashboard-header h1 {
      font-size: 28px;
      font-weight: 600;
      color: #1e293b;
      margin: 0 0 6px 0;
      letter-spacing: -0.02em;
    }
    
    .dashboard-header p {
      color: #64748b;
      font-size: 14px;
      margin: 0;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
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
    
    .chart-card {
      background: white;
      border-radius: 12px;
      padding: 24px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }
    
    .chart-header {
      margin-bottom: 20px;
    }
    
    .chart-title {
      font-size: 18px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }
    
    .chart-subtitle {
      font-size: 13px;
      color: #64748b;
    }
    
    .chart-container {
      position: relative;
      height: 280px;
    }
    
    .action-bar {
      display: flex;
      justify-content: center;
      gap: 12px;
      margin-top: 32px;
    }
    
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 20px;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 500;
      font-size: 14px;
      transition: all 0.15s ease;
      border: 1px solid #e2e8f0;
      cursor: pointer;
    }
    
    .btn-primary {
      background: white;
      color: #3b82f6;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    .btn-primary:hover {
      background: #eff6ff;
      border-color: #3b82f6;
      box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2);
      transform: translateY(-1px);
    }
    
    .btn-primary:active {
      transform: translateY(0);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 768px) {
      .dashboard-header h1 {
        font-size: 24px;
      }
      
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
      
      .charts-section {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>CRM Dashboard</h1>
      <p>Overview of your sales performance and activities</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon blue">
            <i data-lucide="users" width="24" height="24"></i>
          </div>
          <div class="stat-label">Contacts</div>
        </div>
        <div class="stat-value">{$contacts_count}</div>
        <div class="stat-desc">Active contacts</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon purple">
            <i data-lucide="building-2" width="24" height="24"></i>
          </div>
          <div class="stat-label">Organizations</div>
        </div>
        <div class="stat-value">{$orgs_count}</div>
        <div class="stat-desc">Companies</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon orange">
            <i data-lucide="briefcase" width="24" height="24"></i>
          </div>
          <div class="stat-label">Deals</div>
        </div>
        <div class="stat-value">{$deals_count}</div>
        <div class="stat-desc">In pipeline</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon green">
            <i data-lucide="dollar-sign" width="24" height="24"></i>
          </div>
          <div class="stat-label">Total Value</div>
        </div>
        <div class="stat-value">{$total_value_display}</div>
        <div class="stat-desc">Deal value</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon green">
            <i data-lucide="trending-up" width="24" height="24"></i>
          </div>
          <div class="stat-label">Won</div>
        </div>
        <div class="stat-value">{$won_count}</div>
        <div class="stat-desc">{$won_value_display} revenue</div>
      </div>
      
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon red">
            <i data-lucide="trending-down" width="24" height="24"></i>
          </div>
          <div class="stat-label">Lost</div>
        </div>
        <div class="stat-value">{$lost_count}</div>
        <div class="stat-desc">{$lost_value_display} lost</div>
      </div>
    </div>
    
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
    
    <div class="action-bar">
      <a href="/" class="btn btn-primary">
        <i data-lucide="arrow-left" width="18" height="18"></i>
        Back to Home
      </a>
      <a href="/crm/my-contacts" class="btn btn-primary">
        <i data-lucide="users" width="18" height="18"></i>
        View Contacts
      </a>
      <a href="/crm/my-pipeline" class="btn btn-primary">
        <i data-lucide="briefcase" width="18" height="18"></i>
        View Pipeline
      </a>
    </div>
  </div>
  
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Modern color palette
    const colors = {
      blue: '#3b82f6',
      green: '#10b981',
      yellow: '#f59e0b',
      pink: '#ec4899',
      emerald: '#10b981',
      red: '#ef4444',
      slate: '#64748b'
    };
    
    // Stage Chart - Horizontal Bar
    const stageCtx = document.getElementById('stageChart').getContext('2d');
    new Chart(stageCtx, {
      type: 'bar',
      data: {
        labels: {$stage_labels_json},
        datasets: [{
          label: 'Deals',
          data: {$stage_data_json},
          backgroundColor: [
            colors.blue,
            colors.green,
            colors.yellow,
            colors.pink,
            colors.emerald,
            colors.red
          ],
          borderRadius: 6,
          borderSkipped: false,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            titleFont: { size: 13 },
            bodyFont: { size: 14, weight: 'bold' },
            cornerRadius: 8
          }
        },
        scales: {
          x: {
            beginAtZero: true,
            ticks: {
              stepSize: 1,
              font: { size: 11 },
              color: colors.slate
            },
            grid: {
              color: '#f1f5f9',
              drawBorder: false
            }
          },
          y: {
            ticks: {
              font: { size: 12 },
              color: '#1e293b'
            },
            grid: {
              display: false
            }
          }
        }
      }
    });
    
    // Value Chart - Doughnut
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    new Chart(valueCtx, {
      type: 'doughnut',
      data: {
        labels: ['Won', 'Lost', 'Active Pipeline'],
        datasets: [{
          data: [{$won_value}, {$lost_value}, {$active_value}],
          backgroundColor: [colors.green, colors.red, colors.blue],
          borderWidth: 0,
          hoverOffset: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: { size: 12 },
              color: '#1e293b',
              usePointStyle: true,
              pointStyle: 'circle'
            }
          },
          tooltip: {
            backgroundColor: '#1e293b',
            padding: 12,
            titleFont: { size: 13 },
            bodyFont: { size: 14, weight: 'bold' },
            cornerRadius: 8,
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                let value = context.parsed || 0;
                return label + ': $' + (value / 1000000).toFixed(1) + 'M';
              }
            }
          }
        }
      }
    });
  </script>
</body>
</html>
HTML;

    // Return raw HTML response without Drupal theme
    $response = new Response($html);
    return $response;
  }

}
