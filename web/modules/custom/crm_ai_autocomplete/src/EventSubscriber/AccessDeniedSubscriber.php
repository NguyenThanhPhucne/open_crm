<?php

namespace Drupal\crm_ai_autocomplete\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Access\AccessException;

/**
 * Handle access denied errors for API routes.
 */
class AccessDeniedSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::EXCEPTION => ['onException', 100],
    ];
  }

  /**
   * Handle exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();
    $request = $event->getRequest();

    // Check if this is an API route
    $path = $request->getPathInfo();
    if (strpos($path, '/api/crm/ai/') !== 0) {
      return;
    }

    // Check if it's an access exception
    if ($exception instanceof AccessException) {
      $response = new JsonResponse([
        'success' => FALSE,
        'message' => 'Access denied. Please log in.',
        'code' => 403,
      ], 403);
      
      $event->setResponse($response);
    }
  }

}
