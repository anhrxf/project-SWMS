#!/usr/bin/env bash
set -e

for script in /var/www/html/scripts/*; do
  if [ -f "$script" ]; then
    bash "$script"
  fi
done

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
