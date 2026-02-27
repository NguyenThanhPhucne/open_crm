#!/bin/bash

echo "🚀 TẠO QUICK ACCESS PAGE"
echo "========================"
echo ""

# Tạo trang Quick Access đơn giản
echo "📄 Tạo trang Quick Access..."
ddev drush eval '
use Drupal\node\Entity\Node;

$old_nodes = \Drupal::entityTypeManager()
  ->getStorage("node")
  ->loadByProperties(["title" => "Quick Access - CRM", "type" => "page"]);
foreach ($old_nodes as $old_node) {
  $old_node->delete();
}

$html = "
<style>
.crm-quick-access {
  font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}
.crm-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 2rem;
}
.crm-card {
  display: block;
  padding: 24px;
  border-radius: 12px;
  text-decoration: none;
  color: white;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  transition: transform 0.2s;
}
.crm-card:hover {
  transform: translateY(-4px);
}
.card-icon { font-size: 2rem; margin-bottom: 8px; }
.card-title { margin: 0 0 8px 0; font-size: 1.3rem; }
.card-desc { margin: 0; opacity: 0.9; font-size: 0.9rem; }
.bg-purple { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.bg-pink { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.bg-blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.bg-orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.bg-teal { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
.bg-light { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #1a202c; }
.info-box {
  background: #f7fafc;
  border-left: 4px solid #4299e1;
  padding: 20px;
  border-radius: 8px;
  margin-top: 2rem;
}
.login-btn {
  display: inline-block;
  padding: 12px 32px;
  background: #4299e1;
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 1.1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  margin-top: 2rem;
}
</style>

<div class=\"crm-quick-access\">
  <h1 style=\"font-size: 2.5rem; margin-bottom: 1rem; color: #1a202c;\">🚀 Open CRM - Quick Access</h1>
  <p style=\"font-size: 1.1rem; color: #718096; margin-bottom: 2rem;\">Truy cập nhanh các trang quan trọng của hệ thống CRM</p>
  
  <div class=\"crm-grid\">
    <a href=\"/crm/home\" class=\"crm-card bg-purple\">
      <div class=\"card-icon\">🏠</div>
      <h3 class=\"card-title\">Dashboard</h3>
      <p class=\"card-desc\">Tổng quan hệ thống</p>
    </a>
    
    <a href=\"/crm/my-contacts\" class=\"crm-card bg-pink\">
      <div class=\"card-icon\">👥</div>
      <h3 class=\"card-title\">My Contacts</h3>
      <p class=\"card-desc\">Danh sách khách hàng</p>
    </a>
    
    <a href=\"/crm/my-pipeline\" class=\"crm-card bg-blue\">
      <div class=\"card-icon\">💼</div>
      <h3 class=\"card-title\">My Pipeline</h3>
      <p class=\"card-desc\">Bảng kanban deals</p>
    </a>
    
    <a href=\"/crm/my-activities\" class=\"crm-card bg-orange\">
      <div class=\"card-icon\">📅</div>
      <h3 class=\"card-title\">My Activities</h3>
      <p class=\"card-desc\">Hoạt động của tôi</p>
    </a>
    
    <a href=\"/crm/my-organizations\" class=\"crm-card bg-teal\">
      <div class=\"card-icon\">🏢</div>
      <h3 class=\"card-title\">My Organizations</h3>
      <p class=\"card-desc\">Công ty của tôi</p>
    </a>
    
    <a href=\"/admin/content\" class=\"crm-card bg-light\">
      <div class=\"card-icon\">📊</div>
      <h3 class=\"card-title\">All Content</h3>
      <p class=\"card-desc\">Quản lý nội dung</p>
    </a>
  </div>
  
  <div class=\"info-box\">
    <h3 style=\"margin: 0 0 12px 0; color: #2d3748;\">ℹ️ Thông tin đăng nhập</h3>
    <p><strong>Admin:</strong> admin / admin</p>
    <p><strong>Manager:</strong> manager / manager123</p>
    <p><strong>Sales Rep 1:</strong> salesrep1 / sales123</p>
    <p><strong>Sales Rep 2:</strong> salesrep2 / sales123</p>
  </div>
  
  <div style=\"text-align: center;\">
    <a href=\"/user/login\" class=\"login-btn\">🔐 Đăng nhập ngay</a>
  </div>
</div>
";

$node = Node::create([
  "type" => "page",
  "title" => "Quick Access - CRM",
  "body" => [
    "value" => $html,
    "format" => "full_html",
  ],
  "status" => 1,
  "promote" => 0,
  "sticky" => 0,
  "uid" => 1,
]);
$node->save();

echo "✅ Đã tạo Quick Access page (Node ID: " . $node->id() . ")" . PHP_EOL;
echo "   URL: /node/" . $node->id() . PHP_EOL;
'

echo ""
echo "📍 Cập nhật homepage..."
NODE_ID=$(ddev drush eval "
\$nodes = \Drupal::entityTypeManager()
  ->getStorage('node')
  ->loadByProperties(['title' => 'Quick Access - CRM']);
\$node = reset(\$nodes);
echo \$node->id();
")

ddev drush config:set system.site page.front /node/$NODE_ID -y

echo ""
echo "🧹 Clear cache..."
ddev drush cr

echo ""
echo "✨ HOÀN THÀNH!"
echo ""
echo "📌 Truy cập: http://open-crm.ddev.site"
echo "   Bạn sẽ thấy trang Quick Access với 6 cards màu sắc đẹp"
echo ""
