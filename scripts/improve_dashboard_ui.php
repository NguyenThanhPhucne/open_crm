<?php

/**
 * Improve CRM Quick Access Dashboard UI/UX
 * 
 * Changes:
 * - Replace text links with proper CTA buttons
 * - Compact welcome banner (80-100px height)
 * - Modern SaaS dashboard layout
 * - Consistent spacing and visual hierarchy
 * - Responsive grid layout
 * - Lucide icons in buttons
 */

use Drupal\node\Entity\Node;

echo "🎨 IMPROVING CRM DASHBOARD UI/UX\n";
echo "==================================\n\n";

// Load homepage node
$node = Node::load(142);

if (!$node) {
  echo "❌ Homepage node not found\n";
  exit(1);
}

echo "📄 Updating: " . $node->getTitle() . "\n\n";

// New HTML with improved UI/UX
$html = <<<'HTML'
<script src="https://unpkg.com/lucide@latest"></script>
<style>
  :root {
    --color-primary: #3b82f6;
    --color-primary-hover: #2563eb;
    --color-text-primary: #0f172a;
    --color-text-secondary: #64748b;
    --color-border: #e2e8f0;
    --color-background: #f8fafc;
    --spacing-xs: 8px;
    --spacing-sm: 12px;
    --spacing-md: 16px;
    --spacing-lg: 20px;
    --spacing-xl: 24px;
    --spacing-2xl: 32px;
  }
  
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  
  body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: var(--color-background);
    color: var(--color-text-primary);
    line-height: 1.5;
  }
  
  .quick-access-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: var(--spacing-2xl) var(--spacing-lg);
    animation: fadeIn 0.3s ease-in;
  }
  
  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  
  /* Compact Welcome Banner - Only for Anonymous Users */
  .crm-login-banner {
    background: white;
    border: 1px solid var(--color-border);
    border-radius: 12px;
    padding: var(--spacing-lg);
    margin-bottom: var(--spacing-2xl);
    display: none;
    align-items: center;
    justify-content: space-between;
    gap: var(--spacing-lg);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    min-height: 80px;
  }
  
  .login-banner-text {
    flex: 1;
  }
  
  .login-banner-text h2 {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-text-primary);
    margin-bottom: 4px;
  }
  
  .login-banner-text p {
    font-size: 14px;
    color: var(--color-text-secondary);
    line-height: 1.4;
  }
  
  .login-banner-actions {
    display: flex;
    gap: var(--spacing-sm);
    flex-shrink: 0;
  }
  
  .btn-auth {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
    white-space: nowrap;
  }
  
  .btn-auth i {
    width: 16px;
    height: 16px;
  }
  
  .btn-login {
    background: var(--color-primary);
    color: white;
    border: 1px solid var(--color-primary);
  }
  
  .btn-login:hover {
    background: var(--color-primary-hover);
    border-color: var(--color-primary-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }
  
  .btn-register {
    background: white;
    color: var(--color-text-secondary);
    border: 1px solid var(--color-border);
  }
  
  .btn-register:hover {
    background: var(--color-background);
    border-color: #cbd5e1;
    color: #475569;
  }
  
  /* Hide banner for logged in users */
  body.user-logged-in .crm-login-banner {
    display: none !important;
  }
  
  /* Dashboard Header */
  .dashboard-header {
    margin-bottom: var(--spacing-2xl);
    padding-bottom: var(--spacing-xl);
    border-bottom: 1px solid var(--color-border);
  }
  
  .dashboard-header h1 {
    font-size: 32px;
    font-weight: 600;
    color: var(--color-text-primary);
    margin-bottom: var(--spacing-xs);
    letter-spacing: -0.02em;
  }
  
  .dashboard-header p {
    font-size: 15px;
    color: var(--color-text-secondary);
    line-height: 1.6;
    max-width: 600px;
  }
  
  /* Cards Grid */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: var(--spacing-lg);
  }
  
  /* Card Styles */
  .crm-card {
    background: white;
    border-radius: 14px;
    padding: var(--spacing-lg);
    border: 1px solid var(--color-border);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    position: relative;
    min-height: 220px;
  }
  
  .crm-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    border-color: #cbd5e1;
  }
  
  /* Card Header with Icon */
  .card-icon-wrapper {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-md);
    flex-shrink: 0;
  }
  
  .card-icon-wrapper i {
    width: 24px;
    height: 24px;
    stroke-width: 2;
  }
  
  /* Icon Colors */
  .icon-blue { 
    background: #eff6ff;
    color: #3b82f6;
  }
  
  .icon-green { 
    background: #ecfdf5;
    color: #10b981;
  }
  
  .icon-purple { 
    background: #f5f3ff;
    color: #8b5cf6;
  }
  
  .icon-orange { 
    background: #fffbeb;
    color: #f59e0b;
  }
  
  .icon-pink { 
    background: #fdf2f8;
    color: #ec4899;
  }
  
  .icon-teal { 
    background: #f0fdfa;
    color: #14b8a6;
  }
  
  .icon-cyan { 
    background: #cffafe;
    color: #06b6d4;
  }
  
  .icon-gray { 
    background: #f9fafb;
    color: #6b7280;
  }
  
  /* Card Content */
  .card-label {
    font-size: 11px;
    color: var(--color-text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
  }
  
  .card-title {
    font-size: 20px;
    font-weight: 600;
    color: var(--color-text-primary);
    line-height: 1.3;
    margin-bottom: var(--spacing-sm);
  }
  
  .card-description {
    font-size: 14px;
    color: var(--color-text-secondary);
    line-height: 1.5;
    margin-bottom: var(--spacing-lg);
    flex-grow: 1;
  }
  
  /* CTA Button - Modern SaaS Style */
  .crm-card-action {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: 8px 14px;
    border-radius: 10px;
    border: 2px solid;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s ease;
    cursor: pointer;
    white-space: nowrap;
    text-decoration: none;
    align-self: flex-start;
  }
  
  .crm-card-action i {
    width: 16px;
    height: 16px;
    transition: transform 0.2s ease;
  }
  
  /* Button Color Variants */
  .btn-blue {
    border-color: #3b82f6;
    color: #3b82f6;
    background: transparent;
  }
  
  .btn-blue:hover {
    background: #3b82f6;
    color: white;
  }
  
  .btn-green {
    border-color: #10b981;
    color: #10b981;
    background: transparent;
  }
  
  .btn-green:hover {
    background: #10b981;
    color: white;
  }
  
  .btn-purple {
    border-color: #8b5cf6;
    color: #8b5cf6;
    background: transparent;
  }
  
  .btn-purple:hover {
    background: #8b5cf6;
    color: white;
  }
  
  .btn-orange {
    border-color: #f59e0b;
    color: #f59e0b;
    background: transparent;
  }
  
  .btn-orange:hover {
    background: #f59e0b;
    color: white;
  }
  
  .btn-pink {
    border-color: #ec4899;
    color: #ec4899;
    background: transparent;
  }
  
  .btn-pink:hover {
    background: #ec4899;
    color: white;
  }
  
  .btn-teal {
    border-color: #14b8a6;
    color: #14b8a6;
    background: transparent;
  }
  
  .btn-teal:hover {
    background: #14b8a6;
    color: white;
  }
  
  .btn-cyan {
    border-color: #06b6d4;
    color: #06b6d4;
    background: transparent;
  }
  
  .btn-cyan:hover {
    background: #06b6d4;
    color: white;
  }
  
  .btn-gray {
    border-color: #6b7280;
    color: #6b7280;
    background: transparent;
  }
  
  .btn-gray:hover {
    background: #6b7280;
    color: white;
  }
  
  .crm-card-action:hover i {
    transform: translateX(2px);
  }
  
  /* Responsive Design */
  @media (max-width: 768px) {
    .quick-access-container {
      padding: var(--spacing-lg) var(--spacing-md);
    }
    
    .crm-login-banner {
      flex-direction: column;
      align-items: flex-start;
      padding: var(--spacing-md);
      min-height: auto;
    }
    
    .login-banner-text h2 {
      font-size: 15px;
    }
    
    .login-banner-text p {
      font-size: 13px;
    }
    
    .login-banner-actions {
      width: 100%;
      flex-direction: column;
    }
    
    .btn-auth {
      width: 100%;
      justify-content: center;
    }
    
    .dashboard-header {
      margin-bottom: var(--spacing-xl);
      padding-bottom: var(--spacing-md);
    }
    
    .dashboard-header h1 {
      font-size: 24px;
    }
    
    .dashboard-header p {
      font-size: 14px;
    }
    
    .cards-grid {
      grid-template-columns: 1fr;
      gap: var(--spacing-md);
    }
    
    .crm-card {
      min-height: auto;
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
  <!-- Compact Login Banner - Only for Anonymous Users -->
  <div class="crm-login-banner">
    <div class="login-banner-text">
      <h2>Welcome to Open CRM</h2>
      <p>Please login to access the CRM system</p>
    </div>
    <div class="login-banner-actions">
      <a href="/user/login" class="btn-auth btn-login">
        <i data-lucide="log-in"></i>
        <span>Login</span>
      </a>
      <a href="/user/register" class="btn-auth btn-register">
        <i data-lucide="user-plus"></i>
        <span>Register</span>
      </a>
    </div>
  </div>
  
  <!-- Dashboard Header -->
  <div class="dashboard-header">
    <h1>Open CRM</h1>
    <p>Manage customers, deals and business activities in one place.</p>
  </div>
  
  <!-- Cards Grid -->
  <div class="cards-grid">
    <!-- Dashboard Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-blue">
        <i data-lucide="layout-dashboard"></i>
      </div>
      <div class="card-label">ANALYTICS</div>
      <h3 class="card-title">Dashboard</h3>
      <p class="card-description">View analytics and sales statistics</p>
      <a href="/crm/dashboard" class="crm-card-action btn-blue">
        <i data-lucide="layout-dashboard"></i>
        <span>Dashboard</span>
      </a>
    </div>
    
    <!-- My Contacts Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-green">
        <i data-lucide="users"></i>
      </div>
      <div class="card-label">CONTACTS</div>
      <h3 class="card-title">My Contacts</h3>
      <p class="card-description">Manage your customer contact list</p>
      <a href="/crm/my-contacts" class="crm-card-action btn-green">
        <i data-lucide="users"></i>
        <span>Contacts</span>
      </a>
    </div>
    
    <!-- Sales Pipeline Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-purple">
        <i data-lucide="git-branch"></i>
      </div>
      <div class="card-label">PIPELINE</div>
      <h3 class="card-title">Sales Pipeline</h3>
      <p class="card-description">Manage deals with Kanban board</p>
      <a href="/crm/my-pipeline" class="crm-card-action btn-purple">
        <i data-lucide="git-branch"></i>
        <span>Pipeline</span>
      </a>
    </div>
    
    <!-- My Activities Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-orange">
        <i data-lucide="calendar"></i>
      </div>
      <div class="card-label">SCHEDULE</div>
      <h3 class="card-title">My Activities</h3>
      <p class="card-description">Work schedule and tasks to do</p>
      <a href="/crm/my-activities" class="crm-card-action btn-orange">
        <i data-lucide="calendar"></i>
        <span>Activities</span>
      </a>
    </div>
    
    <!-- My Organizations Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-pink">
        <i data-lucide="building"></i>
      </div>
      <div class="card-label">COMPANIES</div>
      <h3 class="card-title">My Organizations</h3>
      <p class="card-description">Your company list</p>
      <a href="/crm/my-organizations" class="crm-card-action btn-pink">
        <i data-lucide="building"></i>
        <span>Organizations</span>
      </a>
    </div>
    
    <!-- My Deals Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-teal">
        <i data-lucide="dollar-sign"></i>
      </div>
      <div class="card-label">DEALS</div>
      <h3 class="card-title">My Deals</h3>
      <p class="card-description">List of deals you manage</p>
      <a href="/crm/my-deals" class="crm-card-action btn-teal">
        <i data-lucide="dollar-sign"></i>
        <span>Deals</span>
      </a>
    </div>
    
    <!-- Import Data Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-cyan">
        <i data-lucide="upload"></i>
      </div>
      <div class="card-label">CSV IMPORT</div>
      <h3 class="card-title">Import Data</h3>
      <p class="card-description">Bulk import from CSV file</p>
      <a href="/admin/content/import" class="crm-card-action btn-cyan">
        <i data-lucide="upload"></i>
        <span>Import</span>
      </a>
    </div>
    
    <!-- All Content Card -->
    <div class="crm-card">
      <div class="card-icon-wrapper icon-gray">
        <i data-lucide="database"></i>
      </div>
      <div class="card-label">ADMIN</div>
      <h3 class="card-title">All Content</h3>
      <p class="card-description">Manage all content in the system</p>
      <a href="/admin/content" class="crm-card-action btn-gray">
        <i data-lucide="database"></i>
        <span>Admin</span>
      </a>
    </div>
  </div>
</div>

<script>
  // Initialize Lucide icons
  lucide.createIcons();
  
  // Show login banner for anonymous users only
  setTimeout(function() {
    const isLoggedIn = document.body.classList.contains('user-logged-in');
    const loginBanner = document.querySelector('.crm-login-banner');
    
    if (!isLoggedIn && loginBanner) {
      loginBanner.style.display = 'flex';
    }
  }, 50);
  
  // Role-based routing for admin vs regular users
  setTimeout(function() {
    const bodyClasses = document.body.className;
    const isAdmin = bodyClasses.includes('role--administrator') || 
                    bodyClasses.includes('role-administrator') ||
                    bodyClasses.includes('user--admin') ||
                    (window.drupalSettings && window.drupalSettings.user && window.drupalSettings.user.uid === '1');
    
    if (isAdmin) {
      // Update cards for admin to show ALL data
      const updates = [
        { selector: 'a[href="/crm/my-contacts"]', href: '/crm/all-contacts', title: 'All Contacts', label: 'ALL CONTACTS', desc: 'View all customers in the system', btnText: 'All Contacts' },
        { selector: 'a[href="/crm/my-deals"]', href: '/crm/all-deals', title: 'All Deals', label: 'ALL DEALS', desc: 'View all deals in the system', btnText: 'All Deals' },
        { selector: 'a[href="/crm/my-organizations"]', href: '/crm/all-organizations', title: 'All Organizations', label: 'ALL COMPANIES', desc: 'View all companies in the system', btnText: 'All Organizations' },
        { selector: 'a[href="/crm/my-activities"]', href: '/crm/all-activities', title: 'All Activities', label: 'ALL ACTIVITIES', desc: 'View all activities in the system', btnText: 'All Activities' },
        { selector: 'a[href="/crm/my-pipeline"]', href: '/crm/all-pipeline', title: 'All Pipeline', label: 'ALL PIPELINE', desc: 'View all deals in pipeline', btnText: 'All Pipeline' }
      ];
      
      updates.forEach(update => {
        const btn = document.querySelector(update.selector);
        if (btn) {
          btn.href = update.href;
          const card = btn.closest('.crm-card');
          if (card) {
            const cardTitle = card.querySelector('.card-title');
            const cardLabel = card.querySelector('.card-label');
            const cardDesc = card.querySelector('.card-description');
            const btnText = btn.querySelector('span');
            
            if (cardTitle) cardTitle.textContent = update.title;
            if (cardLabel) cardLabel.textContent = update.label;
            if (cardDesc) cardDesc.textContent = update.desc;
            if (btnText) btnText.textContent = update.btnText;
          }
        }
      });
      
      // Update page header for admin
      const pageTitle = document.querySelector('.dashboard-header h1');
      if (pageTitle) pageTitle.textContent = 'Admin Dashboard';
      
      const pageSubtitle = document.querySelector('.dashboard-header p');
      if (pageSubtitle) pageSubtitle.textContent = 'Manage all CRM data and system-wide operations.';
    }
  }, 100);
</script>
HTML;

// Update node
$node->set('body', [
  'value' => $html,
  'format' => 'full_html',
]);

$node->save();

echo "✅ Successfully improved dashboard UI/UX!\n\n";

echo "🎨 UI Improvements:\n";
echo "   ✓ Replaced text links with proper CTA buttons\n";
echo "   ✓ Soft outline button style (2px border, hover fill)\n";
echo "   ✓ Lucide icons inside buttons\n";
echo "   ✓ Compact login banner (80-100px height)\n";
echo "   ✓ Enhanced card layout (14px border-radius, better shadows)\n";
echo "   ✓ Improved visual hierarchy (label → title → desc → button)\n";
echo "   ✓ Consistent spacing scale (8px, 12px, 16px, 20px, 24px, 32px)\n";
echo "   ✓ CSS Grid responsive layout\n";
echo "   ✓ Modern SaaS dashboard look (Linear/Stripe/HubSpot style)\n\n";

echo "🔘 Button Features:\n";
echo "   • Soft outline style with color border\n";
echo "   • Icon + text inside button\n";
echo "   • Hover: fills with color + white text\n";
echo "   • Icon slides right on hover\n";
echo "   • 8 color variants (blue, green, purple, orange, pink, teal, cyan, gray)\n\n";

echo "📱 Responsive:\n";
echo "   • Desktop (>1024px): 4 columns\n";
echo "   • Tablet (769-1024px): 2 columns\n";
echo "   • Mobile (<768px): 1 column\n\n";

echo "🌐 View at: http://open-crm.ddev.site/\n\n";

echo "💡 Next: ddev drush cr\n";
