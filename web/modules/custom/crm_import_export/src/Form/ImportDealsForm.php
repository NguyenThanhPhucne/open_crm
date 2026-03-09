<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\crm_import_export\Service\DataValidationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for importing deals from CSV.
 *
 * Provides functionality to import deals through CSV upload with options for
 * handling duplicates, creating missing contacts, and setting default stages.
 */
class ImportDealsForm extends FormBase implements ContainerInjectionInterface {

  /**
   * Date format constant for Drupal datetime fields.
   */
  private const DATE_FORMAT = 'Y-m-d\TH:i:s';

  /**
   * The data validation service.
   *
   * @var \Drupal\crm_import_export\Service\DataValidationService
   */
  protected $validationService;

  /**
   * Constructs ImportDealsForm.
   *
   * @param \Drupal\crm_import_export\Service\DataValidationService $validation_service
   *   The data validation service.
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
    return 'crm_import_deals_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['class'][] = 'crm-import-form';
    
    // Header
    $form['header'] = [
      '#markup' => '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; color: white;">
        <h2 style="margin: 0;">💰 Import Deals from CSV</h2>
        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Upload a CSV file to import multiple deals at once</p>
      </div>',
    ];
    
    // File upload
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload a CSV file with deal data. Maximum file size: 10MB. Must include headers in first row.'),
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv txt'],
      ],
      '#upload_location' => 'public://crm_imports',
      '#required' => TRUE,
    ];
    
    // Import options
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Import Options'),
      '#open' => TRUE,
    ];
    
    $form['options']['skip_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip duplicate deals'),
      '#description' => $this->t('Skip deals with titles that already exist in the system.'),
      '#default_value' => TRUE,
    ];
    
    $form['options']['update_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing deals'),
      '#description' => $this->t('Update existing deals if title matches (overrides skip duplicates).'),
      '#default_value' => FALSE,
    ];
    
    $form['options']['create_missing_contacts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create missing contacts'),
      '#description' => $this->t('Automatically create contacts that don\'t exist yet (based on email).'),
      '#default_value' => FALSE,
    ];
    
    $form['options']['default_stage'] = [
      '#type' => 'select',
      '#title' => $this->t('Default stage for new deals'),
      '#description' => $this->t('Pipeline stage to use if not specified in CSV.'),
      '#options' => $this->get_stage_options(),
      '#default_value' => 'new',
    ];
    
    // CSV Format help
    $form['help'] = [
      '#type' => 'details',
      '#title' => $this->t('📖 CSV Format Guide'),
      '#open' => FALSE,
    ];
    
    $form['help']['content'] = [
      '#markup' => '
        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px;">
          <h4>Required Columns:</h4>
          <ul>
            <li><strong>Title</strong> - Deal name/title</li>
            <li><strong>Amount</strong> - Deal value (numeric)</li>
          </ul>
          
          <h4>Optional Columns:</h4>
          <ul>
            <li><strong>Contact</strong> or <strong>Contact Email</strong> - Related contact</li>
            <li><strong>Organization</strong> - Company name</li>
            <li><strong>Stage</strong> - Pipeline stage (New, Proposal, Negotiation, etc.)</li>
            <li><strong>Probability</strong> - Win probability percentage (0-100)</li>
            <li><strong>Expected Close Date</strong> - Date in YYYY-MM-DD format</li>
            <li><strong>Closing Date</strong> - Actual close date in YYYY-MM-DD format</li>
            <li><strong>Notes</strong> - Internal notes</li>
          </ul>
          
          <h4>Example CSV Format (NOT real data - replace with your actual data):</h4>
          <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.875rem;">
Title,Amount,Contact Email,Organization,Stage,Expected Close Date
Your Deal Title 1,[Amount],[Contact Email],[Organization Name],[Stage ID or Name],[YYYY-MM-DD]
Your Deal Title 2,[Amount],[Contact Email],[Organization Name],[Stage ID or Name],[YYYY-MM-DD]
Your Deal Title 3,[Amount],[Contact Email],[Organization Name],[Stage ID or Name],[YYYY-MM-DD]
          </pre>
        </div>
      ',
    ];
    
    // Actions
    $form['actions'] = [
      '#type' => 'actions',
    ];
    
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import Deals'),
      '#button_type' => 'primary',
      '#attributes' => [
        'style' => 'background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border: none; padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;',
      ],
    ];
    
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => \Drupal\Core\Url::fromRoute('crm_import_export.import_page'),
      '#attributes' => [
        'class' => ['button'],
        'style' => 'margin-left: 1rem;',
      ],
    ];

    return $form;
  }

  /**
   * Get available pipeline stages.
   *
   * @return array
   *   Array of stage options keyed by stage ID.
   */
  private function get_stage_options() {
    $options = [];

    // Load stage taxonomy terms dynamically from database.
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('pipeline_stage');

    foreach ($terms as $term) {
      $options[$term->tid] = $term->name;
    }

    return $options;
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

    $this->validate_csv_file($file, $form_state);
  }

