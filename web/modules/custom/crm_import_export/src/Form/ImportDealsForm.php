<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\Entity\Node;
use Drupal\crm_import_export\Service\DataValidationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form for importing Deals via CSV with Drag & Drop UI.
 */
class ImportDealsForm extends FormBase implements ContainerInjectionInterface {

  private const DATE_FORMAT = 'Y-m-d\TH:i:s';
  protected $validationService;

  public function __construct(DataValidationService $validation_service) {
    $this->validationService = $validation_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('crm_import_export.data_validation')
    );
  }

  public function getFormId() {
    return 'crm_import_deals_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'crm-import-form';
    $form['#attributes']['enctype'] = 'multipart/form-data';
    $form['#attached']['library'][] = 'crm_import_export/import_ui';

    $dropzone_html = Markup::create('
      <div class="crm-import-breadcrumb">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        <a href="/admin/crm/import">Import Data</a>
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
        <span>Import Deals</span>
      </div>

      <div class="crm-import-page-header">
        <div class="crm-import-page-icon" style="background:#fefce8; color:#eab308">
          <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
        </div>
        <div>
          <h2 class="crm-import-page-title">Import Deals</h2>
          <p class="crm-import-page-sub">Click or drag and drop a CSV file to begin. Supports up to 10 MB.</p>
        </div>
      </div>

      <div class="crm-schema-hint">
        <div class="crm-schema-hint__label">Required</div>
        <span class="crm-import-tag crm-import-tag--required">title</span>
        <span class="crm-import-tag crm-import-tag--required">amount</span>
        <div class="crm-schema-hint__label" style="margin-left:12px">Optional</div>
        <span class="crm-import-tag">stage</span>
        <span class="crm-import-tag">contact email</span>
        <span class="crm-import-tag">organization</span>
        <span class="crm-import-tag">probability</span>
        <span class="crm-import-tag">expected close date</span>
        <span class="crm-import-tag">notes</span>
      </div>

      <div class="crm-dropzone" id="crm-deals-dropzone">
        <input type="file" name="files[csv_file]" id="crm-csv-input-deals" accept=".csv,.txt"
          style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:10;">
        <div class="crm-dropzone__icon" id="crm-dz-icon-deals">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg>
        </div>
        <p class="crm-dropzone__title">Drop CSV here or <span class="crm-dropzone__link">click to choose file</span></p>
        <p class="crm-dropzone__hint">CSV · TXT &nbsp;·&nbsp; UTF-8 &nbsp;·&nbsp; Max 10 MB</p>
      </div>

      <div class="crm-file-info" id="crm-file-info-deals">
        <div class="crm-file-info__icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div class="crm-file-info__details">
          <div class="crm-file-info__name">—</div>
          <div class="crm-file-info__meta">—</div>
        </div>
        <button type="button" class="crm-file-info__remove" id="crm-remove-deals" title="Remove file">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="crm-csv-preview" id="crm-preview-deals">
        <div class="crm-csv-preview__header">
          <h4>
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
            Preview <small style="font-weight:400;color:#94a3b8;margin-left:4px;">(first 5 rows)</small>
          </h4>
          <span class="crm-csv-preview__badge" id="crm-preview-badge-deals">0 rows</span>
        </div>
        <div class="crm-csv-preview__scroll" id="crm-preview-table-deals"></div>
      </div>
    ');

    $form['ui'] = ['#markup' => $dropzone_html];

    $form['options_wrap_open'] = [
      '#markup' => '<div class="crm-import-options"><h4><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px;vertical-align:middle"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 2.12 3.64"/><path d="M21.17 11h2.17"/><path d="M19.07 19.07a10 10 0 0 1-14.14 0"/><path d="M4.93 4.93a10 10 0 0 1 3.64-2.12"/><path d="M3 12H.83"/><path d="M4.93 19.07a10 10 0 0 1-2.12-3.64"/><path d="M11 3V.83"/><path d="M13 3V.83"/><path d="M19.07 4.93"/></svg>Import Options</h4>',
    ];

    $form['skip_duplicates'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Skip Duplicates'),
      '#default_value' => TRUE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Skip Duplicates</strong><span>Skip deals with titles that already exist</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['update_existing'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Update Existing'),
      '#default_value' => FALSE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Update Existing</strong><span>Overwrite data when title matches</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['create_missing'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Auto-create Contacts & Orgs'),
      '#default_value' => FALSE,
      '#prefix'        => '<div class="crm-toggle-row"><div class="crm-toggle-label"><strong>Auto-create Contacts & Orgs</strong><span>Create relations from CSV if they don\'t exist</span></div>',
      '#suffix'        => '</div>',
    ];

    $form['options_wrap_close'] = ['#markup' => '</div>'];

    $form['progress_html'] = [
      '#markup' => Markup::create('
        <div class="crm-import-progress" id="crm-progress-deals">
          <div class="crm-import-progress__label">
            <span>Importing deals…</span>
            <span class="crm-import-progress__pct">0%</span>
          </div>
          <div class="crm-import-progress__bar"><div class="crm-import-progress__fill" style="width:0%"></div></div>
          <div class="crm-import-progress__status">Preparing…</div>
        </div>
      '),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type'       => 'submit',
      '#value'      => $this->t('Import Deals'),
      '#attributes' => [
        'class' => ['crm-import-submit-btn'],
        'id'    => 'crm-deals-submit',
      ],
      '#prefix' => '<div class="crm-import-submit">',
      '#suffix' => '</div>',
    ];
    
    $form['actions']['cancel'] = [
      '#type'       => 'link',
      '#title'      => $this->t('← Back'),
      '#url'        => Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => ['class' => ['btn-import', 'btn-import--secondary'], 'style' => 'display:inline-flex;align-items:center;gap:8px;padding:12px 20px;'],
    ];

    return $form;
  }

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
    if (!in_array('title', $headers_lower) && !in_array('name', $headers_lower)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have a "Title" column.'));
    }
    if (!in_array('amount', $headers_lower) && !in_array('value', $headers_lower)) {
      $form_state->setErrorByName('csv_file', $this->t('CSV must have an "Amount" column.'));
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (empty($_FILES['files']['tmp_name']['csv_file'])) {
      return;
    }

    $tmpUpload = $_FILES['files']['tmp_name']['csv_file'];
    
    $handle = fopen($tmpUpload, 'r');
    $rows = [];
    while (($data = fgetcsv($handle)) !== FALSE) {
      $rows[] = $data;
    }
    fclose($handle);

    if (count($rows) < 2) {
      $this->messenger()->addError($this->t('CSV contains no data.'));
      return;
    }

    $headers = array_shift($rows);
    $headers = array_map('strtolower', array_map('trim', $headers));

    $batch_data = [];
    foreach ($rows as $data) {
      if (count($data) !== count($headers)) continue;
      
      $row = [];
      foreach ($headers as $i => $h) {
        $row[$h] = trim($data[$i] ?? '');
      }
      $batch_data[] = $row;
    }

    if (empty($batch_data)) {
      $this->messenger()->addError('No valid data found to import.');
      return;
    }

    $options = [
      'skip_duplicates' => $form_state->getValue('skip_duplicates'),
      'update_existing' => $form_state->getValue('update_existing'),
      'create_missing'  => $form_state->getValue('create_missing'),
      'uid'             => \Drupal::currentUser()->id(),
    ];

    $chunks = array_chunk($batch_data, 50);
    $operations = [];
    
    foreach ($chunks as $chunk) {
      $operations[] = [
        '\Drupal\crm_import_export\Form\ImportDealsForm::batchProcess',
        [$chunk, $options]
      ];
    }

    $batch = [
      'title' => $this->t('Importing Deals...'),
      'operations' => $operations,
      'finished' => '\Drupal\crm_import_export\Form\ImportDealsForm::batchFinished',
      'init_message' => $this->t('Initializing import...'),
      'progress_message' => $this->t('Processed @current of @total batches.'),
      'error_message' => $this->t('An error occurred during import.'),
    ];

    batch_set($batch);
    $form_state->setRedirect('view.my_deals.page_1');
  }

  public static function batchProcess($chunk, $options, &$context) {
    if (empty($context['results'])) {
      $context['results'] = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];
    }

    foreach ($chunk as $row) {
      $title = $row['title'] ?? $row['name'] ?? '';
      if (!$title) {
        $context['results']['errors']++;
        continue;
      }

      $amount_str = $row['amount'] ?? $row['value'] ?? '0';
      $amount = floatval(preg_replace('/[^0-9.]/', '', $amount_str));

      $query = \Drupal::entityQuery('node')
        ->condition('type', 'deal')
        ->condition('title', $title)
        ->accessCheck(FALSE)
        ->range(0, 1);
      $nids = $query->execute();

      $values = self::mapRowToValues($row, $amount, $options);

      if (!empty($nids)) {
        if ($options['skip_duplicates'] && !$options['update_existing']) {
          $context['results']['skipped']++;
          continue;
        }
        if ($options['update_existing']) {
          $nid = reset($nids);
          $node = Node::load($nid);
          foreach ($values as $key => $val) {
            $node->set($key, $val);
          }
          $node->save();
          $context['results']['updated']++;
        }
      } 
      else {
        $create_vals = array_merge(['type' => 'deal', 'title' => $title, 'status' => 1, 'uid' => $options['uid'], 'field_owner' => $options['uid']], $values);
        $node = Node::create($create_vals);
        $node->save();
        $context['results']['created']++;
      }
    }
    
    $context['message'] = 'Processed ' . count($chunk) . ' deals...';
  }

  private static function mapRowToValues($row, $amount, $options) {
    $vals = ['field_amount' => $amount];

    // Stage
    if (!empty($row['stage'])) {
      $stage = self::find_stage_by_name($row['stage']);
      if ($stage) $vals['field_stage'] = $stage;
    }

    // Probability
    if (!empty($row['probability'])) {
      $vals['field_probability'] = intval($row['probability']);
    }

    // Dates
    if (!empty($row['expected close date'])) {
      $date = strtotime($row['expected close date']);
      if ($date) $vals['field_expected_close_date'] = date(self::DATE_FORMAT, $date);
    }
    if (!empty($row['closing date'])) {
      $date = strtotime($row['closing date']);
      if ($date) $vals['field_closing_date'] = date(self::DATE_FORMAT, $date);
    }

    // Notes
    if (!empty($row['notes'])) {
      $vals['field_notes'] = ['value' => $row['notes'], 'format' => 'basic_html'];
    }

    // Realations
    $contact_email = $row['contact email'] ?? $row['contact'] ?? '';
    if (!empty($contact_email)) {
      $contact = self::find_or_create_contact($contact_email, $options['create_missing'], $options['uid']);
      if ($contact) $vals['field_contact'] = $contact->id();
    }

    if (!empty($row['organization'])) {
      $org = self::find_or_create_organization($row['organization'], $options['uid'], $options['create_missing']);
      if ($org) $vals['field_organization'] = $org->id();
    }

    return $vals;
  }

  private static function find_or_create_contact($email, $create_if_missing, $uid) {
    $nids = \Drupal::entityQuery('node')->condition('type', 'contact')->condition('field_email', $email)->accessCheck(FALSE)->range(0, 1)->execute();
    if (!empty($nids)) return Node::load(reset($nids));

    if ($create_if_missing) {
      $contact = Node::create([
        'type' => 'contact',
        'title' => $email,
        'field_email' => $email,
        'status' => 1,
        'uid' => $uid,
        'field_owner' => $uid,
      ]);
      $contact->save();
      return $contact;
    }
    return NULL;
  }

  private static function find_or_create_organization($org_name, $uid, $create_if_missing) {
    $nids = \Drupal::entityQuery('node')->condition('type', 'organization')->condition('title', $org_name)->accessCheck(FALSE)->range(0, 1)->execute();
    if (!empty($nids)) return Node::load(reset($nids));

    if ($create_if_missing) {
      $org = Node::create([
        'type' => 'organization',
        'title' => $org_name,
        'status' => 1,
        'uid' => $uid,
        'field_owner' => $uid,
      ]);
      $org->save();
      return $org;
    }
    return NULL;
  }

  private static function find_stage_by_name($stage_name) {
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties([
      'vid' => 'pipeline_stage',
      'name' => $stage_name,
    ]);
    if (!empty($terms)) return reset($terms)->id();
    return NULL;
  }

  public static function batchFinished($success, $results, $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $msg = sprintf("Import completed successfully. Created: %d, Updated: %d, Skipped: %d, Errors: %d.",
        $results['created'], $results['updated'], $results['skipped'], $results['errors']);
      $messenger->addStatus($msg);
    } 
    else {
      $messenger->addError('The import process failed.');
    }
  }

}
