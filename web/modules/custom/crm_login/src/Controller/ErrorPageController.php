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
    $is_anonymous = \Drupal::currentUser()->isAnonymous();
    $back_url = $is_anonymous ? '/' : '/crm/dashboard';
    $back_label = $is_anonymous ? 'Back to Home' : 'Go to Dashboard';
    $back_icon = $is_anonymous
      ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>'
      : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>';

    $svg_icon = $this->getIcon('search-x', '#7c3aed');

    $html = <<<HTML
<div class="crm-error-page">
  <div class="crm-err-card">
    <div class="crm-err-icon-wrap" style="background:#f5f3ff; border-color:#7c3aed22;">
      {$svg_icon}
    </div>
    <div class="crm-err-code">404</div>
    <h1 class="crm-err-heading">Page Not Found</h1>
    <p class="crm-err-message">The page you're looking for doesn't exist or may have been moved. Double-check the URL and try again.</p>
    <div class="crm-err-actions">
      <a href="{$back_url}" class="crm-err-btn crm-err-btn-primary">
        {$back_icon}
        {$back_label}
      </a>
      <button onclick="history.back()" class="crm-err-btn crm-err-btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
        Go Back
      </button>
    </div>
    <p class="crm-err-hint">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
      Error code 404 &mdash; Not Found
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
