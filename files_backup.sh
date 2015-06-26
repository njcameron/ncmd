#!/bin/bash
PATH_TO_BACKUP=/Users/neil/Sites/ferko/sites/default
BACKUP_FILE=/Users/neil/Sites/ferko/backups/files/ferko_files_backup.`date +"%Y-%m-%d-%H%M"`.tar.gz

/usr/bin/tar -cpzf $BACKUP_FILE $PATH_TO_BACKUP

echo "$BACKUP_FILE was created ON CRON" > /var/log/cron.log