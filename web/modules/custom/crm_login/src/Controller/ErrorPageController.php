<?php

namespace Drupal\crm_login\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\Markup;

/**
 * Renders beautiful 403 / 404 error pages for the CRM.
 */
class ErrorPageController extends ControllerBase {

  /**
   * 403 – Access Denied / Not Logged In.
   */
  public function accessDenied(Request $request) {
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    $destination  = $request->getRequestUri();
    $login_url    = '/login' . ($destination && $destination !== '/403' ? '?destination=' . urlencode($destination) : '');

    if ($is_anonymous) {
      $heading  = 'Sign In Required';
      $message  = 'This page is only available to logged-in users. Please sign in to your CRM account to continue.';
      $icon     = 'lock';
      $accent   = '#2563eb';
      $bg       = '#eff6ff';
      $actions  = <<<HTML
        <a href="{$login_url}" class="crm-err-btn crm-err-btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Sign In to Continue
        </a>
        <a href="/" class="crm-err-btn crm-err-btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          Back to Home
        </a>
HTML;
    }
    else {
      $heading  = 'Access Denied';
      $message  = 'You don\'t have permission to access this page. Contact your administrator if you believe this is a mistake.';
      $icon     = 'shield-off';
      $accent   = '#dc2626';
      $bg       = '#fef2f2';
      $actions  = <<<HTML
        <a href="/crm/dashboard" class="crm-err-btn crm-err-btn-primary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
          Go to Dashboard
        </a>
        <button onclick="history.back()" class="crm-err-btn crm-err-btn-secondary">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          Go Back
        </button>
HTML;
    }

    $svg_icon = $this->getIcon($icon, $accent);

    $html = <<<HTML
<div class="crm-error-page">
  <div class="crm-err-card">
    <div class="crm-err-icon-wrap" style="background:{$bg}; border-color:{$accent}22;">
      {$svg_icon}
    </div>
    <div class="crm-err-code">403</div>
    <h1 class="crm-err-heading">{$heading}</h1>
    <p class="crm-err-message">{$message}</p>
    <div class="crm-err-actions">
      {$actions}
    </div>
    <p class="crm-err-hint">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      Error code 403 &mdash; Access Forbidden
    </p>
  </div>
</div>
HTML;

    return [
      '#markup'   => Markup::create($html),
      '#attached' => ['library' => ['crm_login/error_pages']],
      '#cache'    => ['contexts' => ['user', 'url.path']],
    ];
  }

  /**
   * 404 – Page Not Found.
   */
  public function notFound(Request $request) {
    $dashboard_url = '/crm/dashboard';
    $home_url = '/';
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    $back_url = $is_anonymous ? $home_url : $dashboard_url;
    $back_label = $is_anonymous ? 'Back to Home' : 'Go to Dashboard';
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found — CRM</title>
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' fill='%237c3aed' rx='12'/><text x='50' y='72' font-size='60' font-weight='900' fill='white' text-anchor='middle' font-family='system-ui'>?</text></svg>">
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    html, body {
      width: 100%;
      height: 100%;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
      background: #ffffff;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1e293b;
      overflow-x: hidden;
    }
    
    .container {
      max-width: 900px;
      width: 100%;
      height: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
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
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 48px 56px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.08);
      display: flex;
      gap: 56px;
      align-items: stretch;
    }
    
    .left-content {
      flex: 0 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      min-width: 260px;
    }
    
    .right-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      min-width: 300px;
    }
    
    .icon-wrapper {
      width: 80px;
      height: 80px;
      margin: 0 auto 24px;
      background: #f5f3ff;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    
    .icon-wrapper svg {
      width: 48px;
      height: 48px;
      color: #7c3aed;
      stroke-width: 1.5;
    }
    
    .error-code {
      font-size: 56px;
      font-weight: 900;
      color: #7c3aed;
      margin-bottom: 8px;
      letter-spacing: -0.02em;
    }
    
    .error-title {
      font-size: 28px;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 12px;
      letter-spacing: -0.01em;
    }
    
    .error-message {
      font-size: 15px;
      color: #475569;
      line-height: 1.6;
      margin-bottom: 8px;
      font-weight: 500;
    }
    
    .error-subtext {
      font-size: 13px;
      color: #64748b;
      margin-bottom: 28px;
      font-weight: 400;
    }
    
    .reasons {
      text-align: left;
      background: #f8fafc;
      border-radius: 12px;
      padding: 18px 16px;
      margin-bottom: 28px;
      border-left: 3px solid #7c3aed;
    }
    
    .reasons-title {
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      color: #64748b;
      letter-spacing: 0.05em;
      margin-bottom: 12px;
    }
    
    .reasons-list {
      list-style: none;
      font-size: 13px;
      color: #475569;
      line-height: 1.7;
    }
    
    .reasons-list li {
      margin-bottom: 6px;
    }
    
    .reasons-list li::before {
      content: "•";
      color: #7c3aed;
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
      background: #7c3aed;
      color: white;
    }
    
    .btn-primary:hover {
      background: #6d28d9;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
    }
    
    .btn-secondary {
      background: #f8fafc;
      color: #475569;
      border: 1px solid #e2e8f0;
    }
    
    .btn-secondary:hover {
      background: #f1f5f9;
      border-color: #cbd5e1;
      color: #1e293b;
    }
    
    .divider {
      height: 1px;
      background: #e2e8f0;
      margin: 24px 0;
    }
    
    .contact-admin {
      font-size: 13px;
      color: #475569;
      line-height: 1.6;
    }
    
    .contact-admin a {
      color: #7c3aed;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.15s;
    }
    
    .contact-admin a:hover {
      color: #6d28d9;
      text-decoration: underline;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 32px 20px;
      }
      
      .content-wrapper {
        flex-direction: column;
        gap: 32px;
        padding: 36px 28px;
      }
      
      .left-content {
        min-width: auto;
      }
      
      .right-content {
        min-width: auto;
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
        
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        
        <div class="error-message">
          The page you're looking for doesn't exist or may have been moved.
        </div>
        <div class="error-subtext">
          Double-check the URL and try again.
        </div>
      </div>
      
      <div class="right-content">
        <div class="reasons">
          <div class="reasons-title">Possible reasons:</div>
          <ul class="reasons-list">
            <li>The URL may contain a typo or incorrect path</li>
            <li>The page may have been moved or deleted</li>
            <li>The resource you're looking for is no longer available</li>
          </ul>
        </div>
        
        <div class="actions">
          <a href="{$back_url}" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
              <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
              <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            {$back_label}
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
          Try searching for the page using the site's search feature or <a href="{$dashboard_url}">browse the main navigation</a>.
        </div>
      </div>
    </div>
  </div>
  
  <script>
    if (window.lucide) {
      lucide.createIcons();
    }
  </script>
</body>
</html>
HTML;

    return [
      '#markup'   => Markup::create($html),
      '#cache'    => ['contexts' => ['user', 'url.path']],
    ];
  }

  /**
   * Returns an inline SVG for known Lucide-style icons.
   */
  private function getIcon(string $name, string $color): string {
    $icons = [
      'lock' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>',
      'shield-off' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19.7 14a6.9 6.9 0 0 0 .3-2V5l-8-3-3.2 1.2"/><path d="m2 2 20 20"/><path d="M4.7 4.7 4 5v7c0 6 8 10 8 10a20.3 20.3 0 0 0 5.62-4.38"/></svg>',
      'search-x' => '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="' . $color . '" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="8" x2="14" y2="14"/><line x1="14" y1="8" x2="8" y2="14"/></svg>',
    ];
    return $icons[$name] ?? $icons['lock'];
  }

}
