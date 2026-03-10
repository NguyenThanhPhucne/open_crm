# 🗄️ CRM Database Backup & Recovery Guide

**Last Updated:** March 9, 2026  
**Latest Backup:** `db_backup_20260309_113830.sql.gz` (1.4 MB)

---

## 📋 Quick Summary

✅ **Database Backup Created Successfully**

- **File:** `db_backup_20260309_113830.sql.gz`
- **Location:** `/Users/phucnguyen/Downloads/open_crm/backups/`
- **Size:** 1.4 MB (compressed)
- **Time:** March 9, 2026 11:38:30 UTC
- **Database:** open_crm (MariaDB 11.8 via DDEV)

**Contains:**

- All Drupal core data
- All CRM entities (Contacts, Organizations, Deals, Activities)
- All configurations and permissions
- Dashboard enhancement metrics
- User accounts and assignments

---

## 🚀 Quick Restore (One Command)

```bash
cd /Users/phucnguyen/Downloads/open_crm
./backup_restore.sh restore db_backup_20260309_113830.sql.gz
```

Or using DDEV directly:

```bash
ddev db-import backups/db_backup_20260309_113830.sql.gz
```

---

## 📖 Detailed Usage Guide

### 1. **Create a New Backup** (Recommended before major changes)

```bash
cd /Users/phucnguyen/Downloads/open_crm
./backup_restore.sh backup
```

**Output:**

```
✅ Database exported
Compressing backup file (gzip -9)...
✅ Backup completed successfully!

Backup Details:
  📦 File: db_backup_20260309_113931.sql.gz
  💾 Size: 1.4M
  🕐 Time: 2026-03-09 11:39:31
  📍 Path: /Users/phucnguyen/Downloads/open_crm/backups/
```

### 2. **List All Available Backups**

```bash
./backup_restore.sh list
```

**Output:**

```
📂 Location: /Users/phucnguyen/Downloads/open_crm/backups

📦 db_backup_20260309_113830.sql.gz
   Size: 1.4M | Modified: 2026-03-09 11:38:30

📦 db_backup_20260306_131554.sql.gz
   Size: 1.3M | Modified: 2026-03-06 13:15:54
```

### 3. **Restore from Backup** (⚠️ WARNING: Replaces current data)

```bash
./backup_restore.sh restore db_backup_20260309_113830.sql.gz
```

**Security Prompt:**

```
⚠️  This will REPLACE the current database!
Backup file: /Users/phucnguyen/Downloads/open_crm/backups/db_backup_20260309_113830.sql.gz

Continue? (type 'yes' to confirm): yes
```

### 4. **Show Help**

```bash
./backup_restore.sh help
```

---

## ⚙️ Advanced Usage

### Manual Backup Using DDEV

```bash
ddev export-db --file=backups/manual_dump.sql
```

### Manual Restore Using DDEV

```bash
ddev db-import backups/db_backup_20260309_113830.sql.gz
```

### Verify Database Integrity

```bash
# Check user count
ddev mysql -e "SELECT COUNT(*) FROM users;"

# Check contacts count
ddev mysql -e "SELECT COUNT(*) FROM node WHERE type='contact';"

# Check deals
ddev mysql -e "SELECT COUNT(*) FROM node WHERE type='deal';"

# Check activities
ddev mysql -e "SELECT COUNT(*) FROM node WHERE type='activity';"
```

### Check DDEV Status

```bash
ddev status
ddev logs -s db  # Database container logs
ddev logs -s web # Web server logs
```

---

## 🔄 Setup Automatic Daily Backup

Add this to your `crontab` for automatic backups at 2 AM daily:

```bash
crontab -e
```

Add this line:

```
0 2 * * * /Users/phucnguyen/Downloads/open_crm/backup_restore.sh auto-backup
```

**Features:**

- Runs silently (output to log file)
- Automatically compresses backup
- Keeps only last 10 backups
- Removes old backups to save disk space
- Logs to `/tmp/crm_auto_backup.log`

Check cron logs:

```bash
tail -f /tmp/crm_auto_backup.log
```

---

## 📊 Backup Contents

### Database Tables Included

- ✅ users (User accounts)
- ✅ node (Content - contacts, organizations, deals, activities)
- ✅ node\_\_\* (Field data for all node fields)
- ✅ field\_\* (Field storage)
- ✅ taxonomy_term (Pipeline stages, activity types)
- ✅ role (User roles and permissions)
- ✅ user\_\_\* (User field data)
- ✅ config (Module configurations)

### CRM Entities Included

- **Contacts** - All contact nodes with fields
- **Organizations** - All organization nodes
- **Deals** - All deals with amounts, stages, probabilities
- **Activities** - All activity records with assignments
- **Users & Teams** - Staff assignments and permissions

### Configurations Included

- ✅ Drupal settings
- ✅ Field configurations
- ✅ Views (contact, deal, activity views)
- ✅ Dashboard metrics
- ✅ Role-based access control (RBAC)
- ✅ User permission settings

---

