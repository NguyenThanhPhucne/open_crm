<?php

namespace Drupal\crm_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Drupal\node\Entity\Node;

/**
 * Controller for Export operations.
 */
class ExportController extends ControllerBase {

  /**
   * Main export page — premium hub for all export operations.
   */
  public function exportPage() {
    // Live stats.
    $contact_count = $this->getLiveCount('contact');
    $deal_count    = $this->getLiveCount('deal');
    $org_count     = $this->getLiveCount('organization');

    $html = <<<HTML
<div class="crm-import-hub"> <!-- Using same container class for shared CSS -->

  <!-- ── HEADER ── -->
  <div class="crm-import-header">
    <div class="crm-import-header-inner">
      <div class="crm-import-header-title">
        <div class="crm-icon-wrapper crm-icon-wrapper--magenta">
          <i data-lucide="download-cloud" width="28" height="28"></i>
        </div>
        <div>
          <h1>Export Data</h1>
          <p>Download your CRM data as CSV files for reporting and backups</p>
        </div>
      </div>
      <div class="crm-import-stats">
        <a href="/crm/all-contacts" class="crm-import-stat crm-import-stat--contacts" title="View contacts list">
          <i data-lucide="users" width="14" height="14"></i>
          <span><strong>{$contact_count}</strong> contacts</span>
        </a>
        <a href="/crm/all-deals" class="crm-import-stat crm-import-stat--deals" title="View deals list">
          <i data-lucide="briefcase" width="14" height="14"></i>
          <span><strong>{$deal_count}</strong> deals</span>
        </a>
        <a href="/crm/all-organizations" class="crm-import-stat crm-import-stat--organizations" title="View organizations list">
          <i data-lucide="building-2" width="14" height="14"></i>
          <span><strong>{$org_count}</strong> organizations</span>
        </a>
      </div>
    </div>
  </div>

  <!-- ── BODY ── -->
  <div class="crm-import-body">

    <!-- Export Cards -->
    <div class="crm-import-grid">

      <!-- CONTACTS -->
      <div class="crm-import-card crm-import-card--blue">
        <div class="crm-import-card__head">
          <div class="crm-import-card__icon crm-import-card__icon--blue">
            <i data-lucide="users" width="24" height="24"></i>
          </div>
          <div class="crm-import-card__title">
            <h3>Export Contacts</h3>
            <p>Download all customers and prospects data</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Included Fields</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag">name</span>
            <span class="crm-import-tag">email</span>
            <span class="crm-import-tag">phone</span>
            <span class="crm-import-tag">organization</span>
            <span class="crm-import-tag">owner</span>
            <span class="crm-import-tag">status</span>
            <span class="crm-import-tag">tags</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/export/contacts" class="btn-import btn-import--primary btn-import--contacts">
            <i data-lucide="download" width="15" height="15"></i>
            Start Export
          </a>
        </div>
      </div>

      <!-- DEALS -->
      <div class="crm-import-card crm-import-card--purple">
        <div class="crm-import-card__head">
          <div class="crm-import-card__icon crm-import-card__icon--purple">
            <i data-lucide="trending-up" width="24" height="24"></i>
          </div>
          <div class="crm-import-card__title">
            <h3>Export Deals</h3>
            <p>Download your sales pipeline and deal history</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Included Fields</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag">title</span>
            <span class="crm-import-tag">amount</span>
            <span class="crm-import-tag">stage</span>
            <span class="crm-import-tag">contact</span>
            <span class="crm-import-tag">owner</span>
            <span class="crm-import-tag">status</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/export/deals" class="btn-import btn-import--primary btn-import--deals">
            <i data-lucide="download" width="15" height="15"></i>
            Start Export
          </a>
        </div>
      </div>

      <!-- ORGANIZATIONS -->
      <div class="crm-import-card crm-import-card--teal">
        <div class="crm-import-card__head">
          <div class="crm-import-card__icon crm-import-card__icon--teal">
            <i data-lucide="building-2" width="24" height="24"></i>
          </div>
          <div class="crm-import-card__title">
            <h3>Export Organizations</h3>
            <p>Download all account and company info</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Included Fields</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag">name</span>
            <span class="crm-import-tag">website</span>
            <span class="crm-import-tag">industry</span>
            <span class="crm-import-tag">address</span>
            <span class="crm-import-tag">phone</span>
            <span class="crm-import-tag">status</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/export/organizations" class="btn-import btn-import--primary btn-import--organizations">
            <i data-lucide="download" width="15" height="15"></i>
            Start Export
          </a>
        </div>
      </div>

    </div><!-- /grid -->

    <!-- How to export guide -->
    <div class="crm-import-guide crm-export-border-pink">
      <h3>
        <i data-lucide="file-down" width="20" height="20" class="crm-export-color-slate"></i>
        Exporting your data
      </h3>
      <div class="crm-import-steps">
        <div class="crm-import-step">
          <div class="crm-import-step__num crm-export-bg-pink">1</div>
          <div class="crm-import-step__text"><strong>Select</strong> the entity type (Contacts, Deals, or Organizations) you want to export.</div>
        </div>
        <div class="crm-import-step">
          <div class="crm-import-step__num crm-export-bg-pink">2</div>
          <div class="crm-import-step__text"><strong>Click</strong> the "Start Export" button to generate a real-time CSV file.</div>
        </div>
        <div class="crm-import-step">
          <div class="crm-import-step__num crm-export-bg-pink">3</div>
          <div class="crm-import-step__text"><strong>Save</strong> the file to your computer. The export includes all primary fields and metadata.</div>
        </div>
      </div>
    </div>

  </div><!-- /body -->
</div><!-- /hub -->

<script>
if (typeof lucide !== 'undefined') { lucide.createIcons(); }
else {
  var s = document.createElement('script');
  s.src = 'https://unpkg.com/lucide@latest';
  s.onload = function() { lucide.createIcons(); };
  document.head.appendChild(s);
}
</script>
HTML;

    return [
      '#markup' => \Drupal\Core\Render\Markup::create($html),
      '#attached' => [
        'library' => [
          'crm_import_export/import_ui', // Shared CSS
        ],
      ],
      '#cache' => [
        'contexts' => ['user.roles'],
        'tags' => ['node_list:contact', 'node_list:deal', 'node_list:organization'],
        'max-age' => 300,
      ],
    ];
  }

