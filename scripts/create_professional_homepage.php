<?php

/**
 * Create Professional CRM Quick Access Homepage
 */

use Drupal\node\Entity\Node;

echo "🚀 CREATING PROFESSIONAL CRM QUICK ACCESS HOMEPAGE\n";
echo "==================================================\n\n";

// Delete old homepage nodes
echo "🗑️  Deleting old homepage nodes...\n";
$old_nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Quick Access - CRM', 'type' => 'page']);

foreach ($old_nodes as $old_node) {
  echo "   Deleting Node " . $old_node->id() . "\n";
  $old_node->delete();
}

// Create beautiful professional homepage
echo "\n✨ Creating new professional homepage...\n";

// Check if building for logged in users (banner will be hidden via CSS class)
$welcome_banner_class = 'welcome-banner';

$html = <<<'HTML'
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8fafc;
    color: #1e293b;
  }
  
  .quick-access-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 32px 20px;
    animation: fadeIn 0.3s ease-in;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  .welcome-banner {
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0 0 32px 0;
    margin-bottom: 32px;
    box-shadow: none;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .banner-content h1 {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }
  
  .banner-content p {
    font-size: 15px;
    color: #64748b;
    line-height: 1.5;
    margin-bottom: 20px;
  }
  
  .banner-actions {
    display: flex;
    gap: 12px;
    flex-shrink: 0;
    margin-left: auto;
    align-items: center;
  }
  
  .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    white-space: nowrap;
    border: 1.5px solid transparent;
  }
  
  .btn i {
    width: 16px;
    height: 16px;
    stroke-width: 2;
  }
  
  .btn-primary {
    background: white;
    color: #3b82f6;
    border-color: #3b82f6;
  }
  
  .btn-primary i {
    color: #3b82f6;
  }
  
  .btn-primary:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    box-shadow: 0 1px 3px rgba(59, 130, 246, 0.12);
  }
  
  .btn-primary:active {
    background: #dbeafe;
    border-color: #2563eb;
  }
  
  .btn-primary:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
  }
  
  .btn-secondary {
    background: transparent;
    color: #64748b;
    border-color: #e2e8f0;
    display: none;
  }
  
  .btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
  }
  
  .page-header {
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    gap: 12px;
  }
  
  .page-header-icon {
    width: 32px;
    height: 32px;
    color: #667eea;
    flex-shrink: 0;
  }
  
  .page-header-content h1 {
    font-size: 32px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 6px;
    letter-spacing: -0.02em;
  }
  
  .page-header-content p {
    font-size: 15px;
    color: #64748b;
    line-height: 1.5;
  }
  
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
  }
  
  .crm-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
  }
  
  .crm-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    height: 3px;
    width: 0;
    background: var(--card-color, #3b82f6);
    transition: width 0.25s ease-out;
  }
  
  .crm-card:hover {
    box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.15), 0 6px 12px -4px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
    border-color: #cbd5e1;
  }
  
  .crm-card:hover::before {
    width: 100%;
  }
  
  .crm-card:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }
  
  .card-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
  }
  
  .card-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  
  .card-icon i {
    width: 20px;
    height: 20px;
    stroke-width: 2.5;
  }
  
  .card-icon.blue { 
    background: #eff6ff;
    color: #3b82f6;
  }
  
  .card-icon.green { 
    background: #ecfdf5;
    color: #10b981;
  }
  
  .card-icon.purple { 
    background: #f5f3ff;
    color: #8b5cf6;
  }
  
  .card-icon.orange { 
    background: #fffbeb;
    color: #f59e0b;
  }
  
  .card-icon.pink { 
    background: #fdf2f8;
    color: #ec4899;
  }
  
  .card-icon.teal { 
    background: #f0fdfa;
    color: #14b8a6;
  }
  
  .card-icon.gray { 
    background: #f9fafb;
    color: #6b7280;
  }
  
  .card-icon.cyan { 
    background: #cffafe;
    color: #06b6d4;
  }
  
  .card-content {
    flex: 1;
  }
  
  .card-label {
    font-size: 12px;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 4px;
  }
  
  .card-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
  }
  
  .card-description {
    font-size: 14px;
    color: #64748b;
    line-height: 1.5;
    margin-top: 12px;
    margin-bottom: 16px;
  }
  
  .card-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--card-color, #3b82f6);
    padding: 0;
    transition: all 0.2s ease;
    align-self: flex-start;
  }
  
  .card-action i {
    width: 16px;
    height: 16px;
    transition: transform 0.3s ease;
  }
  
  .crm-card:hover .card-action i {
    transform: translateX(4px);
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .quick-access-container {
      padding: 20px 16px;
    }
    
    .welcome-banner {
      flex-direction: column !important;
      align-items: flex-start !important;
      padding: 0 0 20px 0 !important;
      gap: 16px !important;
      border-bottom: 1px solid #e2e8f0 !important;
    }
    
    .banner-content h1 {
      font-size: 22px;
    }
    
    .banner-actions {
      width: 100%;
      margin-left: 0;
      justify-content: flex-start;
    }
    
    .btn {
      padding: 10px 16px;
      font-size: 13px;
    }
    
    .page-header h1 {
      font-size: 24px;
    }
    
    .cards-grid {
      grid-template-columns: 1fr;
      gap: 16px;
    }
  }
  
  @media (min-width: 769px) and (max-width: 1024px) {
    .cards-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (min-width: 1025px) and (max-width: 1280px) {
    .cards-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
</style>

<div class="quick-access-container">
  <!-- Minimal Header Section (shown only for non-logged-in users) -->
  <div class="welcome-banner" style="display: flex; align-items: center; gap: 24px; margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid #e2e8f0;">
    <div class="banner-content">
      <h1>Welcome to Open CRM</h1>
      <p>Manage customers, deals and business activities in one place.</p>
    </div>
    <div class="banner-actions">
      <a href="/user/login" class="btn btn-primary" title="Login to your account">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
        </svg>
        <span>Login</span>
      </a>
    </div>
  </div>
  
  <div class="page-header">
    <i data-lucide="rocket" class="page-header-icon"></i>
    <div class="page-header-content">
      <h1>Quick Access</h1>
      <p>Quickly access the main CRM features</p>
    </div>
  </div>
  
  <div class="cards-grid">
    <!-- Dashboard Card -->
    <a href="/crm/dashboard" class="crm-card" style="--card-color: #3b82f6;">
      <div class="card-header">
        <div class="card-icon blue">
          <i data-lucide="bar-chart-3"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Analytics</div>
          <h3 class="card-title">Dashboard</h3>
        </div>
      </div>
      <p class="card-description">View analytics and sales statistics</p>
      <div class="card-action">
        <span>View dashboard</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- My Contacts Card -->
    <a href="/crm/my-contacts" class="crm-card" style="--card-color: #10b981;">
      <div class="card-header">
        <div class="card-icon green">
          <i data-lucide="users"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Contacts</div>
          <h3 class="card-title">My Contacts</h3>
        </div>
      </div>
      <p class="card-description">Manage your customer contact list</p>
      <div class="card-action">
        <span>View contacts</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- Sales Pipeline Card -->
    <a href="/crm/my-pipeline" class="crm-card" style="--card-color: #8b5cf6;">
      <div class="card-header">
        <div class="card-icon purple">
          <i data-lucide="git-branch"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Pipeline</div>
          <h3 class="card-title">Sales Pipeline</h3>
        </div>
      </div>
      <p class="card-description">Manage deals with Kanban board</p>
      <div class="card-action">
        <span>Open pipeline</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- My Activities Card -->
    <a href="/crm/my-activities" class="crm-card" style="--card-color: #f59e0b;">
      <div class="card-header">
        <div class="card-icon orange">
          <i data-lucide="calendar"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Schedule</div>
          <h3 class="card-title">My Activities</h3>
        </div>
      </div>
      <p class="card-description">Work schedule and tasks to do</p>
      <div class="card-action">
        <span>View calendar</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- My Organizations Card -->
    <a href="/crm/my-organizations" class="crm-card" style="--card-color: #ec4899;">
      <div class="card-header">
        <div class="card-icon pink">
          <i data-lucide="building-2"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Companies</div>
          <h3 class="card-title">My Organizations</h3>
        </div>
      </div>
      <p class="card-description">Your company list</p>
      <div class="card-action">
        <span>View companies</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- My Deals Card -->
    <a href="/crm/my-deals" class="crm-card" style="--card-color: #14b8a6;">
      <div class="card-header">
        <div class="card-icon teal">
          <i data-lucide="dollar-sign"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Deals</div>
          <h3 class="card-title">My Deals</h3>
        </div>
      </div>
      <p class="card-description">List of deals you manage</p>
      <div class="card-action">
        <span>View deals</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- Import Data Card -->
    <a href="/admin/content/import" class="crm-card" style="--card-color: #06b6d4;">
      <div class="card-header">
        <div class="card-icon cyan">
          <i data-lucide="upload"></i>
        </div>
        <div class="card-content">
          <div class="card-label">CSV Import</div>
          <h3 class="card-title">Import Data</h3>
        </div>
      </div>
      <p class="card-description">Bulk import from CSV file</p>
      <div class="card-action">
        <span>Import data</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
    
    <!-- All Content Card -->
    <a href="/admin/content" class="crm-card" style="--card-color: #6b7280;">
      <div class="card-header">
        <div class="card-icon gray">
          <i data-lucide="database"></i>
        </div>
        <div class="card-content">
          <div class="card-label">Admin</div>
          <h3 class="card-title">All Content</h3>
        </div>
      </div>
      <p class="card-description">Manage all content in the system</p>
      <div class="card-action">
        <span>View all</span>
        <i data-lucide="arrow-right"></i>
      </div>
    </a>
  </div>
</div>

<script>
  // Initialize Lucide icons
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }
  
  // Wait a bit to ensure DOM is fully rendered
  setTimeout(function() {
    console.log('=== CRM Homepage Initialization ===');
    
    // Check if user is logged in and hide welcome banner for authenticated users
    const isLoggedIn = document.body.classList.contains('user-logged-in');
    console.log('Is Logged In:', isLoggedIn);
    
    if (isLoggedIn) {
      const banner = document.querySelector('.welcome-banner');
      if (banner) {
        banner.style.display = 'none';
        console.log('✓ Welcome banner hidden for logged in user');
      }
    } else {
      console.log('ℹ️ User not logged in - Welcome banner is visible');
    }
    
    // Check for admin role
    const bodyClasses = document.body.className;
    const isAdmin = bodyClasses.includes('role--administrator') || 
                    bodyClasses.includes('role-administrator') ||
                    bodyClasses.includes('user--admin') ||
                    (window.drupalSettings && window.drupalSettings.user && window.drupalSettings.user.uid === '1');
    
    console.log('Is Admin:', isAdmin);
    
    if (isAdmin && isLoggedIn) {
      console.log('✅ Admin detected - updating links to ALL views');
      
      // Update links for admin to show ALL data
      const updates = [
        { selector: 'a[href="/crm/my-contacts"]', href: '/crm/all-contacts', title: 'All Contacts', label: 'All Contacts', desc: 'View all customers in the system', action: 'View all' },
        { selector: 'a[href="/crm/my-deals"]', href: '/crm/all-deals', title: 'All Deals', label: 'All Deals', desc: 'View all deals in the system', action: 'View all' },
        { selector: 'a[href="/crm/my-organizations"]', href: '/crm/all-organizations', title: 'All Organizations', label: 'All Organizations', desc: 'View all companies in the system', action: 'View all' },
        { selector: 'a[href="/crm/my-activities"]', href: '/crm/all-activities', title: 'All Activities', label: 'All Activities', desc: 'View all activities in the system', action: 'View all' },
        { selector: 'a[href="/crm/my-pipeline"]', href: '/crm/all-pipeline', title: 'All Pipeline', label: 'All Pipeline', desc: 'View all deals in pipeline', action: 'View all' }
      ];
      
      let updatedCount = 0;
      updates.forEach(update => {
        const card = document.querySelector(update.selector);
        if (card) {
          console.log('✓ Updating card:', update.selector, '→', update.href);
          card.href = update.href;
          const cardTitle = card.querySelector('.card-title');
          if (cardTitle) cardTitle.textContent = update.title;
          const cardLabel = card.querySelector('.card-label');
          if (cardLabel && update.label) cardLabel.textContent = update.label;
          const cardDesc = card.querySelector('.card-description');
          if (cardDesc && update.desc) cardDesc.textContent = update.desc;
          const cardAction = card.querySelector('.card-action span');
          if (cardAction && update.action) cardAction.textContent = update.action;
          updatedCount++;
        }
      });
      
      console.log(`✓ Updated ${updatedCount} cards for admin view`);
      
      // Update page header for admin
      const pageHeaderIcon = document.querySelector('.page-header-icon');
      if (pageHeaderIcon) {
        pageHeaderIcon.setAttribute('data-lucide', 'crown');
        lucide.createIcons();
        console.log('✓ Updated page icon to crown');
      }
      const pageTitle = document.querySelector('.page-header-content h1');
      if (pageTitle) {
        pageTitle.textContent = 'Admin Dashboard';
        console.log('✓ Updated page title to Admin Dashboard');
      }
      const pageSubtitle = document.querySelector('.page-header-content p');
      if (pageSubtitle) {
        pageSubtitle.textContent = 'Manage all CRM data and system-wide operations';
        console.log('✓ Updated page subtitle');
      }
    } else if (isLoggedIn) {
      console.log('ℹ️ Regular user - keeping MY views');
    }
    
    console.log('=== Initialization Complete ===');
  }, 100); // Small delay to ensure DOM is ready
</script>
HTML;

$node = Node::create([
  'type' => 'page',
  'title' => 'Quick Access - CRM',
  'body' => [
    'value' => $html,
    'format' => 'full_html',
  ],
  'status' => 1,
  'promote' => 0,
  'sticky' => 0,
  'uid' => 1,
]);

$node->save();
$node_id = $node->id();

echo "✅ Created professional homepage (Node $node_id)\n";

// Set as homepage
echo "\n🏠 Setting as homepage...\n";
\Drupal::configFactory()
  ->getEditable('system.site')
  ->set('page.front', '/node/' . $node_id)
  ->save();

echo "✅ Homepage configured\n";

// Clear cache
echo "\n🧹 Clearing cache...\n";
drupal_flush_all_caches();

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║            ✨ DASHBOARD-STYLE HOMEPAGE CREATED ✨         ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "✅ Homepage với style giống Dashboard đã tạo xong!\n";
echo "📍 Node ID: $node_id\n";
echo "🌐 URL: http://open-crm.ddev.site/\n";
echo "\n";
echo "🎨 Design Features (giống Dashboard):\n";
echo "  • Background: #f8fafc\n";
echo "  • Border: 1px solid #e2e8f0\n";
echo "  • Shadow: 0 1px 2px rgba(0,0,0,0.05)\n";
echo "  • Hover: translateY(-2px)\n";
echo "  • Icon size: 48px (pastel background)\n";
echo "  • Border-radius: 12px\n";
echo "  • Padding: 24px\n";
echo "\n";
echo "📦 Cards:\n";
echo "  1. Dashboard (Analytics, blue icon)\n";
echo "  2. My Contacts (Contacts, green icon)\n";
echo "  3. Sales Pipeline (Pipeline, purple icon)\n";
echo "  4. My Activities (Schedule, orange icon)\n";
echo "  5. My Organizations (Companies, pink icon)\n";
echo "  6. My Deals (Deals, teal icon)\n";
echo "  7. Import Data (CSV Import, cyan icon)\n";
echo "  8. All Content (Admin, gray icon)\n";
echo "\n";
echo "✨ Đặc điểm:\n";
echo "  • Style nhất quán với Dashboard page\n";
echo "  • Top gradient line khi hover\n";
echo "  • Action button với background #eff6ff\n";
echo "  • Responsive grid layout\n";
echo "\n";