  /**
   * Validate CSV file structure and required columns.
   *
   * @param \Drupal\file\Entity\File $file
   *   File entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  private function validate_csv_file(File $file, FormStateInterface $form_state) {
    $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $handle = fopen($file_path, 'r');

    if ($handle === FALSE) {
      $form_state->setErrorByName('csv_file', $this->t('Unable to read the CSV file.'));
      return;
    }

    $headers = fgetcsv($handle, 0, ',', '"', '');

    if (!$headers) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file appears to be empty or invalid.'));
      fclose($handle);
      return;
    }

    $this->check_required_columns($headers, $form_state);
    fclose($handle);
  }

  /**
   * Check for required CSV columns.
   *
   * @param array $headers
   *   CSV headers.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  private function check_required_columns(array $headers, FormStateInterface $form_state) {
    $headers_lower = array_map('strtolower', $headers);
    $has_title = in_array('title', $headers_lower) || in_array('name', $headers_lower);
    $has_amount = in_array('amount', $headers_lower) || in_array('value', $headers_lower);

    if (!$has_title) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file must have a "Title" column.'));
    }

    if (!$has_amount) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file must have an "Amount" column.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file_id = $form_state->getValue('csv_file');
    $skip_duplicates = $form_state->getValue('skip_duplicates');
    $update_existing = $form_state->getValue('update_existing');
    $create_missing_contacts = $form_state->getValue('create_missing_contacts');
    $default_stage = $form_state->getValue('default_stage');
    
    if (!empty($file_id[0])) {
      $file = File::load($file_id[0]);
      $file_path = \Drupal::service('file_system')->realpath($file->getFileUri());
      
      $results = $this->process_csv(
        $file_path,
        $skip_duplicates,
        $update_existing,
        $create_missing_contacts,
        $default_stage
      );

      $this->display_import_results($results);
      
      // Redirect to deals list
      $form_state->setRedirect('view.my_deals.page_1');
    }
  }

  /**
   * Display import results to the user.
   *
   * @param array $results
   *   Array containing counts of created, updated, skipped, and error records.
   */
  private function display_import_results(array $results) {
    $this->messenger()->addStatus($this->t('Import completed successfully!'));
    $this->messenger()->addStatus($this->t('Created: @created deals', ['@created' => $results['created']]));

    if ($results['updated'] > 0) {
      $this->messenger()->addStatus($this->t('Updated: @updated deals', ['@updated' => $results['updated']]));
    }

    if ($results['skipped'] > 0) {
      $this->messenger()->addWarning($this->t('Skipped: @skipped duplicates', ['@skipped' => $results['skipped']]));
    }

    if ($results['errors'] > 0) {
      $this->messenger()->addError($this->t('Errors: @errors rows had errors', ['@errors' => $results['errors']]));
    }
  }

  /**
   * Process the CSV file and import deals.
   *
   * @param string $file_path
   *   Path to the CSV file.
   * @param bool $skip_duplicates
   *   Whether to skip duplicate deals.
   * @param bool $update_existing
   *   Whether to update existing deals.
   * @param bool $create_missing_contacts
   *   Whether to create contacts that don't exist.
   * @param mixed $default_stage
   *   Default pipeline stage for new deals.
   *
   * @return array
   *   Results array with counts.
   */
  private function process_csv($file_path, $skip_duplicates, $update_existing, $create_missing_contacts, $default_stage) {
    $results = [
      'created' => 0,
      'updated' => 0,
      'skipped' => 0,
      'errors' => 0,
    ];

    if (($handle = fopen($file_path, 'r')) === FALSE) {
      return $results;
    }

    $headers = $this->read_csv_headers($handle);
    $row_number = 1;

    while (($data = fgetcsv($handle, 0, ',', '"', '')) !== FALSE) {
      $row_number++;
      $row = $this->map_csv_row($headers, $data);

      $this->process_deal_row(
        $row,
        $row_number,
        $skip_duplicates,
        $update_existing,
        $create_missing_contacts,
        $default_stage,
        $results
      );
    }

    fclose($handle);
    return $results;
  }

