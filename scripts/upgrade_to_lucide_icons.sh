#!/bin/bash

# Script: Upgrade Quick Access page to use Lucide Icons
# Description: Replace emoji icons with professional Lucide Icons via CDN

echo "🎨 Upgrading Quick Access page to use Lucide Icons..."

# Load HTML from file and update Node 23
cat /var/www/html/lucide_icons_page.html | ddev drush ev "\$html = file_get_contents('php://stdin'); \$node = \Drupal::entityTypeManager()->getStorage('node')->load(23); \$node->body->value = \$html; \$node->body->format = 'full_html'; \$node->save(); echo 'Updated Node 23 with Lucide Icons!\n';"

echo ""
echo "✅ Done! View at: http://open-crm.ddev.site/"
echo ""
echo "🎨 Professional icons from Lucide are now loaded!"
echo "📦 Icons used: layout-dashboard, users, briefcase, calendar-clock, building-2, folder-open"
echo "📖 Read LUCIDE_ICONS_GUIDE.md for more details"
