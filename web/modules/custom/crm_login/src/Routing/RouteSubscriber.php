<?php

namespace Drupal\crm_login\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Alter user.login to use our custom form instead of removing it.
    // Removing it causes RouteNotFoundException in AccessDeniedSubscriber
    // when anonymous users hit a protected page.
    if ($route = $collection->get('user.login')) {
      $route->setPath('/login');
      $route->setDefault('_form', '\Drupal\crm_login\Form\CrmLoginForm');
      $route->setDefault('_title', 'Login');
      // Remove the controller default if it was set by core.
      $route->setDefault('_controller', NULL);
    }
  }

}
