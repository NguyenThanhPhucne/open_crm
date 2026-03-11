<?php

namespace Drupal\crm\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects anonymous users to the login page when they access CRM pages.
 */
class CrmAccessDeniedSubscriber implements EventSubscriberInterface {

  public function __construct(
    protected AccountInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
  ) {}

  public static function getSubscribedEvents(): array {
    // Priority 100 runs before Drupal's default 403 handler (priority 50).
    return [KernelEvents::EXCEPTION => ['onAccessDenied', 100]];
  }

  public function onAccessDenied(ExceptionEvent $event): void {
    if (!($event->getThrowable() instanceof AccessDeniedHttpException)) {
      return;
    }

    // Only act for anonymous users.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    // Only redirect for requests under /crm/.
    $path = $event->getRequest()->getPathInfo();
    if (!str_starts_with($path, '/crm')) {
      return;
    }

    $destination = $event->getRequest()->getPathInfo();
    $login_url = Url::fromRoute('crm_login.login', [], ['query' => ['destination' => $destination]])->toString();

    $event->setResponse(new RedirectResponse($login_url, 302));
    $event->stopPropagation();
  }

}
