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
    $current_user = \Drupal::currentUser();
    if ($current_user->id() == $user->id()) {
      return $this->t('My Profile');
    }
    return $this->t('@name — Profile', ['@name' => $user->getDisplayName()]);
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
    // Capitalize first letter of username for professional display
    $user_name = ucfirst($user_name);
    // Get first letter for avatar placeholder
    $user_initial = strtoupper(mb_substr($user_name, 0, 1));
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
      }
    }
    
    // Get statistics
    $total_deal_value = $this->getUserTotalDealValue($uid);
    $total_value_formatted = '';
    if ($total_deal_value >= 1000000000) {
      $total_value_formatted = '$' . number_format($total_deal_value / 1000000000, 1) . 'B';
    } elseif ($total_deal_value >= 1000000) {
      $total_value_formatted = '$' . number_format($total_deal_value / 1000000, 1) . 'M';
    } elseif ($total_deal_value >= 1000) {
      $total_value_formatted = '$' . number_format($total_deal_value / 1000, 0) . 'K';
    } elseif ($total_deal_value > 0) {
      $total_value_formatted = '$' . number_format($total_deal_value, 0);
    }

    $stats = [
      'contacts' => $this->getUserContactsCount($uid),
      'deals' => $this->getUserDealsCount($uid),
      'organizations' => $this->getUserOrganizationsCount($uid),
      'activities' => $this->getUserActivitiesCount($uid),
      'total_value' => $total_deal_value,
      'total_value_formatted' => $total_value_formatted,
    ];
    
    // Get recent activities
    $recent_activities = $this->getRecentActivities($uid, 5);
    
    // Get recent deals
    $recent_deals = $this->getRecentDeals($uid, 5);
    
    return [
      '#theme' => 'crm_user_profile',
      '#user' => $user,
      '#user_name' => $user_name,
      '#user_initial' => $user_initial,
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
        'contexts' => ['user', 'route'],
        'tags' => [
          'user:' . $uid,
          'node_list:contact',
          'node_list:deal',
          'node_list:organization',
          'node_list:activity',
        ],
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
      ->sort('changed', 'DESC')
      ->range(0, $limit)
      ->accessCheck(FALSE);
    
    $nids = $query->execute();
    
    $activities = [];
    if (!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($nids);
      
      foreach ($nodes as $node) {
        $activity_type = 'Activity';
        if ($node->hasField('field_type') && !$node->get('field_type')->isEmpty() && $node->get('field_type')->entity) {
          $activity_type = $node->get('field_type')->entity->label();
        }
        elseif ($node->hasField('field_activity_type') && !$node->get('field_activity_type')->isEmpty() && $node->get('field_activity_type')->entity) {
          $activity_type = $node->get('field_activity_type')->entity->label();
        }

        $activity_date = '';
        if ($node->hasField('field_datetime') && !$node->get('field_datetime')->isEmpty()) {
          $activity_date = $node->get('field_datetime')->value;
        }
        elseif ($node->hasField('field_activity_date') && !$node->get('field_activity_date')->isEmpty()) {
          $activity_date = $node->get('field_activity_date')->value;
        }

        $activities[] = [
          'id' => $node->id(),
          'title' => $node->label(),
          'type' => $activity_type,
          'date' => $activity_date,
          'url' => $node->toUrl()->toString(),
        ];
      }
    }
    
    return $activities;
  }

  /**
   * Get total deal value for user.
   */
  protected function getUserTotalDealValue($uid) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'deal')
      ->condition('status', 1)
      ->condition('field_owner', $uid)
      ->accessCheck(FALSE);
    $nids = $query->execute();
    $total = 0;
    if (!empty($nids)) {
      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($nids);
      foreach ($nodes as $node) {
        if ($node->hasField('field_amount') && !$node->get('field_amount')->isEmpty()) {
          $total += (float) $node->get('field_amount')->value;
        }
        elseif ($node->hasField('field_deal_value') && !$node->get('field_deal_value')->isEmpty()) {
          $total += (float) $node->get('field_deal_value')->value;
        }
      }
    }
    return $total;
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
        if ($node->hasField('field_stage') && !$node->get('field_stage')->isEmpty() && $node->get('field_stage')->entity) {
          $stage = $node->get('field_stage')->entity->label();
        }
        elseif ($node->hasField('field_deal_stage') && !$node->get('field_deal_stage')->isEmpty() && $node->get('field_deal_stage')->entity) {
          $stage = $node->get('field_deal_stage')->entity->label();
        }
        
        $value = '';
        if ($node->hasField('field_amount') && !$node->get('field_amount')->isEmpty()) {
          $value = number_format($node->get('field_amount')->value, 0, ',', '.');
        }
        elseif ($node->hasField('field_deal_value') && !$node->get('field_deal_value')->isEmpty()) {
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
