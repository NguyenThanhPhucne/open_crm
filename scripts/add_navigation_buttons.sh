#!/bin/bash

# Script: Add Navigation & Action Buttons
# Description: Add menu items for adding content and floating action buttons

echo "🎯 Adding navigation and action buttons..."

# Add menu items for adding content
echo ""
echo "1. Creating menu items for Add actions..."

ddev drush ev "
use Drupal\menu_link_content\Entity\MenuLinkContent;

// Add Contact menu item
\$menu_link = MenuLinkContent::create([
  'title' => '+ Add Contact',
  'link' => ['uri' => 'internal:/node/add/contact'],
  'menu_name' => 'main',
  'weight' => 2,
  'expanded' => FALSE,
]);
\$menu_link->save();
echo '✅ Menu: + Add Contact\n';

// Add Organization menu item  
\$menu_link = MenuLinkContent::create([
  'title' => '+ Add Organization',
  'link' => ['uri' => 'internal:/node/add/organization'],
  'menu_name' => 'main',
  'weight' => 3,
  'expanded' => FALSE,
]);
\$menu_link->save();
echo '✅ Menu: + Add Organization\n';

// Add Deal menu item
\$menu_link = MenuLinkContent::create([
  'title' => '+ Add Deal',
  'link' => ['uri' => 'internal:/node/add/deal'],
  'menu_name' => 'main',
  'weight' => 4,
  'expanded' => FALSE,
]);
\$menu_link->save();
echo '✅ Menu: + Add Deal\n';

// Add Activity menu item
\$menu_link = MenuLinkContent::create([
  'title' => '+ Add Activity',
  'link' => ['uri' => 'internal:/node/add/activity'],
  'menu_name' => 'main',
  'weight' => 5,
  'expanded' => FALSE,
]);
\$menu_link->save();
echo '✅ Menu: + Add Activity\n';
"

# Update views to add header with action button
echo ""
echo "2. Adding action buttons to views..."

ddev drush ev "
// Add header to my_contacts view
\$view = \Drupal\views\Entity\View::load('my_contacts');
if (\$view) {
  \$display = \$view->getDisplay('default');
  \$display['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style=\"text-align: right; margin-bottom: 20px;\">
        <a href=\"/node/add/contact\" class=\"button button--primary\" style=\"
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #10b981;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s;
        \">
          <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
            <path d=\"M12 5v14M5 12h14\"></path>
          </svg>
          Add Contact
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  \$view->save();
  echo '✅ Updated my_contacts view\n';
}

// Add header to my_deals view
\$view = \Drupal\views\Entity\View::load('my_deals');
if (\$view) {
  \$display = \$view->getDisplay('default');
  \$display['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style=\"text-align: right; margin-bottom: 20px;\">
        <a href=\"/node/add/deal\" class=\"button button--primary\" style=\"
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #8b5cf6;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s;
        \">
          <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
            <path d=\"M12 5v14M5 12h14\"></path>
          </svg>
          Add Deal
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  \$view->save();
  echo '✅ Updated my_deals view\n';
}

// Add header to my_activities view
\$view = \Drupal\views\Entity\View::load('my_activities');
if (\$view) {
  \$display = \$view->getDisplay('default');
  \$display['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style=\"text-align: right; margin-bottom: 20px;\">
        <a href=\"/node/add/activity\" class=\"button button--primary\" style=\"
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #f59e0b;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s;
        \">
          <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
            <path d=\"M12 5v14M5 12h14\"></path>
          </svg>
          Add Activity
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  \$view->save();
  echo '✅ Updated my_activities view\n';
}

// Add header to my_organizations view
\$view = \Drupal\views\Entity\View::load('my_organizations');
if (\$view) {
  \$display = \$view->getDisplay('default');
  \$display['display_options']['header']['area'] = [
    'id' => 'area',
    'table' => 'views',
    'field' => 'area',
    'plugin_id' => 'text',
    'content' => [
      'value' => '<div style=\"text-align: right; margin-bottom: 20px;\">
        <a href=\"/node/add/organization\" class=\"button button--primary\" style=\"
          display: inline-flex;
          align-items: center;
          gap: 8px;
          padding: 10px 20px;
          background: #ec4899;
          color: white;
          text-decoration: none;
          border-radius: 8px;
          font-weight: 500;
          transition: all 0.2s;
        \">
          <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"20\" height=\"20\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\">
            <path d=\"M12 5v14M5 12h14\"></path>
          </svg>
          Add Organization
        </a>
      </div>',
      'format' => 'full_html',
    ],
  ];
  \$view->save();
  echo '✅ Updated my_organizations view\n';
}
"

# Clear cache
echo ""
echo "3. Clearing cache..."
ddev drush cr

echo ""
echo "✅ All navigation and action buttons added!"
echo ""
echo "📋 What was added:"
echo "  - Menu: + Add Contact (main menu)"
echo "  - Menu: + Add Organization (main menu)"
echo "  - Menu: + Add Deal (main menu)"
echo "  - Menu: + Add Activity (main menu)"
echo "  - Button: Add Contact (in my_contacts view)"
echo "  - Button: Add Deal (in my_deals view)"
echo "  - Button: Add Activity (in my_activities view)"
echo "  - Button: Add Organization (in my_organizations view)"
echo ""
echo "🎨 Buttons use matching colors with Quick Access cards!"
