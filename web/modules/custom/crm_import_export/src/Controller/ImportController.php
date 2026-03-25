<?php

namespace Drupal\crm_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for Import hub page.
 */
class ImportController extends ControllerBase {

  /**
   * Main import page — premium hub for all import operations.
   */
  public function importPage() {
    // Live stats.
    $contact_count = $this->getLiveCount('contact');
    $deal_count    = $this->getLiveCount('deal');
    $org_count     = $this->getLiveCount('organization');

    $base = \Drupal::request()->getBaseUrl();

    $html = <<<HTML
<div class="crm-import-hub">

  <!-- ── HEADER ── -->
  <div class="crm-import-header">
    <div class="crm-import-header-inner">
      <div class="crm-import-header-title">
        <div class="crm-icon-wrapper">
          <i data-lucide="upload-cloud" width="28" height="28"></i>
        </div>
        <div>
          <h1>Import Data</h1>
          <p>Bulk import your CRM data natively and securely</p>
        </div>
      </div>
      <div class="crm-import-stats">
        <div class="crm-import-stat">
          <i data-lucide="users" width="14" height="14"></i>
          <span><strong>{$contact_count}</strong> contacts</span>
        </div>
        <div class="crm-import-stat">
          <i data-lucide="briefcase" width="14" height="14"></i>
          <span><strong>{$deal_count}</strong> deals</span>
        </div>
        <div class="crm-import-stat">
          <i data-lucide="building-2" width="14" height="14"></i>
          <span><strong>{$org_count}</strong> organizations</span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── BODY ── -->
  <div class="crm-import-body">

    <!-- Import Cards -->
    <div class="crm-import-grid">

      <!-- CONTACTS -->
      <div class="crm-import-card crm-import-card--blue">
        <div class="crm-import-card__head">
          <div class="crm-import-card__icon crm-import-card__icon--blue">
            <i data-lucide="users" width="24" height="24"></i>
          </div>
          <div class="crm-import-card__title">
            <h3>Import Contacts</h3>
            <p>Upload customers, leads, and prospects</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Columns</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag crm-import-tag--required">name</span>
            <span class="crm-import-tag crm-import-tag--required">email</span>
            <span class="crm-import-tag">phone</span>
            <span class="crm-import-tag">position</span>
            <span class="crm-import-tag">organization</span>
            <span class="crm-import-tag">status</span>
            <span class="crm-import-tag">linkedin</span>
            <span class="crm-import-tag">source</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/import/contacts" class="btn-import btn-import--primary">
            <i data-lucide="upload" width="15" height="15"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/contacts_template.csv" class="btn-import btn-import--secondary" download>
            <i data-lucide="download" width="15" height="15"></i>
            Template
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
            <h3>Import Deals</h3>
            <p>Upload deal pipeline and opportunity data</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Columns</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag crm-import-tag--required">title</span>
            <span class="crm-import-tag crm-import-tag--required">amount</span>
            <span class="crm-import-tag crm-import-tag--required">stage</span>
            <span class="crm-import-tag">contact</span>
            <span class="crm-import-tag">close_date</span>
            <span class="crm-import-tag">probability</span>
            <span class="crm-import-tag">currency</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/import/deals" class="btn-import btn-import--primary">
            <i data-lucide="upload" width="15" height="15"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/deals_template.csv" class="btn-import btn-import--secondary" download>
            <i data-lucide="download" width="15" height="15"></i>
            Template
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
            <h3>Import Organizations</h3>
            <p>Upload companies and accounts in bulk</p>
          </div>
        </div>
        <div class="crm-import-schema">
          <h4>Columns</h4>
          <div class="crm-import-tags">
            <span class="crm-import-tag crm-import-tag--required">name</span>
            <span class="crm-import-tag">website</span>
            <span class="crm-import-tag">industry</span>
            <span class="crm-import-tag">address</span>
            <span class="crm-import-tag">phone</span>
            <span class="crm-import-tag">status</span>
            <span class="crm-import-tag">employees</span>
          </div>
        </div>
        <div class="crm-import-card__actions">
          <a href="/admin/crm/import/organizations" class="btn-import btn-import--primary">
            <i data-lucide="upload" width="15" height="15"></i>
            Start Import
          </a>
          <a href="/sites/default/files/import-templates/organizations_template.csv" class="btn-import btn-import--secondary" download>
            <i data-lucide="download" width="15" height="15"></i>
            Template
          </a>
        </div>
      </div>

    </div><!-- /grid -->

    <!-- How to import guide -->
    <div class="crm-import-guide">
      <h3>
        <i data-lucide="book-open" width="20" height="20" style="color:#64748b"></i>
        How to Import
      </h3>
      <div class="crm-import-steps">
        <div class="crm-import-step">
          <div class="crm-import-step__num">1</div>
          <div class="crm-import-step__text"><strong>Download</strong> the CSV template for the entity type you want to import.</div>
        </div>
        <div class="crm-import-step">
          <div class="crm-import-step__num">2</div>
          <div class="crm-import-step__text"><strong>Fill in</strong> your data following the template format. Keep the header row.</div>
        </div>
        <div class="crm-import-step">
          <div class="crm-import-step__num">3</div>
          <div class="crm-import-step__text"><strong>Upload</strong> your CSV and configure import options like duplicate handling.</div>
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
          'crm_import_export/import_ui',
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
