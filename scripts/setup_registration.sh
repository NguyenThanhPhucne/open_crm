#!/bin/bash

echo "🚀 SETUP CRM REGISTRATION MODULE"
echo "=================================="
echo ""

# Step 1: Create user fields if they don't exist
echo "📋 1. Creating user fields..."
ddev drush ev "
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

// Field: Full Name
if (!FieldStorageConfig::loadByName('user', 'field_full_name')) {
  FieldStorageConfig::create([
    'field_name' => 'field_full_name',
    'entity_type' => 'user',
    'type' => 'string',
    'cardinality' => 1,
  ])->save();
  
  FieldConfig::create([
    'field_name' => 'field_full_name',
    'entity_type' => 'user',
    'bundle' => 'user',
    'label' => 'Full Name',
    'required' => FALSE,
  ])->save();
  
  echo 'Created field_full_name' . PHP_EOL;
} else {
  echo 'field_full_name already exists' . PHP_EOL;
}

// Field: Phone
if (!FieldStorageConfig::loadByName('user', 'field_phone')) {
  FieldStorageConfig::create([
    'field_name' => 'field_phone',
    'entity_type' => 'user',
    'type' => 'telephone',
    'cardinality' => 1,
  ])->save();
  
  FieldConfig::create([
    'field_name' => 'field_phone',
    'entity_type' => 'user',
    'bundle' => 'user',
    'label' => 'Phone Number',
    'required' => FALSE,
  ])->save();
  
  echo 'Created field_phone' . PHP_EOL;
} else {
  echo 'field_phone already exists' . PHP_EOL;
}
"

echo "✅ User fields created"
echo ""

# Step 2: Enable the module
echo "🔌 2. Enabling crm_register module..."
ddev drush en crm_register -y

echo "✅ Module enabled"
echo ""

# Step 3: Configure user registration settings
echo "⚙️  3. Configuring user registration settings..."
ddev drush config-set user.settings register visitors -y

echo "✅ Registration opened for visitors"
echo ""

# Step 4: Clear cache
echo "🧹 4. Clearing cache..."
ddev drush cr

echo "✅ Cache cleared"
echo ""

# Step 5: Verify routes
echo "🔍 5. Verifying routes..."
ddev drush ev "
\$route_provider = \Drupal::service('router.route_provider');
try {
  \$route = \$route_provider->getRouteByName('crm_register.register');
  echo '✅ Registration route exists: /register' . PHP_EOL;
} catch (\Exception \$e) {
  echo '❌ Registration route not found' . PHP_EOL;
}
"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✨ SETUP COMPLETE!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📍 Registration URL: http://open-crm.ddev.site/register"
echo ""
echo "🎨 Features:"
echo "   • Responsive design (mobile, tablet, desktop)"
echo "   • Real-time validation"
echo "   • Password strength indicator"
echo "   • Role-based registration (Sales Rep, Manager, Customer)"
echo "   • Auto-login after registration"
echo "   • Welcome email notification"
echo "   • Secure password hashing"
echo ""
echo "🔐 Available Roles:"
echo "   • Sales Rep - Quản lý contact/deal của mình"
echo "   • Sales Manager - Quản lý toàn bộ CRM"
echo "   • Customer - Xem thông tin của mình"
echo ""
echo "🧪 Test it now: http://open-crm.ddev.site/register"
echo ""
