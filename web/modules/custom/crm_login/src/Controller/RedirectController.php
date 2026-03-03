<?php

namespace Drupal\crm_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for redirecting old login URLs.
 */
class RedirectController extends ControllerBase {

  /**
   * Redirect /user/login to /login.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function redirectToLogin() {
    $url = Url::fromRoute('crm_login.login')->toString();
    return new RedirectResponse($url, 301); // 301 = Permanent redirect
  }

}
