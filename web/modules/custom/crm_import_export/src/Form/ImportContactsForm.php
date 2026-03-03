<?php

namespace Drupal\crm_import_export\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Form for importing contacts from CSV.
 *
 * Provides a user interface to upload and process CSV files containing
 * contact information. Supports duplicate detection, updating existing
 * contacts, and automatic organization creation.
 *
 * @package Drupal\crm_import_export\Form
 */
class ImportContactsForm extends FormBase {

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
    $form['#attributes']['class'][] = 'crm-import-form';
    
    // Header
    $form['header'] = [
      '#markup' => '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; color: white;">
        <h2 style="margin: 0;">👥 Import Contacts from CSV</h2>
        <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Upload a CSV file to import multiple contacts at once</p>
      </div>',
    ];
    
    // File upload
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#description' => $this->t('Upload a CSV file with contact data. Maximum file size: 10MB. Must include headers in first row.'),
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
      '#title' => $this->t('Skip duplicate contacts'),
      '#description' => $this->t('Skip contacts with email addresses that already exist in the system.'),
      '#default_value' => TRUE,
    ];
    
    $form['options']['update_existing'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update existing contacts'),
      '#description' => $this->t('Update existing contacts if email matches (overrides skip duplicates).'),
      '#default_value' => FALSE,
    ];
    
    $form['options']['create_missing_orgs'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Create missing organizations'),
      '#description' => $this->t('Automatically create organizations that don\'t exist yet.'),
      '#default_value' => TRUE,
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
            <li><strong>Name</strong> or <strong>Title</strong> - Contact full name</li>
            <li><strong>Email</strong> - Contact email address (used for duplicate detection)</li>
          </ul>
          
          <h4>Optional Columns:</h4>
          <ul>
            <li><strong>Phone</strong> - Phone number</li>
            <li><strong>Position</strong> - Job title/position</li>
            <li><strong>Organization</strong> - Company name</li>
            <li><strong>Source</strong> - Lead source (Website, Referral, etc.)</li>
            <li><strong>Customer Type</strong> - VIP, New, Potential, etc.</li>
            <li><strong>Status</strong> - new, contacted, qualified, etc.</li>
            <li><strong>LinkedIn</strong> - LinkedIn profile URL</li>
            <li><strong>Tags</strong> - Comma-separated tags</li>
          </ul>
          
          <h4>Example CSV:</h4>
          <pre style="background: white; padding: 1rem; border-radius: 4px; overflow-x: auto; font-size: 0.875rem;">
Name,Email,Phone,Position,Organization,Source,Status
John Doe,john@example.com,0901234567,Sales Director,ABC Company,Website,new
Jane Smith,jane@example.com,0987654321,CEO,XYZ Corporation,Referral,contacted
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
      '#value' => $this->t('Import Contacts'),
      '#button_type' => 'primary',
      '#attributes' => [
        'style' => 'background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.75rem 2rem; font-size: 1rem; font-weight: 600;',
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
    $this->validate_csv_file($file_path, $form_state);
  }

  /**
   * Validates CSV file structure and required columns.
   *
   * @param string $file_path
   *   The absolute path to the CSV file.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function validate_csv_file($file_path, FormStateInterface $form_state) {
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

    $this->check_required_columns($headers, $form_state);
  }

  /**
   * Checks if CSV has required columns.
   *
   * @param array $headers
   *   The CSV header row.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  protected function check_required_columns(array $headers, FormStateInterface $form_state) {
    $headers_lower = array_map('strtolower', $headers);
    $has_name = in_array('name', $headers_lower) || in_array('title', $headers_lower);
    $has_email = in_array('email', $headers_lower);

    if (!$has_name) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file must have a "Name" or "Title" column.'));
    }

    if (!$has_email) {
      $form_state->setErrorByName('csv_file', $this->t('The CSV file must have an "Email" column.'));
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
      'skip_duplicates' => $form_state->getValue('skip_duplicates'),
      'update_existing' => $form_state->getValue('update_existing'),
      'create_missing_orgs' => $form_state->getValue('create_missing_orgs'),
    ];

    $results = $this->process_csv($file_path, $options);
    $this->display_import_results($results);

    // Redirect to contacts list.
    $form_state->setRedirect('view.my_contacts.page_1');
  }

  /**
   * Displays import results as messages.
   *
   * @param array $results
   *   Array with counts: created, updated, skipped, errors.
   */
  protected function display_import_results(array $results) {
    $this->messenger()->addStatus($this->t('Import completed successfully!'));
    $this->messenger()->addStatus($this->t('Created: @created contacts', ['@created' => $results['created']]));

    if ($results['updated'] > 0) {
      $this->messenger()->addStatus($this->t('Updated: @updated contacts', ['@updated' => $results['updated']]));
    }

    if ($results['skipped'] > 0) {
      $this->messenger()->addWarning($this->t('Skipped: @skipped duplicates', ['@skipped' => $results['skipped']]));
    }

    if ($results['errors'] > 0) {
      $this->messenger()->addError($this->t('Errors: @errors rows had errors', ['@errors' => $results['errors']]));
    }
  }

  /**
   * Processes the CSV file and imports contacts.
   *
   * @param string $file_path
   *   The absolute path to the CSV file.
   * @param array $options
   *   Import options (skip_duplicates, update_existing, create_missing_orgs).
   *
   * @return array
   *   Results array with counts: created, updated, skipped, errors.
   */
  protected function process_csv($file_path, array $options) {
    $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

    $handle = fopen($file_path, 'r');
    if ($handle === FALSE) {
      return $results;
    }

    $headers = $this->read_csv_headers($handle);
    $row_number = 1;

    while (($data = fgetcsv($handle, 0, ',', '"', '')) !== FALSE) {
      $row_number++;
      $row = $this->map_csv_row($headers, $data);

      try {
        $this->process_contact_row($row, $options, $results);
      }
      catch (\Exception $e) {
        $this->log_import_error($row_number, $e->getMessage());
        $results['errors']++;
      }
    }

    fclose($handle);
    return $results;
  }

  /**
   * Reads and normalizes CSV headers.
   *
   * @param resource $handle
   *   The file handle.
   *
   * @return array
   *   Array of lowercase, trimmed header names.
   */
  protected function read_csv_headers($handle) {
    $headers = fgetcsv($handle, 0, ',', '"', '');
    $headers = array_map('strtolower', $headers);
    return array_map('trim', $headers);
  }

  /**
   * Maps CSV data to associative array.
   *
   * @param array $headers
   *   The CSV headers.
   * @param array $data
   *   The CSV data row.
   *
   * @return array
   *   Associative array mapping headers to values.
   */
  protected function map_csv_row(array $headers, array $data) {
    $row = [];
    foreach ($headers as $index => $header) {
      $row[$header] = isset($data[$index]) ? trim($data[$index]) : '';
    }
    return $row;
  }

  /**
   * Processes a single contact row from CSV.
   *
   * @param array $row
   *   The contact data row.
   * @param array $options
   *   Import options.
   * @param array &$results
   *   Results array to update.
   */
  protected function process_contact_row(array $row, array $options, array &$results) {
    $name = $row['name'] ?? $row['title'] ?? '';
    $email = $row['email'] ?? '';

    if (empty($name) || empty($email)) {
      $results['errors']++;
      return;
    }

    $existing = $this->find_existing_contact($email);

    if ($existing && $options['skip_duplicates'] && !$options['update_existing']) {
      $results['skipped']++;
      return;
    }

    if ($existing && $options['update_existing']) {
      $this->update_contact($existing, $row, $options['create_missing_orgs']);
      $results['updated']++;
    }
    else {
      $this->create_contact($row, $options['create_missing_orgs']);
      $results['created']++;
    }
  }

  /**
   * Logs an import error.
   *
   * @param int $row_number
   *   The CSV row number.
   * @param string $message
   *   The error message.
   */
  protected function log_import_error($row_number, $message) {
    \Drupal::logger('crm_import_export')->error(
      'Error importing row @row: @message',
      ['@row' => $row_number, '@message' => $message]
    );
  }

  /**
   * Finds existing contact by email.
   *
   * @param string $email
   *   The email address to search for.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The contact node if found, NULL otherwise.
   */
  protected function find_existing_contact($email) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_email', $email)
      ->accessCheck(FALSE)
      ->range(0, 1);

    $nids = $query->execute();

    if (!empty($nids)) {
      return Node::load(reset($nids));
    }

    return NULL;
  }

  /**
   * Creates a new contact from CSV row.
   *
   * @param array $row
   *   The CSV row data.
   * @param bool $create_missing_orgs
   *   Whether to create missing organizations.
   *
   * @return \Drupal\node\Entity\Node
   *   The created contact node.
   */
  protected function create_contact(array $row, $create_missing_orgs) {
    $values = [
      'type' => 'contact',
      'title' => $row['name'] ?? $row['title'],
      'field_email' => $row['email'] ?? '',
      'field_phone' => $row['phone'] ?? '',
      'field_position' => $row['position'] ?? '',
      'field_owner' => \Drupal::currentUser()->id(),
      'status' => 1,
      'uid' => \Drupal::currentUser()->id(),
    ];

    // Handle organization.
    if (!empty($row['organization'])) {
      $org = $this->find_or_create_organization($row['organization'], $create_missing_orgs);
      if ($org) {
        $values['field_organization'] = $org->id();
      }
    }

    // Handle LinkedIn.
    if (!empty($row['linkedin'])) {
      $values['field_linkedin'] = [
        'uri' => $row['linkedin'],
        'title' => 'LinkedIn Profile',
      ];
    }

    // Handle status.
    if (!empty($row['status'])) {
      $values['field_status'] = strtolower($row['status']);
    }

    $contact = Node::create($values);
    $contact->save();

    return $contact;
  }

  /**
   * Updates an existing contact from CSV row.
   *
   * @param \Drupal\node\Entity\Node $contact
   *   The contact node to update.
   * @param array $row
   *   The CSV row data.
   * @param bool $create_missing_orgs
   *   Whether to create missing organizations.
   *
   * @return \Drupal\node\Entity\Node
   *   The updated contact node.
   */
  protected function update_contact(Node $contact, array $row, $create_missing_orgs) {
    $this->update_field_if_present($contact, 'field_phone', $row['phone'] ?? '');
    $this->update_field_if_present($contact, 'field_position', $row['position'] ?? '');
    $this->update_field_if_present($contact, 'field_status', strtolower($row['status'] ?? ''));

    // Update organization.
    if (!empty($row['organization'])) {
      $org = $this->find_or_create_organization($row['organization'], $create_missing_orgs);
      if ($org) {
        $contact->set('field_organization', $org->id());
      }
    }

    // Update LinkedIn.
    if (!empty($row['linkedin'])) {
      $contact->set('field_linkedin', [
        'uri' => $row['linkedin'],
        'title' => 'LinkedIn Profile',
      ]);
    }

    $contact->save();
    return $contact;
  }

  /**
   * Updates a field if value is present.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node to update.
   * @param string $field_name
   *   The field name.
   * @param mixed $value
   *   The field value.
   */
  protected function update_field_if_present(Node $node, $field_name, $value) {
    if (!empty($value)) {
      $node->set($field_name, $value);
    }
  }

  /**
   * Finds or creates organization by name.
   *
   * @param string $org_name
   *   The organization name.
   * @param bool $create_if_missing
   *   Whether to create if not found.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The organization node, or NULL if not found and not created.
   */
  protected function find_or_create_organization($org_name, $create_if_missing) {
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

    // Create if allowed.
    if ($create_if_missing) {
      $org = Node::create([
        'type' => 'organization',
        'title' => $org_name,
        'field_owner' => \Drupal::currentUser()->id(),
        'status' => 1,
        'uid' => \Drupal::currentUser()->id(),
      ]);
      $org->save();

      return $org;
    }

    return NULL;
  }

}
