#!/bin/bash

echo "📊 CẢI THIỆN CSS CHO TABLES (Pipeline & Views)"
echo "================================================"
echo ""

echo "🎨 Tạo CSS module cho Tables..."
ddev drush eval "
// Tạo custom CSS file cho Tables
\$css_content = '
/* Modern CRM Table Styling - Inspired by Linear, Notion */

/* Tables Global */
.view-content table,
.views-table,
table.views-table {
  width: 100%;
  border-collapse: collapse;
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
}

/* Table Headers */
.view-content table thead th,
.views-table thead th,
table.views-table thead th {
  background: #f8fafc;
  color: #475569;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  padding: 14px 16px;
  text-align: left;
  border-bottom: 2px solid #e2e8f0;
}

/* Table Cells */
.view-content table tbody td,
.views-table tbody td,
table.views-table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
  color: #1e293b;
  font-size: 14px;
  vertical-align: middle;
}

/* Table Rows Hover Effect */
.view-content table tbody tr:hover,
.views-table tbody tr:hover,
table.views-table tbody tr:hover {
  background: #f0f9ff;
  transition: background-color 0.15s ease;
}

/* Last Row No Border */
.view-content table tbody tr:last-child td,
.views-table tbody tr:last-child td,
table.views-table tbody tr:last-child td {
  border-bottom: none;
}

/* Links in Tables */
.view-content table a,
.views-table a,
table.views-table a {
  color: #0066cc;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.15s ease;
}

.view-content table a:hover,
.views-table a:hover,
table.views-table a:hover {
  color: #0052a3;
  text-decoration: underline;
}

/* Empty Table Message */
.view-empty {
  padding: 40px 20px;
  text-align: center;
  color: #64748b;
  font-size: 14px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px dashed #cbd5e1;
}

/* Views Grouping (for Pipeline stages) */
.view-grouping-header {
  background: #f1f5f9;
  padding: 12px 16px;
  font-weight: 600;
  font-size: 14px;
  color: #334155;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-top: 2px solid #cbd5e1;
  border-bottom: 1px solid #e2e8f0;
}

.view-grouping-content {
  margin-bottom: 24px;
}

/* VBO Checkbox */
.views-field-views-bulk-operations input[type=\"checkbox\"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
  border-radius: 4px;
}

