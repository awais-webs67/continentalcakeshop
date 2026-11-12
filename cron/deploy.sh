#!/bin/sh
# Keeps the live site the same as GitHub (branch main). A cPanel cron job runs it every 5 minutes:
#   /bin/sh /home/CPANELUSER/public_html/cron/deploy.sh >/dev/null 2>&1
# It only changes files that are in GitHub. config/config.php, storage/ (app.key, logs, backups,
# payment screenshots) and photos uploaded in the admin panel are not in GitHub, so they are kept.
# Database changes that come with an update run by themselves on the next visit to the site.

PATH=/usr/local/cpanel/3rdparty/bin:/usr/local/bin:/usr/bin:/bin:$PATH
cd "$(dirname "$0")/.." || exit 1

git fetch -q origin main || exit 1
if [ "$(git rev-parse HEAD)" = "$(git rev-parse origin/main)" ]; then
    exit 0 # nothing new
fi
git reset -q --hard origin/main &&
    echo "$(date '+%Y-%m-%d %H:%M:%S') updated to $(git rev-parse --short HEAD): $(git log -1 --format=%s)" >> storage/logs/deploy.log
