<?php

namespace Drupal\crm_activity_log\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to the activity tab based on node type.
 */
class ActivityTabAccessCheck implements AccessInterface {

  /**
   * Checks access to the activity tab.
   */
  public function access(NodeInterface $node, AccountInterface $account) {
    $allowed_types = ['contact', 'deal', 'organization'];
    
    if (in_array($node->bundle(), $allowed_types)) {
      return AccessResult::allowedIf($account->hasPermission('access content'));
    }
    
    return AccessResult::forbidden();
  }

}