  /**
   * Read and normalize CSV headers.
   *
   * @param resource $handle
   *   File handle.
   *
   * @return array
   *   Normalized header array.
   */
  private function read_csv_headers($handle) {
    $headers = fgetcsv($handle, 0, ',', '"', '');
    $headers = array_map('strtolower', $headers);
    return array_map('trim', $headers);
  }

  /**
   * Map CSV row data to associative array.
   *
   * @param array $headers
   *   CSV headers.
   * @param array $data
   *   CSV row data.
   *
   * @return array
   *   Mapped row data.
   */
  private function map_csv_row(array $headers, array $data) {
    $row = [];
    foreach ($headers as $index => $header) {
      $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
    }
    return $row;
  }

  /**
   * Process a single deal row from CSV.
   *
   * @param array $row
   *   CSV row data.
   * @param int $row_number
   *   Row number for error reporting.
   * @param bool $skip_duplicates
   *   Whether to skip duplicates.
   * @param bool $update_existing
   *   Whether to update existing deals.
   * @param bool $create_missing_contacts
   *   Whether to create missing contacts.
   * @param mixed $default_stage
   *   Default pipeline stage.
   * @param array &$results
   *   Results array (passed by reference).
   */
  private function process_deal_row($row, $row_number, $skip_duplicates, $update_existing, $create_missing_contacts, $default_stage, array &$results) {
    try {
      $title = $row['title'] ?? $row['name'] ?? '';

      if (empty($title)) {
        $this->log_import_error($row_number, 'Missing required field: title');
        $results['errors']++;
        return;
      }

      // Validate deal data before processing
      $deal_data = [
        'title' => $title,
        'amount' => $row['amount'] ?? $row['value'] ?? 0,
        'stage' => $row['stage'] ?? NULL,
        'contact' => $row['contact'] ?? NULL,
      ];

      $validation = $this->validationService->validateDeal($deal_data);
      if (!$validation['valid']) {
        $error_msg = implode('; ', $validation['errors']);
        $this->log_import_error($row_number, 'Validation failed: ' . $error_msg);
        $results['errors']++;
        return;
      }

      $existing = $this->find_existing_deal($title);

      if ($existing && $this->should_skip_duplicate($existing, $skip_duplicates, $update_existing)) {
        $results['skipped']++;
        return;
      }

      if ($existing && $update_existing) {
        $this->update_deal($existing, $row, $create_missing_contacts);
        $results['updated']++;
      }
      else {
        $this->create_deal($row, $create_missing_contacts, $default_stage);
        $results['created']++;
      }
    }
    catch (\Exception $e) {
      $this->log_import_error($row_number, $e->getMessage());
      $results['errors']++;
    }
  }

  /**
   * Check if duplicate should be skipped.
   *
   * @param \Drupal\node\Entity\Node $existing
   *   Existing deal node.
   * @param bool $skip_duplicates
   *   Skip duplicates flag.
   * @param bool $update_existing
   *   Update existing flag.
   *
   * @return bool
   *   TRUE if should skip.
   */
  private function should_skip_duplicate($existing, $skip_duplicates, $update_existing) {
    return $existing && !$update_existing && $skip_duplicates;
  }

  /**
   * Log import error.
   *
   * @param int $row_number
   *   Row number.
   * @param string $message
   *   Error message.
   */
  private function log_import_error($row_number, $message) {
    \Drupal::logger('crm_import_export')->error('Error importing deal row @row: @message', [
      '@row' => $row_number,
      '@message' => $message,
    ]);
  }

