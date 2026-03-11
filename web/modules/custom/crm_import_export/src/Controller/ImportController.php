<?php

namespace Drupal\crm_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * Controller for Import pages.
 */
class ImportController extends ControllerBase {

  /**
   * Main import page - hub for all import operations.
   */
  public function importPage() {
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    
    $html = <<<HTML
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
      padding: 20px;
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
      flex-shrink: 0;
    }
    
    .import-icon.blue {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
    }
    
    .import-icon.purple {
      background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
      color: white;
    }
    
    .import-card-title h2 {
      font-size: 20px;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }
    
    .import-card-title p {
      font-size: 14px;
      color: #64748b;
    }
    
    .import-card-body {
      margin-bottom: 24px;
    }
    
    .field-list h4 {
      font-size: 14px;
      font-weight: 600;
      color: #475569;
      margin-bottom: 12px;
    }
    
    .field-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }
    
    .field-tag {
      background: #f1f5f9;
      color: #475569;
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 13px;
      font-family: 'Courier New', monospace;
      border: 1px solid #e2e8f0;
    }
    
    .field-list ul {
      list-style: none;
      color: #475569;
      font-size: 14px;
      line-height: 1.8;
    }
    
    .field-list li:before {
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
      transition: background .15s, border-color .15s, color .15s;
      cursor: pointer;
      border: 1.5px solid;
    }
    
    .btn-primary {
      background: #fff;
      color: #2563eb;
      border-color: #2563eb;
    }
    
    .btn-primary:hover {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #1d4ed8;
    }
    
    .btn-secondary {
      background: #fff;
      color: #64748b;
      border-color: #cbd5e1;
    }
    
    .btn-secondary:hover {
      background: #f8fafc;
      color: #475569;
      border-color: #94a3b8;
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
    
    @media (max-width: 1100px) {
      .import-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="import-header">
    <h1>
      <i data-lucide="upload" width="28" height="28" style="color: #3b82f6;"></i>
      Import Data
    </h1>
    <p>Bulk import contacts, deals, and other CRM data from CSV/Excel files</p>
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
            <p>Upload CSV file to import customer and lead data</p>
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
          <a href="/admin/crm/import/contacts" class="btn btn-primary">
            <i data-lucide="upload" width="16" height="16"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/contacts_template.csv" class="btn btn-secondary" download>
            <i data-lucide="download" width="16" height="16"></i>
            Download Template
          </a>
        </div>
      </div>
      
      <!-- Deals Import -->
      <div class="import-card">
        <div class="import-card-header">
          <div class="import-icon purple">
            <i data-lucide="trending-up" width="28" height="28"></i>
          </div>
          <div class="import-card-title">
            <h2>Import Deals</h2>
            <p>Upload CSV file to import deal pipeline data</p>
          </div>
        </div>
        
        <div class="import-card-body">
          <div class="field-list">
            <h4>Required CSV Columns</h4>
            <div class="field-tags">
              <span class="field-tag">title</span>
              <span class="field-tag">amount</span>
              <span class="field-tag">stage</span>
              <span class="field-tag">contact</span>
            </div>
          </div>
        </div>
        
        <div class="import-actions">
          <a href="/admin/crm/import/deals" class="btn btn-primary">
            <i data-lucide="upload" width="16" height="16"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/deals_template.csv" class="btn btn-secondary" download>
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
        <li>Review the field mapping and data preview</li>
        <li>Click "Import" to begin processing your data</li>
        <li>Wait for the import to complete and check the results</li>
      </ul>
    </div>
  </div>

  <script>
    lucide.createIcons();
  </script>
HTML;

    return [
      '#markup' => Markup::create($html),
      '#attached' => [
        'library' => [
          'core/drupal',
        ],
      ],
    ];
  }

}
