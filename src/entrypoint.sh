#!/bin/bash

if [[ "$CRON_ENABLED" == "true" ]] ; then
    crontab -l | { cat; echo "$CRON_SCHEDULE root bash /app/export.sh"; } | crontab -
    crond -f
else
    bash /app/export.sh
fi