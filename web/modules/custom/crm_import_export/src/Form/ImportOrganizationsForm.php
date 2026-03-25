<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

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
    $form['#attached']['library'][] = 'crm_import_export/import_ui';
    $form['#attached']['library'][] = 'core/once';

    $form['breadcrumb'] = [
      '#markup' => '<div class="crm-import-breadcrumb">
        <i data-lucide="home" width="14" height="14"></i>
        <a href="/admin/crm/import">Import Data</a>
        <i data-lucide="chevron-right" width="14" height="14"></i>
        <span>Import Organizations</span>
      </div>',
    ];

    $form['heading'] = [
      '#markup' => '<div style="display:flex;align-items:center;gap:16px;margin-bottom:28px;">
        <div style="width:52px;height:52px;background:#f0fdfa;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#14b8a6;flex-shrink:0;">
          <i data-lucide="building-2" width="26" height="26"></i>
        </div>
        <div>
          <h2 style="font-size:22px;font-weight:800;color:#1e293b;margin:0 0 3px;">Import Organizations</h2>
          <p style="font-size:14px;color:#64748b;margin:0;">Drag &amp; drop your CSV file or click to browse</p>
        </div>
      </div>',
    ];

    $form['dropzone'] = [
      '#markup' => '<div class="crm-dropzone" id="crm-orgs-dropzone">
        <input type="file" accept=".csv,.txt" class="crm-dropzone__input" id="crm-orgs-file-browse" aria-label="Upload CSV file">
        <div class="crm-dropzone__icon" style="color:#14b8a6;">
          <i data-lucide="cloud-upload" width="32" height="32"></i>
        </div>
        <h3>Drop your CSV file here</h3>
        <p>Supports CSV and TXT files up to 10 MB</p>
        <label class="crm-dropzone__browse" style="background:#14b8a6;" for="crm-orgs-file-browse">
          <i data-lucide="folder-open" width="16" height="16"></i>
          Browse File
        </label>
      </div>',
    ];

    $form['file_info'] = [
      '#markup' => '<div class="crm-file-info">
        <div class="crm-file-info__icon"><i data-lucide="file-check" width="22" height="22"></i></div>
        <div class="crm-file-info__details">
          <div class="crm-file-info__name">No file selected</div>
          <div class="crm-file-info__meta"></div>
        </div>
        <button type="button" class="crm-file-info__remove">
          <i data-lucide="x" width="18" height="18"></i>
        </button>
      </div>',
    ];

    $form['csv_preview'] = [
      '#markup' => '<div class="crm-csv-preview">
        <div class="crm-csv-preview__header">
          <h4><i data-lucide="table" width="16" height="16"></i> Data Preview</h4>
          <span class="crm-csv-preview__badge">0 rows detected</span>
        </div>
        <div class="crm-csv-preview__scroll"></div>
      </div>',
    ];

    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv txt'],
      ],
      '#upload_location' => 'public://crm_imports',
      '#required' => TRUE,
      '#attributes' => ['class' => ['crm-hidden-file-upload']],
    ];

    $form['options_wrap'] = [
      '#markup' => '<div class="crm-import-options">
        <h4><i data-lucide="settings-2" width="16" height="16"></i> Import Options</h4>',
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

    $form['options_close'] = ['#markup' => '</div>'];

    $form['progress'] = [
      '#markup' => '<div class="crm-import-progress">
        <div class="crm-import-progress__label">
          <span>Importing…</span>
          <span class="crm-import-progress__pct">0%</span>
        </div>
        <div class="crm-import-progress__bar">
          <div class="crm-import-progress__fill" style="width:0%"></div>
        </div>
        <div class="crm-import-progress__status">Preparing…</div>
      </div>',
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Organizations'),
      '#attributes' => [
        'class' => ['crm-import-submit-btn'],
        'style' => 'background:linear-gradient(135deg,#14b8a6 0%,#0d9488 100%);box-shadow:0 4px 14px rgba(20,184,166,.35);',
      ],
      '#prefix' => '<div class="crm-import-submit">',
      '#suffix' => '</div>',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('← Back to Import Hub'),
      '#url' => \Drupal\Core\Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => ['class' => ['btn-import', 'btn-import--secondary'], 'style' => 'display:inline-flex;align-items:center;gap:8px;padding:12px 20px;'],
    ];

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
    if (empty($file_id[0])) return;

    $file = File::load($file_id[0]);
    if (!$file) return;

    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $handle = fopen($path, 'r');
    if (!$handle) {
      $form_state->setErrorByName('csv_file', $this->t('Unable to read CSV file.'));
      return;
    }

    $headers = fgetcsv($handle, 0, ',', '"', '');
    fclose($handle);

    if (!$headers) {
      $form_state->setErrorByName('csv_file', $this->t('CSV is empty or invalid.'));
      return;
    }

    $h = array_map('strtolower', array_map('trim', $headers));
    if (!in_array('name', $h)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have a "name" column.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('csv_file');
    if (empty($file_id[0])) return;

    $file = File::load($file_id[0]);
    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());

    $options = [
      'skip_duplicates' => (bool) $form_state->getValue('skip_duplicates'),
      'update_existing' => (bool) $form_state->getValue('update_existing'),
    ];

    batch_set([
      'title'      => $this->t('Importing organizations…'),
      'operations' => [[[static::class, 'batchImport'], [$file_path, $options]]],
      'finished'   => [static::class, 'batchFinished'],
    ]);

    $form_state->setRedirect('view.my_organizations.page_1');
  }

  /**
   * Batch operation.
   */
  public static function batchImport($file_path, $options, &$context) {
    $batch_size = 25;

    if (empty($context['sandbox'])) {
      $context['sandbox']['handle'] = fopen($file_path, 'r');
      $context['sandbox']['headers'] = [];
      $context['sandbox']['current'] = 0;
      $context['results'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

      if ($context['sandbox']['handle']) {
        $raw = fgetcsv($context['sandbox']['handle'], 0, ',', '"', '');
        $context['sandbox']['headers'] = array_map('strtolower', array_map('trim', $raw ?? []));
        $context['sandbox']['total'] = max(0, count(file($file_path)) - 1);
      }
    }

    $handle  = $context['sandbox']['handle'];
    $headers = $context['sandbox']['headers'];

    if (!$handle) { $context['finished'] = 1; return; }

    $processed = 0;
    while ($processed < $batch_size && ($data = fgetcsv($handle, 0, ',', '"', '')) !== FALSE) {
      if (array_filter($data)) {
        $row = [];
        foreach ($headers as $idx => $h) {
          $row[$h] = isset($data[$idx]) ? trim($data[$idx]) : '';
        }
        $name = $row['name'] ?? '';
        if (empty($name)) { $context['results']['errors']++; $processed++; continue; }

        $nids = \Drupal::entityQuery('node')
          ->condition('type', 'organization')
          ->condition('title', $name)
          ->accessCheck(FALSE)->range(0, 1)->execute();

        $existing = $nids ? Node::load(reset($nids)) : NULL;

        if ($existing && $options['skip_duplicates'] && !$options['update_existing']) {
          $context['results']['skipped']++;
        }
        elseif ($existing && $options['update_existing']) {
          if (!empty($row['website']))  $existing->set('field_website', ['uri' => $row['website']]);
          if (!empty($row['industry'])) $existing->set('field_industry', $row['industry']);
          if (!empty($row['address']))  $existing->set('field_address', $row['address']);
          if (!empty($row['status']))   $existing->set('field_status', $row['status']);
          $existing->save();
          $context['results']['updated']++;
        }
        else {
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
          $context['results']['created']++;
        }
      }
      $processed++;
      $context['sandbox']['current']++;
    }

    $total = $context['sandbox']['total'] ?: 1;
    $context['finished'] = $context['sandbox']['current'] / $total;
    if ($context['finished'] >= 1) fclose($handle);
    $context['message'] = t('Processed @cur of @total rows…', ['@cur' => $context['sandbox']['current'], '@total' => $context['sandbox']['total']]);
  }

  /**
   * Batch finished callback.
   */
  public static function batchFinished(bool $success, array $results, array $operations) {
    $m = \Drupal::messenger();
    if ($success) {
      $m->addStatus(t('✅ Import completed! Created: @c, Updated: @u, Skipped: @s, Errors: @e', [
        '@c' => $results['created'] ?? 0, '@u' => $results['updated'] ?? 0,
        '@s' => $results['skipped'] ?? 0, '@e' => $results['errors'] ?? 0,
      ]));
    }
    else {
      $m->addError(t('Import encountered an error.'));
    }
  }

}
