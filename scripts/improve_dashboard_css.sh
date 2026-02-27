#!/bin/bash

echo "🎨 CẢI THIỆN CSS DASHBOARD - PHONG CÁCH TAILWIND/STRIPE/VERCEL"
echo "================================================================"
echo ""

echo "📊 Đang cập nhật Dashboard với CSS chuyên nghiệp..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  \$node->set('body', [
    'value' => '
<style>
/* Reset & Base */
.crm-dashboard {
  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;
  max-width: 1400px;
  margin: 0 auto;
  padding: 32px 24px;
  background: #fafbfc;
}

/* Dashboard Header */
.dashboard-header {
  margin-bottom: 40px;
}

.dashboard-header h1 {
  color: #0f172a;
  font-size: 32px;
  font-weight: 700;
  margin: 0 0 8px 0;
  letter-spacing: -0.5px;
}

.dashboard-header p {
  color: #64748b;
  font-size: 16px;
  margin: 0;
  font-weight: 400;
}

/* Stats Grid - Tailwind Style */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  margin-bottom: 40px;
}

.stat-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 24px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border-color: #cbd5e1;
}

.stat-label {
  color: #64748b;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 12px;
  display: block;
}

.stat-value {
  color: #0f172a;
  font-size: 36px;
  font-weight: 700;
  margin: 0 0 8px 0;
  line-height: 1;
  letter-spacing: -1px;
}

.stat-change {
  font-size: 14px;
  font-weight: 500;
  color: #10b981;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.stat-change.negative {
  color: #ef4444;
}

.stat-change::before {
  content: \"↗\";
  font-size: 16px;
}

.stat-change.negative::before {
  content: \"↘\";
}

/* Quick Actions - Stripe/Vercel Style */
.quick-actions {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 32px;
  margin-bottom: 40px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.quick-actions h2 {
  color: #0f172a;
  font-size: 20px;
  font-weight: 600;
  margin: 0 0 24px 0;
  letter-spacing: -0.3px;
}

.action-buttons {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
}

/* Primary Button - Stripe Blue */
.action-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 12px 20px;
  background: #0066cc;
  color: #ffffff;
  text-decoration: none;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  letter-spacing: 0.2px;
  transition: all 0.2s ease;
  cursor: pointer;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.action-btn:hover {
  background: #0052a3;
  color: #ffffff;
  transform: translateY(-1px);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.action-btn:active {
  transform: translateY(0);
}

/* Secondary Button - Vercel Style */
.action-btn.secondary {
  background: #ffffff;
  color: #0f172a;
  border: 1px solid #e5e7eb;
}

.action-btn.secondary:hover {
  background: #f9fafb;
  color: #0f172a;
  border-color: #d1d5db;
}

/* Navigation Cards */
.nav-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 24px;
}

.nav-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 28px;
  text-decoration: none;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  display: block;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.nav-card:hover {
  border-color: #0066cc;
  box-shadow: 0 10px 15px -3px rgba(0, 102, 204, 0.1), 0 4px 6px -2px rgba(0, 102, 204, 0.05);
  transform: translateY(-2px);
}

.nav-card h3 {
  color: #0066cc;
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 12px 0;
  letter-spacing: -0.3px;
}

.nav-card p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  line-height: 1.6;
}

/* Responsive */
@media (max-width: 768px) {
  .crm-dashboard {
    padding: 20px 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .action-buttons {
    grid-template-columns: 1fr;
  }
  
  .nav-cards {
    grid-template-columns: 1fr;
  }
}
</style>

<div class=\"crm-dashboard\">
  <div class=\"dashboard-header\">
    <h1>Sales Dashboard</h1>
    <p>Welcome back! Here is your performance overview.</p>
  </div>

  <div class=\"stats-grid\">
    <div class=\"stat-card\">
      <span class=\"stat-label\">Total Pipeline</span>
      <div class=\"stat-value\">\$525,000</div>
      <span class=\"stat-change\">12% from last month</span>
    </div>
    
    <div class=\"stat-card\">
      <span class=\"stat-label\">Active Deals</span>
      <div class=\"stat-value\">6</div>
      <span class=\"stat-change\">2 new this week</span>
    </div>
    
    <div class=\"stat-card\">
      <span class=\"stat-label\">Total Contacts</span>
      <div class=\"stat-value\">6</div>
      <span class=\"stat-change\">15% growth rate</span>
    </div>
    
    <div class=\"stat-card\">
      <span class=\"stat-label\">Win Rate</span>
      <div class=\"stat-value\">68%</div>
      <span class=\"stat-change\">5% improvement</span>
    </div>
  </div>

  <div class=\"quick-actions\">
    <h2>Quick Actions</h2>
    <div class=\"action-buttons\">
      <a href=\"/node/add/contact\" class=\"action-btn\">+ Add Contact</a>
      <a href=\"/node/add/deal\" class=\"action-btn\">+ Create Deal</a>
      <a href=\"/node/add/activity\" class=\"action-btn secondary\">Log Activity</a>
      <a href=\"/node/add/organization\" class=\"action-btn secondary\">Add Organization</a>
    </div>
  </div>

  <div class=\"nav-cards\">
    <a href=\"/crm/my-contacts\" class=\"nav-card\">
      <h3>My Contacts</h3>
      <p>View and manage your contact list. Track interactions and maintain relationships with your customers.</p>
    </a>
    
    <a href=\"/crm/my-pipeline\" class=\"nav-card\">
      <h3>Sales Pipeline</h3>
      <p>Monitor your deals across different stages. Track progress from prospecting to closing.</p>
    </a>
    
    <a href=\"/crm/my-activities\" class=\"nav-card\">
      <h3>My Activities</h3>
      <p>Review your recent activities and upcoming tasks. Stay on top of your sales workflow.</p>
    </a>
    
    <a href=\"/crm/my-organizations\" class=\"nav-card\">
      <h3>Organizations</h3>
      <p>Manage company accounts. View all contacts and deals associated with each organization.</p>
    </a>
  </div>
</div>
',
    'format' => 'full_html',
  ]);
  \$node->save();
  echo '✅ Dashboard đã được cập nhật với CSS Tailwind/Stripe/Vercel style' . PHP_EOL;
}
"

echo ""
echo "✨ HOÀN THÀNH! Dashboard CSS đã được nâng cấp."
echo ""
echo "🎨 CÁC CẢI TIẾN:"
echo ""
echo "   1. ✅ STAT CARDS (Tailwind Style):"
echo "      - Box-shadow mềm mại (shadow-md)"
echo "      - Hover: translateY(-4px) + shadow nổi"
echo "      - Label: uppercase, letter-spacing 0.8px"
echo "      - Value: font-weight 700, letter-spacing -1px"
echo "      - Border-radius: 12px, màu #e2e8f0"
echo ""
echo "   2. ✅ QUICK ACTION BUTTONS:"
echo "      - Primary: Blue #0066cc (Stripe style)"
echo "      - Secondary: White với border #e5e7eb (Vercel style)"
echo "      - Hover: darker + translateY(-1px) + shadow"
echo "      - Border-radius: 6px, padding 12px 20px"
echo ""
echo "   3. ✅ NAVIGATION CARDS:"
echo "      - Hover: border-color blue + shadow blue"
echo "      - Transform: translateY(-2px)"
echo "      - Transition: cubic-bezier mượt mà"
echo ""
echo "📊 KIỂM TRA:"
echo "   URL: http://open-crm.ddev.site"
echo "   Đăng nhập: salesrep1 / sales123"
echo "   Hover chuột vào các cards để xem hiệu ứng!"
