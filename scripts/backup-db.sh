#!/bin/bash
#
# Vinyls Stock - Database Backup Script
# Usage: ./scripts/backup-db.sh [keep_days]
# Default: keeps last 7 days of backups
#

set -e

# Config
DB_NAME="vinyls_stock"
DB_USER="root"
DB_PASS="${DB_PASSWORD:-}"
BACKUP_DIR="${HOME}/backups/vinyls-stock"
KEEP_DAYS="${1:-7}"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/vinyls_stock_${DATE}.sql"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Functions
log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Check if running in project directory
if [ ! -f "artisan" ]; then
    log_error "Must run from Laravel project root directory"
    exit 1
fi

# Create backup directory
mkdir -p "${BACKUP_DIR}"

log_info "Starting database backup..."
log_info "Backup location: ${BACKUP_FILE}"

# Get DB credentials from .env if available
if [ -f ".env" ]; then
    DB_NAME=$(grep DB_DATABASE .env | cut -d '=' -f2 | tr -d ' ')
    DB_USER=$(grep DB_USERNAME .env | cut -d '=' -f2 | tr -d ' ')
    DB_PASS=$(grep DB_PASSWORD .env | cut -d '=' -f2 | tr -d ' ')
fi

# Create backup
if mysqldump -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" > "${BACKUP_FILE}" 2>/dev/null; then
    # Compress backup
    gzip "${BACKUP_FILE}"
    FINAL_SIZE=$(du -h "${BACKUP_FILE}.gz" | cut -f1)
    log_info "Backup completed: ${BACKUP_FILE}.gz (${FINAL_SIZE})"
else
    log_error "Backup failed - check database credentials"
    rm -f "${BACKUP_FILE}"
    exit 1
fi

# Cleanup old backups
DELETED_COUNT=$(find "${BACKUP_DIR}" -name "vinyls_stock_*.sql.gz" -mtime +${KEEP_DAYS} | wc -l)
find "${BACKUP_DIR}" -name "vinyls_stock_*.sql.gz" -mtime +${KEEP_DAYS} -delete

if [ ${DELETED_COUNT} -gt 0 ]; then
    log_info "Cleaned up ${DELETED_COUNT} old backup(s) (> ${KEEP_DAYS} days)"
fi

# Show backup stats
BACKUP_COUNT=$(ls -1 "${BACKUP_DIR}"/vinyls_stock_*.sql.gz 2>/dev/null | wc -l)
log_info "Total backups stored: ${BACKUP_COUNT}"
log_info "Backup completed successfully! 🎉"

# Optional: Add to crontab for daily execution
# 0 2 * * * /path/to/vinyls-stock/scripts/backup-db.sh 7