  /**
   * Find existing deal by title.
   *
   * @param string $title
   *   Deal title to search for.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Deal node if found, NULL otherwise.
   */
  private function find_existing_deal($title) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('title', $title)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      return Node::load(reset($nids));
    }

    return NULL;
  }

  /**
   * Create a new deal from CSV row.
   *
   * @param array $row
   *   CSV row data.
   * @param bool $create_missing_contacts
   *   Whether to create missing contacts.
   * @param mixed $default_stage
   *   Default pipeline stage.
   *
   * @return \Drupal\node\Entity\Node
   *   Created deal node.
   */
  private function create_deal($row, $create_missing_contacts, $default_stage) {
    $values = [
      'type' => 'deal',
      'title' => $row['title'] ?? $row['name'],
      'field_amount' => $this->parse_amount($row),
      'status' => 1,
      'uid' => \Drupal::currentUser()->id(),
      'field_owner' => \Drupal::currentUser()->id(),
    ];

    $this->add_contact_to_values($values, $row, $create_missing_contacts);
    $this->add_organization_to_values($values, $row);
    $this->add_stage_to_values($values, $row, $default_stage);
    $this->add_probability_to_values($values, $row);
    $this->add_dates_to_values($values, $row);
    $this->add_notes_to_values($values, $row);

    $deal = Node::create($values);
    $deal->save();

    return $deal;
  }

  /**
   * Parse amount from row data.
   *
   * @param array $row
   *   CSV row data.
   *
   * @return float
   *   Parsed amount.
   */
  private function parse_amount(array $row) {
    $amount = $row['amount'] ?? $row['value'] ?? 0;
    // Remove currency symbols.
    $amount = preg_replace('/[^0-9.]/', '', $amount);
    return floatval($amount);
  }

  /**
   * Add contact reference to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   * @param bool $create_missing_contacts
   *   Whether to create missing contacts.
   */
  private function add_contact_to_values(array &$values, array $row, $create_missing_contacts) {
    $contact_email = $row['contact email'] ?? $row['contact'] ?? '';
    if (!empty($contact_email)) {
      $contact = $this->find_or_create_contact($contact_email, $create_missing_contacts);
      if ($contact) {
        $values['field_contact'] = $contact->id();
      }
    }
  }

  /**
   * Add organization reference to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   */
  private function add_organization_to_values(array &$values, array $row) {
    if (!empty($row['organization'])) {
      $org = $this->find_or_create_organization($row['organization']);
      if ($org) {
        $values['field_organization'] = $org->id();
      }
    }
  }

  /**
   * Add stage to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   * @param mixed $default_stage
   *   Default stage value.
   */
  private function add_stage_to_values(array &$values, array $row, $default_stage) {
    if (!empty($row['stage'])) {
      $stage = $this->find_stage_by_name($row['stage']);
      $values['field_stage'] = $stage ?? $default_stage;
    }
    else {
      $values['field_stage'] = $default_stage;
    }
  }

  /**
   * Add probability to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   */
  private function add_probability_to_values(array &$values, array $row) {
    if (!empty($row['probability'])) {
      $values['field_probability'] = intval($row['probability']);
    }
  }

  /**
   * Add dates to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   */
  private function add_dates_to_values(array &$values, array $row) {
    // Expected Close Date.
    if (!empty($row['expected close date'])) {
      $date = strtotime($row['expected close date']);
      if ($date) {
        $values['field_expected_close_date'] = date(self::DATE_FORMAT, $date);
      }
    }

    // Closing Date.
    if (!empty($row['closing date'])) {
      $date = strtotime($row['closing date']);
      if ($date) {
        $values['field_closing_date'] = date(self::DATE_FORMAT, $date);
      }
    }
  }

  /**
   * Add notes to values array.
   *
   * @param array &$values
   *   Node values array (passed by reference).
   * @param array $row
   *   CSV row data.
   */
  private function add_notes_to_values(array &$values, array $row) {
    if (!empty($row['notes'])) {
      $values['field_notes'] = [
        'value' => $row['notes'],
        'format' => 'basic_html',
      ];
    }
  }

  /**
   * Update an existing deal from CSV row.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Existing deal node to update.
   * @param array $row
   *   CSV row data.
   * @param bool $create_missing_contacts
   *   Whether to create missing contacts.
   *
   * @return \Drupal\node\Entity\Node
   *   Updated deal node.
   */
  private function update_deal($deal, $row, $create_missing_contacts) {
    $this->update_deal_amount($deal, $row);
    $this->update_deal_contact($deal, $row, $create_missing_contacts);
    $this->update_deal_organization($deal, $row);
    $this->update_deal_stage($deal, $row);
    $this->update_deal_probability($deal, $row);
    $this->update_deal_dates($deal, $row);
    $this->update_deal_notes($deal, $row);

    $deal->save();
    return $deal;
  }

  /**
   * Update deal amount.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_amount($deal, array $row) {
    if (isset($row['amount']) || isset($row['value'])) {
      $deal->set('field_amount', $this->parse_amount($row));
    }
  }

  /**
   * Update deal contact.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   * @param bool $create_missing_contacts
   *   Whether to create missing contacts.
   */
  private function update_deal_contact($deal, array $row, $create_missing_contacts) {
    $contact_email = $row['contact email'] ?? $row['contact'] ?? '';
    if (empty($contact_email)) {
      return;
    }

    $contact = $this->find_or_create_contact($contact_email, $create_missing_contacts);
    if ($contact) {
      $deal->set('field_contact', $contact->id());
    }
  }

  /**
   * Update deal organization.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_organization($deal, array $row) {
    if (empty($row['organization'])) {
      return;
    }

    $org = $this->find_or_create_organization($row['organization']);
    if ($org) {
      $deal->set('field_organization', $org->id());
    }
  }

  /**
   * Update deal stage.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_stage($deal, array $row) {
    if (empty($row['stage'])) {
      return;
    }

    $stage = $this->find_stage_by_name($row['stage']);
    if ($stage) {
      $deal->set('field_stage', $stage);
    }
  }

  /**
   * Update deal probability.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_probability($deal, array $row) {
    if (!empty($row['probability'])) {
      $deal->set('field_probability', intval($row['probability']));
    }
  }

  /**
   * Update deal dates.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_dates($deal, array $row) {
    if (!empty($row['expected close date'])) {
      $date = strtotime($row['expected close date']);
      if ($date) {
        $deal->set('field_expected_close_date', date(self::DATE_FORMAT, $date));
      }
    }
  }

  /**
   * Update deal notes.
   *
   * @param \Drupal\node\Entity\Node $deal
   *   Deal node.
   * @param array $row
   *   CSV row data.
   */
  private function update_deal_notes($deal, array $row) {
    if (!empty($row['notes'])) {
      $deal->set('field_notes', [
        'value' => $row['notes'],
        'format' => 'basic_html',
      ]);
    }
  }

  /**
   * Find or create contact by email.
   *
   * @param string $email
   *   Contact email address.
   * @param bool $create_if_missing
   *   Whether to create contact if not found.
   *
   * @return \Drupal\node\Entity\Node|null
   *   Contact node if found or created, NULL otherwise.
   */
  private function find_or_create_contact($email, $create_if_missing) {
    // Search for existing contact.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_email', $email)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      return Node::load(reset($nids));
    }

    // Create if allowed.
    if ($create_if_missing) {
      $contact = Node::create([
        'type' => 'contact',
        'title' => $email,
        'field_email' => $email,
        'status' => 1,
        'uid' => \Drupal::currentUser()->id(),
        'field_owner' => \Drupal::currentUser()->id(),
      ]);
      $contact->save();

      return $contact;
    }

    return NULL;
  }

  /**
   * Find or create organization by name.
   *
   * @param string $org_name
   *   Organization name.
   *
   * @return \Drupal\node\Entity\Node
   *   Organization node.
   */
  private function find_or_create_organization($org_name) {
    // Search for existing organization.
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('title', $org_name)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      return Node::load(reset($nids));
    }

    // Create new organization.
    $org = Node::create([
      'type' => 'organization',
      'title' => $org_name,
      'status' => 1,
      'uid' => \Drupal::currentUser()->id(),
      'field_owner' => \Drupal::currentUser()->id(),
    ]);
    $org->save();

    return $org;
  }

  /**
   * Find stage taxonomy term by name.
   *
   * @param string $stage_name
   *   Stage name to search for.
   *
   * @return int|null
   *   Term ID if found, NULL otherwise.
   */
  private function find_stage_by_name($stage_name) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'vid' => 'pipeline_stage',
        'name' => $stage_name,
      ]);

    if (!empty($terms)) {
      return reset($terms)->id();
    }

    return NULL;
  }

}
