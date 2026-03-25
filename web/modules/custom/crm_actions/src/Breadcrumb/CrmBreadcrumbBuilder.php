<?php

namespace Drupal\crm_actions\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Builds custom breadcrumbs for CRM and App paths.
 */
class CrmBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  use StringTranslationTrait;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return FALSE;
    }
    
    $path = $request->getPathInfo();
    // Only apply for our custom frontend endpoints
    return str_starts_with($path, '/crm/') || str_starts_with($path, '/app/');
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path']);
    
    $request = $this->requestStack->getCurrentRequest();
    $path = $request ? $request->getPathInfo() : '';

    $links = [];
    $links[] = Link::createFromRoute($this->t('Home'), '<front>');
    
    if ($path !== '/crm/dashboard') {
      // Assuming 'crm_dashboard.dashboard' is the route for CRM Dashboard.
      // If the route name doesn't exist, this might throw an exception,
      // but earlier we queried and found `crm_dashboard.dashboard` exists for '/crm/dashboard'.
      $links[] = Link::createFromRoute($this->t('CRM Dashboard'), 'crm_dashboard.dashboard');
    }

    try {
      $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());
    } 
    catch (\Exception $e) {
      $title = '';
    }

    if ($title) {
      $links[] = Link::createFromRoute($title, '<none>');
    } 
    else {
      $parts = array_filter(explode('/', trim($path, '/')));
      if (count($parts) > 1) {
        $last_part = end($parts);
        $human_title = ucwords(str_replace('-', ' ', $last_part));
        $links[] = Link::createFromRoute($human_title, '<none>');
      }
    }

    $breadcrumb->setLinks($links);
    return $breadcrumb;
  }

}
