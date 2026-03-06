#!/bin/bash

###############################################################################
# Database Backup Script for Open CRM
# 
# Usage: 
#   ./backup_database.sh                 # Backup to backups/ directory
#   ./backup_database.sh /path/to/dir    # Backup to specific directory
#
# Requirements:
#   - DDEV must be running (ddev status)
#   - mysqldump or drush installed
###############################################################################

# Set backup directory
BACKUP_DIR="${1:-./../backups}"
TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="$BACKUP_DIR/db_backup_$TIMESTAMP.sql.gz"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}========================================${NC}"
echo -e "${YELLOW}Open CRM Database Backup${NC}"
echo -e "${YELLOW}========================================${NC}"

# Check if DDEV is running
echo "Checking DDEV status..."
if ! ddev status > /dev/null 2>&1; then
  echo -e "${RED}✗ DDEV is not running!${NC}"
  echo "Start DDEV with: ddev start"
  exit 1
fi

echo -e "${GREEN}✓ DDEV is running${NC}"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

# Method 1: Use Drush (preferred)
echo ""
echo "Creating database backup using Drush..."

if ddev exec drush sql:dump --gzip > "$BACKUP_FILE" 2>/dev/null; then
  BACKUP_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
  echo -e "${GREEN}✓ Database backup created successfully!${NC}"
  echo ""
  echo "Backup Details:"
  echo "  File: $BACKUP_FILE"
  echo "  Size: $BACKUP_SIZE"
  echo "  Created: $(date)"
  echo ""
  echo -e "${GREEN}✓ Backup location: $BACKUP_FILE${NC}"
  exit 0
else
  echo -e "${RED}✗ Drush backup failed!${NC}"
  
  # Method 2: Use mysqldump as fallback
  echo ""
  echo "Attempting backup using mysqldump..."
  
  if ddev exec mysqldump -udb -pdb db 2>/dev/null | gzip > "$BACKUP_FILE"; then
    BACKUP_SIZE=$(ls -lh "$BACKUP_FILE" | awk '{print $5}')
    echo -e "${GREEN}✓ Database backup created with mysqldump!${NC}"
    echo ""
    echo "Backup Details:"
    echo "  File: $BACKUP_FILE"
    echo "  Size: $BACKUP_SIZE"
    echo "  Created: $(date)"
    exit 0
  else
    echo -e "${RED}✗ Both backup methods failed!${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "  1. Ensure DDEV is running: ddev status"
    echo "  2. Check database credentials in web/sites/default/settings.php"
    echo "  3. Try: ddev exec drush sql:dump --help"
    exit 1
  fi
fi
