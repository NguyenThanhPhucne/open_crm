<?php

namespace Drupal\crm_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Controller for Import pages.
 */
class ImportController extends ControllerBase {

  /**
   * Main import page - hub for all import operations.
   */
  public function importPage() {
    $build = [];

    // Header with gradient style
    $build['header'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['import-header'],
        'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; color: white;'
      ],
      '#value' => '<h1 style="margin: 0; font-size: 2rem;">📥 Import Data</h1><p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Import contacts, deals, and other CRM data from CSV/Excel files</p>',
    ];

    // Import options container
    $build['import_options'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['import-options-grid'],
        'style' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;'
      ],
    ];

    // Import Contacts option
    $build['import_options']['contacts'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['import-card'],
        'style' => 'background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; transition: all 0.3s; cursor: pointer;',
      ],
    ];
    
    $contacts_url = Url::fromRoute('crm_import_export.import_contacts');
    $build['import_options']['contacts']['icon'] = [
      '#markup' => '<div style="font-size: 3rem; margin-bottom: 1rem;">👥</div>',
    ];
    $build['import_options']['contacts']['title'] = [
      '#markup' => '<h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">Import Contacts</h3>',
    ];
    $build['import_options']['contacts']['description'] = [
      '#markup' => '<p style="color: #6b7280; margin: 0 0 1rem 0;">Import customer and lead data from CSV files. Supports duplicate detection and field mapping.</p>',
    ];
    $build['import_options']['contacts']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Import Contacts →'),
      '#url' => $contacts_url,
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'display: inline-block; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;',
      ],
    ];

    // Import Deals option
    $build['import_options']['deals'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['import-card'],
        'style' => 'background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 1.5rem; transition: all 0.3s;',
      ],
    ];
    
    $deals_url = Url::fromRoute('crm_import_export.import_deals');
    $build['import_options']['deals']['icon'] = [
      '#markup' => '<div style="font-size: 3rem; margin-bottom: 1rem;">💰</div>',
    ];
    $build['import_options']['deals']['title'] = [
      '#markup' => '<h3 style="margin: 0 0 0.5rem 0; font-size: 1.25rem;">Import Deals</h3>',
    ];
    $build['import_options']['deals']['description'] = [
      '#markup' => '<p style="color: #6b7280; margin: 0 0 1rem 0;">Import deal pipeline data with amounts, stages, and related contacts.</p>',
    ];
    $build['import_options']['deals']['link'] = [
      '#type' => 'link',
      '#title' => $this->t('Import Deals →'),
      '#url' => $deals_url,
      '#attributes' => [
        'class' => ['button', 'button--primary'],
        'style' => 'display: inline-block; padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600;',
      ],
    ];

    // Help section
    $build['help'] = [
      '#type' => 'details',
      '#title' => $this->t('📖 Import Guidelines'),
      '#open' => FALSE,
      '#attributes' => [
        'style' => 'background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; margin-top: 2rem;'
      ],
    ];

    $build['help']['content'] = [
      '#markup' => '
        <div style="color: #374151;">
          <h4 style="margin-top: 0;">CSV File Format Requirements:</h4>
          <ul>
            <li><strong>Encoding:</strong> UTF-8 (recommended)</li>
            <li><strong>Delimiter:</strong> Comma (,) or semicolon (;)</li>
            <li><strong>First row:</strong> Must contain column headers</li>
            <li><strong>File size:</strong> Maximum 10MB per file</li>
          </ul>
          
          <h4>Contacts CSV Example:</h4>
          <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto;">
Name,Email,Phone,Position,Organization
John Doe,john@example.com,0901234567,Sales Director,ABC Company
Jane Smith,jane@example.com,0987654321,CEO,XYZ Corporation
          </pre>
          
          <h4>Deals CSV Example:</h4>
          <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto;">
Title,Amount,Contact Email,Stage,Closing Date
New Website Project,50000,john@example.com,Proposal,2026-03-30
ERP System,150000,jane@example.com,Negotiation,2026-04-15
          </pre>
          
          <h4>Tips:</h4>
          <ul>
            <li>✓ Use template files for correct column names</li>
            <li>✓ Duplicate detection is based on email (contacts) or title (deals)</li>
            <li>✓ Large files will be processed in batches</li>
            <li>✓ You can preview data before final import</li>
          </ul>
        </div>
      ',
    ];

    // Recent imports (if we want to track this later)
    $build['recent'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'margin-top: 2rem; padding: 1.5rem; background: white; border-radius: 8px; border: 1px solid #e5e7eb;'
      ],
    ];
    $build['recent']['title'] = [
      '#markup' => '<h3 style="margin-top: 0;">📊 Quick Stats</h3>',
    ];

    // Get current user
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();

    // Get entity counts for current user only
    $contact_count = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    
    $deal_count = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['recent']['stats'] = [
      '#markup' => "
        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;'>
          <div style='padding: 1rem; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white;'>
            <div style='font-size: 2rem; font-weight: bold;'>{$contact_count}</div>
            <div style='opacity: 0.9;'>Total Contacts</div>
          </div>
          <div style='padding: 1rem; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; color: white;'>
            <div style='font-size: 2rem; font-weight: bold;'>{$deal_count}</div>
            <div style='opacity: 0.9;'>Total Deals</div>
          </div>
        </div>
      ",
    ];

    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $build;
  }

}
