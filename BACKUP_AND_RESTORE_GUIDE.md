# Open CRM - Database Backup & Restore Guide

**Last Updated:** March 6, 2026  
**Version:** 1.0

---

## 📋 Overview

This guide explains how to backup and restore the Open CRM database for production and development environments.

**Key Points:**
- ✅ Backup script: `scripts/backup_database.sh`
- ✅ Requires DDEV and Drush
- ✅ Uses gzip compression (reduces file size by 80%)
- ✅ Backups stored in: `backups/` directory
- ✅ Backups directory is `.gitignore`'d (not committed to git for security)

---

## 🔄 Quick Start

### Create a Backup

```bash
# Make script executable
chmod +x scripts/backup_database.sh

# Run backup
./scripts/backup_database.sh

# Result: backups/db_backup_20260306_085814.sql.gz
```

**Requirements:**
- ✓ DDEV running: `ddev status`
- ✓ Drush installed: `ddev exec drush --version`
- ✓ Database credentials configured

---

## 📦 Backup Process (Step by Step)

### Step 1: Ensure DDEV is Running

```bash
ddev status
```

If not running:
```bash
ddev start
ddev launch  # Opens web browser
```

---

### Step 2: Create Backup

```bash
# Method 1: Using bash script (RECOMMENDED)
./scripts/backup_database.sh

# Method 2: Manual using Drush
ddev exec drush sql:dump --gzip > backups/db_backup_$(date '+%Y%m%d_%H%M%S').sql.gz

# Method 3: Manual using mysqldump
ddev exec mysqldump -udb -pdb db | gzip > backups/db_backup_$(date '+%Y%m%d_%H%M%S').sql.gz
```

---

### Step 3: Verify Backup

```bash
# List recent backups
ls -lah backups/

# Check backup integrity
gunzip -t backups/db_backup_20260306_085814.sql.gz
# Output: backups/db_backup_20260306_085814.sql.gz: OK
```

---

## 🔙 Restore Process

### Restore from Backup

```bash
# Method 1: Using Drush (RECOMMENDED)
ddev exec drush sql:drop --yes
ddev exec drush sql:cli < <(gunzip -c backups/db_backup_20260306_085814.sql.gz)

# Method 2: Manual using Drush
gunzip -c backups/db_backup_20260306_085814.sql.gz | ddev exec drush sql:cli

# Method 3: Using mysql directly
gunzip -c backups/db_backup_20260306_085814.sql.gz | ddev exec mysql -udb -pdb db
```

---

### Verify Restore

```bash
# Check database is restored
ddev exec drush status

# Count entities
ddev exec drush sql:query "SELECT COUNT(*) FROM node;"
ddev exec drush sql:query "SELECT COUNT(*) FROM users;"
```

---

## 📅 Automated Backups (Recommended Setup)

### Option 1: Cron Job (Local Development)

Add to crontab:
```bash
crontab -e

# Add this line (backup daily at 2 AM):
0 2 * * * cd /Users/phucnguyen/Downloads/open_crm && ./scripts/backup_database.sh
```

---

### Option 2: GitHub Actions (Production)

Create `.github/workflows/backup.yml`:

```yaml
name: Daily Database Backup

on:
  schedule:
    - cron: '0 2 * * *'  # 2 AM UTC every day

jobs:
  backup:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Create backup
        run: |
          # Your backup command here
          # This would need database access credentials
          
      - name: Upload to S3 (optional)
        run: |
          aws s3 cp backup.sql.gz s3://your-bucket/backups/
        env:
          AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY }}
          AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_KEY }}
      
      - name: Commit backup info
        run: |
          git config user.name "Backup Bot"
          git config user.email "backup@example.com"
          echo "Backup created: $(date)" >> BACKUP_LOG.md
          git add BACKUP_LOG.md
          git commit -m "Backup created $(date '+%Y-%m-%d')"
          git push
```

---

## 📊 Backup File Information

### File Size

```
Database Size vs Backup Size
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Item              | Uncompressed | Compressed
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Full database     | ~6 MB        | ~1.2 MB
Contacts only     | ~3 MB        | ~0.6 MB
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Compression: 80% reduction
```

### Backup Naming Convention

```
db_backup_YYYYMMDD_HHMMSS.sql.gz
           ↑         ↑
      Date part  Time part

Example:
db_backup_20260306_085814.sql.gz
           │       │
      March 6, 2026 at 08:58:14
```

---

## 🛡️ Backup Best Practices

### Do's ✅

```bash
✓ Create backup before major changes
✓ Test restore process regularly
✓ Store backups in multiple locations
✓ Keep recent 7-day backups locally
✓ Archive old backups monthly
✓ Document backup schedule
✓ Monitor backup file sizes
✓ Verify backup integrity
```

### Don'ts ❌

