<?php

namespace Drupal\crm_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for CRM Import page.
 */
class ImportController extends ControllerBase {

  /**
   * Display the import page.
   */
  public function importPage() {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Import Data - CRM</title>
  <link rel="icon" type="image/x-icon" href="/core/misc/favicon.ico">
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
      min-height: 100vh;
      color: #1e293b;
    }
    
    .import-header {
      background: white;
      border-bottom: 1px solid #e2e8f0;
      padding: 20px 24px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .import-header h1 {
      font-size: 24px;
      font-weight: 600;
      color: #1e293b;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .import-header p {
      color: #64748b;
      font-size: 14px;
      margin-top: 8px;
      margin-left: 40px;
    }
    
    .import-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 32px 24px;
    }
    
    .import-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
      gap: 24px;
      margin-bottom: 32px;
    }
    
    .import-card {
      background: white;
      border-radius: 12px;
      padding: 32px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      border: 1px solid #e2e8f0;
      transition: all 0.2s ease;
    }
    
    .import-card:hover {
      box-shadow: 0 4px 12px rgba(0,0,0,0.12);
      border-color: #3b82f6;
    }
    
    .import-card-header {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 20px;
    }
    
    .import-icon {
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .import-icon.blue {
      background: #eff6ff;
      color: #3b82f6;
    }
    
    .import-icon.purple {
      background: #f5f3ff;
      color: #8b5cf6;
    }
    
    .import-card-title {
      flex: 1;
    }
    
    .import-card-title h2 {
      font-size: 20px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }
    
    .import-card-title p {
      font-size: 13px;
      color: #64748b;
    }
    
    .import-card-body {
      margin-bottom: 24px;
    }
    
    .field-list {
      background: #f8fafc;
      border-radius: 8px;
      padding: 16px;
      margin-bottom: 16px;
    }
    
    .field-list h4 {
      font-size: 13px;
      font-weight: 600;
      color: #475569;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .field-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    
    .field-tag {
      background: white;
      border: 1px solid #e2e8f0;
      color: #64748b;
      padding: 4px 12px;
      border-radius: 6px;
      font-size: 13px;
      font-family: 'Monaco', 'Courier New', monospace;
    }
    
    .field-tag:before {
      content: '•';
      color: #10b981;
      margin-right: 6px;
      font-weight: bold;
    }
    
    .import-actions {
      display: flex;
      gap: 12px;
    }
    
    .btn {
      flex: 1;
      padding: 12px 20px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
      cursor: pointer;
      border: none;
    }
    
    .btn-primary {
      background: #3b82f6;
      color: white;
    }
    
    .btn-primary:hover {
      background: #2563eb;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    
    .btn-secondary {
      background: #f1f5f9;
      color: #475569;
      border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
      background: #e2e8f0;
      border-color: #cbd5e1;
    }
    
    .info-box {
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 12px;
      padding: 24px;
      margin-top: 32px;
    }
    
    .info-box-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }
    
    .info-box-header i {
      color: #3b82f6;
    }
    
    .info-box-header h3 {
      font-size: 16px;
      font-weight: 600;
      color: #1e40af;
    }
    
    .info-box ul {
      list-style: none;
      color: #1e40af;
      font-size: 14px;
      line-height: 1.8;
    }
    
    .info-box li:before {
      content: '✓';
      color: #10b981;
      font-weight: bold;
      margin-right: 8px;
    }
  </style>
</head>
<body>
  <div class="import-header">
    <h1>
      <i data-lucide="upload" width="28" height="28" style="color: #3b82f6;"></i>
      Import Data
    </h1>
    <p>Bulk import contacts and organizations from CSV files</p>
  </div>
  
  <div class="import-container">
    <div class="import-grid">
      <!-- Contacts Import -->
      <div class="import-card">
        <div class="import-card-header">
          <div class="import-icon blue">
            <i data-lucide="users" width="28" height="28"></i>
          </div>
          <div class="import-card-title">
            <h2>Import Contacts</h2>
            <p>Upload CSV file to import contacts in bulk</p>
          </div>
        </div>
        
        <div class="import-card-body">
          <div class="field-list">
            <h4>Required CSV Columns</h4>
            <div class="field-tags">
              <span class="field-tag">name</span>
              <span class="field-tag">email</span>
              <span class="field-tag">phone</span>
              <span class="field-tag">position</span>
            </div>
          </div>
        </div>
        
        <div class="import-actions">
          <a href="/feed/add/contact_csv_import" class="btn btn-primary">
            <i data-lucide="upload" width="16" height="16"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/contacts_template.csv" class="btn btn-secondary" download>
            <i data-lucide="download" width="16" height="16"></i>
            Download Template
          </a>
        </div>
      </div>
      
      <!-- Organizations Import -->
      <div class="import-card">
        <div class="import-card-header">
          <div class="import-icon purple">
            <i data-lucide="building-2" width="28" height="28"></i>
          </div>
          <div class="import-card-title">
            <h2>Import Organizations</h2>
            <p>Upload CSV file to import organizations in bulk</p>
          </div>
        </div>
        
        <div class="import-card-body">
          <div class="field-list">
            <h4>Required CSV Columns</h4>
            <div class="field-tags">
              <span class="field-tag">name</span>
              <span class="field-tag">website</span>
              <span class="field-tag">industry</span>
              <span class="field-tag">address</span>
              <span class="field-tag">status</span>
            </div>
          </div>
        </div>
        
        <div class="import-actions">
          <a href="/feed/add/organization_csv_import" class="btn btn-primary">
            <i data-lucide="upload" width="16" height="16"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/organizations_template.csv" class="btn btn-secondary" download>
            <i data-lucide="download" width="16" height="16"></i>
            Download Template
          </a>
        </div>
      </div>
    </div>
    
    <div class="info-box">
      <div class="info-box-header">
        <i data-lucide="info" width="24" height="24"></i>
        <h3>How to Import</h3>
      </div>
      <ul>
        <li>Download the CSV template for the entity type you want to import</li>
        <li>Fill in your data following the template format (keep the header row)</li>
        <li>Click "Start Import" and upload your CSV file</li>
        <li>Review the mapping and click "Import" to begin</li>
        <li>Wait for the import to complete and check the results</li>
      </ul>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>
</body>
</html>
HTML;

    return new Response($html);
  }

}
