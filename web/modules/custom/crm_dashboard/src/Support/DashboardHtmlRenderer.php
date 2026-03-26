<?php

namespace Drupal\crm_dashboard\Support;

final class DashboardHtmlRenderer {

  /**
   * Render dashboard HTML with section item renderer callback.
   */
  public static function render(array $view, callable $renderRecentSectionItems) { // NOSONAR
    extract($view, EXTR_SKIP);
    $html = <<<HTML
  <script src="https://unpkg.com/lucide@latest"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

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
          <span>New Activity</span>
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
          <div class="stat-value">{$avg_days_in_pipeline}<span class="stat-unit-days"> days</span></div>
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

    $html .= $renderRecentSectionItems('deals', $recent_deals);

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

    $html .= $renderRecentSectionItems('activities', $recent_activities);

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
    $html .= $renderRecentSectionItems('contacts', $recent_contacts);
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
    $html .= $renderRecentSectionItems('organizations', $recent_organizations);
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
    $html .= $renderRecentSectionItems('pipeline', $recent_pipeline);
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
        this.setCardValue('Avg Cycle', Number(metrics.avg_days_in_pipeline || 0) + '<span class="stat-unit-days"> days</span>', true);

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

    return $html;
  }

}
