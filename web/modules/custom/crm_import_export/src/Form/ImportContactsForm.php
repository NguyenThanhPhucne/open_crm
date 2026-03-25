<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\crm_import_export\Service\DataValidationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for importing contacts from CSV — premium drag-drop UI + batch.
 */
class ImportContactsForm extends FormBase {

  /**
   * The data validation service.
   *
   * @var \Drupal\crm_import_export\Service\DataValidationService
   */
  protected $validationService;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataValidationService $validation_service) {
    $this->validationService = $validation_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_import_export.data_validation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_import_contacts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'crm-import-form-page';

    // Attach premium library.
    $form['#attached']['library'][] = 'crm_import_export/import_ui';
    $form['#attached']['library'][] = 'core/once';

    // Breadcrumb.
    $form['breadcrumb'] = [
      '#markup' => '<div class="crm-import-breadcrumb">
        <i data-lucide="home" width="14" height="14"></i>
        <a href="/admin/crm/import">Import Data</a>
        <i data-lucide="chevron-right" width="14" height="14"></i>
        <span>Import Contacts</span>
      </div>',
    ];

    // Page heading.
    $form['heading'] = [
      '#markup' => '<div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <div style="width:52px;height:52px;background:#eff6ff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0;">
          <i data-lucide="users" width="26" height="26"></i>
        </div>
        <div>
          <h2 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 3px;">Import Contacts</h2>
          <p style="font-size:14px;color:#64748b;margin:0;">Drag &amp; drop your CSV file or click to browse</p>
        </div>
      </div>',
    ];

    // ── DRAG-DROP ZONE ──
    $form['dropzone_wrap'] = [
      '#markup' => '<div class="crm-import-form-wrap">',
    ];

    $form['dropzone'] = [
      '#markup' => '<div class="crm-dropzone" id="crm-contacts-dropzone">
        <input type="file" accept=".csv,.txt" class="crm-dropzone__input" id="crm-contacts-file-browse" aria-label="Upload CSV file">
        <div class="crm-dropzone__icon">
          <i data-lucide="cloud-upload" width="32" height="32"></i>
        </div>
        <h3>Drop your CSV file here</h3>
        <p>Supports CSV and TXT files up to 10 MB</p>
        <label class="crm-dropzone__browse" for="crm-contacts-file-browse">
          <i data-lucide="folder-open" width="16" height="16"></i>
          Browse File
        </label>
      </div>',
    ];

    // File info bar (shown after selection).
    $form['file_info'] = [
      '#markup' => '<div class="crm-file-info">
        <div class="crm-file-info__icon"><i data-lucide="file-check" width="22" height="22"></i></div>
        <div class="crm-file-info__details">
          <div class="crm-file-info__name">No file selected</div>
          <div class="crm-file-info__meta"></div>
        </div>
        <button type="button" class="crm-file-info__remove" title="Remove file">
          <i data-lucide="x" width="18" height="18"></i>
        </button>
      </div>',
    ];

    // CSV Preview.
    $form['csv_preview'] = [
      '#markup' => '<div class="crm-csv-preview">
        <div class="crm-csv-preview__header">
          <h4><i data-lucide="table" width="16" height="16"></i> Data Preview</h4>
          <span class="crm-csv-preview__badge">0 rows detected</span>
        </div>
        <div class="crm-csv-preview__scroll"></div>
      </div>',
    ];

    // ── HIDDEN DRUPAL FILE UPLOAD (actual submission) ──
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload CSV with contact data.'),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv txt'],
      ],
      '#upload_location' => 'public://crm_imports',
      '#required' => TRUE,
      '#attributes' => ['class' => ['crm-hidden-file-upload']],
    ];

    // ── IMPORT OPTIONS ──
    $form['options_wrap'] = [
      '#markup' => '<div class="crm-import-options">
        <h4><i data-lucide="settings-2" width="16" height="16"></i> Import Options</h4>',
    ];

    $form['skip_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip duplicate contacts'),
      '#description' => $this->t('Skip rows where email already exists in the system.'),
      '#default_value' => TRUE,
      '#attributes' => ['class' => ['crm-toggle-checkbox']],
      '#prefix' => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Skip Duplicates</strong><span>Skip contacts with email addresses already in the system</span></div>',
      '#suffix' => '</div>',
    ];

    $form['update_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing contacts'),
      '#description' => $this->t('Update existing contacts if email matches (overrides skip duplicates).'),
      '#default_value' => FALSE,
      '#prefix' => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Update Existing</strong><span>Overwrite existing contact data when email matches</span></div>',
      '#suffix' => '</div>',
    ];

    $form['create_missing_orgs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create missing organizations'),
      '#description' => $this->t('Automatically create organizations that don\'t exist yet.'),
      '#default_value' => TRUE,
      '#prefix' => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Auto-create Organizations</strong><span>Create organizations from CSV if they don\'t exist yet</span></div>',
      '#suffix' => '</div>',
    ];

    $form['options_close'] = ['#markup' => '</div>'];

    // Progress bar.
    $form['progress'] = [
      '#markup' => '<div class="crm-import-progress">
        <div class="crm-import-progress__label">
          <span><i data-lucide="loader" width="14" height="14" style="margin-right:6px;"></i> Importing…</span>
          <span class="crm-import-progress__pct">0%</span>
        </div>
        <div class="crm-import-progress__bar">
          <div class="crm-import-progress__fill" style="width:0%"></div>
        </div>
        <div class="crm-import-progress__status">Preparing…</div>
      </div>',
    ];

    // ── SUBMIT ──
    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Contacts'),
      '#attributes' => [
        'class' => ['crm-import-submit-btn'],
        'id' => 'crm-import-contacts-submit',
      ],
      '#prefix' => '<div class="crm-import-submit"><i data-lucide="upload" width="18" height="18" style="pointer-events:none;position:absolute;left:18px;"></i>',
      '#suffix' => '</div>',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Import Hub'),
      '#url' => \Drupal\Core\Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => ['class' => ['btn-import', 'btn-import--secondary'], 'style' => 'display:inline-flex;align-items:center;gap:8px;padding:12px 20px;'],
    ];

    $form['dropzone_close'] = ['#markup' => '</div>'];

    // Initialize icons after render.
    $form['icons_init'] = [
      '#markup' => '<script>if(typeof lucide!=="undefined"){lucide.createIcons();}else{var _s=document.createElement("script");_s.src="https://unpkg.com/lucide@latest";_s.onload=function(){lucide.createIcons();};document.head.appendChild(_s);}</script>',
      '#allowed_tags' => ['script'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('csv_file');
    if (empty($file_id[0])) {
      return;
    }

    $file = File::load($file_id[0]);
    if (!$file) {
      return;
    }

    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $handle = fopen($file_path, 'r');
    if ($handle === FALSE) {
      $form_state->setErrorByName('csv_file', $this->t('Unable to read the CSV file.'));
      return;
    }

    $headers = fgetcsv($handle, 0, ',', '"', '');
    fclose($handle);

    if (!$headers) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file appears to be empty or invalid.'));
      return;
    }

    $h = array_map('strtolower', array_map('trim', $headers));
    if (!in_array('name', $h) && !in_array('title', $h)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have a "name" or "title" column.'));
    }
    if (!in_array('email', $h)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have an "email" column.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('csv_file');
    if (empty($file_id[0])) {
      return;
    }

    $file = File::load($file_id[0]);
    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());

    $options = [
      'skip_duplicates'    => (bool) $form_state->getValue('skip_duplicates'),
      'update_existing'    => (bool) $form_state->getValue('update_existing'),
      'create_missing_orgs'=> (bool) $form_state->getValue('create_missing_orgs'),
    ];

    // Build batch.
    $batch = [
      'title' => $this->t('Importing contacts…'),
      'operations' => [
        [
          [static::class, 'batchImport'],
          [$file_path, $options],
        ],
      ],
      'finished' => [static::class, 'batchFinished'],
      'progress_message' => $this->t('Processing @current of @total rows.'),
    ];

    batch_set($batch);
    $form_state->setRedirect('view.my_contacts.page_1');
  }

  /**
   * Batch operation: imports contacts row-by-row with progress feedback.
   */
  public static function batchImport($file_path, $options, &$context) {
    $batch_size = 25;

    // Initialize sandbox.
    if (empty($context['sandbox'])) {
      $context['sandbox']['handle'] = fopen($file_path, 'r');
      $context['sandbox']['headers'] = [];
      $context['sandbox']['current'] = 0;
      $context['sandbox']['total'] = 0;
      $context['results'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

      if ($context['sandbox']['handle']) {
        $raw_headers = fgetcsv($context['sandbox']['handle'], 0, ',', '"', '');
        $context['sandbox']['headers'] = array_map('strtolower', array_map('trim', $raw_headers ?? []));

        // Count total rows.
        $contents = file($file_path);
        $context['sandbox']['total'] = max(0, count($contents) - 1);
      }
    }

    $handle  = $context['sandbox']['handle'];
    $headers = $context['sandbox']['headers'];

    if (!$handle) {
      $context['finished'] = 1;
      return;
    }

    $processed = 0;
    while ($processed < $batch_size && ($data = fgetcsv($handle, 0, ',', '"', '')) !== FALSE) {
      if (array_filter($data)) {
        $row = [];
        foreach ($headers as $idx => $h) {
          $row[$h] = isset($data[$idx]) ? trim($data[$idx]) : '';
        }
        static::processRow($row, $options, $context['results']);
      }
      $processed++;
      $context['sandbox']['current']++;
    }

    $total = $context['sandbox']['total'] ?: 1;
    $context['finished'] = $context['sandbox']['current'] / $total;

    if ($context['finished'] >= 1) {
      fclose($handle);
    }

    $context['message'] = t('Processed @cur of @total rows…', [
      '@cur'   => $context['sandbox']['current'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Process a single contact row.
   */
  protected static function processRow(array $row, array $options, array &$results) {
    $name  = $row['name'] ?? $row['title'] ?? '';
    $email = $row['email'] ?? '';

    if (empty($name) || empty($email)) {
      $results['errors']++;
      return;
    }

    // Duplicate check.
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_email', $email)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    $existing = $nids ? Node::load(reset($nids)) : NULL;

    if ($existing && $options['skip_duplicates'] && !$options['update_existing']) {
      $results['skipped']++;
      return;
    }

    if ($existing && $options['update_existing']) {
      static::updateContact($existing, $row, $options['create_missing_orgs']);
      $results['updated']++;
    }
    else {
      static::createContact($row, $options['create_missing_orgs']);
      $results['created']++;
    }
  }

  /**
   * Create a new contact node.
   */
  protected static function createContact(array $row, bool $create_missing_orgs): void {
    $values = [
      'type'           => 'contact',
      'title'          => $row['name'] ?? $row['title'] ?? '',
      'field_email'    => $row['email'] ?? '',
      'field_phone'    => $row['phone'] ?? '',
      'field_position' => $row['position'] ?? '',
      'field_owner'    => \Drupal::currentUser()->id(),
      'status'         => 1,
      'uid'            => \Drupal::currentUser()->id(),
    ];

    if (!empty($row['status'])) {
      $values['field_status'] = strtolower($row['status']);
    }

    if (!empty($row['organization'])) {
      $org = static::findOrCreateOrg($row['organization'], $create_missing_orgs);
      if ($org) {
        $values['field_organization'] = $org->id();
      }
    }

    if (!empty($row['linkedin'])) {
      $values['field_linkedin'] = ['uri' => $row['linkedin'], 'title' => 'LinkedIn'];
    }

    Node::create($values)->save();
  }

  /**
   * Update existing contact node.
   */
  protected static function updateContact(Node $contact, array $row, bool $create_missing_orgs): void {
    if (!empty($row['phone']))    $contact->set('field_phone', $row['phone']);
    if (!empty($row['position'])) $contact->set('field_position', $row['position']);
    if (!empty($row['status']))   $contact->set('field_status', strtolower($row['status']));

    if (!empty($row['organization'])) {
      $org = static::findOrCreateOrg($row['organization'], $create_missing_orgs);
      if ($org) $contact->set('field_organization', $org->id());
    }

    if (!empty($row['linkedin'])) {
      $contact->set('field_linkedin', ['uri' => $row['linkedin'], 'title' => 'LinkedIn']);
    }

    $contact->save();
  }

  /**
   * Find or create organization by name.
   */
  protected static function findOrCreateOrg(string $name, bool $create): ?Node {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('title', $name)
      ->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    if ($nids) {
      return Node::load(reset($nids));
    }

    if ($create) {
      $org = Node::create([
        'type'        => 'organization',
        'title'       => $name,
        'field_owner' => \Drupal::currentUser()->id(),
        'status'      => 1,
        'uid'         => \Drupal::currentUser()->id(),
      ]);
      $org->save();
      return $org;
    }

    return NULL;
  }

  /**
   * Batch finished callback — displays result summary.
   */
  public static function batchFinished(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $messenger->addStatus(t('✅ Import completed!'));
      $messenger->addStatus(t('Created: @n contacts', ['@n' => $results['created'] ?? 0]));
      if (!empty($results['updated'])) {
        $messenger->addStatus(t('Updated: @n contacts', ['@n' => $results['updated']]));
      }
      if (!empty($results['skipped'])) {
        $messenger->addWarning(t('Skipped: @n duplicates', ['@n' => $results['skipped']]));
      }
      if (!empty($results['errors'])) {
        $messenger->addError(t('Errors: @n rows could not be imported', ['@n' => $results['errors']]));
      }
    }
    else {
      $messenger->addError(t('Import encountered an error.'));
    }
  }

}
