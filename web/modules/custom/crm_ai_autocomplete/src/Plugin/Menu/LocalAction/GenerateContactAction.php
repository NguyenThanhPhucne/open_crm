<?php

namespace Drupal\crm_ai_autocomplete\Plugin\Menu\LocalAction;

use Drupal\Core\Menu\LocalActionDefault;
use Symfony\Component\HttpFoundation\Request;

/**
 * Action for Generate Contact button.
 */
class GenerateContactAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request = NULL) {
    return '✨ Generate data';
  }

}
