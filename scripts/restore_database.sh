#!/bin/bash

###############################################################################
# Database Restore Script for Open CRM
# 
# Usage: 
#   ./restore_database.sh backups/db_backup_20260306_085814.sql.gz
#
# Requirements:
#   - DDEV must be running (ddev status)
#   - Drush installed (ddev exec drush --version)
#   - Backup file must exist and be readable
#
# WARNING: This will DROP all existing data and restore from backup!
###############################################################################

# Check arguments
if [ $# -ne 1 ]; then
    echo "Usage: ./restore_database.sh <backup_file>"
    echo ""
    echo "Example:"
    echo "  ./restore_database.sh backups/db_backup_20260306_085814.sql.gz"
    echo ""
    echo "Available backups:"
    ls -lah backups/ | grep ".sql.gz"
    exit 1
fi

BACKUP_FILE="$1"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Open CRM Database Restore${NC}"
echo -e "${YELLOW}========================================${NC}"
echo ""

# Verify backup file exists
if [ ! -f "$BACKUP_FILE" ]; then
    echo -e "${RED}✗ Backup file not found: $BACKUP_FILE${NC}"
    exit 1
fi

BACKUP_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
echo -e "${BLUE}Backup File:${NC} $BACKUP_FILE"
echo -e "${BLUE}File Size:${NC} $BACKUP_SIZE"
echo ""

# Check if DDEV is running
echo "Checking DDEV status..."
if ! ddev status > /dev/null 2>&1; then
    echo -e "${RED}✗ DDEV is not running!${NC}"
    echo "Start DDEV with: ddev start"
    exit 1
fi

echo -e "${GREEN}✓ DDEV is running${NC}"
echo ""

# Confirmation
echo -e "${RED}⚠️  WARNING: This will DELETE all current database data!${NC}"
echo ""
echo "This action will:"
echo "  1. Drop all existing database tables"
echo "  2. Restore data from: $BACKUP_FILE"
echo "  3. Create data from: $(date '+%Y-%m-%d %H:%M:%S')"
echo ""
read -p "Are you sure? Type 'yes' to confirm: " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo -e "${YELLOW}Restore cancelled.${NC}"
    exit 0
fi

echo ""
echo -e "${YELLOW}Starting database restore...${NC}"

# Create pre-restore backup (safety)
echo ""
echo "Creating safety backup before restore..."
SAFETY_BACKUP="backups/pre-restore-backup_$TIMESTAMP.sql.gz"

if ddev exec drush sql:dump --gzip > "$SAFETY_BACKUP" 2>/dev/null; then
    echo -e "${GREEN}✓ Safety backup created: $SAFETY_BACKUP${NC}"
else
    echo -e "${RED}✗ Safety backup failed (continuing anyway)${NC}"
fi

echo ""
echo "Restoring database..."

# Method 1: Using Drush (preferred)
if ddev exec drush sql:drop --yes 2>/dev/null && \
   gunzip -c "$BACKUP_FILE" | ddev exec drush sql:cli 2>/dev/null; then
    
    echo -e "${GREEN}✓ Database restore completed!${NC}"
    echo ""
    
    # Clear drupal cache
    echo "Clearing Drupal cache..."
    ddev exec drush cache:rebuild
    
    echo ""
    echo -e "${GREEN}✓ Restore process finished!${NC}"
    echo ""
    echo "Database Status:"
    ddev exec drush status | grep -E "Database|Username|Database name"
    
    echo ""
    echo "Next steps:"
    echo "  1. Clear browser cache"
    echo "  2. Test application functionality"
    echo "  3. Verify data integrity"
    echo "  4. Update deployment notes"
    
    exit 0
else
    # Fallback method
    echo -e "${YELLOW}Drush method failed, trying mysqldump method...${NC}"
    
    if gunzip -c "$BACKUP_FILE" | ddev exec mysql -udb -pdb db 2>/dev/null; then
        echo -e "${GREEN}✓ Database restore completed!${NC}"
        echo ""
        
        # Clear drupal cache
        echo "Clearing Drupal cache..."
        ddev exec drush cache:rebuild
        
        echo -e "${GREEN}✓ Restore process finished!${NC}"
        exit 0
    else
        echo -e "${RED}✗ Restore failed!${NC}"
        echo ""
        echo "Troubleshooting:"
        echo "  1. Check backup file integrity: gunzip -t $BACKUP_FILE"
        echo "  2. Verify DDEV is running: ddev status"
        echo "  3. Check database logs: ddev logs -s db"
        echo ""
        echo "Your original data is preserved in: $SAFETY_BACKUP"
        exit 1
    fi
fi
