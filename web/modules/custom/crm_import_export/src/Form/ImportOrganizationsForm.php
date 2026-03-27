<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Form for importing organizations from CSV — premium drag-drop UI + batch.
 */
class ImportOrganizationsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'crm_import_organizations_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'crm-import-form-page';
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['#attached']['library'][] = 'crm_import_export/import_ui';
    $form['#attached']['library'][] = 'core/once';

    $dropzone_html = Markup::create('
      <div class="crm-import-breadcrumb">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <a href="/admin/crm/import">Import Data</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Import Organizations</span>
      </div>

      <div class="crm-import-page-header">
        <div class="crm-import-page-icon crm-import-page-icon--org">
          <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01"/><path d="M16 6h.01"/><path d="M12 6h.01"/><path d="M12 10h.01"/><path d="M12 14h.01"/><path d="M16 10h.01"/><path d="M16 14h.01"/><path d="M8 10h.01"/><path d="M8 14h.01"/></svg>
        </div>
        <div>
          <h2 class="crm-import-page-title">Import Organizations</h2>
          <p class="crm-import-page-sub">Click or drag and drop a CSV file to begin. Supports up to 10 MB.</p>
        </div>
      </div>

      <!-- SCHEMA HINT -->
      <div class="crm-schema-hint">
        <div class="crm-schema-hint__label">Required</div>
        <span class="crm-import-tag crm-import-tag--required">name</span>
        <div class="crm-schema-hint__label crm-schema-hint__label--optional">Optional</div>
        <span class="crm-import-tag">website</span>
        <span class="crm-import-tag">industry</span>
        <span class="crm-import-tag">address</span>
        <span class="crm-import-tag">status</span>
      </div>

      <!-- DROP ZONE -->
      <div class="crm-dropzone" id="crm-orgs-dropzone">
        <input
          type="file"
          name="files[csv_file]"
          id="crm-csv-input-orgs"
          accept=".csv,.txt"
          class="crm-import-file-input">

        <div class="crm-dropzone__icon" id="crm-dz-icon-orgs">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        </div>
        <p class="crm-dropzone__title">Drop CSV here or <span class="crm-dropzone__link">click to choose file</span></p>
        <p class="crm-dropzone__hint">CSV · TXT &nbsp;·&nbsp; UTF-8 &nbsp;·&nbsp; Max 10 MB</p>
      </div>

      <!-- FILE INFO (shown by JS after selection) -->
      <div class="crm-file-info" id="crm-file-info-orgs">
        <div class="crm-file-info__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div class="crm-file-info__details">
          <div class="crm-file-info__name">—</div>
          <div class="crm-file-info__meta">—</div>
        </div>
        <button type="button" class="crm-file-info__remove" id="crm-remove-orgs" title="Remove file">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- CSV PREVIEW (shown by JS) -->
      <div class="crm-csv-preview" id="crm-preview-orgs">
        <div class="crm-csv-preview__header">
          <h4>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
            Preview <small class="crm-import-preview-hint">(first 5 rows)</small>
          </h4>
          <span class="crm-csv-preview__badge" id="crm-preview-badge-orgs">0 rows</span>
        </div>
        <div class="crm-csv-preview__scroll" id="crm-preview-table-orgs"></div>
      </div>
    ');

    $form['ui'] = ['#markup' => $dropzone_html];

    $form['options_wrap_open'] = [
      '#markup' => '<div class="crm-import-options"><h4><svg class="crm-import-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 2.12 3.64"/><path d="M21.17 11h2.17"/><path d="M19.07 19.07a10 10 0 0 1-14.14 0"/><path d="M4.93 4.93a10 10 0 0 1 3.64-2.12"/><path d="M3 12H.83"/><path d="M4.93 19.07a10 10 0 0 1-2.12-3.64"/><path d="M11 3V.83"/><path d="M13 3V.83"/><path d="M19.07 4.93"/></svg>Import Options</h4>',
    ];

    $form['skip_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip duplicate organizations'),
      '#default_value' => TRUE,
      '#prefix' => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Skip Duplicates</strong><span>Skip organizations with a name that already exists</span></div>',
      '#suffix' => '</div>',
    ];

    $form['update_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing organizations'),
      '#default_value' => FALSE,
      '#prefix' => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Update Existing</strong><span>Overwrite existing organization data when name matches</span></div>',
      '#suffix' => '</div>',
    ];

    $form['options_wrap_close'] = ['#markup' => '</div>'];

    $form['progress_html'] = [
      '#markup' => Markup::create('
        <div class="crm-import-progress" id="crm-progress-orgs">
          <div class="crm-import-progress__label">
            <span>Importing organizations…</span>
            <span class="crm-import-progress__pct">0%</span>
          </div>
          <div class="crm-import-progress__bar">
            <div class="crm-import-progress__fill" style="width:0%"></div>
          </div>
          <div class="crm-import-progress__status">Preparing…</div>
        </div>
      '),
    ];

    $form['actions'] = [
      '#type'       => 'container',
      '#attributes' => ['class' => ['crm-import-actions-row']],
    ];
    $form['actions']['submit'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Import Organizations'),
      '#attributes' => [
        'class' => ['crm-import-submit-btn'],
        'id'    => 'crm-orgs-submit',
      ],
    ];

    $form['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('← Back'),
      '#url'        => Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => ['class' => ['btn-import', 'btn-import--secondary', 'crm-import-btn-cancel']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($_FILES['files']['name']['csv_file'])) {
      $form_state->setErrorByName('csv_file', $this->t('Please select a CSV file.'));
      return;
    }

    $tmp_name = $_FILES['files']['tmp_name']['csv_file'];
    if (!is_uploaded_file($tmp_name)) {
      $form_state->setErrorByName('csv_file', $this->t('Error uploading file.'));
      return;
    }

    $handle = fopen($tmp_name, 'r');
    if ($handle === FALSE) {
      $form_state->setErrorByName('csv_file', $this->t('Cannot read file.'));
      return;
    }

    $headers = fgetcsv($handle);
    fclose($handle);

    if (!$headers) {
      $form_state->setErrorByName('csv_file', $this->t('CSV is empty or invalid.'));
      return;
    }

    $headers_lower = array_map('strtolower', array_map('trim', $headers));
    if (!in_array('name', $headers_lower)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have a "name" column.'));
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
      'skip_duplicates' => (bool) $form_state->getValue('skip_duplicates'),
      'update_existing' => (bool) $form_state->getValue('update_existing'),
    ];

    batch_set([
      'title'            => $this->t('Importing organizations...'),
      'init_message'     => $this->t('Reading CSV file...'),
      'progress_message' => $this->t('Processed @current rows...'),
      'error_message'    => $this->t('An error occurred.'),
      'operations'       => [[static::class . '::batchProcess', [$file_path, $options]]],
      'finished'         => static::class . '::batchFinished',
    ]);

    $form_state->setRedirect('crm.all_organizations');
  }

  /**
   * Batch operation.
   */
  public static function batchProcess(string $file_path, array $options, &$context) {
    if (empty($context['sandbox'])) {
      $handle = @fopen($file_path, 'r');
      $context['sandbox']['handle']  = $handle ?: NULL;
      $context['sandbox']['current'] = 0;
      $context['results']            = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

      if ($handle) {
        $raw = fgetcsv($handle, 0, ',', '"', '');
        $context['sandbox']['headers'] = $raw ? array_map('strtolower', array_map('trim', $raw)) : [];
        $context['sandbox']['total'] = max(0, count(file($file_path)) - 1);
      } else {
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
        static::processSingleRow($row, $options, $context['results']);
      }
      $processed++;
      $context['sandbox']['current']++;
    }

    $total = max(1, $context['sandbox']['total']);
    $context['finished'] = $context['sandbox']['current'] / $total;
    if ($context['finished'] >= 1) @fclose($handle);

    $context['message'] = 'Processed ' . $context['sandbox']['current'] . ' of ' . $context['sandbox']['total'] . ' rows...';
  }

  protected static function processSingleRow(array $row, array $options, array &$results): void {
      $name = $row['name'] ?? '';
      if (empty($name)) { 
        $results['errors']++; 
        return; 
      }

      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'organization')
        ->condition('title', $name)
        ->accessCheck(FALSE)
        ->range(0, 1)
        ->execute();

      $existing = $nids ? Node::load(reset($nids)) : NULL;

      if ($existing && $options['skip_duplicates'] && !$options['update_existing']) {
        $results['skipped']++;
      }
      elseif ($existing && $options['update_existing']) {
        if (!empty($row['website']))  $existing->set('field_website', ['uri' => $row['website']]);
        if (!empty($row['industry'])) $existing->set('field_industry', $row['industry']);
        if (!empty($row['address']))  $existing->set('field_address', $row['address']);
        if (!empty($row['status']))   $existing->set('field_status', $row['status']);
        $existing->save();
        $results['updated']++;
      }
      elseif (!$existing) {
        $values = [
          'type' => 'organization', 'title' => $name,
          'field_owner' => \Drupal::currentUser()->id(),
          'status' => 1, 'uid' => \Drupal::currentUser()->id(),
        ];
        if (!empty($row['website']))  $values['field_website']  = ['uri' => $row['website']];
        if (!empty($row['industry'])) $values['field_industry'] = $row['industry'];
        if (!empty($row['address']))  $values['field_address']  = $row['address'];
        if (!empty($row['status']))   $values['field_status']   = $row['status'];
        Node::create($values)->save();
        $results['created']++;
      }
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations) {
    $m = \Drupal::messenger();
    if ($success) {
      $m->addStatus(t('Import completed successfully. Created: @c, Updated: @u, Skipped: @s, Errors: @e.', [
        '@c' => $results['created'] ?? 0, '@u' => $results['updated'] ?? 0,
        '@s' => $results['skipped'] ?? 0, '@e' => $results['errors'] ?? 0,
      ]));
    }
    else {
      $m->addError(t('Import encountered an error.'));
    }
  }

}
