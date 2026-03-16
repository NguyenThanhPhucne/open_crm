<?php

namespace Drupal\crm\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Handles 403 Access Denied errors for CRM pages with professional UI.
 * 
 * - Redirects anonymous users to login
 * - Shows professional 403 page for authenticated users without permission
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

    $path = $event->getRequest()->getPathInfo();
    
    // Only handle /crm/* paths.
    if (!str_starts_with($path, '/crm')) {
      return;
    }

    // Redirect anonymous users to login.
    if (!$this->currentUser->isAuthenticated()) {
      $destination = $path;
      $login_url = Url::fromRoute('crm_login.login', [], ['query' => ['destination' => $destination]])->toString();
      $event->setResponse(new RedirectResponse($login_url, 302));
      $event->stopPropagation();
      return;
    }

    // Authenticated users without permission: show professional 403 page.
    $event->setResponse(new Response($this->render403Page(), 403, ['Content-Type' => 'text/html; charset=utf-8']));
    $event->stopPropagation();
  }

  /**
   * Renders a professional 403 Access Denied page for CRM routes.
   */
  private function render403Page(): string {
    $dashboard_url = Url::fromRoute('crm_dashboard.dashboard')->toString();
    $contact_email = \Drupal::config('system.site')->get('mail') ?: 'admin@example.com';
    
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access Denied - CRM</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%23dc2626' rx='12'/><text x='50' y='72' font-size='60' font-weight='900' fill='white' text-anchor='middle' font-family='system-ui'>!</text></svg>">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    :root {
      --crm-403-bg: #f8fafc;
      --crm-403-panel: #ffffff;
      --crm-403-border: #e2e8f0;
      --crm-403-text: #0f172a;
      --crm-403-subtext: #475569;
      --crm-403-muted: #64748b;
      --crm-403-accent: #dc2626;
      --crm-403-accent-soft: #fef2f2;
      --crm-403-primary: #2563eb;
      --crm-403-primary-hover: #1d4ed8;
      --crm-403-secondary: #f8fafc;
      --crm-403-secondary-hover: #f1f5f9;
      --crm-403-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "SF Pro Text", Roboto, "Helvetica Neue", Arial, sans-serif;
      background:
        radial-gradient(circle at 20% 10%, #fee2e2 0, rgba(254, 226, 226, 0) 36%),
        radial-gradient(circle at 90% 90%, #dbeafe 0, rgba(219, 234, 254, 0) 40%),
        var(--crm-403-bg);
      min-height: 100vh;
      margin: 0;
      color: var(--crm-403-text);
      overflow-x: hidden;
    }

    .container {
      max-width: 980px;
      width: min(980px, calc(100% - 32px));
      min-height: 100vh;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 0;
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }
      to {
        opacity: 1;
      }
    }
    
    .content-wrapper {
      width: 100%;
      background: var(--crm-403-panel);
      border: 1px solid var(--crm-403-border);
      border-radius: 14px;
      padding: 40px;
      box-shadow: var(--crm-403-shadow);
      display: grid;
      grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
      gap: 40px;
      align-items: start;
    }

    .left-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      min-width: 0;
    }

    .right-content {
      display: flex;
      flex-direction: column;
      min-width: 0;
    }

    .icon-wrapper {
      width: 80px;
      height: 80px;
      margin: 0 auto 24px;
      background: var(--crm-403-accent-soft);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .icon-wrapper svg {
      width: 48px;
      height: 48px;
      color: var(--crm-403-accent);
      stroke-width: 1.5;
    }

    .error-code {
      font-size: 56px;
      font-weight: 900;
      color: var(--crm-403-accent);
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }

    .error-title {
      font-size: 28px;
      font-weight: 800;
      color: var(--crm-403-text);
      margin-bottom: 12px;
      letter-spacing: -0.01em;
    }

    .error-message {
      font-size: 15px;
      color: var(--crm-403-subtext);
      line-height: 1.6;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .error-subtext {
      font-size: 13px;
      color: var(--crm-403-muted);
      margin-bottom: 28px;
      font-weight: 400;
    }

    .reasons {
      text-align: left;
      background: #f8fafc;
      border-radius: 12px;
      padding: 18px 16px;
      margin-bottom: 28px;
      border-left: 3px solid #3b82f6;
    }

    .reasons-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: var(--crm-403-muted);
      letter-spacing: 0.05em;
      margin-bottom: 12px;
    }

    .reasons-list {
      list-style: none;
      font-size: 13px;
      color: var(--crm-403-subtext);
      line-height: 1.7;
    }

    .reasons-list li {
      margin-bottom: 6px;
    }

    .reasons-list li::before {
      content: "•";
      color: #3b82f6;
      font-weight: 800;
      margin-right: 8px;
    }

    .actions {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 28px;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.15s ease;
      border: none;
      cursor: pointer;
      white-space: nowrap;
    }

    .btn svg {
      width: 16px;
      height: 16px;
      flex-shrink: 0;
    }

    .btn-primary {
      background: var(--crm-403-primary);
      color: white;
    }

    .btn-primary:hover {
      background: var(--crm-403-primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    .btn-secondary {
      background: var(--crm-403-secondary);
      color: var(--crm-403-subtext);
      border: 1px solid var(--crm-403-border);
    }

    .btn-secondary:hover {
      background: var(--crm-403-secondary-hover);
      border-color: #cbd5e1;
      color: var(--crm-403-text);
    }

    .divider {
      height: 1px;
      background: var(--crm-403-border);
      margin: 24px 0;
    }

    .contact-admin {
      font-size: 13px;
      color: var(--crm-403-subtext);
      line-height: 1.6;
    }

    .contact-admin a {
      color: #3b82f6;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s;
    }

    .contact-admin a:hover {
      color: #2563eb;
      text-decoration: underline;
    }

    @media (max-width: 768px) {
      .container {
        width: min(100%, calc(100% - 24px));
        padding: 24px 0;
      }

      .content-wrapper {
        grid-template-columns: 1fr;
        gap: 32px;
        padding: 32px 24px;
      }

      .error-code {
        font-size: 48px;
      }

      .error-title {
        font-size: 24px;
      }

      .icon-wrapper {
        width: 72px;
        height: 72px;
      }

      .icon-wrapper svg {
        width: 40px;
        height: 40px;
      }

      .error-message {
        font-size: 14px;
      }
    }

    @media (max-width: 480px) {
      .container {
        width: min(100%, calc(100% - 16px));
      }

      .content-wrapper {
        border-radius: 12px;
        padding: 28px 18px;
      }

      .error-code {
        font-size: 42px;
      }

      .error-title {
        font-size: 22px;
      }

      .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="content-wrapper">
      <div class="left-content">
        <div class="icon-wrapper">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 8v4"/>
            <path d="M12 16h.01"/>
          </svg>
        </div>
        
        <div class="error-code">403</div>
        <div class="error-title">Access Denied</div>
        
        <div class="error-message">
          You don't have permission to access this page.
        </div>
        <div class="error-subtext">
          Your current role doesn't grant access to this resource.
        </div>
      </div>
      
      <div class="right-content">
        <div class="reasons">
          <div class="reasons-title">Possible reasons:</div>
          <ul class="reasons-list">
            <li>You don't have the required role or permission</li>
            <li>Your account access may be restricted</li>
            <li>This page requires admin or manager access</li>
          </ul>
        </div>
        
        <div class="actions">
          <a href="$dashboard_url" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Go to Dashboard
          </a>
          <a href="javascript:history.back()" class="btn btn-secondary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Go Back
          </a>
        </div>
        
        <div class="divider"></div>
        
        <div class="contact-admin">
          If you believe this is a mistake, please <a href="mailto:$contact_email">contact your administrator</a>.
        </div>
      </div>
    </div>
  </div>
  
</body>
</html>
HTML;
  }

}
