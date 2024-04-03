#!/bin/bash

if [[ "$CRON_ENABLED" == "true" ]] ; then
    echo "$CRON_SCHEDULE root bash /app/export.sh"
    cron
else
    bash /app/export.sh
fi