<?php

namespace Drupal\crm_navigation\Service;

use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Navigation helper service for CRM.
 */
class NavigationHelper {

  use StringTranslationTrait;

  /**
   * Get back button render array.
   *
   * @param string $route_name
   *   The route to go back to.
   * @param array $route_parameters
   *   Route parameters.
   * @param string $label
   *   Button label.
   *
   * @return array
   *   Render array for back button.
   */
  public function getBackButton($route_name, array $route_parameters = [], $label = 'Back to Dashboard') {
    $url = Url::fromRoute($route_name, $route_parameters);
    
    return [
      '#type' => 'link',
      '#title' => $this->t($label),
      '#url' => $url,
      '#attributes' => [
        'class' => ['button', 'button--back', 'crm-back-button'],
      ],
      '#prefix' => '<div class="crm-back-button-wrapper">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Get navigation bar with common links.
   *
   * @return array
   *   Render array for navigation bar.
   */
  public function getNavigationBar() {
    $links = [
      'dashboard' => [
        'title' => $this->t('Dashboard'),
        'route' => 'crm_dashboard.dashboard',
        'icon' => 'layout-dashboard',
      ],
      'contacts' => [
        'title' => $this->t('My Contacts'),
        'route' => 'crm.my_contacts',
        'icon' => 'users',
      ],
      'deals' => [
        'title' => $this->t('My Deals'),
        'route' => 'crm.my_deals',
        'icon' => 'briefcase',
      ],
      'activities' => [
        'title' => $this->t('My Activities'),
        'route' => 'crm.my_activities',
        'icon' => 'calendar-clock',
      ],
      'organizations' => [
        'title' => $this->t('Organizations'),
        'route' => 'crm.all_organizations',
        'icon' => 'building-2',
      ],
      'chat' => [
        'title' => $this->t('Realtime Chat'),
        'route' => 'crm_realtime_chat.page',
        'icon' => 'messages-square',
      ],
    ];

    $items = [];
    foreach ($links as $key => $link) {
      try {
        $url = Url::fromRoute($link['route']);
        $icon_html = '<i data-lucide="' . $link['icon'] . '" width="18" height="18"></i>';
        $items[] = [
          '#type' => 'link',
          '#title' => $link['title'],
          '#url' => $url,
          '#attributes' => [
            'class' => ['crm-nav-link', 'crm-nav-' . $key],
            'data-icon' => $link['icon'],
          ],
          '#prefix' => '<span class="crm-nav-item">' . $icon_html,
          '#suffix' => '</span>',
        ];
      }
      catch (\Exception $e) {
        // Route doesn't exist, skip
      }
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#attributes' => ['class' => ['crm-navigation-bar']],
      '#prefix' => '<nav class="crm-quick-nav">',
      '#suffix' => '</nav>',
      '#attached' => [
        'library' => ['crm_navigation/lucide'],
      ],
    ];
  }

  /**
   * Get context-aware back URL.
   *
   * @param string $entity_type
   *   Entity type (contact, deal, etc).
   *
   * @return \Drupal\Core\Url
   *   URL object.
   */
  public function getContextBackUrl($entity_type = NULL) {
    // Map entity types to their list views
    $entity_routes = [
      'contact' => 'crm.my_contacts',
      'deal' => 'crm.my_deals',
      'activity' => 'crm.my_activities',
      'organization' => 'crm.all_organizations',
    ];

    if ($entity_type && isset($entity_routes[$entity_type])) {
      try {
        return Url::fromRoute($entity_routes[$entity_type]);
      }
      catch (\Exception $e) {
        // Fall through to default
      }
    }

    // Default to dashboard
    return Url::fromRoute('crm_dashboard.dashboard');
  }

}
