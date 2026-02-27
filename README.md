# Open CRM - Drupal 11 CRM System

Enterprise-grade CRM system built on Drupal 11 with modern UI/UX using Lucide Icons and professional design patterns.

## 🚀 Features

### Core CRM Functionality

- ✅ **Dashboard** - KPI cards with Chart.js visualizations (bar & doughnut charts)
- ✅ **Contacts Management** - Full contact lifecycle with inline forms
- ✅ **Organizations** - Company management with status tracking
- ✅ **Sales Pipeline** - Kanban board with drag-and-drop deals between stages
- ✅ **Activities** - Task and meeting management
- ✅ **CSV Import** - Bulk import contacts and organizations from CSV files

### Design & UX

- 🎨 Professional UI with Lucide Icons (via CDN)
- 🎨 Tailwind-inspired color system
- 🎨 Responsive design with clean cards and subtle shadows
- 🎨 Quick Access homepage with 7 main feature cards

### Technical Features

- 📊 Real-time charts with Chart.js 4.4.1
- 🔄 Drag-and-drop functionality with SortableJS
- 📁 CSV import with Drupal Feeds module
- 🎯 Custom controllers for clean HTML responses
- 🔐 Role-based access control (Sales Manager, Sales Rep, Customer)

## 📋 Requirements

- **PHP**: 8.4+
- **Drupal**: 11.3.3
- **MariaDB**: 11.8+ (or MySQL 8.0+)
- **Composer**: 2.x
- **DDEV**: 1.23+ (recommended for local development)

## 🛠️ Installation

### 1. Clone Repository

```bash
git clone <repository-url> open_crm
cd open_crm
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Setup DDEV (Recommended)

```bash
ddev start
ddev drush site:install --existing-config -y
ddev drush cr
```

### 4. Import Sample Data (Optional)

```bash
# Create sample users
ddev exec bash scripts/create_sample_users.sh

# Create sample data
ddev exec bash scripts/create_sample_data_v2.sh
```

### 5. Access the Site

```bash
ddev launch
```

Default credentials:

- **Admin**: admin / admin
- **Sales Manager**: sales_manager / password
- **Sales Rep**: sales_rep / password

## 📂 Project Structure

```
open_crm/
├── composer.json                   # Dependencies
├── .gitignore                      # Git ignore rules
├── web/                           # Drupal webroot
│   ├── modules/custom/            # Custom modules
│   │   ├── crm_dashboard/        # Dashboard with KPI & Charts
│   │   ├── crm_kanban/           # Kanban Pipeline board
│   │   ├── crm_import/           # CSV Import functionality
│   │   └── crm_actions/          # Local action buttons
│   ├── themes/custom/            # Custom themes (if any)
│   └── sites/default/files/      # Uploaded files
│       └── import-templates/     # CSV templates
├── scripts/                       # Utility scripts
│   ├── create_sample_data_v2.sh  # Generate sample CRM data
│   ├── create_dashboard.sh       # Setup dashboard
│   └── update_homepage_links.sh  # Update Quick Access page
├── lucide_icons_page.html        # Quick Access homepage template
└── README.md                      # This file
```

## 🎯 Custom Modules

### 1. crm_dashboard

**Route**: `/crm/dashboard`

Features:

- 6 KPI cards (Contacts, Organizations, Deals, Total Value, Won, Lost)
- Horizontal bar chart for pipeline stages
- Doughnut chart for deal value distribution
- Real-time statistics from database

Dependencies:

- Chart.js 4.4.1 (CDN)
- Lucide Icons (CDN)

### 2. crm_kanban

**Route**: `/crm/pipeline`

Features:

- 6-column Kanban board (New → Qualified → Proposal → Negotiation → Won → Lost)
- Drag-and-drop deals between stages
- Auto-save via AJAX endpoint
- Column totals calculation
- Real-time update on drop

Dependencies:

- SortableJS 1.15.0 (CDN)
- Lucide Icons (CDN)

### 3. crm_import

**Route**: `/crm/import`

Features:

- CSV import for Contacts and Organizations
- Download CSV templates
- Field mapping configuration
- Duplicate detection (email for contacts, name for organizations)
- Batch processing

Dependencies:

- Drupal Feeds 3.2.0

### 4. crm_actions

Local action buttons for adding entities directly from list views.

## 📊 Content Types

### Contact

Fields:

- Title (Name)
- Email (unique)
- Phone
- Position
- Organization (reference)
- Source (taxonomy reference)
- Owner (user reference)

### Organization

Fields:

- Title (Name, unique)
- Website (link)
- Industry
- Address
- Status (active/inactive)
- Logo (image)
- Assigned Staff (user reference)

### Deal

Fields:

- Title (Deal name)
- Amount (decimal)
- Stage (taxonomy reference: New, Qualified, Proposal, Negotiation, Won, Lost)
- Closing Date
- Probability (integer)
- Contact (reference)
- Organization (reference)
- Owner (user reference)

### Activity

Fields:

- Title (Activity name)
- Type (taxonomy reference: Call, Meeting, Email, Task)
- Date/Time
- Description
- Deal (reference)
- Owner (user reference)

## 🔧 Configuration

### Homepage Setup

Homepage (Node 23) uses `lucide_icons_page.html` template with 7 Quick Access cards.

To update homepage:

```bash
bash scripts/update_homepage_links.sh
```

### CSV Import Templates

Located at: `web/sites/default/files/import-templates/`

- `contacts_template.csv` - Contact import template
- `organizations_template.csv` - Organization import template

### Clear Cache

```bash
ddev drush cr
```

## 🚦 Routes

| Route                   | Component | Description               |
| ----------------------- | --------- | ------------------------- |
| `/`                     | Homepage  | Quick Access with 7 cards |
| `/crm/dashboard`        | Dashboard | KPI cards + charts        |
| `/crm/pipeline`         | Kanban    | Drag-drop deals board     |
| `/crm/my-contacts`      | View      | Contacts list             |
| `/crm/my-organizations` | View      | Organizations list        |
| `/crm/my-activities`    | View      | Activities list           |
| `/crm/import`           | Import    | CSV upload page           |

## 🎨 Design System

### Color Palette

```css
Blue:    #3b82f6 / #eff6ff (Dashboard, Contacts)
Green:   #10b981 / #ecfdf5 (Won, Success)
Purple:  #8b5cf6 / #f5f3ff (Pipeline)
Orange:  #f59e0b / #fffbeb (Activities, Negotiation)
Pink:    #ec4899 / #fdf2f8 (Organizations)
Cyan:    #06b6d4 / #ecfeff (Import)
Red:     #ef4444 / #fef2f2 (Lost, Admin)
```

### Icons

Using **Lucide Icons** via CDN:

```html
<script src="https://unpkg.com/lucide@latest"></script>
<script>
  lucide.createIcons();