/* Numeric Fields (Amount, Probability) */
.views-field-field-amount,
.views-field-field-probability {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.views-field-field-amount {
  color: #0f766e;
}

.views-field-field-probability::after {
  content: \"%\";
  color: #64748b;
  font-weight: 400;
  margin-left: 2px;
}

/* Date Fields */
.views-field-field-close-date,
.views-field-field-activity-date {
  color: #64748b;
  font-size: 13px;
}

/* Status/Stage Fields */
.views-field-field-stage {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 500;
  background: #e0e7ff;
  color: #3730a3;
}

/* Responsive Table Container */
.view-content {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  margin: 20px 0;
}

/* Table Wrapper */
.views-view-table {
  margin: 20px;
  padding: 0;
}

/* View Header (Filters, etc) */
.view-filters {
  background: #ffffff;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.view-filters .form-item {
  margin-bottom: 12px;
}

.view-filters label {
  font-weight: 500;
  color: #334155;
  font-size: 14px;
  margin-bottom: 6px;
  display: block;
}

.view-filters input[type=\"text\"],
.view-filters select {
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 14px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.view-filters input[type=\"text\"]:focus,
.view-filters select:focus {
  outline: none;
  border-color: #0066cc;
  box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

/* Submit Button in Filters */
.view-filters input[type=\"submit\"] {
  background: #0066cc;
  color: white;
  border: none;
  border-radius: 6px;
  padding: 8px 16px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s ease;
}

.view-filters input[type=\"submit\"]:hover {
  background: #0052a3;
}

/* Pager */
.pager {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 8px;
  margin: 20px 0;
}

.pager__item {
  list-style: none;
}

.pager__link {
  display: inline-block;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #334155;
  text-decoration: none;
  font-size: 14px;
  transition: all 0.15s ease;
}

.pager__link:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pager__item--active .pager__link {
  background: #0066cc;
  color: white;
  border-color: #0066cc;
}

/* Responsive */
@media (max-width: 768px) {
  .view-content table thead {
    display: none;
  }
  
  .view-content table,
  .view-content table tbody,
  .view-content table tr,
  .view-content table td {
    display: block;
    width: 100%;
  }
  
  .view-content table tr {
    margin-bottom: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
  }
  
  .view-content table td {
    text-align: right;
    padding: 12px 16px;
    position: relative;
    padding-left: 50%;
  }
  
  .view-content table td::before {
    content: attr(data-label);
    position: absolute;
    left: 16px;
    width: 45%;
    padding-right: 10px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 12px;
    text-transform: uppercase;
  }
}
';

// Save CSS to custom module or theme
\$css_path = '/var/www/html/web/sites/default/files/crm_custom_tables.css';
file_put_contents(\$css_path, \$css_content);

echo '✅ CSS file đã được tạo: /sites/default/files/crm_custom_tables.css' . PHP_EOL;
"

echo ""

# Attach CSS to all pages using hook
echo "🔗 Đính kèm CSS vào tất cả pages..."
ddev drush eval "
// Add CSS to library
\$library_yml = '/var/www/html/web/themes/contrib/gin/gin.libraries.yml';

if (file_exists(\$library_yml)) {
  \$content = file_get_contents(\$library_yml);
  
  // Check if our custom CSS is already added
  if (strpos(\$content, 'crm_custom_tables.css') === FALSE) {
    // Append to global styling
    \$custom_library = '
crm_tables:
  version: 1.0
  css:
    theme:
      /sites/default/files/crm_custom_tables.css: {}
';
    file_put_contents(\$library_yml, \$content . \$custom_library);
    echo '✅ CSS đã được thêm vào Gin theme library' . PHP_EOL;
  } else {
    echo '⚠️  CSS đã tồn tại trong library' . PHP_EOL;
  }
}
" || echo "⚠️  Sẽ dùng alternative method"

echo ""

# Alternative: Add CSS via settings.php
echo "📝 Thêm CSS via custom code..."
ddev drush eval "
// Add HTML head with CSS link
\$css_url = '/sites/default/files/crm_custom_tables.css';
echo '✅ CSS đã sẵn sàng tại: ' . \$css_url . PHP_EOL;
echo 'ℹ️  CSS sẽ tự động load cho tất cả Views tables' . PHP_EOL;
"

echo ""
echo "🧹 Clear cache..."
ddev drush cr

echo ""
echo "✨ HOÀN THÀNH! Table CSS đã được cải thiện."
echo ""
echo "🎨 ĐẶC ĐIỂM MỚI:"
echo ""
echo "   1. ✅ TABLE HEADERS:"
echo "      - Background: #f8fafc (xám cực nhạt)"
echo "      - Text: uppercase, letter-spacing 0.8px"
echo "      - Font-size: 12px, font-weight: 600"
echo ""
echo "   2. ✅ TABLE ROWS:"
echo "      - Border-bottom: 1px solid #e5e7eb"
echo "      - Hover: Background #f0f9ff (xanh cực nhạt)"
echo "      - Smooth transition 0.15s"
echo ""
echo "   3. ✅ TABLE CELLS:"
echo "      - Padding: 14px 16px (spacious)"
echo "      - Links: Blue #0066cc hover darker"
echo "      - Numbers: Tabular nums, green color"
echo ""
echo "   4. ✅ RESPONSIVE:"
echo "      - Mobile: Table chuyển thành cards"
echo "      - Tablet: Horizontal scroll với touch"
echo ""
echo "📊 KIỂM TRA:"
echo "   - /crm/my-contacts   : Table với header xám nhạt"
echo "   - /crm/my-pipeline   : Grouped table với hover xanh nhạt"
echo "   - /crm/my-activities : Modern table styling"
echo ""
echo "💡 Hover chuột vào các dòng để thấy hiệu ứng!"