  /**
   * Export contacts to CSV.
   */
  public function exportContacts() {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $see_all = $current_user->hasRole('administrator') || $user_id == 1 || $current_user->hasRole('sales_manager');

    $response = new StreamedResponse(function () use ($see_all, $user_id) {
      // Disable any output buffering so chunks stream immediately
      while (ob_get_level()) ob_end_clean();
      @ini_set('output_buffering', 'off');
      @ini_set('zlib.output_compression', 'off');

      $handle = fopen('php://output', 'w');
      // Add BOM to fix UTF-8 in Excel
      fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
      
      fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Position', 'Organization', 'Owner', 'Source', 'Customer Type', 'Status', 'Tags', 'LinkedIn', 'Last Contacted', 'Created Date', 'Modified Date']);

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'contact')
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');
      if (!$see_all) {
        $query->condition('field_owner', $user_id);
      }

      $nids = $query->execute();
      $chunks = array_chunk($nids, 50);

      foreach ($chunks as $chunk) {
        $contacts = Node::loadMultiple($chunk);
        foreach ($contacts as $contact) {
          $row = [
            $contact->id(),
            $contact->getTitle(),
            $contact->hasField('field_email') && !$contact->get('field_email')->isEmpty() ? $contact->get('field_email')->value : '',
            $contact->hasField('field_phone') && !$contact->get('field_phone')->isEmpty() ? $contact->get('field_phone')->value : '',
            $contact->hasField('field_position') && !$contact->get('field_position')->isEmpty() ? $contact->get('field_position')->value : '',
            ($contact->hasField('field_organization') && $contact->get('field_organization')->entity) ? $contact->get('field_organization')->entity->getTitle() : '',
            ($contact->hasField('field_owner') && $contact->get('field_owner')->entity) ? $contact->get('field_owner')->entity->getDisplayName() : '',
            ($contact->hasField('field_source') && $contact->get('field_source')->entity) ? $contact->get('field_source')->entity->getName() : '',
            ($contact->hasField('field_customer_type') && $contact->get('field_customer_type')->entity) ? $contact->get('field_customer_type')->entity->getName() : '',
            $contact->hasField('field_status') ? $contact->get('field_status')->value : '',
          ];
          $tags = [];
          if ($contact->hasField('field_tags')) {
            foreach ($contact->get('field_tags') as $tag) {
              if ($tag->entity) $tags[] = $tag->entity->getName();
            }
          }
          $row[] = implode(', ', $tags);
          $row[] = $contact->hasField('field_linkedin') ? $contact->get('field_linkedin')->uri : '';
          $row[] = $contact->hasField('field_last_contacted') ? $contact->get('field_last_contacted')->value : '';
          $row[] = date('Y-m-d H:i:s', $contact->getCreatedTime());
          $row[] = date('Y-m-d H:i:s', $contact->getChangedTime());

          fputcsv($handle, $row);
        }
        // Flush entity cache and push data to output stream after each chunk
        \Drupal::entityTypeManager()->getStorage('node')->resetCache($chunk);
        flush();
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="contacts_export_' . date('Y-m-d_His') . '.csv"');

    return $response;
  }

  /**
   * Export deals to CSV.
   */
  public function exportDeals() {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $see_all = $current_user->hasRole('administrator') || $user_id == 1 || $current_user->hasRole('sales_manager');

    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($see_all, $user_id) {
      // Disable any output buffering so chunks stream immediately
      while (ob_get_level()) ob_end_clean();
      @ini_set('output_buffering', 'off');
      @ini_set('zlib.output_compression', 'off');

      $handle = fopen('php://output', 'w');
      // Add BOM to fix UTF-8 in Excel
      fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
      
      fputcsv($handle, ['ID', 'Title', 'Amount', 'Stage', 'Probability', 'Contact', 'Organization', 'Owner', 'Expected Close Date', 'Closing Date', 'Status', 'Notes', 'Lost Reason', 'Created Date', 'Modified Date']);

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');
      if (!$see_all) {
        $query->condition('field_owner', $user_id);
      }

      $nids = $query->execute();
      $chunks = array_chunk($nids, 50);

      foreach ($chunks as $chunk) {
        $deals = Node::loadMultiple($chunk);
        foreach ($deals as $deal) {
          $row = [
            $deal->id(),
            $deal->getTitle(),
            $deal->hasField('field_amount') ? $deal->get('field_amount')->value : '0',
            ($deal->hasField('field_stage') && $deal->get('field_stage')->entity) ? $deal->get('field_stage')->entity->getName() : '',
            $deal->hasField('field_probability') ? $deal->get('field_probability')->value : '',
            ($deal->hasField('field_contact') && $deal->get('field_contact')->entity) ? $deal->get('field_contact')->entity->getTitle() : '',
            ($deal->hasField('field_organization') && $deal->get('field_organization')->entity) ? $deal->get('field_organization')->entity->getTitle() : '',
            ($deal->hasField('field_owner') && $deal->get('field_owner')->entity) ? $deal->get('field_owner')->entity->getDisplayName() : '',
            $deal->hasField('field_expected_close_date') ? $deal->get('field_expected_close_date')->value : '',
            $deal->hasField('field_closing_date') ? $deal->get('field_closing_date')->value : '',
            $deal->isPublished() ? 'Active' : 'Closed',
            $deal->hasField('field_notes') ? strip_tags($deal->get('field_notes')->value) : '',
            $deal->hasField('field_lost_reason') ? strip_tags($deal->get('field_lost_reason')->value) : '',
            date('Y-m-d H:i:s', $deal->getCreatedTime()),
            date('Y-m-d H:i:s', $deal->getChangedTime())
          ];
          
          fputcsv($handle, $row);
        }
        \Drupal::entityTypeManager()->getStorage('node')->resetCache($chunk);
        flush();
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="deals_export_' . date('Y-m-d_His') . '.csv"');
    
    return $response;
  }

  /**
   * Export organizations to CSV.
   */
  public function exportOrganizations() {
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $see_all = $current_user->hasRole('administrator') || $user_id == 1 || $current_user->hasRole('sales_manager');

    $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($see_all, $user_id) {
      // Disable any output buffering so chunks stream immediately
      while (ob_get_level()) ob_end_clean();
      @ini_set('output_buffering', 'off');
      @ini_set('zlib.output_compression', 'off');

      $handle = fopen('php://output', 'w');
      // Add BOM to fix UTF-8 in Excel
      fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
      
      fputcsv($handle, ['ID', 'Name', 'Website', 'Industry', 'Address', 'Phone', 'Status', 'Employees', 'Created Date', 'Modified Date']);

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'organization')
        ->accessCheck(FALSE)
        ->sort('created', 'DESC');
      if (!$see_all) {
        $query->condition('field_owner', $user_id);
      }

      $nids = $query->execute();
      $chunks = array_chunk($nids, 50);

      foreach ($chunks as $chunk) {
        $orgs = Node::loadMultiple($chunk);
        foreach ($orgs as $org) {
          $row = [
            $org->id(),
            $org->getTitle(),
            $org->hasField('field_website') ? $org->get('field_website')->uri : '',
            ($org->hasField('field_industry') && $org->get('field_industry')->entity) ? $org->get('field_industry')->entity->getName() : '',
            $org->hasField('field_address') ? $org->get('field_address')->value : '',
            $org->hasField('field_phone') ? $org->get('field_phone')->value : '',
            $org->hasField('field_status') ? $org->get('field_status')->value : '',
            $org->hasField('field_employees') ? $org->get('field_employees')->value : '',
            date('Y-m-d H:i:s', $org->getCreatedTime()),
            date('Y-m-d H:i:s', $org->getChangedTime())
          ];
          
          fputcsv($handle, $row);
        }
        \Drupal::entityTypeManager()->getStorage('node')->resetCache($chunk);
        flush();
      }
      fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="organizations_export_' . date('Y-m-d_His') . '.csv"');
    
    return $response;
  }

  /**
   * Returns published node count for a given type.
   */
  protected function getLiveCount(string $type): string {
    try {
      $count = \Drupal::entityQuery('node')
        ->condition('type', $type)
        ->condition('status', 1)
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      return number_format((int) $count);
    }
    catch (\Exception $e) {
      return '—';
    }
  }

}