</script>
```

## 🧪 Testing

### Test Import

```bash
# Test contact import
ddev drush ev "
\$csv = [['name'=>'Test','email'=>'test@test.com','phone'=>'123','position'=>'Manager']];
foreach(\$csv as \$row) {
  \$node = \Drupal\node\Entity\Node::create(['type'=>'contact','title'=>\$row['name'],'field_email'=>\$row['email'],'field_phone'=>\$row['phone'],'field_position'=>\$row['position'],'uid'=>1]);
  \$node->save();
}
echo 'Test contact created';
"
```

### Verify Dashboard

```bash
ddev drush ev "
\$html = \Drupal::service('http_kernel')->handle(\Symfony\Component\HttpFoundation\Request::create('/crm/dashboard'))->getContent();
echo 'Dashboard HTML: ' . strlen(\$html) . ' bytes';
"
```

## 📚 Documentation

- [Content Types](CONTENT_TYPES.md) - Detailed field definitions
- [System Gap Analysis](SYSTEM_GAP_ANALYSIS.md) - Feature roadmap
- [Master Plan](docs/master-plan.md) - Original requirements (if exists)

## 🤝 Contributing

1. Create feature branch: `git checkout -b feature/my-feature`
2. Commit changes: `git commit -am 'Add new feature'`
3. Push to branch: `git push origin feature/my-feature`
4. Submit pull request

## 📝 License

See [LICENSE.txt](LICENSE.txt) for details.

## 🙏 Credits

Built with:

- [Drupal 11](https://www.drupal.org/)
- [Lucide Icons](https://lucide.dev/)
- [Chart.js](https://www.chartjs.org/)
- [SortableJS](https://sortablejs.github.io/Sortable/)
- [Drupal Feeds](https://www.drupal.org/project/feeds)

---

**Version**: 1.0.0  
**Last Updated**: February 27, 2026  
**Maintainer**: Thành Phúc
