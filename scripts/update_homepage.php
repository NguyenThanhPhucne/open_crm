<?php

/**
 * Update node/146 (homepage "Quick Access - CRM") with ClickUp-style design.
 * Run: ddev drush php-script scripts/update_homepage.php
 */

$new_body = <<<'HTML'
<script src="https://unpkg.com/lucide@latest"></script>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

.qac-wrapper {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  max-width: 1320px;
  margin: 0 auto;
  padding: 28px 24px 64px;
  background: #ffffff;
  animation: qacFadeIn 0.3s ease;
}

@keyframes qacFadeIn {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ── HERO ─────────────────────────────────────────────── */
.qac-hero {
  background: linear-gradient(135deg, #ffffff 0%, #f0f5ff 60%, #f5f0ff 100%);
  border-radius: 16px;
  border: 1px solid #dde8ff;
  border-top: 3px solid #2563eb;
  padding: 36px 40px;
  margin-bottom: 36px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  overflow: hidden;
  box-shadow: 0 2px 12px rgba(37, 99, 235, 0.07);
}

.qac-hero::before {
  content: '';
  position: absolute;
  top: -30%;
  right: -3%;
  width: 340px;
  height: 340px;
  background: radial-gradient(circle, rgba(99,102,241,0.06) 0%, transparent 70%);
  pointer-events: none;
}

.qac-hero::after {
  display: none;
}

.qac-hero-text {
  position: relative;
  z-index: 1;
}

.qac-hero-text h1 {
  font-size: 26px;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 8px;
  letter-spacing: -0.02em;
}

.qac-hero-text p {
  font-size: 14px;
  color: #64748b;
  font-weight: 500;
}

.qac-hero-actions {
  display: flex;
  gap: 10px;
  flex-shrink: 0;
  flex-wrap: wrap;
  position: relative;
  z-index: 1;
}

.qac-btn {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 18px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  transition: all 0.18s ease;
  font-family: inherit;
  border: 1.5px solid transparent;
  cursor: pointer;
}

.qac-btn i { width: 15px; height: 15px; stroke-width: 2; }

.qac-btn-white {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
}

.qac-btn-white:hover {
  background: #1d4ed8;
  border-color: #1d4ed8;
  color: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(37,99,235,0.35);
}

.qac-btn-outline {
  background: white;
  color: #2563eb;
  border-color: #dbeafe;
}

.qac-btn-outline:hover {
  background: #eff6ff;
  border-color: #93c5fd;
  color: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(37,99,235,0.12);
}

.qac-btn-login {
  background: #2563eb;
  color: white;
  border-color: #2563eb;
  font-size: 14px;
  padding: 12px 28px;
}

.qac-btn-login:hover {
  background: #1d4ed8;
  color: white;
  border-color: #1d4ed8;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(37,99,235,0.35);
}

/* ── SECTION LABEL ────────────────────────────────────── */
.qac-section-label {
  font-size: 10px;
  font-weight: 700;
  color: #b0b8c8;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.qac-section-label::after {
  content: '';
  flex: 1;
  height: 1px;
  background: #eaedf2;
}

/* ── CARDS GRID ───────────────────────────────────────── */
.qac-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
  margin-bottom: 32px;
}

.qac-grid-3 {
  grid-template-columns: repeat(3, 1fr);
}

/* ── CARD ─────────────────────────────────────────────── */
.qac-card {
  background: white;
  border-radius: 10px;
  padding: 22px 20px 18px;
  border: 1px solid #e9edf2;
  border-left: 3px solid var(--cc, #e2e8f0);
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.qac-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 22px rgba(0,0,0,0.09);
  background: #ffffff;
}

.qac-card-top {
  display: flex;
  align-items: center;
  gap: 13px;
}

.qac-card-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  background: var(--ci, #eff6ff);
  color: var(--cc, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.qac-card-icon i {
  width: 20px;
  height: 20px;
  stroke-width: 2;
}

.qac-card-meta { flex: 1; min-width: 0; }

.qac-card-label {
  font-size: 10px;
  font-weight: 700;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 2px;
}

.qac-card-title {
  font-size: 15px;
  font-weight: 700;
  color: #111827;
  letter-spacing: -0.01em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.qac-card-desc {
  font-size: 12.5px;
  color: #64748b;
  line-height: 1.5;
  flex: 1;
}

.qac-card-action {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--cc, #3b82f6);
  margin-top: 4px;
  transition: gap 0.15s ease;
}

.qac-card:hover .qac-card-action { gap: 8px; }

.qac-card-action i { width: 13px; height: 13px; stroke-width: 2.5; }

/* ── RESPONSIVE ───────────────────────────────────────── */
@media (max-width: 1200px) {
  .qac-grid     { grid-template-columns: repeat(3, 1fr); }
  .qac-grid-3   { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 900px) {
  .qac-grid     { grid-template-columns: repeat(2, 1fr); }
  .qac-grid-3   { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 640px) {
  .qac-wrapper  { padding: 16px 14px 40px; }
  .qac-hero     { flex-direction: column; align-items: flex-start; gap: 20px; padding: 24px 20px; }
  .qac-hero-actions { width: 100%; }
  .qac-btn      { flex: 1; justify-content: center; }
  .qac-grid, .qac-grid-3 { grid-template-columns: 1fr; }
}
</style>

<div class="qac-wrapper">

  <!-- HERO: anonymous = login CTA, logged-in = quick-add buttons (set by JS) -->
  <div class="qac-hero" id="qac-hero">
    <div class="qac-hero-text">
      <h1 id="qac-hero-title">Welcome to Open CRM</h1>
      <p id="qac-hero-subtitle">Your workspace for customers, deals &amp; growth</p>
    </div>
    <div class="qac-hero-actions" id="qac-hero-actions">
      <a href="/login" class="qac-btn qac-btn-login" id="qac-login-link">
        <i data-lucide="log-in"></i>
        <span>Sign In</span>
      </a>
    </div>
  </div>

  <!-- Section: Core CRM -->
  <div class="qac-section-label">Core CRM</div>
  <div class="qac-grid">

    <a href="/crm/dashboard" class="qac-card" style="--cc:#3b82f6;--ci:#eff6ff;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="bar-chart-3"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Analytics</div>
          <div class="qac-card-title">Dashboard</div>
        </div>
      </div>
      <div class="qac-card-desc">Sales overview, KPIs &amp; live pipeline charts</div>
      <div class="qac-card-action"><span>Open dashboard</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/crm/my-contacts" class="qac-card" id="card-contacts" style="--cc:#10b981;--ci:#ecfdf5;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="users"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">People</div>
          <div class="qac-card-title" id="card-contacts-title">My Contacts</div>
        </div>
      </div>
      <div class="qac-card-desc" id="card-contacts-desc">Your customer &amp; lead contact list</div>
      <div class="qac-card-action"><span id="card-contacts-action">View contacts</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/crm/my-organizations" class="qac-card" id="card-orgs" style="--cc:#ec4899;--ci:#fdf2f8;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="building-2"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Companies</div>
          <div class="qac-card-title" id="card-orgs-title">My Organizations</div>
        </div>
      </div>
      <div class="qac-card-desc" id="card-orgs-desc">Companies &amp; accounts you manage</div>
      <div class="qac-card-action"><span id="card-orgs-action">View companies</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/crm/my-deals" class="qac-card" id="card-deals" style="--cc:#14b8a6;--ci:#f0fdfa;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="dollar-sign"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Revenue</div>
          <div class="qac-card-title" id="card-deals-title">My Deals</div>
        </div>
      </div>
      <div class="qac-card-desc" id="card-deals-desc">Deals you're currently managing</div>
      <div class="qac-card-action"><span id="card-deals-action">View deals</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/crm/my-pipeline" class="qac-card" id="card-pipeline" style="--cc:#8b5cf6;--ci:#f5f3ff;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="git-branch"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Pipeline</div>
          <div class="qac-card-title" id="card-pipeline-title">Sales Pipeline</div>
        </div>
      </div>
      <div class="qac-card-desc" id="card-pipeline-desc">Kanban board for deal stages</div>
      <div class="qac-card-action"><span id="card-pipeline-action">Open pipeline</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/crm/my-activities" class="qac-card" id="card-activities" style="--cc:#f59e0b;--ci:#fffbeb;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="calendar-check"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Schedule</div>
          <div class="qac-card-title" id="card-activities-title">My Activities</div>
        </div>
      </div>
      <div class="qac-card-desc" id="card-activities-desc">Calls, meetings &amp; tasks</div>
      <div class="qac-card-action"><span id="card-activities-action">View activities</span><i data-lucide="arrow-right"></i></div>
    </a>

  </div>

  <!-- Section: Tools & Admin -->
  <div class="qac-section-label">Tools &amp; Admin</div>
  <div class="qac-grid qac-grid-3">

    <a href="/admin/content/import" class="qac-card" style="--cc:#06b6d4;--ci:#ecfeff;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="upload-cloud"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">CSV Import</div>
          <div class="qac-card-title">Import Data</div>
        </div>
      </div>
      <div class="qac-card-desc">Bulk import contacts &amp; organizations from CSV files</div>
      <div class="qac-card-action"><span>Import now</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/admin/content" class="qac-card" style="--cc:#6366f1;--ci:#eef2ff;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="database"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Admin</div>
          <div class="qac-card-title">All Content</div>
        </div>
      </div>
      <div class="qac-card-desc">Browse and manage all nodes &amp; content types</div>
      <div class="qac-card-action"><span>View all</span><i data-lucide="arrow-right"></i></div>
    </a>

    <a href="/user" class="qac-card" style="--cc:#64748b;--ci:#f1f5f9;">
      <div class="qac-card-top">
        <div class="qac-card-icon"><i data-lucide="circle-user-round"></i></div>
        <div class="qac-card-meta">
          <div class="qac-card-label">Account</div>
          <div class="qac-card-title">My Profile</div>
        </div>
      </div>
      <div class="qac-card-desc">View and update your profile &amp; settings</div>
      <div class="qac-card-action"><span>View profile</span><i data-lucide="arrow-right"></i></div>
    </a>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  if (window.lucide) lucide.createIcons();

  var isLoggedIn = document.body.classList.contains('user-logged-in');
  var isAdmin = document.body.classList.contains('role--administrator') ||
                document.body.classList.contains('role-administrator') ||
                (window.drupalSettings && window.drupalSettings.user &&
                 (window.drupalSettings.user.uid === '1' || window.drupalSettings.user.uid === 1));

  if (isLoggedIn) {
    // Swap hero to quick-add buttons
    var heroActions = document.getElementById('qac-hero-actions');
    if (heroActions) {
      heroActions.innerHTML =
        '<a href="/node/add/contact" class="qac-btn qac-btn-white">' +
          '<i data-lucide="user-plus"></i><span>New Contact</span>' +
        '</a>' +
        '<a href="/node/add/organization" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="building-2"></i><span>New Organization</span>' +
        '</a>' +
        '<a href="/node/add/deal" class="qac-btn qac-btn-outline">' +
          '<i data-lucide="plus-circle"></i><span>New Deal</span>' +
        '</a>';
    }
    document.getElementById('qac-hero-title').textContent = 'Quick Access';
    document.getElementById('qac-hero-subtitle').textContent = 'Jump to any section of your CRM workspace';
  }

  if (isAdmin && isLoggedIn) {
    document.getElementById('qac-hero-title').textContent = 'Admin Quick Access';

    var updates = [
      { card: 'card-contacts',   href: '/crm/all-contacts',      title: 'All Contacts',      desc: 'All customers in the system',         action: 'View all' },
      { card: 'card-orgs',       href: '/crm/all-organizations',  title: 'All Organizations', desc: 'All companies in the system',          action: 'View all' },
      { card: 'card-deals',      href: '/crm/all-deals',          title: 'All Deals',         desc: 'All deals across the system',          action: 'View all' },
      { card: 'card-pipeline',   href: '/crm/all-pipeline',       title: 'All Pipeline',      desc: 'All deals across all stages',          action: 'View all' },
      { card: 'card-activities', href: '/crm/all-activities',     title: 'All Activities',    desc: 'All activities in the system',         action: 'View all' },
    ];

    updates.forEach(function (u) {
      var card = document.getElementById(u.card);
      if (card) card.href = u.href;
      var t = document.getElementById(u.card + '-title');
      if (t) t.textContent = u.title;
      var d = document.getElementById(u.card + '-desc');
      if (d) d.textContent = u.desc;
      var a = document.getElementById(u.card + '-action');
      if (a) a.textContent = u.action;
    });
  }

  if (window.lucide) lucide.createIcons();
});
</script>
HTML;

$node = \Drupal\node\Entity\Node::load(146);
if (!$node) {
  echo "ERROR: Node 146 not found.\n";
  exit(1);
}

$node->set('body', [
  'value'  => $new_body,
  'format' => 'full_html',
]);
$node->save();

echo "✅ Node 146 (homepage) updated successfully.\n";
echo "Length: " . strlen($new_body) . " chars\n";