```bash
✗ Never backup to production server only
✗ Don't store credentials in backup metadata
✗ Avoid uncompressed backups (wastes space)
✗ Don't rely on single backup copy
✗ Never backup sensitive data with production credentials
✗ Don't forget to test restores
✗ Avoid leaving backups unencrypted on shared systems
```

---

## 🚨 Disaster Recovery Plan

### Scenario: Database Corrupted

```
1. DETECT CORRUPTION
   └─ ddev exec drush status
      └─ Error: Database connection failed
      
2. IDENTIFY LATEST GOOD BACKUP
   └─ ls -lah backups/
   └─ Choose: db_backup_20260305_000000.sql.gz
   
3. RESTORE FROM BACKUP
   └─ ./scripts/restore_database.sh db_backup_20260305_000000.sql.gz
   
4. VERIFY RESTORATION
   └─ ddev exec drush status
   └─ ddev exec drush cr  # Clear cache
   
5. NOTIFY TEAM
   └─ Update deployment notes
   └─ Document incident
```

---

### Scenario: Accidental Data Deletion

```
1. STOP IMMEDIATE OPERATIONS
   └─ Prevent further changes to corrupted data
   
2. ASSESS DATA LOSS
   └─ How much data was deleted?
   └─ When did it happen?
   
3. FIND APPROPRIATE BACKUP
   └─ Use most recent backup BEFORE deletion
   └─ Example: If deleted at 3 PM on March 6
   │        └─ Use backup from 2 PM on March 6
   │        └─ Or 11 PM on March 5
   
4. RESTORE AND MERGE
   └─ Restore from backup
   └─ Manually re-add any changes after deletion
   
5. DOCUMENT INCIDENT
   └─ Update INCIDENT_LOG.md
   └─ Review process to prevent recurrence
```

---

## 🔗 Backup Retention Policy

### Recommended Schedule

```
RETENTION POLICY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Frequency  | Keep For | Location
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Hourly     | 24 hours | backups/
Daily      | 7 days   | backups/
Weekly     | 4 weeks  | backups/archive/
Monthly    | 1 year   | Cloud storage (S3/GCS)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

### Clean Up Old Backups

```bash
# Keep only last 7 daily backups
cd backups/
ls -t db_backup_*.sql.gz | tail -n +8 | xargs rm

# Archive older backups
mkdir -p archive/$(date +%Y-%m)
find . -name "db_backup_*.sql.gz" -mtime +7 -exec mv {} archive/$(date +%Y-%m)/ \;
```

---

## 📝 Backup Checklist

Before major operations:

- [ ] Create fresh backup: `./scripts/backup_database.sh`
- [ ] Verify backup file created: `ls -lah backups/`
- [ ] Test restore (use copy): `cp backups/latest.sql.gz test-restore.sql.gz`
- [ ] Document changes and backup file name
- [ ] Notify team of maintenance window
- [ ] Have rollback plan ready

After operations:

- [ ] Verify all systems working
- [ ] Check data integrity
- [ ] Create final backup: `./scripts/backup_database.sh`
- [ ] Update deployment notes
- [ ] Archive backup if needed

---

## 🔧 Troubleshooting

### Issue: DDEV Not Running

```bash
Error: Docker daemon not running

Solution:
1. Start Docker Desktop
2. Run: ddev start
3. Verify: ddev status
```

### Issue: Drush Not Available

```bash
Error: drush: command not found

Solution 1 (DDEV):
ddev exec drush --version

Solution 2 (Install Drush):
ddev composer require drush/drush
ddev exec drush --version
```

### Issue: Database Access Denied

```bash
Error: Access denied for user 'db'@'localhost'

Solution 1: Check credentials in settings.php
cat web/sites/default/settings.php | grep -A 5 "databases"

Solution 2: Reset DDEV
ddev delete
ddev start
ddev drush site:install --existing-config -y
```

### Issue: Backup File Corrupted

```bash
Error: gzip: unexpected end of file

Cause: Backup process interrupted

Solution:
1. Delete corrupted file: rm backups/db_backup_*.sql.gz
2. Create new backup: ./scripts/backup_database.sh
3. Verify: gunzip -t backups/db_backup_*.sql.gz
```

---

## 📞 Support

For issues or questions:

1. Check DDEV docs: https://ddev.readthedocs.io
2. Check Drush docs: https://www.drush.org
3. Review Drupal database backup guide: https://www.drupal.org/docs/administering-drupal-site/backing-database

---

## 📋 Related Files

- **Backup Script:** `scripts/backup_database.sh`
- **Restore Script:** `scripts/restore_database.sh` (planned)
- **DDEV Config:** `.ddev/config.yaml`
- **Settings:** `web/sites/default/settings.php`
- **Backups Directory:** `backups/`

---

**Document Version:** 1.0  
**Last Updated:** March 6, 2026  
**Author:** Open CRM Team  
**Audience:** Developers, DevOps Engineers, Site Administrators
