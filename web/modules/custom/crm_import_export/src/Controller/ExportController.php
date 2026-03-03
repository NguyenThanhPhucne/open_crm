<?php

namespace Drupal\crm_import_export\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\node\Entity\Node;

/**
 * Controller for Export operations.
 */
class ExportController extends ControllerBase {

  /**
   * Export contacts to CSV.
   */
  public function exportContacts() {
    // Get current user to filter data
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Query contacts owned by current user only
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');
    
    $nids = $query->execute();
    $contacts = Node::loadMultiple($nids);

    // Prepare CSV data
    $csv_data = [];
    
    // Headers
    $headers = [
      'ID',
      'Name',
      'Email',
      'Phone',
      'Position',
      'Organization',
      'Owner',
      'Source',
      'Customer Type',
      'Status',
      'Tags',
      'LinkedIn',
      'Last Contacted',
      'Created Date',
      'Modified Date',
    ];
    $csv_data[] = $headers;

    // Data rows
    foreach ($contacts as $contact) {
      $row = [];
      $row[] = $contact->id();
      $row[] = $contact->getTitle();
      $row[] = $contact->hasField('field_email') && !$contact->get('field_email')->isEmpty() 
        ? $contact->get('field_email')->value : '';
      $row[] = $contact->hasField('field_phone') && !$contact->get('field_phone')->isEmpty() 
        ? $contact->get('field_phone')->value : '';
      $row[] = $contact->hasField('field_position') && !$contact->get('field_position')->isEmpty() 
        ? $contact->get('field_position')->value : '';
      
      // Organization
      if ($contact->hasField('field_organization') && !$contact->get('field_organization')->isEmpty()) {
        $org = $contact->get('field_organization')->entity;
        $row[] = $org ? $org->getTitle() : '';
      } else {
        $row[] = '';
      }
      
      // Owner
      if ($contact->hasField('field_owner') && !$contact->get('field_owner')->isEmpty()) {
        $owner = $contact->get('field_owner')->entity;
        $row[] = $owner ? $owner->getDisplayName() : '';
      } else {
        $row[] = '';
      }
      
      // Source
      if ($contact->hasField('field_source') && !$contact->get('field_source')->isEmpty()) {
        $source = $contact->get('field_source')->entity;
        $row[] = $source ? $source->getName() : '';
      } else {
        $row[] = '';
      }
      
      // Customer Type
      if ($contact->hasField('field_customer_type') && !$contact->get('field_customer_type')->isEmpty()) {
        $type = $contact->get('field_customer_type')->entity;
        $row[] = $type ? $type->getName() : '';
      } else {
        $row[] = '';
      }
      
      // Status
      if ($contact->hasField('field_status') && !$contact->get('field_status')->isEmpty()) {
        $status = $contact->get('field_status')->value;
        $row[] = $status;
      } else {
        $row[] = '';
      }
      
      // Tags
      if ($contact->hasField('field_tags') && !$contact->get('field_tags')->isEmpty()) {
        $tags = [];
        foreach ($contact->get('field_tags') as $tag) {
          if ($tag->entity) {
            $tags[] = $tag->entity->getName();
          }
        }
        $row[] = implode(', ', $tags);
      } else {
        $row[] = '';
      }
      
      // LinkedIn
      if ($contact->hasField('field_linkedin') && !$contact->get('field_linkedin')->isEmpty()) {
        $row[] = $contact->get('field_linkedin')->uri;
      } else {
        $row[] = '';
      }
      
      // Last Contacted
      if ($contact->hasField('field_last_contacted') && !$contact->get('field_last_contacted')->isEmpty()) {
        $row[] = date('Y-m-d H:i:s', strtotime($contact->get('field_last_contacted')->value));
      } else {
        $row[] = '';
      }
      
      $row[] = date('Y-m-d H:i:s', $contact->getCreatedTime());
      $row[] = date('Y-m-d H:i:s', $contact->getChangedTime());
      
      $csv_data[] = $row;
    }

    // Generate CSV
    $csv_content = $this->arrayToCsv($csv_data);
    
    // Create response
    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="contacts_export_' . date('Y-m-d_His') . '.csv"');
    
    return $response;
  }

