<?php

/**
 * Fix homepage links to be role-based
 * Admin/Manager should see "All" links, Sales Rep sees "My" links
 */

use Drupal\node\Entity\Node;

// Load homepage node (Node 23)
$node = Node::load(23);

if (!$node) {
  echo "❌ ERROR: Homepage node (23) not found!\n";
  exit(1);
}

// Get current body
$current_body = $node->body->value;

// Add JavaScript to dynamically fix links based on user role
$role_based_js = <<<'JAVASCRIPT'

<script>
/**
 * Role-Based Link Updater for Homepage
 * Updates Quick Access card links based on user's role
 */
(function() {
  'use strict';
  
  // Wait for DOM to be ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateLinksBasedOnRole);
  } else {
    updateLinksBasedOnRole();
  }
  
  function updateLinksBasedOnRole() {
    // Check if user is admin or manager by checking if they can access certain pages
    // We'll check the body classes that Drupal adds
    const bodyClasses = document.body.className;
    
    // Check if user has admin/manager role
    // Drupal adds role classes like: role--administrator, role--sales-manager
    const isAdmin = bodyClasses.includes('role--administrator') || 
                    bodyClasses.includes('role--sales-manager');
    
    console.log('🔍 Role detection:', isAdmin ? 'Admin/Manager' : 'Sales Rep');
    
    if (!isAdmin) {
      // Sales rep - no changes needed, links already point to "My" pages
      console.log('✅ Sales Rep detected - keeping "My" links');
      return;
    }
    
    // Admin/Manager - update links to "All" pages
    console.log('🔧 Admin/Manager detected - updating to "All" links');
    
    const linkMap = {
      '/crm/my-contacts': '/crm/all-contacts',
      '/crm/my-deals': '/crm/all-deals',
      '/crm/my-activities': '/crm/all-activities',
      '/crm/my-organizations': '/crm/all-organizations'
    };
    
    // Update all links on the page
    document.querySelectorAll('a').forEach(function(link) {
      const href = link.getAttribute('href');
      
      if (href && linkMap[href]) {
        const oldHref = href;
        link.setAttribute('href', linkMap[href]);
        console.log('✅ Updated:', oldHref, '→', linkMap[href]);
        
        // Also update the card title if it says "My"
        const titleElement = link.querySelector('h3');
        if (titleElement) {
          const titleText = titleElement.textContent;
          if (titleText.includes('của tôi')) {
            // Replace "của tôi" with "Tất cả"
            titleElement.textContent = titleText.replace('của tôi', 'Tất cả');
          }
          if (titleText.includes('My ')) {
            titleElement.textContent = titleText.replace('My ', 'All ');
          }
        }
        
        // Update description if needed
        const descElement = link.querySelector('p');
        if (descElement) {
          const descText = descElement.textContent;
          if (descText.includes('của bạn')) {
            descElement.textContent = descText.replace('của bạn', 'trong hệ thống');
          }
          if (descText.includes('your ')) {
            descElement.textContent = descText.replace('your ', 'all ');
          }
        }
      }
    });
    
    console.log('✅ Homepage links updated for Admin/Manager role');
  }
})();
</script>
JAVASCRIPT;

// Check if JS already exists
if (strpos($current_body, 'updateLinksBasedOnRole') !== false) {
  echo "⚠️  Role-based JS already exists in homepage\n";
  echo "Updating it...\n";
  
  // Remove old version and add new one
  $current_body = preg_replace(
    '/<script>[\s\S]*?updateLinksBasedOnRole[\s\S]*?<\/script>/',
    '',
    $current_body
  );
}

// Add the JS before closing body tag or at the end
if (strpos($current_body, '</script>') !== false) {
  // Add after the last script tag
  $current_body = preg_replace(
    '/(<\/script>)(?![\s\S]*<\/script>)/',
    "$1\n" . $role_based_js,
    $current_body
  );
} else {
  // Just append at the end
  $current_body .= "\n" . $role_based_js;
}

// Update the node
$node->body->value = $current_body;
$node->body->format = 'full_html';
$node->save();

echo "✅ SUCCESS: Homepage updated with role-based link system!\n\n";
echo "How it works:\n";
echo "─────────────\n";
echo "1. Detects user role from body classes (Drupal adds: role--administrator, role--sales-manager)\n";
echo "2. For Admin/Manager:\n";
echo "   - Automatically changes /crm/my-contacts → /crm/all-contacts\n";
echo "   - Automatically changes /crm/my-deals → /crm/all-deals\n";
echo "   - Automatically changes /crm/my-activities → /crm/all-activities\n";
echo "   - Automatically changes /crm/my-organizations → /crm/all-organizations\n";
echo "   - Updates card titles and descriptions\n";
echo "3. For Sales Rep:\n";
echo "   - Keeps original 'My' links unchanged\n\n";

echo "🌐 Test URLs:\n";
echo "─────────────\n";
echo "Homepage: http://open-crm.ddev.site/\n";
echo "Login as admin and check the Quick Access cards!\n\n";

echo "💡 TIP: Clear browser cache (Cmd+Shift+R) to see changes!\n";
