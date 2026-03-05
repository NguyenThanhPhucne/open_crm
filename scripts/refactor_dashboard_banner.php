<?php

/**
 * Refactor Dashboard Banner - Remove Marketing Style, Add Professional Header
 * 
 * Changes:
 * - Remove large gradient hero banner with Login/Register buttons
 * - Add minimal professional dashboard header
 * - Keep Quick Access cards as main focus
 */

use Drupal\node\Entity\Node;

echo "🎨 REFACTORING DASHBOARD BANNER\n";
echo "================================\n\n";

// Load homepage node (node 142 - Quick Access - CRM)
$node = Node::load(142);

if (!$node) {
  echo "❌ Homepage node not found (node 142)\n";
  exit(1);
}

echo "📄 Current homepage: " . $node->getTitle() . " (node/{$node->id()})\n\n";

// Create new HTML with minimal professional header
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
  
  /* Minimal Professional Header */
  .dashboard-header {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e2e8f0;
  }
  
  .dashboard-header h1 {
    font-size: 32px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 8px;
    letter-spacing: -0.02em;
  }
  
  .dashboard-header p {
    font-size: 15px;
    color: #64748b;
    line-height: 1.6;
    max-width: 600px;
  }
  
  /* Cards Grid */
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
    
    .dashboard-header {
      margin-bottom: 24px;
      padding-bottom: 16px;
    }
    
    .dashboard-header h1 {
      font-size: 24px;
    }
    
    .dashboard-header p {
      font-size: 14px;
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
  <!-- Minimal Professional Header -->
  <div class="dashboard-header">
    <h1>Open CRM</h1>
    <p>Manage customers, deals and business activities in one place.</p>
  </div>
  
  <!-- Quick Access Cards Grid -->
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
  // Initialize Lucide icons first
  lucide.createIcons();
  
  // Wait a bit to ensure DOM is fully rendered, then apply role-based logic
  setTimeout(function() {
    console.log('=== CRM Homepage Role-Based Routing Debug ===');
    
    // Check if user is logged in
    const isLoggedIn = document.body.classList.contains('user-logged-in');
    console.log('Is Logged In:', isLoggedIn);
    
    // Role-based routing: Admin sees ALL data, regular users see MY data
    const bodyClasses = document.body.className;
    console.log('Body classes:', bodyClasses);
    
    // Check Drupal settings for user info
    if (window.drupalSettings && window.drupalSettings.user) {
      console.log('Drupal user settings:', window.drupalSettings.user);
    }
    
    // Check multiple possible admin detection methods
    const isAdmin = bodyClasses.includes('role--administrator') || 
                    bodyClasses.includes('role-administrator') ||
                    bodyClasses.includes('user--admin') ||
                    (window.drupalSettings && window.drupalSettings.user && window.drupalSettings.user.uid === '1');
    
    console.log('Is Admin:', isAdmin);
    
    if (isAdmin) {
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
        } else {
          console.log('✗ Card not found:', update.selector);
        }
      });
      
      console.log(`Updated ${updatedCount} cards for admin view`);
      
      // Update page header for admin
      const pageTitle = document.querySelector('.dashboard-header h1');
      if (pageTitle) {
        pageTitle.textContent = 'Admin Dashboard';
        console.log('✓ Updated page title to Admin Dashboard');
      }
      const pageSubtitle = document.querySelector('.dashboard-header p');
      if (pageSubtitle) {
        pageSubtitle.textContent = 'Manage all CRM data and system-wide operations.';
        console.log('✓ Updated page subtitle');
      }
    } else {
      console.log('ℹ️ Regular user - keeping MY views');
    }
    
    console.log('=== End Debug ===');
  }, 100); // Small delay to ensure DOM is ready
</script>
HTML;

// Update node with new content
$node->set('body', [
  'value' => $html,
  'format' => 'full_html',
]);

$node->save();

echo "✅ Successfully refactored dashboard banner!\n\n";

echo "📊 Changes made:\n";
echo "   ✓ Removed gradient hero banner with purple background\n";
echo "   ✓ Removed Login/Register buttons (not needed in authenticated CRM)\n";
echo "   ✓ Replaced with minimal professional header:\n";
echo "     - Title: 'Open CRM' (32px, font-weight: 600)\n";
echo "     - Subtitle: Muted gray description text\n";
echo "     - Clean divider line below header\n";
echo "   ✓ Reduced header spacing (40px → 32px margin-bottom)\n";
echo "   ✓ Quick Access cards grid remains the main visual focus\n";
echo "   ✓ Professional admin dashboard style (Stripe/Linear/Notion)\n\n";

echo "🎨 Design principles:\n";
echo "   • Clean, minimal, functional\n";
echo "   • No gradient backgrounds or decorative cards\n";
echo "   • Lightweight header (not visually heavy)\n";
echo "   • Balanced spacing (header: 32px, sections: 24px)\n";
echo "   • Professional SaaS admin interface style\n\n";

echo "🌐 View changes:\n";
echo "   http://open-crm.ddev.site/\n\n";

echo "💡 Next: Clear cache with 'ddev drush cr'\n";
