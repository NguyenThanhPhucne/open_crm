<?php

namespace Drupal\crm\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for enhanced user profile pages.
 */
class UserProfileController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static();
  }

  /**
   * Custom access check for user profile.
   */
  public function access(UserInterface $user, AccountInterface $account) {
    // Allow users to view their own profile
    if ($account->id() == $user->id()) {
      return AccessResult::allowed();
    }
    
    // Allow users with 'access user profiles' permission
    if ($account->hasPermission('access user profiles')) {
      return AccessResult::allowed();
    }
    
    return AccessResult::forbidden();
  }

  /**
   * Get title for user profile page.
   */
  public function getTitle(UserInterface $user) {
    return $user->getDisplayName();
  }

  /**
   * Display enhanced user profile page.
   */
  public function view(UserInterface $user) {
    $current_user = $this->currentUser();
    $uid = $user->id();
    
    // Check if current user is viewing their own profile
    $is_own_profile = ($current_user->id() == $uid);
    
    // Get user basic info
    $user_name = $user->getDisplayName();
    $user_email = $user->getEmail();
    $user_created = $user->getCreatedTime();
    $user_roles = $user->getRoles();
    
    // Get role names (exclude authenticated)
    $role_names = [];
    $role_storage = \Drupal::entityTypeManager()->getStorage('user_role');
    foreach ($user_roles as $role_id) {
      if ($role_id !== 'authenticated') {
        $role = $role_storage->load($role_id);
        if ($role) {
          $role_names[] = $role->label();
        }
      }
    }
    
    // Get team info
    $team_name = '';
    if ($user->hasField('field_team') && !$user->get('field_team')->isEmpty()) {
      $team = $user->get('field_team')->entity;
      if ($team) {
        $team_name = $team->label();
      }
    }
    
    // Get user picture
    $user_picture_url = '';
    $has_picture = FALSE;
    if ($user->hasField('user_picture') && !$user->get('user_picture')->isEmpty()) {
      $picture = $user->get('user_picture')->entity;
      if ($picture) {
        $file_uri = $picture->getFileUri();
        
        // Try to use medium image style
        $image_style = \Drupal::service('entity_type.manager')
          ->getStorage('image_style')
          ->load('medium');
        
        if ($image_style) {
          $user_picture_url = $image_style->buildUrl($file_uri);
        } else {
          // Fallback to original file URL
          $file_url_generator = \Drupal::service('file_url_generator');
          $user_picture_url = $file_url_generator->generateAbsoluteString($file_uri);
        }
        
        $has_picture = TRUE;
        
        // Debug logging
        \Drupal::logger('crm')->notice('User @uid picture: @url (has_picture: @has)', [
          '@uid' => $uid,
          '@url' => $user_picture_url,
          '@has' => $has_picture ? 'TRUE' : 'FALSE',
        ]);
      }
    } else {
      // Debug logging for no picture
      \Drupal::logger('crm')->notice('User @uid: No picture found (hasField: @has, isEmpty: @empty)', [
        '@uid' => $uid,
        '@has' => $user->hasField('user_picture') ? 'TRUE' : 'FALSE',
        '@empty' => ($user->hasField('user_picture') && $user->get('user_picture')->isEmpty()) ? 'TRUE' : 'FALSE',
      ]);
    }
    
    // Get statistics
    $stats = [
      'contacts' => $this->getUserContactsCount($uid),
      'deals' => $this->getUserDealsCount($uid),
      'organizations' => $this->getUserOrganizationsCount($uid),
      'activities' => $this->getUserActivitiesCount($uid),
    ];
    
    // Get recent activities
    $recent_activities = $this->getRecentActivities($uid, 5);
    
    // Get recent deals
    $recent_deals = $this->getRecentDeals($uid, 5);
    
    return [
      '#theme' => 'crm_user_profile',
      '#user' => $user,
      '#user_name' => $user_name,
      '#user_email' => $user_email,
      '#user_created' => $user_created,
      '#user_picture_url' => $user_picture_url,
      '#has_picture' => $has_picture,
      '#role_names' => $role_names,
      '#team_name' => $team_name,
      '#stats' => $stats,
      '#recent_activities' => $recent_activities,
      '#recent_deals' => $recent_deals,
      '#current_user_id' => \Drupal::currentUser()->id(),
      '#is_own_profile' => $is_own_profile,
      '#cache' => [
        'max-age' => 0,
        'contexts' => ['user'],
        'tags' => ['user:' . $uid],
      ],
      '#attached' => [
        'library' => [
          'crm/user_profile_styles',
        ],
      ],
    ];
  }

  /**
   * Get count of contacts owned by user.
   */
  protected function getUserContactsCount($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('status', 1)
      ->condition('field_owner', $uid)
      ->accessCheck(FALSE);
    return $query->count()->execute();
  }

  /**
   * Get count of deals owned by user.
   */
  protected function getUserDealsCount($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_owner', $uid)
      ->accessCheck(FALSE);
    return $query->count()->execute();
  }

  /**
   * Get count of organizations assigned to user.
   */
  protected function getUserOrganizationsCount($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'organization')
      ->condition('status', 1)
      ->condition('field_assigned_staff', $uid)
      ->accessCheck(FALSE);
    return $query->count()->execute();
  }

  /**
   * Get count of activities assigned to user.
   */
  protected function getUserActivitiesCount($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('status', 1)
      ->condition('field_assigned_to', $uid)
      ->accessCheck(FALSE);
    return $query->count()->execute();
  }

  /**
   * Get recent activities for user.
   */
  protected function getRecentActivities($uid, $limit = 5) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'activity')
      ->condition('status', 1)
      ->condition('field_assigned_to', $uid)
      ->sort('created', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    $activities = [];
    if (!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($nids);
      
      foreach ($nodes as $node) {
        $activities[] = [
          'id' => $node->id(),
          'title' => $node->label(),
          'type' => $node->hasField('field_activity_type') && !$node->get('field_activity_type')->isEmpty()
            ? $node->get('field_activity_type')->entity->label()
            : 'Activity',
          'date' => $node->hasField('field_activity_date') && !$node->get('field_activity_date')->isEmpty()
            ? $node->get('field_activity_date')->value
            : '',
          'url' => $node->toUrl()->toString(),
        ];
      }
    }
    
    return $activities;
  }

  /**
   * Get recent deals for user.
   */
  protected function getRecentDeals($uid, $limit = 5) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_owner', $uid)
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    $deals = [];
    if (!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($nids);
      
      foreach ($nodes as $node) {
        $stage = '';
        if ($node->hasField('field_deal_stage') && !$node->get('field_deal_stage')->isEmpty()) {
          $stage = $node->get('field_deal_stage')->entity->label();
        }
        
        $value = '';
        if ($node->hasField('field_deal_value') && !$node->get('field_deal_value')->isEmpty()) {
          $value = number_format($node->get('field_deal_value')->value, 0, ',', '.');
        }
        
        $deals[] = [
          'id' => $node->id(),
          'title' => $node->label(),
          'stage' => $stage,
          'value' => $value,
          'url' => $node->toUrl()->toString(),
        ];
      }
    }
    
    return $deals;
  }

}
