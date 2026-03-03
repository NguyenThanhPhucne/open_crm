<?php

/**
 * Add Login/Register Banner to Homepage
 */

use Drupal\node\Entity\Node;

echo "🔐 THÊM LOGIN/REGISTER VÀO HOMEPAGE\n";
echo "====================================\n\n";

// Load homepage node (node 23 - Quick Access)
$node = Node::load(23);

if (!$node) {
  echo "❌ Không tìm thấy homepage (node 23)\n";
  exit(1);
}

echo "📄 Homepage: " . $node->getTitle() . " (node/{$node->id()})\n\n";

$current_body = $node->body->value;

// Check if login banner already exists
if (stripos($current_body, 'crm-login-banner') !== FALSE) {
  echo "⚠️  Login banner đã tồn tại, đang cập nhật...\n";
  // Remove old banner
  $current_body = preg_replace('/<!-- Login Section for Anonymous Users -->.*?<\/div>\n\n/s', '', $current_body);
}

// Create login banner HTML
$login_banner = <<<'HTML'
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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 24px 32px;
      margin: -20px -20px 32px -20px;
      border-radius: 0 0 16px 16px;
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
      animation: slideDown 0.5s ease-out;
      display: flex !important;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      flex-wrap: wrap;
    }
    
    .crm-login-banner .login-text {
      flex: 1;
      min-width: 250px;
    }
    
    .crm-login-banner h2 {
      margin: 0 0 8px 0;
      font-size: 24px;
      font-weight: 700;
      color: white;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .crm-login-banner p {
      margin: 0;
      font-size: 15px;
      color: rgba(255, 255, 255, 0.9);
      line-height: 1.5;
    }
    
    .crm-login-banner .login-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    
    .crm-login-banner .btn-login,
    .crm-login-banner .btn-register {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 28px;
      border-radius: 10px;
      font-size: 15px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      white-space: nowrap;
    }
    
    .crm-login-banner .btn-login {
      background: white;
      color: #667eea;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }
    
    .crm-login-banner .btn-login:hover {
      background: #f8f9ff;
      color: #5568d3;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .crm-login-banner .btn-register {
      background: rgba(255, 255, 255, 0.15);
      color: white;
      border: 2px solid rgba(255, 255, 255, 0.5);
      backdrop-filter: blur(10px);
    }
    
    .crm-login-banner .btn-register:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    @media (max-width: 768px) {
      .crm-login-banner {
        flex-direction: column;
        align-items: flex-start;
        padding: 20px 24px;
      }
      
      .crm-login-banner .login-actions {
        width: 100%;
      }
      
      .crm-login-banner .btn-login,
      .crm-login-banner .btn-register {
        flex: 1;
        justify-content: center;
      }
    }
    
    /* Hide banner if user is logged in */
    body.user-logged-in .crm-login-banner {
      display: none !important;
    }
  </style>
  
  <div class="login-text">
    <h2>🚀 Welcome to Open CRM</h2>
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
    <a href="/user/register" class="btn-register">
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

// Add login banner at the beginning
$new_body = $login_banner . $current_body;

$node->set('body', [
  'value' => $new_body,
  'format' => 'full_html',
]);

$node->save();

echo "✅ Đã thêm login/register banner vào homepage!\n\n";
echo "📍 Trang chủ: http://open-crm.ddev.site/\n";
echo "🔗 Đăng nhập: http://open-crm.ddev.site/user/login\n";
echo "🔗 Đăng ký: http://open-crm.ddev.site/user/register\n\n";
echo "🎨 Features:\n";
echo "   • Banner gradient màu tím chuyên nghiệp\n";
echo "   • Slide-down animation khi load\n";
echo "   • 2 nút: Đăng nhập (trắng) + Đăng ký (trong suốt)\n";
echo "   • Tự động ẩn khi user đã login\n";
echo "   • Responsive design cho mobile\n";
echo "   • Smooth hover effects\n\n";
echo "✨ HOÀN THÀNH!\n";
