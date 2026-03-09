<?php

namespace Drupal\crm_test\Tests;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests for CRM data integrity validations.
 *
 * @group crm
 */
class CrmDataIntegrityTest extends BrowserTestBase {

  protected static $modules = ['crm', 'crm_import_export', 'node', 'user'];

  protected $adminUser;
  protected $repUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test users.
    $this->adminUser = $this->createUser(['administer nodes']);
    $this->repUser = $this->createUser(['create deal content', 'create contact content']);
  }

  /**
   * Test deal creation without owner auto-assigns to current user.
   */
  public function testDealOwnerAutoAssignment() {
    $this->drupalLogin($this->repUser);

    $deal = Node::create([
      'type' => 'deal',
      'title' => 'Test Deal',
      'field_amount' => 50000,
    ]);
    
    // Before save, owner is empty
    $this->assertTrue($deal->get('field_owner')->isEmpty());

    // After presave hook, should be auto-assigned
    $deal->save();

    // Reload to verify
    $deal = Node::load($deal->id());
    $this->assertFalse($deal->get('field_owner')->isEmpty());
    $this->assertEqual($deal->get('field_owner')->target_id, $this->repUser->id());
  }

  /**
   * Test deal creation requires contact or organization.
   */
  public function testDealRequiresContactOrOrganization() {
    $this->drupalLogin($this->repUser);

    // Try to create deal without contact or org
    try {
      $deal = Node::create([
        'type' => 'deal',
        'title' => 'Deal Without Contact',
        'field_amount' => 10000,
      ]);
      $deal->save();
      $this->fail('Should throw exception for deal without contact/org');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertStringContainsString('Contact', $e->getMessage());
    }
  }

  /**
   * Test deal amount validation.
   */
  public function testDealAmountValidation() {
    $this->drupalLogin($this->repUser);

    // Create contact for reference
    $contact = Node::create(['type' => 'contact', 'title' => 'Test Contact']);
    $contact->save();

    // Try to create deal with negative amount
    try {
      $deal = Node::create([
        'type' => 'deal',
        'title' => 'Negative Deal',
        'field_amount' => -5000,
        'field_contact' => $contact->id(),
      ]);
      $deal->save();
      $this->fail('Should reject negative deal amount');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertStringContainsString('negative', strtolower($e->getMessage()));
    }
  }

  /**
   * Test stage format normalization.
   */
  public function testStageFormatNormalization() {
    $this->drupalLogin($this->repUser);

    // Create contact
    $contact = Node::create(['type' => 'contact', 'title' => 'Contact']);
    $contact->save();

    // Create deal with numeric stage (legacy)
    $deal = Node::create([
      'type' => 'deal',
      'title' => 'Legacy Deal',
      'field_amount' => 50000,
      'field_contact' => $contact->id(),
      'field_stage' => 5, // Numeric value (old format)
    ]);
    $deal->save();

    // After presave, should be converted to string
    $deal = Node::load($deal->id());
    $this->assertEqual($deal->get('field_stage')->value, 'closed_won');
  }

  /**
   * Test activity requires contact or deal.
   */
  public function testActivityRequiresContactOrDeal() {
    $this->drupalLogin($this->repUser);

    // Try to create activity without contact or deal
    try {
      $activity = Node::create([
        'type' => 'activity',
        'title' => 'Orphan Activity',
      ]);
      $activity->save();
      $this->fail('Should throw exception for activity without contact/deal');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertStringContainsString('Contact', $e->getMessage());
    }
  }

  /**
   * Test activity auto-assignment.
   */
  public function testActivityAutoAssignment() {
    $this->drupalLogin($this->repUser);

    // Create contact
    $contact = Node::create(['type' => 'contact', 'title' => 'Contact']);
    $contact->save();

    // Create activity without assigned_to
    $activity = Node::create([
      'type' => 'activity',
      'title' => 'Test Activity',
      'field_contact' => $contact->id(),
    ]);
    $activity->save();

    // Verify auto-assigned
    $activity = Node::load($activity->id());
    $this->assertEqual($activity->get('field_assigned_to')->target_id, $this->repUser->id());
  }

  /**
   * Test contact requires phone.
   */
  public function testContactRequiresPhone() {
    $this->drupalLogin($this->repUser);

    try {
      $contact = Node::create([
        'type' => 'contact',
        'title' => 'No Phone Contact',
      ]);
      $contact->save();
      $this->fail('Should require phone for contact');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertStringContainsString('phone', strtolower($e->getMessage()));
    }
  }
}
