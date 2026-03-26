<?php

namespace Drupal\crm_deduplication\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;

/**
 * Service for detecting and managing duplicate CRM entities.
 */
class DuplicateDetectionService {

  /**
   * Database connection.
   */
  protected Connection $database;

  /**
   * Entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger.
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(
    Connection $database,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('crm_deduplication');
  }

  /**
   * Find duplicate contacts.
   *
   * @return array
   *   Array of duplicate groups.
   */
  public function findDuplicateContacts(): array {
    $duplicates = [];

    // 1. Exact email match (highest confidence)
    $exact_email = $this->findExactEmailDuplicates('contact');
    $duplicates = array_merge($duplicates, $exact_email);

    // 2. Exact phone match (high confidence)
    $exact_phone = $this->findExactPhoneDuplicates('contact');
    $duplicates = array_merge($duplicates, $exact_phone);

    // 3. Fuzzy name match + same organization (medium confidence)
    $fuzzy_name = $this->findFuzzyNameDuplicates('contact');
    $duplicates = array_merge($duplicates, $fuzzy_name);

    return $this->deduplicate($duplicates);
  }

  /**
   * Find duplicate organizations.
   */
  public function findDuplicateOrganizations(): array {
    $duplicates = [];

    // 1. Exact email + phone
    $exact_email = $this->findExactEmailDuplicates('organization');
    $duplicates = array_merge($duplicates, $exact_email);

    // 2. Fuzzy name match with domain
    $fuzzy_name = $this->findFuzzyNameDuplicates('organization');
    $duplicates = array_merge($duplicates, $fuzzy_name);

    return $this->deduplicate($duplicates);
  }

  /**
   * Find entities with exact email match.
   */
  protected function findExactEmailDuplicates(string $bundle): array {
    $duplicates = [];

    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_email', 'fe', 'fe.entity_id = n.nid AND fe.deleted = 0');
    $query->condition('n.type', $bundle);
    $query->condition('n.status', 1);
    $query->isNotNull('fe.field_email_value');
    $query->addField('fe', 'field_email_value', 'email');
    $query->addField('n', 'nid');
    $query->addField('n', 'title');
    
    $results = $query->execute()->fetchAll();

    // Group by email
    $email_groups = [];
    foreach ($results as $row) {
      $email = strtolower(trim($row->email));
      $email_groups[$email][] = [
        'nid' => $row->nid,
        'title' => $row->title,
        'confidence' => 99, // Exact match
      ];
    }

    // Only include groups with duplicates
    foreach ($email_groups as $email => $nodes) {
      if (count($nodes) > 1) {
        $duplicates[] = [
          'type' => 'exact_email',
          'match_value' => $email,
          'nodes' => $nodes,
          'confidence' => 99,
        ];
      }
    }

    return $duplicates;
  }

  /**
   * Find entities with exact phone match.
   */
  protected function findExactPhoneDuplicates(string $bundle): array {
    $duplicates = [];

    $query = $this->database->select('node_field_data', 'n');
    $query->leftJoin('node__field_phone', 'fp', 'fp.entity_id = n.nid AND fp.deleted = 0');
    $query->condition('n.type', $bundle);
    $query->condition('n.status', 1);
    $query->isNotNull('fp.field_phone_value');
    $query->addField('fp', 'field_phone_value', 'phone');
    $query->addField('n', 'nid');
    $query->addField('n', 'title');
    
    $results = $query->execute()->fetchAll();

    // Group by phone
    $phone_groups = [];
    foreach ($results as $row) {
      $phone = $this->normalizePhone($row->phone);
      if (!empty($phone)) {
        $phone_groups[$phone][] = [
          'nid' => $row->nid,
          'title' => $row->title,
          'confidence' => 95, // Near exact
        ];
      }
    }

    // Only include groups with duplicates
    foreach ($phone_groups as $phone => $nodes) {
      if (count($nodes) > 1) {
        $duplicates[] = [
          'type' => 'exact_phone',
          'match_value' => $phone,
          'nodes' => $nodes,
          'confidence' => 95,
        ];
      }
    }

    return $duplicates;
  }

  /**
   * Find entities with fuzzy name match.
   */
  protected function findFuzzyNameDuplicates(string $bundle): array {
    $duplicates = [];

    // Load all active entities
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', $bundle)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE);

    $nids = $query->execute();
    $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);

    // Compare each pair
    $checked = [];
    foreach ($nodes as $node1) {
      foreach ($nodes as $node2) {
        // Skip if same node or already compared
        if ($node1->id() >= $node2->id() || isset($checked[$node1->id() . ':' . $node2->id()])) {
          continue;
        }
        $checked[$node1->id() . ':' . $node2->id()] = TRUE;

        // Calculate similarity
        $similarity = $this->calculateSimilarity($node1, $node2, $bundle);
        if ($similarity >= 75) { // 75% match threshold
          $duplicates[] = [
            'type' => 'fuzzy_name',
            'match_value' => 'Name similarity',
            'nodes' => [
              ['nid' => $node1->id(), 'title' => $node1->getTitle(), 'confidence' => $similarity],
              ['nid' => $node2->id(), 'title' => $node2->getTitle(), 'confidence' => $similarity],
            ],
            'confidence' => $similarity,
          ];
        }
      }
    }

