#!/bin/bash

# Set variables
DB_NAME="lpo_db"
DB_USER="root"
DB_PASS=""
BACKUP_DIR="../../backups/"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Create backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > "$BACKUP_DIR/${DB_NAME}_$TIMESTAMP.sql"
