#!/bin/bash

# ============================================================================
# Database Backup & Restore Script for OpenCRM DDEV Project
# ============================================================================
# This script simplifies database backup and restore operations
# Usage: ./backup_restore.sh [backup|restore|list|auto-backup]
# ============================================================================

PROJECT_DIR="/Users/phucnguyen/Downloads/open_crm"
BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="db_backup_${TIMESTAMP}.sql.gz"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================================
# Helper Functions
# ============================================================================

print_header() {
    echo -e "\n${BLUE}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Check if DDEV project is running (Bypass ANSI color code issues)
check_ddev_running() {
    cd "$PROJECT_DIR" || { print_error "Directory not found: $PROJECT_DIR"; exit 1; }
    
    # Try to execute a simple command inside the container
    if ! ddev exec "echo 1" >/dev/null 2>&1; then
        print_error "DDEV project 'open-crm' is not running!"
        print_info "Start project with: ddev start"
        exit 1
    fi
    print_success "DDEV project is running"
}

# ============================================================================
# BACKUP FUNCTION
# ============================================================================

backup_database() {
    print_header "DATABASE BACKUP"
    
    check_ddev_running
    
    print_info "Creating database backup..."
    print_info "Target: $BACKUP_DIR/$BACKUP_FILE"
    
    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"
    
    # Create backup
    if ddev export-db --file="$BACKUP_DIR/${BACKUP_FILE%.gz}"; then
        print_success "Database exported"
        
        # Compress
        print_info "Compressing backup file (gzip -9)..."
        gzip -9 "$BACKUP_DIR/${BACKUP_FILE%.gz}" 2>/dev/null
        
        # Get file info
        FILE_SIZE=$(ls -lh "$BACKUP_DIR/$BACKUP_FILE" | awk '{print $5}')
        BACKUP_TIME=$(date '+%Y-%m-%d %H:%M:%S')
        
        print_success "Backup completed successfully!"
        echo -e "\n${BLUE}Backup Details:${NC}"
        echo "  📦 File: $BACKUP_FILE"
        echo "  💾 Size: $FILE_SIZE"
        echo "  🕐 Time: $BACKUP_TIME"
        echo "  📍 Path: $BACKUP_DIR/"
        
        # Update manifest
        echo "Latest: $BACKUP_FILE ($FILE_SIZE) - $BACKUP_TIME" >> "$BACKUP_DIR/BACKUP_LOG.txt"
        
        return 0
    else
        print_error "Failed to create backup"
        return 1
    fi
}

# ============================================================================
# RESTORE FUNCTION
# ============================================================================

restore_database() {
    print_header "DATABASE RESTORE"
    
    if [ -z "$1" ]; then
        print_error "No backup file specified!"
        echo "Usage: $0 restore <backup_file>"
        echo "Example: $0 restore db_backup_20260309_113830.sql.gz"
        list_backups
        return 1
    fi
    
    RESTORE_FILE="$BACKUP_DIR/$1"
    
    if [ ! -f "$RESTORE_FILE" ]; then
        print_error "Backup file not found: $RESTORE_FILE"
        list_backups
        return 1
    fi
    
    check_ddev_running
    
    print_warning "⚠️  This will REPLACE the current database!"
    echo "Backup file: $RESTORE_FILE"
    echo ""
    read -p "Continue? (type 'yes' to confirm): " CONFIRM
    
    if [ "$CONFIRM" != "yes" ]; then
        print_info "Restore cancelled"
        return 0
    fi
    
    print_info "Restoring database from $RESTORE_FILE..."
    
    if ddev db-import "$RESTORE_FILE"; then
        print_success "Database restored successfully!"
        echo ""
        
        # Verify
        print_info "Verifying restoration..."
        RECORD_COUNT=$(ddev mysql -e "SELECT COUNT(*) FROM users;" 2>/dev/null | tail -1)
        print_success "Database has $RECORD_COUNT user records"
        
        return 0
    else
        print_error "Failed to restore database"
        return 1
    fi
}

# ============================================================================
# LIST BACKUPS FUNCTION
# ============================================================================

list_backups() {
    print_header "AVAILABLE BACKUPS"
    
    if [ ! -d "$BACKUP_DIR" ]; then
        print_error "Backup directory not found: $BACKUP_DIR"
        return 1
    fi
    
    echo "📂 Location: $BACKUP_DIR"
    echo ""
    
    # List backups with details
    ls -1 "$BACKUP_DIR"/*.sql.gz 2>/dev/null | sort -r | while read file; do
        filename=$(basename "$file")
        filesize=$(ls -lh "$file" | awk '{print $5}')
        filemtime=$(stat -f %Sm -t "%Y-%m-%d %H:%M:%S" "$file" 2>/dev/null || stat -c %y "$file" 2>/dev/null | cut -d' ' -f1,2)
        
        echo "  📦 $filename"
        echo "     Size: $filesize | Modified: $filemtime"
        echo ""
    done
}

# ============================================================================
# AUTO-BACKUP FUNCTION (for cron)
# ============================================================================

auto_backup() {
    # Run backup silently
    backup_database 2>&1 >> /tmp/crm_auto_backup.log
    
    # Cleanup old backups (keep last 10)
    print_info "Cleaning up old backups..."
    ls -1t "$BACKUP_DIR"/db_backup_*.sql.gz 2>/dev/null | tail -n +11 | while read file; do
        rm -f "$file"
        echo "Removed old backup: $(basename $file)" >> /tmp/crm_auto_backup.log
    done
}

# ============================================================================
# MAIN MENU
# ============================================================================

show_usage() {
    cat << EOF
${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}
${BLUE}║         OpenCRM Database Backup & Restore Tool                 ║${NC}
${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}

${YELLOW}USAGE:${NC}
    $0 [command] [options]

${YELLOW}COMMANDS:${NC}
    backup              Create a new database backup
    restore <file>      Restore database from backup file
    list                List all available backups
    auto-backup         Backup for cron (silent mode)
    help                Show this help message

${YELLOW}EXAMPLES:${NC}
    # Create backup
    $0 backup

    # Restore from specific backup
    $0 restore db_backup_20260309_113830.sql.gz

    # List available backups
    $0 list

    # Setup automatic daily backup (cron)
    0 2 * * * /Users/phucnguyen/Downloads/open_crm/backup_restore.sh auto-backup

${YELLOW}BACKUP LOCATION:${NC}
    $BACKUP_DIR/

${YELLOW}DDEV PROJECT:${NC}
    open-crm (Drupal 10/11)

EOF
}

# ============================================================================
# MAIN EXECUTION
# ============================================================================

case "${1:-help}" in
    backup)
        backup_database
        ;;
    restore)
        restore_database "$2"
        ;;
    list)
        list_backups
        ;;
    auto-backup)
        auto_backup
        ;;
    help|--help|-h)
        show_usage
        ;;
    *)
        print_error "Unknown command: $1"
        show_usage
        exit 1
        ;;
esac

exit $?