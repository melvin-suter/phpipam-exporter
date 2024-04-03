#!/bin/bash

if [[ "$CRON_ENABLED" == "true" ]] ; then
    crontab -l | { cat; "$CRON_SCHEDULE root bash /app/export.sh"; } | crontab -
    crond
else
    bash /app/export.sh
fi