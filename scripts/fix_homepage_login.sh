#!/bin/bash

echo "🔐 TẠO TRANG CHỦ VỚI NÚT LOG IN RÕ RÀNG"
echo "========================================="
echo ""

echo "📝 Cập nhật homepage với nút đăng nhập..."
ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Welcome to Open CRM', 'type' => 'page']);

if (\$node = reset(\$nodes)) {
  \$node->set('body', [
    'value' => '
<style>
/* Login Page Styling */
.crm-login-page {
  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
  padding: 60px 24px;
  text-align: center;
}

.login-hero {
  margin-bottom: 60px;
}

.login-hero h1 {
  color: #0f172a;
  font-size: 48px;
  font-weight: 700;
  margin: 0 0 16px 0;
  letter-spacing: -1px;
}

.login-hero p {
  color: #64748b;
  font-size: 20px;
  margin: 0 0 40px 0;
  line-height: 1.6;
}

.login-button {
  display: inline-block;
  padding: 16px 48px;
  background: #0066cc;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-size: 18px;
  font-weight: 600;
  transition: all 0.2s ease;
  box-shadow: 0 4px 6px -1px rgba(0, 102, 204, 0.2);
  margin-bottom: 40px;
}

.login-button:hover {
  background: #0052a3;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 10px 15px -3px rgba(0, 102, 204, 0.3);
}

.demo-accounts {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 40px;
  margin-bottom: 40px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.demo-accounts h2 {
  color: #0f172a;
  font-size: 24px;
  font-weight: 600;
  margin: 0 0 32px 0;
}

.accounts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
  text-align: left;
}

.account-card {
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  padding: 24px;
  transition: all 0.2s ease;
}

.account-card:hover {
  border-color: #0066cc;
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0, 102, 204, 0.1);
}

.account-card h3 {
  color: #0066cc;
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 16px 0;
}

.account-info {
  font-size: 14px;
  line-height: 1.8;
  color: #475569;
}

.account-info strong {
  color: #1e293b;
  display: inline-block;
  min-width: 90px;
}

.account-info code {
  background: white;
  padding: 2px 8px;
  border-radius: 4px;
  font-family: \"SF Mono\", Monaco, monospace;
  color: #0066cc;
  font-size: 13px;
  border: 1px solid #e2e8f0;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 24px;
  margin-top: 40px;
}

.feature-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 24px;
  text-align: left;
}

.feature-card h3 {
  color: #0f172a;
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 8px 0;
}

.feature-card p {
  color: #64748b;
  font-size: 14px;
  margin: 0;
  line-height: 1.6;
}

.quick-login {
  background: #fef3c7;
  border: 2px solid #fbbf24;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 40px;
  text-align: center;
}

.quick-login p {
  color: #78350f;
  font-size: 16px;
  font-weight: 500;
  margin: 0;
}

@media (max-width: 768px) {
  .login-hero h1 {
    font-size: 36px;
  }
  
  .accounts-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class=\"crm-login-page\">
  <div class=\"login-hero\">
    <h1>🚀 Open CRM</h1>
    <p>Professional Customer Relationship Management System<br>Built with Drupal 11 + Modern UI</p>
    
    <a href=\"/user/login\" class=\"login-button\">🔐 Login to Dashboard</a>
  </div>

  <div class=\"quick-login\">
    <p>⚡ Quick Start: Use <code>salesrep1</code> / <code>sales123</code> to see the system immediately!</p>
  </div>

  <div class=\"demo-accounts\">
    <h2>Demo Accounts (For Testing)</h2>
    <div class=\"accounts-grid\">
      <div class=\"account-card\">
        <h3>👨‍💼 Admin</h3>
        <div class=\"account-info\">
          <div><strong>Username:</strong> <code>admin</code></div>
          <div><strong>Password:</strong> <code>aa5BLB69Jt</code></div>
          <div><strong>Access:</strong> Full system control</div>
        </div>
      </div>

      <div class=\"account-card\">
        <h3>👔 Sales Manager</h3>
        <div class=\"account-info\">
          <div><strong>Username:</strong> <code>manager</code></div>
          <div><strong>Password:</strong> <code>manager123</code></div>
          <div><strong>Access:</strong> View all team data</div>
        </div>
      </div>

      <div class=\"account-card\">
        <h3>👤 Sales Rep 1</h3>
        <div class=\"account-info\">
          <div><strong>Username:</strong> <code>salesrep1</code></div>
          <div><strong>Password:</strong> <code>sales123</code></div>
          <div><strong>Access:</strong> Own data only</div>
        </div>
      </div>

      <div class=\"account-card\">
        <h3>👤 Sales Rep 2</h3>
        <div class=\"account-info\">
          <div><strong>Username:</strong> <code>salesrep2</code></div>
          <div><strong>Password:</strong> <code>sales123</code></div>
          <div><strong>Access:</strong> Own data only</div>
        </div>
      </div>
    </div>
  </div>

  <div class=\"features-grid\">
    <div class=\"feature-card\">
      <h3>📊 Dashboard</h3>
      <p>Real-time stats with modern Tailwind-style cards</p>
    </div>

    <div class=\"feature-card\">
      <h3>👥 Contacts</h3>
      <p>Manage customer relationships with data privacy</p>
    </div>

    <div class=\"feature-card\">
      <h3>💼 Pipeline</h3>
      <p>Track deals across stages with grouped tables</p>
    </div>

    <div class=\"feature-card\">
      <h3>📅 Activities</h3>
      <p>Log calls, meetings, and interactions</p>
    </div>
  </div>
</div>
',
    'format' => 'full_html',
  ]);
  \$node->save();
  echo '✅ Homepage đã được cập nhật với nút Login rõ ràng' . PHP_EOL;
}
"

echo ""
echo "🧹 Clear cache..."
ddev drush cr

echo ""
echo "✨ HOÀN THÀNH!"
echo ""
echo "📍 BÂY GIỜ TRUY CẬP:"
echo "   http://open-crm.ddev.site"
echo ""
echo "   Bạn sẽ thấy:"
echo "   ✅ Nút 'Login to Dashboard' lớn màu xanh"
echo "   ✅ Bảng Demo Accounts với 4 tài khoản"
echo "   ✅ Username/Password hiển thị rõ ràng"
echo "   ✅ Quick Start guide: salesrep1 / sales123"
echo ""
echo "🔐 CÁCH ĐĂNG NHẬP:"
echo "   1. Click nút 'Login to Dashboard'"
echo "   2. Nhập: salesrep1"
echo "   3. Nhập: sales123"
echo "   4. Click 'Log in'"
echo "   5. Enjoy! 🎉"