## 🛡️ Backup Security

### File Permissions

```bash
ls -l backups/db_backup_*.sql.gz
# -rw-r--r-- (owner can read/write, others can read)
```

### Recommended Security Measures

1. **Restrict Permissions** (if sensitive)

```bash
chmod 600 backups/db_backup_*.sql.gz  # Owner only
```

2. **Encrypt Sensitive Backups**

```bash
# Encrypt backup
openssl enc -aes-256-cbc -salt -in backups/db_backup_20260309_113830.sql.gz \
  -out backups/db_backup_20260309_113830.sql.gz.enc -k mypassword

# Decrypt for restore
openssl enc -aes-256-cbc -d -in backups/db_backup_20260309_113830.sql.gz.enc \
  -out backups/db_backup_20260309_113830.sql.gz -k mypassword
```

3. **Backup to External Drive**

```bash
cp backups/db_backup_20260309_113830.sql.gz /Volumes/BackupDrive/
```

---

## 📈 Backup Size Reference

| Backup File                      | Size   | Entities      | Date       |
| -------------------------------- | ------ | ------------- | ---------- |
| db_backup_20260309_113830.sql.gz | 1.4 MB | Full Database | 2026-03-09 |
| db_backup_20260306_131554.sql.gz | 1.3 MB | Full Database | 2026-03-06 |

Database grows by **~0.1 MB per 1000 records** approximately.

---

## 🚨 Disaster Recovery Steps

### Scenario 1: Data Accidentally Deleted

1. **Identify issue:** Realize data was deleted
2. **List backups:** `./backup_restore.sh list`
3. **Choose backup:** From before deletion
4. **Restore:** `./backup_restore.sh restore db_backup_20260309_113830.sql.gz`
5. **Verify:** `ddev mysql -e "SELECT COUNT(*) FROM node WHERE type='contact';"`

### Scenario 2: Database Corrupted

1. **Check status:** `ddev status` (if containers OK)
2. **Restart containers:** `ddev restart`
3. **Import backup:** `ddev db-import backups/db_backup_20260309_113830.sql.gz`
4. **Clear cache:** `ddev exec drush cache:rebuild` (if drush available)

### Scenario 3: Server Issues

1. **Backup is portable:** Copy `.sql.gz` file to new server
2. **Setup DDEV:** `ddev start` on new location
3. **Import:** `ddev db-import backups/db_backup_20260309_113830.sql.gz`

---

## 📝 Backup Log

All backup operations are logged in:

- **Location:** `backups/BACKUP_LOG.txt`
- **Manifest:** `backups/BACKUP_MANIFEST_20260309.txt`

View log:

```bash
cat /Users/phucnguyen/Downloads/open_crm/backups/BACKUP_LOG.txt
```

---

## 🔍 Troubleshooting

### Problem: "DDEV project is not running"

```bash
# Start DDEV
ddev start

# Check status
ddev status
```

### Problem: "Permission denied" on backup file

```bash
# Fix permissions
chmod 644 backups/db_backup_*.sql.gz

# Or for script
chmod +x backup_restore.sh
```

### Problem: Restore fails with "Error establishing connection"

```bash
# Restart database container
ddev restart

# Check database logs
ddev logs -s db | head -50
```

### Problem: File too large to transfer

```bash
# Split into smaller chunks
split -b 500m backups/db_backup_20260309_113830.sql.gz backup_chunk_

# Reassemble
cat backup_chunk_* > backups/db_backup_20260309_113830.sql.gz
```

---

## ✅ Best Practices

1. **Backup Before Major Changes**
   - Before updating modules
   - Before running migrations
   - Before major data imports

2. **Test Restores Regularly**
   - Monthly: Verify backups are valid
   - Restore to test environment
   - Confirm all data intact

3. **Keep Multiple Backups**
   - Daily automatic backups
   - Weekly full manual backups
   - Monthly archived backups

4. **Document Recovery Process**
   - Know where backups are stored
   - Test recovery procedures
   - Keep documentation updated

5. **Monitor Backup Completion**
   - Create backup before major changes
   - Verify file size is reasonable
   - Check modification date

---

## 📞 Support & Resources

- **DDEV Docs:** https://ddev.readthedocs.io/
- **MySQL Docs:** https://dev.mysql.com/doc/
- **Drupal Docs:** https://www.drupal.org/docs
- **Local Project:** `/Users/phucnguyen/Downloads/open_crm/`

---

## 🎯 Summary

| Task           | Command                              | Time       |
| -------------- | ------------------------------------ | ---------- |
| Create backup  | `./backup_restore.sh backup`         | ~10 sec    |
| Restore backup | `./backup_restore.sh restore <file>` | ~15-30 sec |
| List backups   | `./backup_restore.sh list`           | ~2 sec     |
| View help      | `./backup_restore.sh help`           | ~1 sec     |

**Remember:** Backups are your safety net. Create them frequently, test them sometimes, restore only when needed!

---

_Last Updated: March 9, 2026 11:38:30 UTC_
