<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;

/**
 * Premium CSV import form for Contacts.
 *
 * The file input physically overlays the entire dropzone (inset:0, opacity:0,
 * no pointer-events restriction) so BOTH click-to-browse and drag-and-drop
 * work natively. No managed_file AJAX needed — plain multipart POST.
 */
class ImportContactsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static();
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
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['#attached']['library'][] = 'crm_import_export/import_ui';
    $form['#attached']['library'][] = 'core/once';

    // ── "Browse File" aria-label is set on the input so screen readers work.
    // The input has position:absolute;inset:0 to cover the whole dropzone
    // card, opacity:0 so it's invisible but fully interactive.
    // Clicking anywhere in the dashed box opens the OS file picker.
    // Drag-and-drop fires 'change' on the input via DataTransfer (see JS).
    $dropzone_html = Markup::create('
      <div class="crm-import-breadcrumb">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <a href="/admin/crm/import">Import Data</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Import Contacts</span>
      </div>

      <div class="crm-import-page-header">
        <div class="crm-import-page-icon crm-import-page-icon--blue">
          <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
          <h2 class="crm-import-page-title">Import Contacts</h2>
          <p class="crm-import-page-sub">Click or drag and drop a CSV file to begin. Supports up to 10 MB.</p>
        </div>
      </div>

      <!-- SCHEMA HINT -->
      <div class="crm-schema-hint">
        <div class="crm-schema-hint__label">Required</div>
        <span class="crm-import-tag crm-import-tag--required">name</span>
        <span class="crm-import-tag crm-import-tag--required">email</span>
        <div class="crm-schema-hint__label crm-schema-hint__label--optional">Optional</div>
        <span class="crm-import-tag">phone</span>
        <span class="crm-import-tag">position</span>
        <span class="crm-import-tag">organization</span>
        <span class="crm-import-tag">status</span>
        <span class="crm-import-tag">linkedin</span>
        <span class="crm-import-tag">source</span>
      </div>

      <!-- DROP ZONE — file input overlays entire card -->
      <div class="crm-dropzone" id="crm-contacts-dropzone">
        <!-- The actual file input: covers entire zone via position:absolute;inset:0 -->
        <input
          type="file"
          name="files[csv_file]"
          id="crm-csv-input-contacts"
          accept=".csv,.txt"
          class="crm-import-file-input">

        <div class="crm-dropzone__icon" id="crm-dz-icon-contacts">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        </div>
        <p class="crm-dropzone__title">Drop CSV here or <span class="crm-dropzone__link">click to choose file</span></p>
        <p class="crm-dropzone__hint">CSV · TXT &nbsp;·&nbsp; UTF-8 &nbsp;·&nbsp; Max 10 MB</p>
      </div>

      <!-- FILE INFO (shown by JS after selection) -->
      <div class="crm-file-info" id="crm-file-info-contacts">
        <div class="crm-file-info__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div class="crm-file-info__details">
          <div class="crm-file-info__name">—</div>
          <div class="crm-file-info__meta">—</div>
        </div>
        <button type="button" class="crm-file-info__remove" id="crm-remove-contacts" title="Remove file">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- CSV PREVIEW (shown by JS) -->
      <div class="crm-csv-preview" id="crm-preview-contacts">
        <div class="crm-csv-preview__header">
            Preview <small class="crm-import-preview-hint">(first 5 rows)</small>
          </h4>
          <span class="crm-csv-preview__badge" id="crm-preview-badge-contacts">0 rows</span>
        </div>
        <div class="crm-csv-preview__scroll" id="crm-preview-table-contacts"></div>
      </div>
    ');

    $form['ui'] = ['#markup' => $dropzone_html];

    // ── IMPORT OPTIONS ──
    $form['options_wrap_open'] = [
      '#markup' => '<div class="crm-import-options"><h4><svg class="crm-import-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 2.12 3.64"/><path d="M21.17 11h2.17"/><path d="M19.07 19.07a10 10 0 0 1-14.14 0"/><path d="M4.93 4.93a10 10 0 0 1 3.64-2.12"/><path d="M3 12H.83"/><path d="M4.93 19.07a10 10 0 0 1-2.12-3.64"/><path d="M11 3V.83"/><path d="M13 3V.83"/><path d="M19.07 4.93"/></svg>Import Options</h4>',
    ];

    $form['skip_duplicates'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Skip Duplicates'),
      '#description'   => $this->t('Skip contacts whose email already exists in the system.'),
      '#default_value' => TRUE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Skip Duplicates</strong><span>Skip contacts whose email already exists</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['update_existing'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Update Existing'),
      '#description'   => $this->t('Overwrite existing contact data when email matches.'),
      '#default_value' => FALSE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Update Existing</strong><span>Overwrite data when email matches</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['create_missing_orgs'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Auto-create Organizations'),
      '#description'   => $this->t('Create organizations from CSV if they don\'t exist yet.'),
      '#default_value' => TRUE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Auto-create Organizations</strong><span>Create orgs from CSV automatically</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['options_wrap_close'] = ['#markup' => '</div>'];

    // Progress bar (shown by JS on submit).
    $form['progress_html'] = [
      '#markup' => Markup::create('
        <div class="crm-import-progress" id="crm-progress-contacts">
          <div class="crm-import-progress__label">
            <span>Importing contacts…</span>
            <span class="crm-import-progress__pct">0%</span>
          </div>
          <div class="crm-import-progress__bar">
            <div class="crm-import-progress__fill" style="width:0%"></div>
          </div>
          <div class="crm-import-progress__status">Preparing…</div>
        </div>
      '),
    ];

    // ── ACTIONS ──
    $form['actions'] = ['#type' => 'actions'];

    $form['actions']['submit'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Import Contacts'),
      '#attributes' => [
        'class' => ['crm-import-submit-btn'],
        'id'    => 'crm-contacts-submit',
      ],
      '#prefix' => '<div class="crm-import-submit">',
      '#suffix' => '</div>',
    ];

    $form['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('← Back'),
      '#url'        => \Drupal\Core\Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => ['class' => ['btn-import', 'btn-import--secondary'], 'style' => 'display:inline-flex;align-items:center;gap:8px;padding:12px 20px;'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Access the raw uploaded file.
    $files  = \Drupal::request()->files->get('files', []);
    $upload = $files['csv_file'] ?? NULL;

    if (!$upload || !$upload->isValid() || $upload->getSize() === 0) {
      $form_state->setErrorByName('', $this->t('Please select a CSV file before importing.'));
      return;
    }

    // Extension check.
    $ext = strtolower($upload->getClientOriginalExtension());
    if (!in_array($ext, ['csv', 'txt'])) {
      $form_state->setErrorByName('', $this->t('Only CSV or TXT files are allowed.'));
      return;
    }

    // Check required columns.
    $handle = fopen($upload->getRealPath(), 'r');
    if ($handle) {
      $headers = fgetcsv($handle, 0, ',', '"', '');
      fclose($handle);
      if ($headers) {
        $h = array_map('strtolower', array_map('trim', $headers));
        if (!in_array('name', $h) && !in_array('title', $h)) {
          $form_state->setErrorByName('', $this->t('CSV must have a "name" or "title" column.'));
        }
        if (!in_array('email', $h)) {
          $form_state->setErrorByName('', $this->t('CSV must have an "email" column.'));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($_FILES['files']['tmp_name']['csv_file'])) {
      return;
    }

    $tmpUpload = $_FILES['files']['tmp_name']['csv_file'];
    $filename  = $_FILES['files']['name']['csv_file'];

    $destination = 'public://crm_imports';
    \Drupal::service('file_system')->prepareDirectory($destination, \Drupal\Core\File\FileSystemInterface::CREATE_DIRECTORY | \Drupal\Core\File\FileSystemInterface::MODIFY_PERMISSIONS);

    $file_uri = $destination . '/' . basename($filename);
    try {
      $file_path = \Drupal::service('file_system')->realpath($file_uri);
      
      // Use PHP native move_uploaded_file for robust handling of $_FILES['...']['tmp_name']
      if (!@move_uploaded_file($tmpUpload, $file_path)) {
        if (!@copy($tmpUpload, $file_path)) {
          throw new \Exception('Failed to move or copy the uploaded file.');
        }
      }
    } catch (\Exception $e) {
      $this->messenger()->addError($this->t('Could not move the uploaded file. Check directory permissions.'));
      return;
    }

    $options = [
      'skip_duplicates'     => (bool) $form_state->getValue('skip_duplicates'),
      'update_existing'     => (bool) $form_state->getValue('update_existing'),
      'create_missing_orgs' => (bool) $form_state->getValue('create_missing_orgs'),
    ];

    batch_set([
      'title'            => $this->t('Importing contacts…'),
      'init_message'     => $this->t('Reading CSV file…'),
      'progress_message' => $this->t('Processed @current rows…'),
      'error_message'    => $this->t('An error occurred.'),
      'operations'       => [[[static::class, 'batchImport'], [$file_path, $options]]],
      'finished'         => [static::class, 'batchFinished'],
    ]);

    $form_state->setRedirect('crm.all_contacts');
  }

  // ───────────────────────────── BATCH ──────────────────────────────────────

  /**
   * Batch operation: processes up to 50 rows per call.
   */
  public static function batchImport(string $file_path, array $options, array &$context): void {
    if (empty($context['sandbox'])) {
      $handle = @fopen($file_path, 'r');
      $context['sandbox']['handle']  = $handle ?: NULL;
      $context['sandbox']['current'] = 0;
      $context['results']            = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

      if ($handle) {
        $raw = fgetcsv($handle, 0, ',', '"', '');
        $context['sandbox']['headers'] = $raw
          ? array_map('strtolower', array_map('trim', $raw))
          : [];
        $context['sandbox']['total'] = max(0, count(file($file_path)) - 1);
      }
      else {
        $context['sandbox']['total'] = 0;
        $context['finished'] = 1;
        return;
      }
    }

    $handle  = $context['sandbox']['handle'];
    $headers = $context['sandbox']['headers'] ?? [];

    if (!$handle) { $context['finished'] = 1; return; }

    $processed = 0;
    while ($processed < 50 && ($data = fgetcsv($handle, 0, ',', '"', '')) !== FALSE) {
      if (array_filter($data)) {
        $row = [];
        foreach ($headers as $i => $h) {
          $row[$h] = isset($data[$i]) ? trim($data[$i]) : '';
        }
        static::processRow($row, $options, $context['results']);
      }
      $processed++;
      $context['sandbox']['current']++;
    }

    $total = max(1, $context['sandbox']['total']);
    $context['finished'] = $context['sandbox']['current'] / $total;
    if ($context['finished'] >= 1) @fclose($handle);

    $context['message'] = t('Processed @cur of @total rows…', [
      '@cur'   => $context['sandbox']['current'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Process a single data row.
   */
  protected static function processRow(array $row, array $options, array &$results): void {
    $name  = $row['name'] ?? $row['title'] ?? '';
    $email = $row['email'] ?? '';

    if (empty($name) || empty($email)) {
      $results['errors']++;
      return;
    }

    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_email', $email)
      ->accessCheck(FALSE)->range(0, 1)->execute();

    $existing = $nids ? Node::load(reset($nids)) : NULL;

    if ($existing && $options['skip_duplicates'] && !$options['update_existing']) {
      $results['skipped']++;
      return;
    }

    if ($existing instanceof Node && $options['update_existing']) {
      static::updateContact($existing, $row, $options['create_missing_orgs']);
      $results['updated']++;
    }
    else {
      static::createContact($row, $options['create_missing_orgs']);
      $results['created']++;
    }
  }

  /**
   * Creates a new contact node from a CSV row.
   */
  protected static function createContact(array $row, bool $create_orgs): void {
    $uid = \Drupal::currentUser()->id();
    $values = [
      'type'           => 'contact',
      'title'          => $row['name'] ?? $row['title'] ?? '',
      'field_email'    => $row['email'] ?? '',
      'field_phone'    => $row['phone'] ?? '',
      'field_position' => $row['position'] ?? '',
      'field_owner'    => $uid,
      'status'         => 1,
      'uid'            => $uid,
    ];

    if (!empty($row['status'])) $values['field_status'] = strtolower($row['status']);

    if (!empty($row['organization'])) {
      $org = static::findOrCreateOrg($row['organization'], $create_orgs);
      if ($org) $values['field_organization'] = $org->id();
    }

    if (!empty($row['linkedin'])) {
      $values['field_linkedin'] = ['uri' => $row['linkedin'], 'title' => 'LinkedIn'];
    }

    Node::create($values)->save();
  }

  /**
   * Updates an existing contact node from a CSV row.
   */
  protected static function updateContact(Node $contact, array $row, bool $create_orgs): void {
    if (!empty($row['phone']))    $contact->set('field_phone', $row['phone']);
    if (!empty($row['position'])) $contact->set('field_position', $row['position']);
    if (!empty($row['status']))   $contact->set('field_status', strtolower($row['status']));

    if (!empty($row['organization'])) {
      $org = static::findOrCreateOrg($row['organization'], $create_orgs);
      if ($org) $contact->set('field_organization', $org->id());
    }

    if (!empty($row['linkedin'])) {
      $contact->set('field_linkedin', ['uri' => $row['linkedin'], 'title' => 'LinkedIn']);
    }

    $contact->save();
  }

  /**
   * Finds or creates an organization by name.
   */
  protected static function findOrCreateOrg(string $name, bool $create): ?Node {
    $nids = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('title', $name)
      ->accessCheck(FALSE)->range(0, 1)->execute();

    if ($nids) return Node::load(reset($nids));

    if ($create) {
      $uid = \Drupal::currentUser()->id();
      $org = Node::create([
        'type' => 'organization', 'title' => $name,
        'field_owner' => $uid, 'status' => 1, 'uid' => $uid,
      ]);
      $org->save();
      return $org;
    }
    return NULL;
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations): void {
    $m = \Drupal::messenger();
    if ($success) {
      $m->addStatus(t('Import completed successfully. Created: @c, Updated: @u, Skipped: @s, Errors: @e.', [
        '@c' => $results['created'] ?? 0,
        '@u' => $results['updated'] ?? 0,
        '@s' => $results['skipped'] ?? 0,
        '@e' => $results['errors']  ?? 0,
      ]));
    }
    else {
      $m->addError(t('Import encountered an error. Check Drupal logs for details.'));
    }
  }

}
