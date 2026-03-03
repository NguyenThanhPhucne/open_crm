<?php

/**
 * Update Login Banner to New Professional Style
 * Run with: ddev drush scr scripts/update_banner_style.php
 */

use Drupal\node\Entity\Node;

echo "🎨 CẬP NHẬT BANNER STYLE MỚI\n";
echo "====================================\n\n";

// Load homepage node
$node = Node::load(23);

if (!$node) {
  echo "❌ Không tìm thấy homepage (node 23)\n";
  exit(1);
}

echo "📄 Homepage: " . $node->getTitle() . "\n";

$current_body = $node->body->value;

// Remove old banner completely
if (stripos($current_body, 'crm-login-banner') !== FALSE) {
  echo "🗑️  Xóa banner cũ...\n";
  // Find the start
  $start_pos = strpos($current_body, '<!-- Login Section for Anonymous Users -->');
  if ($start_pos !== FALSE) {
    // Find the end - look for the closing script tag and div
    $search_from = $start_pos;
    $end_marker = '</script>';
    $end_pos = strpos($current_body, $end_marker, $search_from);
    
    if ($end_pos !== FALSE) {
      // Include the closing tag
      $end_pos += strlen($end_marker);
      // Remove this entire section
      $before = substr($current_body, 0, $start_pos);
      $after = substr($current_body, $end_pos);
      $current_body = $before . $after;
      echo "✅ Đã xóa banner cũ\n";
    }
  }
}

// Add new professional banner
$new_banner = <<<'HTML'
<!-- Login Section for Anonymous Users -->
<div class="crm-login-banner" style="display: none;">
  <style>
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .crm-login-banner {
      background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);
      border: 1px solid #e8eaed;
      border-radius: 12px;
      padding: 28px 36px;
      margin: 0 0 28px 0;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      animation: slideDown 0.5s ease-out;
      display: flex !important;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      flex-wrap: wrap;
      position: relative;
      overflow: hidden;
    }
    
    .crm-login-banner::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: linear-gradient(to bottom, #1877f2 0%, #4285f4 100%);
    }
    
    .crm-login-banner .login-text {
      flex: 1;
      min-width: 280px;
    }
    
    .crm-login-banner h2 {
      margin: 0 0 8px 0;
      font-size: 22px;
      font-weight: 600;
      color: #202124;
      letter-spacing: -0.3px;
    }
    
    .crm-login-banner p {
      margin: 0;
      font-size: 14px;
      color: #5f6368;
      line-height: 1.6;
    }
    
    .crm-login-banner .login-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      align-items: center;
    }
    
    .crm-login-banner .btn-login,
    .crm-login-banner .btn-register {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 24px;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
      border: 1px solid transparent;
    }
    
    .crm-login-banner .btn-login {
      background: #1877f2;
      color: white;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .crm-login-banner .btn-login:hover {
      background: #166fe5;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }
    
    .crm-login-banner .btn-register {
      background: white;
      color: #5f6368;
      border: 1px solid #dadce0;
    }
    
    .crm-login-banner .btn-register:hover {
      background: #f8f9fa;
      border-color: #d1d3d6;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }
    
    @media (max-width: 768px) {
      .crm-login-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 28px;
        gap: 20px;
      }
      
      .crm-login-banner .login-actions {
        width: 100%;
      }
      
      .crm-login-banner .btn-login,
      .crm-login-banner .btn-register {
        flex: 1;
        justify-content: center;
        min-height: 40px;
      }
    }
    
    /* Hide banner if user is logged in */
    body.user-logged-in .crm-login-banner {
      display: none !important;
    }
  </style>
  
  <div class="login-text">
    <h2>Welcome to Open CRM</h2>
    <p>Quản lý khách hàng, deals và hoạt động kinh doanh một cách chuyên nghiệp. Đăng nhập để bắt đầu!</p>
  </div>
  
  <div class="login-actions">
    <a href="/user/login" class="btn-login">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
        <polyline points="10 17 15 12 10 7"></polyline>
        <line x1="15" y1="12" x2="3" y2="12"></line>
      </svg>
      Đăng nhập
    </a>
    <a href="/register" class="btn-register">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
        <circle cx="9" cy="7" r="4"></circle>
        <line x1="19" y1="8" x2="19" y2="14"></line>
        <line x1="22" y1="11" x2="16" y2="11"></line>
      </svg>
      Đăng ký
    </a>
  </div>
  
  <script>
    // Show banner only for anonymous users
    document.addEventListener('DOMContentLoaded', function() {
      var loginBanner = document.querySelector('.crm-login-banner');
      if (loginBanner && !document.body.classList.contains('user-logged-in')) {
        loginBanner.style.display = 'flex';
      }
    });
  </script>
</div>

HTML;

// Add banner at the beginning of content
$new_body = $new_banner . "\n\n" . trim($current_body);

// Update node
$node->body->value = $new_body;
$node->body->format = 'full_html';
$node->save();

echo "✅ Đã cập nhật banner style mới!\n";
echo "🎨 Design: Professional (giống Facebook/Google)\n";
echo "📊 Màu sắc: Xám nhạt/trắng với accent xanh\n";
echo "🔄 Refresh trang để xem thay đổi!\n";
