<?php

namespace Drupal\crm\Helper;

use Drupal\Core\Render\Markup;

/**
 * Helper to manage dynamic page actions for CRM pages.
 */
class CrmActionHelper {

  /**
   * Renders the action buttons for a CRM page.
   *
   * @param string $entity_type
   *   The entity type (contact, organization, deal, activity).
   * @param array $default_actions
   *   Array of default actions. Each action is an array with:
   *   - label: The button text.
   *   - url: The button URL (for <a> tags).
   *   - icon: The Lucide icon name.
   *   - class: CSS class (default: btn-primary).
   *   - attributes: Extra HTML attributes.
   *
   * @return string
   *   The rendered HTML for the action buttons.
   */
  public static function renderActions(string $entity_type, array $default_actions = []): string {
    $actions = $default_actions;

    // Allow other modules to alter the actions.
    \Drupal::moduleHandler()->alter('crm_page_actions', $actions, $entity_type);

    $html = '';
    foreach ($actions as $key => $action) {
      $label = $action['label'] ?? '';
      $icon = $action['icon'] ?? '';
      $class = $action['class'] ?? 'btn-primary';
      $attrs = '';
      if (!empty($action['attributes'])) {
        foreach ($action['attributes'] as $name => $value) {
          $attrs .= ' ' . $name . '="' . htmlspecialchars($value) . '"';
        }
      }

      $icon_html = $icon ? '<i data-lucide="' . $icon . '"></i>' : '';

      if (!empty($action['url'])) {
        $html .= '<a href="' . $action['url'] . '" class="' . $class . '"' . $attrs . '>' . $icon_html . ' ' . $label . '</a>';
      } else {
        $html .= '<button class="' . $class . '"' . $attrs . '>' . $icon_html . ' ' . $label . '</button>';
      }
    }

    return $html;
  }
}
