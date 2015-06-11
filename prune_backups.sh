#!/bin/bash

# Prune all but the most recent 5 backups.
# See http://stackoverflow.com/questions/25785/delete-all-but-the-most-recent-x-files-in-bash
cd /Users/neil/Sites/ferko/backups/db/
(ls -t|head -n 50;ls)|sort|uniq -u|xargs rm

cd /Users/neil/Sites/ferko/backups/files/
(ls -t|head -n 15;ls)|sort|uniq -u|xargs rm

echo "Backups pruned ON CRON" > /var/log/cron.log