  /**
   * Export deals to CSV.
   */
  public function exportDeals() {
    // Get current user to filter data
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    
    // Query deals owned by current user only
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('field_owner', $user_id)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC');
    
    $nids = $query->execute();
    $deals = Node::loadMultiple($nids);

    // Prepare CSV data
    $csv_data = [];
    
    // Headers
    $headers = [
      'ID',
      'Title',
      'Amount',
      'Stage',
      'Probability',
      'Contact',
      'Organization',
      'Owner',
      'Expected Close Date',
      'Closing Date',
      'Status',
      'Notes',
      'Lost Reason',
      'Created Date',
      'Modified Date',
    ];
    $csv_data[] = $headers;

    // Data rows
    foreach ($deals as $deal) {
      $row = [];
      $row[] = $deal->id();
      $row[] = $deal->getTitle();
      $row[] = $deal->hasField('field_amount') && !$deal->get('field_amount')->isEmpty() 
        ? $deal->get('field_amount')->value : '0';
      
      // Stage
      if ($deal->hasField('field_stage') && !$deal->get('field_stage')->isEmpty()) {
        $stage = $deal->get('field_stage')->entity;
        $row[] = $stage ? $stage->getName() : '';
      } else {
        $row[] = '';
      }
      
      // Probability
      $row[] = $deal->hasField('field_probability') && !$deal->get('field_probability')->isEmpty() 
        ? $deal->get('field_probability')->value : '';
      
      // Contact
      if ($deal->hasField('field_contact') && !$deal->get('field_contact')->isEmpty()) {
        $contact = $deal->get('field_contact')->entity;
        $row[] = $contact ? $contact->getTitle() : '';
      } else {
        $row[] = '';
      }
      
      // Organization
      if ($deal->hasField('field_organization') && !$deal->get('field_organization')->isEmpty()) {
        $org = $deal->get('field_organization')->entity;
        $row[] = $org ? $org->getTitle() : '';
      } else {
        $row[] = '';
      }
      
      // Owner
      if ($deal->hasField('field_owner') && !$deal->get('field_owner')->isEmpty()) {
        $owner = $deal->get('field_owner')->entity;
        $row[] = $owner ? $owner->getDisplayName() : '';
      } else {
        $row[] = '';
      }
      
      // Expected Close Date
      if ($deal->hasField('field_expected_close_date') && !$deal->get('field_expected_close_date')->isEmpty()) {
        $row[] = date('Y-m-d', strtotime($deal->get('field_expected_close_date')->value));
      } else {
        $row[] = '';
      }
      
      // Closing Date
      if ($deal->hasField('field_closing_date') && !$deal->get('field_closing_date')->isEmpty()) {
        $row[] = date('Y-m-d', strtotime($deal->get('field_closing_date')->value));
      } else {
        $row[] = '';
      }
      
      // Status (won/lost)
      $row[] = $deal->isPublished() ? 'Active' : 'Closed';
      
      // Notes
      if ($deal->hasField('field_notes') && !$deal->get('field_notes')->isEmpty()) {
        $notes = strip_tags($deal->get('field_notes')->value);
        $row[] = substr($notes, 0, 200); // Limit to 200 chars
      } else {
        $row[] = '';
      }
      
      // Lost Reason
      if ($deal->hasField('field_lost_reason') && !$deal->get('field_lost_reason')->isEmpty()) {
        $lost_reason = strip_tags($deal->get('field_lost_reason')->value);
        $row[] = substr($lost_reason, 0, 200);
      } else {
        $row[] = '';
      }
      
      $row[] = date('Y-m-d H:i:s', $deal->getCreatedTime());
      $row[] = date('Y-m-d H:i:s', $deal->getChangedTime());
      
      $csv_data[] = $row;
    }

    // Generate CSV
    $csv_content = $this->arrayToCsv($csv_data);
    
    // Create response
    $response = new Response($csv_content);
    $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="deals_export_' . date('Y-m-d_His') . '.csv"');
    
    return $response;
  }

  /**
   * Convert array to CSV string.
   */
  private function arrayToCsv(array $data) {
    $output = fopen('php://temp', 'r+');
    
    // Add BOM for UTF-8
    fputs($output, "\xEF\xBB\xBF");
    
    foreach ($data as $row) {
      fputcsv($output, $row);
    }
    
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    
    return $csv;
  }

}