    return $duplicates;
  }

  /**
   * Calculate similarity between two nodes.
   */
  protected function calculateSimilarity(NodeInterface $node1, NodeInterface $node2, string $bundle): int {
    $title1 = strtolower($node1->getTitle());
    $title2 = strtolower($node2->getTitle());

    // Levenshtein distance for name similarity
    $max_len = max(strlen($title1), strlen($title2));
    if ($max_len === 0) {
      return 0;
    }

    $distance = levenshtein($title1, $title2);
    $similarity = (int) ceil((1 - ($distance / $max_len)) * 100);

    // Check if same organization (for contacts)
    if ($bundle === 'contact' && $node1->hasField('field_organization') && $node2->hasField('field_organization')) {
      $org1 = $node1->get('field_organization')->target_id ?? NULL;
      $org2 = $node2->get('field_organization')->target_id ?? NULL;
      
      if ($org1 && $org1 === $org2) {
        $similarity += 10; // Boost score if same organization
      }
    }

    // Cap at 100
    return min(100, $similarity);
  }

  /**
   * Merge two nodes.
   *
   * @param int $master_nid
   *   The NID of master record (keep this)
   * @param int $duplicate_nid
   *   The NID of duplicate record (merge into master)
   * @param array $field_preferences
   *   Which fields to keep from each (optional)
   *
   * @return bool
   *   Success status
   */
  public function mergeNodes(int $master_nid, int $duplicate_nid, array $field_preferences = []): bool {
    try {
      $master = $this->entityTypeManager->getStorage('node')->load($master_nid);
      $duplicate = $this->entityTypeManager->getStorage('node')->load($duplicate_nid);

      if (!$master || !$duplicate || $master->bundle() !== $duplicate->bundle()) {
        return FALSE;
      }

      // 1. Redirect all relationships from duplicate to master
      $this->redirectRelationships($duplicate_nid, $master_nid, $duplicate->bundle());

      // 2. Merge field values (prefer master, fill blanks from duplicate)
      $this->mergeFieldValues($master, $duplicate, $field_preferences);

      // 3. Soft delete the duplicate record
      $duplicate->set('field_deleted_at', \Drupal::time()->getCurrentTime());
      $duplicate->save();

      // 4. Log the merge
      $this->logger->notice(
        'Merged duplicate @duplicate_id into master @master_id',
        ['@master_id' => $master_nid, '@duplicate_id' => $duplicate_nid]
      );

      \Drupal::cache()->invalidateTags(['node_list', 'node:' . $master_nid, 'node:' . $duplicate_nid]);

      return TRUE;
    } catch (\Exception $e) {
      $this->logger->error('Merge failed: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Redirect relationships from one node to another.
   */
  protected function redirectRelationships(int $from_nid, int $to_nid, string $bundle): void {
    // Find all nodes that reference the duplicate
    $reference_fields = [
      'contact' => ['field_contact'],
      'deal' => ['field_contact', 'field_organization'],
      'organization' => ['field_organization'],
      'activity' => ['field_contact', 'field_deal', 'field_organization'],
    ];

    $fields = $reference_fields[$bundle] ?? [];

    foreach ($fields as $field) {
      $entities = $this->database->select('node__' . $field, 'f')
        ->fields('f', ['entity_id'])
        ->condition('f.' . $field . '_target_id', $from_nid)
        ->execute()
        ->fetchAll();

      foreach ($entities as $row) {
        $this->database->update('node__' . $field)
          ->fields([$field . '_target_id' => $to_nid])
          ->condition('entity_id', $row->entity_id)
          ->execute();
      }
    }
  }

  /**
   * Merge field values from duplicate into master.
   */
  protected function mergeFieldValues(NodeInterface $master, NodeInterface $duplicate, array $preferences = []): void {
    $fields = $master->getFields();

    foreach ($fields as $field_name => $field) {
      // Skip special fields
      if (in_array($field_name, ['nid', 'vid', 'uid', 'created', 'changed', 'status'])) {
        continue;
      }

      // If master field is empty, fill from duplicate
      if ($master->get($field_name)->isEmpty() && !$duplicate->get($field_name)->isEmpty()) {
        if (isset($preferences[$field_name]) && $preferences[$field_name] === 'duplicate') {
          $master->set($field_name, $duplicate->get($field_name)->getValue());
        } elseif (!isset($preferences[$field_name])) {
          $master->set($field_name, $duplicate->get($field_name)->getValue());
        }
      }
    }

    $master->save();
  }

  /**
   * Normalize phone number for comparison.
   */
  protected function normalizePhone(string $phone): string {
    // Remove all non-digit characters except +
    $normalized = preg_replace('/[^\d+]/', '', $phone);
    
    // If starts with +, keep it; otherwise remove leading 0 for country code detection
    if (substr($normalized, 0, 1) !== '+' && substr($normalized, 0, 1) === '0') {
      $normalized = substr($normalized, 1);
    }

    return $normalized;
  }

  /**
   * Remove duplicate entries from results array.
   */
  protected function deduplicate(array $duplicates): array {
    $seen = [];
    $unique = [];

    foreach ($duplicates as $group) {
      $key = implode(':', array_map(fn($n) => $n['nid'], $group['nodes']));
      if (!isset($seen[$key])) {
        $seen[$key] = TRUE;
        $unique[] = $group;
      }
    }

    return $unique;
  }
}
