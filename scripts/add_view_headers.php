<?php

/**
 * Add action buttons to view headers
 * Run with: ddev drush scr scripts/add_view_headers.php
 */

use Drupal\views\Entity\View;

// Fix my_contacts view
$view = View::load('my_contacts');
if ($view) {
  $displays = $view->get('display');
  if (!isset($displays['page_1']['display_options']['header'])) {
    $displays['page_1']['display_options']['header'] = [];
  }
  $displays['page_1']['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style="text-align: right; margin-bottom: 20px;">
        <a href="/node/add/contact" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #10b981;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        " onmouseover="this.style.background=\'#059669\'" onmouseout="this.style.background=\'#10b981\'">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Add Contact
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  $view->set('display', $displays);
  $view->save();
  echo "✅ my_contacts updated\n";
}

// Fix my_deals view
$view = View::load('my_deals');
if ($view) {
  $displays = $view->get('display');
  if (!isset($displays['page_1']['display_options']['header'])) {
    $displays['page_1']['display_options']['header'] = [];
  }
  $displays['page_1']['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style="text-align: right; margin-bottom: 20px;">
        <a href="/node/add/deal" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #8b5cf6;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          box-shadow: 0 2px 4px rgba(139, 92, 246, 0.2);
        " onmouseover="this.style.background=\'#7c3aed\'" onmouseout="this.style.background=\'#8b5cf6\'">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Add Deal
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  $view->set('display', $displays);
  $view->save();
  echo "✅ my_deals updated\n";
}

// Fix my_activities view
$view = View::load('my_activities');
if ($view) {
  $displays = $view->get('display');
  if (!isset($displays['page_1']['display_options']['header'])) {
    $displays['page_1']['display_options']['header'] = [];
  }
  $displays['page_1']['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style="text-align: right; margin-bottom: 20px;">
        <a href="/node/add/activity" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #f59e0b;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
        " onmouseover="this.style.background=\'#d97706\'" onmouseout="this.style.background=\'#f59e0b\'">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Add Activity
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  $view->set('display', $displays);
  $view->save();
  echo "✅ my_activities updated\n";
}

// Fix my_organizations view
$view = View::load('my_organizations');
if ($view) {
  $displays = $view->get('display');
  if (!isset($displays['page_1']['display_options']['header'])) {
    $displays['page_1']['display_options']['header'] = [];
  }
  $displays['page_1']['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style="text-align: right; margin-bottom: 20px;">
        <a href="/node/add/organization" class="button button--primary" style="
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #ec4899;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          box-shadow: 0 2px 4px rgba(236, 72, 153, 0.2);
        " onmouseover="this.style.background=\'#db2777\'" onmouseout="this.style.background=\'#ec4899\'">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 5v14M5 12h14"></path>
          </svg>
          Add Organization
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  $view->set('display', $displays);
  $view->save();
  echo "✅ my_organizations updated\n";
}

echo "\n✅ All views updated with action buttons!\n";
