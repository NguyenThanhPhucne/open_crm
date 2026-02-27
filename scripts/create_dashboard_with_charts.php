<?php

/**
 * Create enhanced dashboard with KPI cards and charts
 * Run with: ddev drush scr scripts/create_dashboard_with_charts.php
 */

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

// Build the HTML dashboard
$stage_labels_json = json_encode(array_values($stages));
$stage_data_json = json_encode(array_values($deals_by_stage));
$stage_colors_json = json_encode(array_values($stage_colors));

$html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM Dashboard</title>
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      padding: 40px 20px;
    }
    
    .dashboard-container {
      max-width: 1400px;
      margin: 0 auto;
    }
    
    .dashboard-header {
      text-align: center;
      color: white;
      margin-bottom: 40px;
    }
    
    .dashboard-header h1 {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 10px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .dashboard-header p {
      font-size: 1.2rem;
      opacity: 0.9;
    }
    
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .kpi-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .kpi-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 12px rgba(0,0,0,0.15);
    }
    
    .kpi-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    
    .kpi-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
    }
    
    .kpi-icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .kpi-icon.green { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
    .kpi-icon.purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    .kpi-icon.orange { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
    .kpi-icon.pink { background: linear-gradient(135deg, #ec4899 0%, #db2777 100%); }
    .kpi-icon.red { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
    
    .kpi-title {
      font-size: 0.9rem;
      color: #6b7280;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .kpi-value {
      font-size: 2.5rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 8px;
    }
    
    .kpi-subtitle {
      font-size: 0.9rem;
      color: #9ca3af;
    }
    
    .charts-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
      gap: 20px;
      margin-bottom: 40px;
    }
    
    .chart-card {
      background: white;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    .chart-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 20px;
    }
    
    .chart-container {
      position: relative;
      height: 300px;
    }
    
    .back-link {
      text-align: center;
      margin-top: 40px;
    }
    
    .back-link a {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 24px;
      background: white;
      color: #667eea;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 500;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }
    
    .back-link a:hover {
      background: #667eea;
      color: white;
      box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    @media (max-width: 768px) {
      .dashboard-header h1 {
        font-size: 2rem;
      }
      
      .kpi-grid {
        grid-template-columns: 1fr;
      }
      
      .charts-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="dashboard-container">
    <div class="dashboard-header">
      <h1>📊 CRM Dashboard</h1>
      <p>Real-time insights and analytics</p>
    </div>
    
    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon blue">
            <i data-lucide="users" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Total Contacts</div>
          </div>
        </div>
        <div class="kpi-value">{$contacts_count}</div>
        <div class="kpi-subtitle">Active contacts in system</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon purple">
            <i data-lucide="building-2" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Organizations</div>
          </div>
        </div>
        <div class="kpi-value">{$orgs_count}</div>
        <div class="kpi-subtitle">Companies in database</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon orange">
            <i data-lucide="briefcase" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Total Deals</div>
          </div>
        </div>
        <div class="kpi-value">{$deals_count}</div>
        <div class="kpi-subtitle">All pipeline opportunities</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon green">
            <i data-lucide="dollar-sign" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Total Value</div>
          </div>
        </div>
        <div class="kpi-value">\$" . number_format($total_value, 0) . "</div>
        <div class="kpi-subtitle">Combined deal value</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon green">
            <i data-lucide="trending-up" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Deals Won</div>
          </div>
        </div>
        <div class="kpi-value">{$won_count}</div>
        <div class="kpi-subtitle">\$" . number_format($won_value, 0) . " in revenue</div>
      </div>
      
      <div class="kpi-card">
        <div class="kpi-header">
          <div class="kpi-icon red">
            <i data-lucide="trending-down" width="24" height="24"></i>
          </div>
          <div>
            <div class="kpi-title">Deals Lost</div>
          </div>
        </div>
        <div class="kpi-value">{$lost_count}</div>
        <div class="kpi-subtitle">\$" . number_format($lost_value, 0) . " lost opportunity</div>
      </div>
    </div>
    
    <!-- Charts -->
    <div class="charts-grid">
      <div class="chart-card">
        <div class="chart-title">Deals by Pipeline Stage</div>
        <div class="chart-container">
          <canvas id="stageChart"></canvas>
        </div>
      </div>
      
      <div class="chart-card">
        <div class="chart-title">Deal Value Distribution</div>
        <div class="chart-container">
          <canvas id="valueChart"></canvas>
        </div>
      </div>
    </div>
    
    <div class="back-link">
      <a href="/">
        <i data-lucide="arrow-left" width="20" height="20"></i>
        Back to Quick Access
      </a>
    </div>
  </div>
  
  <script>
    // Initialize Lucide icons
    lucide.createIcons();
    
    // Stage Chart
    const stageCtx = document.getElementById('stageChart').getContext('2d');
    new Chart(stageCtx, {
      type: 'bar',
      data: {
        labels: {$stage_labels_json},
        datasets: [{
          label: 'Number of Deals',
          data: {$stage_data_json},
          backgroundColor: {$stage_colors_json},
          borderRadius: 8,
          borderSkipped: false,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          }
        }
      }
    });
    
    // Value Chart - Pie chart showing won vs lost vs active
    const valueCtx = document.getElementById('valueChart').getContext('2d');
    new Chart(valueCtx, {
      type: 'doughnut',
      data: {
        labels: ['Deals Won', 'Deals Lost', 'Active Pipeline'],
        datasets: [{
          data: [{$won_value}, {$lost_value}, " . ($total_value - $won_value - $lost_value) . "],
          backgroundColor: ['#10b981', '#ef4444', '#60a5fa'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  </script>
</body>
</html>
HTML;

// Update the sales_dashboard view to show this content
$view = \Drupal\views\Entity\View::load('sales_dashboard');
if ($view) {
  $displays = $view->get('display');
  
  // Add the dashboard HTML to the header
  if (!isset($displays['page_1']['display_options']['header'])) {
    $displays['page_1']['display_options']['header'] = [];
  }
  
  $displays['page_1']['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => $html,
      'format' => 'full_html',
    ],
  ];
  
  // Hide the view results since we just want the dashboard
  $displays['page_1']['display_options']['empty']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'empty' => TRUE,
    'content' => [
      'value' => '',
      'format' => 'full_html',
    ],
  ];
  
  $view->set('display', $displays);
  $view->save();
  echo "✅ Updated sales_dashboard view with charts\n";
}

echo "\n📊 Dashboard Statistics:\n";
echo "  Contacts: {$contacts_count}\n";
echo "  Organizations: {$orgs_count}\n";
echo "  Deals: {$deals_count}\n";
echo "  Activities: {$activities_count}\n";
echo "  Total Value: \$" . number_format($total_value, 2) . "\n";
echo "  Won: {$won_count} deals (\$" . number_format($won_value, 2) . ")\n";
echo "  Lost: {$lost_count} deals (\$" . number_format($lost_value, 2) . ")\n";
echo "\n✅ Dashboard with charts created successfully!\n";
echo "Visit: /crm/dashboard\n";
